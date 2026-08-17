<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MIE YAYRA - Administration')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin-corporate.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'success',
                    title: 'Protocole Validé',
                    text: "{{ session('success') }}"
                });
            });
        @endif

        @if (session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Alerte Système',
                    text: "{{ session('error') }}"
                });
            });
        @endif

        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Erreur de Validation',
                    text: "Veuillez vérifier les champs du formulaire."
                });
            });
        @endif

        @if (session('info'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'info',
                    title: 'Information',
                    text: "{{ session('info') }}"
                });
            });
        @endif
        
        @if (session('warning'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: "{{ session('warning') }}"
                });
            });
        @endif
    </script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        @media (max-width: 1024px) {
            #sidebar:not(.open) { transform: translateX(-100%); }
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
    <aside id="sidebar" class="fixed top-0 left-0 z-40 h-screen sidebar-corporate sidebar-transition">
        <div class="flex flex-col h-full">
            <!-- Institutional Branding -->
            <div class="p-6 border-b border-slate-100 mb-2">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white shadow-sm">
                        <i class="fas fa-shield-halved text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">MIE YAYRA</h2>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Pilotage Bancaire</p>
                    </div>
                </div>
            </div>

            <!-- Professional Navigation -->
            <nav class="flex-1 py-4 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span>Tableau de Bord</span>
                </a>

                <div class="sidebar-section-title">Portefeuille Clients</div>
                <a href="{{ route('admin.clients.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Registre des Clients</span>
                </a>
                <a href="{{ route('admin.accounts.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                    <i class="fas fa-vault"></i>
                    <span>Registre des Actifs</span>
                </a>

                <div class="sidebar-section-title">Division Trésorerie</div>
                <a href="{{ route('admin.accounts.depot') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.accounts.depot') ? 'active' : '' }}">
                    <i class="fas fa-circle-dollar-to-slot text-emerald-500"></i>
                    <span>Injections de Flux (Dépôts)</span>
                </a>
                <a href="{{ route('admin.accounts.retrait') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.accounts.retrait') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-dollar text-rose-500"></i>
                    <span>Décaissements (Retraits)</span>
                </a>
                <a href="{{ route('admin.cashier.sessions.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.cashier.sessions.*') ? 'active' : '' }}">
                    <i class="fas fa-cash-register text-blue-500"></i>
                    <span>Sessions de Caisse</span>
                </a>
                <a href="{{ route('admin.loans.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.loans.*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Engagements de Crédit</span>
                </a>
                <a href="{{ route('admin.tontines.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.tontines.*') ? 'active' : '' }}">
                    <i class="fas fa-rotate"></i>
                    <span>Épargne Mutuelle (Tontines)</span>
                </a>

                <div class="sidebar-section-title">Audit Interne</div>
                <a href="{{ route('admin.profitability.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.profitability.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie text-emerald-500"></i>
                    <span>Rentabilité</span>
                </a>
                <a href="{{ route('admin.transactions.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Audit du Grand Livre</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Analyses Officiers</span>
                </a>
                <a href="{{ route('admin.reports.regulatory.aging') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.reports.regulatory.aging') ? 'active' : '' }}">
                    <i class="fas fa-file-shield text-amber-600"></i>
                    <span>Balance Agée (Réglementaire)</span>
                </a>
                <a href="{{ route('admin.reports.agencies.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.reports.agencies*') ? 'active' : '' }}">
                    <i class="fas fa-chart-column"></i>
                    <span>Performance Réseau</span>
                </a>

                <div class="sidebar-section-title">Infrastructure</div>
                <a href="{{ route('admin.users.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-user-tie"></i>
                    <span>Registre du Personnel</span>
                </a>
                <a href="{{ route('admin.agencies.index') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.agencies.*') ? 'active' : '' }}">
                    <i class="fas fa-landmark"></i>
                    <span>Réseau d'Agences</span>
                </a>
                <a href="{{ route('admin.config.parameters') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.config.parameters') ? 'active' : '' }}">
                    <i class="fas fa-cogs"></i>
                    <span>Configuration Système</span>
                </a>
                <a href="{{ route('admin.system-health') }}" class="sidebar-item-corporate {{ request()->routeIs('admin.system-health') ? 'active' : '' }}">
                    <i class="fas fa-server"></i>
                    <span>Santé du Système</span>
                </a>
            </nav>

            <!-- Auditor Profile -->
            <div class="p-6 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 font-bold text-[10px] border border-slate-200">
                        {{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1) . substr(auth()->user()->last_name ?? 'D', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate">{{ auth()->user()->full_name ?? 'Auditeur' }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase">{{ auth()->user()->role ?? 'Admin' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 text-[10px] font-bold text-slate-500 hover:text-red-600 transition border border-slate-100 rounded-lg">
                        <i class="fas fa-power-off"></i>
                        <span>Déconnexion Sécurisée</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="min-h-screen lg:ml-[280px]">
        <!-- Institutional Navbar -->
        <nav class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-200 h-16 flex items-center shadow-sm">
            <div class="px-6 w-full flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button id="menuToggle" class="p-2 text-slate-500 lg:hidden">
                        <i class="fas fa-bars-staggered"></i>
                    </button>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight">
                        <span class="text-slate-400 font-medium">Console /</span> @yield('page-title', 'Aperçu')
                    </h1>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Global Search -->
                    <div class="relative hidden sm:block">
                        <input type="text" placeholder="Rechercher ID Membre..." class="w-64 bg-slate-50 border border-slate-200 rounded-lg px-8 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 outline-none transition">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                    </div>

                    <!-- System Notifications -->
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button id="notificationBtn" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-blue-600 transition relative">
                                <i class="fas fa-bell text-xs"></i>
                                <span id="notificationBadge" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 rounded-full text-[10px] font-bold text-white flex items-center justify-center notification-badge hidden">0</span>
                            </button>
                            
                            <!-- Notification Dropdown -->
                            <div id="notificationDropdown" class="dropdown-menu w-[360px]">
                                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                                    <h3 class="font-bold text-slate-800 text-sm">Notifications</h3>
                                    <button id="markAllReadBtn" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                        Tout marquer lu
                                    </button>
                                </div>
                                
                                <div id="notificationList" class="max-h-[400px] overflow-y-auto custom-scrollbar">
                                    <!-- Les notifications seront chargées ici dynamiquement -->
                                    <div class="p-8 text-center text-slate-400">
                                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                        <p class="text-xs">Chargement...</p>
                                    </div>
                                </div>
                                
                                <div class="p-3 border-t border-slate-100 text-center">
                                    <a href="{{ route('admin.notifications.all') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                        Voir toutes les notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Test Notification Button (pour debug - à supprimer en production) -->
                        @if(config('app.debug'))
                        <button id="testNotificationBtn" class="w-8 h-8 rounded-lg bg-green-50 border border-green-200 flex items-center justify-center text-green-500 hover:text-green-600 transition" title="Créer notification test">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                        @endif
                        
                        <div class="text-right hidden xl:block">
                            <p class="text-[10px] font-bold text-slate-700 leading-none">{{ auth()->user()->full_name }}</p>
                            <p class="text-[8px] font-bold text-green-600 uppercase mt-1">Session Vérifiée</p>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dynamic Content -->
        <main class="p-8 max-w-[1600px] mx-auto">
            <!-- Professional Messaging -->
            <!-- Renplacement des alertes statiques par des Toasts SweetAlert2 via JS -->
            <!-- Les messages de session seront gérés par le script en bas de page -->
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
        const sidebarLinks = document.querySelectorAll('.sidebar-link-premium');
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

        console.log('🏦 MIE YAYRA - Console d\'Administration');
        console.log('✅ Interface chargée avec succès');
        console.log('📱 Mode responsive activé');
        console.log('🔐 Connexion sécurisée établie');

        // ===============================
        // 🔔 NOTIFICATION SYSTEM
        // ===============================
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        const testNotificationBtn = document.getElementById('testNotificationBtn');

        // Charger les notifications
        async function loadNotifications() {
            try {
                const response = await fetch('{{ route("admin.notifications.index") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) throw new Error('Erreur réseau');
                
                const data = await response.json();
                
                // Mise à jour du badge
                if (data.unread_count > 0) {
                    notificationBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    notificationBadge.classList.remove('hidden');
                } else {
                    notificationBadge.classList.add('hidden');
                }
                
                // Afficher les notifications
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = `
                        <div class="p-8 text-center text-slate-400">
                            <i class="fas fa-bell-slash text-3xl mb-3"></i>
                            <p class="text-sm font-medium">Aucune notification</p>
                            <p class="text-xs mt-1">Vous êtes à jour !</p>
                        </div>
                    `;
                } else {
                    notificationList.innerHTML = data.notifications.map(notification => `
                        <div class="notification-item p-4 border-b border-slate-50 hover:bg-slate-50 cursor-pointer transition ${notification.is_read ? 'opacity-60' : ''}" 
                             data-id="${notification.id}"
                             onclick="markNotificationRead(${notification.id})">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg ${notification.type_class} flex items-center justify-center shrink-0">
                                    <i class="fas ${notification.icon} text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-800 truncate">${notification.title}</p>
                                        ${!notification.is_read ? '<span class="w-2 h-2 bg-blue-500 rounded-full shrink-0"></span>' : ''}
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">${notification.message}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">${notification.time_ago}</p>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
                
                console.log('🔔 Notifications chargées:', data.notifications.length);
                
            } catch (error) {
                console.error('Erreur chargement notifications:', error);
                notificationList.innerHTML = `
                    <div class="p-8 text-center text-red-400">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p class="text-xs">Erreur de chargement</p>
                    </div>
                `;
            }
        }

        // Marquer une notification comme lue
        async function markNotificationRead(id) {
            try {
                await fetch(`/admin/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                // Recharger les notifications
                loadNotifications();
                
            } catch (error) {
                console.error('Erreur marquage notification:', error);
            }
        }

        // Marquer toutes comme lues
        markAllReadBtn?.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                await fetch('{{ route("admin.notifications.markAllRead") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                loadNotifications();
                
            } catch (error) {
                console.error('Erreur marquage toutes notifications:', error);
            }
        });

        // Créer une notification de test (debug)
        testNotificationBtn?.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                await fetch('{{ route("admin.notifications.createTest") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                loadNotifications();
                
            } catch (error) {
                console.error('Erreur création notification test:', error);
            }
        });

        // Charger les notifications au chargement et ouvrir le dropdown
        document.addEventListener('DOMContentLoaded', loadNotifications);
        
        notificationBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Fermer l'éventuel menu utilisateur s'il existe
            userDropdown?.classList.remove('show');
            
            const isShowing = notificationDropdown.classList.toggle('show');
            if (isShowing) {
                loadNotifications();
            }
        });

        // Rafraîchir les notifications toutes les 60 secondes
        setInterval(loadNotifications, 60000);

        // Exposer la fonction globalement
        window.markNotificationRead = markNotificationRead;
    </script>
    @stack('scripts')
</body>
</html>
