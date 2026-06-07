<?php
/**
 * LinkFyl Configuration File
 * Database & Site Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u276588429_linkfyl');
define('DB_USER', 'u276588429_linkfyl');
define('DB_PASS', 'Shakeel@8505');

// Site Configuration
define('SITE_URL', 'https://linkfyl.in');
define('SITE_NAME', 'Linkfyl');
define('SITE_TAGLINE', 'Your Brand. One Link.');
define('SITE_DESCRIPTION', 'Create your professional link-in-bio landing page');

// Email Configuration
define('ADMIN_EMAIL', 'admin@linkfyl.in');
define('SUPPORT_EMAIL', 'support@linkfyl.in');

// Razorpay Configuration
define('RAZORPAY_KEY_ID', 'rzp_live_SySk7kKjgDMnsL');
define('RAZORPAY_KEY_SECRET', '6CtGfE6wPju4guV3X4xB5HwI');

// Payment Configuration
define('PREMIUM_PLAN_PRICE', 1999);
define('PREMIUM_PLAN_ID', 'plan_linkfyl_premium');

// File Paths
define('ROOT_PATH', dirname(__FILE__));
define('BASE_PATH', dirname(dirname(__FILE__)));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('ASSETS_PATH', '/assets');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Security
define('JWT_SECRET', 'linkfyl_jwt_secret_key_2024');
define('SESSION_TIMEOUT', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Allowed File Types
define('ALLOWED_IMAGE_TYPES', array('jpg', 'jpeg', 'png', 'gif', 'webp'));
define('ALLOWED_VIDEO_TYPES', array('mp4', 'webm', 'ogg'));
define('MAX_FILE_SIZE', 5242880); // 5MB

// Currency
define('CURRENCY', 'INR');
define('CURRENCY_SYMBOL', '₹');

// Pagination
define('ITEMS_PER_PAGE', 10);

// API Rate Limit
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Analytics Configuration
define('ANALYTICS_RETENTION_DAYS', 90);

// Affiliate Configuration
define('REFERRAL_COMMISSION_PERCENT', 15);
define('MINIMUM_REFERRAL_PAYOUT', 500);

// Premium Plan Features
define('FREE_PLAN_LINKS', 5);
define('PREMIUM_PLAN_LINKS', 999999);

?>
