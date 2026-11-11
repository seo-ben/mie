import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../domain/notifiers/auth_notifier.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../auth/login_screen.dart';
import '../clients/client_detail_screen.dart';
import '../clients/express_register_screen.dart';
import '../operations/deposit_screen.dart';
import '../operations/operation_logs_screen.dart';
import '../operations/withdrawal_screen.dart';
import '../operations/transfer_screen.dart';
import '../loans/loans_list_screen.dart';
import '../loans/loan_application_screen.dart';
import '../profile/cashier_profile_screen.dart';
import '../operations/vault_transfer_modal.dart';
import '../operations/transaction_reversal_dialog.dart';
import '../../../domain/notifiers/sync_notifier.dart';
import '../../widgets/sync_status_badge.dart';
import 'reports_tab.dart';
import '../agents/field_agents_screen.dart';

class CashierDashboardScreen extends ConsumerStatefulWidget {
  const CashierDashboardScreen({super.key});

  @override
  ConsumerState<CashierDashboardScreen> createState() => _CashierDashboardScreenState();
}

class _CashierDashboardScreenState extends ConsumerState<CashierDashboardScreen> {
  int _selectedIndex = 0;
  final TextEditingController _clientSearchController = TextEditingController();
  String _clientSearchQuery = '';
  String _clientFilterType = 'all'; // 'all', 'with_balance', 'savings', 'tontine', 'kyc_approved', 'kyc_pending'
  String _clientSortBy = 'recent'; // 'recent', 'name_asc', 'balance_desc'

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final cashier = ref.read(cashierNotifierProvider.notifier);
      cashier.fetchCurrentSession();
      cashier.fetchDashboardStats();
      cashier.fetchClients();
      cashier.fetchOperations();
    });
  }

  @override
  void dispose() {
    _clientSearchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authNotifierProvider);
    final cashier = ref.watch(cashierNotifierProvider);

    final tabs = [
      _buildHomeTab(auth, cashier),
      _buildClientsTab(cashier),
      _buildOperationsTab(cashier),
      _buildReportsTab(cashier),
      const LoansListScreen(isTab: true),
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FC),
      drawer: _buildDrawer(cashier),
      appBar: AppBar(
        automaticallyImplyLeading: false,
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: true,
        titleSpacing: 4,
        leading: Builder(
          builder: (context) => IconButton(
            tooltip: 'Menu',
            padding: EdgeInsets.zero,
            onPressed: () => Scaffold.of(context).openDrawer(),
            icon: Container(
              padding: const EdgeInsets.all(7),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(LucideIcons.menu, size: 18, color: Color(0xFF1E293B)),
            ),
          ),
        ),
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Caisse YAYRA',
              style: TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 15,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.3,
              ),
            ),
            Text(
              'Terminal Guichet POS',
              style: TextStyle(
                color: Color(0xFF94A3B8),
                fontSize: 9.5,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        actions: [
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: SyncStatusBadge(showIfOnline: true),
          ),
          Padding(
            padding: const EdgeInsets.only(left: 4, right: 8),
            child: IconButton(
              tooltip: 'Actualiser',
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
              onPressed: () async {
                ScaffoldMessenger.of(context).hideCurrentSnackBar();
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Synchronisation et actualisation...'),
                    duration: Duration(seconds: 1),
                  ),
                );
                final online = await ref.read(syncNotifierProvider.notifier).checkConnectivity();
                if (online) {
                  // 1. Synchroniser en priorité les opérations hors-ligne en attente vers le serveur
                  await ref.read(syncNotifierProvider.notifier).syncNow();
                  // 2. Ensuite, recharger les données consolidées du serveur
                  await Future.wait([
                    ref.read(cashierNotifierProvider.notifier).fetchDashboardStats(),
                    ref.read(cashierNotifierProvider.notifier).fetchOperations(),
                    ref.read(cashierNotifierProvider.notifier).fetchClients(),
                  ]);
                  if (context.mounted) {
                    final syncState = ref.read(syncNotifierProvider);
                    if (syncState.pendingCount > 0) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('⚠️ ${syncState.pendingCount} opération(s) en attente de synchro.'),
                          backgroundColor: const Color(0xFFD97706),
                          duration: const Duration(seconds: 3),
                        ),
                      );
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('✅ Données et opérations synchronisées avec succès !'),
                          backgroundColor: Color(0xFF059669),
                          duration: Duration(seconds: 2),
                        ),
                      );
                    }
                  }
                } else {
                  await ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                  await ref.read(cashierNotifierProvider.notifier).fetchOperations();
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('⚠️ Mode Hors-Ligne : Données locales chargées.'),
                        backgroundColor: Color(0xFFD97706),
                        duration: Duration(seconds: 3),
                      ),
                    );
                  }
                }
              },
              icon: Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(LucideIcons.refreshCw, size: 16, color: Color(0xFF1E293B)),
              ),
            ),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: const Color(0xFFE2E8F0), height: 1),
        ),
      ),
      body: tabs[_selectedIndex],
      floatingActionButton: _selectedIndex == 1
          ? FloatingActionButton.extended(
              tooltip: 'Créer un client',
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              elevation: 4,
              icon: const Icon(LucideIcons.userPlus, size: 18),
              label: const Text('Nouveau client', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
              onPressed: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const ExpressRegisterScreen()),
                );
                if (mounted) {
                  await ref.read(cashierNotifierProvider.notifier).fetchClients();
                }
              },
            )
          : _selectedIndex == 2
              ? Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    FloatingActionButton.extended(
                      heroTag: 'cashier_transfer_fab',
                      backgroundColor: const Color(0xFF4F46E5),
                      foregroundColor: Colors.white,
                      elevation: 4,
                      icon: const Icon(LucideIcons.arrowLeftRight, size: 18),
                      label: const Text('Virement Tontine ➔ Épargne', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                      onPressed: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const TransferScreen()),
                        );
                        if (mounted) {
                          ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                          ref.read(cashierNotifierProvider.notifier).fetchClients();
                        }
                      },
                    ),
                    const SizedBox(height: 10),
                    FloatingActionButton.extended(
                      heroTag: 'cashier_deposit_fab',
                      backgroundColor: const Color(0xFF10B981),
                      foregroundColor: Colors.white,
                      elevation: 4,
                      icon: const Icon(LucideIcons.arrowDownLeft, size: 18),
                      label: const Text('Encaissement', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                      onPressed: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const DepositScreen()),
                        );
                        if (mounted) {
                          ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                          ref.read(cashierNotifierProvider.notifier).fetchClients();
                        }
                      },
                    ),
                    const SizedBox(height: 10),
                    FloatingActionButton.extended(
                      heroTag: 'cashier_withdrawal_fab',
                      backgroundColor: const Color(0xFFEF4444),
                      foregroundColor: Colors.white,
                      elevation: 4,
                      icon: const Icon(LucideIcons.arrowUpRight, size: 18),
                      label: const Text('Décaissement', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                      onPressed: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const WithdrawalScreen()),
                        );
                        if (mounted) {
                          ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                          ref.read(cashierNotifierProvider.notifier).fetchClients();
                        }
                      },
                    ),
                  ],
                )
              : null,
      floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          border: const Border(top: BorderSide(color: Color(0xFFE2E8F0), width: 1)),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF0F172A).withValues(alpha: 0.06),
              blurRadius: 20,
              offset: const Offset(0, -6),
            ),
          ],
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _navItem(
                  index: 0,
                  icon: LucideIcons.layoutGrid,
                  activeIcon: LucideIcons.layoutGrid,
                  label: 'Accueil',
                  isActive: _selectedIndex == 0,
                ),
                _navItem(
                  index: 1,
                  icon: LucideIcons.users,
                  activeIcon: LucideIcons.users,
                  label: 'Clients',
                  isActive: _selectedIndex == 1,
                ),
                _navItem(
                  index: 2,
                  icon: LucideIcons.arrowLeftRight,
                  activeIcon: LucideIcons.arrowLeftRight,
                  label: 'Opérations',
                  isActive: _selectedIndex == 2,
                  isPrimary: true,
                ),
                _navItem(
                  index: 3,
                  icon: LucideIcons.barChart3,
                  activeIcon: LucideIcons.barChart3,
                  label: 'Rapports',
                  isActive: _selectedIndex == 3,
                ),
                _navItem(
                  index: 4,
                  icon: LucideIcons.badgePercent,
                  activeIcon: LucideIcons.badgePercent,
                  label: 'Prêts (PR)',
                  isActive: _selectedIndex == 4,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDrawer(CashierState cashier) {
    return Drawer(
      backgroundColor: Colors.white,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              decoration: const BoxDecoration(
                border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
              ),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppColors.primary, Color(0xFF00A892)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(14),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primary.withValues(alpha: 0.25),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: const Icon(LucideIcons.wallet, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 12),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Guichet YAYRA',
                        style: TextStyle(
                          color: Color(0xFF0F172A),
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Système Caisse Intégré',
                        style: TextStyle(
                          color: Color(0xFF94A3B8),
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 12),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 4),
                      child: Text(
                        'MODULES PRINCIPAUX',
                        style: TextStyle(
                          color: const Color(0xFF94A3B8).withValues(alpha: 0.9),
                          fontSize: 10,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 1,
                        ),
                      ),
                    ),
                    _drawerAction(
                      cashier.isSessionOpen ? LucideIcons.lock : LucideIcons.unlock,
                      cashier.isSessionOpen ? 'Clôturer la session de caisse' : 'Ouvrir la session de caisse',
                      () {
                        if (cashier.isSessionOpen) {
                          _showCloseSessionSheet(context, cashier);
                        } else {
                          _showOpenSessionSheet(context);
                        }
                      },
                      color: cashier.isSessionOpen ? AppColors.warning : AppColors.success,
                    ),
                    _drawerAction(
                      LucideIcons.arrowLeftRight,
                      'Virement Tontine ➔ Épargne',
                      () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const TransferScreen())),
                      color: const Color(0xFF4F46E5),
                    ),
                    _drawerAction(
                      LucideIcons.arrowDownUp,
                      'Transfert Coffre (Appro / Décharge)',
                      () => VaultTransferModal.show(context),
                      color: const Color(0xFFD97706),
                    ),
                    _drawerAction(
                      LucideIcons.fileClock,
                      'Journal des opérations',
                      () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const OperationLogsScreen())),
                    ),
                    _drawerAction(
                      LucideIcons.badgePercent,
                      'Gestion des Prêts & Crédits',
                      () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoansListScreen())),
                    ),
                    _drawerAction(
                      LucideIcons.usersRound,
                      'Agents de Terrain & Collectes',
                      () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const FieldAgentsScreen())),
                      color: const Color(0xFF008080),
                    ),
                    _drawerAction(
                      LucideIcons.userPlus,
                      'Inscription client express',
                      () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ExpressRegisterScreen())),
                    ),
                    _drawerAction(
                      LucideIcons.cloudSync,
                      'Synchronisation Hors-Ligne',
                      () => ref.read(syncNotifierProvider.notifier).syncNow(),
                      color: const Color(0xFF0284C7),
                    ),
                    _drawerAction(
                      LucideIcons.refreshCw,
                      'Actualiser les données',
                      () {
                        final c = ref.read(cashierNotifierProvider.notifier);
                        c.fetchCurrentSession();
                        c.fetchDashboardStats();
                        c.fetchClients();
                      },
                    ),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
            ),
            const Divider(color: Color(0xFFE2E8F0), height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 6),
              child: Text(
                'SESSION UTILISATEUR',
                style: TextStyle(
                  color: const Color(0xFF94A3B8).withValues(alpha: 0.9),
                  fontSize: 10,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1,
                ),
              ),
            ),
            _drawerAction(
              LucideIcons.user,
              'Mon Profil & Compte',
              () => Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const CashierProfileScreen()),
              ),
            ),
            _drawerAction(
              LucideIcons.logOut,
              'Déconnexion',
              () async {
                final navigator = Navigator.of(context);
                await ref.read(authNotifierProvider.notifier).logout();
                if (!mounted) return;
                navigator.pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen()));
              },
              color: AppColors.danger,
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Widget _drawerAction(IconData icon, String label, VoidCallback onTap, {Color color = const Color(0xFF1E293B)}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
      child: ListTile(
        dense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        leading: Container(
          padding: const EdgeInsets.all(7),
          decoration: BoxDecoration(
            color: color == AppColors.danger ? const Color(0xFFFEE2E2) : const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 17, color: color),
        ),
        title: Text(
          label,
          style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 13),
        ),
        onTap: () {
          Navigator.of(context).pop();
          onTap();
        },
      ),
    );
  }

  Widget _navItem({
    required int index,
    required IconData icon,
    required IconData activeIcon,
    required String label,
    required bool isActive,
    bool isPrimary = false,
  }) {
    if (isPrimary) {
      return Expanded(
        child: InkWell(
          onTap: () => setState(() => _selectedIndex = index),
          splashColor: Colors.transparent,
          highlightColor: Colors.transparent,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                curve: Curves.easeOutCubic,
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  gradient: isActive
                      ? const LinearGradient(
                          colors: [AppColors.primary, Color(0xFF00A892)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        )
                      : const LinearGradient(
                          colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: isActive
                      ? [
                          BoxShadow(
                            color: AppColors.primary.withValues(alpha: 0.35),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ]
                      : [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.1),
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                ),
                child: Icon(
                  isActive ? activeIcon : icon,
                  size: 17,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: isActive ? FontWeight.w800 : FontWeight.w600,
                  color: isActive ? AppColors.primaryDark : const Color(0xFF64748B),
                  letterSpacing: -0.2,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _selectedIndex = index),
        splashColor: Colors.transparent,
        highlightColor: Colors.transparent,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding: EdgeInsets.symmetric(
                  horizontal: isActive ? 14 : 8,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: isActive
                      ? AppColors.primary.withValues(alpha: 0.14)
                      : Colors.transparent,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  isActive ? activeIcon : icon,
                  size: 20,
                  color: isActive ? AppColors.primaryDark : const Color(0xFF94A3B8),
                ),
              ),
              const SizedBox(height: 3),
              Text(
                label,
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: isActive ? FontWeight.w800 : FontWeight.w600,
                  color: isActive ? AppColors.primaryDark : const Color(0xFF64748B),
                  letterSpacing: -0.2,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHomeTab(AuthState auth, CashierState cashier) {
    return RefreshIndicator(
      onRefresh: () => ref.read(cashierNotifierProvider.notifier).fetchDashboardStats(),
      color: AppColors.primary,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Anti-Braquage Ceiling Alert
            _buildCeilingAlert(cashier),

            // Hero Balance Card with Premium Fintech Look
            Container(
              width: double.infinity,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF0F172A).withValues(alpha: 0.25),
                    blurRadius: 18,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Stack(
                children: [
                  // Subtle top-right decorative glow
                  Positioned(
                    right: -20,
                    top: -20,
                    child: Container(
                      width: 140,
                      height: 140,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: AppColors.primary.withValues(alpha: 0.12),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.10),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Row(
                                    children: [
                                      Icon(LucideIcons.calendar, color: Color(0xFFCBD5E1), size: 12),
                                      SizedBox(width: 5),
                                      Text(
                                        'Aujourd\'hui',
                                        style: TextStyle(
                                          color: Color(0xFFE2E8F0),
                                          fontSize: 11,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            InkWell(
                              onTap: () {
                                if (cashier.isSessionOpen) {
                                  _showCloseSessionSheet(context, cashier);
                                } else {
                                  _showOpenSessionSheet(context);
                                }
                              },
                              borderRadius: BorderRadius.circular(999),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                decoration: BoxDecoration(
                                  color: cashier.isSessionOpen
                                      ? const Color(0xFF10B981).withValues(alpha: 0.18)
                                      : const Color(0xFFEF4444).withValues(alpha: 0.22),
                                  borderRadius: BorderRadius.circular(999),
                                  border: Border.all(
                                    color: cashier.isSessionOpen
                                        ? const Color(0xFF10B981).withValues(alpha: 0.5)
                                        : const Color(0xFFEF4444).withValues(alpha: 0.6),
                                    width: 1,
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      cashier.isSessionOpen ? Icons.circle : Icons.lock_outline_rounded,
                                      color: cashier.isSessionOpen ? const Color(0xFF10B981) : const Color(0xFFFCA5A5),
                                      size: cashier.isSessionOpen ? 7 : 11,
                                    ),
                                    const SizedBox(width: 5),
                                    Text(
                                      cashier.isSessionOpen ? 'Caisse Ouverte' : 'Caisse Fermée',
                                      style: TextStyle(
                                        color: cashier.isSessionOpen ? const Color(0xFF34D399) : const Color(0xFFFCA5A5),
                                        fontSize: 10.5,
                                        fontWeight: FontWeight.w800,
                                        letterSpacing: 0.2,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 18),
                        Text(
                          'SOLDE DE CAISSE ACTUEL',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.65),
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 1.2,
                          ),
                        ),
                        const SizedBox(height: 6),
                        FittedBox(
                          fit: BoxFit.scaleDown,
                          alignment: Alignment.centerLeft,
                          child: Text(
                            CurrencyFormatter.format(cashier.cashBalance),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 32,
                              fontWeight: FontWeight.w900,
                              letterSpacing: -0.5,
                            ),
                          ),
                        ),
                        const SizedBox(height: 14),
                        // Boutons d'actions directes Session & Coffre
                        Row(
                          children: [
                            if (!cashier.isSessionOpen)
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => _showOpenSessionSheet(context),
                                  icon: const Icon(LucideIcons.unlock, size: 15),
                                  label: const Text(
                                    'Ouvrir la caisse du jour',
                                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12),
                                  ),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF10B981),
                                    foregroundColor: Colors.white,
                                    elevation: 0,
                                    padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 12),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                ),
                              )
                            else ...[
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => VaultTransferModal.show(context),
                                  icon: const Icon(LucideIcons.arrowDownUp, size: 14),
                                  label: const Text(
                                    'Transfert Coffre',
                                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5),
                                  ),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFFD97706),
                                    foregroundColor: Colors.white,
                                    elevation: 0,
                                    padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 8),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () => _showCloseSessionSheet(context, cashier),
                                  icon: const Icon(LucideIcons.lock, size: 14, color: Color(0xFFE2E8F0)),
                                  label: const Text(
                                    'Clôturer session',
                                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 11.5, color: Color(0xFFE2E8F0)),
                                  ),
                                  style: OutlinedButton.styleFrom(
                                    side: BorderSide(color: Colors.white.withValues(alpha: 0.3)),
                                    padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 8),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'STATISTIQUES DU JOUR',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF64748B),
                    letterSpacing: 1,
                  ),
                ),
                Text(
                  'Mise à jour en temps réel',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF94A3B8).withValues(alpha: 0.9),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            GridView.count(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1.15,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              children: [
                _statCard(
                  title: 'Solde d’ouverture',
                  value: CurrencyFormatter.formatSimple(cashier.openingBalance),
                  accent: const Color(0xFF6366F1),
                  icon: LucideIcons.walletCards,
                ),
                _statCard(
                  title: 'Total entrées',
                  value: CurrencyFormatter.formatSimple(cashier.todayTotalIn),
                  accent: const Color(0xFF10B981),
                  icon: LucideIcons.arrowDownLeft,
                ),
                _statCard(
                  title: 'Total sorties',
                  value: CurrencyFormatter.formatSimple(cashier.todayTotalOut),
                  accent: const Color(0xFFEF4444),
                  icon: LucideIcons.arrowUpRight,
                ),
                _statCard(
                  title: 'Clients agence',
                  value: cashier.agencyClientCount.toString(),
                  accent: const Color(0xFF0284C7),
                  icon: LucideIcons.users,
                ),
                _statCard(
                  title: 'Opérations du jour',
                  value: cashier.todayTxCount.toString(),
                  accent: const Color(0xFFF59E0B),
                  icon: LucideIcons.receiptText,
                ),
                _statCard(
                  title: 'Agents de terrain',
                  value: 'Collectes ➔',
                  accent: const Color(0xFF008080),
                  icon: LucideIcons.usersRound,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const FieldAgentsScreen()),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSyncAlert() {
    final syncState = ref.watch(syncNotifierProvider);
    if (syncState.pendingCount == 0 && !syncState.isSyncing) {
      return const SizedBox.shrink();
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: syncState.isSyncing
            ? const Color(0xFFEEF2FF)
            : const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: syncState.isSyncing
              ? const Color(0xFFC7D2FE)
              : const Color(0xFFFDE68A),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: (syncState.isSyncing ? Colors.indigo : Colors.amber).withValues(alpha: 0.08),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: syncState.isSyncing ? const Color(0xFF6366F1) : const Color(0xFFD97706),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              syncState.isSyncing ? LucideIcons.refreshCw : LucideIcons.cloudOff,
              color: Colors.white,
              size: 16,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  syncState.isSyncing
                      ? 'Synchronisation des opérations...'
                      : 'Mode Hors-Ligne (${syncState.pendingCount} en attente)',
                  style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w800,
                    color: syncState.isSyncing ? const Color(0xFF3730A3) : const Color(0xFF92400E),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  syncState.isSyncing
                      ? 'Envoi sécurisé des encaissements vers le serveur...'
                      : 'Vos encaissements sont sauvegardés en local et seront envoyés dès le retour du réseau.',
                  style: TextStyle(
                    fontSize: 11,
                    color: syncState.isSyncing ? const Color(0xFF4F46E5) : const Color(0xFFB45309),
                  ),
                ),
              ],
            ),
          ),
          if (!syncState.isSyncing)
            ElevatedButton.icon(
              onPressed: () => ref.read(syncNotifierProvider.notifier).syncNow(),
              icon: const Icon(LucideIcons.send, size: 12),
              label: const Text('Envoyer', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800)),
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                backgroundColor: const Color(0xFFD97706),
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildCeilingAlert(CashierState cashier) {
    const double ceilingLimit = 1500000;
    const double warningLimit = 1200000;
    final balance = cashier.cashBalance;

    if (balance < warningLimit || !cashier.isSessionOpen) {
      return const SizedBox.shrink();
    }

    final isCritical = balance >= ceilingLimit;
    final excessAmount = balance - 500000;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isCritical ? const Color(0xFFFEF2F2) : const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isCritical ? const Color(0xFFEF4444).withValues(alpha: 0.6) : const Color(0xFFF59E0B).withValues(alpha: 0.6),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: (isCritical ? Colors.red : Colors.amber).withValues(alpha: 0.08),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: isCritical ? const Color(0xFFDC2626) : const Color(0xFFD97706),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(LucideIcons.shieldAlert, color: Colors.white, size: 18),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isCritical ? '🚨 ALERTE SÉCURITÉ : Plafond d\'Encaisse Dépassé' : '⚠️ Vigilance : Liquidités Élevées en Caisse',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w900,
                        color: isCritical ? const Color(0xFF991B1B) : const Color(0xFF92400E),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Solde actuel : ${CurrencyFormatter.format(balance)} (Plafond max : ${CurrencyFormatter.format(ceilingLimit)})',
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: isCritical ? const Color(0xFFB91C1C) : const Color(0xFFB45309),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            isCritical
                ? 'Pour des raisons de sécurité anti-braquage, effectuez immédiatement un versement de décharge vers le coffre-fort de l\'agence.'
                : 'Pensez à décharger une partie des liquidités vers le coffre-fort pour limiter les risques.',
            style: TextStyle(
              fontSize: 11,
              color: isCritical ? const Color(0xFF7F1D1D) : const Color(0xFF78350F),
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => VaultTransferModal.show(
                context,
                initialType: 'discharge',
                suggestedAmount: excessAmount > 0 ? excessAmount : null,
              ),
              icon: const Icon(LucideIcons.arrowUpFromDot, size: 16),
              label: Text(
                'Décharger ${excessAmount > 0 ? CurrencyFormatter.formatSimple(excessAmount) : ""} vers le Coffre',
                style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: isCritical ? const Color(0xFFDC2626) : const Color(0xFFD97706),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildClientsTab(CashierState cashier) {
    // 1. Filtering logic
    final filteredClients = cashier.clients.where((client) {
      // Search query
      if (_clientSearchQuery.trim().isNotEmpty) {
        final q = _clientSearchQuery.trim().toLowerCase();
        final matchName = client.fullName.toLowerCase().contains(q);
        final matchPhone = (client.phone ?? '').toLowerCase().contains(q);
        if (!matchName && !matchPhone) return false;
      }

      // Filter category
      switch (_clientFilterType) {
        case 'with_balance':
          return client.totalAccountBalance > 0;
        case 'savings':
          return (client.accounts ?? []).any((a) => a is Map && (a['account_type'] == 'savings' || a['account_type'] == 'epargne'));
        case 'tontine':
          return (client.accounts ?? []).any((a) => a is Map && a['account_type'] == 'tontine');
        case 'kyc_approved':
          return client.kycStatus == 'approved';
        case 'kyc_pending':
          return client.kycStatus != 'approved';
        default:
          return true;
      }
    }).toList();

    // 2. Sorting logic
    switch (_clientSortBy) {
      case 'name_asc':
        filteredClients.sort((a, b) => a.fullName.toLowerCase().compareTo(b.fullName.toLowerCase()));
        break;
      case 'balance_desc':
        filteredClients.sort((a, b) => b.totalAccountBalance.compareTo(a.totalAccountBalance));
        break;
      case 'recent':
      default:
        // default latest order
        break;
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(cashierNotifierProvider.notifier).fetchClients(),
      color: AppColors.primary,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Header & Stats Counter
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Clients de l\'agence',
                  style: TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.4,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFE0F2FE),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    '${filteredClients.length} / ${cashier.clients.length} trouvés',
                    style: const TextStyle(
                      color: Color(0xFF0369A1),
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Modern Search & Sort Bar
            Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.015),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: TextField(
                      controller: _clientSearchController,
                      onChanged: (val) => setState(() => _clientSearchQuery = val),
                      decoration: InputDecoration(
                        hintText: 'Rechercher par nom, téléphone...',
                        hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                        prefixIcon: const Icon(LucideIcons.search, size: 17, color: Color(0xFF64748B)),
                        suffixIcon: _clientSearchQuery.isNotEmpty
                            ? IconButton(
                                icon: const Icon(LucideIcons.x, size: 16, color: Color(0xFF94A3B8)),
                                onPressed: () {
                                  _clientSearchController.clear();
                                  setState(() => _clientSearchQuery = '');
                                },
                              )
                            : null,
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                // Sort Menu Button
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: PopupMenuButton<String>(
                    tooltip: 'Trier la liste',
                    icon: Icon(
                      LucideIcons.arrowUpDown,
                      size: 18,
                      color: _clientSortBy != 'recent' ? AppColors.primaryDark : const Color(0xFF64748B),
                    ),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    onSelected: (val) => setState(() => _clientSortBy = val),
                    itemBuilder: (ctx) => [
                      const PopupMenuItem(value: 'recent', child: Text('Plus récents (défaut)', style: TextStyle(fontSize: 13))),
                      const PopupMenuItem(value: 'name_asc', child: Text('Nom alphabétique (A-Z)', style: TextStyle(fontSize: 13))),
                      const PopupMenuItem(value: 'balance_desc', child: Text('Solde le plus élevé', style: TextStyle(fontSize: 13))),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),

            // Horizontal Filter Chips
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _filterChip('Tous', 'all', LucideIcons.layers),
                  const SizedBox(width: 8),
                  _filterChip('Solde > 0', 'with_balance', LucideIcons.wallet),
                  const SizedBox(width: 8),
                  _filterChip('Épargne', 'savings', LucideIcons.piggyBank),
                  const SizedBox(width: 8),
                  _filterChip('Tontine', 'tontine', LucideIcons.repeat2),
                  const SizedBox(width: 8),
                  _filterChip('KYC Validé', 'kyc_approved', LucideIcons.badgeCheck),
                  const SizedBox(width: 8),
                  _filterChip('KYC En attente', 'kyc_pending', LucideIcons.shieldAlert),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // Client List Body
            Expanded(
              child: cashier.isLoading && cashier.clients.isEmpty
                  ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                  : filteredClients.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            const SizedBox(height: 60),
                            Center(
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: const Icon(LucideIcons.searchX, size: 32, color: Color(0xFF94A3B8)),
                                  ),
                                  const SizedBox(height: 12),
                                  const Text(
                                    'Aucun client ne correspond aux critères.',
                                    style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.w600, fontSize: 13),
                                  ),
                                  if (_clientSearchQuery.isNotEmpty || _clientFilterType != 'all') ...[
                                    const SizedBox(height: 10),
                                    TextButton.icon(
                                      onPressed: () {
                                        _clientSearchController.clear();
                                        setState(() {
                                          _clientSearchQuery = '';
                                          _clientFilterType = 'all';
                                          _clientSortBy = 'recent';
                                        });
                                      },
                                      icon: const Icon(LucideIcons.rotateCcw, size: 14),
                                      label: const Text('Réinitialiser les filtres'),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          physics: const AlwaysScrollableScrollPhysics(),
                          itemCount: filteredClients.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (_, index) {
                            final client = filteredClients[index];
                            final hasPositiveBalance = client.totalAccountBalance > 0;
                            return InkWell(
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => ClientDetailScreen(client: client)),
                              ),
                              borderRadius: BorderRadius.circular(14),
                              child: Container(
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: const Color(0xFFE2E8F0)),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(alpha: 0.015),
                                      blurRadius: 6,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                                child: Row(
                                  children: [
                                    Container(
                                      width: 42,
                                      height: 42,
                                      decoration: BoxDecoration(
                                        gradient: const LinearGradient(
                                          colors: [Color(0xFFE0F2FE), Color(0xFFBAE6FD)],
                                          begin: Alignment.topLeft,
                                          end: Alignment.bottomRight,
                                        ),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Center(
                                        child: Text(
                                          client.firstName.isNotEmpty ? client.firstName[0].toUpperCase() : 'C',
                                          style: const TextStyle(
                                            color: Color(0xFF0369A1),
                                            fontWeight: FontWeight.w800,
                                            fontSize: 16,
                                          ),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Flexible(
                                                child: Text(
                                                  client.fullName,
                                                  maxLines: 1,
                                                  overflow: TextOverflow.ellipsis,
                                                  style: const TextStyle(
                                                    color: Color(0xFF0F172A),
                                                    fontSize: 14,
                                                    fontWeight: FontWeight.w700,
                                                  ),
                                                ),
                                              ),
                                              if (client.kycStatus == 'approved') ...[
                                                const SizedBox(width: 5),
                                                const Icon(LucideIcons.badgeCheck, size: 14, color: Color(0xFF10B981)),
                                              ],
                                            ],
                                          ),
                                          const SizedBox(height: 2),
                                          Row(
                                            children: [
                                              if (client.phone != null && client.phone!.isNotEmpty)
                                                Text(
                                                  client.phone!,
                                                  style: const TextStyle(
                                                    color: Color(0xFF64748B),
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              if ((client.accounts ?? []).isNotEmpty) ...[
                                                const SizedBox(width: 6),
                                                Text(
                                                  '• ${(client.accounts ?? []).length} compte(s)',
                                                  style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10),
                                                ),
                                              ],
                                            ],
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Column(
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        Text(
                                          CurrencyFormatter.formatSimple(client.totalAccountBalance),
                                          style: TextStyle(
                                            color: hasPositiveBalance ? const Color(0xFF0F172A) : const Color(0xFF94A3B8),
                                            fontSize: 13,
                                            fontWeight: FontWeight.w800,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        const Text(
                                          'Solde total',
                                          style: TextStyle(
                                            color: Color(0xFF94A3B8),
                                            fontSize: 10,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(width: 6),
                                    const Icon(LucideIcons.chevronRight, size: 16, color: Color(0xFFCBD5E1)),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _filterChip(String label, String key, IconData icon) {
    final isSelected = _clientFilterType == key;
    return InkWell(
      onTap: () => setState(() => _clientFilterType = key),
      borderRadius: BorderRadius.circular(10),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primaryDark : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppColors.primaryDark : const Color(0xFFE2E8F0),
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.25),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ]
              : [],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 13,
              color: isSelected ? Colors.white : const Color(0xFF64748B),
            ),
            const SizedBox(width: 5),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                color: isSelected ? Colors.white : const Color(0xFF475569),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOperationsTab(CashierState cashier) {
    return RefreshIndicator(
      onRefresh: () async {
        await ref.read(cashierNotifierProvider.notifier).fetchOperations();
      },
      color: AppColors.primary,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 24),
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Expanded(
                child: Text(
                  'Opérations de caisse',
                  style: TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.4,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              TextButton.icon(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const OperationLogsScreen()),
                ),
                icon: const Icon(LucideIcons.history, size: 14, color: AppColors.primaryDark),
                label: const Text(
                  'Historique',
                  style: TextStyle(
                    color: AppColors.primaryDark,
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (cashier.isLoading && cashier.recentTransactions.isEmpty)
            const Padding(
              padding: EdgeInsets.all(40),
              child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
            )
          else if (cashier.recentTransactions.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: const Center(
                child: Column(
                  children: [
                    Icon(LucideIcons.receiptText, size: 36, color: Color(0xFF94A3B8)),
                    SizedBox(height: 10),
                    Text(
                      'Aucune opération récente.',
                      style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                  ],
                ),
              ),
            )
          else
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.015),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: cashier.recentTransactions.take(20).length,
                separatorBuilder: (_, __) => const Divider(height: 1, color: Color(0xFFF1F5F9), indent: 56),
                itemBuilder: (_, idx) {
                  final transaction = cashier.recentTransactions[idx];
                  final incoming = transaction.isDeposit;
                  return _transactionRow(
                    transaction.clientName ?? transaction.description ?? (incoming ? 'Encaissement' : 'Décaissement'),
                    '${incoming ? '+' : '-'} ${CurrencyFormatter.formatSimple(transaction.amount)}',
                    transaction.createdAt != null
                        ? '${transaction.createdAt!.hour.toString().padLeft(2, '0')}:${transaction.createdAt!.minute.toString().padLeft(2, '0')}'
                        : '--:--',
                    incoming ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                    incoming,
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildReportsTab(CashierState cashier) {
    return ReportsTabView(
      onOpenSession: () => _showOpenSessionSheet(context),
      onCloseSession: () => _showCloseSessionSheet(context, cashier),
    );
  }

  Widget _buildProfileTab(AuthState auth) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Profil & Session',
            style: TextStyle(
              color: Color(0xFF0F172A),
              fontSize: 20,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.4,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Informations relatives à votre compte agent',
            style: TextStyle(color: Color(0xFF64748B), fontSize: 12, fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: 18),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFFE2E8F0)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.015),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [AppColors.primary, Color(0xFF00A892)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primary.withValues(alpha: 0.25),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: const Icon(LucideIcons.user, color: Colors.white, size: 24),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            auth.user?.fullName ?? 'Caissier',
                            style: const TextStyle(
                              color: Color(0xFF0F172A),
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            auth.user?.email ?? 'caissier@yayra.com',
                            style: const TextStyle(
                              color: Color(0xFF64748B),
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                const Divider(color: Color(0xFFF1F5F9), height: 1),
                const SizedBox(height: 16),
                _profileInfoRow('Agence', auth.user?.agencyName ?? 'Agence principale', LucideIcons.building2),
                _profileInfoRow('Rôle', 'Caissier principal', LucideIcons.shieldCheck),
                _profileInfoRow('Statut', 'Connecté', LucideIcons.checkCircle2, statusColor: const Color(0xFF10B981)),
              ],
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () async {
                final navigator = Navigator.of(context);
                await ref.read(authNotifierProvider.notifier).logout();
                if (!mounted) return;
                navigator.pushReplacement(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              },
              icon: const Icon(LucideIcons.logOut, size: 18),
              label: const Text('Déconnexion', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.danger,
                side: const BorderSide(color: Color(0xFFFECACA), width: 1.5),
                backgroundColor: const Color(0xFFFEF2F2),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _profileInfoRow(String label, String value, IconData icon, {Color? statusColor}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, size: 15, color: const Color(0xFF94A3B8)),
          const SizedBox(width: 8),
          Expanded(
            flex: 5,
            child: Text(
              label,
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.w500),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 5,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (statusColor != null) ...[
                  Icon(Icons.circle, size: 6, color: statusColor),
                  const SizedBox(width: 5),
                ],
                Flexible(
                  child: Text(
                    value,
                    textAlign: TextAlign.right,
                    style: TextStyle(
                      color: statusColor ?? const Color(0xFF0F172A),
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _statCard({
    required String title,
    required String value,
    required Color accent,
    required IconData icon,
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.015),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, color: accent, size: 17),
                ),
                Icon(onTap != null ? LucideIcons.arrowUpRight : LucideIcons.sparkle, size: 12, color: accent.withValues(alpha: 0.7)),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(
                    value,
                    style: const TextStyle(
                      color: Color(0xFF0F172A),
                      fontSize: 17,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.3,
                    ),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Color(0xFF64748B),
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _transactionRow(String label, String amount, String time, Color color, bool incoming) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: incoming ? const Color(0xFFD1FAE5) : const Color(0xFFFEE2E2),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              incoming ? LucideIcons.arrowDownLeft : LucideIcons.arrowUpRight,
              size: 16,
              color: incoming ? const Color(0xFF059669) : const Color(0xFFDC2626),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  time,
                  style: const TextStyle(
                    fontSize: 11,
                    color: Color(0xFF94A3B8),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            amount,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w800,
              fontSize: 13,
              letterSpacing: -0.2,
            ),
          ),
        ],
      ),
    );
  }

  void _showOpenSessionSheet(BuildContext context) {
    final amountController = TextEditingController(text: '0');
    final notesController = TextEditingController();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final maxHeight = MediaQuery.of(context).size.height * 0.88;

            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: Container(
                constraints: BoxConstraints(maxHeight: maxHeight),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                ),
                child: SingleChildScrollView(
                  physics: const ClampingScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(24, 16, 24, 30),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 40,
                          height: 4,
                          decoration: BoxDecoration(
                            color: const Color(0xFFCBD5E1),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFF10B981).withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(LucideIcons.unlock, color: Color(0xFF10B981), size: 22),
                        ),
                        const SizedBox(width: 14),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Ouverture de Caisse',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w900,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                              Text(
                                'Déclarez votre fond de caisse initial',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Color(0xFF64748B),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 22),
                    const Text(
                      'Fond de caisse initial (FCFA)',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF334155),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: amountController,
                      keyboardType: TextInputType.number,
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                        color: Color(0xFF0F172A),
                      ),
                      decoration: InputDecoration(
                        prefixIcon: const Icon(LucideIcons.banknote, color: Color(0xFF10B981)),
                        suffixText: 'FCFA',
                        suffixStyle: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide: const BorderSide(color: Color(0xFF10B981), width: 1.8),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    // Quick chips
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [0, 25000, 50000, 100000, 250000].map((val) {
                          return Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: ActionChip(
                              label: Text(
                                val == 0 ? '0 FCFA' : '${CurrencyFormatter.formatNumber(val)} F',
                                style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700),
                              ),
                              backgroundColor: const Color(0xFFF1F5F9),
                              side: const BorderSide(color: Color(0xFFE2E8F0)),
                              onPressed: () {
                                setModalState(() {
                                  amountController.text = val.toString();
                                });
                              },
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Observations / Notes (optionnel)',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF334155),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: notesController,
                      maxLines: 2,
                      decoration: InputDecoration(
                        hintText: 'Ex: Billetage vérifié, fond initial conforme...',
                        hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final amount = double.tryParse(amountController.text.trim()) ?? 0.0;
                                setModalState(() => isSubmitting = true);
                                final session = await ref.read(cashierNotifierProvider.notifier).openSession(
                                      openingBalance: amount,
                                      notes: notesController.text.trim(),
                                    );
                                if (!context.mounted) return;
                                Navigator.pop(ctx);

                                if (session != null) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text('Caisse ouverte avec succès. Fond initial : ${CurrencyFormatter.format(amount)}'),
                                      backgroundColor: const Color(0xFF10B981),
                                    ),
                                  );
                                } else {
                                  final err = ref.read(cashierNotifierProvider).errorMessage ?? 'Erreur lors de l\'ouverture';
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text(err), backgroundColor: Colors.red),
                                  );
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF10B981),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 15),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          elevation: 0,
                        ),
                        child: isSubmitting
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white),
                              )
                            : const Text(
                                'Confirmer et Ouvrir la Caisse',
                                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    },
  );
}

  void _showCloseSessionSheet(BuildContext context, CashierState cashier) {
    final expected = cashier.cashBalance;
    final closingController = TextEditingController(text: expected > 0 ? expected.toInt().toString() : '0');
    final notesController = TextEditingController();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final inputStr = closingController.text.trim();
            final physicalAmount = double.tryParse(inputStr) ?? 0.0;
            final diff = physicalAmount - expected;
            final maxHeight = MediaQuery.of(context).size.height * 0.90;

            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: Container(
                constraints: BoxConstraints(maxHeight: maxHeight),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                ),
                child: SingleChildScrollView(
                  physics: const ClampingScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(24, 16, 24, 30),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 40,
                          height: 4,
                          decoration: BoxDecoration(
                            color: const Color(0xFFCBD5E1),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(LucideIcons.lock, color: Color(0xFFD97706), size: 22),
                          ),
                          const SizedBox(width: 14),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Clôture de Caisse Journalière',
                                  style: TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.w900,
                                    color: Color(0xFF0F172A),
                                  ),
                                ),
                                Text(
                                  'Rapprochement & comptage physique',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      // Pending Offline Transactions Alert if any
                      Builder(
                        builder: (context) {
                          final syncState = ref.watch(syncNotifierProvider);
                          if (syncState.pendingCount == 0) return const SizedBox.shrink();
                          return Container(
                            margin: const EdgeInsets.only(bottom: 16),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: const Color(0xFFFDE68A)),
                            ),
                            child: Row(
                              children: [
                                const Icon(LucideIcons.cloudOff, color: Color(0xFFD97706), size: 20),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '${syncState.pendingCount} transaction(s) hors-ligne',
                                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Color(0xFF92400E)),
                                      ),
                                      const Text(
                                        'Veuillez synchroniser vos opérations avant la clôture.',
                                        style: TextStyle(fontSize: 11, color: Color(0xFFB45309)),
                                      ),
                                    ],
                                  ),
                                ),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFFD97706),
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                    minimumSize: Size.zero,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                  onPressed: syncState.isSyncing
                                      ? null
                                      : () => ref.read(syncNotifierProvider.notifier).syncNow(),
                                  child: Text(
                                    syncState.isSyncing ? 'Sync...' : 'Sync',
                                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800),
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                      // Summary Card
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Column(
                          children: [
                            _summaryRow('Fond d\'ouverture :', CurrencyFormatter.format(cashier.openingBalance)),
                            const SizedBox(height: 6),
                            _summaryRow('Total entrées (+) :', '+ ${CurrencyFormatter.format(cashier.todayTotalIn)}', color: const Color(0xFF10B981)),
                            const SizedBox(height: 6),
                            _summaryRow('Total sorties (-) :', '- ${CurrencyFormatter.format(cashier.todayTotalOut)}', color: const Color(0xFFEF4444)),
                            const Padding(
                              padding: EdgeInsets.symmetric(vertical: 8),
                              child: Divider(color: Color(0xFFE2E8F0), height: 1),
                            ),
                            _summaryRow(
                              'Solde théorique attendu :',
                              CurrencyFormatter.format(expected),
                              isBold: true,
                              color: expected < 0 ? const Color(0xFFDC2626) : const Color(0xFF0F172A),
                            ),
                            if (expected < 0) ...[
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFEF2F2),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: const Color(0xFFFECACA)),
                                ),
                                child: const Row(
                                  children: [
                                    Icon(LucideIcons.alertTriangle, size: 14, color: Color(0xFFDC2626)),
                                    SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        'Attention : Les sorties dépassent les fonds disponibles (solde négatif).',
                                        style: TextStyle(fontSize: 11, color: Color(0xFFDC2626), fontWeight: FontWeight.w700),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 18),
                      const Text(
                        'Montant physique compté en caisse (FCFA)',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF334155),
                        ),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: closingController,
                        keyboardType: TextInputType.number,
                        onChanged: (_) => setModalState(() {}),
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                          color: Color(0xFF0F172A),
                        ),
                        decoration: InputDecoration(
                          prefixIcon: const Icon(LucideIcons.banknote, color: Color(0xFF6366F1)),
                          suffixText: 'FCFA',
                          suffixStyle: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: Color(0xFF6366F1), width: 1.8),
                          ),
                        ),
                      ),
                      const SizedBox(height: 10),
                      // Live difference status badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: diff.abs() < 1
                              ? const Color(0xFFECFDF5)
                              : (diff > 0 ? const Color(0xFFFEF3C7) : const Color(0xFFFEE2E8)),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: diff.abs() < 1
                                ? const Color(0xFF10B981).withValues(alpha: 0.3)
                                : (diff > 0 ? const Color(0xFFF59E0B).withValues(alpha: 0.4) : const Color(0xFFEF4444).withValues(alpha: 0.4)),
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              diff.abs() < 1 ? LucideIcons.checkCircle : (diff > 0 ? LucideIcons.alertTriangle : LucideIcons.alertCircle),
                              size: 16,
                              color: diff.abs() < 1
                                  ? const Color(0xFF059669)
                                  : (diff > 0 ? const Color(0xFFD97706) : const Color(0xFFDC2626)),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                diff.abs() < 1
                                    ? 'Caisse exacte (Écart : 0 FCFA)'
                                    : (diff > 0
                                        ? 'Excédent de caisse : +${CurrencyFormatter.format(diff)}'
                                        : 'Déficit de caisse : ${CurrencyFormatter.format(diff)}'),
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: diff.abs() < 1
                                      ? const Color(0xFF065F46)
                                      : (diff > 0 ? const Color(0xFF92400E) : const Color(0xFF991B1B)),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                      const Text(
                        'Justification / Notes de clôture',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF334155),
                        ),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: notesController,
                        maxLines: 2,
                        decoration: InputDecoration(
                          hintText: 'Ex: Arrêté de caisse journalier conforme...',
                          hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
                        ),
                      ),
                      const SizedBox(height: 22),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: isSubmitting
                              ? null
                              : () async {
                                  final amount = double.tryParse(closingController.text.trim()) ?? 0.0;
                                  setModalState(() => isSubmitting = true);
                                  final res = await ref.read(cashierNotifierProvider.notifier).closeSession(
                                        closingBalance: amount,
                                        notes: notesController.text.trim(),
                                      );
                                  if (!context.mounted) return;
                                  Navigator.pop(ctx);

                                  if (res != null) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(
                                        content: Text('Session clôturée avec succès. Solde validé : ${CurrencyFormatter.format(amount)}'),
                                        backgroundColor: const Color(0xFF0F172A),
                                      ),
                                    );
                                  } else {
                                    final err = ref.read(cashierNotifierProvider).errorMessage ?? 'Erreur lors de la clôture';
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(content: Text(err), backgroundColor: Colors.red),
                                    );
                                  }
                                },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF0F172A),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 15),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                            elevation: 0,
                          ),
                          child: isSubmitting
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white),
                                )
                              : const Text(
                                  'Valider la Clôture Définitive',
                                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
                                ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _summaryRow(String label, String value, {bool isBold = false, Color? color}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: isBold ? 13 : 12,
            fontWeight: isBold ? FontWeight.w800 : FontWeight.w500,
            color: const Color(0xFF64748B),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: isBold ? 14 : 12.5,
            fontWeight: isBold ? FontWeight.w900 : FontWeight.w700,
            color: color ?? const Color(0xFF1E293B),
          ),
        ),
      ],
    );
  }
}

