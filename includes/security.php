<?php
/**
 * RCard Security Module
 * 
 * Provides input validation, sanitization, session management, and security utilities
 * 
 * @package RCard
 * @version 1.0.0
 */

/**
 * Start secure session with proper configuration
 * 
 * @return bool Success status
 */
function r_session_start(): bool {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    
    // Set secure session parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    
    return session_start();
}

/**
 * Check if user is authenticated
 * 
 * @return bool Authentication status
 */
function r_is_authenticated(): bool {
    r_session_start();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user ID
 * 
 * @return int|null User ID or null if not authenticated
 */
function r_get_user_id(): ?int {
    r_session_start();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Set authenticated user session
 * 
 * @param int $user_id User ID
 * @param array $user_data Additional user data to store in session
 * @return void
 */
function r_set_authenticated_user(int $user_id, array $user_data = []): void {
    r_session_start();
    
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $user_data['username'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Destroy user session (logout)
 * 
 * @return void
 */
function r_logout(): void {
    r_session_start();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Validate and check session timeout
 * 
 * @param int $timeout Timeout in seconds (default 3600 = 1 hour)
 * @return bool True if session is valid, false if expired
 */
function r_check_session_timeout(int $timeout = 3600): bool {
    r_session_start();
    
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > $timeout) {
        r_logout();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Sanitize string input - remove HTML tags and special characters
 * 
 * @param string $input Raw input
 * @return string Sanitized string
 */
function r_sanitize_string(string $input): string {
    // Remove null bytes
    $input = str_replace(chr(0), '', $input);
    
    // Strip HTML tags
    $input = strip_tags($input);
    
    // Trim whitespace
    $input = trim($input);
    
    return $input;
}

/**
 * Sanitize integer input
 * 
 * @param mixed $input Raw input
 * @return int Sanitized integer
 */
function r_sanitize_int($input): int {
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float input
 * 
 * @param mixed $input Raw input
 * @return float Sanitized float
 */
function r_sanitize_float($input): float {
    return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize email input
 * 
 * @param string $input Raw email
 * @return string|false Sanitized email or false if invalid
 */
function r_sanitize_email(string $input) {
    return filter_var($input, FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email format
 * 
 * @param string $email Email address
 * @return bool Validation status
 */
function r_validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize file path - prevent directory traversal
 * 
 * @param string $path Raw path
 * @return string|false Sanitized path or false if invalid
 */
function r_sanitize_path(string $path) {
    // Remove null bytes
    $path = str_replace(chr(0), '', $path);
    
    // Normalize path separators
    $path = str_replace('\\', '/', $path);
    
    // Remove any attempts at directory traversal
    $path = preg_replace('#/\.+/#', '/', $path);
    $path = str_replace('../', '', $path);
    $path = str_replace('..\\', '', $path);
    
    // Ensure no absolute paths or special characters
    if (strpos($path, '..') !== false || strpos($path, chr(0)) !== false) {
        return false;
    }
    
    return $path;
}

/**
 * Hash password using bcrypt
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function r_hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * 
 * @param string $password Plain text password
 * @param string $hash Password hash
 * @return bool Verification status
 */
function r_verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generate secure random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
function r_generate_token(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Generate unique identifier
 * 
 * @return string Unique ID
 */
function r_generate_id(): string {
    return uniqid('rcard_', true);
}

/**
 * Rate limiting check (simple in-memory)
 * Note: For production, use Redis/Memcached
 * 
 * @param string $key Rate limit key (e.g., user_id or IP)
 * @param int $max_attempts Max attempts allowed
 * @param int $window Time window in seconds
 * @return bool True if allowed, false if rate limited
 */
function r_rate_limit_check(string $key, int $max_attempts = 5, int $window = 60): bool {
    r_session_start();
    
    $rate_key = 'rate_limit_' . $key;
    $now = time();
    
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = [
            'attempts' => 1,
            'window_start' => $now
        ];
        return true;
    }
    
    $data = $_SESSION[$rate_key];
    
    // Reset window if expired
    if ($now - $data['window_start'] > $window) {
        $_SESSION[$rate_key] = [
            'attempts' => 1,
            'window_start' => $now
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['attempts'] >= $max_attempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION[$rate_key]['attempts']++;
    return true;
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool Validation status
 */
function r_validate_csrf_token(string $token): bool {
    r_session_start();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function r_generate_csrf_token(): string {
    r_session_start();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = r_generate_token(32);
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Escape HTML output
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function r_escape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate JSON structure
 * 
 * @param string $json JSON string
 * @return bool Validation status
 */
function r_validate_json(string $json): bool {
    json_decode($json);
    return json_last_error() === JSON_ERROR_NONE;
}
