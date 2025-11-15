<?php
/**
 * RCard Authentication Module
 * 
 * User management and authentication functions
 * 
 * @package RCard
 * @version 1.0.0
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/encryption.php';

/**
 * Get user data by ID
 * 
 * @param int $user_id User ID
 * @return array|false User data or false if not found
 */
function r_user_get(int $user_id) {
    $filepath = RCARD_JSON_PATH . "/users/$user_id.json";
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    $json = file_get_contents($filepath);
    if ($json === false) {
        return false;
    }
    
    $user = json_decode($json, true);
    if ($user === null) {
        return false;
    }
    
    return $user;
}

/**
 * Get user by username
 * 
 * @param string $username Username
 * @return array|false User data or false if not found
 */
function r_user_get_by_username(string $username) {
    $users_dir = RCARD_JSON_PATH . '/users';
    
    if (!is_dir($users_dir)) {
        return false;
    }
    
    $files = glob($users_dir . '/*.json');
    foreach ($files as $file) {
        $json = file_get_contents($file);
        $user = json_decode($json, true);
        
        if ($user && isset($user['username']) && $user['username'] === $username) {
            return $user;
        }
    }
    
    return false;
}

/**
 * Create new user
 * 
 * @param string $username Username
 * @param string $password Plain text password
 * @param array $additional_data Additional user data
 * @return int|false User ID or false on failure
 */
function r_user_create(string $username, string $password, array $additional_data = []) {
    // Check if username already exists
    if (r_user_get_by_username($username) !== false) {
        r_log("User creation failed: Username '$username' already exists", 'WARNING');
        return false;
    }
    
    // Generate user ID
    $user_id = r_generate_numeric_id();
    
    // Ensure unique ID
    while (r_user_get($user_id) !== false) {
        $user_id = r_generate_numeric_id();
    }
    
    // Create user data
    $user = [
        'id' => $user_id,
        'username' => $username,
        'password_hash' => r_hash_password($password),
        'cards' => [],
        'balances' => [
            'central_wallet' => 0
        ],
        'created_at' => r_timestamp(),
        'last_login' => null
    ];
    
    // Merge additional data
    $user = array_merge($user, $additional_data);
    
    // Save user
    if (!r_user_update($user_id, $user)) {
        return false;
    }
    
    r_log("User created: ID=$user_id, Username=$username", 'INFO');
    return $user_id;
}

/**
 * Update user data
 * 
 * @param int $user_id User ID
 * @param array $user_data User data to save
 * @return bool Success status
 */
function r_user_update(int $user_id, array $user_data): bool {
    $filepath = RCARD_JSON_PATH . "/users/$user_id.json";
    
    // Ensure users directory exists
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $json = json_encode($user_data, JSON_PRETTY_PRINT);
    return r_atomic_write($filepath, $json);
}

/**
 * Delete user
 * 
 * @param int $user_id User ID
 * @return bool Success status
 */
function r_user_delete(int $user_id): bool {
    $filepath = RCARD_JSON_PATH . "/users/$user_id.json";
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    return unlink($filepath);
}

/**
 * Authenticate user with username and password
 * 
 * @param string $username Username
 * @param string $password Plain text password
 * @return array|false User data or false on failure
 */
function r_user_authenticate(string $username, string $password) {
    $user = r_user_get_by_username($username);
    
    if ($user === false) {
        r_log("Authentication failed: User '$username' not found", 'WARNING');
        return false;
    }
    
    if (!isset($user['password_hash'])) {
        r_log("Authentication failed: No password hash for user '$username'", 'ERROR');
        return false;
    }
    
    if (!r_verify_password($password, $user['password_hash'])) {
        r_log("Authentication failed: Invalid password for user '$username'", 'WARNING');
        return false;
    }
    
    // Update last login
    $user['last_login'] = r_timestamp();
    r_user_update($user['id'], $user);
    
    r_log("User authenticated: ID={$user['id']}, Username=$username", 'INFO');
    return $user;
}

/**
 * Add card to user account
 * 
 * @param int $user_id User ID
 * @param array $card_info Card info (id, card_identifier, type)
 * @return bool Success status
 */
function r_user_add_card(int $user_id, array $card_info): bool {
    $user = r_user_get($user_id);
    
    if ($user === false) {
        return false;
    }
    
    // Check if card already added
    foreach ($user['cards'] as $card) {
        if ($card['id'] === $card_info['id']) {
            return false; // Card already exists
        }
    }
    
    $user['cards'][] = $card_info;
    return r_user_update($user_id, $user);
}

/**
 * Get user's cards
 * 
 * @param int $user_id User ID
 * @return array Array of cards
 */
function r_user_get_cards(int $user_id): array {
    $user = r_user_get($user_id);
    
    if ($user === false) {
        return [];
    }
    
    return $user['cards'] ?? [];
}

/**
 * Update user balance
 * 
 * @param int $user_id User ID
 * @param string $balance_type Balance type (e.g., 'central_wallet')
 * @param float $amount Amount to set
 * @return bool Success status
 */
function r_user_update_balance(int $user_id, string $balance_type, float $amount): bool {
    $user = r_user_get($user_id);
    
    if ($user === false) {
        return false;
    }
    
    if (!isset($user['balances'])) {
        $user['balances'] = [];
    }
    
    $user['balances'][$balance_type] = $amount;
    return r_user_update($user_id, $user);
}

/**
 * Get user balance
 * 
 * @param int $user_id User ID
 * @param string $balance_type Balance type (e.g., 'central_wallet')
 * @return float Balance amount
 */
function r_user_get_balance(int $user_id, string $balance_type = 'central_wallet'): float {
    $user = r_user_get($user_id);
    
    if ($user === false) {
        return 0.0;
    }
    
    return $user['balances'][$balance_type] ?? 0.0;
}

/**
 * List all users (for admin purposes)
 * 
 * @return array Array of users
 */
function r_user_list_all(): array {
    $users_dir = RCARD_JSON_PATH . '/users';
    
    if (!is_dir($users_dir)) {
        return [];
    }
    
    $users = [];
    $files = glob($users_dir . '/*.json');
    
    foreach ($files as $file) {
        $json = file_get_contents($file);
        $user = json_decode($json, true);
        
        if ($user) {
            // Remove sensitive data
            unset($user['password_hash']);
            $users[] = $user;
        }
    }
    
    return $users;
}
