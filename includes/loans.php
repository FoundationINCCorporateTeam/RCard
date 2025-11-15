<?php
/**
 * RCard Loans Module
 * 
 * Loan management with daily interest calculation
 * 
 * @package RCard
 * @version 1.0.0
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/cards.php';
require_once __DIR__ . '/auth.php';

/**
 * Create new loan
 * 
 * @param int $user_id User ID
 * @param int $card_id Card ID (from card catalog or custom)
 * @param float $principal Loan principal amount
 * @param int $days Loan duration in days
 * @param array $options Additional loan options
 * @return string|false Loan ID or false on failure
 */
function r_loan_create(int $user_id, int $card_id, float $principal, int $days, array $options = []) {
    // Validate user exists
    $user = r_user_get($user_id);
    if ($user === false) {
        r_log("Loan creation failed: User $user_id not found", 'ERROR');
        return false;
    }
    
    // Get card policy (using card_id as policy identifier)
    $policy = r_lookup_card_policy((string)$card_id);
    
    // Validate minimum days
    $min_days = $policy['min_interest_days'] ?? 5;
    if ($days < $min_days) {
        r_log("Loan creation failed: Days ($days) less than minimum ($min_days)", 'WARNING');
        return false;
    }
    
    // Check yearly loan limit
    $yearly_total = r_loan_get_yearly_total($user_id);
    $max_yearly = $policy['max_yearly_loans'] ?? 1000;
    
    if ($yearly_total + $principal > $max_yearly) {
        r_log("Loan creation failed: Would exceed yearly limit. Total: $yearly_total, Principal: $principal, Max: $max_yearly", 'WARNING');
        return false;
    }
    
    // Generate loan ID
    $loan_id = 'loan_' . uniqid();
    
    // Calculate due date
    $created_date = r_date();
    $due_date = r_add_days($created_date, $days);
    
    // Get interest rate
    $interest_rate_monthly = $options['interest_rate_monthly'] ?? $policy['interest_rate_monthly'] ?? 10;
    
    // Create loan data
    $loan = [
        'id' => $loan_id,
        'user_id' => $user_id,
        'card_id' => $card_id,
        'principal' => $principal,
        'interest_rate_monthly' => $interest_rate_monthly,
        'interest_accrued' => 0,
        'created_at' => $created_date,
        'due_date' => $due_date,
        'status' => 'active',
        'last_interest_calc' => $created_date,
        'days_duration' => $days
    ];
    
    // Save loan
    if (!r_loan_save($user_id, $loan_id, $loan)) {
        return false;
    }
    
    r_log("Loan created: LoanID=$loan_id, UserID=$user_id, Principal=$principal, Days=$days", 'INFO');
    return $loan_id;
}

/**
 * Save loan data
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @param array $loan_data Loan data
 * @return bool Success status
 */
function r_loan_save(int $user_id, string $loan_id, array $loan_data): bool {
    $filepath = RCARD_JSON_PATH . "/loans/$user_id/$loan_id.json";
    
    // Ensure directory exists
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return false;
        }
    }
    
    $json = json_encode($loan_data, JSON_PRETTY_PRINT);
    return r_atomic_write($filepath, $json);
}

/**
 * Load loan data
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @return array|false Loan data or false if not found
 */
function r_loan_load(int $user_id, string $loan_id) {
    $filepath = RCARD_JSON_PATH . "/loans/$user_id/$loan_id.json";
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    $json = file_get_contents($filepath);
    $loan = json_decode($json, true);
    
    return $loan !== null ? $loan : false;
}

/**
 * List all loans for a user
 * 
 * @param int $user_id User ID
 * @param string|null $status Filter by status (active, paid, defaulted)
 * @return array Array of loans
 */
