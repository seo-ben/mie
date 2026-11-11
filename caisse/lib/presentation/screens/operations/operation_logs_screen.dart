import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../receipts/receipt_screen.dart';

class OperationLogsScreen extends ConsumerStatefulWidget {
  const OperationLogsScreen({super.key});

  @override
  ConsumerState<OperationLogsScreen> createState() => _OperationLogsScreenState();
}

class _OperationLogsScreenState extends ConsumerState<OperationLogsScreen> {
  final _searchController = TextEditingController();
  DateTime? _dateStart;
  DateTime? _dateEnd;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadOperations();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadOperations() async {
    setState(() => _isLoading = true);
    await ref.read(cashierNotifierProvider.notifier).fetchOperations(
      search: _searchController.text,
      dateStart: _dateStart,
      dateEnd: _dateEnd,
    );
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _pickDate(bool start) async {
    final selected = await showDatePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDate: start ? (_dateStart ?? DateTime.now()) : (_dateEnd ?? DateTime.now()),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.light(primary: AppColors.primary),
        ),
        child: child!,
      ),
    );
    if (selected == null) return;
    setState(() {
      if (start) {
        _dateStart = selected;
      } else {
        _dateEnd = selected;
      }
    });
    await _loadOperations();
  }

  String _dateLabel(DateTime? date) {
    if (date == null) return 'Toutes les dates';
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    final cashier = ref.watch(cashierNotifierProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FB),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        foregroundColor: const Color(0xFF1D2A3A),
        elevation: 0,
        title: const Text('Journal des Opérations'),
        actions: [
          IconButton(onPressed: _loadOperations, icon: const Icon(LucideIcons.refreshCw, size: 19)),
        ],
      ),
      body: Column(
        children: [
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _searchController,
                        textInputAction: TextInputAction.search,
                        onSubmitted: (_) => _loadOperations(),
                        decoration: InputDecoration(
                          hintText: 'Rechercher par nom du client',
                          prefixIcon: const Icon(LucideIcons.search, size: 19),
                          suffixIcon: IconButton(
                            onPressed: _loadOperations,
                            icon: const Icon(LucideIcons.arrowRight, size: 18),
                          ),
                          filled: true,
                          fillColor: const Color(0xFFF5F7FB),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _pickDate(true),
                        icon: const Icon(LucideIcons.calendarDays, size: 16),
                        label: Text(_dateLabel(_dateStart), overflow: TextOverflow.ellipsis),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _pickDate(false),
                        icon: const Icon(LucideIcons.calendarDays, size: 16),
                        label: Text(_dateLabel(_dateEnd), overflow: TextOverflow.ellipsis),
                      ),
                    ),
                    if (_dateStart != null || _dateEnd != null)
                      IconButton(
                        onPressed: () {
                          setState(() {
                            _dateStart = null;
                            _dateEnd = null;
                          });
                          _loadOperations();
                        },
                        icon: const Icon(LucideIcons.x, size: 18),
                      ),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _loadOperations,
              color: AppColors.primary,
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                  : cashier.operations.isEmpty
                      ? ListView(
                          children: const [
                            SizedBox(height: 140),
                            Center(
                              child: Text(
                                'Aucune opération trouvée.',
                                style: TextStyle(color: Color(0xFF72809A), fontSize: 14),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.all(16),
                          itemCount: cashier.operations.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (ctx, index) {
                            final tx = cashier.operations[index];
                            return InkWell(
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => ReceiptScreen(transaction: tx)),
                                );
                              },
                              borderRadius: BorderRadius.circular(16),
                              child: Container(
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(color: const Color(0x1A00D1B2)),
                                ),
                                child: Row(
                                  children: [
                                    Container(
                                      width: 44,
                                      height: 44,
                                      decoration: BoxDecoration(
                                        color: (tx.isDeposit ? AppColors.success : AppColors.danger)
                                            .withValues(alpha: 0.12),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Icon(
                                        tx.isDeposit ? LucideIcons.arrowDownLeft : LucideIcons.arrowUpRight,
                                        color: tx.isDeposit ? AppColors.success : AppColors.danger,
                                        size: 20,
                                      ),
                                    ),
                                    const SizedBox(width: 14),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            tx.clientName ?? 'Client Guichet',
                                            style: const TextStyle(
                                              fontSize: 15,
                                              fontWeight: FontWeight.w700,
                                              color: Color(0xFF1D2A3A),
                                            ),
                                          ),
                                          const SizedBox(height: 3),
                                          Text(
                                            '${tx.reference} • ${CurrencyFormatter.formatDate(tx.createdAt)}',
                                            style: const TextStyle(fontSize: 11, color: Color(0xFF8A93A6)),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Column(
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        Text(
                                          '${tx.isDeposit ? '+' : '-'}${CurrencyFormatter.formatSimple(tx.amount)}',
                                          style: TextStyle(
                                            fontSize: 15,
                                            fontWeight: FontWeight.w800,
                                            color: tx.isDeposit ? AppColors.success : AppColors.danger,
                                          ),
                                        ),
                                        const SizedBox(height: 3),
                                        Text(
                                          tx.status == 'completed' ? 'Validé' : tx.status,
                                          style: const TextStyle(
                                            fontSize: 10,
                                            color: AppColors.success,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }
}
