<?php
/**
 * RCard User Dashboard Home
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
    <title>Dashboard - RCard</title>
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
                    <a href="/dashboard/home.php" class="text-white px-3 py-2 rounded-md text-sm font-medium bg-purple-600">Home</a>
                    <a href="/dashboard/cards.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Cards</a>
                    <a href="/dashboard/loans.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Loans</a>
                    <button onclick="handleLogout()" class="text-white hover:text-red-300 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-purple-600 to-blue-600 rounded-xl p-8 mb-8 shadow-2xl">
            <h2 class="text-white text-lg mb-2">Central Wallet Balance</h2>
            <p class="text-5xl font-bold text-white" id="walletBalance">R$ <?= number_format($user['balances']['central_wallet'] ?? 0, 2) ?></p>
            <div class="mt-4 flex space-x-4">
                <button onclick="showAddFundsModal()" class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg backdrop-blur-sm transition">
                    Add Funds
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="glass rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-300 text-sm">Active Cards</p>
                        <p class="text-3xl font-bold text-white" id="activeCardsCount">0</p>
                    </div>
                    <div class="text-4xl">💳</div>
                </div>
            </div>
            
            <div class="glass rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-300 text-sm">Active Loans</p>
                        <p class="text-3xl font-bold text-white" id="activeLoansCount">0</p>
                    </div>
                    <div class="text-4xl">💰</div>
                </div>
            </div>
            
            <div class="glass rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-300 text-sm">Member Since</p>
                        <p class="text-xl font-bold text-white"><?= date('M Y', strtotime($user['created_at'])) ?></p>
                    </div>
                    <div class="text-4xl">⭐</div>
                </div>
            </div>
        </div>

        <!-- Cards Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-white">My Cards</h2>
                <a href="/dashboard/cards.php" class="text-purple-400 hover:text-purple-300">View All →</a>
            </div>
            <div id="cardsContainer" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="glass rounded-xl p-6 text-center">
                    <p class="text-gray-400">Loading cards...</p>
                </div>
            </div>
        </div>

        <!-- Recent Loans -->
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-white">Recent Loans</h2>
                <a href="/dashboard/loans.php" class="text-purple-400 hover:text-purple-300">View All →</a>
            </div>
            <div id="loansContainer" class="space-y-4">
                <div class="glass rounded-xl p-6 text-center">
                    <p class="text-gray-400">Loading loans...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Funds Modal -->
    <div id="addFundsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-4">Add Funds</h2>
            <p class="text-gray-300 mb-4">Enter the amount to add to your wallet:</p>
            <input type="number" id="fundsAmount" placeholder="0.00" class="form-input mb-4" step="0.01" min="0">
            <div class="flex space-x-4">
                <button onclick="addFunds()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg">
                    Add
                </button>
                <button onclick="hideAddFundsModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 rounded-lg">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
    <script>
        // Load dashboard data
        async function loadDashboard() {
            // Load cards
            const cardsResult = await apiCall('cards_list');
            if (cardsResult.status === 'success') {
                const cards = cardsResult.data.cards;
                document.getElementById('activeCardsCount').textContent = cards.length;
                
                if (cards.length > 0) {
                    renderCards(cards.slice(0, 3)); // Show first 3
                } else {
                    document.getElementById('cardsContainer').innerHTML = `
                        <div class="glass rounded-xl p-6 text-center">
                            <p class="text-gray-400">No cards yet</p>
                            <a href="/dashboard/cards.php" class="text-purple-400 hover:text-purple-300">Browse Cards</a>
                        </div>
                    `;
                }
            }
            
            // Load loans
            const loansResult = await apiCall('loans_list', { status: 'active' });
            if (loansResult.status === 'success') {
                const loans = loansResult.data.loans;
                document.getElementById('activeLoansCount').textContent = loans.length;
                
                if (loans.length > 0) {
                    renderLoans(loans.slice(0, 3)); // Show first 3
                } else {
                    document.getElementById('loansContainer').innerHTML = `
                        <div class="glass rounded-xl p-6 text-center">
                            <p class="text-gray-400">No active loans</p>
                        </div>
                    `;
                }
            }
        }
        
        function renderCards(cards) {
            const container = document.getElementById('cardsContainer');
            container.innerHTML = cards.map(card => `
                <div class="rcard-card">
                    <div class="text-2xl mb-2">💳</div>
                    <h3 class="text-white font-bold">${card.card_identifier}</h3>
                    <p class="text-gray-400 text-sm capitalize">${card.type}</p>
                </div>
            `).join('');
        }
        
        function renderLoans(loans) {
            const container = document.getElementById('loansContainer');
            container.innerHTML = loans.map(loan => `
                <div class="glass rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-400 text-sm">Loan #${loan.id.substring(5, 13)}</p>
                            <p class="text-2xl font-bold text-white">${formatCurrency(loan.principal)}</p>
                            <p class="text-gray-300 text-sm mt-1">Due: ${formatDate(loan.due_date)}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-400">Interest</p>
                            <p class="text-lg font-bold text-yellow-400">${formatCurrency(loan.interest_accrued || 0)}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        async function handleLogout() {
            await apiCall('auth_logout');
            localStorage.removeItem('rcard_user');
            window.location.href = '/index.php';
        }
        
        function showAddFundsModal() {
            document.getElementById('addFundsModal').classList.remove('hidden');
            document.getElementById('addFundsModal').classList.add('flex');
        }
        
        function hideAddFundsModal() {
            document.getElementById('addFundsModal').classList.add('hidden');
            document.getElementById('addFundsModal').classList.remove('flex');
        }
        
        async function addFunds() {
            const amount = parseFloat(document.getElementById('fundsAmount').value);
            if (isNaN(amount) || amount <= 0) {
                showToast('Please enter a valid amount', 'error');
                return;
            }
            
            // In a real system, this would integrate with a payment processor
            // For now, we'll just update the balance directly (demo purposes)
            showToast('In production, this would integrate with a payment gateway', 'info');
            hideAddFundsModal();
        }
        
        // Load data on page load
        loadDashboard();
    </script>
</body>
</html>
