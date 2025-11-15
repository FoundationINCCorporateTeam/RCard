<?php
/**
 * RCard API Endpoint
 * 
 * Main API handler for all RCard operations
 * 
 * @package RCard
 * @version 1.0.0
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Enable CORS for development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required modules
require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/encryption.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cards.php';
require_once __DIR__ . '/includes/loans.php';

// Start session
r_session_start();

// Get action parameter
$action = r_request('action', '');

// Sanitize action
$action = r_sanitize_string($action);

if (empty($action)) {
    r_error('Action parameter required', 400);
}

// Route to appropriate handler
try {
    switch ($action) {
        // Authentication endpoints
        case 'auth_login':
            handle_auth_login();
            break;
        
        case 'auth_logout':
            handle_auth_logout();
            break;
        
        case 'auth_register':
            handle_auth_register();
            break;
        
        // Card endpoints
        case 'cards_list':
            handle_cards_list();
            break;
        
        case 'cards_details':
            handle_cards_details();
            break;
        
        case 'cards_apply':
            handle_cards_apply();
            break;
        
        case 'cards_catalog':
            handle_cards_catalog();
            break;
        
        // Loan endpoints
        case 'loans_bootstrap':
            handle_loans_bootstrap();
            break;
        
        case 'loans_preview':
            handle_loans_preview();
            break;
        
        case 'loans_create':
            handle_loans_create();
            break;
        
        case 'loans_list':
            handle_loans_list();
            break;
        
        case 'loans_repay':
            handle_loans_repay();
            break;
        
        // Fraud reporting
        case 'fraud_report':
            handle_fraud_report();
            break;
        
        default:
            r_error('Unknown action: ' . $action, 404);
    }
    
} catch (Exception $e) {
    r_log("API Exception: " . $e->getMessage(), 'ERROR');
    r_error('Internal server error', 500);
}

/**
 * Handle user login
 */
function handle_auth_login(): void {
    $username = r_sanitize_string(r_request('username', ''));
    $password = r_request('password', '');
    
    if (empty($username) || empty($password)) {
        r_error('Username and password required', 400);
    }
    
    // Rate limiting
    if (!r_rate_limit_check('login_' . $username, 5, 300)) {
        r_error('Too many login attempts. Please try again later.', 429);
    }
    
    $user = r_user_authenticate($username, $password);
    
    if ($user === false) {
        r_error('Invalid username or password', 401);
    }
    
    // Set session
    r_set_authenticated_user($user['id'], $user);
    
    // Return user data (without password hash)
    unset($user['password_hash']);
    
    r_success([
        'user' => $user,
        'session_token' => session_id()
    ]);
}

/**
 * Handle user logout
 */
function handle_auth_logout(): void {
    r_logout();
    r_success(['message' => 'Logged out successfully']);
}

/**
 * Handle user registration
 */
function handle_auth_register(): void {
    $username = r_sanitize_string(r_request('username', ''));
    $password = r_request('password', '');
    
    if (empty($username) || empty($password)) {
        r_error('Username and password required', 400);
    }
    
    // Validate username length
    if (strlen($username) < 3 || strlen($username) > 30) {
        r_error('Username must be between 3 and 30 characters', 400);
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        r_error('Password must be at least 6 characters', 400);
    }
    
    // Create user
    $user_id = r_user_create($username, $password);
    
    if ($user_id === false) {
        r_error('Username already exists or registration failed', 400);
    }
    
    // Auto-login
    $user = r_user_get($user_id);
    r_set_authenticated_user($user_id, $user);
    
    unset($user['password_hash']);
    
    r_success([
        'user' => $user,
        'message' => 'Registration successful'
    ]);
}

/**
 * Handle listing user's cards
 */
function handle_cards_list(): void {
    $user_id = r_require_auth();
    
    $user_cards = r_user_get_cards($user_id);
    
    // Enrich with card details
    $enriched_cards = [];
    foreach ($user_cards as $user_card) {
        $card_info = $user_card;
        
        // Try to get full card details if org_id and card_id are available
        if (isset($user_card['org_id']) && isset($user_card['card_id'])) {
            $full_card = r_card_load($user_card['org_id'], $user_card['card_id']);
            if ($full_card) {
                $card_info['details'] = $full_card;
            }
        }
        
        $enriched_cards[] = $card_info;
    }
    
    r_success(['cards' => $enriched_cards]);
}

/**
 * Handle getting card details
 */
function handle_cards_details(): void {
    $public_id = r_sanitize_string(r_request('public_id', ''));
    
    if (empty($public_id)) {
        r_error('Public ID required', 400);
    }
    
    $card = r_card_get_by_public_id($public_id);
    
    if ($card === false) {
        r_error('Card not found', 404);
    }
    
    r_success(['card' => $card]);
}

/**
 * Handle card application
 */
function handle_cards_apply(): void {
    $user_id = r_require_auth();
    
    $card_id = r_sanitize_int(r_request('card_id', 0));
    $card_identifier = r_sanitize_string(r_request('card_identifier', ''));
    $card_type = r_sanitize_string(r_request('card_type', 'custom'));
    
    if ($card_id <= 0 || empty($card_identifier)) {
        r_error('Card ID and identifier required', 400);
    }
    
    // Add card to user account
    $card_info = [
        'id' => $card_id,
        'card_identifier' => $card_identifier,
        'type' => $card_type,
        'applied_at' => r_timestamp()
    ];
    
    $success = r_user_add_card($user_id, $card_info);
    
    if (!$success) {
        r_error('Failed to add card (may already exist)', 400);
    }
    
    r_success([
        'message' => 'Card added successfully',
        'card' => $card_info
    ]);
}

