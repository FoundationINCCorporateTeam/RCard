<?php
/**
 * RCard Utility Functions
 * 
 * General purpose helper functions
 * 
 * @package RCard
 * @version 1.0.0
 */

/**
 * Define base paths
 */
define('RCARD_BASE_PATH', dirname(__DIR__));
define('RCARD_JSON_PATH', RCARD_BASE_PATH . '/jsondata');

/**
 * Send JSON response and exit
 * 
 * @param string $status Status: 'success' or 'error'
 * @param mixed $data Response data
 * @param int $http_code HTTP status code
 * @return void
 */
function r_json_response(string $status, $data = null, int $http_code = 200): void {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = ['status' => $status];
    
    if ($data !== null) {
        if ($status === 'success') {
            $response['data'] = $data;
        } else {
            $response['error'] = $data;
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send success JSON response
 * 
 * @param mixed $data Response data
 * @return void
 */
function r_success($data = null): void {
    r_json_response('success', $data, 200);
}

/**
 * Send error JSON response
 * 
 * @param string $message Error message
 * @param int $http_code HTTP status code
 * @return void
 */
function r_error(string $message, int $http_code = 400): void {
    r_json_response('error', $message, $http_code);
}

/**
 * Get current timestamp in ISO 8601 format
 * 
 * @return string ISO 8601 timestamp
 */
function r_timestamp(): string {
    return date('c');
}

/**
 * Get date in Y-m-d format
 * 
 * @param int|null $timestamp Unix timestamp (null for current time)
 * @return string Date string
 */
function r_date(?int $timestamp = null): string {
    return date('Y-m-d', $timestamp ?? time());
}

/**
 * Calculate days between two dates
 * 
 * @param string $date1 First date (Y-m-d format)
 * @param string $date2 Second date (Y-m-d format)
 * @return int Number of days
 */
function r_days_between(string $date1, string $date2): int {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return abs($interval->days);
}

/**
 * Add days to a date
 * 
 * @param string $date Date (Y-m-d format)
 * @param int $days Number of days to add
 * @return string New date
 */
function r_add_days(string $date, int $days): string {
    $datetime = new DateTime($date);
    $datetime->modify("+$days days");
    return $datetime->format('Y-m-d');
}

/**
 * Format currency amount
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency symbol
 * @return string Formatted currency
 */
function r_format_currency(float $amount, string $currency = 'R$'): string {
    return $currency . number_format($amount, 2);
}

/**
 * Get request method
 * 
 * @return string HTTP method (GET, POST, etc.)
 */
function r_request_method(): string {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Get request parameter from GET or POST
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value if not found
 * @return mixed Parameter value
 */
function r_request(string $key, $default = null) {
    if (r_request_method() === 'POST') {
        return $_POST[$key] ?? $default;
    }
    return $_GET[$key] ?? $default;
}

/**
 * Get JSON input from request body
 * 
 * @return array|null Decoded JSON or null on failure
 */
function r_get_json_input(): ?array {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return null;
    }
    
    $data = json_decode($input, true);
    return ($data === null) ? null : $data;
}

/**
 * Require authentication - exit with error if not authenticated
 * 
 * @return int User ID
 */
function r_require_auth(): int {
    require_once __DIR__ . '/security.php';
    
    if (!r_is_authenticated()) {
        r_error('Authentication required', 401);
    }
    
    $user_id = r_get_user_id();
    if ($user_id === null) {
        r_error('Invalid session', 401);
    }
    
    return $user_id;
}

/**
 * Log message to error log
 * 
 * @param string $message Log message
 * @param string $level Log level (INFO, WARNING, ERROR)
 * @return void
 */
function r_log(string $message, string $level = 'INFO'): void {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] [$level] $message");
}

/**
 * Generate a numeric ID from timestamp and random
 * 
 * @return int Numeric ID
 */
function r_generate_numeric_id(): int {
    return (int) (time() . rand(100, 999));
}

/**
 * Validate required fields in array
 * 
 * @param array $data Data array
 * @param array $required_fields Required field names
 * @return array|null Array of missing fields or null if all present
 */
function r_validate_required_fields(array $data, array $required_fields): ?array {
    $missing = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    
    return empty($missing) ? null : $missing;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function r_get_client_ip(): string {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return $_SERVER[$key];
        }
    }
    
    return '0.0.0.0';
}

/**
 * Generate pagination metadata
 * 
 * @param int $total Total items
 * @param int $page Current page
 * @param int $per_page Items per page
 * @return array Pagination data
 */
function r_paginate(int $total, int $page = 1, int $per_page = 20): array {
    $page = max(1, $page);
    $total_pages = (int) ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;
    
    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1
    ];
}

/**
 * Clean and normalize phone number
 * 
 * @param string $phone Raw phone number
 * @return string Cleaned phone number
 */
function r_clean_phone(string $phone): string {
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * Truncate string to max length
 * 
 * @param string $string String to truncate
 * @param int $max_length Maximum length
 * @param string $suffix Suffix to add if truncated
 * @return string Truncated string
 */
function r_truncate(string $string, int $max_length = 100, string $suffix = '...'): string {
    if (strlen($string) <= $max_length) {
        return $string;
    }
    
    return substr($string, 0, $max_length - strlen($suffix)) . $suffix;
}
