<?php
/**
 * RCard Organization - Create Card
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_card'])) {
    $org_id = 'org_' . $user_id;
    
    // Collect and sanitize inputs
    $spec = [
        'name' => r_sanitize_string($_POST['card_name'] ?? ''),
        'public_identifier' => r_sanitize_string($_POST['public_id'] ?? uniqid('pub_')),
        'annual_fee' => r_sanitize_float($_POST['annual_fee'] ?? 0),
        'interest_rate' => r_sanitize_float($_POST['interest_rate'] ?? 10),
        'transaction_fee' => r_sanitize_float($_POST['transaction_fee'] ?? 0),
        'max_yearly_loans' => r_sanitize_float($_POST['max_yearly_loans'] ?? 1000),
        'min_interest_days' => r_sanitize_int($_POST['min_interest_days'] ?? 5),
        'brand_primary' => r_sanitize_string($_POST['brand_primary'] ?? '#6366f1'),
        'brand_secondary' => r_sanitize_string($_POST['brand_secondary'] ?? '#818cf8'),
        'benefit_key' => r_sanitize_string($_POST['benefit_key'] ?? 'default-benefits'),
        'hero_title' => r_sanitize_string($_POST['hero_title'] ?? ''),
        'hero_subtitle' => r_sanitize_string($_POST['hero_subtitle'] ?? ''),
        'hero_bg_image' => r_sanitize_string($_POST['hero_bg_image'] ?? ''),
        'hero_logo' => r_sanitize_string($_POST['hero_logo'] ?? ''),
        'description' => r_sanitize_string($_POST['description'] ?? ''),
        'card_type' => r_sanitize_string($_POST['card_type'] ?? 'custom')
    ];
    
    // Create card
    $card_id = r_card_create($org_id, $spec);
    
    if ($card_id) {
        $_SESSION['success_message'] = 'Card created successfully!';
        header('Location: /org/dashboard.php');
        exit;
    } else {
        $error_message = 'Failed to create card. Please try again.';
    }
}

$org_id = 'org_' . $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Card - RCard</title>
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
                    <a href="/org/dashboard.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Sponsor Dashboard</a>
                    <a href="/org/create_card.php" class="text-white px-3 py-2 rounded-md text-sm font-medium bg-purple-600">Create Card</a>
                    <button onclick="handleLogout()" class="text-white hover:text-red-300 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="/org/dashboard.php" class="text-purple-400 hover:text-purple-300 mb-4 inline-block">← Back to Dashboard</a>
            <h1 class="text-4xl font-bold text-white mb-2">Create New Card</h1>
            <p class="text-gray-400">Design and configure your custom card program</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error mb-6">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <!-- Basic Information -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Basic Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Card Name *</label>
                        <input type="text" name="card_name" required class="form-input" placeholder="e.g., Premium Rewards Card">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Public Identifier *</label>
                        <input type="text" name="public_id" required class="form-input" value="<?= uniqid('pub_') ?>" placeholder="Unique ID for the card">
                        <p class="text-gray-500 text-sm mt-1">Used in the public URL for this card</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Description</label>
                        <textarea name="description" rows="3" class="form-input" placeholder="Brief description of your card program"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Card Type</label>
                        <select name="card_type" class="form-input">
                            <option value="custom">Custom</option>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                            <option value="merchant">Merchant</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Financial Settings -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Financial Settings</h2>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Annual Fee (R$)</label>
                        <input type="number" name="annual_fee" step="0.01" min="0" value="100" class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Transaction Fee (R$)</label>
                        <input type="number" name="transaction_fee" step="0.01" min="0" value="0" class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Interest Rate (% Monthly)</label>
                        <input type="number" name="interest_rate" step="0.01" min="0" value="10" class="form-input">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Minimum Interest Days</label>
                        <input type="number" name="min_interest_days" min="1" value="5" class="form-input">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-300 mb-2">Max Yearly Loans (R$)</label>
                        <input type="number" name="max_yearly_loans" step="0.01" min="0" value="5000" class="form-input">
                        <p class="text-gray-500 text-sm mt-1">Maximum total loan amount per year per user</p>
                    </div>
                </div>
            </div>

            <!-- Branding -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Branding</h2>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Primary Brand Color</label>
                        <input type="color" name="brand_primary" value="#6366f1" class="form-input h-12">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Secondary Brand Color</label>
                        <input type="color" name="brand_secondary" value="#818cf8" class="form-input h-12">
                    </div>
                </div>
            </div>

            <!-- Hero Section -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Public Page Hero Section</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Hero Title</label>
                        <input type="text" name="hero_title" class="form-input" placeholder="Main headline for the public page">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Hero Subtitle</label>
                        <input type="text" name="hero_subtitle" class="form-input" placeholder="Supporting text under the title">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Hero Background Image URL</label>
                        <input type="text" name="hero_bg_image" class="form-input" placeholder="https://example.com/image.jpg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Hero Logo URL</label>
                        <input type="text" name="hero_logo" class="form-input" placeholder="https://example.com/logo.png">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Benefit Key</label>
                        <input type="text" name="benefit_key" value="custom-benefits" class="form-input">
                        <p class="text-gray-500 text-sm mt-1">Identifier for associated benefits package</p>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-2xl font-bold text-white mb-4">Card Preview</h2>
                <div id="cardPreview" class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl p-6 text-white">
                    <h3 class="text-2xl font-bold mb-2" id="previewName">Your Card Name</h3>
                    <p class="text-white/80 mb-4" id="previewDesc">Card description will appear here</p>
                    <div class="space-y-1 text-sm">
                        <p>Annual Fee: <span id="previewFee">R$ 100.00</span></p>
                        <p>Interest Rate: <span id="previewRate">10%</span> monthly</p>
                        <p>Max Loans/Year: <span id="previewLoans">R$ 5,000.00</span></p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex space-x-4">
                <button type="submit" name="create_card" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-lg text-lg transition">
                    Create Card Program
                </button>
                <a href="/org/dashboard.php" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-4 rounded-lg text-lg transition text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="/public/js/main.js"></script>
    <script>
        // Live preview
        const inputs = {
            card_name: document.querySelector('[name="card_name"]'),
            description: document.querySelector('[name="description"]'),
            annual_fee: document.querySelector('[name="annual_fee"]'),
            interest_rate: document.querySelector('[name="interest_rate"]'),
            max_yearly_loans: document.querySelector('[name="max_yearly_loans"]'),
            brand_primary: document.querySelector('[name="brand_primary"]'),
            brand_secondary: document.querySelector('[name="brand_secondary"]')
        };
        
        function updatePreview() {
            const preview = document.getElementById('cardPreview');
            const name = inputs.card_name.value || 'Your Card Name';
            const desc = inputs.description.value || 'Card description will appear here';
            const fee = parseFloat(inputs.annual_fee.value || 0);
            const rate = parseFloat(inputs.interest_rate.value || 0);
            const loans = parseFloat(inputs.max_yearly_loans.value || 0);
            const primary = inputs.brand_primary.value;
            const secondary = inputs.brand_secondary.value;
            
            document.getElementById('previewName').textContent = name;
            document.getElementById('previewDesc').textContent = desc;
            document.getElementById('previewFee').textContent = formatCurrency(fee);
            document.getElementById('previewRate').textContent = rate + '%';
            document.getElementById('previewLoans').textContent = formatCurrency(loans);
            
            preview.style.background = `linear-gradient(135deg, ${primary} 0%, ${secondary} 100%)`;
        }
        
        Object.values(inputs).forEach(input => {
            if (input) input.addEventListener('input', updatePreview);
        });
        
        async function handleLogout() {
            await apiCall('auth_logout');
            localStorage.removeItem('rcard_user');
            window.location.href = '/index.php';
        }
    </script>
</body>
</html>
