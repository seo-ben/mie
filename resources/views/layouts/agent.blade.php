<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'YAYRA Agent') - {{ config('app.name') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    title: 'Opération Réussie',
                    text: "{{ session('success') }}"
                });
            });
        @endif

        @if (session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Erreur',
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

    <!-- Custom CSS -->
    {{-- <link rel="stylesheet" href="{{ asset('css/agent-dashboard.css') }}"> --}}

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-brand {
            padding: 0 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand i {
            font-size: 2rem;
            color: white;
        }

        .sidebar-brand h4 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 1.4rem;
            white-space: nowrap;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .sidebar-brand h4 {
            opacity: 0;
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 15px;
            position: relative;
        }

        .menu-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .menu-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left: 4px solid white;
        }

        .menu-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .menu-link span {
            white-space: nowrap;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .menu-link span {
            opacity: 0;
            display: none;
        }

        .menu-badge {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }

        /* Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
            padding: 5px 10px;
            transition: all 0.3s;
        }

        .toggle-sidebar:hover {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 5px;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 25px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.3s;
        }

        .notification-bell:hover {
            color: #667eea;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
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

        .user-profile:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #212529;
        }

        .user-role {
            font-size: 0.75rem;
            color: #6c757d;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 10px;
        }

        .dropdown-item {
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .dropdown-divider {
            margin: 10px 0;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
            }

            .search-box {
                display: none;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }

            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        /* Footer */
        .footer {
            background: white;
            padding: 20px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 50px;
            border-top: 1px solid #dee2e6;
        }

        /* Quick Actions Floating Button */
        .quick-actions-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 998;
        }

        .quick-actions-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-landmark"></i>
            <h4>MIE YAYRA</h4>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="{{ route('agent.dashboard') }}" class="menu-link {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="{{ route('agent.accounts.daily-collection') }}" class="menu-link {{ request()->routeIs('agent.accounts.daily-collection') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>Tournée du Jour</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('agent.clients.index') }}" class="menu-link {{ request()->routeIs('agent.clients.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('agent.accounts.index') }}" class="menu-link {{ request()->routeIs('agent.accounts.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet"></i>
                    <span>Comptes</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('agent.transactions.index') }}" class="menu-link {{ request()->routeIs('agent.transactions.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="{{ route('agent.accounts.quick-deposit') }}" class="menu-link {{ request()->routeIs('agent.accounts.quick-deposit') ? 'active' : '' }}">
                    <i class="fas fa-bolt me-2"></i>Dépôt Rapide
                </a>
            </li>

            {{-- <li class="menu-item">
                <a href="{{ route('agent.reports.daily') }}" class="menu-link {{ request()->routeIs('agent.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Rapports</span>
                </a>
            </li>

            <li class="mt-4 menu-item">
                <a href="#" class="menu-link">
                    <i class="fas fa-cog"></i>
                    <span>Paramètres</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="#" class="menu-link">
                    <i class="fas fa-question-circle"></i>
                    <span>Aide</span>
                </a>
            </li> --}}

            <li class="menu-item">
                <a href="#" class="menu-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="search-box">
                    <input type="text" placeholder="Rechercher un client, compte...">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="navbar-right">
                <!-- Notifications -->
                <div class="dropdown">
                    <div class="notification-bell" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <li class="dropdown-header">
                            <strong>Notifications</strong>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-exclamation-circle text-danger"></i>
                                3 prêts en retard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user-plus text-success"></i>
                                5 nouveaux clients inscrits
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-clock text-warning"></i>
                                2 tontines arrivent à échéance
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="text-center dropdown-item text-primary" href="#">
                                Voir toutes les notifications
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- User Profile -->
                <div class="dropdown">
                    <div class="user-profile" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->full_name }}</span>
                            <span class="user-role">{{ ucfirst(auth()->user()->role) }}</span>
                        </div>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i> Mon profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-2"></i> Paramètres
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
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
        <main>
            <!-- Toasts SweetAlert2 (remplace les alertes statiques) -->
            <!-- Configuré via le script en bas de page -->
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p class="mb-0">© {{ date('Y') }} MicroFinance. Tous droits réservés.</p>
        </footer>
    </div>

    <!-- Quick Actions Button -->
    <div class="quick-actions-btn" data-bs-toggle="modal" data-bs-target="#quickActionsModal" title="Actions rapides">
        <i class="fas fa-bolt"></i>
    </div>

    <!-- Quick Actions Modal -->
    <div class="modal fade" id="quickActionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Actions rapides</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="{{ route('agent.clients.create') }}" class="py-3 btn btn-outline-primary w-100">
                                <i class="mb-2 fas fa-user-plus fa-2x d-block"></i>
                                Nouveau client
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('agent.clients.index') }}" class="py-3 btn btn-outline-success w-100">
                                <i class="mb-2 fas fa-wallet fa-2x d-block"></i>
                                Nouveau compte
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('agent.accounts.quick-deposit') }}" class="py-3 btn btn-outline-info w-100">
                                <i class="mb-2 fas fa-money-bill-wave fa-2x d-block"></i>
                                Dépôt
                            </a>
                        </div>

                        <div class="col-6">
                            <a href="{{ route('agent.reports.daily') }}" class="py-3 btn btn-outline-warning w-100">
                                <i class="mb-2 fas fa-chart-line fa-2x d-block"></i>
                                Rapport
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (optional but useful) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Toggle Sidebar
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');

            // Save state to localStorage
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('sidebarState', 'collapsed');
            } else {
                localStorage.setItem('sidebarState', 'expanded');
            }
        });

        // Restore sidebar state
        window.addEventListener('DOMContentLoaded', function() {
            const sidebarState = localStorage.getItem('sidebarState');
            const sidebar = document.getElementById('sidebar');

            if (sidebarState === 'collapsed') {
                sidebar.classList.add('collapsed');
            }
        });

        // Mobile Sidebar Toggle
        if (window.innerWidth <= 768) {
            document.getElementById('toggleSidebar').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
                document.getElementById('sidebarOverlay').classList.toggle('show');
            });

            document.getElementById('sidebarOverlay').addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('show');
                this.classList.remove('show');
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // CSRF Token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Format numbers
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        // Show loading spinner
        function showLoading() {
            document.querySelector('.loading-spinner').style.display = 'block';
        }

        // Hide loading spinner
        function hideLoading() {
            document.querySelector('.loading-spinner').style.display = 'none';
        }
    </script>

    @stack('scripts')
</body>
</html>
