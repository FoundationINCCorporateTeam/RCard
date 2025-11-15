<?php
/**
 * RCard User Dashboard - Cards
 * 
 * @package RCard
 * @version 1.0.0
 */

require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cards.php';

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

$catalog = r_get_card_catalog();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cards - RCard</title>
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
                    <a href="/dashboard/cards.php" class="text-white px-3 py-2 rounded-md text-sm font-medium bg-purple-600">Cards</a>
                    <a href="/dashboard/loans.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Loans</a>
                    <button onclick="handleLogout()" class="text-white hover:text-red-300 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-4xl font-bold text-white mb-8">Card Catalog</h1>
        
        <!-- My Cards -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">My Cards</h2>
            <div id="myCardsContainer" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="glass rounded-xl p-6 text-center">
                    <p class="text-gray-400">Loading...</p>
                </div>
            </div>
        </div>
        
        <!-- Credit Cards -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <span class="text-3xl mr-3">💳</span>
                Credit Cards
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($catalog['credit'] as $card): ?>
                    <div class="glass rounded-xl p-6 hover:scale-105 transition-transform cursor-pointer" style="border-color: <?= htmlspecialchars($card['brand_primary']) ?>;">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-white mb-2"><?= htmlspecialchars($card['name']) ?></h3>
                                <p class="text-gray-400 text-sm"><?= htmlspecialchars($card['description']) ?></p>
                            </div>
                            <div class="text-3xl">💳</div>
                        </div>
                        
                        <div class="border-t border-gray-700 pt-4 space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Annual Fee:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['annual_fee']) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Interest Rate:</span>
                                <span class="text-white font-semibold"><?= $card['interest_rate_monthly'] ?>%/mo</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Max Yearly Loans:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['max_yearly_loans']) ?></span>
                            </div>
                        </div>
                        
                        <button onclick="applyForCard('<?= $card['id'] ?>', '<?= htmlspecialchars($card['name']) ?>', '<?= $card['type'] ?>')" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg transition">
                            Apply Now
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Debit Cards -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <span class="text-3xl mr-3">💵</span>
                Debit Cards
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($catalog['debit'] as $card): ?>
                    <div class="glass rounded-xl p-6 hover:scale-105 transition-transform cursor-pointer" style="border-color: <?= htmlspecialchars($card['brand_primary']) ?>;">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-white mb-2"><?= htmlspecialchars($card['name']) ?></h3>
                                <p class="text-gray-400 text-sm"><?= htmlspecialchars($card['description']) ?></p>
                            </div>
                            <div class="text-3xl">💵</div>
                        </div>
                        
                        <div class="border-t border-gray-700 pt-4 space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Annual Fee:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['annual_fee']) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Transaction Fee:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['transaction_fee']) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Max Yearly Loans:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['max_yearly_loans']) ?></span>
                            </div>
                        </div>
                        
                        <button onclick="applyForCard('<?= $card['id'] ?>', '<?= htmlspecialchars($card['name']) ?>', '<?= $card['type'] ?>')" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg transition">
                            Apply Now
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Merchant Cards -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <span class="text-3xl mr-3">🏪</span>
                Merchant Cards
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($catalog['merchant'] as $card): ?>
                    <div class="glass rounded-xl p-6 hover:scale-105 transition-transform cursor-pointer" style="border-color: <?= htmlspecialchars($card['brand_primary']) ?>;">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-white mb-2"><?= htmlspecialchars($card['name']) ?></h3>
                                <p class="text-gray-400 text-sm"><?= htmlspecialchars($card['description']) ?></p>
                            </div>
                            <div class="text-3xl">🏪</div>
                        </div>
                        
                        <div class="border-t border-gray-700 pt-4 space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Annual Fee:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['annual_fee']) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Interest Rate:</span>
                                <span class="text-white font-semibold"><?= $card['interest_rate_monthly'] ?>%/mo</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Max Yearly Loans:</span>
                                <span class="text-white font-semibold"><?= r_format_currency($card['max_yearly_loans']) ?></span>
                            </div>
                        </div>
                        
                        <button onclick="applyForCard('<?= $card['id'] ?>', '<?= htmlspecialchars($card['name']) ?>', '<?= $card['type'] ?>')" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded-lg transition">
                            Apply Now
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
    <script>
        async function loadMyCards() {
            const result = await apiCall('cards_list');
            const container = document.getElementById('myCardsContainer');
            
            if (result.status === 'success' && result.data.cards.length > 0) {
                container.innerHTML = result.data.cards.map(card => `
                    <div class="rcard-card">
                        <div class="text-2xl mb-2">💳</div>
                        <h3 class="text-white font-bold">${card.card_identifier}</h3>
                        <p class="text-gray-400 text-sm capitalize">${card.type}</p>
                        <p class="text-gray-500 text-xs mt-2">Applied: ${formatDate(card.applied_at)}</p>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="glass rounded-xl p-6 text-center col-span-3">
                        <p class="text-gray-400">You haven't applied for any cards yet</p>
                    </div>
                `;
            }
        }
        
        async function applyForCard(cardId, cardName, cardType) {
            if (!confirm(`Apply for ${cardName}?`)) return;
            
            const result = await apiPost('cards_apply', {
                card_id: cardId,
                card_identifier: cardId,
                card_type: cardType
            });
            
            if (result.status === 'success') {
                showToast('Card application successful!', 'success');
                loadMyCards();
            } else {
                showToast(result.error || 'Application failed', 'error');
            }
        }
        
        async function handleLogout() {
            await apiCall('auth_logout');
            localStorage.removeItem('rcard_user');
            window.location.href = '/index.php';
        }
        
        loadMyCards();
    </script>
</body>
</html>
