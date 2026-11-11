import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/field_agent_model.dart';
import '../../../domain/notifiers/field_agents_notifier.dart';
import '../clients/client_detail_screen.dart';
import '../operations/deposit_screen.dart';

class FieldAgentDetailScreen extends ConsumerStatefulWidget {
  final int agentId;
  final String agentName;
  final DateTime initialDate;

  const FieldAgentDetailScreen({
    super.key,
    required this.agentId,
    required this.agentName,
    required this.initialDate,
  });

  @override
  ConsumerState<FieldAgentDetailScreen> createState() => _FieldAgentDetailScreenState();
}

class _FieldAgentDetailScreenState extends ConsumerState<FieldAgentDetailScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  late DateTime _selectedDate;
  DateTime? _startDate;
  DateTime? _endDate;
  String _period = 'today';
  final TextEditingController _clientSearchController = TextEditingController();
  String _clientSearchQuery = '';

  @override
  void initState() {
    super.initState();
    _selectedDate = widget.initialDate;
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(fieldAgentsNotifierProvider.notifier).fetchAgentDetail(
            widget.agentId,
            date: _selectedDate,
          );
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _clientSearchController.dispose();
    super.dispose();
  }

  void _onDatePicked(DateTime date) {
    setState(() {
      _selectedDate = date;
      _startDate = null;
      _endDate = null;
      _period = 'custom';
    });
    ref.read(fieldAgentsNotifierProvider.notifier).fetchAgentDetail(
          widget.agentId,
          date: date,
          dateStart: null,
          dateEnd: null,
          period: 'custom',
        );
  }

  void _onPeriodChanged(String period) {
    setState(() {
      _period = period;
      _startDate = null;
      _endDate = null;
    });
    ref.read(fieldAgentsNotifierProvider.notifier).fetchAgentDetail(
          widget.agentId,
          period: period,
          dateStart: null,
          dateEnd: null,
        );
  }

  void _onRangePicked(DateTime start, DateTime end) {
    setState(() {
      _startDate = start;
      _endDate = end;
      _period = 'custom_range';
    });
    ref.read(fieldAgentsNotifierProvider.notifier).fetchAgentDetail(
          widget.agentId,
          dateStart: start,
          dateEnd: end,
          period: 'custom_range',
        );
  }

  Future<void> _selectCustomDateRange() async {
    final now = DateTime.now();
    final initialStart = _startDate ?? _selectedDate;
    final initialEnd = _endDate ?? _selectedDate;

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
        _onDatePicked(picked.start);
      } else {
        _onRangePicked(picked.start, picked.end);
      }
    }
  }

  String _formatDateDisplay([DateTime? specificDate]) {
    if (specificDate != null) {
      final now = DateTime.now();
      final today = DateTime(now.year, now.month, now.day);
      final target = DateTime(specificDate.year, specificDate.month, specificDate.day);

      if (target == today) return 'Aujourd\'hui';
      if (target == today.subtract(const Duration(days: 1))) return 'Hier';
      return '${specificDate.day.toString().padLeft(2, '0')}/${specificDate.month.toString().padLeft(2, '0')}/${specificDate.year}';
    }

    if (_startDate != null && _endDate != null && _startDate != _endDate) {
      return '${_startDate!.day.toString().padLeft(2, '0')}/${_startDate!.month.toString().padLeft(2, '0')} - ${_endDate!.day.toString().padLeft(2, '0')}/${_endDate!.month.toString().padLeft(2, '0')}';
    }
    if (_period == 'week') return 'Cette semaine';
    if (_period == 'month') return 'Ce mois';
    if (_period == 'all') return 'Tout l\'historique';

    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final target = DateTime(_selectedDate.year, _selectedDate.month, _selectedDate.day);

    if (target == today) return 'Aujourd\'hui';
    if (target == today.subtract(const Duration(days: 1))) return 'Hier';
    return '${_selectedDate.day.toString().padLeft(2, '0')}/${_selectedDate.month.toString().padLeft(2, '0')}/${_selectedDate.year}';
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(fieldAgentsNotifierProvider);
    final detail = state.agentDetail;
    final isLoading = state.isDetailLoading;

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
        title: Column(
          children: [
            Text(
              widget.agentName,
              style: const TextStyle(
                color: Color(0xFF0F172A),
                fontWeight: FontWeight.w800,
                fontSize: 16,
              ),
            ),
            const Text(
              'Agent de Terrain & Collecteur',
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
            onPressed: () => ref.read(fieldAgentsNotifierProvider.notifier).fetchAgentDetail(
                  widget.agentId,
                  date: _selectedDate,
                ),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: isLoading && detail == null
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : Column(
              children: [
                _buildHeaderSummary(detail),
                _buildDateSelector(),
                _buildTabBar(detail),
                Expanded(
                  child: TabBarView(
                    controller: _tabController,
                    children: [
                      _buildCollectionsTab(detail, isLoading),
                      _buildClientsTab(detail, isLoading),
                    ],
                  ),
                ),
              ],
            ),
      bottomNavigationBar: detail != null && detail.summary.totalCollected > 0
          ? Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.06),
                    blurRadius: 10,
                    offset: const Offset(0, -3),
                  ),
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Espèces à réceptionner :',
                            style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                          ),
                          Text(
                            CurrencyFormatter.format(detail.summary.totalCollected),
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                        ],
                      ),
                    ),
                    ElevatedButton.icon(
                      onPressed: () => _showReceptionConfirmationDialog(context, detail),
                      icon: const Icon(LucideIcons.arrowDownToDot, size: 16),
                      label: const Text(
                        'Encaisser au Guichet',
                        style: TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF10B981),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        elevation: 0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ],
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildHeaderSummary(FieldAgentDetailResponse? detail) {
    final phone = detail?.agent.phone;
    final totalCollected = detail?.summary.totalCollected ?? 0.0;
    final opsCount = detail?.summary.operationsCount ?? 0;
    final totalClients = detail?.agent.totalClients ?? 0;

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.2),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [AppColors.primary, Color(0xFF00A892)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Center(
                  child: Text(
                    widget.agentName.isNotEmpty ? widget.agentName.substring(0, 1).toUpperCase() : 'A',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 18),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.agentName,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        const Icon(LucideIcons.phone, size: 12, color: Color(0xFF94A3B8)),
                        const SizedBox(width: 4),
                        Text(
                          phone != null && phone.isNotEmpty ? phone : 'Téléphone non renseigné',
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11.5, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.4)),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(LucideIcons.checkCircle2, size: 11, color: Color(0xFF10B981)),
                    SizedBox(width: 4),
                    Text(
                      'Actif',
                      style: TextStyle(color: Color(0xFF10B981), fontSize: 10.5, fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          const Divider(color: Color(0xFF334155), height: 1),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _headerStatItem(
                label: 'Collecté le jour',
                value: CurrencyFormatter.formatSimple(totalCollected),
                valueColor: const Color(0xFF34D399),
                icon: LucideIcons.wallet,
              ),
              Container(width: 1, height: 28, color: const Color(0xFF334155)),
              _headerStatItem(
                label: 'Opérations',
                value: opsCount.toString(),
                valueColor: Colors.white,
                icon: LucideIcons.receiptText,
              ),
              Container(width: 1, height: 28, color: const Color(0xFF334155)),
              _headerStatItem(
                label: 'Portefeuille Clients',
                value: totalClients.toString(),
                valueColor: const Color(0xFF60A5FA),
                icon: LucideIcons.users,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _headerStatItem({
    required String label,
    required String value,
    required Color valueColor,
    required IconData icon,
  }) {
    return Column(
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 11, color: const Color(0xFF94A3B8)),
            const SizedBox(width: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
            ),
          ],
        ),
        const SizedBox(height: 3),
        Text(
          value,
          style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: valueColor),
        ),
      ],
    );
  }

  Widget _buildDateSelector() {
    final isToday = _period == 'today' && _startDate == null;
    final isYesterday = _period == 'yesterday' && _startDate == null;
    final isWeek = _period == 'week';
    final isMonth = _period == 'month';
    final isCustom = _startDate != null || _period == 'custom' || _period == 'custom_range';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: SingleChildScrollView(
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
              onTap: _selectCustomDateRange,
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
                      isCustom ? _formatDateDisplay() : 'Intervalle...',
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
      ),
    );
  }

  Widget _periodChip(String label, String periodId, bool isSelected) {
    return InkWell(
      onTap: () => _onPeriodChanged(periodId),
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

  Widget _buildTabBar(FieldAgentDetailResponse? detail) {
    final collectionsCount = detail?.collections.length ?? 0;
    final clientsCount = detail?.clients.length ?? 0;

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 4, 16, 8),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F5F9),
        borderRadius: BorderRadius.circular(14),
      ),
      child: TabBar(
        controller: _tabController,
        indicator: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.06),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        labelColor: AppColors.primary,
        unselectedLabelColor: const Color(0xFF64748B),
        labelStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12.5),
        unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12.5),
        padding: const EdgeInsets.all(4),
        indicatorSize: TabBarIndicatorSize.tab,
        dividerColor: Colors.transparent,
        tabs: [
          Tab(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(LucideIcons.receiptText, size: 14),
                const SizedBox(width: 6),
                Text('Collectes ($collectionsCount)'),
              ],
            ),
          ),
          Tab(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(LucideIcons.users, size: 14),
                const SizedBox(width: 6),
                Text('Clients ($clientsCount)'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCollectionsTab(FieldAgentDetailResponse? detail, bool isLoading) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: AppColors.primary));
    }

    final collections = detail?.collections ?? [];

    if (collections.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  shape: BoxShape.circle,
                ),
                child: const Icon(LucideIcons.inbox, size: 36, color: Color(0xFF94A3B8)),
              ),
              const SizedBox(height: 14),
              const Text(
                'Aucune collecte enregistrée à cette date',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
              ),
              const SizedBox(height: 6),
              Text(
                'L\'agent n\'a enregistré aucun encaissement le ${_formatDateDisplay(_selectedDate)}.',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              ),
            ],
          ),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 80),
      itemCount: collections.length,
      itemBuilder: (context, index) {
        final tx = collections[index];
        final isTontine = tx.accountType.toLowerCase().contains('tontine') || tx.type.toLowerCase().contains('tontine');
        final isLoan = tx.type.toLowerCase().contains('loan') || tx.type.toLowerCase().contains('repayment');

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.02),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: isTontine
                      ? const Color(0xFF8B5CF6).withValues(alpha: 0.12)
                      : (isLoan
                          ? const Color(0xFFF59E0B).withValues(alpha: 0.12)
                          : const Color(0xFF10B981).withValues(alpha: 0.12)),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  isTontine
                      ? LucideIcons.coins
                      : (isLoan ? LucideIcons.badgePercent : LucideIcons.arrowDownLeft),
                  size: 18,
                  color: isTontine
                      ? const Color(0xFF8B5CF6)
                      : (isLoan ? const Color(0xFFD97706) : const Color(0xFF10B981)),
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
                            tx.clientName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Color(0xFF0F172A)),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            tx.time,
                            style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: Color(0xFF64748B)),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                          decoration: BoxDecoration(
                            color: isTontine
                                ? const Color(0xFFEDE9FE)
                                : (isLoan ? const Color(0xFFFEF3C7) : const Color(0xFFDCFCE7)),
                            borderRadius: BorderRadius.circular(5),
                          ),
                          child: Text(
                            isTontine ? 'Tontine' : (isLoan ? 'Remb. Prêt' : 'Épargne'),
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              color: isTontine
                                  ? const Color(0xFF6D28D9)
                                  : (isLoan ? const Color(0xFFB45309) : const Color(0xFF15803D)),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          'Cpt: ${tx.accountNumber}',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Text(
                '+ ${CurrencyFormatter.formatSimple(tx.amount)}',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w900,
                  color: Color(0xFF10B981),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildClientsTab(FieldAgentDetailResponse? detail, bool isLoading) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: AppColors.primary));
    }

    final allClients = detail?.clients ?? [];
    final filteredClients = _clientSearchQuery.isEmpty
        ? allClients
        : allClients.where((c) {
            final q = _clientSearchQuery.toLowerCase();
            return c.fullName.toLowerCase().contains(q) ||
                (c.phone != null && c.phone!.contains(q)) ||
                c.clientNumber.toLowerCase().contains(q);
          }).toList();

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
          child: Container(
            height: 42,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: TextField(
              controller: _clientSearchController,
              onChanged: (val) => setState(() => _clientSearchQuery = val),
              style: const TextStyle(fontSize: 12.5),
              decoration: InputDecoration(
                hintText: 'Rechercher parmi les clients de l\'agent...',
                hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                prefixIcon: const Icon(LucideIcons.search, size: 16, color: Color(0xFF94A3B8)),
                suffixIcon: _clientSearchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(LucideIcons.x, size: 14, color: Color(0xFF94A3B8)),
                        onPressed: () {
                          _clientSearchController.clear();
                          setState(() => _clientSearchQuery = '');
                        },
                      )
                    : null,
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 10),
              ),
            ),
          ),
        ),
        Expanded(
          child: filteredClients.isEmpty
              ? Center(
                  child: Text(
                    _clientSearchQuery.isEmpty
                        ? 'Aucun client enregistré par cet agent'
                        : 'Aucun client correspondant à "$_clientSearchQuery"',
                    style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 80),
                  itemCount: filteredClients.length,
                  itemBuilder: (context, index) {
                    final client = filteredClients[index];

                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.02),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 38,
                            height: 38,
                            decoration: BoxDecoration(
                              color: const Color(0xFF0284C7).withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Center(
                              child: Text(
                                client.firstName.isNotEmpty ? client.firstName.substring(0, 1).toUpperCase() : 'C',
                                style: const TextStyle(color: Color(0xFF0284C7), fontWeight: FontWeight.w900, fontSize: 15),
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
                                        client.fullName,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Color(0xFF0F172A)),
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                                      decoration: BoxDecoration(
                                        color: client.kycStatus == 'approved'
                                            ? const Color(0xFFDCFCE7)
                                            : const Color(0xFFFEF3C7),
                                        borderRadius: BorderRadius.circular(5),
                                      ),
                                      child: Text(
                                        client.kycStatus == 'approved' ? 'KYC Validé' : 'KYC En attente',
                                        style: TextStyle(
                                          fontSize: 9.5,
                                          fontWeight: FontWeight.w800,
                                          color: client.kycStatus == 'approved'
                                              ? const Color(0xFF15803D)
                                              : const Color(0xFFB45309),
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
                                      client.phone ?? 'N/A',
                                      style: const TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                                    ),
                                    const SizedBox(width: 10),
                                    Text(
                                      '• ${client.accountsCount} compte(s)',
                                      style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 10),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              const Text('Solde Total', style: TextStyle(fontSize: 9.5, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600)),
                              Text(
                                CurrencyFormatter.formatSimple(client.totalBalance),
                                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  void _showReceptionConfirmationDialog(BuildContext context, FieldAgentDetailResponse detail) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFFDCFCE7),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(LucideIcons.arrowDownToDot, color: Color(0xFF15803D), size: 20),
            ),
            const SizedBox(width: 10),
            const Expanded(
              child: Text(
                'Réception de Collecte',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Confirmez-vous la réception des espèces physiques collectées par l\'agent ${widget.agentName} pour le ${_formatDateDisplay(_selectedDate)} ?',
              style: const TextStyle(fontSize: 13, color: Color(0xFF475569)),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: [
                  _dialogRow('Montant Total à Remettre', CurrencyFormatter.format(detail.summary.totalCollected), isBold: true, color: const Color(0xFF10B981)),
                  const SizedBox(height: 8),
                  _dialogRow('Nombre d\'opérations', '${detail.summary.operationsCount} reçus'),
                  const SizedBox(height: 6),
                  _dialogRow('Part Épargne', CurrencyFormatter.formatSimple(detail.summary.savingsAmount)),
                  const SizedBox(height: 6),
                  _dialogRow('Part Tontine', CurrencyFormatter.formatSimple(detail.summary.tontineAmount)),
                  if (detail.summary.loanRepaymentsAmount > 0) ...[
                    const SizedBox(height: 6),
                    _dialogRow('Remboursements Prêt', CurrencyFormatter.formatSimple(detail.summary.loanRepaymentsAmount)),
                  ],
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Fermer', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.w700)),
          ),
          ElevatedButton.icon(
            onPressed: () {
              Navigator.of(ctx).pop();
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  backgroundColor: const Color(0xFF10B981),
                  content: Text(
                    'Collecte de ${CurrencyFormatter.format(detail.summary.totalCollected)} pointée avec succès pour ${widget.agentName}.',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
              );
            },
            icon: const Icon(LucideIcons.check, size: 15),
            label: const Text('Valider le Pointage', style: TextStyle(fontWeight: FontWeight.w800)),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _dialogRow(String label, String value, {bool isBold = false, Color? color}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
        Text(
          value,
          style: TextStyle(
            fontSize: isBold ? 14 : 12,
            fontWeight: isBold ? FontWeight.w900 : FontWeight.w700,
            color: color ?? const Color(0xFF1E293B),
          ),
        ),
      ],
    );
  }
}
