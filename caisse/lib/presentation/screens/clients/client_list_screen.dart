import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../data/models/client_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../operations/deposit_screen.dart';
import '../operations/withdrawal_screen.dart';

class ClientListScreen extends ConsumerStatefulWidget {
  const ClientListScreen({super.key});

  @override
  ConsumerState<ClientListScreen> createState() => _ClientListScreenState();
}

class _ClientListScreenState extends ConsumerState<ClientListScreen> {
  final _searchController = TextEditingController();
  List<ClientModel> _results = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _performSearch('');
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _performSearch(String query) async {
    setState(() => _isLoading = true);
    final cashier = ref.read(cashierNotifierProvider.notifier);
    final list = await cashier.searchClients(query);
    setState(() {
      _results = list;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.backgroundDark,
      appBar: AppBar(
        title: const Text('Répertoire des Clients'),
      ),
      body: Column(
        children: [
          // Search Input
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
            color: AppColors.surfaceDark,
            child: TextField(
              controller: _searchController,
              style: const TextStyle(color: Colors.white, fontSize: 14),
              decoration: InputDecoration(
                hintText: 'Rechercher par nom, téléphone...',
                hintStyle: const TextStyle(color: AppColors.textMutedDark, fontSize: 13),
                prefixIcon: const Icon(LucideIcons.search, color: AppColors.textSecondaryDark, size: 18),
                filled: true,
                fillColor: AppColors.surfaceLightDark,
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              ),
              onChanged: (val) => _performSearch(val.trim()),
            ),
          ),

          // Client List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                : _results.isEmpty
                    ? const Center(
                        child: Text(
                          'Aucun client trouvé.',
                          style: TextStyle(color: AppColors.textSecondaryDark, fontSize: 14),
                        ),
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.all(20),
                        itemCount: _results.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (ctx, index) {
                          final client = _results[index];
                          return Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: AppColors.surfaceDark,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: const Color(0x1A00D1B2)),
                            ),
                            child: Row(
                              children: [
                                CircleAvatar(
                                  radius: 22,
                                  backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                                  child: Text(
                                    client.firstName.isNotEmpty ? client.firstName[0].toUpperCase() : 'C',
                                    style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 16),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        client.fullName,
                                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: Colors.white),
                                      ),
                                      const SizedBox(height: 3),
                                      Text(
                                        client.phone ?? 'Sans téléphone',
                                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondaryDark),
                                      ),
                                    ],
                                  ),
                                ),
                                IconButton(
                                  icon: const Icon(LucideIcons.arrowDownCircle, color: AppColors.success, size: 22),
                                  tooltip: 'Dépôt',
                                  onPressed: () async {
                                    await Navigator.of(context).push(
                                      MaterialPageRoute(builder: (_) => const DepositScreen()),
                                    );
                                    if (mounted) {
                                      ref.read(cashierNotifierProvider.notifier).fetchClients();
                                    }
                                  },
                                ),
                                IconButton(
                                  icon: const Icon(LucideIcons.arrowUpCircle, color: AppColors.danger, size: 22),
                                  tooltip: 'Retrait',
                                  onPressed: () async {
                                    await Navigator.of(context).push(
                                      MaterialPageRoute(builder: (_) => const WithdrawalScreen()),
                                    );
                                    if (mounted) {
                                      ref.read(cashierNotifierProvider.notifier).fetchClients();
                                    }
                                  },
                                ),
                              ],
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}
