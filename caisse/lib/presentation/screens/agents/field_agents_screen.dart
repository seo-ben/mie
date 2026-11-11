import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/field_agent_model.dart';
import '../../../domain/notifiers/field_agents_notifier.dart';
import 'field_agent_detail_screen.dart';

class FieldAgentsScreen extends ConsumerStatefulWidget {
  const FieldAgentsScreen({super.key});

  @override
  ConsumerState<FieldAgentsScreen> createState() => _FieldAgentsScreenState();
}

class _FieldAgentsScreenState extends ConsumerState<FieldAgentsScreen> {
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(fieldAgentsNotifierProvider.notifier).fetchFieldAgents();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _selectCustomDateRange(FieldAgentsState state) async {
    final now = DateTime.now();
    final initialStart = state.startDate ?? state.selectedDate;
    final initialEnd = state.endDate ?? state.selectedDate;

    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2023),
      lastDate: now.add(const Duration(days: 1)),
      initialDateRange: DateTimeRange(
        start: initialStart.isAfter(now) ? now : initialStart,
        end: initialEnd.isAfter(now) ? now : initialEnd,
      ),
      saveText: 'Valider',
      helpText: 'Sélectionner une période',
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppColors.primary,
              onPrimary: Colors.white,
              onSurface: Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      if (picked.start.year == picked.end.year &&
          picked.start.month == picked.end.month &&
          picked.start.day == picked.end.day) {
        ref.read(fieldAgentsNotifierProvider.notifier).changeDate(picked.start);
      } else {
        ref.read(fieldAgentsNotifierProvider.notifier).changeDateRange(picked.start, picked.end);
      }
    }
  }

  String _formatDisplayLabel(FieldAgentsState state) {
    if (state.isRangeMode) {
      final s = state.startDate!;
      final e = state.endDate!;
      return '${s.day.toString().padLeft(2, '0')}/${s.month.toString().padLeft(2, '0')} - ${e.day.toString().padLeft(2, '0')}/${e.month.toString().padLeft(2, '0')}';
    }
    if (state.period == 'week') return 'Cette semaine';
    if (state.period == 'month') return 'Ce mois';
    if (state.period == 'all') return 'Tout';

    final d = state.selectedDate;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final target = DateTime(d.year, d.month, d.day);

    if (target == today) return 'Aujourd\'hui';
    if (target == today.subtract(const Duration(days: 1))) return 'Hier';
    return '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(fieldAgentsNotifierProvider);
    final teamData = state.teamData;
    final agents = teamData?.agents ?? [];
    final summary = teamData?.summary ?? const AgencyFieldSummary();
    final selectedDate = state.selectedDate;

    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: const Column(
          children: [
            Text(
              'Agents de Terrain & Collectes',
              style: TextStyle(
                color: Color(0xFF0F172A),
                fontWeight: FontWeight.w800,
                fontSize: 16,
              ),
            ),
            Text(
              'Supervision & Pointage des Collecteurs',
              style: TextStyle(
                color: Color(0xFF64748B),
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Actualiser',
            icon: const Icon(LucideIcons.refreshCw, size: 18, color: Color(0xFF64748B)),
            onPressed: () => ref.read(fieldAgentsNotifierProvider.notifier).fetchFieldAgents(),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () async {
          await ref.read(fieldAgentsNotifierProvider.notifier).fetchFieldAgents();
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
                child: Column(
                  children: [
                    _buildDateSelector(state),
                    const SizedBox(height: 12),
                    _buildAgencySummaryCard(summary, selectedDate),
                    const SizedBox(height: 12),
                    _buildSearchBar(),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
            ),
            if (state.isLoading && teamData == null)
              const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
              )
            else if (state.errorMessage != null && teamData == null)
              SliverFillRemaining(
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(LucideIcons.alertTriangle, size: 42, color: Color(0xFFEF4444)),
                        const SizedBox(height: 12),
                        Text(
                          state.errorMessage!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => ref.read(fieldAgentsNotifierProvider.notifier).fetchFieldAgents(),
                          icon: const Icon(LucideIcons.refreshCw, size: 14),
                          label: const Text('Réessayer'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else if (agents.isEmpty)
              SliverFillRemaining(
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: const BoxDecoration(
                            color: Color(0xFFF1F5F9),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(LucideIcons.usersRound, size: 40, color: Color(0xFF94A3B8)),
                        ),
                        const SizedBox(height: 14),
                        const Text(
                          'Aucun agent de terrain trouvé',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF1E293B)),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Aucun collecteur de terrain rattaché à cette agence ou correspondant à la recherche.',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 30),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final agent = agents[index];
                      return _buildAgentCard(agent, selectedDate);
                    },
                    childCount: agents.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildDateSelector(FieldAgentsState state) {
    final isToday = state.period == 'today' && !state.isRangeMode;
    final isYesterday = state.period == 'yesterday' && !state.isRangeMode;
    final isWeek = state.period == 'week' && !state.isRangeMode;
    final isMonth = state.period == 'month' && !state.isRangeMode;
    final isCustom = state.isRangeMode || state.period == 'custom' || state.period == 'custom_range';

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _periodChip('Aujourd\'hui', 'today', isToday),
          const SizedBox(width: 8),
          _periodChip('Hier', 'yesterday', isYesterday),
          const SizedBox(width: 8),
          _periodChip('Cette semaine', 'week', isWeek),
          const SizedBox(width: 8),
          _periodChip('Ce mois', 'month', isMonth),
          const SizedBox(width: 8),
          InkWell(
            onTap: () => _selectCustomDateRange(state),
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: isCustom ? AppColors.primary : Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isCustom ? AppColors.primary : const Color(0xFFE2E8F0),
                  width: isCustom ? 1.5 : 1,
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    LucideIcons.calendarRange,
                    size: 14,
                    color: isCustom ? Colors.white : const Color(0xFF64748B),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    isCustom ? _formatDisplayLabel(state) : 'Intervalle...',
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w700,
                      color: isCustom ? Colors.white : const Color(0xFF1E293B),
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

  Widget _periodChip(String label, String periodId, bool isSelected) {
    return InkWell(
      onTap: () => ref.read(fieldAgentsNotifierProvider.notifier).changePeriod(periodId),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? AppColors.primary : const Color(0xFFE2E8F0),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
            color: isSelected ? Colors.white : const Color(0xFF64748B),
          ),
        ),
      ),
    );
  }

  String _formatDateDisplay(DateTime d) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final target = DateTime(d.year, d.month, d.day);

    if (target == today) return 'Aujourd\'hui';
    if (target == today.subtract(const Duration(days: 1))) return 'Hier';
    return '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}/${d.year}';
  }

  Widget _buildAgencySummaryCard(AgencyFieldSummary summary, DateTime date) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF008080), Color(0xFF005B5B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF008080).withValues(alpha: 0.25),
            blurRadius: 14,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(LucideIcons.banknote, color: Colors.white, size: 16),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'TOTAL COLLECTÉ SUR LE TERRAIN',
                    style: TextStyle(
                      color: Color(0xFFCCFBF1),
                      fontSize: 10.5,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '${summary.activeAgents}/${summary.totalAgents} actifs',
                  style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.w800),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              CurrencyFormatter.format(summary.totalCollected),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 28,
                fontWeight: FontWeight.w900,
                letterSpacing: -0.5,
              ),
            ),
          ),
          const SizedBox(height: 12),
          const Divider(color: Color(0x33FFFFFF), height: 1),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(LucideIcons.receipt, color: Color(0xFFCCFBF1), size: 13),
                  const SizedBox(width: 5),
                  Text(
                    '${summary.totalOperations} opérations enregistrées',
                    style: const TextStyle(color: Color(0xFFE6FFFA), fontSize: 11.5, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
              Text(
                'Date: ${_formatDateDisplay(date)}',
                style: const TextStyle(color: Color(0xFFCCFBF1), fontSize: 10.5, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Container(
      height: 44,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 4,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: TextField(
        controller: _searchController,
        onChanged: (val) => ref.read(fieldAgentsNotifierProvider.notifier).search(val),
        style: const TextStyle(fontSize: 13),
        decoration: InputDecoration(
          hintText: 'Rechercher un agent par nom ou téléphone...',
          hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
          prefixIcon: const Icon(LucideIcons.search, size: 16, color: Color(0xFF94A3B8)),
          suffixIcon: _searchController.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(LucideIcons.x, size: 14, color: Color(0xFF94A3B8)),
                  onPressed: () {
                    _searchController.clear();
                    ref.read(fieldAgentsNotifierProvider.notifier).search('');
                  },
                )
              : null,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 10),
        ),
      ),
    );
  }

  Widget _buildAgentCard(FieldAgentSummaryModel agent, DateTime selectedDate) {
    final col = agent.collections;
    final hasCollected = col.totalCollected > 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: hasCollected ? const Color(0xFF10B981).withValues(alpha: 0.3) : const Color(0xFFE2E8F0),
          width: hasCollected ? 1.2 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: InkWell(
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => FieldAgentDetailScreen(
                agentId: agent.id,
                agentName: agent.fullName,
                initialDate: selectedDate,
              ),
            ),
          );
        },
        borderRadius: BorderRadius.circular(18),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: hasCollected
                          ? const Color(0xFF10B981).withValues(alpha: 0.12)
                          : const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Center(
                      child: Text(
                        agent.firstName.isNotEmpty ? agent.firstName.substring(0, 1).toUpperCase() : 'A',
                        style: TextStyle(
                          color: hasCollected ? const Color(0xFF10B981) : const Color(0xFF64748B),
                          fontWeight: FontWeight.w900,
                          fontSize: 18,
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
                            Expanded(
                              child: Text(
                                agent.fullName,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 14.5,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
                              decoration: BoxDecoration(
                                color: hasCollected ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                hasCollected ? '${col.operationsCount} reçus' : 'Aucune collecte',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: hasCollected ? const Color(0xFF15803D) : const Color(0xFF64748B),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 3),
                        Row(
                          children: [
                            const Icon(LucideIcons.phone, size: 11, color: Color(0xFF94A3B8)),
                            const SizedBox(width: 4),
                            Text(
                              agent.phone ?? 'N/A',
                              style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(width: 10),
                            const Icon(LucideIcons.users, size: 11, color: Color(0xFF94A3B8)),
                            const SizedBox(width: 4),
                            Text(
                              '${agent.totalClients} clients',
                              style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Collecte du jour',
                          style: TextStyle(fontSize: 10.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          CurrencyFormatter.format(col.totalCollected),
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                            color: hasCollected ? const Color(0xFF10B981) : const Color(0xFF94A3B8),
                          ),
                        ),
                      ],
                    ),
                    if (hasCollected)
                      Row(
                        children: [
                          if (col.tontineAmount > 0)
                            _miniBreakdownChip('Tontine', col.tontineAmount, const Color(0xFF8B5CF6)),
                          if (col.savingsAmount > 0) ...[
                            const SizedBox(width: 6),
                            _miniBreakdownChip('Épargne', col.savingsAmount, const Color(0xFF0284C7)),
                          ],
                        ],
                      ),
                    Row(
                      children: const [
                        Text(
                          'Détails',
                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primary),
                        ),
                        SizedBox(width: 3),
                        Icon(LucideIcons.chevronRight, size: 14, color: AppColors.primary),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _miniBreakdownChip(String label, double amount, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(fontSize: 8.5, fontWeight: FontWeight.w700, color: color)),
          Text(
            CurrencyFormatter.formatSimple(amount),
            style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: color),
          ),
        ],
      ),
    );
  }
}
