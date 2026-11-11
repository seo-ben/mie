<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Guichet Caissier') - {{ config('app.name', 'FSD YAYRA') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                        title: 'Opération Validée !',
                        html: "{!! addslashes(session('success')) !!}",
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-2"></i> Imprimer le Reçu',
                        cancelButtonText: 'Fermer',
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#64748b',
                        allowOutsideClick: false,
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

        @if (isset($errors) && $errors->any())
            document.addEventListener('DOMContentLoaded', function() {
                Toast.fire({ icon: 'error', title: 'Erreur de Validation', text: "Vérifiez les données saisies." });
            });
        @endif
    </script>

    <style>
        :root {
            --sidebar-bg: #111827;
            --sidebar-border: rgba(255, 255, 255, 0.06);
            --sidebar-text: #94a3b8;
            --sidebar-hover-bg: rgba(255, 255, 255, 0.05);
            --sidebar-hover-text: #ffffff;
            --sidebar-active-bg: rgba(16, 185, 129, 0.12);
            --sidebar-active-text: #10b981;
            --sidebar-w: 260px;
            --sidebar-collapsed-w: 78px;

            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #ecfdf5;

            --bg-page: #f5f6fa;
            --card-bg: #ffffff;
            --border-color: #f1f5f9;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-page);
            min-height: 100vh;
            color: var(--text-body);
            overflow-x: hidden;
        }

        /* ========================================================
           KPI PASTEL CARDS (INSPIRATION MOCKUP EMART / DASHBOARD)
           ======================================================== */
        .kpi-pastel-card {
            border-radius: 16px;
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 122px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            position: relative;
        }

        .kpi-pastel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .kpi-pastel-green {
            background: #e8f8f0;
            border: 1px solid #c8eed9;
        }
        .kpi-pastel-green .kpi-icon-wrapper {
            background: #ffffff;
            color: #10b981;
        }

        .kpi-pastel-amber {
            background: #fef7ea;
            border: 1px solid #fce8ca;
        }
        .kpi-pastel-amber .kpi-icon-wrapper {
            background: #ffffff;
            color: #f59e0b;
        }

        .kpi-pastel-peach {
            background: #fff3ec;
            border: 1px solid #fedecd;
        }
        .kpi-pastel-peach .kpi-icon-wrapper {
            background: #ffffff;
            color: #f97316;
        }

        .kpi-pastel-blue {
            background: #eef5ff;
            border: 1px solid #dbeafe;
        }
        .kpi-pastel-blue .kpi-icon-wrapper {
            background: #ffffff;
            color: #3b82f6;
        }

        .kpi-pastel-teal {
            background: #e9faf8;
            border: 1px solid #c6f3ed;
        }
        .kpi-pastel-teal .kpi-icon-wrapper {
            background: #ffffff;
            color: #0d9488;
        }

        .kpi-pastel-rose {
            background: #fef1f2;
            border: 1px solid #fcdadc;
        }
        .kpi-pastel-rose .kpi-icon-wrapper {
            background: #ffffff;
            color: #f43f5e;
        }

        .kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .kpi-icon-wrapper {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .kpi-trend {
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 255, 255, 0.75);
            padding: 3px 9px;
            border-radius: 20px;
        }

        .kpi-body {
            display: flex;
            flex-direction: column;
        }

        .kpi-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            letter-spacing: -0.02em;
            font-feature-settings: "tnum";
        }

        .kpi-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            margin-top: 4px;
        }

        /* SEGMENTED CONTROL / PILL TABS */
        .filter-segment-group {
            display: inline-flex;
            background: #eef2f6;
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
        }

        .filter-segment-pill {
            padding: 7px 16px;
            border-radius: 9px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .filter-segment-pill:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.6);
        }

        .filter-segment-pill.active {
            background: #ffffff;
            color: #0f172a;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }

        /* ========================================================
           SIDEBAR MODERNE SOMBRE (#111827) - INSPIRÉE DU MOCKUP
           ======================================================== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            padding: 0;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s ease;
            z-index: 1030;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .sidebar-brand-box {
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--sidebar-border);
            min-height: 70px;
        }

        .brand-content {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            flex-shrink: 0;
        }

        .brand-text-block {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 1.12rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.3px;
            margin: 0;
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 0.65rem;
            font-weight: 700;
            color: #10b981;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .btn-sidebar-collapse {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 0.85rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-sidebar-collapse:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        /* Menu de la sidebar */
        .sidebar-menu-list {
            list-style: none;
            padding: 14px 12px 24px 12px;
            margin: 0;
            flex: 1;
        }

        .menu-category-header {
            padding: 16px 12px 8px 12px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #64748b;
        }

        .menu-category-header:first-child {
            padding-top: 4px;
        }

        .menu-nav-item {
            margin-bottom: 2px;
        }

        .menu-nav-link {
            display: flex;
            align-items: center;
            padding: 9px 12px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 600;
            transition: all 0.2s ease;
            gap: 12px;
            position: relative;
        }

        .menu-nav-link:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-hover-text);
        }

        .menu-nav-link.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-text);
            font-weight: 700;
        }

        .menu-nav-link i.link-icon {
            font-size: 1rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
            color: #64748b;
            transition: color 0.2s;
        }

        .menu-nav-link:hover i.link-icon {
            color: #ffffff;
        }

        .menu-nav-link.active i.link-icon {
            color: #10b981;
        }

        .menu-nav-link .link-label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .menu-nav-link .link-arrow {
            font-size: 0.65rem;
            color: #475569;
            transition: transform 0.25s ease, color 0.2s;
        }

        .menu-nav-link:hover .link-arrow {
            color: #94a3b8;
        }

        .menu-nav-link.active .link-arrow {
            color: #10b981;
        }

        /* Sous-menus Dépliables (Collapsible Submenus) */
        .menu-parent-link {
            cursor: pointer;
            user-select: none;
        }

        .menu-parent-link[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .menu-parent-link[aria-expanded="true"] .link-arrow {
            transform: rotate(90deg);
            color: #10b981 !important;
        }

        .menu-parent-link.is-active-parent {
            color: #10b981;
            font-weight: 700;
        }

        .menu-parent-link.is-active-parent i.link-icon {
            color: #10b981;
        }

        .sidebar-submenu-list {
            list-style: none;
            padding-left: 0;
            margin: 4px 0 6px 16px;
            border-left: 1.5px solid rgba(255, 255, 255, 0.08);
        }

        .submenu-item {
            margin-bottom: 2px;
        }

        .submenu-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px 7px 16px;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 7px;
            transition: all 0.2s;
            position: relative;
        }

        .submenu-link::before {
            content: '';
            position: absolute;
            left: -1px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 1.5px;
            background: rgba(255, 255, 255, 0.15);
            transition: width 0.2s, background 0.2s;
        }

        .submenu-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.04);
        }

        .submenu-link:hover::before {
            width: 10px;
            background: #10b981;
        }

        .submenu-link.active {
            color: #10b981;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.12);
        }

        .submenu-link.active::before {
            width: 12px;
            background: #10b981;
        }

        .sidebar-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .sidebar-badge.badge-green { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .sidebar-badge.badge-amber { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .sidebar-badge.badge-rose { background: rgba(244, 63, 94, 0.2); color: #f43f5e; }

        /* ========================================================
           ZONE PRINCIPALE & TOPBAR BLANCHE (#ffffff)
           ======================================================== */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            height: 70px;
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        /* Barre de Recherche Topbar */
        .search-box-wrapper {
            position: relative;
            width: 340px;
        }

        .search-box-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.88rem;
            pointer-events: none;
        }

        .topbar-search-input {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 14px 8px 38px;
            font-size: 0.84rem;
            color: #1e293b;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .topbar-search-input:focus {
            background: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
        }

        .topbar-search-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        /* Actions Droite de la Topbar */
        .topbar-right-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-icon-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .topbar-icon-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .topbar-icon-btn .badge-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 7px;
            height: 7px;
            background: #f43f5e;
            border-radius: 50%;
            border: 1.5px solid #ffffff;
        }

        .pill-agency {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
        }

        .session-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 700;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .session-status-badge .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
        }

        .session-status-badge-closed {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 700;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .session-status-badge-closed .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
        }

        /* Profil utilisateur dans la topbar */
        .user-profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 6px 4px 10px;
            border-radius: 12px;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .user-profile-btn:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .user-avatar-round {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #0d9488 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
            flex-shrink: 0;
        }

        .user-info-text {
            text-align: right;
            line-height: 1.25;
        }

        .user-name-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .user-role-sub {
            font-size: 0.7rem;
            font-weight: 500;
            color: #94a3b8;
            margin: 0;
        }

        /* ========================================================
           CORPS DE PAGE ET COMPOSANTS BLANCS / PASTELS
           ======================================================== */
        .content-body {
            padding: 24px 32px 40px 32px;
            flex: 1;
        }

        .white-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }

        .white-card:hover {
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
            border-color: #cbd5e1;
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            line-height: 1.2;
            font-feature-settings: "tnum";
        }

        .stat-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-corporate {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-corporate th {
            background: #f8fafc;
            padding: 12px 16px;
            font-size: 0.72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-corporate td {
            padding: 13px 16px;
            font-size: 0.85rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table-corporate tr:last-child td {
            border-bottom: none;
        }

        .table-corporate tr:hover td {
            background: #f8fafc;
        }

        /* ========================================================
           COLLAPSED SIDEBAR (MINI-BAR) SUR DESKTOP
           ======================================================== */
        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-w);
        }

        body.sidebar-collapsed .main-wrapper {
            margin-left: var(--sidebar-collapsed-w);
        }

        body.sidebar-collapsed .brand-text-block,
        body.sidebar-collapsed .menu-category-header,
        body.sidebar-collapsed .link-label,
        body.sidebar-collapsed .link-arrow,
        body.sidebar-collapsed .sidebar-badge {
            display: none !important;
        }

        body.sidebar-collapsed .menu-nav-link {
            justify-content: center;
            padding: 12px 0;
        }

        body.sidebar-collapsed .menu-nav-link i.link-icon {
            margin: 0;
            font-size: 1.2rem;
        }

        /* ========================================================
           RESPONSIVE MOBILE
           ======================================================== */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0 !important;
            }
            .search-box-wrapper {
                width: 200px;
            }
            .top-navbar {
                padding: 0 16px;
            }
            .content-body {
                padding: 16px;
            }
        }

        @media (max-width: 640px) {
            .search-box-wrapper { display: none; }
            .pill-agency { display: none; }
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- SIDEBAR MODERNE SOMBRE (#161c28) -->
    <aside class="sidebar" id="sidebar">
        <!-- Logo & Titre -->
        <div class="sidebar-brand-box">
            <a href="{{ route('caissier.dashboard') }}" class="brand-content">
                <div class="brand-icon">
                    <i class="fas fa-vault"></i>
                </div>
                <div class="brand-text-block">
                    <h5 class="brand-title">FSD YAYRA</h5>
                    <span class="brand-sub">Guichet & Caisse</span>
                </div>
            </a>
            <button type="button" class="btn-sidebar-collapse d-none d-lg-block" id="btnCollapseSidebar" title="Réduire le menu">
                <i class="fas fa-angles-left"></i>
            </button>
            <button type="button" class="btn-sidebar-collapse d-lg-none" id="btnCloseSidebarMobile">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <!-- Navigation organisée en sous-menus dépliables (Collapsible) -->
        <ul class="sidebar-menu-list">
            <!-- SECTION 1: DASHBOARD -->
            <div class="menu-category-header">Dashboard</div>

            <li class="menu-nav-item">
                <a href="{{ route('caissier.dashboard') }}" class="menu-nav-link {{ request()->routeIs('caissier.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie link-icon"></i>
                    <span class="link-label">Tableau de Bord</span>
                </a>
            </li>

            <li class="menu-nav-item">
                <a href="{{ route('caissier.terminal') }}" class="menu-nav-link {{ request()->routeIs('caissier.terminal') ? 'active' : '' }}">
                    <i class="fas fa-cash-register link-icon"></i>
                    <span class="link-label">Terminal de Caisse</span>
                </a>
            </li>

            <!-- SECTION 2: OPÉRATIONS GUICHET (DÉPLIABLE) -->
            <div class="menu-category-header">Guichet & Opérations</div>

            @php
                $isOpsActive = request()->routeIs('caissier.depot') || request()->routeIs('caissier.retrait') || request()->routeIs('caissier.transfer');
            @endphp
            <li class="menu-nav-item">
                <a class="menu-nav-link menu-parent-link {{ $isOpsActive ? 'is-active-parent' : '' }}" data-bs-toggle="collapse" href="#subOps" role="button" aria-expanded="{{ $isOpsActive ? 'true' : 'false' }}">
                    <i class="fas fa-money-bill-transfer link-icon"></i>
                    <span class="link-label">Opérations Guichet</span>
                    <i class="fas fa-chevron-right link-arrow ms-auto"></i>
                </a>
                <div class="collapse {{ $isOpsActive ? 'show' : '' }}" id="subOps">
                    <ul class="sidebar-submenu-list">
                        <li class="submenu-item">
                            <a href="{{ route('caissier.depot') }}" class="submenu-link {{ request()->routeIs('caissier.depot') ? 'active' : '' }}">
                                <i class="fas fa-arrow-down fs-8 text-success me-1"></i> Encaissement (Dépôt)
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.retrait') }}" class="submenu-link {{ request()->routeIs('caissier.retrait') ? 'active' : '' }}">
                                <i class="fas fa-arrow-up fs-8 text-danger me-1"></i> Décaissement (Retrait)
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.transfer') }}" class="submenu-link {{ request()->routeIs('caissier.transfer') ? 'active' : '' }}">
                                <i class="fas fa-arrow-right-arrow-left fs-8 text-primary me-1"></i> Virement Inter-Comptes
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="javascript:void(0)" class="submenu-link" data-bs-toggle="modal" data-bs-target="#vaultTransferModal">
                                <i class="fas fa-building-columns fs-8 text-warning me-1"></i> Transfert Coffre-Fort
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="javascript:void(0)" class="submenu-link text-danger" data-bs-toggle="modal" data-bs-target="#reversalModal">
                                <i class="fas fa-rotate-left fs-8 text-danger me-1"></i> Extourne (PIN)
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- SECTION 3: PORTEFEUILLE CRÉDITS (DÉPLIABLE) -->
            <div class="menu-category-header">Crédits & Prêts</div>

            @php
                $isLoansActive = request()->routeIs('caissier.loans.*');
            @endphp
            <li class="menu-nav-item">
                <a class="menu-nav-link menu-parent-link {{ $isLoansActive ? 'is-active-parent' : '' }}" data-bs-toggle="collapse" href="#subLoans" role="button" aria-expanded="{{ $isLoansActive ? 'true' : 'false' }}">
                    <i class="fas fa-hand-holding-dollar link-icon"></i>
                    <span class="link-label">Gestion des Prêts</span>
                    <i class="fas fa-chevron-right link-arrow ms-auto"></i>
                </a>
                <div class="collapse {{ $isLoansActive ? 'show' : '' }}" id="subLoans">
                    <ul class="sidebar-submenu-list">
                        <li class="submenu-item">
                            <a href="{{ route('caissier.loans.index') }}" class="submenu-link {{ request()->routeIs('caissier.loans.index') && !request()->has('status') ? 'active' : '' }}">
                                <i class="fas fa-folder-open fs-8 text-muted me-1"></i> Tous les Prêts
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.loans.apply.form') }}" class="submenu-link {{ request()->routeIs('caissier.loans.apply.form') ? 'active' : '' }}">
                                <i class="fas fa-file-circle-plus fs-8 text-success me-1"></i> Demande de Prêt
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.loans.disbursement') }}" class="submenu-link {{ request()->routeIs('caissier.loans.disbursement') ? 'active' : '' }}">
                                <i class="fas fa-money-bill-wave fs-8 text-emerald-500 me-1"></i> Décaissements Prêts
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.loans.index', ['status' => 'disbursed']) }}" class="submenu-link {{ request()->routeIs('caissier.loans.index') && request('status') === 'disbursed' ? 'active' : '' }}">
                                <i class="fas fa-coins fs-8 text-warning me-1"></i> Remboursements Prêts
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- SECTION 4: GESTION CLIENTÈLE (DÉPLIABLE) -->
            <div class="menu-category-header">Clients & Comptes</div>

            @php
                $isClientsActive = request()->routeIs('caissier.clients.*') || request()->routeIs('caissier.accounts.*');
            @endphp
            <li class="menu-nav-item">
                <a class="menu-nav-link menu-parent-link {{ $isClientsActive ? 'is-active-parent' : '' }}" data-bs-toggle="collapse" href="#subClients" role="button" aria-expanded="{{ $isClientsActive ? 'true' : 'false' }}">
                    <i class="fas fa-address-book link-icon"></i>
                    <span class="link-label">Dossiers Clients</span>
                    <i class="fas fa-chevron-right link-arrow ms-auto"></i>
                </a>
                <div class="collapse {{ $isClientsActive ? 'show' : '' }}" id="subClients">
                    <ul class="sidebar-submenu-list">
                        <li class="submenu-item">
                            <a href="{{ route('caissier.clients.index') }}" class="submenu-link {{ request()->routeIs('caissier.clients.index') ? 'active' : '' }}">
                                <i class="fas fa-users fs-8 text-muted me-1"></i> Répertoire Clients
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.clients.register-with-tontine') }}" class="submenu-link {{ request()->routeIs('caissier.clients.register-with-tontine') ? 'active' : '' }}">
                                <i class="fas fa-user-plus fs-8 text-primary me-1"></i> Inscription Express
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.accounts.index') }}" class="submenu-link {{ request()->routeIs('caissier.accounts.*') ? 'active' : '' }}">
                                <i class="fas fa-piggy-bank fs-8 text-amber-500 me-1"></i> Comptes Tontine & Épargne
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- SECTION 5: AGENTS & TERRAIN (DÉPLIABLE) -->
            <div class="menu-category-header">Agents & Terrain</div>

            @php
                $isAgentsActive = request()->routeIs('caissier.field-agents.*');
            @endphp
            <li class="menu-nav-item">
                <a class="menu-nav-link menu-parent-link {{ $isAgentsActive ? 'is-active-parent' : '' }}" data-bs-toggle="collapse" href="#subAgents" role="button" aria-expanded="{{ $isAgentsActive ? 'true' : 'false' }}">
                    <i class="fas fa-users-viewfinder link-icon"></i>
                    <span class="link-label">Collectes Terrain</span>
                    <i class="fas fa-chevron-right link-arrow ms-auto"></i>
                </a>
                <div class="collapse {{ $isAgentsActive ? 'show' : '' }}" id="subAgents">
                    <ul class="sidebar-submenu-list">
                        <li class="submenu-item">
                            <a href="{{ route('caissier.field-agents.index') }}" class="submenu-link {{ request()->routeIs('caissier.field-agents.*') ? 'active' : '' }}">
                                <i class="fas fa-motorcycle fs-8 text-info me-1"></i> Supervision Tournées
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- SECTION 6: RAPPORTS & SESSIONS (DÉPLIABLE) -->
            <div class="menu-category-header">Rapports & Audit</div>

            @php
                $isReportsActive = request()->routeIs('caissier.logs') || request()->routeIs('caissier.reports') || request()->routeIs('caissier.session.*');
            @endphp
            <li class="menu-nav-item">
                <a class="menu-nav-link menu-parent-link {{ $isReportsActive ? 'is-active-parent' : '' }}" data-bs-toggle="collapse" href="#subReports" role="button" aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}">
                    <i class="fas fa-chart-line link-icon"></i>
                    <span class="link-label">États & Clôture</span>
                    <i class="fas fa-chevron-right link-arrow ms-auto"></i>
                </a>
                <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="subReports">
                    <ul class="sidebar-submenu-list">
                        <li class="submenu-item">
                            <a href="{{ route('caissier.logs') }}" class="submenu-link {{ request()->routeIs('caissier.logs') ? 'active' : '' }}">
                                <i class="fas fa-clock-rotate-left fs-8 text-muted me-1"></i> Journal des Flux
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.reports') }}" class="submenu-link {{ request()->routeIs('caissier.reports') ? 'active' : '' }}">
                                <i class="fas fa-chart-pie fs-8 text-emerald-500 me-1"></i> Rapports Financiers
                            </a>
                        </li>
                        <li class="submenu-item">
                            <a href="{{ route('caissier.session.close.form') }}" class="submenu-link text-danger">
                                <i class="fas fa-lock fs-8 text-danger me-1"></i> Clôture de Caisse
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </aside>

    <!-- WRAPPER DE CONTENU -->
    <div class="main-wrapper" id="mainWrapper">

        <!-- TOP NAVBAR BLANCHE AVEC RECHERCHE ET PROFIL -->
        <header class="top-navbar">
            <!-- Partie Gauche : Toggle Mobile + Champ de recherche -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none border" id="btnToggleSidebarMobile">
                    <i class="fas fa-bars"></i>
                </button>

                <form action="{{ route('caissier.depot') }}" method="GET" class="search-box-wrapper m-0">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" class="topbar-search-input" value="{{ request('search') }}" placeholder="Rechercher un client, compte, prêt... (Entrée)">
                </form>
            </div>

            <!-- Partie Droite : Notifications, Agence, Statut de Session & Profil -->
            <div class="topbar-right-actions">
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="topbar-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="far fa-bell"></i>
                        <span class="badge-dot"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border p-2 mt-2" style="width: 280px;">
                        <li class="px-3 py-2 border-bottom fw-bold text-dark fs-7">Notifications Récentes</li>
                        <li class="px-3 py-2 fs-8 text-muted">
                            <i class="fas fa-circle-check text-success me-2"></i> Guichet ouvert et opérationnel
                        </li>
                        <li class="px-3 py-2 fs-8 text-muted">
                            <i class="fas fa-info-circle text-primary me-2"></i> Prêts disponibles pour décaissement
                        </li>
                    </ul>
                </div>

                <!-- Sélecteur / Badge d'Agence -->
                <div class="pill-agency">
                    <i class="fas fa-building text-muted"></i>
                    <span>{{ auth()->user()->agency->name ?? 'Agence Centrale' }}</span>
                </div>

                <!-- Statut de la Session Guichet -->
                @php
                    $navActiveSession = auth()->user()->activeCashierSession();
                    $navCanOpen = auth()->user()->can_open_cashier_session || in_array(auth()->user()->role, ['administrateur_systeme', 'administrateur_reglementaire']);
                @endphp

                @if($navActiveSession)
                    <div class="session-status-badge d-none d-sm-inline-flex" title="Session n° {{ $navActiveSession->id }} active">
                        <span class="dot"></span>
                        <span>Guichet Actif (#{{ $navActiveSession->id }})</span>
                    </div>
                    <a href="{{ route('caissier.session.close.form') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fs-8 fw-bold d-none d-md-inline-flex align-items-center gap-1" title="Clôturer le guichet">
                        <i class="fas fa-lock"></i> Clôturer
                    </a>
                @else
                    <div class="session-status-badge-closed d-none d-sm-inline-flex" title="Aucune session de caisse en cours">
                        <span class="dot"></span>
                        <span>Guichet Fermé</span>
                    </div>
                    @if($navCanOpen)
                        <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-emerald text-white rounded-pill px-3 py-1 fs-8 fw-bold d-none d-sm-inline-flex align-items-center gap-1 shadow-xs" title="Ouvrir votre caisse">
                            <i class="fas fa-cash-register"></i> Ouvrir ma Caisse
                        </a>
                    @else
                        <span class="badge bg-slate-100 text-slate-500 rounded-pill px-2.5 py-1 fs-8 border border-slate-200 d-none d-md-inline-flex align-items-center gap-1" title="Votre caisse doit être ouverte par un administrateur">
                            <i class="fas fa-shield-halved"></i> Attente Admin
                        </span>
                    @endif
                @endif

                <!-- Profil Utilisateur Caissier -->
                <div class="dropdown">
                    <div class="user-profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-info-text d-none d-md-block">
                            <p class="user-name-title">{{ auth()->user()->full_name ?? 'Kodzo GADZEKPO' }}</p>
                            <p class="user-role-sub">{{ ucfirst(auth()->user()->role ?? 'Caissier') }} Guichet</p>
                        </div>
                        <div class="user-avatar-round">
                            {{ strtoupper(substr(auth()->user()->first_name ?? 'C', 0, 1)) }}
                        </div>
                        <i class="fas fa-chevron-down text-muted fs-8 ms-1"></i>
                    </div>

                    <ul class="dropdown-menu dropdown-menu-end shadow border p-2 mt-2" style="min-width: 220px;">
                        <li>
                            <div class="px-3 py-2 border-bottom mb-1">
                                <p class="mb-0 fw-bold text-dark fs-7">{{ auth()->user()->full_name }}</p>
                                <small class="text-muted fs-8">{{ auth()->user()->email }}</small>
                            </div>
                        </li>
                        <li><a class="dropdown-item py-2 fs-7" href="{{ route('caissier.logs') }}"><i class="fas fa-clock-rotate-left me-2 text-muted"></i> Mon Journal de Flux</a></li>
                        <li><a class="dropdown-item py-2 fs-7" href="{{ route('caissier.reports') }}"><i class="fas fa-chart-pie me-2 text-muted"></i> Synthèse Financière</a></li>
                        @if($navActiveSession)
                            <li><a class="dropdown-item py-2 fs-7 text-danger" href="{{ route('caissier.session.close.form') }}"><i class="fas fa-lock me-2"></i> Clôturer ma Caisse</a></li>
                        @elseif($navCanOpen)
                            <li><a class="dropdown-item py-2 fs-7 text-success fw-bold" href="{{ route('caissier.terminal') }}"><i class="fas fa-cash-register me-2"></i> Ouvrir ma Caisse</a></li>
                        @endif
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger py-2 fs-7">
                                    <i class="fas fa-power-off me-2"></i> Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- CORPS DE PAGE PRINCIPAL -->
        <main class="content-body">
            @yield('content')
        </main>
    </div>

    <!-- ======================================================== -->
    <!--  MODAL 1: VIREMENT TRÉSORERIE / COFFRE-FORT               -->
    <!-- ======================================================== -->
    <div class="modal fade" id="vaultTransferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="{{ route('caissier.session.vault-transfer') }}" method="POST">
                    @csrf
                    <div class="modal-header border-bottom p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-amber-50 p-2 text-warning fs-4">
                                <i class="fas fa-building-columns"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">Transfert avec la Trésorerie</h5>
                                <small class="text-muted">Coffre-fort d'agence ⇄ Guichet caisse</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 space-y-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-7">Sens du Transfert</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="type" id="typeSupply" value="supply" checked>
                                    <label class="btn btn-outline-success w-100 py-3 text-start rounded-3" for="typeSupply">
                                        <div class="fw-bold fs-7"><i class="fas fa-arrow-down me-1"></i> Approvisionner</div>
                                        <small class="text-muted fs-8 d-block">Coffre → Caisse</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="type" id="typeDischarge" value="discharge">
                                    <label class="btn btn-outline-danger w-100 py-3 text-start rounded-3" for="typeDischarge">
                                        <div class="fw-bold fs-7"><i class="fas fa-arrow-up me-1"></i> Décharger</div>
                                        <small class="text-muted fs-8 d-block">Caisse → Coffre</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-7">Montant du Transfert (FCFA)</label>
                            <div class="input-group input-group-lg">
                                <input type="number" name="amount" class="form-control fw-bold" placeholder="50 000" min="1" step="any" required>
                                <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark fs-7">Motif / Justification</label>
                            <input type="text" name="notes" class="form-control" placeholder="Ex: Réserve pour décaissement de prêt, décharge du midi...">
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-light fw-bold text-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="fas fa-check-circle me-1"></i> Confirmer le Transfert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!--  MODAL 2: EXTOURNE TRANSACTION AVEC PIN SUPERVISEUR       -->
    <!-- ======================================================== -->
    <div class="modal fade" id="reversalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form id="reversalForm" method="POST" action="{{ route('caissier.transactions.reversal.direct') }}">
                    @csrf
                    <div class="modal-header border-bottom p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-rose-50 p-2 text-danger fs-4">
                                <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0">Extourne de Transaction</h5>
                                <small class="text-muted">Annulation sécurisée sous contrôle d'un superviseur</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-7">Référence de la Transaction à annuler</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light text-muted border"><i class="fas fa-barcode"></i></span>
                                <input type="text" id="reversalTxReference" name="transaction_reference" class="form-control fw-bold font-monospace text-uppercase" placeholder="Ex: TXN-20261110-8519" required>
                            </div>
                            <small class="text-muted fs-8">Indiquez le numéro de référence inscrit sur le reçu client ou cliquez sur "Extourner" dans la liste des opérations.</small>
                        </div>

                        <div id="reversalSelectedInfo" class="alert alert-light border p-3 rounded-3 mb-3 d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-8 text-muted">Montant concerné :</span>
                                <strong class="fs-7 text-dark font-numeric" id="reversalAmountText">-</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark fs-7">Code PIN Superviseur</label>
                            <input type="password" name="supervisor_pin" class="form-control form-control-lg text-center tracking-widest fw-black" maxlength="8" placeholder="••••" required>
                            <small class="text-muted fs-8">Code d'habilitation du gestionnaire d'agence ou administrateur.</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark fs-7">Motif obligatoire de l'extourne</label>
                            <textarea name="reason" rows="2" class="form-control" placeholder="Erreur de saisie de montant, mauvais compte..." required minlength="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-light fw-bold text-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-danger fw-bold px-4" id="btnConfirmReversal">
                            <i class="fas fa-ban me-1"></i> Valider l'Extourne
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!--  MODAL 3: CONFIRMATION D'ACTION INSTITUTIONNELLE          -->
    <!-- ======================================================== -->
    <div class="modal fade" id="globalActionConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
                <div class="modal-header border-bottom p-4 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" id="confirmModalIconBox" style="width: 46px; height: 46px; background: #ecfdf5; color: #059669;">
                            <i class="fas fa-circle-question fs-4" id="confirmModalIcon"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-black text-dark mb-0 fs-6" id="confirmModalTitle">Confirmation Requise</h5>
                            <small class="text-muted fs-8" id="confirmModalSubtitle">Validation opérationnelle guichet</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="fs-7 text-dark mb-0 fw-medium leading-relaxed" id="confirmModalMessage">
                        Êtes-vous certain de vouloir exécuter cette opération ?
                    </p>
                </div>
                <div class="modal-footer border-top p-3 bg-slate-50 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border fw-semibold text-secondary px-4 py-2 rounded-3 fs-7" data-bs-dismiss="modal" id="confirmModalCancelBtn">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-success fw-bold px-4 py-2 rounded-3 fs-7 shadow-sm" id="confirmModalSubmitBtn">
                        <i class="fas fa-check me-1.5"></i> Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Utilitaire global de formatage des montants FCFA
        window.formatNumber = function(num) {
            if (num === null || num === undefined || num === '') return '0';
            const val = parseFloat(num);
            if (isNaN(val)) return '0';
            return Math.round(val).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        };

        // GESTIONNAIRE INSTITUTIONNEL DE CONFIRMATION PAR MODAL
        let pendingConfirmCallback = null;

        window.showConfirmModal = function(options) {
            const modalEl = document.getElementById('globalActionConfirmModal');
            if (!modalEl) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: options.title || 'Confirmation Requise',
                        html: options.message,
                        icon: options.swalIcon || 'question',
                        showCancelButton: true,
                        confirmButtonColor: options.confirmColor || '#10b981',
                        cancelButtonColor: '#94a3b8',
                        confirmButtonText: options.confirmText || 'Confirmer',
                        cancelButtonText: options.cancelText || 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed && typeof options.onConfirm === 'function') {
                            options.onConfirm();
                        }
                    });
                    return;
                }
                if (confirm(options.message)) {
                    if (typeof options.onConfirm === 'function') options.onConfirm();
                }
                return;
            }

            const titleEl = document.getElementById('confirmModalTitle');
            const subtitleEl = document.getElementById('confirmModalSubtitle');
            const messageEl = document.getElementById('confirmModalMessage');
            const iconEl = document.getElementById('confirmModalIcon');
            const iconBox = document.getElementById('confirmModalIconBox');
            const submitBtn = document.getElementById('confirmModalSubmitBtn');
            const cancelBtn = document.getElementById('confirmModalCancelBtn');

            if (titleEl) titleEl.textContent = options.title || 'Confirmation Requise';
            if (subtitleEl) subtitleEl.textContent = options.subtitle || 'Validation opérationnelle guichet';
            if (messageEl) messageEl.innerHTML = options.message || 'Êtes-vous certain de vouloir continuer ?';
            if (cancelBtn) cancelBtn.textContent = options.cancelText || 'Annuler';

            if (submitBtn) {
                submitBtn.className = 'btn fw-bold px-4 py-2 rounded-3 fs-7 shadow-sm ' + (options.confirmBtnClass || 'btn-success');
                submitBtn.innerHTML = '<i class="' + (options.confirmIcon || 'fas fa-check') + ' me-1.5"></i> ' + (options.confirmText || 'Confirmer');
            }

            if (iconEl && iconBox) {
                iconEl.className = 'fas ' + (options.icon || 'fa-circle-question') + ' fs-4';
                iconBox.style.background = options.iconBg || '#ecfdf5';
                iconBox.style.color = options.iconColor || '#059669';
            }

            pendingConfirmCallback = options.onConfirm || null;
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        };

        $(document).on('click', '#confirmModalSubmitBtn', function() {
            const modalEl = document.getElementById('globalActionConfirmModal');
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();

            if (typeof pendingConfirmCallback === 'function') {
                const cb = pendingConfirmCallback;
                pendingConfirmCallback = null;
                cb();
            }
        });

        // Interception automatique de tous les déclencheurs avec [data-confirm]
        $(document).on('click', '[data-confirm]', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const trigger = $(this);
            const form = trigger.closest('form');
            const message = trigger.attr('data-confirm');
            const title = trigger.attr('data-confirm-title') || 'Confirmation Requise';
            const subtitle = trigger.attr('data-confirm-subtitle') || 'Validation opérationnelle';
            const confirmText = trigger.attr('data-confirm-btn') || 'Confirmer';
            const confirmBtnClass = trigger.attr('data-confirm-class') || 'btn-success';
            const icon = trigger.attr('data-confirm-icon') || 'fa-circle-question';
            const iconBg = trigger.attr('data-confirm-icon-bg') || '#ecfdf5';
            const iconColor = trigger.attr('data-confirm-icon-color') || '#059669';

            showConfirmModal({
                title: title,
                subtitle: subtitle,
                message: message,
                confirmText: confirmText,
                confirmBtnClass: confirmBtnClass,
                icon: icon,
                iconBg: iconBg,
                iconColor: iconColor,
                onConfirm: function() {
                    if (form.length > 0) {
                        form.removeAttr('onsubmit');
                        form[0].submit();
                    } else if (trigger.is('a') && trigger.attr('href') && trigger.attr('href') !== '#' && !trigger.attr('href').startsWith('javascript:')) {
                        window.location.href = trigger.attr('href');
                    }
                }
            });
        });

        // Interception automatique de tous les formulaires avec [data-confirm]
        $(document).on('submit', 'form[data-confirm]', function(e) {
            e.preventDefault();
            const form = this;
            const message = form.getAttribute('data-confirm');
            const title = form.getAttribute('data-confirm-title') || 'Confirmation Requise';
            const subtitle = form.getAttribute('data-confirm-subtitle') || 'Validation opérationnelle';
            const confirmText = form.getAttribute('data-confirm-btn') || 'Confirmer';
            const confirmBtnClass = form.getAttribute('data-confirm-class') || 'btn-success';
            const icon = form.getAttribute('data-confirm-icon') || 'fa-circle-question';
            const iconBg = form.getAttribute('data-confirm-icon-bg') || '#ecfdf5';
            const iconColor = form.getAttribute('data-confirm-icon-color') || '#059669';

            showConfirmModal({
                title: title,
                subtitle: subtitle,
                message: message,
                confirmText: confirmText,
                confirmBtnClass: confirmBtnClass,
                icon: icon,
                iconBg: iconBg,
                iconColor: iconColor,
                onConfirm: function() {
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            });
        });

        // Toggle Sidebar sur Desktop (Collapse)
        const btnCollapse = document.getElementById('btnCollapseSidebar');
        if (btnCollapse) {
            btnCollapse.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
            });

            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }
        }

        // Toggle Sidebar sur Mobile
        const btnToggleMobile = document.getElementById('btnToggleSidebarMobile');
        const btnCloseMobile = document.getElementById('btnCloseSidebarMobile');
        const sidebar = document.getElementById('sidebar');

        if (btnToggleMobile && sidebar) {
            btnToggleMobile.addEventListener('click', () => {
                sidebar.classList.add('show');
            });
        }

        if (btnCloseMobile && sidebar) {
            btnCloseMobile.addEventListener('click', () => {
                sidebar.classList.remove('show');
            });
        }

        // Helper global pour ouvrir l'extourne directement depuis n'importe quelle ligne de transaction
        function triggerReversal(txRef, amountText) {
            const modal = new bootstrap.Modal(document.getElementById('reversalModal'));
            const input = document.getElementById('reversalTxReference');
            const infoBox = document.getElementById('reversalSelectedInfo');
            const amountSpan = document.getElementById('reversalAmountText');
            
            if (input) input.value = txRef;
            if (infoBox && amountSpan && amountText) {
                amountSpan.textContent = amountText + ' FCFA';
                infoBox.classList.remove('d-none');
            }
            modal.show();
        }
    </script>

    @stack('scripts')
</body>
</html>
