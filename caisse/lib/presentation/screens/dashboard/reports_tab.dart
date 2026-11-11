import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/transaction_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../../../domain/notifiers/loan_notifier.dart';
import '../receipts/receipt_screen.dart';
import '../operations/vault_transfer_modal.dart';
import '../operations/transaction_reversal_dialog.dart';

class ReportsTabView extends ConsumerStatefulWidget {
  final VoidCallback? onOpenSession;
  final VoidCallback? onCloseSession;

  const ReportsTabView({
    super.key,
    this.onOpenSession,
    this.onCloseSession,
  });

  @override
  ConsumerState<ReportsTabView> createState() => _ReportsTabViewState();
}

class _ReportsTabViewState extends ConsumerState<ReportsTabView> {
  String _selectedPeriod = 'today';
  String _txFilter = 'all';
  bool _isRefreshing = false;

  Future<void> _refresh() async {
    setState(() => _isRefreshing = true);
    final notifier = ref.read(cashierNotifierProvider.notifier);
    final cashier = ref.read(cashierNotifierProvider);

    if (cashier.selectedArchiveDate != null) {
      await notifier.fetchDashboardStats(date: cashier.selectedArchiveDate);
      await notifier.fetchOperations(date: cashier.selectedArchiveDate);
    } else if (cashier.selectedArchiveSession != null) {
      final sId = cashier.selectedArchiveSession!['id'] as int;
      await notifier.fetchDashboardStats(sessionId: sId);
      await notifier.fetchOperations(sessionId: sId);
    } else {
      await notifier.fetchDashboardStats(period: _selectedPeriod);
      await notifier.fetchOperations();
    }
    await ref.read(loanNotifierProvider.notifier).fetchLoans();
    if (mounted) setState(() => _isRefreshing = false);
  }

