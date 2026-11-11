import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/client_model.dart';
import '../../../data/models/account_model.dart';
import '../../../data/models/transaction_model.dart';
import '../../../data/models/loan_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../../../domain/notifiers/loan_notifier.dart';
import '../operations/deposit_screen.dart';
import '../operations/transfer_screen.dart';
import '../loans/loan_application_screen.dart';
import '../loans/loan_detail_screen.dart';

class ClientDetailScreen extends ConsumerStatefulWidget {
  final ClientModel client;

  const ClientDetailScreen({super.key, required this.client});

  @override
  ConsumerState<ClientDetailScreen> createState() => _ClientDetailScreenState();
}

class _ClientDetailScreenState extends ConsumerState<ClientDetailScreen> {
  bool _isApprovingKyc = false;
  bool _isCreatingAccount = false;
  bool _isUpdatingClient = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(loanNotifierProvider.notifier).fetchLoans();
      ref.read(cashierNotifierProvider.notifier).fetchClients();
    });
  }

  ClientModel _getClient(CashierState cashier) {
    try {
      return cashier.clients.firstWhere((c) => c.id == widget.client.id);
    } catch (_) {
      return widget.client;
    }
  }

  List<Map> _getAccounts(ClientModel client) {
    return (client.accounts ?? []).whereType<Map>().toList();
  }

  List<LoanModel> _getLoans(ClientModel client, List<LoanModel> globalLoans) {
    final Map<int, LoanModel> loanMap = {};

    // 1. Embedded client loans from API
    if (client.loans != null) {
      for (final l in client.loans!) {
        if (l is LoanModel) {
          loanMap[l.id] = l;
        } else if (l is Map) {
          final model = LoanModel.fromJson(Map<String, dynamic>.from(l));
          loanMap[model.id] = model;
        }
      }
    }

    // 2. Global loans matching clientId
    for (final l in globalLoans) {
      if (l.clientId == client.id) {
        loanMap[l.id] = l;
      }
    }

    final list = loanMap.values.toList();
    list.sort((a, b) => b.id.compareTo(a.id));
    return list;
  }

  double _totalFor(List<Map> accounts, String type) {
    return accounts.where((account) => account['account_type'] == type).fold<double>(
      0,
      (total, account) => total + (double.tryParse(account['balance']?.toString() ?? '0') ?? 0),
    );
  }

  Future<void> _approveKyc() async {
    setState(() => _isApprovingKyc = true);
    final success = await ref.read(cashierNotifierProvider.notifier).approveClientKyc(widget.client.id);
    if (!mounted) return;
    setState(() => _isApprovingKyc = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(success ? 'KYC activé pour les demandes de prêt.' : 'Impossible d’activer le KYC.'),
        backgroundColor: success ? const Color(0xFF10B981) : const Color(0xFFEF4444),
      ),
    );
  }

  void _openEditClientSheet(ClientModel client) {
    final firstNameController = TextEditingController(text: client.firstName);
    final lastNameController = TextEditingController(text: client.lastName);
    final phoneController = TextEditingController(text: client.phone ?? '');
    final addressController = TextEditingController(text: client.address ?? '');
    final professionController = TextEditingController(text: client.profession ?? '');

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.primary.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(LucideIcons.userPen, color: AppColors.primary, size: 20),
                            ),
                            const SizedBox(width: 12),
                            const Text(
                              'Modifier le client',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                          ],
                        ),
                        IconButton(
                          onPressed: () => Navigator.of(context).pop(),
                          icon: const Icon(LucideIcons.x, size: 20, color: Color(0xFF94A3B8)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Mettre à jour les coordonnées de ${client.fullName}',
                      style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                    ),
                    const SizedBox(height: 18),

                    // Nom & Prénoms
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('PRÉNOM', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B))),
                              const SizedBox(height: 6),
                              TextField(
                                controller: firstNameController,
                                decoration: InputDecoration(
                                  filled: true,
                                  fillColor: const Color(0xFFF8FAFC),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('NOM', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B))),
                              const SizedBox(height: 6),
                              TextField(
                                controller: lastNameController,
                                decoration: InputDecoration(
                                  filled: true,
                                  fillColor: const Color(0xFFF8FAFC),
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),

                    // Téléphone
                    const Text('TÉLÉPHONE', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: phoneController,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        prefixIcon: const Icon(LucideIcons.phone, size: 18, color: Color(0xFF64748B)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Adresse
                    const Text('ADRESSE / QUARTIER', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: addressController,
                      decoration: InputDecoration(
                        prefixIcon: const Icon(LucideIcons.mapPin, size: 18, color: Color(0xFF64748B)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Profession
                    const Text('PROFESSION / ACTIVITÉ', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B))),
                    const SizedBox(height: 6),
                    TextField(
                      controller: professionController,
                      decoration: InputDecoration(
                        prefixIcon: const Icon(LucideIcons.briefcase, size: 18, color: Color(0xFF64748B)),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                      ),
                    ),
                    const SizedBox(height: 24),

                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        onPressed: _isUpdatingClient
                            ? null
                            : () async {
                                final fn = firstNameController.text.trim();
                                final ln = lastNameController.text.trim();
                                final ph = phoneController.text.trim();

                                if (fn.isEmpty || ln.isEmpty || ph.isEmpty) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text('Le prénom, le nom et le téléphone sont requis.'),
                                      backgroundColor: Color(0xFFEF4444),
                                    ),
                                  );
                                  return;
                                }

                                Navigator.of(context).pop();
                                setState(() => _isUpdatingClient = true);

                                final res = await ref.read(cashierNotifierProvider.notifier).updateClient(
                                  clientId: client.id,
                                  firstName: fn,
                                  lastName: ln,
                                  phone: ph,
                                  address: addressController.text.trim(),
                                  profession: professionController.text.trim(),
                                );

                                if (!mounted) return;
                                setState(() => _isUpdatingClient = false);

                                if (res != null) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text('Données du client mises à jour avec succès.'),
                                      backgroundColor: Color(0xFF10B981),
                                    ),
                                  );
                                } else {
                                  final err = ref.read(cashierNotifierProvider).errorMessage;
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(err ?? 'Erreur lors de la mise à jour.'),
                                      backgroundColor: const Color(0xFFEF4444),
                                    ),
                                  );
                                }
                              },
                        child: _isUpdatingClient
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('Enregistrer les modifications', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _openCreateAccountSheet(ClientModel currentClient) {
    final accounts = _getAccounts(currentClient);
    final hasSavings = accounts.any((a) => a['account_type'] == 'savings' || a['account_type'] == 'epargne');

    String selectedType = hasSavings ? 'tontine' : 'savings';
    String selectedFrequency = 'daily';
    int selectedDuration = 12;
    final amountController = TextEditingController(text: '500');

    final quickAmounts = [100, 200, 300, 500, 1000, 2000, 5000];

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.primary.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(LucideIcons.walletCards, color: AppColors.primary, size: 20),
                            ),
                            const SizedBox(width: 12),
                            const Text(
                              'Nouveau compte',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                          ],
                        ),
                        IconButton(
                          onPressed: () => Navigator.of(context).pop(),
                          icon: const Icon(LucideIcons.x, size: 20, color: Color(0xFF94A3B8)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Création de compte pour ${currentClient.fullName}',
                      style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                    ),
                    const SizedBox(height: 18),
                    const Text(
                      'TYPE DE COMPTE',
                      style: TextStyle(
                        fontSize: 10.5,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF64748B),
                        letterSpacing: 0.8,
                      ),
                    ),
                    const SizedBox(height: 8),

                    if (hasSavings)
                      Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: const Color(0xFFFDE68A)),
                        ),
                        child: const Row(
                          children: [
                            Icon(LucideIcons.info, color: Color(0xFFD97706), size: 16),
                            SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Le client a déjà un compte épargne. Seuls des comptes tontine additionnels peuvent être créés.',
                                style: TextStyle(color: Color(0xFF92400E), fontSize: 11, fontWeight: FontWeight.w600),
                              ),
                            ),
                          ],
                        ),
                      ),

                    Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: hasSavings
                                ? () {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text('Un seul compte épargne est autorisé par client.'),
                                        backgroundColor: Color(0xFFD97706),
                                      ),
                                    );
                                  }
                                : () => setModalState(() => selectedType = 'savings'),
                            borderRadius: BorderRadius.circular(14),
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 200),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: hasSavings
                                    ? const Color(0xFFF1F5F9)
                                    : (selectedType == 'savings' ? AppColors.primary.withValues(alpha: 0.1) : const Color(0xFFF8FAFC)),
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(
                                  color: hasSavings
                                      ? const Color(0xFFE2E8F0)
                                      : (selectedType == 'savings' ? AppColors.primary : const Color(0xFFE2E8F0)),
                                  width: selectedType == 'savings' && !hasSavings ? 1.5 : 1,
                                ),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Icon(
                                        LucideIcons.piggyBank,
                                        size: 20,
                                        color: hasSavings ? const Color(0xFF94A3B8) : (selectedType == 'savings' ? AppColors.primaryDark : const Color(0xFF64748B)),
                                      ),
                                      if (hasSavings)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFE2E8F0),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: const Text('Existant', style: TextStyle(fontSize: 9, color: Color(0xFF64748B), fontWeight: FontWeight.w700)),
                                        ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    'Compte Épargne',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                      fontSize: 13,
                                      color: hasSavings ? const Color(0xFF94A3B8) : (selectedType == 'savings' ? AppColors.primaryDark : const Color(0xFF0F172A)),
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    hasSavings ? '1 seul compte autorisé' : 'Dépôts et retraits libres',
                                    style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: InkWell(
                            onTap: () => setModalState(() => selectedType = 'tontine'),
                            borderRadius: BorderRadius.circular(14),
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 200),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: selectedType == 'tontine' ? const Color(0xFF6366F1).withValues(alpha: 0.1) : const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(
                                  color: selectedType == 'tontine' ? const Color(0xFF6366F1) : const Color(0xFFE2E8F0),
                                  width: selectedType == 'tontine' ? 1.5 : 1,
                                ),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Icon(
                                        LucideIcons.repeat2,
                                        size: 20,
                                        color: selectedType == 'tontine' ? const Color(0xFF6366F1) : const Color(0xFF64748B),
                                      ),
                                      if (hasSavings)
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFEEF2FF),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: const Text('Recommandé', style: TextStyle(fontSize: 9, color: Color(0xFF4F46E5), fontWeight: FontWeight.w700)),
                                        ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    'Compte Tontine',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                      fontSize: 13,
                                      color: selectedType == 'tontine' ? const Color(0xFF4F46E5) : const Color(0xFF0F172A),
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  const Text(
                                    'Cotisations cycliques',
                                    style: TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (selectedType == 'tontine') ...[
                      const SizedBox(height: 16),
                      const Text(
                        'MISE PÉRIODIQUE (FCFA)',
                        style: TextStyle(
                          fontSize: 10.5,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF64748B),
                          letterSpacing: 0.8,
                        ),
                      ),
                      const SizedBox(height: 8),

                      // Quick select chips
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: Row(
                          children: quickAmounts.map((amt) {
                            final isSel = amountController.text == amt.toString();
                            return Padding(
                              padding: const EdgeInsets.only(right: 6, bottom: 8),
                              child: ChoiceChip(
                                label: Text('$amt F', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: isSel ? Colors.white : const Color(0xFF0F172A))),
                                selected: isSel,
                                selectedColor: const Color(0xFF4F46E5),
                                backgroundColor: const Color(0xFFF1F5F9),
                                onSelected: (s) {
                                  if (s) {
                                    setModalState(() => amountController.text = amt.toString());
                                  }
                                },
                              ),
                            );
                          }).toList(),
                        ),
                      ),

                      TextField(
                        controller: amountController,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          hintText: 'Ex: 100, 200, 500, 1000...',
                          prefixIcon: const Icon(LucideIcons.coins, size: 18, color: Color(0xFF64748B)),
                          suffixText: 'FCFA',
                          filled: true,
                          fillColor: const Color(0xFFF8FAFC),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFF4F46E5), width: 1.5),
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'FRÉQUENCE',
                                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
                                ),
                                const SizedBox(height: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF8FAFC),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: const Color(0xFFE2E8F0)),
                                  ),
                                  child: DropdownButtonHideUnderline(
                                    child: DropdownButton<String>(
                                      value: selectedFrequency,
                                      isExpanded: true,
                                      items: const [
                                        DropdownMenuItem(value: 'daily', child: Text('Journalier', style: TextStyle(fontSize: 12))),
                                        DropdownMenuItem(value: 'weekly', child: Text('Hebdomadaire', style: TextStyle(fontSize: 12))),
                                        DropdownMenuItem(value: 'monthly', child: Text('Mensuel', style: TextStyle(fontSize: 12))),
                                      ],
                                      onChanged: (v) => setModalState(() => selectedFrequency = v ?? 'daily'),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'DURÉE DU CYCLE',
                                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
                                ),
                                const SizedBox(height: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF8FAFC),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: const Color(0xFFE2E8F0)),
                                  ),
                                  child: DropdownButtonHideUnderline(
                                    child: DropdownButton<int>(
                                      value: selectedDuration,
                                      isExpanded: true,
                                      items: const [
                                        DropdownMenuItem(value: 1, child: Text('1 Mois', style: TextStyle(fontSize: 12))),
                                        DropdownMenuItem(value: 3, child: Text('3 Mois', style: TextStyle(fontSize: 12))),
                                        DropdownMenuItem(value: 6, child: Text('6 Mois', style: TextStyle(fontSize: 12))),
                                        DropdownMenuItem(value: 12, child: Text('12 Mois', style: TextStyle(fontSize: 12))),
                                        DropdownMenuItem(value: 24, child: Text('24 Mois', style: TextStyle(fontSize: 12))),
                                      ],
                                      onChanged: (v) => setModalState(() => selectedDuration = v ?? 12),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: selectedType == 'tontine' ? const Color(0xFF4F46E5) : AppColors.primary,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        ),
                        onPressed: _isCreatingAccount
                            ? null
                            : () async {
                                final target = double.tryParse(amountController.text.trim()) ?? 100;
                                Navigator.of(context).pop();
                                setState(() => _isCreatingAccount = true);

                                final account = await ref.read(cashierNotifierProvider.notifier).createAccount(
                                  clientId: currentClient.id,
                                  accountType: selectedType,
                                  targetAmount: target,
                                  cycleDurationMonths: selectedDuration,
                                  paymentFrequency: selectedFrequency,
                                );

                                if (!mounted) return;
                                setState(() => _isCreatingAccount = false);

                                if (account != null) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text('Compte ${account.typeLabel} créé avec succès (${account.accountNumber}).'),
                                      backgroundColor: const Color(0xFF10B981),
                                    ),
                                  );
                                } else {
                                  final err = ref.read(cashierNotifierProvider).errorMessage;
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(err ?? 'Impossible de créer le compte.'),
                                      backgroundColor: const Color(0xFFEF4444),
                                    ),
                                  );
                                }
                              },
                        child: _isCreatingAccount
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text('Valider & Créer le compte', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final cashier = ref.watch(cashierNotifierProvider);
    final loanState = ref.watch(loanNotifierProvider);
    final client = _getClient(cashier);
    final accounts = _getAccounts(client);
    final loans = _getLoans(client, loanState.loans);
    final kycApproved = client.kycStatus == 'approved';

    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        foregroundColor: const Color(0xFF0F172A),
        elevation: 0,
        centerTitle: true,
        title: const Text(
          'Détail du client',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            tooltip: 'Demande de Prêt',
            onPressed: () async {
              await Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => LoanApplicationScreen(preselectedClient: client),
                ),
              );
              if (context.mounted) {
                ref.read(loanNotifierProvider.notifier).fetchLoans();
                ref.read(cashierNotifierProvider.notifier).fetchClients();
              }
            },
            icon: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: const Color(0xFF6366F1).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(LucideIcons.badgePercent, color: Color(0xFF6366F1), size: 18),
            ),
          ),
          IconButton(
            tooltip: 'Nouveau compte',
            onPressed: () => _openCreateAccountSheet(client),
            icon: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(LucideIcons.plus, color: AppColors.primaryDark, size: 18),
            ),
          ),
          const SizedBox(width: 8),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: const Color(0xFFE2E8F0), height: 1),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Profile Card
          Container(
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
            child: Row(
              children: [
                Container(
                  width: 54,
                  height: 54,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFFE0F2FE), Color(0xFFBAE6FD)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Center(
                    child: Text(
                      client.firstName.isNotEmpty ? client.firstName[0].toUpperCase() : 'C',
                      style: const TextStyle(
                        color: Color(0xFF0369A1),
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        client.fullName,
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          const Icon(LucideIcons.phone, size: 12, color: Color(0xFF94A3B8)),
                          const SizedBox(width: 4),
                          Text(
                            client.phone ?? 'Téléphone non renseigné',
                            style: const TextStyle(color: Color(0xFF64748B), fontSize: 12, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                InkWell(
                  onTap: () => _openEditClientSheet(client),
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.primary.withValues(alpha: 0.3)),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(LucideIcons.userPen, size: 14, color: AppColors.primaryDark),
                        SizedBox(width: 4),
                        Text(
                          'Modifier',
                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primaryDark),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Total balances
          Row(
            children: [
              Expanded(
                child: _totalCard(
                  'Total épargne',
                  _totalFor(accounts, 'savings'),
                  const Color(0xFF10B981),
                  LucideIcons.walletCards,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _totalCard(
                  'Total tontine',
                  _totalFor(accounts, 'tontine'),
                  const Color(0xFF6366F1),
                  LucideIcons.repeat2,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Accounts Header with Add Account CTA
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Text(
                    'Comptes actifs',
                    style: TextStyle(
                      color: Color(0xFF0F172A),
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '${accounts.length}',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ),
                ],
              ),
              Row(
                children: [
                  if (accounts.any((a) => a['account_type'] == 'tontine'))
                    TextButton.icon(
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        minimumSize: Size.zero,
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      onPressed: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => TransferScreen(preselectedClient: client),
                          ),
                        );
                        if (context.mounted) {
                          ref.read(cashierNotifierProvider.notifier).fetchClients();
                          ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                        }
                      },
                      icon: const Icon(LucideIcons.arrowLeftRight, size: 13, color: Color(0xFF4F46E5)),
                      label: const Text(
                        'Virement',
                        style: TextStyle(
                          color: Color(0xFF4F46E5),
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  const SizedBox(width: 4),
                  TextButton.icon(
                    style: TextButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                    onPressed: () => _openCreateAccountSheet(client),
                    icon: const Icon(LucideIcons.plusCircle, size: 13, color: AppColors.primaryDark),
                    label: const Text(
                      'Ajouter',
                      style: TextStyle(
                        color: AppColors.primaryDark,
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),

          if (accounts.isEmpty)
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: [
                  const Icon(LucideIcons.walletCards, size: 32, color: Color(0xFF94A3B8)),
                  const SizedBox(height: 8),
                  const Text(
                    'Aucun compte actif pour ce client.',
                    style: TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: () => _openCreateAccountSheet(client),
                    icon: const Icon(LucideIcons.plus, size: 14),
                    label: const Text('Créer un compte maintenant'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.primaryDark,
                      side: const BorderSide(color: AppColors.primary),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ],
              ),
            )
          else
            ...accounts.map((account) => _accountRow(account)),

          const SizedBox(height: 18),

          // KYC Status Card
          Container(
            padding: const EdgeInsets.all(16),
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
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: kycApproved ? const Color(0xFFD1FAE5) : const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(
                    kycApproved ? LucideIcons.badgeCheck : LucideIcons.shieldAlert,
                    color: kycApproved ? const Color(0xFF059669) : const Color(0xFFD97706),
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        kycApproved ? 'Statut KYC : Validé' : 'Statut KYC : En attente',
                        style: const TextStyle(
                          color: Color(0xFF0F172A),
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                      Text(
                        kycApproved ? 'Éligible aux demandes de prêts et facilités.' : 'Requis pour débloquer les demandes de crédit.',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 11),
                      ),
                    ],
                  ),
                ),
                if (!kycApproved)
                  IconButton(
                    onPressed: _isApprovingKyc ? null : _approveKyc,
                    icon: _isApprovingKyc
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                        : Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Icon(LucideIcons.check, color: AppColors.primaryDark, size: 16),
                          ),
                    tooltip: 'Activer le KYC',
                  ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          // Loans & Credit Section
          _buildLoansSection(context, client, loans),

          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildLoansSection(BuildContext context, ClientModel client, List<LoanModel> loans) {
    final disbursedLoans = loans.where((l) => l.isDisbursed || l.isCompleted).toList();
    final approvedLoans = loans.where((l) => l.isApproved).toList();
    final pendingLoans = loans.where((l) => l.isPending).toList();

    final totalDisbursed = disbursedLoans.fold<double>(
      0,
      (sum, l) => sum + (l.approvedAmount > 0 ? l.approvedAmount : l.requestedAmount),
    );
    final totalApprovedNotDisbursed = approvedLoans.fold<double>(
      0,
      (sum, l) => sum + (l.approvedAmount > 0 ? l.approvedAmount : l.requestedAmount),
    );
    final totalPendingRequested = pendingLoans.fold<double>(
      0,
      (sum, l) => sum + l.requestedAmount,
    );

    final totalPaid = loans.fold<double>(0, (sum, l) => sum + l.totalPaid);
    final totalRemaining = disbursedLoans.fold<double>(0, (sum, l) => sum + l.remainingDue);
    final progressRate = totalDisbursed > 0 ? (totalPaid / totalDisbursed).clamp(0.0, 1.0) : 0.0;
    final hasDisbursed = disbursedLoans.isNotEmpty;
    final hasApproved = approvedLoans.isNotEmpty;
    final hasPendingOnly = !hasDisbursed && !hasApproved && pendingLoans.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Section Header with Overflow Protection
        Row(
          children: [
            Expanded(
              child: Row(
                children: [
                  const Flexible(
                    child: Text(
                      'Prêts & Crédits',
                      style: TextStyle(
                        color: Color(0xFF0F172A),
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: loans.isNotEmpty ? const Color(0xFFEEF2FF) : const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '${loans.length}',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: loans.isNotEmpty ? const Color(0xFF4F46E5) : const Color(0xFF64748B),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 6),
            TextButton.icon(
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              onPressed: () async {
                await Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => LoanApplicationScreen(preselectedClient: client),
                  ),
                );
                if (context.mounted) {
                  ref.read(loanNotifierProvider.notifier).fetchLoans();
                  ref.read(cashierNotifierProvider.notifier).fetchClients();
                }
              },
              icon: const Icon(LucideIcons.plusCircle, size: 14, color: Color(0xFF4F46E5)),
              label: const Text(
                'Demander un prêt',
                style: TextStyle(
                  color: Color(0xFF4F46E5),
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),

        if (loans.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
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
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: const BoxDecoration(
                    color: Color(0xFFF1F5F9),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(LucideIcons.badgePercent, size: 28, color: Color(0xFF94A3B8)),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Aucun prêt demandé pour le moment',
                  style: TextStyle(color: Color(0xFF0F172A), fontSize: 14, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Ce client n\'a pas encore de dossier de prêt ou de facilité de crédit.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 11.5),
                ),
                const SizedBox(height: 14),
                ElevatedButton.icon(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => LoanApplicationScreen(preselectedClient: client),
                      ),
                    );
                  },
                  icon: const Icon(LucideIcons.filePlus, size: 15),
                  label: const Text('Créer une demande de prêt', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4F46E5),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ],
            ),
          )
        else ...[
          // Global KPI summary card
          Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: hasPendingOnly
                    ? [const Color(0xFF451A03), const Color(0xFF78350F)]
                    : (hasApproved && !hasDisbursed
                        ? [const Color(0xFF1E3A8A), const Color(0xFF1D4ED8)]
                        : [const Color(0xFF1E1B4B), const Color(0xFF312E81)]),
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: (hasPendingOnly ? const Color(0xFF78350F) : const Color(0xFF312E81)).withValues(alpha: 0.25),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
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
                            color: Colors.white.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Icon(
                            hasPendingOnly ? LucideIcons.hourglass : LucideIcons.pieChart,
                            color: Colors.white,
                            size: 16,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          hasPendingOnly ? 'DEMANDE DE CRÉDIT EN ATTENTE' : 'SYNTHÈSE DES CRÉDITS',
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.8,
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
                        hasPendingOnly
                            ? 'Non validé'
                            : (hasApproved && !hasDisbursed
                                ? 'Prêt validé'
                                : '${(progressRate * 100).toInt()}% Remboursé'),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // 3 KPIs
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            hasPendingOnly ? 'Montant Demandé' : 'Total Accordé',
                            style: const TextStyle(color: Colors.white60, fontSize: 10.5, fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 2),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            child: Text(
                              CurrencyFormatter.formatSimple(
                                hasPendingOnly ? totalPendingRequested : (totalDisbursed + totalApprovedNotDisbursed),
                              ),
                              style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(height: 28, width: 1, color: Colors.white24),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Total Remboursé',
                            style: TextStyle(color: Color(0xFF86EFAC), fontSize: 10.5, fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 2),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            child: Text(
                              CurrencyFormatter.formatSimple(totalPaid),
                              style: const TextStyle(color: Color(0xFF86EFAC), fontSize: 14, fontWeight: FontWeight.w800),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(height: 28, width: 1, color: Colors.white24),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Reste Dû',
                            style: TextStyle(color: Color(0xFFFCA5A5), fontSize: 10.5, fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 2),
                          FittedBox(
                            fit: BoxFit.scaleDown,
                            child: Text(
                              CurrencyFormatter.formatSimple(totalRemaining),
                              style: const TextStyle(color: Color(0xFFFCA5A5), fontSize: 14, fontWeight: FontWeight.w800),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                if (hasPendingOnly) ...[
                  const SizedBox(height: 12),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Row(
                      children: [
                        Icon(LucideIcons.info, color: Color(0xFFFDE68A), size: 14),
                        SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Prêt non encore validé ni décaissé. Aucun engagement dû pour le moment.',
                            style: TextStyle(color: Color(0xFFFEF3C7), fontSize: 10.5, fontWeight: FontWeight.w600),
                          ),
                        ),
                      ],
                    ),
                  ),
                ] else if (hasDisbursed) ...[
                  const SizedBox(height: 12),
                  // Progress bar
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: progressRate,
                      minHeight: 6,
                      backgroundColor: Colors.white.withValues(alpha: 0.15),
                      valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF10B981)),
                    ),
                  ),
                ],
              ],
            ),
          ),

          // Loan Cards List
          ...loans.map((loan) => _loanCard(context, loan)),
        ],
      ],
    );
  }

  Widget _loanCard(BuildContext context, LoanModel loan) {
    final isPending = loan.isPending;
    final isApproved = loan.isApproved;
    final isDisbursed = loan.isDisbursed;
    final isCompleted = loan.isCompleted;

    final effectiveAmount = loan.effectiveAmount;
    final paidAmount = loan.totalPaid;
    final rem = isDisbursed ? loan.remainingDue : 0.0;
    final prog = isDisbursed ? loan.repaymentProgress : (isCompleted ? 1.0 : 0.0);

    return InkWell(
      onTap: () async {
        await Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => LoanDetailScreen(loan: loan),
          ),
        );
        if (context.mounted) {
          ref.read(loanNotifierProvider.notifier).fetchLoans();
          ref.read(cashierNotifierProvider.notifier).fetchClients();
        }
      },
      borderRadius: BorderRadius.circular(16),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: loan.isOverdue
                ? const Color(0xFFFCA5A5)
                : (isDisbursed
                    ? const Color(0xFFC7D2FE)
                    : (isApproved ? const Color(0xFFBAE6FD) : const Color(0xFFE2E8F0))),
            width: loan.isOverdue || isDisbursed || isApproved ? 1.5 : 1,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Code + Status Badge
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(7),
                      decoration: BoxDecoration(
                        color: (isPending
                                ? const Color(0xFFF59E0B)
                                : (isApproved ? const Color(0xFF0284C7) : const Color(0xFF4F46E5)))
                            .withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Icon(
                        isPending ? LucideIcons.hourglass : LucideIcons.handCoins,
                        color: isPending
                            ? const Color(0xFFD97706)
                            : (isApproved ? const Color(0xFF0284C7) : const Color(0xFF4F46E5)),
                        size: 16,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          loan.loanNumber,
                          style: const TextStyle(
                            color: Color(0xFF0F172A),
                            fontWeight: FontWeight.w800,
                            fontSize: 13.5,
                          ),
                        ),
                        if (loan.applicationDate != null)
                          Text(
                            'Demandé le ${loan.applicationDate!.day}/${loan.applicationDate!.month}/${loan.applicationDate!.year}',
                            style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10.5),
                          ),
                      ],
                    ),
                  ],
                ),
                _loanStatusBadge(loan.status, isOverdue: loan.isOverdue, daysOverdue: loan.daysOverdue),
              ],
            ),

            if (loan.purpose != null && loan.purpose!.trim().isNotEmpty) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  'Motif : ${loan.purpose}',
                  style: const TextStyle(color: Color(0xFF475569), fontSize: 11, fontWeight: FontWeight.w500),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],

            const SizedBox(height: 12),
            const Divider(height: 1, color: Color(0xFFF1F5F9)),
            const SizedBox(height: 10),

            // Metrics row
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isPending
                            ? 'Montant demandé'
                            : (isApproved ? 'Montant approuvé' : 'Montant décaissé'),
                        style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10.5, fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        CurrencyFormatter.formatSimple(effectiveAmount),
                        style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13.5, fontWeight: FontWeight.w800),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isPending
                            ? 'Décaissement'
                            : (isApproved ? 'Validation' : 'Déjà payé'),
                        style: TextStyle(
                          color: isDisbursed || isCompleted ? const Color(0xFF10B981) : const Color(0xFF64748B),
                          fontSize: 10.5,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        isPending
                            ? 'Non accordé'
                            : (isApproved
                                ? 'Prêt validé'
                                : CurrencyFormatter.formatSimple(paidAmount)),
                        style: TextStyle(
                          color: isPending
                              ? const Color(0xFFD97706)
                              : (isApproved ? const Color(0xFF0284C7) : const Color(0xFF10B981)),
                          fontSize: 13.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        isDisbursed ? 'Reste à solder' : 'Reste dû',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 10.5, fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        isPending
                            ? '0 FCFA'
                            : (isApproved
                                ? '0 FCFA'
                                : CurrencyFormatter.formatSimple(rem)),
                        style: TextStyle(
                          color: isDisbursed && rem > 0 ? const Color(0xFFEF4444) : const Color(0xFF10B981),
                          fontSize: 13.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),

            if (isPending) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF3C7),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Row(
                  children: [
                    Icon(LucideIcons.clock, size: 13, color: Color(0xFFD97706)),
                    SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'En attente d’étude et de validation. Aucun montant n’est encore dû.',
                        style: TextStyle(color: Color(0xFF92400E), fontSize: 10.5, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),
            ] else if (isApproved) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFFDBEAFE),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Row(
                  children: [
                    Icon(LucideIcons.checkCircle2, size: 13, color: Color(0xFF1D4ED8)),
                    SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'Prêt validé ! Cliquez sur le dossier pour procéder au décaissement.',
                        style: TextStyle(color: Color(0xFF1E40AF), fontSize: 10.5, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),
            ] else if (isDisbursed || isCompleted) ...[
              const SizedBox(height: 10),
              // Progress bar
              Row(
                children: [
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: prog,
                        minHeight: 5,
                        backgroundColor: const Color(0xFFF1F5F9),
                        valueColor: AlwaysStoppedAnimation<Color>(
                          isCompleted ? const Color(0xFF10B981) : const Color(0xFF4F46E5),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${(prog * 100).toInt()}%',
                    style: const TextStyle(color: Color(0xFF64748B), fontSize: 10.5, fontWeight: FontWeight.w700),
                  ),
                ],
              ),
            ],

            const SizedBox(height: 10),
            // Footer details and CTA
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(LucideIcons.calendar, size: 12, color: Color(0xFF94A3B8)),
                    const SizedBox(width: 4),
                    Text(
                      '${loan.durationMonths} mois • ${CurrencyFormatter.formatSimple(loan.monthlyPayment)}/m',
                      style: const TextStyle(color: Color(0xFF64748B), fontSize: 11, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
                const Row(
                  children: [
                    Text(
                      'Détail & Échéances',
                      style: TextStyle(color: Color(0xFF4F46E5), fontSize: 11, fontWeight: FontWeight.w700),
                    ),
                    SizedBox(width: 2),
                    Icon(LucideIcons.chevronRight, size: 14, color: Color(0xFF4F46E5)),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _loanStatusBadge(String status, {bool isOverdue = false, int daysOverdue = 0}) {
    if (isOverdue) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: const Color(0xFFFEE2E2),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          'En retard ($daysOverdue j)',
          style: const TextStyle(color: Color(0xFFDC2626), fontSize: 10.5, fontWeight: FontWeight.w700),
        ),
      );
    }

    switch (status) {
      case 'pending':
      case 'under_review':
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFFFEF3C7),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            'En attente',
            style: TextStyle(color: Color(0xFFD97706), fontSize: 10.5, fontWeight: FontWeight.w700),
          ),
        );
      case 'approved':
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFFDBEAFE),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            'Approuvé (À décaisser)',
            style: TextStyle(color: Color(0xFF2563EB), fontSize: 10.5, fontWeight: FontWeight.w700),
          ),
        );
      case 'disbursed':
      case 'active':
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFFEEF2FF),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            'En cours de paiement',
            style: TextStyle(color: Color(0xFF4F46E5), fontSize: 10.5, fontWeight: FontWeight.w700),
          ),
        );
      case 'completed':
      case 'repaid':
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFFD1FAE5),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            'Soldé / Terminé',
            style: TextStyle(color: Color(0xFF059669), fontSize: 10.5, fontWeight: FontWeight.w700),
          ),
        );
      case 'rejected':
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFFFEE2E2),
            borderRadius: BorderRadius.circular(8),
          ),
          child: const Text(
            'Rejeté',
            style: TextStyle(color: Color(0xFFDC2626), fontSize: 10.5, fontWeight: FontWeight.w700),
          ),
        );
      default:
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            status,
            style: const TextStyle(color: Color(0xFF64748B), fontSize: 10.5, fontWeight: FontWeight.w700),
          ),
        );
    }
  }

  Widget _totalCard(String title, double value, Color color, IconData icon) {
    return Container(
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
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 16),
          ),
          const SizedBox(height: 10),
          FittedBox(
            alignment: Alignment.centerLeft,
            fit: BoxFit.scaleDown,
            child: Text(
              CurrencyFormatter.formatSimple(value),
              style: const TextStyle(color: Color(0xFF0F172A), fontSize: 16, fontWeight: FontWeight.w800),
            ),
          ),
          const SizedBox(height: 2),
          Text(title, style: const TextStyle(color: Color(0xFF64748B), fontSize: 11, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Widget _accountRow(Map account) {
    final type = account['account_type']?.toString() ?? 'savings';
    final isSavings = type == 'savings' || type == 'epargne';
    final accountModel = AccountModel.fromJson(Map<String, dynamic>.from(account));

    return InkWell(
      onTap: () => _showAccountTransactions(accountModel),
      borderRadius: BorderRadius.circular(14),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
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
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: isSavings
                    ? const Color(0xFF10B981).withValues(alpha: 0.12)
                    : const Color(0xFF6366F1).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                isSavings ? LucideIcons.piggyBank : LucideIcons.repeat2,
                color: isSavings ? const Color(0xFF10B981) : const Color(0xFF6366F1),
                size: 18,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    isSavings
                        ? 'Compte Épargne'
                        : (accountModel.dailyAmount != null && accountModel.dailyAmount! > 0
                            ? 'Tontine (${accountModel.dailyAmount!.toInt()} FCFA/j)'
                            : 'Compte Tontine'),
                    style: const TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.w700, fontSize: 13),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${accountModel.accountNumber}${!isSavings && accountModel.cycleDurationMonths != null ? ' • ${accountModel.cycleDurationMonths} mois' : ''}',
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  CurrencyFormatter.formatSimple(accountModel.balance),
                  style: const TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.w800, fontSize: 13),
                ),
                const SizedBox(height: 2),
                Text(
                  accountModel.status == 'active' ? 'Actif' : accountModel.status,
                  style: const TextStyle(color: Color(0xFF10B981), fontSize: 10, fontWeight: FontWeight.w700),
                ),
              ],
            ),
            const SizedBox(width: 6),
            const Icon(LucideIcons.chevronRight, color: Color(0xFFCBD5E1), size: 16),
          ],
        ),
      ),
    );
  }

  Future<void> _showAccountTransactions(AccountModel account) async {
    final transactions = await ref.read(cashierNotifierProvider.notifier).fetchAccountTransactions(account.id);
    if (!mounted) return;
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFFF6F8FC),
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _TransactionsSheet(account: account, transactions: transactions),
    );
  }
}

