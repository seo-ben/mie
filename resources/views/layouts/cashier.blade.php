<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Guichet Caissier') - {{ config('app.name') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                @if (session('print_receipt'))
                    Swal.fire({
                        title: 'Opération Réussie !',
                        html: "{!! addslashes(session('success')) !!}",
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-2"></i> IMPRIMER LE REÇU',
                        cancelButtonText: 'Plus tard',
                        confirmButtonColor: '#00d1b2',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open("{{ session('print_receipt') }}", '_blank');
                        }
                    });
                @else
                    Toast.fire({ icon: 'success', title: 'Succès', text: "{!! addslashes(session('success')) !!}" });
                @endif
            });
        @endif

        @if (session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({ icon: 'error', title: 'Erreur', text: "{!! addslashes(session('error')) !!}" });
            });
        @endif

        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({ icon: 'error', title: 'Erreur de Validation', text: "Veuillez vérifier les champs du formulaire." });
            });
        @endif

        @if (session('info'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({ icon: 'info', title: 'Information', text: "{!! addslashes(session('info')) !!}" });
            });
        @endif

        @if (session('warning'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({ icon: 'warning', title: 'Attention', text: "{!! addslashes(session('warning')) !!}" });
            });
        @endif
    </script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f1923;
            min-height: 100vh;
            color: #e1e8ed;
        }

        /* ========== SIDEBAR CAISSIER ========== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 270px;
            background: linear-gradient(180deg, #1a2332 0%, #0d1b2a 100%);
            padding: 20px 0;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid rgba(0, 209, 178, 0.15);
        }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(0, 209, 178, 0.3); border-radius: 10px; }

        .sidebar.collapsed { width: 80px; }

        .sidebar-brand {
            padding: 0 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #00d1b2, #00b4d8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            flex-shrink: 0;
        }

        .sidebar-brand h4 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 1.1rem;
            white-space: nowrap;
            transition: opacity 0.3s;
        }

        .sidebar-brand .brand-sub {
            font-size: 0.7rem;
            color: #00d1b2;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar.collapsed .sidebar-brand h4,
        .sidebar.collapsed .sidebar-brand .brand-sub { opacity: 0; display: none; }

        .menu-section {
            padding: 10px 20px 5px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(0, 209, 178, 0.6);
        }

        .sidebar.collapsed .menu-section { opacity: 0; display: none; }

        .sidebar-menu { list-style: none; padding: 0; margin: 0; }

        .menu-item { margin-bottom: 2px; }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 11px 20px;
            color: rgba(225, 232, 237, 0.7);
            text-decoration: none;
            transition: all 0.2s ease;
            gap: 14px;
            position: relative;
            font-size: 0.88rem;
        }

        .menu-link:hover {
            background: rgba(0, 209, 178, 0.08);
            color: #00d1b2;
        }

        .menu-link.active {
            background: rgba(0, 209, 178, 0.12);
            color: #00d1b2;
            border-left: 3px solid #00d1b2;
        }

        .menu-link i { font-size: 1.1rem; width: 22px; text-align: center; flex-shrink: 0; }
        .menu-link span { white-space: nowrap; transition: opacity 0.3s; }
        .sidebar.collapsed .menu-link span { opacity: 0; display: none; }

        .menu-badge {
            margin-left: auto;
            background: rgba(0, 209, 178, 0.2);
            color: #00d1b2;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .menu-badge.danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: 270px;
            min-height: 100vh;
            transition: all 0.3s ease;
            background: #0f1923;
        }

        .sidebar.collapsed ~ .main-content { margin-left: 80px; }

        /* ========== TOP NAVBAR ========== */
        .top-navbar {
            background: #1a2332;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom: 1px solid rgba(0, 209, 178, 0.1);
        }

        .navbar-left { display: flex; align-items: center; gap: 20px; }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: #00d1b2;
            cursor: pointer;
            padding: 5px 10px;
            transition: all 0.3s;
        }

        .toggle-sidebar:hover {
            background: rgba(0, 209, 178, 0.1);
            border-radius: 5px;
        }

        .page-title-bar h5 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
        }

        .page-title-bar span {
            font-size: 0.75rem;
            color: rgba(225, 232, 237, 0.5);
        }

        .navbar-right { display: flex; align-items: center; gap: 18px; }

        .session-indicator {
            background: rgba(0, 209, 178, 0.1);
            border: 1px solid rgba(0, 209, 178, 0.3);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            color: #00d1b2;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .session-indicator .pulse {
            width: 8px;
            height: 8px;
            background: #00d1b2;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 209, 178, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(0, 209, 178, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 209, 178, 0); }
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 25px;
            transition: background 0.3s;
        }

        .user-profile:hover { background: rgba(0, 209, 178, 0.1); }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d1b2, #00b4d8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; font-size: 0.85rem; color: white; }
        .user-role { font-size: 0.7rem; color: #00d1b2; }

        /* Dropdown */
        .dropdown-menu {
            background: #1a2332;
            border: 1px solid rgba(0, 209, 178, 0.15);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            padding: 8px;
        }

        .dropdown-item {
            color: rgba(225, 232, 237, 0.8);
            padding: 9px 14px;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.85rem;
        }

        .dropdown-item:hover {
            background: rgba(0, 209, 178, 0.1);
            color: #00d1b2;
        }

        .dropdown-divider { border-color: rgba(0, 209, 178, 0.1); }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f1923; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 209, 178, 0.3); border-radius: 3px; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { width: 270px; transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .session-indicator span { display: none; }
            .user-info { display: none; }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: rgba(0, 0, 0, 0.6);
                z-index: 999;
            }
            .sidebar-overlay.show { display: block; }
        }

        /* ========== FOOTER ========== */
        .footer {
            background: #1a2332;
            padding: 15px 30px;
            text-align: center;
            color: rgba(225, 232, 237, 0.4);
            font-size: 0.8rem;
            margin-top: 40px;
            border-top: 1px solid rgba(0, 209, 178, 0.1);
        }

        /* ========== LOADING SPINNER ========== */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar Caissier -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-cash-register"></i>
            </div>
            <div>
                <h4>MIE YAYRA</h4>
                <span class="brand-sub">Guichet Caissier</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <div class="menu-section">Principal</div>

            <li class="menu-item">
                <a href="{{ route('caissier.dashboard') }}" class="menu-link {{ request()->routeIs('caissier.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de Bord</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('caissier.terminal') }}" class="menu-link {{ request()->routeIs('caissier.terminal') ? 'active' : '' }}">
                    <i class="fas fa-cash-register"></i>
                    <span>Terminal de Caisse</span>
                </a>
            </li>
            <!-- Opérations de Caisse -->
            <li class="menu-section">Opérations</li>
            <li class="menu-item">
                <a href="{{ route('caissier.depot') }}" class="menu-link {{ request()->routeIs('caissier.depot') ? 'active' : '' }}">
                    <i class="fas fa-arrow-down"></i>
                    <span>Encaissement (Dépôt)</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('caissier.retrait') }}" class="menu-link {{ request()->routeIs('caissier.retrait') ? 'active' : '' }}">
                    <i class="fas fa-arrow-up"></i>
                    <span>Décaissement (Retrait)</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('caissier.loans.disbursement') }}" class="menu-link {{ request()->routeIs('caissier.loans.disbursement') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Décaissement Prêts</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('caissier.logs') }}" class="menu-link {{ request()->routeIs('caissier.logs') ? 'active' : '' }}">
                    <i class="fas fa-list-ul"></i>
                    <span>Mes Opérations</span>
                </a>
            </li>

            <div class="menu-section">Gestion</div>

            <li class="menu-item">
                <a href="{{ route('caissier.clients.register-with-tontine') }}" class="menu-link {{ request()->routeIs('caissier.clients.register-with-tontine') ? 'active' : '' }}">
                    <i class="fas fa-user-plus text-teal-accent"></i>
                    <span>Inscription Express</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('caissier.clients.index') }}" class="menu-link {{ request()->routeIs('caissier.clients.index') || (request()->routeIs('caissier.clients.*') && !request()->routeIs('caissier.clients.register-with-tontine')) ? 'active' : '' }}">
                    <i class="fas fa-users text-teal-accent"></i>
                    <span>Tous les Clients</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('caissier.accounts.index') }}" class="menu-link {{ request()->routeIs('caissier.accounts.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet"></i>
                    <span>Comptes Tontine</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('caissier.transactions.index') }}" class="menu-link {{ request()->routeIs('caissier.transactions.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Historique</span>
                </a>
            </li>

            <div class="menu-section">Compte</div>

            <li class="menu-item">
                <a href="#" class="menu-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title-bar">
                    <h5>@yield('page-title', 'Guichet')</h5>
                    <span>@yield('page-subtitle', '')</span>
                </div>
            </div>

            <div class="navbar-right">
                <div class="session-indicator">
                    <div class="pulse"></div>
                    <span>Session active</span>
                </div>

                <!-- User Profile -->
                <div class="dropdown">
                    <div class="user-profile" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->full_name }}</span>
                            <span class="user-role">Caissier</span>
                        </div>
                        <i class="fas fa-chevron-down ms-1" style="color: rgba(225,232,237,0.5); font-size: 0.7rem;"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i> Mon profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('password.change') }}">
                                <i class="fas fa-key me-2"></i> Changer mot de passe
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main style="padding: 25px 30px;">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p class="mb-0">© {{ date('Y') }} MIE YAYRA Microfinance – Guichet Caissier</p>
        </footer>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner-border" style="width: 3rem; height: 3rem; color: #00d1b2;" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Toggle Sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                document.getElementById('sidebarOverlay').classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('cashierSidebarState', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
            }
        });

        // Restore sidebar state
        window.addEventListener('DOMContentLoaded', function() {
            const state = localStorage.getItem('cashierSidebarState');
            if (state === 'collapsed') {
                document.getElementById('sidebar').classList.add('collapsed');
            }
        });

        // Mobile overlay close
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('show');
            this.classList.remove('show');
        });

        // CSRF Token for AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Utility functions
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        function showLoading() {
            document.querySelector('.loading-spinner').style.display = 'block';
        }

        function hideLoading() {
            document.querySelector('.loading-spinner').style.display = 'none';
        }
    </script>

    @stack('scripts')
</body>
</html>
