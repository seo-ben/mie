import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/loan_model.dart';
import '../../../domain/notifiers/loan_notifier.dart';
import 'loan_detail_screen.dart';
import 'loan_application_screen.dart';

class LoansListScreen extends ConsumerStatefulWidget {
  final bool isTab;

  const LoansListScreen({super.key, this.isTab = false});

  @override
  ConsumerState<LoansListScreen> createState() => _LoansListScreenState();
}

class _LoansListScreenState extends ConsumerState<LoansListScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(loanNotifierProvider.notifier).fetchLoans(status: _selectedFilter);
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final loanState = ref.watch(loanNotifierProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FC),
      appBar: AppBar(
        automaticallyImplyLeading: false,
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: widget.isTab
            ? null
            : IconButton(
                icon: const Icon(LucideIcons.arrowLeft, color: Color(0xFF1E293B)),
                onPressed: () => Navigator.of(context).pop(),
              ),
        title: const Column(
          children: [
            Text(
              'Gestion des Prêts & Crédits',
              style: TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 16,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              'Suivi, Décaissements & Recouvrements',
              style: TextStyle(
                color: Color(0xFF64748B),
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: widget.isTab
            ? null
            : [
                IconButton(
                  tooltip: 'Actualiser',
                  icon: const Icon(LucideIcons.refreshCw, size: 18, color: Color(0xFF1E293B)),
                  onPressed: () => ref.read(loanNotifierProvider.notifier).fetchLoans(
                        status: _selectedFilter,
                        search: _searchController.text.trim(),
                      ),
                ),
              ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        elevation: 3,
        icon: const Icon(LucideIcons.plus, size: 18),
        label: const Text(
          'Nouveau Prêt',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
        ),
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const LoanApplicationScreen()),
          );
        },
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(loanNotifierProvider.notifier).fetchLoans(
              status: _selectedFilter,
              search: _searchController.text.trim(),
            ),
        child: CustomScrollView(
          slivers: [
            // KPI Metrics Header
            SliverToBoxAdapter(
              child: _buildMetricsHeader(loanState.metrics),
            ),

            // Search Bar & Filter Tabs
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Column(
                  children: [
                    TextField(
                      controller: _searchController,
                      onChanged: (val) {
                        ref.read(loanNotifierProvider.notifier).fetchLoans(
                              status: _selectedFilter,
                              search: val.trim(),
                            );
                      },
                      decoration: InputDecoration(
                        hintText: 'Rechercher par client, tél ou N° de prêt...',
                        hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                        prefixIcon: const Icon(LucideIcons.search, size: 18, color: Color(0xFF94A3B8)),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(LucideIcons.x, size: 16),
                                onPressed: () {
                                  _searchController.clear();
                                  ref.read(loanNotifierProvider.notifier).fetchLoans(
                                        status: _selectedFilter,
                                        search: '',
                                      );
                                },
                              )
                            : null,
                        filled: true,
                        fillColor: Colors.white,
                        contentPadding: const EdgeInsets.symmetric(vertical: 12),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(14),
                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildFilterTabs(),
                  ],
                ),
              ),
            ),

            // Loans List
            if (loanState.isLoading)
              const SliverFillRemaining(
                child: Center(
                  child: CircularProgressIndicator(),
                ),
              )
            else if (loanState.loans.isEmpty)
              SliverFillRemaining(
                child: _buildEmptyState(),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 90),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final loan = loanState.loans[index];
                      return _buildLoanCard(loan);
                    },
                    childCount: loanState.loans.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildMetricsHeader(LoanMetricsModel metrics) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 14, 16, 6),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.15),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'PORTEFEUILLE CRÉDITS',
                style: TextStyle(
                  color: Color(0xFF94A3B8),
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${metrics.repaymentRate}% Recouvré',
                  style: const TextStyle(
                    color: Color(0xFF34D399),
                    fontSize: 10.5,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: _metricColumn(
                  'Total Décaissé',
                  CurrencyFormatter.format(metrics.totalDisbursed),
                  const Color(0xFF60A5FA),
                ),
              ),
              Container(width: 1, height: 35, color: Colors.white.withValues(alpha: 0.1)),
              Expanded(
                child: _metricColumn(
                  'Total Remboursé',
                  CurrencyFormatter.format(metrics.totalRepaid),
                  const Color(0xFF34D399),
                ),
              ),
              Container(width: 1, height: 35, color: Colors.white.withValues(alpha: 0.1)),
              Expanded(
                child: _metricColumn(
                  'Encours Restant',
                  CurrencyFormatter.format(metrics.totalOutstanding),
                  const Color(0xFFFBBF24),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _metricColumn(String label, String value, Color valueColor) {
    return Column(
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.6),
            fontSize: 10,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 3),
        FittedBox(
          fit: BoxFit.scaleDown,
          child: Text(
            value,
            style: TextStyle(
              color: valueColor,
              fontSize: 13,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildFilterTabs() {
    final filters = [
      {'key': 'all', 'label': 'Tous'},
      {'key': 'in_progress', 'label': 'En cours'},
      {'key': 'pending', 'label': 'En attente'},
      {'key': 'approved', 'label': 'Approuvés'},
      {'key': 'completed', 'label': 'Soldés'},
    ];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: filters.map((f) {
          final isSelected = _selectedFilter == f['key'];
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              label: Text(
                f['label']!,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                  color: isSelected ? Colors.white : const Color(0xFF475569),
                ),
              ),
              selected: isSelected,
              selectedColor: const Color(0xFF0F172A),
              backgroundColor: Colors.white,
              side: BorderSide(
                color: isSelected ? const Color(0xFF0F172A) : const Color(0xFFE2E8F0),
              ),
              onSelected: (selected) {
                if (selected) {
                  setState(() => _selectedFilter = f['key']!);
                  ref.read(loanNotifierProvider.notifier).fetchLoans(
                        status: _selectedFilter,
                        search: _searchController.text.trim(),
                      );
                }
              },
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildLoanCard(LoanModel loan) {
    final clientName = loan.client?.fullName ?? 'Client';
    final clientPhone = loan.client?.phone ?? '';
    final progress = loan.repaymentProgress;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: InkWell(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => LoanDetailScreen(loan: loan),
            ),
          );
        },
        borderRadius: BorderRadius.circular(18),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header: Client & Status Badge
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Center(
                      child: Icon(LucideIcons.banknote, color: Color(0xFF475569), size: 20),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          clientName,
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${loan.loanNumber} • $clientPhone',
                          style: const TextStyle(
                            fontSize: 11.5,
                            color: Color(0xFF64748B),
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  _buildStatusBadge(loan.status),
                ],
              ),
              const SizedBox(height: 14),

              // Amounts summary row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Capital Accordé',
                        style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        CurrencyFormatter.format(loan.effectiveAmount),
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w900,
                          color: Color(0xFF0F172A),
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Text(
                        'Reste à payer',
                        style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        CurrencyFormatter.format(loan.remainingDue),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w900,
                          color: loan.isCompleted ? const Color(0xFF10B981) : const Color(0xFFD97706),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 10),

              // Repayment Progress Bar
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Progression : ${(progress * 100).toInt()}%',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                      ),
                      Text(
                        '${CurrencyFormatter.format(loan.totalPaid)} / ${CurrencyFormatter.format(loan.effectiveTotalDue)}',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(999),
                    child: LinearProgressIndicator(
                      value: progress,
                      minHeight: 6,
                      backgroundColor: const Color(0xFFF1F5F9),
                      valueColor: AlwaysStoppedAnimation<Color>(
                        loan.isCompleted ? const Color(0xFF10B981) : const Color(0xFF6366F1),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),

              // Action buttons per status
              _buildCardActions(loan),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color bg;
    Color text;
    String label;

    switch (status) {
      case 'pending':
      case 'under_review':
        bg = const Color(0xFFFEF3C7);
        text = const Color(0xFFB45309);
        label = 'En attente';
        break;
      case 'approved':
        bg = const Color(0xFFDBEAFE);
        text = const Color(0xFF1D4ED8);
        label = 'Approuvé';
        break;
      case 'disbursed':
      case 'active':
        bg = const Color(0xFFECFDF5);
        text = const Color(0xFF047857);
        label = 'En cours';
        break;
      case 'completed':
      case 'repaid':
        bg = const Color(0xFFF3E8FF);
        text = const Color(0xFF7E22CE);
        label = 'Soldé 🎉';
        break;
      case 'defaulted':
        bg = const Color(0xFFFEE2E2);
        text = const Color(0xFFB91C1C);
        label = 'En retard';
        break;
      default:
        bg = const Color(0xFFF1F5F9);
        text = const Color(0xFF475569);
        label = status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: text,
          fontSize: 10.5,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }

  Widget _buildCardActions(LoanModel loan) {
    if (loan.isPending) {
      return SizedBox(
        width: double.infinity,
        child: ElevatedButton.icon(
          onPressed: () {
            Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => LoanDetailScreen(loan: loan)),
            );
          },
          icon: const Icon(LucideIcons.checkCheck, size: 14),
          label: const Text('Examiner & Valider le Prêt', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF2563EB),
            foregroundColor: Colors.white,
            elevation: 0,
            padding: const EdgeInsets.symmetric(vertical: 8),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
      );
    }

    if (loan.isApproved) {
      return SizedBox(
        width: double.infinity,
        child: ElevatedButton.icon(
          onPressed: () {
            Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => LoanDetailScreen(loan: loan)),
            );
          },
          icon: const Icon(LucideIcons.arrowUpRight, size: 14),
          label: const Text('Décaisser les espèces au client', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF059669),
            foregroundColor: Colors.white,
            elevation: 0,
            padding: const EdgeInsets.symmetric(vertical: 8),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
      );
    }

    if (loan.isDisbursed) {
      return Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => LoanDetailScreen(loan: loan)),
                );
              },
              icon: const Icon(LucideIcons.calendarDays, size: 13),
              label: const Text('Échéancier', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: ElevatedButton.icon(
              onPressed: () {
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => LoanDetailScreen(loan: loan, autoOpenRepay: true)),
                );
              },
              icon: const Icon(LucideIcons.arrowDownLeft, size: 13),
              label: const Text('Rembourser', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w800)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F172A),
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      );
    }

    return const SizedBox.shrink();
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              shape: BoxShape.circle,
            ),
            child: const Icon(LucideIcons.folderClosed, size: 36, color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 14),
          const Text(
            'Aucun dossier de prêt trouvé',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: Color(0xFF334155),
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Créez une nouvelle demande pour initier un micro-prêt.',
            style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
          ),
        ],
      ),
    );
  }
}
