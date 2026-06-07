<?php
/**
 * User Class
 * Handles user registration, login, and profile management
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        $this->db->query("SELECT email FROM users WHERE email = ?");
        $this->db->bind("s", $data['email']);
        
        if ($this->db->single()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        $this->db->query("SELECT username FROM users WHERE username = ?");
        $this->db->bind("s", $data['username']);
        
        if ($this->db->single()) {
            return ['success' => false, 'message' => 'Username already taken'];
        }
        
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $verificationToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $this->db->query("INSERT INTO users (username, email, password, verification_token, verification_token_expires, is_verified, plan_type) VALUES (?, ?, ?, ?, ?, 0, 'free')");
        $this->db->bind("s", $data['username']);
        $this->db->bind("s", $data['email']);
        $this->db->bind("s", $hashedPassword);
        $this->db->bind("s", $verificationToken);
        $this->db->bind("s", $expiresAt);
        
        if ($this->db->execute()) {
            $userId = $this->db->lastId();
            
            // Create user profile
            $slug = strtolower($data['username']);
            $this->db->query("INSERT INTO profiles (user_id, slug, title, is_published) VALUES (?, ?, ?, 0)");
            $this->db->bind("i", $userId);
            $this->db->bind("s", $slug);
            $this->db->bind("i", 0);
            $this->db->execute();
            
            // Send verification email
            $this->sendVerificationEmail($data['email'], $verificationToken);
            
            return ['success' => true, 'message' => 'Registration successful. Please verify your email.'];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $this->db->query("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $this->db->bind("s", $email);
        
        $user = $this->db->single();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Account locked. Try again later.'];
        }
        
        if (!password_verify($password, $user['password'])) {
            $attempts = $user['login_attempts'] + 1;
            $lockedUntil = $attempts >= MAX_LOGIN_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOCKOUT_TIME) : null;
            
            $this->db->query("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
            $this->db->bind("i", $attempts);
            $this->db->bind("s", $lockedUntil);
            $this->db->bind("i", $user['id']);
            $this->db->execute();
            
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if (!$user['is_verified']) {
            return ['success' => false, 'message' => 'Please verify your email first'];
        }
        
        // Reset login attempts
        $lastLogin = date('Y-m-d H:i:s');
        $this->db->query("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = ? WHERE id = ?");
        $this->db->bind("s", $lastLogin);
        $this->db->bind("i", $user['id']);
        $this->db->execute();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['plan_type'] = $user['plan_type'];
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $this->db->query("SELECT * FROM users WHERE id = ?");
        $this->db->bind("i", $id);
        return $this->db->single();
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $this->db->query("SELECT * FROM users WHERE username = ?");
        $this->db->bind("s", $username);
        return $this->db->single();
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($token) {
        $this->db->query("SELECT id FROM users WHERE verification_token = ? AND verification_token_expires > NOW()");
        $this->db->bind("s", $token);
        
        $user = $this->db->single();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired verification token'];
        }
        
        $userId = $user['id'];
        $this->db->query("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expires = NULL WHERE id = ?");
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Email verified successfully'];
        }
        
        return ['success' => false, 'message' => 'Verification failed'];
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $this->db->query("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
        $this->db->bind("s", $data['first_name']);
        $this->db->bind("s", $data['last_name']);
        $this->db->bind("s", $data['phone']);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Profile updated'];
        }
        
        return ['success' => false, 'message' => 'Update failed'];
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->query("UPDATE users SET password = ? WHERE id = ?");
        $this->db->bind("s", $hashedPassword);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        return ['success' => false, 'message' => 'Password change failed'];
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $token) {
        $link = SITE_URL . "/verify-email?token=" . $token;
        $subject = "Verify your email - " . SITE_NAME;
        
        $message = "
        <html>
        <body>
        <h2>Welcome to " . SITE_NAME . "!</h2>
        <p>Please verify your email by clicking the link below:</p>
        <p><a href='" . $link . "'>Verify Email</a></p>
        <p>This link expires in 24 hours.</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . ADMIN_EMAIL . "\r\n";
        
        mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email) {
        $user = $this->db->query("SELECT id FROM users WHERE email = ?");
        $this->db->bind("s", $email);
        $user = $this->db->single();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }
        
        $resetToken = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->db->query("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $this->db->bind("i", $user['id']);
        $this->db->bind("s", $resetToken);
        $this->db->bind("s", $expiresAt);
        
        if ($this->db->execute()) {
            $link = SITE_URL . "/reset-password?token=" . $resetToken;
            $subject = "Reset your password - " . SITE_NAME;
            
            $message = "
            <html>
            <body>
            <h2>Password Reset Request</h2>
            <p>Click the link below to reset your password:</p>
            <p><a href='" . $link . "'>Reset Password</a></p>
            <p>This link expires in 1 hour.</p>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . ADMIN_EMAIL . "\r\n";
            
            mail($email, $subject, $message, $headers);
            
            return ['success' => true, 'message' => 'Password reset link sent to your email'];
        }
        
        return ['success' => false, 'message' => 'Failed to send reset email'];
    }
    
    /**
     * Reset password
     */
    public function resetPassword($token, $password) {
        $this->db->query("SELECT user_id FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used_at IS NULL");
        $this->db->bind("s", $token);
        
        $row = $this->db->single();
        
        if (!$row) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $userId = $row['user_id'];
        
        $this->db->query("UPDATE users SET password = ? WHERE id = ?");
        $this->db->bind("s", $hashedPassword);
        $this->db->bind("i", $userId);
        $this->db->execute();
        
        $usedAt = date('Y-m-d H:i:s');
        $this->db->query("UPDATE password_reset_tokens SET used_at = ? WHERE token = ?");
        $this->db->bind("s", $usedAt);
        $this->db->bind("s", $token);
        $this->db->execute();
        
        return ['success' => true, 'message' => 'Password reset successfully'];
    }
    
    /**
     * Delete account
     */
    public function deleteAccount($userId, $password) {
        $user = $this->getUserById($userId);
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Password is incorrect'];
        }
        
        $deletedAt = date('Y-m-d H:i:s');
        $this->db->query("UPDATE users SET is_active = 0, deleted_at = ? WHERE id = ?");
        $this->db->bind("s", $deletedAt);
        $this->db->bind("i", $userId);
        
        if ($this->db->execute()) {
            return ['success' => true, 'message' => 'Account deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Account deletion failed'];
    }
}
?>
