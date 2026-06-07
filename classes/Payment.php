<?php
/**
 * Payment Class
 * Handles Razorpay payments and subscriptions
 */

class Payment {
    private $db;
    private $razorpay_key;
    private $razorpay_secret;
    
    public function __construct() {
        $this->db = new Database();
        $this->razorpay_key = RAZORPAY_KEY_ID;
        $this->razorpay_secret = RAZORPAY_KEY_SECRET;
    }
    
    /**
     * Create order
     */
    public function createOrder($userId, $amount, $description = '') {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.razorpay.com/v1/orders",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => $amount * 100,
                'currency' => 'INR',
                'receipt' => 'receipt_' . time(),
                'description' => $description
            ]),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->razorpay_key . ":" . $this->razorpay_secret,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            return ['success' => false, 'message' => 'Error: ' . $err];
        }
        
        $response = json_decode($response, true);
        
        if (isset($response['id'])) {
            // Store order in database
            $this->db->query("INSERT INTO payments (user_id, razorpay_order_id, amount, currency, status, description) VALUES (?, ?, ?, ?, ?, ?)");
            $this->db->bind("i", $userId);
            $this->db->bind("s", $response['id']);
            $this->db->bind("s", $amount);
            $this->db->bind("s", 'INR');
            $this->db->bind("s", 'pending');
            $this->db->bind("s", $description);
            $this->db->execute();
            
            return ['success' => true, 'order' => $response];
        }
        
        return ['success' => false, 'message' => 'Failed to create order'];
    }
    
    /**
     * Verify payment signature
     */
    public function verifyPayment($paymentId, $orderId, $signature) {
        $generated_signature = hash_hmac('sha256', $orderId . "|" . $paymentId, $this->razorpay_secret);
        
        if ($generated_signature === $signature) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Complete payment
     */
    public function completePayment($userId, $paymentId, $orderId, $signature) {
        if (!$this->verifyPayment($paymentId, $orderId, $signature)) {
            return ['success' => false, 'message' => 'Invalid signature'];
        }
        
        // Update payment status
        $this->db->query("UPDATE payments SET razorpay_payment_id = ?, razorpay_signature = ?, status = ? WHERE razorpay_order_id = ? AND user_id = ?");
        $this->db->bind("s", $paymentId);
        $this->db->bind("s", $signature);
        $this->db->bind("s", "completed");
        $this->db->bind("s", $orderId);
        $this->db->bind("i", $userId);
        $this->db->execute();
        
        // Create subscription
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime('+1 year'));
        
        $this->db->query("INSERT INTO subscriptions (user_id, plan_type, status, start_date, end_date, auto_renewal) VALUES (?, ?, ?, ?, ?, 1)");
        $this->db->bind("i", $userId);
        $this->db->bind("s", "premium");
        $this->db->bind("s", "active");
        $this->db->bind("s", $startDate);
        $this->db->bind("s", $endDate);
        $this->db->execute();
        
        $subscriptionId = $this->db->lastId();
        
        // Update user plan
        $this->db->query("UPDATE users SET plan_type = ?, subscription_id = ?, subscription_expires = ? WHERE id = ?");
        $this->db->bind("s", "premium");
        $this->db->bind("i", $subscriptionId);
        $this->db->bind("s", $endDate);
        $this->db->bind("i", $userId);
        $this->db->execute();
        
        // Update payment with subscription ID
        $this->db->query("UPDATE payments SET subscription_id = ? WHERE razorpay_payment_id = ?");
        $this->db->bind("i", $subscriptionId);
        $this->db->bind("s", $paymentId);
        $this->db->execute();
        
        return ['success' => true, 'message' => 'Payment successful'];
    }
    
    /**
     * Get payment history
     */
    public function getPaymentHistory($userId) {
        $this->db->query("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC");
        $this->db->bind("i", $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Get subscription status
     */
    public function getSubscriptionStatus($userId) {
        $this->db->query("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $this->db->bind("i", $userId);
        return $this->db->single();
    }
    
    /**
     * Check if subscription is active
     */
    public function isSubscriptionActive($userId) {
        $this->db->query("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date > NOW()");
        $this->db->bind("i", $userId);
        $subscription = $this->db->single();
        
        return $subscription ? true : false;
    }
    
    /**
     * Cancel subscription
     */
    public function cancelSubscription($userId) {
        $this->db->query("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            // Update user plan to free
            $this->db->query("UPDATE users SET plan_type = 'free' WHERE id = ?");
            $this->db->bind("i", $userId);
            $this->db->execute();
            
            return ['success' => true, 'message' => 'Subscription cancelled'];
        }
        
        return ['success' => false, 'message' => 'Cancel failed'];
    }
    
    /**
     * Generate invoice
     */
    public function generateInvoice($paymentId) {
        $this->db->query("SELECT p.*, u.email, u.first_name, u.last_name FROM payments p JOIN users u ON p.user_id = u.id WHERE p.razorpay_payment_id = ?");
        $this->db->bind("s", $paymentId);
        $payment = $this->db->single();
        
        if (!$payment) {
            return null;
        }
        
        $invoiceNumber = 'INV-' . date('YmdHis', strtotime($payment['created_at'])) . '-' . $payment['user_id'];
        
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 800px; margin: 0 auto; padding: 20px; }
                .header { border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
                .company-name { font-size: 28px; font-weight: bold; }
                .invoice-details { margin-bottom: 20px; }
                .label { font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
                .total { font-weight: bold; font-size: 18px; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='company-name'>" . SITE_NAME . "</div>
                    <div>" . SITE_TAGLINE . "</div>
                </div>
                
                <div class='invoice-details'>
                    <div><span class='label'>Invoice Number:</span> " . $invoiceNumber . "</div>
                    <div><span class='label'>Date:</span> " . date('Y-m-d H:i:s', strtotime($payment['created_at'])) . "</div>
                    <div><span class='label'>Status:</span> " . strtoupper($payment['status']) . "</div>
                </div>
                
                <div>
                    <div class='label'>Bill To:</div>
                    <div>" . $payment['first_name'] . " " . $payment['last_name'] . "</div>
                    <div>" . $payment['email'] . "</div>
                </div>
                
                <table>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                    <tr>
                        <td>Premium Plan Subscription (1 Year)</td>
                        <td>" . CURRENCY_SYMBOL . " " . $payment['amount'] . "</td>
                    </tr>
                    <tr>
                        <td class='total'>TOTAL</td>
                        <td class='total'>" . CURRENCY_SYMBOL . " " . $payment['amount'] . "</td>
                    </tr>
                </table>
                
                <div class='footer'>
                    <p>Thank you for your purchase!</p>
                    <p>" . ADMIN_EMAIL . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Apply coupon
     */
    public function applyCoupon($couponCode, $amount) {
        $this->db->query("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())");
        $this->db->bind("s", $couponCode);
        $coupon = $this->db->single();
        
        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid coupon code'];
        }
        
        if ($coupon['max_uses'] && $coupon['current_uses'] >= $coupon['max_uses']) {
            return ['success' => false, 'message' => 'Coupon expired'];
        }
        
        if ($amount < $coupon['min_purchase_amount']) {
            return ['success' => false, 'message' => 'Minimum purchase amount not met'];
        }
        
        $discount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($amount * $coupon['discount_value']) / 100;
        } else {
            $discount = $coupon['discount_value'];
        }
        
        $finalAmount = $amount - $discount;
        
        return ['success' => true, 'discount' => $discount, 'final_amount' => $finalAmount, 'coupon_id' => $coupon['id']];
    }
}
?>
