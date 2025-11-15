<?php
/**
 * RCard Organization Dashboard
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

// For demo, use user ID as org ID
$org_id = 'org_' . $user_id;
$org_cards = r_card_list_by_org($org_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsor Dashboard - RCard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/public/css/main.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-black/30 backdrop-blur-lg border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-white">RCard Sponsor</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300">Organization: <?= htmlspecialchars($user['username']) ?></span>
                    <a href="/dashboard/home.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">User Dashboard</a>
                    <a href="/org/dashboard.php" class="text-white px-3 py-2 rounded-md text-sm font-medium bg-purple-600">Sponsor Dashboard</a>
                    <a href="/org/create_card.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Create Card</a>
                    <button onclick="handleLogout()" class="text-white hover:text-red-300 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white">Sponsor Dashboard</h1>
                <p class="text-gray-400 mt-2">Manage your card programs and track performance</p>
            </div>
            <a href="/org/create_card.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg transition">
                + Create New Card
            </a>
        </div>

        <!-- Stats Overview -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="glass rounded-xl p-6">
                <p class="text-gray-400 text-sm mb-2">Total Cards</p>
                <p class="text-4xl font-bold text-white"><?= count($org_cards) ?></p>
            </div>
            <div class="glass rounded-xl p-6">
                <p class="text-gray-400 text-sm mb-2">Active Programs</p>
                <p class="text-4xl font-bold text-green-400"><?= count($org_cards) ?></p>
            </div>
            <div class="glass rounded-xl p-6">
                <p class="text-gray-400 text-sm mb-2">Total Applicants</p>
                <p class="text-4xl font-bold text-purple-400">0</p>
            </div>
            <div class="glass rounded-xl p-6">
                <p class="text-gray-400 text-sm mb-2">Revenue</p>
                <p class="text-4xl font-bold text-yellow-400">R$ 0.00</p>
            </div>
        </div>

        <!-- Cards List -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">Your Card Programs</h2>
            
            <?php if (empty($org_cards)): ?>
                <div class="glass rounded-xl p-12 text-center">
                    <div class="text-6xl mb-4">💳</div>
                    <h3 class="text-2xl font-bold text-white mb-2">No Cards Yet</h3>
                    <p class="text-gray-400 mb-6">Create your first card program to start offering benefits to users</p>
                    <a href="/org/create_card.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg">
                        Create Your First Card
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($org_cards as $card): 
                        $spec = $card['spec'] ?? [];
                    ?>
                        <div class="glass rounded-xl p-6 border-2" style="border-color: <?= htmlspecialchars($spec['brand_primary'] ?? '#6366f1') ?>;">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-2xl font-bold text-white"><?= htmlspecialchars($spec['name'] ?? 'Unnamed Card') ?></h3>
                                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded text-xs font-bold uppercase">Active</span>
                                    </div>
                                    <p class="text-gray-400 mb-4">Public ID: <?= htmlspecialchars($card['public_identifier']) ?></p>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div>
                                            <p class="text-gray-400 text-sm">Annual Fee</p>
                                            <p class="text-white font-semibold"><?= r_format_currency($spec['annual_fee'] ?? 0) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Interest Rate</p>
                                            <p class="text-white font-semibold"><?= ($spec['interest_rate'] ?? 0) ?>%</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Max Loans/Year</p>
                                            <p class="text-white font-semibold"><?= r_format_currency($spec['max_yearly_loans'] ?? 0) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm">Created</p>
                                            <p class="text-white font-semibold"><?= date('M d, Y', strtotime($card['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="/cards/<?= htmlspecialchars($org_id) ?>/<?= htmlspecialchars($card['public_identifier']) ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                        View Public Page
                                    </a>
                                    <button onclick="editCard('<?= htmlspecialchars($card['_card_id']) ?>')" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid md:grid-cols-3 gap-6">
            <a href="/org/create_card.php" class="glass rounded-xl p-6 hover:scale-105 transition-transform">
                <div class="text-4xl mb-4">➕</div>
                <h3 class="text-xl font-bold text-white mb-2">Create Card</h3>
                <p class="text-gray-400">Launch a new card program</p>
            </a>
            
            <div class="glass rounded-xl p-6 hover:scale-105 transition-transform cursor-pointer" onclick="showToast('Coming soon!', 'info')">
                <div class="text-4xl mb-4">📊</div>
                <h3 class="text-xl font-bold text-white mb-2">Analytics</h3>
                <p class="text-gray-400">View detailed metrics</p>
            </div>
            
            <div class="glass rounded-xl p-6 hover:scale-105 transition-transform cursor-pointer" onclick="showToast('Coming soon!', 'info')">
                <div class="text-4xl mb-4">⚙️</div>
                <h3 class="text-xl font-bold text-white mb-2">Settings</h3>
                <p class="text-gray-400">Configure your organization</p>
            </div>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
    <script>
        async function handleLogout() {
            await apiCall('auth_logout');
            localStorage.removeItem('rcard_user');
            window.location.href = '/index.php';
        }
        
        function editCard(cardId) {
            showToast('Card editing coming soon!', 'info');
        }
    </script>
</body>
</html>
