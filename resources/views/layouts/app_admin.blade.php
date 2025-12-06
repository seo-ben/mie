<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MIE YAYRA - Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            overflow-x: hidden;
        }

        /* Sidebar Transitions */
        .sidebar-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar {
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.08);
        }

        .sidebar-link {
            position: relative;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: #3B82F6;
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }

        .sidebar-link:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.08) 0%, transparent 100%);
            padding-left: 1.125rem;
        }

        .sidebar-link:hover::before {
            transform: scaleY(1);
        }

        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.02) 100%);
            font-weight: 600;
            color: #1E40AF;
            padding-left: 1.125rem;
        }

        .sidebar-link.active::before {
            transform: scaleY(1);
        }

        .sidebar-link i {
            font-size: 1.125rem;
            width: 24px;
            text-align: center;
        }

        /* Gradient moderne et professionnel */
        .gradient-bg {
            background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 50%, #60A5FA 100%);
            position: relative;
        }

        .gradient-bg::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.1) 100%);
        }

        /* Section Headers */
        .section-header {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #6B7280;
            margin: 1.5rem 0 0.5rem 0;
            padding: 0 1rem;
        }

        /* Navbar shadow on scroll */
        .navbar-shadow {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        /* Badge notifications */
        .notification-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        /* Dropdown menu */
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            min-width: 280px;
            z-index: 50;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            transition: background 0.15s ease;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: #F3F4F6;
        }

        /* User avatar */
        .user-avatar {
            background: linear-gradient(135deg, #3B82F6 0%, #8B5CF6 100%);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        /* Mobile sidebar overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 35;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        /* Responsive sidebar */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }
        }

        /* Search input focus */
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Security indicator */
        .security-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .security-dot {
            width: 6px;
            height: 6px;
            background: #10B981;
            border-radius: 50%;
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        /* Scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #F3F4F6;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #D1D5DB;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
        }

        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Metric card */
        .metric-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .stat-up {
            color: #10B981;
        }

        .stat-down {
            color: #EF4444;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen bg-white sidebar sidebar-transition">
        <div class="flex flex-col h-full">
            <!-- Logo & Header -->
            <div class="relative p-6 text-white gradient-bg">
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">MIE YAYRA</h2>
                        <p class="text-sm font-medium opacity-90 mt-0.5">Administration</p>
                    </div>
                    <button id="closeSidebar" class="flex items-center justify-center w-8 h-8 text-white transition rounded-lg md:hidden hover:bg-white/20">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-4 overflow-y-auto custom-scrollbar">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-chart-line"></i>
                    <span class="text-sm">Tableau de bord</span>
                </a>

                <!-- Section: Gestion Clients -->
                <div class="section-header">GESTION CLIENTS</div>

                <a href="{{ route('admin.clients.index') }}" class="sidebar-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-users"></i>
                    <span class="text-sm">Clients</span>
                </a>

                <a href="{{ route('admin.accounts.index') }}" class="sidebar-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-wallet"></i>
                    <span class="text-sm">Comptes</span>
                </a>

                <!-- Section: Opérations -->
                <div class="section-header">OPÉRATIONS FINANCIÈRES</div>

                <a href="{{ route('admin.accounts.depot') }}" class="sidebar-link {{ request()->routeIs('admin.accounts.depot') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="w-5 mr-3 fas fa-bolt"></i>
                    <span class="text-sm">Dépôt Rapide</span>
                </a>
                <a href="{{ route('admin.loans.index') }}" class="sidebar-link {{ request()->routeIs('admin.loans.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span class="text-sm">Prêts</span>
                </a>

                <a href="{{ route('admin.tontines.index') }}" class="sidebar-link {{ request()->routeIs('admin.tontines.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-hands-helping"></i>
                    <span class="text-sm">Tontines</span>
                </a>
                <a href="{{ route('admin.transactions.index') }}" class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-exchange-alt"></i>
                    <span class="text-sm">Transactions</span>
                </a>
                <!-- Section: Analyse & Rapports -->
                <div class="section-header">ANALYSE & RAPPORTS</div>

                <a href="{{-- route('admin.reports.index') --}}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-chart-pie"></i>
                    <span class="text-sm">Rapports</span>
                </a>

                <a href="{{ route('admin.reports.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.users*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-chart-pie"></i>
                    <span class="text-sm">Rapports utilisateurs</span>
                </a>

                <a href="{{-- route('admin.geography.index') --}}" class="sidebar-link {{ request()->routeIs('admin.geography.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-map-marked-alt"></i>
                    <span class="text-sm">Géographie</span>
                </a>

                <!-- Section: Administration -->
                <div class="section-header">ADMINISTRATION</div>

                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-user-shield"></i>
                    <span class="text-sm">Utilisateurs</span>
                </a>

                <a href="{{ route('admin.agencies.index') }}" class="sidebar-link {{ request()->routeIs('admin.agencies.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-building"></i>
                    <span class="text-sm">Agences</span>
                </a>

                <a href="{{ route('admin.profitability.index') }}" class="sidebar-link {{ request()->routeIs('admin.profitability.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-building"></i>
                    <span class="text-sm">profitability</span>
                </a>

                <!-- Section: Système -->
                <div class="section-header">SYSTÈME</div>

                <a href="{{ route('admin.system-health') }}" class="sidebar-link {{ request()->routeIs('admin.system-health') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-heartbeat"></i>
                    <span class="text-sm">Santé Système</span>
                </a>

                <a href="{{ route('admin.config.backups') }}" class="sidebar-link {{ request()->routeIs('admin.config.backups') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-database"></i>
                    <span class="text-sm">Sauvegardes</span>
                </a>

                <a href="{{ route('admin.config.parameters') }}" class="sidebar-link {{ request()->routeIs('admin.config.parameters') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-sliders-h"></i>
                    <span class="text-sm">Configuration</span>
                </a>

                <a href="{{-- route('admin.config.integrations') --}}" class="sidebar-link {{ request()->routeIs('admin.config.integrations') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-plug"></i>
                    <span class="text-sm">Intégrations API</span>
                </a>

                <a href="{{-- route('admin.settings.index') --}}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-gray-700">
                    <i class="fas fa-cog"></i>
                    <span class="text-sm">Paramètres</span>
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 text-sm font-semibold text-white rounded-full user-avatar">
                        {{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1) . substr(auth()->user()->last_name ?? 'D', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->full_name ?? 'Admin Système' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ ucfirst(str_replace('_', ' ', auth()->user()->role ?? 'administrateur')) }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center justify-center w-8 h-8 text-gray-400 transition rounded-lg hover:text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
                <div class="pt-3 mt-3 border-t border-gray-200">
                    <div class="security-indicator">
                        <span class="security-dot"></span>
                        <span>Connexion sécurisée</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="min-h-screen md:ml-64">
        <!-- Top Navbar -->
        <nav class="sticky top-0 z-30 bg-white navbar-shadow">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Left Section -->
                    <div class="flex items-center gap-4">
                        <button id="menuToggle" class="flex items-center justify-center w-10 h-10 text-gray-600 transition rounded-lg hover:text-gray-900 md:hidden hover:bg-gray-100">
                            <i class="text-xl fas fa-bars"></i>
                        </button>

                        <div>
                            <h1 class="text-lg font-bold text-gray-900">@yield('page-title', 'Tableau de bord')</h1>
                            @if(isset($period))
                            <p class="hidden sm:block text-xs text-gray-500 mt-0.5">
                                <i class="mr-1 far fa-calendar"></i>
                                Période: {{ $period }} jours
                            </p>
                            @endif
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div class="flex items-center gap-3">
                        <!-- Period Selector -->
                        @if(request()->routeIs('admin.dashboard'))
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="hidden lg:block">
                            <select name="period" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white cursor-pointer" onchange="this.form.submit()">
                                <option value="7" {{ ($period ?? 7) == 7 ? 'selected' : '' }}>7 derniers jours</option>
                                <option value="30" {{ ($period ?? 7) == 30 ? 'selected' : '' }}>30 derniers jours</option>
                                <option value="90" {{ ($period ?? 7) == 90 ? 'selected' : '' }}>90 derniers jours</option>
                                <option value="365" {{ ($period ?? 7) == 365 ? 'selected' : '' }}>1 an</option>
                            </select>
                        </form>
                        @endif

                        <!-- Search -->
                        <div class="relative hidden lg:block">
                            <input type="text"
                                   placeholder="Rechercher client, transaction..."
                                   class="py-2 pl-10 pr-4 text-sm transition border border-gray-300 rounded-lg search-input w-72 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <i class="absolute text-gray-400 fas fa-search left-3 top-2.5 text-sm"></i>
                        </div>

                        <!-- Notifications -->
                        <div class="relative">
                            <button id="notificationBtn" class="relative flex items-center justify-center w-10 h-10 text-gray-600 transition rounded-lg hover:text-gray-900 hover:bg-gray-100">
                                <i class="text-lg fas fa-bell"></i>
                                @if(isset($operational['pending_tasks']))
                                @php
                                    $totalAlerts = ($operational['pending_tasks']['kyc_pending'] ?? 0) +
                                                   ($operational['pending_tasks']['loan_applications'] ?? 0) +
                                                   (count($operational['system_alerts'] ?? []));
                                @endphp
                                @if($totalAlerts > 0)
                                <span class="absolute flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-red-500 rounded-full notification-badge -top-1 -right-1">{{ min($totalAlerts, 99) }}</span>
                                @endif
                                @endif
                            </button>

                            <!-- Notifications Dropdown -->
                            <div id="notificationDropdown" class="dropdown-menu">
                                <div class="p-4 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-bold text-gray-900">Notifications</h3>
                                        <span class="text-xs font-semibold text-blue-600 cursor-pointer hover:underline">Tout marquer comme lu</span>
                                    </div>
                                </div>
                                <div class="overflow-y-auto max-h-96 custom-scrollbar">
                                    @if(isset($operational['pending_tasks']))
                                        @if(($operational['pending_tasks']['kyc_pending'] ?? 0) > 0)
                                        <div class="border-b border-gray-100 dropdown-item">
                                            <div class="flex items-start gap-3">
                                                <div class="w-2 h-2 bg-amber-500 rounded-full mt-1.5"></div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">KYC en attente</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $operational['pending_tasks']['kyc_pending'] }} documents à vérifier</p>
                                                    <p class="mt-1 text-xs text-gray-400">En attente</p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if(($operational['pending_tasks']['loan_applications'] ?? 0) > 0)
                                        <div class="border-b border-gray-100 dropdown-item">
                                            <div class="flex items-start gap-3">
                                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5"></div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">Demandes de prêt</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $operational['pending_tasks']['loan_applications'] }} demandes en attente</p>
                                                    <p class="mt-1 text-xs text-gray-400">À traiter</p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @else
                                    <div class="p-4 text-sm text-center text-gray-500">
                                        Aucune notification
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div class="relative">
                            <button id="userMenuBtn" class="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-2 py-1.5 transition">
                                <div class="flex items-center justify-center w-8 h-8 text-xs font-semibold text-white rounded-full user-avatar">
                                    {{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1) . substr(auth()->user()->last_name ?? 'D', 0, 1)) }}
                                </div>
                                <i class="hidden text-xs text-gray-600 fas fa-chevron-down sm:block"></i>
                            </button>

                            <!-- User Dropdown -->
                            <div id="userDropdown" class="dropdown-menu">
                                <div class="p-4 border-b border-gray-200">
                                    <p class="text-sm font-bold text-gray-900">{{ auth()->user()->full_name ?? 'Admin Système' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ auth()->user()->email ?? 'admin@mieyayra.com' }}</p>
                                    <div class="mt-2">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded">{{ ucfirst(str_replace('_', ' ', auth()->user()->role ?? 'administrateur')) }}</span>
                                    </div>
                                </div>
                                <a href="#" class="flex items-center gap-3 dropdown-item">
                                    <i class="w-5 text-gray-400 fas fa-user"></i>
                                    <span class="text-sm text-gray-700">Mon profil</span>
                                </a>
                                <a href="#" class="flex items-center gap-3 dropdown-item">
                                    <i class="w-5 text-gray-400 fas fa-cog"></i>
                                    <span class="text-sm text-gray-700">Paramètres</span>
                                </a>
                                <a href="#" class="flex items-center gap-3 dropdown-item">
                                    <i class="w-5 text-gray-400 fas fa-shield-alt"></i>
                                    <span class="text-sm text-gray-700">Sécurité</span>
                                </a>
                                <div class="my-1 border-t border-gray-200"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full gap-3 text-red-600 dropdown-item hover:bg-red-50">
                                        <i class="w-5 fas fa-sign-out-alt"></i>
                                        <span class="text-sm font-semibold">Déconnexion</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="p-4 mb-6 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    @if (session('temporary_password'))
                        <p class="mt-2 text-sm"><strong>Mot de passe temporaire :</strong> <code class="px-2 py-1 bg-green-200 rounded">{{ session('temporary_password') }}</code></p>
                    @endif
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 mb-6 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 mb-6 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="font-semibold">Erreurs de validation :</span>
                    </div>
                    <ul class="ml-6 text-sm list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    <script>
        // ===============================
        // 🔹 TOGGLE SIDEBAR MOBILE
        // ===============================
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });

        closeSidebar?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });

        sidebarOverlay?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });

        // ===============================
        // 🔹 NOTIFICATION DROPDOWN
        // ===============================
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');

        if (notificationBtn && notificationDropdown) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
                userDropdown?.classList.remove('show');
            });

            // Empêcher la fermeture quand on clique DANS le menu de notifications
            notificationDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // ===============================
        // 🔹 USER MENU DROPDOWN
        // ===============================
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
                notificationDropdown?.classList.remove('show');
            });

            userDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // ===============================
        // 🔹 FERMER DROPDOWNS AU CLIC EXTERNE
        // ===============================
        document.addEventListener('click', () => {
            notificationDropdown?.classList.remove('show');
            userDropdown?.classList.remove('show');
        });

        // ===============================
        // 🔹 NAVBAR SHADOW ON SCROLL
        // ===============================
        const navbar = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 10) {
                navbar?.classList.add('navbar-shadow');
            } else {
                navbar?.classList.remove('navbar-shadow');
            }
        });

        // ===============================
        // 🔹 CLOSE SIDEBAR ON MOBILE LINK CLICK
        // ===============================
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });

        // ===============================
        // 🔹 SEARCH FOCUS STYLE
        // ===============================
        const searchInput = document.querySelector('.search-input');
        searchInput?.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-blue-500');
        });

        searchInput?.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-blue-500');
        });

        // ===============================
        // 🔹 KEYBOARD SHORTCUTS
        // ===============================
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K → focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput?.focus();
            }

            // ESC → fermer menus et sidebar
            if (e.key === 'Escape') {
                notificationDropdown?.classList.remove('show');
                userDropdown?.classList.remove('show');
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
            }
        });

        // ===============================
        // 🔹 WINDOW RESIZE HANDLER
        // ===============================
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                }
            }, 250);
        });

        // ===============================
        // 🔹 AUTO-HIDE ALERTS (5 sec)
        // ===============================
        // setTimeout(() => {
        //     const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100, .bg-yellow-100');
        //     alerts.forEach(alert => {
        //         alert.style.transition = 'opacity 0.5s ease';
        //         alert.style.opacity = '0';
        //         setTimeout(() => alert.remove(), 500);
        //     });
        // }, 5000);

        // ===============================
        // 🔹 CONSOLE LOG DEMO
        // ===============================
        console.log('🏦 MIE YAYRA Admin Dashboard');
        console.log('✅ Interface chargée avec succès');
        console.log('📱 Mode responsive activé');
        console.log('🔐 Connexion sécurisée établie');
    </script>
    @stack('scripts')
</body>
</html>
