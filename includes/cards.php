<?php
/**
 * RCard Cards Module
 * 
 * Card management and policy functions
 * 
 * @package RCard
 * @version 1.0.0
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/encryption.php';

/**
 * Global card catalog with policies
 * 
 * @return array Card catalog
 */
function r_get_card_catalog(): array {
    return [
        'credit' => [
            [
                'id' => 'platinum-credit',
                'name' => 'Platinum Credit Card',
                'type' => 'credit',
                'annual_fee' => 500,
                'interest_rate_monthly' => 12,
                'transaction_fee' => 5,
                'max_yearly_loans' => 2500,
                'min_interest_days' => 5,
                'brand_primary' => '#14b8a6',
                'brand_secondary' => '#3b82f6',
                'benefit_key' => 'platinum-benefits',
                'description' => 'Premium credit card with exclusive benefits'
            ],
            [
                'id' => 'gold-credit',
                'name' => 'Gold Credit Card',
                'type' => 'credit',
                'annual_fee' => 300,
                'interest_rate_monthly' => 10,
                'transaction_fee' => 3,
                'max_yearly_loans' => 2000,
                'min_interest_days' => 5,
                'brand_primary' => '#f59e0b',
                'brand_secondary' => '#fbbf24',
                'benefit_key' => 'gold-benefits',
                'description' => 'Mid-tier credit card with great rewards'
            ],
            [
                'id' => 'silver-credit',
                'name' => 'Silver Credit Card',
                'type' => 'credit',
                'annual_fee' => 100,
                'interest_rate_monthly' => 8,
                'transaction_fee' => 2,
                'max_yearly_loans' => 1500,
                'min_interest_days' => 5,
                'brand_primary' => '#6b7280',
                'brand_secondary' => '#9ca3af',
                'benefit_key' => 'silver-benefits',
                'description' => 'Entry-level credit card for everyday use'
            ]
        ],
        'debit' => [
            [
                'id' => 'premium-debit',
                'name' => 'Premium Debit Card',
                'type' => 'debit',
                'annual_fee' => 200,
                'interest_rate_monthly' => 0,
                'transaction_fee' => 1,
                'max_yearly_loans' => 1000,
                'min_interest_days' => 5,
                'brand_primary' => '#8b5cf6',
                'brand_secondary' => '#a78bfa',
                'benefit_key' => 'premium-debit-benefits',
                'description' => 'Premium debit card with low fees'
            ],
            [
                'id' => 'standard-debit',
                'name' => 'Standard Debit Card',
                'type' => 'debit',
                'annual_fee' => 50,
                'interest_rate_monthly' => 0,
                'transaction_fee' => 0.5,
                'max_yearly_loans' => 500,
                'min_interest_days' => 5,
                'brand_primary' => '#10b981',
                'brand_secondary' => '#34d399',
                'benefit_key' => 'standard-debit-benefits',
                'description' => 'Basic debit card for everyday transactions'
            ]
        ],
        'merchant' => [
            [
                'id' => 'merchant-pro',
                'name' => 'Merchant Pro Card',
                'type' => 'merchant',
                'annual_fee' => 1000,
                'interest_rate_monthly' => 5,
                'transaction_fee' => 0,
                'max_yearly_loans' => 5000,
                'min_interest_days' => 5,
                'brand_primary' => '#ef4444',
                'brand_secondary' => '#f87171',
                'benefit_key' => 'merchant-pro-benefits',
                'description' => 'Professional card for business merchants'
            ]
        ],
        'default' => [
            'id' => 'default',
            'name' => 'Default Card',
            'type' => 'custom',
            'annual_fee' => 0,
            'interest_rate_monthly' => 15,
            'transaction_fee' => 5,
            'max_yearly_loans' => 1000,
            'min_interest_days' => 5,
            'brand_primary' => '#6366f1',
            'brand_secondary' => '#818cf8',
            'benefit_key' => 'default-benefits',
            'description' => 'Default card configuration'
        ]
    ];
}

/**
 * Lookup card policy by ID or type
 * 
 * @param string $card_id Card ID
 * @param string|null $card_type Optional card type to narrow search
 * @return array|false Card policy or false if not found
 */
function r_lookup_card_policy(string $card_id, ?string $card_type = null) {
    $catalog = r_get_card_catalog();
    
    // Search in specific type if provided
    if ($card_type && isset($catalog[$card_type])) {
        foreach ($catalog[$card_type] as $card) {
            if ($card['id'] === $card_id) {
                return $card;
            }
        }
    }
    
    // Search in all types
    foreach ($catalog as $type => $cards) {
        if ($type === 'default') continue;
        
        foreach ($cards as $card) {
            if ($card['id'] === $card_id) {
                return $card;
            }
        }
    }
    
    // Return default policy if not found
    return $catalog['default'];
}

