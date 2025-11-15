<?php
/**
 * RCard Seed Data Script
 * 
 * Creates sample users, cards, and loans for testing
 * 
 * Usage: php seed.php
 * 
 * @package RCard
 * @version 1.0.0
 */

require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cards.php';
require_once __DIR__ . '/includes/loans.php';

echo "🌱 RCard Seed Data Script\n";
echo "========================\n\n";

// Create test users
echo "Creating test users...\n";

$test_users = [
    ['username' => 'alice', 'password' => 'password123'],
    ['username' => 'bob', 'password' => 'password123'],
    ['username' => 'charlie', 'password' => 'password123'],
    ['username' => 'sponsor1', 'password' => 'password123']
];

$created_users = [];

foreach ($test_users as $user_data) {
    // Check if user exists
    $existing = r_user_get_by_username($user_data['username']);
    
    if ($existing !== false) {
        echo "  ✓ User '{$user_data['username']}' already exists (ID: {$existing['id']})\n";
        $created_users[$user_data['username']] = $existing['id'];
    } else {
        $user_id = r_user_create($user_data['username'], $user_data['password']);
        
        if ($user_id) {
            echo "  ✓ Created user '{$user_data['username']}' (ID: $user_id)\n";
            $created_users[$user_data['username']] = $user_id;
            
            // Give them some initial balance
            r_user_update_balance($user_id, 'central_wallet', 10000);
            echo "    Added R$ 10,000 to wallet\n";
        } else {
            echo "  ✗ Failed to create user '{$user_data['username']}'\n";
        }
    }
}

echo "\n";

// Create sample sponsor cards
echo "Creating sample sponsor cards...\n";

if (isset($created_users['sponsor1'])) {
    $sponsor_id = $created_users['sponsor1'];
    $org_id = "org_$sponsor_id";
    
    $sample_cards = [
        [
            'name' => 'Elite Titanium Card',
            'public_identifier' => 'elite_titanium',
            'annual_fee' => 750,
            'interest_rate' => 15,
            'transaction_fee' => 10,
            'max_yearly_loans' => 10000,
            'min_interest_days' => 5,
            'brand_primary' => '#14b8a6',
            'brand_secondary' => '#0d9488',
            'benefit_key' => 'titanium-benefits',
            'hero_title' => 'Elite Titanium - Premium Benefits',
            'hero_subtitle' => 'Exclusive perks for elite members',
            'description' => 'Our premium card with the best benefits',
            'card_type' => 'credit'
        ],
        [
            'name' => 'Student Starter Card',
            'public_identifier' => 'student_starter',
            'annual_fee' => 50,
            'interest_rate' => 8,
            'transaction_fee' => 1,
            'max_yearly_loans' => 2000,
            'min_interest_days' => 5,
            'brand_primary' => '#3b82f6',
            'brand_secondary' => '#2563eb',
            'benefit_key' => 'student-benefits',
            'hero_title' => 'Student Starter - Begin Your Journey',
            'hero_subtitle' => 'Perfect for students and first-time users',
            'description' => 'Affordable card for students',
            'card_type' => 'debit'
        ],
        [
            'name' => 'Business Pro Card',
            'public_identifier' => 'business_pro',
            'annual_fee' => 1500,
            'interest_rate' => 5,
            'transaction_fee' => 0,
            'max_yearly_loans' => 50000,
            'min_interest_days' => 7,
            'brand_primary' => '#ef4444',
            'brand_secondary' => '#dc2626',
            'benefit_key' => 'business-benefits',
            'hero_title' => 'Business Pro - Enterprise Solution',
            'hero_subtitle' => 'Built for serious businesses',
            'description' => 'Professional card for business needs',
            'card_type' => 'merchant'
        ]
    ];
    
    foreach ($sample_cards as $card_spec) {
        $card_id = r_card_create($org_id, $card_spec);
        
        if ($card_id) {
            echo "  ✓ Created card '{$card_spec['name']}' (ID: $card_id)\n";
        } else {
            echo "  ✗ Failed to create card '{$card_spec['name']}'\n";
        }
    }
} else {
    echo "  ⚠ Sponsor user not found, skipping card creation\n";
}

echo "\n";

// Apply cards to test users
echo "Applying cards to test users...\n";

$catalog = r_get_card_catalog();
$card_counter = 1;

foreach (['alice', 'bob', 'charlie'] as $username) {
    if (isset($created_users[$username])) {
        $user_id = $created_users[$username];
        
        // Add a few cards from catalog
        foreach (['credit', 'debit'] as $type) {
            if (isset($catalog[$type]) && !empty($catalog[$type])) {
                $card = $catalog[$type][0]; // Get first card of type
                
                $card_info = [
                    'id' => $card_counter++,
                    'card_identifier' => $card['id'],
                    'type' => $card['type'],
                    'applied_at' => r_timestamp()
                ];
                
                if (r_user_add_card($user_id, $card_info)) {
                    echo "  ✓ Applied {$card['name']} to $username\n";
                }
            }
        }
    }
}

echo "\n";

// Create sample loans
echo "Creating sample loans...\n";

if (isset($created_users['alice'])) {
    $user_id = $created_users['alice'];
    
    $sample_loans = [
        ['card_id' => 1, 'principal' => 500, 'days' => 30],
        ['card_id' => 1, 'principal' => 1000, 'days' => 60]
    ];
    
    foreach ($sample_loans as $loan_data) {
        $loan_id = r_loan_create(
            $user_id,
            $loan_data['card_id'],
            $loan_data['principal'],
            $loan_data['days']
        );
        
        if ($loan_id) {
            echo "  ✓ Created loan for alice: R$ {$loan_data['principal']} for {$loan_data['days']} days (ID: $loan_id)\n";
        } else {
            echo "  ✗ Failed to create loan for alice\n";
        }
    }
}

echo "\n";

// Summary
echo "========================\n";
echo "✅ Seed data created successfully!\n\n";
echo "Test Users:\n";
foreach ($test_users as $user) {
    echo "  • {$user['username']} / {$user['password']}\n";
}
echo "\nYou can now log in with any of these accounts.\n";