function r_loan_list(int $user_id, ?string $status = null): array {
    $loans_dir = RCARD_JSON_PATH . "/loans/$user_id";
    
    if (!is_dir($loans_dir)) {
        return [];
    }
    
    $loans = [];
    $files = glob($loans_dir . '/*.json');
    
    foreach ($files as $file) {
        $json = file_get_contents($file);
        $loan = json_decode($json, true);
        
        if ($loan) {
            // Filter by status if specified
            if ($status === null || (isset($loan['status']) && $loan['status'] === $status)) {
                $loans[] = $loan;
            }
        }
    }
    
    // Sort by created_at desc
    usort($loans, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $loans;
}

/**
 * Calculate interest preview for a loan
 * 
 * @param float $principal Loan principal
 * @param float $interest_rate_monthly Monthly interest rate (percentage)
 * @param int $days Loan duration in days
 * @param int $min_days Minimum interest days
 * @return array Interest calculation details
 */
function r_loan_preview_interest(float $principal, float $interest_rate_monthly, int $days, int $min_days = 5): array {
    // Ensure minimum days
    $effective_days = max($days, $min_days);
    
    // Convert monthly rate to daily rate
    $daily_rate = $interest_rate_monthly / 30 / 100; // Convert percentage to decimal and monthly to daily
    
    // Calculate interest
    $interest_amount = $principal * $daily_rate * $effective_days;
    $total_due = $principal + $interest_amount;
    
    return [
        'principal' => $principal,
        'interest_rate_monthly' => $interest_rate_monthly,
        'daily_rate' => $daily_rate * 100, // Convert back to percentage for display
        'days' => $days,
        'effective_days' => $effective_days,
        'min_days' => $min_days,
        'interest_amount' => round($interest_amount, 2),
        'total_due' => round($total_due, 2)
    ];
}

/**
 * Calculate accrued interest for active loan
 * 
 * @param array $loan Loan data
 * @return float Accrued interest amount
 */
function r_loan_calculate_accrued_interest(array $loan): float {
    if ($loan['status'] !== 'active') {
        return $loan['interest_accrued'] ?? 0;
    }
    
    $principal = $loan['principal'];
    $rate_monthly = $loan['interest_rate_monthly'];
    $last_calc = $loan['last_interest_calc'];
    $today = r_date();
    
    // Calculate days since last calculation
    $days_elapsed = r_days_between($last_calc, $today);
    
    if ($days_elapsed === 0) {
        return $loan['interest_accrued'] ?? 0;
    }
    
    // Calculate new interest
    $daily_rate = $rate_monthly / 30 / 100;
    $new_interest = $principal * $daily_rate * $days_elapsed;
    
    return ($loan['interest_accrued'] ?? 0) + $new_interest;
}

/**
 * Update loan interest (called periodically)
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @return bool Success status
 */
function r_loan_update_interest(int $user_id, string $loan_id): bool {
    $loan = r_loan_load($user_id, $loan_id);
    
    if ($loan === false || $loan['status'] !== 'active') {
        return false;
    }
    
    // Calculate and update interest
    $loan['interest_accrued'] = r_loan_calculate_accrued_interest($loan);
    $loan['last_interest_calc'] = r_date();
    
    return r_loan_save($user_id, $loan_id, $loan);
}

/**
 * Repay loan (full or partial)
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @param float $amount Repayment amount
 * @return array|false Repayment result or false on failure
 */
function r_loan_repay(int $user_id, string $loan_id, float $amount) {
    $loan = r_loan_load($user_id, $loan_id);
    
    if ($loan === false) {
        r_log("Repayment failed: Loan $loan_id not found", 'ERROR');
        return false;
    }
    
    if ($loan['status'] !== 'active') {
        r_log("Repayment failed: Loan $loan_id not active (status: {$loan['status']})", 'WARNING');
        return false;
    }
    
    // Update interest first
    $interest_accrued = r_loan_calculate_accrued_interest($loan);
    $total_due = $loan['principal'] + $interest_accrued;
    
    if ($amount <= 0 || $amount > $total_due) {
        r_log("Repayment failed: Invalid amount $amount (total due: $total_due)", 'WARNING');
        return false;
    }
    
    // Check user balance
    $user_balance = r_user_get_balance($user_id, 'central_wallet');
    if ($user_balance < $amount) {
        r_log("Repayment failed: Insufficient balance. Has: $user_balance, Needs: $amount", 'WARNING');
        return false;
    }
    
    // Deduct from user balance
    r_user_update_balance($user_id, 'central_wallet', $user_balance - $amount);
    
    // Update loan
    if ($amount >= $total_due) {
        // Full repayment
        $loan['status'] = 'paid';
        $loan['paid_at'] = r_timestamp();
        $loan['paid_amount'] = $amount;
        $loan['interest_accrued'] = $interest_accrued;
    } else {
        // Partial repayment (apply to interest first, then principal)
        if ($amount > $interest_accrued) {
            $principal_payment = $amount - $interest_accrued;
            $loan['principal'] -= $principal_payment;
            $loan['interest_accrued'] = 0;
        } else {
            $loan['interest_accrued'] = $interest_accrued - $amount;
        }
    }
    
    $loan['last_interest_calc'] = r_date();
    
    r_loan_save($user_id, $loan_id, $loan);
    
    r_log("Loan repayment: LoanID=$loan_id, Amount=$amount, NewStatus={$loan['status']}", 'INFO');
    
    return [
        'loan_id' => $loan_id,
        'amount_paid' => $amount,
        'remaining_principal' => $loan['principal'],
        'remaining_interest' => $loan['interest_accrued'] ?? 0,
        'status' => $loan['status']
    ];
}

/**
 * Get total loan amount taken this year
 * 
 * @param int $user_id User ID
 * @return float Total loan amount
 */
function r_loan_get_yearly_total(int $user_id): float {
    $loans = r_loan_list($user_id);
    $current_year = date('Y');
    $total = 0;
    
    foreach ($loans as $loan) {
        $loan_year = date('Y', strtotime($loan['created_at']));
        if ($loan_year === $current_year) {
            $total += $loan['principal'];
        }
    }
    
    return $total;
}

/**
 * Get loan with updated interest
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @return array|false Loan with current interest or false if not found
 */
function r_loan_get_current(int $user_id, string $loan_id) {
    $loan = r_loan_load($user_id, $loan_id);
    
    if ($loan === false) {
        return false;
    }
    
    // Calculate current interest
    if ($loan['status'] === 'active') {
        $loan['interest_accrued'] = r_loan_calculate_accrued_interest($loan);
        $loan['total_due'] = $loan['principal'] + $loan['interest_accrued'];
    } else {
        $loan['total_due'] = $loan['principal'] + ($loan['interest_accrued'] ?? 0);
    }
    
    return $loan;
}

/**
 * Delete loan (admin only)
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @return bool Success status
 */
function r_loan_delete(int $user_id, string $loan_id): bool {
    $filepath = RCARD_JSON_PATH . "/loans/$user_id/$loan_id.json";
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    return unlink($filepath);
}