/**
 * Load card data by org and card ID
 * 
 * @param string $org_id Organization ID
 * @param string $card_id Card ID
 * @return array|false Card data or false if not found
 */
function r_card_load(string $org_id, string $card_id) {
    $filepath = RCARD_JSON_PATH . "/cards/$org_id/$card_id.json";
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    // Try to decrypt (encrypted cards)
    $decrypted = r_decrypt_from_file($filepath);
    if ($decrypted !== false) {
        return $decrypted;
    }
    
    // Fallback to plain JSON (if not encrypted)
    $json = file_get_contents($filepath);
    $card = json_decode($json, true);
    
    return $card !== null ? $card : false;
}

/**
 * Save card data (encrypted)
 * 
 * @param string $org_id Organization ID
 * @param string $card_id Card ID
 * @param array $card_data Card data
 * @return bool Success status
 */
function r_card_save(string $org_id, string $card_id, array $card_data): bool {
    $filepath = RCARD_JSON_PATH . "/cards/$org_id/$card_id.json";
    
    // Ensure directory exists
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return false;
        }
    }
    
    // Encrypt and save
    return r_encrypt_to_file($filepath, $card_data);
}

/**
 * Create new card
 * 
 * @param string $org_id Organization ID
 * @param array $spec Card specification
 * @return string|false Card ID or false on failure
 */
function r_card_create(string $org_id, array $spec) {
    // Generate unique card identifier
    $card_id = 'card_' . uniqid();
    
    // Ensure unique public identifier
    $public_identifier = $spec['public_identifier'] ?? uniqid('pub_');
    
    // Create card data
    $card_data = [
        'public_identifier' => $public_identifier,
        'org_id' => $org_id,
        'created_at' => r_timestamp(),
        'spec' => $spec
    ];
    
    // Save card
    if (!r_card_save($org_id, $card_id, $card_data)) {
        return false;
    }
    
    r_log("Card created: OrgID=$org_id, CardID=$card_id", 'INFO');
    return $card_id;
}

/**
 * Get card by public identifier
 * 
 * @param string $public_identifier Public card identifier
 * @return array|false Card data with org_id and card_id, or false if not found
 */
function r_card_get_by_public_id(string $public_identifier) {
    $cards_dir = RCARD_JSON_PATH . '/cards';
    
    if (!is_dir($cards_dir)) {
        return false;
    }
    
    // Search through all org directories
    $org_dirs = glob($cards_dir . '/*', GLOB_ONLYDIR);
    
    foreach ($org_dirs as $org_dir) {
        $org_id = basename($org_dir);
        $card_files = glob($org_dir . '/*.json');
        
        foreach ($card_files as $card_file) {
            $card_id = basename($card_file, '.json');
            $card = r_card_load($org_id, $card_id);
            
            if ($card && isset($card['public_identifier']) && $card['public_identifier'] === $public_identifier) {
                $card['_org_id'] = $org_id;
                $card['_card_id'] = $card_id;
                return $card;
            }
        }
    }
    
    return false;
}

/**
 * List all cards for an organization
 * 
 * @param string $org_id Organization ID
 * @return array Array of cards
 */
function r_card_list_by_org(string $org_id): array {
    $org_dir = RCARD_JSON_PATH . "/cards/$org_id";
    
    if (!is_dir($org_dir)) {
        return [];
    }
    
    $cards = [];
    $files = glob($org_dir . '/*.json');
    
    foreach ($files as $file) {
        $card_id = basename($file, '.json');
        $card = r_card_load($org_id, $card_id);
        
        if ($card) {
            $card['_card_id'] = $card_id;
            $cards[] = $card;
        }
    }
    
    return $cards;
}

/**
 * Delete card
 * 
 * @param string $org_id Organization ID
 * @param string $card_id Card ID
 * @return bool Success status
 */
function r_card_delete(string $org_id, string $card_id): bool {
    $filepath = RCARD_JSON_PATH . "/cards/$org_id/$card_id.json";
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    return unlink($filepath);
}

/**
 * Get card policy merged with custom spec
 * 
 * @param array $card Card data
 * @return array Merged policy
 */
function r_card_get_effective_policy(array $card): array {
    $spec = $card['spec'] ?? [];
    
    // Get base policy
    $policy_id = $spec['policy_id'] ?? 'default';
    $base_policy = r_lookup_card_policy($policy_id);
    
    // Merge spec over base policy
    return array_merge($base_policy, $spec);
}