/**
 * Handle getting card catalog
 */
function handle_cards_catalog(): void {
    $catalog = r_get_card_catalog();
    r_success(['catalog' => $catalog]);
}

/**
 * Handle loans bootstrap (get initial data)
 */
function handle_loans_bootstrap(): void {
    $user_id = r_require_auth();
    
    // Get user loans
    $loans = r_loan_list($user_id, 'active');
    
    // Update interest for each loan
    $updated_loans = [];
    foreach ($loans as $loan) {
        $updated_loan = r_loan_get_current($user_id, $loan['id']);
        if ($updated_loan) {
            $updated_loans[] = $updated_loan;
        }
    }
    
    // Get user cards for loan creation
    $cards = r_user_get_cards($user_id);
    
    // Get card policies
    $policies = [];
    foreach ($cards as $card) {
        $policy = r_lookup_card_policy((string)$card['id']);
        $policies[$card['id']] = $policy;
    }
    
    r_success([
        'loans' => $updated_loans,
        'cards' => $cards,
        'policies' => $policies
    ]);
}

/**
 * Handle loan preview
 */
function handle_loans_preview(): void {
    $user_id = r_require_auth();
    
    $card_id = r_sanitize_int(r_request('card_id', 0));
    $principal = r_sanitize_float(r_request('principal', 0));
    $days = r_sanitize_int(r_request('days', 5));
    
    if ($card_id <= 0 || $principal <= 0 || $days <= 0) {
        r_error('Valid card_id, principal, and days required', 400);
    }
    
    // Get card policy
    $policy = r_lookup_card_policy((string)$card_id);
    
    // Calculate preview
    $preview = r_loan_preview_interest(
        $principal,
        $policy['interest_rate_monthly'],
        $days,
        $policy['min_interest_days']
    );
    
    // Add yearly limit check
    $yearly_total = r_loan_get_yearly_total($user_id);
    $max_yearly = $policy['max_yearly_loans'];
    $remaining = $max_yearly - $yearly_total;
    
    $preview['yearly_limit'] = [
        'used' => $yearly_total,
        'max' => $max_yearly,
        'remaining' => $remaining,
        'can_borrow' => $principal <= $remaining
    ];
    
    r_success(['preview' => $preview]);
}

/**
 * Handle loan creation
 */
function handle_loans_create(): void {
    $user_id = r_require_auth();
    
    $card_id = r_sanitize_int(r_request('card_id', 0));
    $principal = r_sanitize_float(r_request('principal', 0));
    $days = r_sanitize_int(r_request('days', 5));
    
    if ($card_id <= 0 || $principal <= 0 || $days <= 0) {
        r_error('Valid card_id, principal, and days required', 400);
    }
    
    // Rate limiting for loan creation
    if (!r_rate_limit_check('loan_create_' . $user_id, 3, 60)) {
        r_error('Too many loan requests. Please wait a moment.', 429);
    }
    
    $loan_id = r_loan_create($user_id, $card_id, $principal, $days);
    
    if ($loan_id === false) {
        r_error('Failed to create loan. Check limits and requirements.', 400);
    }
    
    $loan = r_loan_get_current($user_id, $loan_id);
    
    r_success([
        'message' => 'Loan created successfully',
        'loan' => $loan
    ]);
}

/**
 * Handle listing loans
 */
function handle_loans_list(): void {
    $user_id = r_require_auth();
    
    $status = r_sanitize_string(r_request('status', ''));
    $status = empty($status) ? null : $status;
    
    $loans = r_loan_list($user_id, $status);
    
    // Update interest for active loans
    $updated_loans = [];
    foreach ($loans as $loan) {
        $updated_loan = r_loan_get_current($user_id, $loan['id']);
        if ($updated_loan) {
            $updated_loans[] = $updated_loan;
        }
    }
    
    r_success(['loans' => $updated_loans]);
}

/**
 * Handle loan repayment
 */
function handle_loans_repay(): void {
    $user_id = r_require_auth();
    
    $loan_id = r_sanitize_string(r_request('loan_id', ''));
    $amount = r_sanitize_float(r_request('amount', 0));
    
    if (empty($loan_id) || $amount <= 0) {
        r_error('Valid loan_id and amount required', 400);
    }
    
    $result = r_loan_repay($user_id, $loan_id, $amount);
    
    if ($result === false) {
        r_error('Repayment failed. Check balance and loan status.', 400);
    }
    
    r_success([
        'message' => 'Repayment successful',
        'result' => $result
    ]);
}

/**
 * Handle fraud report
 */
function handle_fraud_report(): void {
    $user_id = r_require_auth();
    
    $description = r_sanitize_string(r_request('description', ''));
    $type = r_sanitize_string(r_request('type', 'general'));
    
    if (empty($description)) {
        r_error('Description required', 400);
    }
    
    // Save fraud report
    $report_id = 'fraud_' . uniqid();
    $report = [
        'id' => $report_id,
        'user_id' => $user_id,
        'type' => $type,
        'description' => $description,
        'ip_address' => r_get_client_ip(),
        'created_at' => r_timestamp(),
        'status' => 'pending'
    ];
    
    $filepath = RCARD_JSON_PATH . "/fraud_reports/$report_id.json";
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $json = json_encode($report, JSON_PRETTY_PRINT);
    r_atomic_write($filepath, $json);
    
    r_log("Fraud report created: $report_id by user $user_id", 'WARNING');
    
    r_success([
        'message' => 'Report submitted successfully',
        'report_id' => $report_id
    ]);
}
