<?php
/**
 * RCard User Dashboard - Loans
 * 
 * @package RCard
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session
r_session_start();

// Check authentication
if (!r_is_authenticated()) {
    header('Location: /../index.php');
    exit;
}

$user_id = r_get_user_id();
$user = r_user_get($user_id);

if (!$user) {
    header('Location: /../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans - RCard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black/30 backdrop-blur-lg border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-white">RCard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300">Welcome, <?= htmlspecialchars($user['username']) ?></span>
                    <a href="/dashboard/home.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="/dashboard/cards.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Cards</a>
                    <a href="/dashboard/loans.php" class="text-white px-3 py-2 rounded-md text-sm font-medium bg-purple-600">Loans</a>
                    <button onclick="handleLogout()" class="text-white hover:text-red-300 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-white">Loan Management</h1>
            <button onclick="showNewLoanModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition">
                + New Loan
            </button>
        </div>

        <!-- Loans Wrapper -->
        <div id="loans-wrapper">
            <!-- Loading skeleton -->
            <div class="space-y-4">
                <div class="skeleton h-32 w-full"></div>
                <div class="skeleton h-32 w-full"></div>
            </div>
        </div>
    </div>

    <!-- New Loan Modal -->
    <div id="newLoanModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4 border border-white/20 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-6">Create New Loan</h2>
            
            <!-- Card Selection -->
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Select Card</label>
                <select id="loanCardId" onchange="updateLoanPreview()" class="form-input">
                    <option value="">Choose a card...</option>
                </select>
            </div>
            
            <!-- Principal Amount -->
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Loan Amount (R$)</label>
                <input type="number" id="loanPrincipal" oninput="updateLoanPreview()" placeholder="0.00" class="form-input" step="0.01" min="0">
            </div>
            
            <!-- Days Duration -->
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Duration (Days)</label>
                <input type="number" id="loanDays" oninput="updateLoanPreview()" placeholder="5" class="form-input" min="5" value="30">
                <p class="text-gray-400 text-sm mt-1">Minimum: <span id="minDays">5</span> days</p>
            </div>
            
            <!-- Loan Preview -->
            <div id="loanPreview" class="hidden bg-purple-900/30 rounded-lg p-6 mb-6 border border-purple-500/50">
                <h3 class="text-white font-bold mb-4">Loan Preview</h3>
                <div class="space-y-2 text-gray-300">
                    <div class="flex justify-between">
                        <span>Principal:</span>
                        <span class="font-bold text-white" id="previewPrincipal">R$ 0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Interest Rate (Monthly):</span>
                        <span id="previewRate">0%</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Days:</span>
                        <span id="previewDays">0</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Interest Amount:</span>
                        <span class="font-bold text-yellow-400" id="previewInterest">R$ 0.00</span>
                    </div>
                    <div class="border-t border-gray-600 pt-2 mt-2 flex justify-between text-lg">
                        <span class="font-bold">Total Due:</span>
                        <span class="font-bold text-green-400" id="previewTotal">R$ 0.00</span>
                    </div>
                </div>
                <div id="yearlyLimitWarning" class="hidden mt-4 p-3 bg-red-500/20 border border-red-500 rounded text-red-300 text-sm">
                    <strong>Warning:</strong> This loan exceeds your yearly limit!
                </div>
            </div>
            
            <div class="flex space-x-4">
                <button onclick="createLoan()" id="createLoanBtn" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg disabled:bg-gray-600 disabled:cursor-not-allowed">
                    Create Loan
                </button>
                <button onclick="hideNewLoanModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 rounded-lg">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Repay Loan Modal -->
    <div id="repayLoanModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6">Repay Loan</h2>
            
            <div class="mb-6">
                <p class="text-gray-300 mb-2">Current Wallet Balance:</p>
                <p class="text-3xl font-bold text-white">R$ <?= number_format($user['balances']['central_wallet'] ?? 0, 2) ?></p>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Repayment Amount</label>
                <input type="number" id="repayAmount" placeholder="0.00" class="form-input" step="0.01" min="0">
                <p class="text-gray-400 text-sm mt-1">Total Due: <span id="repayTotalDue">R$ 0.00</span></p>
            </div>
            
            <div class="flex space-x-4">
                <button onclick="repayLoan()" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg">
                    Repay
                </button>
                <button onclick="hideRepayLoanModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 rounded-lg">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
    <script src="/public/js/loans.js"></script>
    <script>
        async function handleLogout() {
            await apiCall('auth_logout');
            localStorage.removeItem('rcard_user');
            window.location.href = '/index.php';
        }
    </script>
</body>
</html>