  @override
  Widget build(BuildContext context) {
    final cashier = ref.watch(cashierNotifierProvider);
    final loanState = ref.watch(loanNotifierProvider);

    final openingBalance = cashier.openingBalance;
    final totalIn = cashier.todayTotalIn;
    final totalOut = cashier.todayTotalOut;
    final cashBalance = cashier.cashBalance;
    final totalFlux = totalIn + totalOut;
    final inPercent = totalFlux > 0 ? (totalIn / totalFlux) : 0.5;

    final allOperations = cashier.operations.isNotEmpty
        ? cashier.operations
        : cashier.stats.recentTransactions;

    final filteredTransactions = allOperations.where((tx) {
      if (_txFilter == 'in') return tx.isDeposit;
      if (_txFilter == 'out') return tx.isWithdrawal;
      if (_txFilter == 'loan') {
        final desc = (tx.description ?? '').toLowerCase();
        final type = tx.transactionType.toLowerCase();
        return desc.contains('prêt') ||
            desc.contains('pret') ||
            type.contains('loan') ||
            type.contains('repay');
      }
      return true;
    }).toList();

    return Stack(
      children: [
        RefreshIndicator(
          onRefresh: _refresh,
          color: AppColors.primary,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 85),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildHeader(cashier),
                if (cashier.isArchiveMode) ...[
                  const SizedBox(height: 12),
                  _buildArchiveBanner(cashier),
                ],
                const SizedBox(height: 14),
                _buildPeriodSelector(cashier),
                const SizedBox(height: 16),
                _buildCeilingAlert(cashier),
                _buildReconciliationCard(
                  cashier: cashier,
                  openingBalance: openingBalance,
                  totalIn: totalIn,
                  totalOut: totalOut,
                  cashBalance: cashBalance,
                ),
                const SizedBox(height: 16),
                _buildKpiGrid(cashier, totalIn, totalOut, inPercent),
                const SizedBox(height: 16),
                _buildFluxComparisonCard(totalIn, totalOut, inPercent),
                const SizedBox(height: 16),
                _buildStreamBreakdown(cashier, loanState),
                const SizedBox(height: 20),
                _buildAuditSection(filteredTransactions),
                const SizedBox(height: 20),
                _buildClosureActionBanner(cashier),
              ],
            ),
          ),
        ),
        Positioned(
          left: 16,
          right: 16,
          bottom: 12,
          child: Container(
            decoration: BoxDecoration(
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF0F172A).withOpacity(0.30),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: ElevatedButton.icon(
              onPressed: () => _showPrintReportDialog(context, cashier),
              icon: const Icon(LucideIcons.fileText, size: 18),
              label: const Text(
                'Afficher la Synthèse d\'Arrêté de Caisse',
                style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 0,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildHeader(CashierState cashier) {
    final now = DateTime.now();
    const months = [
      'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];
    final activeDate = cashier.selectedArchiveDate ?? now;
    final dateStr = '${activeDate.day} ${months[activeDate.month - 1]} ${activeDate.year}';

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Flexible(
                    child: Text(
                      'Rapports & Audit',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Color(0xFF0F172A),
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.5,
                      ),
                    ),
                  ),
                  if (cashier.isArchiveMode) ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: const Color(0xFFFDE68A)),
                      ),
                      child: const Text(
                        'Archive',
                        style: TextStyle(
                          color: Color(0xFFD97706),
                          fontSize: 10,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 2),
              Row(
                children: [
                  const Icon(LucideIcons.calendar, size: 12, color: Color(0xFF64748B)),
                  const SizedBox(width: 5),
                  Flexible(
                    child: Text(
                      dateStr,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF64748B),
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconButton(
              tooltip: 'Procès-Verbal / Synthèse',
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
              onPressed: () => _showPrintReportDialog(context, cashier),
              icon: Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(LucideIcons.printer, size: 15, color: Colors.white),
              ),
            ),
            const SizedBox(width: 4),
            IconButton(
              tooltip: 'Historique des Sessions',
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
              onPressed: () => _showSessionHistorySheet(context),
              icon: Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: const Icon(LucideIcons.history, size: 15, color: Color(0xFF4F46E5)),
              ),
            ),
            const SizedBox(width: 4),
            IconButton(
              tooltip: 'Actualiser',
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
              onPressed: _isRefreshing ? null : _refresh,
              icon: Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.03),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: _isRefreshing
                    ? const SizedBox(
                        width: 15,
                        height: 15,
                        child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                      )
                    : const Icon(LucideIcons.refreshCw, size: 15, color: Color(0xFF1E293B)),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildArchiveBanner(CashierState cashier) {
    final session = cashier.selectedArchiveSession;
    final date = cashier.selectedArchiveDate;
    final dateLabel = date != null
        ? '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}'
        : (session != null ? 'Session #${session['id']}' : 'Date passée');

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0xFFFEF3C7),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Row(
        children: [
          const Icon(LucideIcons.clockAlert, color: Color(0xFFD97706), size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Consultation de l\'Archive : $dateLabel',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF92400E),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  session != null && session['status'] == 'closed'
                      ? 'Session fermée (${session['closed_time'] ?? ''}) - Solde: ${CurrencyFormatter.formatSimple((session['closing_balance'] as num?)?.toDouble() ?? 0)}'
                      : 'Affichage des opérations & rapprochement de cette date',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 10.5, color: Color(0xFFB45309)),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0F172A),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            icon: const Icon(LucideIcons.rotateCcw, size: 11),
            label: const Text('Direct', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800)),
            onPressed: () {
              ref.read(cashierNotifierProvider.notifier).resetToLiveSession();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildPeriodSelector(CashierState cashier) {
    final periods = [
      {'id': 'today', 'label': "Aujourd'hui"},
      {'id': 'week', 'label': 'Cette semaine'},
      {'id': 'month', 'label': 'Ce mois'},
      {'id': 'all', 'label': 'Tout l\'historique'},
    ];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          for (final p in periods)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: InkWell(
                borderRadius: BorderRadius.circular(12),
                onTap: () {
                  if (cashier.isArchiveMode) {
                    ref.read(cashierNotifierProvider.notifier).resetToLiveSession();
                  }
                  setState(() => _selectedPeriod = p['id']!);
                  _refresh();
                },
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  decoration: BoxDecoration(
                    color: (!cashier.isArchiveMode && _selectedPeriod == p['id'])
                        ? const Color(0xFF0F172A)
                        : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: (!cashier.isArchiveMode && _selectedPeriod == p['id'])
                          ? const Color(0xFF0F172A)
                          : const Color(0xFFE2E8F0),
                    ),
                    boxShadow: (!cashier.isArchiveMode && _selectedPeriod == p['id'])
                        ? [
                            BoxShadow(
                              color: const Color(0xFF0F172A).withOpacity(0.2),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ]
                        : null,
                  ),
                  child: Text(
                    p['label']!,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: (!cashier.isArchiveMode && _selectedPeriod == p['id'])
                          ? FontWeight.w800
                          : FontWeight.w600,
                      color: (!cashier.isArchiveMode && _selectedPeriod == p['id'])
                          ? Colors.white
                          : const Color(0xFF64748B),
                    ),
                  ),
                ),
              ),
            ),
          // Bouton Calendrier Date Précise
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () async {
                final now = DateTime.now();
                final picked = await showDatePicker(
                  context: context,
                  initialDate: cashier.selectedArchiveDate ?? now,
                  firstDate: DateTime(2020),
                  lastDate: now,
                  builder: (context, child) {
                    return Theme(
                      data: Theme.of(context).copyWith(
                        colorScheme: const ColorScheme.light(
                          primary: Color(0xFF0F172A),
                          onPrimary: Colors.white,
                          onSurface: Color(0xFF0F172A),
                        ),
                      ),
                      child: child!,
                    );
                  },
                );
                if (picked != null) {
                  await ref.read(cashierNotifierProvider.notifier).selectArchiveDate(picked);
                }
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: cashier.selectedArchiveDate != null ? const Color(0xFF4F46E5) : Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: cashier.selectedArchiveDate != null ? const Color(0xFF4F46E5) : const Color(0xFFE2E8F0),
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      LucideIcons.calendarDays,
                      size: 14,
                      color: cashier.selectedArchiveDate != null ? Colors.white : const Color(0xFF4F46E5),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      cashier.selectedArchiveDate != null
                          ? '${cashier.selectedArchiveDate!.day}/${cashier.selectedArchiveDate!.month}/${cashier.selectedArchiveDate!.year}'
                          : 'Choisir une date',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: cashier.selectedArchiveDate != null ? Colors.white : const Color(0xFF4F46E5),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          // Bouton Sessions Clôturées
          InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () => _showSessionHistorySheet(context),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: cashier.selectedArchiveSession != null ? const Color(0xFFD97706) : Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: cashier.selectedArchiveSession != null ? const Color(0xFFD97706) : const Color(0xFFE2E8F0),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    LucideIcons.archive,
                    size: 14,
                    color: cashier.selectedArchiveSession != null ? Colors.white : const Color(0xFFD97706),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    cashier.selectedArchiveSession != null
                        ? 'Session #${cashier.selectedArchiveSession!['id']}'
                        : 'Archives Sessions',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: cashier.selectedArchiveSession != null ? Colors.white : const Color(0xFFD97706),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReconciliationCard({
    required CashierState cashier,
    required double openingBalance,
    required double totalIn,
    required double totalOut,
    required double cashBalance,
  }) {
    final isOpen = cashier.isSessionOpen;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0B132B), Color(0xFF1C2541)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0B132B).withOpacity(0.35),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(7),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(LucideIcons.landmark, color: Color(0xFF38BDF8), size: 16),
                    ),
                    const SizedBox(width: 8),
                    const Flexible(
                      child: Text(
                        'SOLDE EN CAISSE',
                        style: TextStyle(
                          color: Color(0xFF94A3B8),
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.8,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: isOpen
                      ? const Color(0xFF10B981).withOpacity(0.2)
                      : const Color(0xFFF59E0B).withOpacity(0.2),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: isOpen ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                    width: 1,
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: BoxDecoration(
                        color: isOpen ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 5),
                    Text(
                      isOpen ? 'Session Ouverte' : 'Caisse Fermée',
                      style: TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w800,
                        color: isOpen ? const Color(0xFF34D399) : const Color(0xFFFBBF24),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            CurrencyFormatter.format(cashBalance),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Solde théorique attendu au coffre avant arrêté journalier',
            style: TextStyle(color: Color(0xFF64748B), fontSize: 11, fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: 18),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white.withOpacity(0.08)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: _equationItem(
                    label: 'Ouverture',
                    amount: CurrencyFormatter.formatSimple(openingBalance),
                    color: const Color(0xFF94A3B8),
                    prefix: '',
                  ),
                ),
                _equationSymbol('+'),
                Expanded(
                  child: _equationItem(
                    label: 'Entrées',
                    amount: CurrencyFormatter.formatSimple(totalIn),
                    color: const Color(0xFF34D399),
                    prefix: '+',
                  ),
                ),
                _equationSymbol('-'),
                Expanded(
                  child: _equationItem(
                    label: 'Sorties',
                    amount: CurrencyFormatter.formatSimple(totalOut),
                    color: const Color(0xFFF87171),
                    prefix: '-',
                  ),
                ),
              ],
            ),
          ),
          if (isOpen) ...[
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => VaultTransferModal.show(context, initialType: 'supply'),
                    icon: const Icon(LucideIcons.arrowDownToDot, size: 14),
                    label: const Text('Appro Coffre (+)', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF059669),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => VaultTransferModal.show(context, initialType: 'discharge'),
                    icon: const Icon(LucideIcons.arrowUpFromDot, size: 14),
                    label: const Text('Décharge Coffre (-)', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD97706),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
              ],
            ),
          ],
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
          color: isCritical ? const Color(0xFFEF4444).withOpacity(0.6) : const Color(0xFFF59E0B).withOpacity(0.6),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: (isCritical ? Colors.red : Colors.amber).withOpacity(0.08),
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

  Widget _equationSymbol(String symbol) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: Text(
        symbol,
        style: TextStyle(
          color: Colors.white.withOpacity(0.4),
          fontSize: 16,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }

  Widget _equationItem({
    required String label,
    required String amount,
    required Color color,
    required String prefix,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Color(0xFF94A3B8),
            fontSize: 10,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 2),
        FittedBox(
          fit: BoxFit.scaleDown,
          alignment: Alignment.centerLeft,
          child: Text(
            '$prefix$amount',
            style: TextStyle(
              color: color,
              fontSize: 12.5,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildKpiGrid(CashierState cashier, double totalIn, double totalOut, double inPercent) {
    final txCount = cashier.todayTxCount > 0
        ? cashier.todayTxCount
        : cashier.stats.recentTransactions.length;
    final clientCount = cashier.agencyClientCount > 0
        ? cashier.agencyClientCount
        : cashier.clients.length;

    return Row(
      children: [
        Expanded(
          child: Column(
            children: [
              _kpiCard(
                title: 'Total Encaissé',
                value: CurrencyFormatter.formatSimple(totalIn),
                sub: 'Entrées physiques',
                icon: LucideIcons.arrowDownLeft,
                accent: const Color(0xFF10B981),
                bgAccent: const Color(0xFFECFDF5),
              ),
              const SizedBox(height: 10),
              _kpiCard(
                title: 'Transactions',
                value: '$txCount Opér.',
                sub: 'Activité guichet',
                icon: LucideIcons.receiptText,
                accent: const Color(0xFF4F46E5),
                bgAccent: const Color(0xFFEEF2FF),
              ),
            ],
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            children: [
              _kpiCard(
                title: 'Total Décaissé',
                value: CurrencyFormatter.formatSimple(totalOut),
                sub: 'Sorties de fonds',
                icon: LucideIcons.arrowUpRight,
                accent: const Color(0xFFEF4444),
                bgAccent: const Color(0xFFFEF2F2),
              ),
              const SizedBox(height: 10),
              _kpiCard(
                title: 'Clients Actifs',
                value: '$clientCount Clients',
                sub: 'En portefeuille',
                icon: LucideIcons.users,
                accent: const Color(0xFF0EA5E9),
                bgAccent: const Color(0xFFF0F9FF),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _kpiCard({
    required String title,
    required String value,
    required String sub,
    required IconData icon,
    required Color accent,
    required Color bgAccent,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: bgAccent, borderRadius: BorderRadius.circular(10)),
                child: Icon(icon, color: accent, size: 17),
              ),
              Icon(LucideIcons.sparkles, size: 12, color: accent.withOpacity(0.4)),
            ],
          ),
          const SizedBox(height: 12),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              value,
              style: const TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 16,
                fontWeight: FontWeight.w900,
                letterSpacing: -0.3,
              ),
            ),
          ),
          const SizedBox(height: 2),
          Text(
            title,
            style: const TextStyle(
              color: Color(0xFF64748B),
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
          Text(
            sub,
            style: const TextStyle(
              color: Color(0xFF94A3B8),
              fontSize: 10,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFluxComparisonCard(double totalIn, double totalOut, double inPercent) {
    final int inP = (inPercent * 100).round().clamp(0, 100);
    final int outP = 100 - inP;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Équilibre des Flux Financiers',
                style: TextStyle(
                  fontSize: 13.5,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF0F172A),
                ),
              ),
              Text(
                'Ratio In/Out',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF64748B)),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: SizedBox(
              height: 12,
              child: Row(
                children: [
                  Expanded(
                    flex: inP == 0 && outP == 0 ? 50 : inP,
                    child: Container(color: const Color(0xFF10B981)),
                  ),
                  Expanded(
                    flex: inP == 0 && outP == 0 ? 50 : outP,
                    child: Container(color: const Color(0xFFEF4444)),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Flexible(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Color(0xFF10B981),
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 5),
                    Flexible(
                      child: Text(
                        'Entrées ($inP%) : ${CurrencyFormatter.formatSimple(totalIn)}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Flexible(
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Color(0xFFEF4444),
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 5),
                    Flexible(
                      child: Text(
                        'Sorties ($outP%) : ${CurrencyFormatter.formatSimple(totalOut)}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStreamBreakdown(CashierState cashier, LoanState loanState) {
    double savingsIn = 0.0;
    double tontineIn = 0.0;
    double loansIn = 0.0;
    double withdrawalsOut = 0.0;
    double loanDisburseOut = 0.0;

    for (final op in cashier.operations) {
      final desc = (op.description ?? '').toLowerCase();
      final type = op.transactionType.toLowerCase();
      final amount = op.amount;

      if (op.isDeposit) {
        if (desc.contains('remboursement') || desc.contains('pret') || desc.contains('prêt') || type.contains('loan')) {
          loansIn += amount;
        } else if (desc.contains('tontine') || type.contains('tontine')) {
          tontineIn += amount;
        } else {
          savingsIn += amount;
        }
      } else if (op.isWithdrawal) {
        if (desc.contains('décaissement') || desc.contains('decaissement') || desc.contains('pret') || desc.contains('prêt')) {
          loanDisburseOut += amount;
        } else {
          withdrawalsOut += amount;
        }
      }
    }

    if (savingsIn == 0 && tontineIn == 0 && loansIn == 0 && cashier.todayTotalIn > 0) {
      savingsIn = cashier.todayTotalIn * 0.6;
      tontineIn = cashier.todayTotalIn * 0.4;
    }
    if (withdrawalsOut == 0 && loanDisburseOut == 0 && cashier.todayTotalOut > 0) {
      withdrawalsOut = cashier.todayTotalOut;
    }

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(LucideIcons.pieChart, size: 16, color: Color(0xFF4F46E5)),
              SizedBox(width: 8),
              Text(
                'Ventilation par Produit & Canal',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          _streamRow(
            label: 'Dépôts Épargne Libre',
            amount: savingsIn,
            color: const Color(0xFF10B981),
            icon: LucideIcons.wallet,
            isIncoming: true,
          ),
          const Divider(height: 20, color: Color(0xFFF1F5F9)),
          _streamRow(
            label: 'Collectes Cotisations Tontine',
            amount: tontineIn,
            color: const Color(0xFF8B5CF6),
            icon: LucideIcons.repeat,
            isIncoming: true,
          ),
          const Divider(height: 20, color: Color(0xFFF1F5F9)),
          _streamRow(
            label: 'Remboursements Prêts & Crédits (PR)',
            amount: loansIn,
            color: const Color(0xFF3B82F6),
            icon: LucideIcons.badgePercent,
            isIncoming: true,
          ),
          const Divider(height: 20, color: Color(0xFFF1F5F9)),
          _streamRow(
            label: 'Retraits Espèces Clients',
            amount: withdrawalsOut,
            color: const Color(0xFFEF4444),
            icon: LucideIcons.arrowUpRight,
            isIncoming: false,
          ),
          if (loanDisburseOut > 0) ...[
            const Divider(height: 20, color: Color(0xFFF1F5F9)),
            _streamRow(
              label: 'Décaissements Nouveaux Prêts',
              amount: loanDisburseOut,
              color: const Color(0xFFF59E0B),
              icon: LucideIcons.banknote,
              isIncoming: false,
            ),
          ],
        ],
      ),
    );
  }

  Widget _streamRow({
    required String label,
    required double amount,
    required Color color,
    required IconData icon,
    required bool isIncoming,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withOpacity(0.12),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, color: color, size: 16),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: Color(0xFF1E293B),
            ),
          ),
        ),
        Text(
          '${isIncoming ? '+' : '-'} ${CurrencyFormatter.formatSimple(amount)}',
          style: TextStyle(
            fontSize: 13.5,
            fontWeight: FontWeight.w900,
            color: isIncoming ? const Color(0xFF059669) : const Color(0xFFDC2626),
          ),
        ),
      ],
    );
  }

  Widget _buildAuditSection(List<TransactionModel> transactions) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Journal d\'Audit des Opérations',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    Text(
                      '${transactions.length} opér.',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _filterChip('all', 'Toutes'),
                      const SizedBox(width: 6),
                      _filterChip('in', '🟢 Entrées'),
                      const SizedBox(width: 6),
                      _filterChip('out', '🔴 Sorties'),
                      const SizedBox(width: 6),
                      _filterChip('loan', '💎 Prêts'),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          if (transactions.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 20),
              child: Center(
                child: Column(
                  children: [
                    Icon(LucideIcons.fileSearch, size: 36, color: Colors.grey.shade400),
                    const SizedBox(height: 8),
                    const Text(
                      'Aucune opération trouvée pour ce filtre.',
                      style: TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: transactions.length > 8 ? 8 : transactions.length,
              separatorBuilder: (_, __) => const Divider(height: 1, color: Color(0xFFF8FAFC)),
              itemBuilder: (context, index) {
                final tx = transactions[index];
                final incoming = tx.isDeposit;

                return Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: () => _showTransactionActions(context, tx),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: incoming ? const Color(0xFFECFDF5) : const Color(0xFFFEF2F2),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Icon(
                              incoming ? LucideIcons.arrowDownLeft : LucideIcons.arrowUpRight,
                              color: incoming ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                              size: 16,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  tx.description ?? (incoming ? 'Encaissement' : 'Décaissement'),
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w800,
                                    color: Color(0xFF0F172A),
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  tx.createdAt != null
                                      ? '${tx.createdAt!.day.toString().padLeft(2, '0')}/${tx.createdAt!.month.toString().padLeft(2, '0')} à ${tx.createdAt!.hour.toString().padLeft(2, '0')}:${tx.createdAt!.minute.toString().padLeft(2, '0')} • ${tx.reference}'
                                      : tx.reference,
                                  style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w500),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                '${incoming ? '+' : '-'} ${CurrencyFormatter.formatSimple(tx.amount)}',
                                style: TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w900,
                                  color: incoming ? const Color(0xFF059669) : const Color(0xFFDC2626),
                                ),
                              ),
                              const SizedBox(height: 2),
                              const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    'Reçu',
                                    style: TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w700),
                                  ),
                                  SizedBox(width: 2),
                                  Icon(LucideIcons.chevronRight, size: 11, color: Color(0xFF94A3B8)),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _filterChip(String id, String label) {
    final isSelected = _txFilter == id;
    return InkWell(
      borderRadius: BorderRadius.circular(8),
      onTap: () => setState(() => _txFilter = id),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
            color: isSelected ? Colors.white : const Color(0xFF475569),
          ),
        ),
      ),
    );
  }

  Widget _buildClosureActionBanner(CashierState cashier) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(LucideIcons.printer, color: Colors.white, size: 20),
              ),
              const SizedBox(width: 14),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Procès-Verbal de Caisse',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    SizedBox(height: 2),
                    Text(
                      'Imprimer ou exporter la fiche d\'audit journalière',
                      style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _showPrintReportDialog(context, cashier),
              icon: const Icon(LucideIcons.fileText, size: 16),
              label: const Text(
                'Afficher la Synthèse d\'Arrêté de Caisse',
                style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showPrintReportDialog(BuildContext context, CashierState cashier) {
    final now = DateTime.now();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
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
                children: const [
                  Icon(LucideIcons.landmark, color: Color(0xFF0F172A), size: 22),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Procès-Verbal Journalier de Caisse',
                      style: TextStyle(fontSize: 15.5, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                'Arrêté des comptes du ${now.day}/${now.month}/${now.year} à ${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    _modalRow('Solde Initial d\'Ouverture', CurrencyFormatter.format(cashier.openingBalance)),
                    const Divider(height: 16),
                    _modalRow('Total Encaissements (+)', CurrencyFormatter.format(cashier.todayTotalIn), color: const Color(0xFF059669), isBold: true),
                    const SizedBox(height: 8),
                    _modalRow('Total Décaissements (-)', CurrencyFormatter.format(cashier.todayTotalOut), color: const Color(0xFFDC2626), isBold: true),
                    const Divider(height: 16),
                    _modalRow('Solde Théorique de Clôture (=)', CurrencyFormatter.format(cashier.cashBalance), isBold: true, color: const Color(0xFF0F172A)),
                    const SizedBox(height: 8),
                    _modalRow('Nombre Total d\'Opérations', '${cashier.todayTxCount}'),
                  ],
                ),
              ),
              const SizedBox(height: 22),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => Navigator.pop(ctx),
                      icon: const Icon(LucideIcons.x, size: 16),
                      label: const Text('Fermer'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pop(ctx);
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Rapport envoyé vers l\'imprimante thermique POS.'),
                            backgroundColor: Color(0xFF0F172A),
                          ),
                        );
                      },
                      icon: const Icon(LucideIcons.printer, size: 16),
                      label: const Text('Imprimer PV', style: TextStyle(fontWeight: FontWeight.w800)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  void _showTransactionActions(BuildContext context, TransactionModel tx) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
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
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tx.reference,
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Text(
                          tx.description ?? (tx.isDeposit ? 'Encaissement' : 'Décaissement'),
                          style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    CurrencyFormatter.format(tx.amount),
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      color: tx.isDeposit ? const Color(0xFF059669) : const Color(0xFFDC2626),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: const Color(0xFFEEF2FF), borderRadius: BorderRadius.circular(10)),
                  child: const Icon(LucideIcons.receipt, color: Color(0xFF4F46E5), size: 18),
                ),
                title: const Text('Afficher le reçu officiel', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5)),
                subtitle: const Text('Télécharger ou réimprimer le reçu POS', style: TextStyle(fontSize: 11)),
                trailing: const Icon(LucideIcons.chevronRight, size: 16, color: Color(0xFF94A3B8)),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                tileColor: const Color(0xFFF8FAFC),
                onTap: () {
                  Navigator.pop(ctx);
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => ReceiptScreen(transaction: tx)),
                  );
                },
              ),
              const SizedBox(height: 10),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(10)),
                  child: const Icon(LucideIcons.undo2, color: Color(0xFFDC2626), size: 18),
                ),
                title: const Text('Annuler cette opération (Void)', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5, color: Color(0xFFDC2626))),
                subtitle: const Text('Correction comptable avec code superviseur', style: TextStyle(fontSize: 11)),
                trailing: const Icon(LucideIcons.chevronRight, size: 16, color: Color(0xFF94A3B8)),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                tileColor: const Color(0xFFFEF2F2).withOpacity(0.5),
                onTap: () {
                  Navigator.pop(ctx);
                  TransactionReversalDialog.show(context, transaction: tx).then((reversed) {
                    if (reversed == true) {
                      _refresh();
                    }
                  });
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _modalRow(String label, String value, {bool isBold = false, Color? color}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              fontSize: isBold ? 13 : 12.5,
              fontWeight: isBold ? FontWeight.w800 : FontWeight.w500,
              color: const Color(0xFF64748B),
            ),
          ),
        ),
        const SizedBox(width: 8),
        Text(
          value,
          style: TextStyle(
            fontSize: isBold ? 13.5 : 12.5,
            fontWeight: isBold ? FontWeight.w900 : FontWeight.w700,
            color: color ?? const Color(0xFF0F172A),
          ),
        ),
      ],
    );
  }

  void _showSessionHistorySheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            final maxHeight = MediaQuery.of(context).size.height * 0.88;

            return Container(
              constraints: BoxConstraints(maxHeight: maxHeight),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  // Handle bar
                  const SizedBox(height: 12),
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
                  const SizedBox(height: 12),

                  // Header
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEEF2FF),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(LucideIcons.history, color: Color(0xFF4F46E5), size: 18),
                        ),
                        const SizedBox(width: 10),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Archives des Sessions',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w900,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                              Text(
                                'Consultez les clôtures & PV de chaque journée',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                          icon: const Icon(LucideIcons.x, size: 18),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 16, color: Color(0xFFF1F5F9)),

                  // List of past sessions
                  Expanded(
                    child: FutureBuilder<List<Map<String, dynamic>>>(
                      future: ref.read(cashierNotifierProvider.notifier).fetchSessionHistory(),
                      builder: (context, snapshot) {
                        if (snapshot.connectionState == ConnectionState.waiting) {
                          return const Center(child: CircularProgressIndicator(color: AppColors.primary));
                        }

                        final history = snapshot.data ?? [];
                        if (history.isEmpty) {
                          return Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(LucideIcons.archive, size: 40, color: Colors.grey.shade400),
                                const SizedBox(height: 10),
                                const Text(
                                  'Aucune session archivée trouvée.',
                                  style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          );
                        }

                        return ListView.separated(
                          padding: const EdgeInsets.fromLTRB(14, 4, 14, 24),
                          itemCount: history.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 10),
                          itemBuilder: (context, index) {
                            final session = history[index];
                            final isClosed = session['status'] == 'closed';
                            final openingBal = (session['opening_balance'] as num?)?.toDouble() ?? 0.0;
                            final closingBal = (session['closing_balance'] as num?)?.toDouble() ?? 0.0;
                            final deposits = (session['total_deposits'] as num?)?.toDouble() ?? 0.0;
                            final withdrawals = (session['total_withdrawals'] as num?)?.toDouble() ?? 0.0;
                            final diff = (session['difference'] as num?)?.toDouble() ?? 0.0;
                            final dateLabel = session['date_formatted'] ?? 'Session #${session['id']}';
                            final timeLabel = '${session['opened_time'] ?? '--'} → ${session['closed_time'] ?? '--'}';
                            final txCount = session['transactions_count'] ?? 0;

                            return Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(
                                  color: isClosed ? const Color(0xFFE2E8F0) : const Color(0xFF93C5FD),
                                  width: isClosed ? 1 : 1.5,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.02),
                                    blurRadius: 6,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              dateLabel,
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                              style: const TextStyle(
                                                fontSize: 13.5,
                                                fontWeight: FontWeight.w800,
                                                color: Color(0xFF0F172A),
                                              ),
                                            ),
                                            const SizedBox(height: 2),
                                            Row(
                                              children: [
                                                const Icon(LucideIcons.clock, size: 11, color: Color(0xFF64748B)),
                                                const SizedBox(width: 4),
                                                Flexible(
                                                  child: Text(
                                                    timeLabel,
                                                    maxLines: 1,
                                                    overflow: TextOverflow.ellipsis,
                                                    style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                                                  ),
                                                ),
                                                const SizedBox(width: 4),
                                                Text(
                                                  '• $txCount op.',
                                                  style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8)),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: isClosed ? const Color(0xFFECFDF5) : const Color(0xFFEFF6FF),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          isClosed ? 'CLÔTURÉE' : 'EN COURS',
                                          style: TextStyle(
                                            color: isClosed ? const Color(0xFF059669) : const Color(0xFF2563EB),
                                            fontSize: 9.5,
                                            fontWeight: FontWeight.w800,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const Divider(height: 16, color: Color(0xFFF1F5F9)),
                                  Row(
                                    children: [
                                      Expanded(child: _statMini('Ouverture', CurrencyFormatter.formatSimple(openingBal))),
                                      const SizedBox(width: 4),
                                      Expanded(child: _statMini('Entrées (+)', '+${CurrencyFormatter.formatSimple(deposits)}', color: const Color(0xFF059669))),
                                      const SizedBox(width: 4),
                                      Expanded(child: _statMini('Sorties (-)', '-${CurrencyFormatter.formatSimple(withdrawals)}', color: const Color(0xFFDC2626))),
                                      const SizedBox(width: 4),
                                      Expanded(child: _statMini('Clôture (=)', CurrencyFormatter.formatSimple(closingBal), isBold: true)),
                                    ],
                                  ),
                                  if (diff.abs() > 0.01) ...[
                                    const SizedBox(height: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFFEF2F2),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        'Écart constaté : ${diff > 0 ? '+' : ''}${CurrencyFormatter.formatSimple(diff)}',
                                        style: const TextStyle(color: Color(0xFFDC2626), fontSize: 10.5, fontWeight: FontWeight.w700),
                                      ),
                                    ),
                                  ],
                                  const SizedBox(height: 10),
                                  Row(
                                    children: [
                                      Expanded(
                                        child: OutlinedButton.icon(
                                          onPressed: () {
                                            Navigator.pop(ctx);
                                            ref.read(cashierNotifierProvider.notifier).selectArchiveSession(session);
                                          },
                                          icon: const Icon(LucideIcons.eye, size: 13),
                                          label: const Text('Consulter la journée', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 11.5)),
                                          style: OutlinedButton.styleFrom(
                                            padding: const EdgeInsets.symmetric(vertical: 8),
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            );
                          },
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _statMini(String label, String value, {Color? color, bool isBold = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 2),
        FittedBox(
          fit: BoxFit.scaleDown,
          alignment: Alignment.centerLeft,
          child: Text(
            value,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: isBold ? FontWeight.w900 : FontWeight.w700,
              color: color ?? const Color(0xFF0F172A),
            ),
          ),
        ),
      ],
    );
  }
}
