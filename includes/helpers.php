<?php
/**
 * Helper Functions
 * Global utility functions
 */

/**
 * Start user session
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = getCurrentUserId();
    $user = new User();
    return $user->getUserById($userId);
}

/**
 * Redirect to login
 */
function redirectToLogin() {
    header('Location: ' . SITE_URL . '/login');
    exit;
}

/**
 * Redirect to dashboard
 */
function redirectToDashboard() {
    header('Location: ' . SITE_URL . '/dashboard');
    exit;
}

/**
 * Sanitize input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }
    
    return ['valid' => true];
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Upload file
 */
function uploadFile($file, $allowed_types = ALLOWED_IMAGE_TYPES, $max_size = MAX_FILE_SIZE) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $fileSize = filesize($file['tmp_name']);
    
    if ($fileSize > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    $fileName = bin2hex(random_bytes(16)) . '.' . $fileExt;
    $uploadDir = UPLOADS_PATH . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => '/uploads/' . $fileName];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($fileName) {
    $filePath = UPLOADS_PATH . '/' . $fileName;
    
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    
    return false;
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Get time ago
 */
function getTimeAgo($date) {
    $timestamp = strtotime($date);
    $difference = time() - $timestamp;
    
    $periods = ['second', 'minute', 'hour', 'day', 'week', 'month', 'year'];
    $lengths = [60, 60, 24, 7, 4.35, 12, 10];
    
    for ($j = 0; $j < count($lengths); $j++) {
        if ($difference < $lengths[$j]) {
            return ($difference == 1) ? '1 ' . $periods[$j] . ' ago' : $difference . ' ' . $periods[$j] . 's ago';
        }
        $difference /= $lengths[$j];
    }
    
    return date($format, $timestamp);
}

/**
 * Send JSON response
 */
function sendJSON($data, $httpCode = 200) {
    header('Content-Type: application/json');
    http_response_code($httpCode);
    echo json_encode($data);
    exit;
}

/**
 * Send success response
 */
function sendSuccess($message = 'Success', $data = []) {
    sendJSON(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Send error response
 */
function sendError($message = 'Error', $httpCode = 400) {
    sendJSON(['success' => false, 'message' => $message], $httpCode);
}

/**
 * Log activity
 */
function logActivity($userId, $action, $description = '', $ipAddress = null) {
    $db = new Database();
    $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $db->query("INSERT INTO admin_logs (admin_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $db->bind("i", $userId);
    $db->bind("s", $action);
    $db->bind("s", $description);
    $db->bind("s", $ipAddress);
    $db->bind("s", $userAgent);
    return $db->execute();
}

/**
 * Get setting
 */
function getSetting($key, $default = null) {
    static $settings = [];
    
    if (isset($settings[$key])) {
        return $settings[$key];
    }
    
    $db = new Database();
    $db->query("SELECT setting_value FROM settings WHERE setting_key = ?");
    $db->bind("s", $key);
    $result = $db->single();
    
    if ($result) {
        $settings[$key] = $result['setting_value'];
        return $result['setting_value'];
    }
    
    return $default;
}

/**
 * Update setting
 */
function updateSetting($key, $value) {
    $db = new Database();
    
    $db->query("SELECT id FROM settings WHERE setting_key = ?");
    $db->bind("s", $key);
    
    if ($db->single()) {
        $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $db->bind("s", $value);
        $db->bind("s", $key);
    } else {
        $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $db->bind("s", $key);
        $db->bind("s", $value);
    }
    
    return $db->execute();
}

/**
 * Generate unique referral code
 */
function generateReferralCode($length = 10) {
    return strtoupper(substr(bin2hex(random_bytes($length)), 0, $length));
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . $suffix;
    }
    return $text;
}

/**
 * Get file size formatted
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get gravatar URL
 */
function getGravatarURL($email, $size = 200) {
    return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=" . $size . "&d=identicon";
}

/**
 * Detect mobile device
 */
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent);
}

/**
 * Get client IP
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

/**
 * Generate SEO friendly URL
 */
function seoFriendlyURL($url) {
    $url = strtolower($url);
    $url = preg_replace('/[^a-z0-9]+/', '-', $url);
    $url = trim($url, '-');
    return $url;
}

/**
 * Generate meta tags
 */
function generateMetaTags($title, $description, $image = null, $url = null) {
    $meta = '';
    $meta .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    $meta .= '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $meta .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    
    if ($image) {
        $meta .= '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }
    
    if ($url) {
        $meta .= '<meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
    }
    
    $meta .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $meta .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
    
    if ($image) {
        $meta .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }
    
    return $meta;
}

/**
 * Get plan features
 */
function getPlanFeatures($planType) {
    $features = [
        'free' => [
            'links' => FREE_PLAN_LINKS,
            'profile_photo' => true,
            'cover_photo' => true,
            'bio' => true,
            'social_icons' => true,
            'analytics' => true,
            'themes' => false,
            'landing_pages' => false,
            'lead_forms' => false,
            'no_branding' => false
        ],
        'premium' => [
            'links' => PREMIUM_PLAN_LINKS,
            'profile_photo' => true,
            'cover_photo' => true,
            'bio' => true,
            'social_icons' => true,
            'analytics' => true,
            'themes' => true,
            'landing_pages' => true,
            'lead_forms' => true,
            'no_branding' => true
        ]
    ];
    
    return $features[$planType] ?? [];
}
?>
