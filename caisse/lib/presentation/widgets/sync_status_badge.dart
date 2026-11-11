import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:intl/intl.dart';
import '../../core/services/sync_queue_service.dart';
import '../../domain/notifiers/cashier_notifier.dart';
import '../../domain/notifiers/sync_notifier.dart';
import '../../core/providers/repository_providers.dart';

class SyncStatusBadge extends ConsumerWidget {
  final bool showIfOnline;

  const SyncStatusBadge({
    super.key,
    this.showIfOnline = false,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final syncState = ref.watch(syncNotifierProvider);

    // 1. En cours de synchronisation
    if (syncState.isSyncing) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(
          color: const Color(0xFF6366F1).withOpacity(0.12),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFF6366F1).withOpacity(0.3)),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              width: 12,
              height: 12,
              child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF6366F1)),
            ),
            SizedBox(width: 6),
            Text(
              'Synchro...',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: Color(0xFF4F46E5),
              ),
            ),
          ],
        ),
      );
    }

    // 2. Opérations en attente dans la file locale
    if (syncState.pendingCount > 0) {
      return InkWell(
        onTap: () => _showPendingOperationsSheet(context, ref),
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
          decoration: BoxDecoration(
            color: const Color(0xFFF59E0B).withOpacity(0.15),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: const Color(0xFFF59E0B).withOpacity(0.4)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(LucideIcons.cloudOff, size: 13, color: Color(0xFFD97706)),
              const SizedBox(width: 5),
              Text(
                '${syncState.pendingCount} en attente',
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFFB45309),
                ),
              ),
              const SizedBox(width: 4),
              const Icon(LucideIcons.refreshCw, size: 10, color: Color(0xFFD97706)),
            ],
          ),
        ),
      );
    }

    // 3. Déconnecté (Hors-Ligne) sans opération en attente
    if (!syncState.isOnline) {
      return InkWell(
        onTap: () async {
          ScaffoldMessenger.of(context).hideCurrentSnackBar();
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Test de la connexion au serveur...'),
              duration: Duration(seconds: 1),
            ),
          );
          final isOnline = await ref.read(syncNotifierProvider.notifier).checkConnectivity();
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(isOnline ? '✅ Connexion rétablie !' : '⚠️ Aucune connexion internet active.'),
                backgroundColor: isOnline ? const Color(0xFF059669) : const Color(0xFFEF4444),
                duration: const Duration(seconds: 2),
              ),
            );
          }
        },
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          decoration: BoxDecoration(
            color: const Color(0xFFEF4444).withOpacity(0.12),
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFFEF4444).withOpacity(0.3)),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(LucideIcons.wifiOff, size: 12, color: Color(0xFFDC2626)),
              SizedBox(width: 4),
              Text(
                'Hors-Ligne',
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFFDC2626),
                ),
              ),
            ],
          ),
        ),
      );
    }

    // 4. En Ligne
    if (!showIfOnline) return const SizedBox.shrink();

    return InkWell(
      onTap: () async {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Vérification de la liaison serveur...'),
            duration: Duration(seconds: 1),
          ),
        );
        final online = await ref.read(syncNotifierProvider.notifier).checkConnectivity();
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(online ? '✅ Connexion active - Système synchronisé' : '⚠️ Aucune connexion internet.'),
              backgroundColor: online ? const Color(0xFF059669) : const Color(0xFFEF4444),
              duration: const Duration(seconds: 2),
            ),
          );
        }
      },
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: const Color(0xFF10B981).withOpacity(0.12),
          borderRadius: BorderRadius.circular(8),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(LucideIcons.cloudCheck, size: 12, color: Color(0xFF059669)),
            SizedBox(width: 4),
            Text(
              'En Ligne',
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w700,
                color: Color(0xFF059669),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showPendingOperationsSheet(BuildContext context, WidgetRef ref) {
    final currencyFmt = NumberFormat('#,##0', 'fr_FR');
    final syncState = ref.read(syncNotifierProvider);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return FutureBuilder<List<Map<String, dynamic>>>(
          future: SyncQueueService.getPendingTransactions(),
          builder: (context, snapshot) {
            final items = snapshot.data ?? [];
            return Container(
              height: MediaQuery.of(context).size.height * 0.75,
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  // Handle bar
                  Container(
                    margin: const EdgeInsets.only(top: 12, bottom: 8),
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),

                  // Header
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF59E0B).withOpacity(0.15),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(LucideIcons.cloudOff, color: Color(0xFFD97706), size: 22),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Opérations en attente',
                                style: TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFF1E293B),
                                ),
                              ),
                              Text(
                                '${items.length} opération(s) enregistrée(s) en local',
                                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, color: Colors.grey),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),

                  // Info / Error banner
                  if (syncState.errorMessage != null && syncState.errorMessage!.isNotEmpty)
                    Container(
                      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      constraints: const BoxConstraints(maxHeight: 140),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF2F2),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFFECACA)),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(LucideIcons.alertTriangle, color: Color(0xFFDC2626), size: 18),
                          const SizedBox(width: 10),
                          Expanded(
                            child: SingleChildScrollView(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Dernier retour du serveur :',
                                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF991B1B)),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    syncState.errorMessage!,
                                    style: const TextStyle(fontSize: 11, color: Color(0xFFB91C1C), height: 1.25),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    )
                  else
                    Container(
                      margin: const EdgeInsets.all(16),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFBFDBFE)),
                      ),
                      child: const Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(LucideIcons.shieldCheck, color: Color(0xFF2563EB), size: 18),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Vos données sont sécurisées sur cet appareil. Dès que la connexion est disponible, elles sont automatiquement envoyées au serveur sans doublon.',
                              style: TextStyle(fontSize: 12, color: Color(0xFF1E40AF), height: 1.3),
                            ),
                          ),
                        ],
                      ),
                    ),

                  // List of items
                  Expanded(
                    child: items.isEmpty
                        ? const Center(
                            child: Text(
                              'Aucune opération en attente',
                              style: TextStyle(color: Colors.grey, fontSize: 14),
                            ),
                          )
                        : ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            itemCount: items.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, index) {
                              final item = items[index];
                              final action = item['action']?.toString() ?? item['transaction_type']?.toString() ?? 'Opération';
                              final amount = double.tryParse(item['amount']?.toString() ?? '0') ?? 0.0;
                              final offlineId = item['offline_id']?.toString() ?? item['id']?.toString() ?? 'N/A';
                              final dateStr = item['timestamp']?.toString() ?? item['created_at']?.toString();
                              final date = dateStr != null ? DateTime.tryParse(dateStr) : null;
                              final dateFormatted = date != null ? DateFormat('dd/MM HH:mm').format(date) : '';

                              IconData iconData;
                              Color iconBg;
                              Color iconColor;
                              String title;
                              String subtitle;

                              if (action == 'create_client' || offlineId.startsWith('CLIENT-')) {
                                iconData = LucideIcons.userPlus;
                                iconBg = const Color(0xFFDBEAFE);
                                iconColor = const Color(0xFF1D4ED8);
                                title = 'Nouveau Client: ${item['first_name'] ?? ''} ${item['last_name'] ?? ''}';
                                subtitle = 'Tél: ${item['phone'] ?? 'N/A'} • Réf: $offlineId';
                              } else if (action == 'deposit' || action == 'credit') {
                                iconData = LucideIcons.arrowDownLeft;
                                iconBg = const Color(0xFFD1FAE5);
                                iconColor = const Color(0xFF059669);
                                title = 'Dépôt (${currencyFmt.format(amount)} FCFA)';
                                subtitle = 'Compte: ${item['account_number'] ?? item['account_id'] ?? 'N/A'} • $dateFormatted';
                              } else if (action == 'withdrawal' || action == 'debit') {
                                iconData = LucideIcons.arrowUpRight;
                                iconBg = const Color(0xFFFEE2E2);
                                iconColor = const Color(0xFFDC2626);
                                title = 'Retrait (${currencyFmt.format(amount)} FCFA)';
                                subtitle = 'Compte: ${item['account_number'] ?? item['account_id'] ?? 'N/A'} • $dateFormatted';
                              } else {
                                iconData = LucideIcons.activity;
                                iconBg = const Color(0xFFF3F4F6);
                                iconColor = const Color(0xFF4B5563);
                                title = '$action (${currencyFmt.format(amount)} FCFA)';
                                subtitle = 'Réf: $offlineId • $dateFormatted';
                              }

                              return Padding(
                                padding: const EdgeInsets.symmetric(vertical: 10),
                                child: Row(
                                  children: [
                                    Container(
                                      width: 40,
                                      height: 40,
                                      decoration: BoxDecoration(
                                        color: iconBg,
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: Icon(iconData, color: iconColor, size: 20),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            title,
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w600,
                                              fontSize: 13,
                                              color: Color(0xFF1E293B),
                                            ),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            subtitle,
                                            style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFFEF3C7),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: const Text(
                                        'En attente',
                                        style: TextStyle(
                                          fontSize: 10,
                                          fontWeight: FontWeight.w700,
                                          color: Color(0xFFB45309),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                  ),

                  // Action buttons
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Column(
                      children: [
                        SizedBox(
                          width: double.infinity,
                          height: 48,
                          child: ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF4F46E5),
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            icon: const Icon(LucideIcons.refreshCw, size: 18),
                            label: const Text(
                              'Synchroniser maintenant',
                              style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
                            ),
                            onPressed: () async {
                              Navigator.pop(ctx);
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Envoi des opérations en cours...'),
                                  duration: Duration(seconds: 2),
                                ),
                              );
                              final ok = await ref.read(syncNotifierProvider.notifier).syncNow();
                              if (context.mounted) {
                                final currentSync = ref.read(syncNotifierProvider);
                                if (ok) {
                                  await ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                                  await ref.read(cashierNotifierProvider.notifier).fetchOperations();
                                  await ref.read(cashierNotifierProvider.notifier).fetchClients();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text('✅ Synchronisation réussie ! Vos données sont en ligne.'),
                                      backgroundColor: Color(0xFF059669),
                                      duration: Duration(seconds: 3),
                                    ),
                                  );
                                } else {
                                  final errText = currentSync.errorMessage?.isNotEmpty == true
                                      ? currentSync.errorMessage!
                                      : 'Impossible de joindre le serveur distant.';
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text('⚠️ $errText'),
                                      backgroundColor: const Color(0xFFDC2626),
                                      duration: const Duration(seconds: 5),
                                    ),
                                  );
                                }
                              }
                            },
                          ),
                        ),
                        const SizedBox(height: 6),
                        TextButton.icon(
                          onPressed: () async {
                            final confirm = await showDialog<bool>(
                              context: context,
                              builder: (dCtx) => AlertDialog(
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                title: const Text('Vider la file locale ?'),
                                content: const Text(
                                  'Si ces opérations ont déjà été enregistrées sur le serveur ou si vous souhaitez réinitialiser l\'indicateur, confirmez la suppression.',
                                ),
                                actions: [
                                  TextButton(
                                    onPressed: () => Navigator.pop(dCtx, false),
                                    child: const Text('Annuler'),
                                  ),
                                  ElevatedButton(
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: const Color(0xFFDC2626),
                                      foregroundColor: Colors.white,
                                    ),
                                    onPressed: () => Navigator.pop(dCtx, true),
                                    child: const Text('Vider la file'),
                                  ),
                                ],
                              ),
                            );
                            if (confirm == true) {
                              Navigator.pop(ctx);
                              await ref.read(syncNotifierProvider.notifier).clearQueue();
                              if (context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('✅ File locale réinitialisée.'),
                                    backgroundColor: Color(0xFF059669),
                                    duration: Duration(seconds: 2),
                                  ),
                                );
                              }
                            }
                          },
                          icon: const Icon(LucideIcons.trash2, size: 14, color: Color(0xFF94A3B8)),
                          label: const Text(
                            'Vider la file d\'attente locale',
                            style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                          ),
                        ),
                      ],
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
}
