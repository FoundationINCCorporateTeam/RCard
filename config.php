<?php
/**
 * RCard Configuration
 * 
 * Central configuration file for the RCard platform
 * 
 * @package RCard
 * @version 1.0.0
 */

// Environment
define('RCARD_ENV', getenv('RCARD_ENV') ?: 'development');
define('RCARD_DEBUG', RCARD_ENV === 'development');

// Encryption
if (!defined('R_JSON_KEY')) {
    // IMPORTANT: Change this in production!
    // Set via environment variable: RCARD_ENCRYPTION_KEY
    define('R_JSON_KEY', getenv('RCARD_ENCRYPTION_KEY') ?: 'change-this-key-in-production-minimum-32-characters-required-for-security');
}

// Paths
define('RCARD_BASE_PATH', __DIR__);
define('RCARD_JSON_PATH', RCARD_BASE_PATH . '/jsondata');
define('RCARD_INCLUDES_PATH', RCARD_BASE_PATH . '/includes');
define('RCARD_PUBLIC_PATH', RCARD_BASE_PATH . '/public');

// Session Configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', '3600'); // 1 hour

// Error Reporting
if (RCARD_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Timezone
date_default_timezone_set('America/Chicago'); // Minnesota timezone

// Application Settings
define('RCARD_APP_NAME', 'RCard');
define('RCARD_VERSION', '1.0.0');

// Security Settings
define('RCARD_SESSION_TIMEOUT', 3600); // 1 hour
define('RCARD_MAX_LOGIN_ATTEMPTS', 5);
define('RCARD_LOGIN_TIMEOUT', 300); // 5 minutes
define('RCARD_MAX_LOAN_ATTEMPTS', 3);
define('RCARD_LOAN_TIMEOUT', 60); // 1 minute

// Loan Defaults
define('RCARD_DEFAULT_MIN_DAYS', 5);
define('RCARD_DEFAULT_MAX_YEARLY_LOANS', 1000);
define('RCARD_DEFAULT_INTEREST_RATE', 10); // 10% monthly

// API Settings
define('RCARD_API_RATE_LIMIT', 100); // requests per minute
define('RCARD_API_TIMEOUT', 30); // seconds

// Create required directories if they don't exist
$required_dirs = [
    RCARD_JSON_PATH . '/users',
    RCARD_JSON_PATH . '/cards',
    RCARD_JSON_PATH . '/loans',
    RCARD_JSON_PATH . '/transactions',
    RCARD_JSON_PATH . '/settings',
    RCARD_JSON_PATH . '/fraud_reports'
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Auto-load includes
spl_autoload_register(function ($class) {
    $file = RCARD_INCLUDES_PATH . '/' . strtolower($class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