class _TransactionsSheet extends ConsumerWidget {
  final AccountModel account;
  final List<TransactionModel> transactions;

  const _TransactionsSheet({required this.account, required this.transactions});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return SafeArea(
      child: SizedBox(
        height: MediaQuery.sizeOf(context).height * 0.72,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 18, 12, 12),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      account.typeLabel,
                      style: const TextStyle(color: Color(0xFF0F172A), fontSize: 17, fontWeight: FontWeight.w800),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(LucideIcons.x, color: Color(0xFF94A3B8), size: 20),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('SOLDE DISPONIBLE', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF94A3B8))),
                          const SizedBox(height: 2),
                          FittedBox(
                            alignment: Alignment.centerLeft,
                            fit: BoxFit.scaleDown,
                            child: Text(
                              CurrencyFormatter.format(account.balance),
                              style: const TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.w900, fontSize: 17),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (account.accountType.toLowerCase() == 'tontine' && account.balance > 0) ...[
                          IconButton(
                            tooltip: 'Virement vers compte Épargne',
                            style: IconButton.styleFrom(
                              backgroundColor: const Color(0xFFEEF2FF),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(10),
                                side: const BorderSide(color: Color(0xFFC7D2FE)),
                              ),
                              padding: const EdgeInsets.all(8),
                              minimumSize: const Size(36, 36),
                            ),
                            onPressed: () async {
                              Navigator.of(context).pop();
                              await Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => TransferScreen(
                                    preselectedSourceAccount: account,
                                  ),
                                ),
                              );
                              if (context.mounted) {
                                ref.read(cashierNotifierProvider.notifier).fetchClients();
                                ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                              }
                            },
                            icon: const Icon(LucideIcons.arrowLeftRight, size: 16, color: Color(0xFF4F46E5)),
                          ),
                          const SizedBox(width: 6),
                        ],
                        IconButton(
                          tooltip: 'Encaissement / Cotisation',
                          style: IconButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                            padding: const EdgeInsets.all(8),
                            minimumSize: const Size(36, 36),
                          ),
                          onPressed: () async {
                            Navigator.of(context).pop();
                            await Navigator.of(context).push(
                              MaterialPageRoute(builder: (_) => DepositScreen(preselectedAccount: account)),
                            );
                            if (context.mounted) {
                              ref.read(cashierNotifierProvider.notifier).fetchClients();
                              ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                            }
                          },
                          icon: const Icon(LucideIcons.arrowDownLeft, size: 16, color: Colors.white),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Expanded(
              child: transactions.isEmpty
                  ? const Center(
                      child: Text(
                        'Aucune transaction enregistrée pour ce compte.',
                        style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                      itemCount: transactions.length,
                      separatorBuilder: (_, __) => const Divider(height: 1, color: Color(0xFFF1F5F9)),
                      itemBuilder: (_, index) {
                        final transaction = transactions[index];
                        final incoming = transaction.isDeposit;
                        return ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: incoming ? const Color(0xFFD1FAE5) : const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Icon(
                              incoming ? LucideIcons.arrowDownLeft : LucideIcons.arrowUpRight,
                              color: incoming ? const Color(0xFF059669) : const Color(0xFFDC2626),
                              size: 16,
                            ),
                          ),
                          title: Text(
                            transaction.description ?? (incoming ? 'Encaissement' : 'Décaissement'),
                            style: const TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.w700, fontSize: 13),
                          ),
                          subtitle: Text(
                            transaction.createdAt != null
                                ? '${transaction.createdAt!.day}/${transaction.createdAt!.month} à ${transaction.createdAt!.hour.toString().padLeft(2, '0')}:${transaction.createdAt!.minute.toString().padLeft(2, '0')}'
                                : transaction.reference,
                            style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                          ),
                          trailing: Text(
                            '${incoming ? '+' : '-'} ${CurrencyFormatter.formatSimple(transaction.amount)}',
                            style: TextStyle(
                              color: incoming ? const Color(0xFF059669) : const Color(0xFFDC2626),
                              fontWeight: FontWeight.w800,
                              fontSize: 13,
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
}