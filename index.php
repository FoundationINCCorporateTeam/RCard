<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCard - Virtual Financial Membership Platform</title>
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
                <div class="flex space-x-4">
                    <a href="/dashboard/home.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="/org/dashboard.php" class="text-white hover:text-purple-300 px-3 py-2 rounded-md text-sm font-medium">Sponsors</a>
                    <a href="#login" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold text-white mb-6">
                Virtual Financial Cards<br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-600">
                    Built for the Metaverse
                </span>
            </h1>
            <p class="text-xl text-gray-300 mb-8 max-w-3xl mx-auto">
                RCard is a comprehensive virtual financial membership platform designed for Roblox-integrated ecosystems. 
                Manage cards, loans, and perks with enterprise-grade security.
            </p>
            <div class="flex justify-center space-x-4">
                <button onclick="showLoginModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition">
                    Get Started
                </button>
                <a href="#features" class="bg-white/10 hover:bg-white/20 text-white font-bold py-3 px-8 rounded-lg text-lg transition backdrop-blur-sm">
                    Learn More
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h2 class="text-4xl font-bold text-white text-center mb-12">Platform Features</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
                <div class="text-4xl mb-4">💳</div>
                <h3 class="text-xl font-bold text-white mb-2">Custom Cards</h3>
                <p class="text-gray-300">Create and manage credit, debit, and merchant cards with customizable benefits and branding.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
                <div class="text-4xl mb-4">💰</div>
                <h3 class="text-xl font-bold text-white mb-2">Smart Loans</h3>
                <p class="text-gray-300">Interest-bearing loans with daily accrual, early repayment options, and smart preview calculations.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
                <div class="text-4xl mb-4">🔒</div>
                <h3 class="text-xl font-bold text-white mb-2">AES-256 Security</h3>
                <p class="text-gray-300">Enterprise-grade encryption for all sensitive data with fraud protection tracking.</p>
            </div>
            
            <!-- Feature 4 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
                <div class="text-4xl mb-4">🎁</div>
                <h3 class="text-xl font-bold text-white mb-2">Perks & Benefits</h3>
                <p class="text-gray-300">Tiered benefit systems with in-game perks and sponsor partnerships.</p>
            </div>
            
            <!-- Feature 5 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
                <div class="text-4xl mb-4">📊</div>
                <h3 class="text-xl font-bold text-white mb-2">Full Dashboard</h3>
                <p class="text-gray-300">Comprehensive user and sponsor dashboards with real-time metrics.</p>
            </div>
            
            <!-- Feature 6 -->
            <div class="bg-white/10 backdrop-blur-lg rounded-xl p-6 border border-white/20">
                <div class="text-4xl mb-4">🗄️</div>
                <h3 class="text-xl font-bold text-white mb-2">JSON Database</h3>
                <p class="text-gray-300">Lightweight JSON storage with migration path to SQL or MongoDB.</p>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-white/20">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white" id="modalTitle">Login</h2>
                <button onclick="hideLoginModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="loginForm">
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Username</label>
                    <input type="text" id="username" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-purple-500">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-300 mb-2">Password</label>
                    <input type="password" id="password" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-purple-500">
                </div>
                <div id="errorMessage" class="hidden mb-4 p-3 bg-red-500/20 border border-red-500 rounded-lg text-red-300"></div>
                <button onclick="handleLogin()" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition">
                    Login
                </button>
                <div class="mt-4 text-center">
                    <button onclick="toggleRegister()" class="text-purple-400 hover:text-purple-300">
                        Don't have an account? Register
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
</body>
</html>
