import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/account_model.dart';
import '../../../data/models/client_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';
import '../receipts/receipt_screen.dart';

class TransferScreen extends ConsumerStatefulWidget {
  final ClientModel? preselectedClient;
  final AccountModel? preselectedSourceAccount;

  const TransferScreen({
    super.key,
    this.preselectedClient,
    this.preselectedSourceAccount,
  });

  @override
  ConsumerState<TransferScreen> createState() => _TransferScreenState();
}

class _TransferScreenState extends ConsumerState<TransferScreen> {
  final _searchController = TextEditingController();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  ClientModel? _selectedClient;
  AccountModel? _selectedSourceAccount;
  AccountModel? _selectedDestAccount;

  List<AccountModel> _tontineAccounts = [];
  List<AccountModel> _savingsAccounts = [];
  String _searchQuery = '';
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _selectedClient = widget.preselectedClient;
    _selectedSourceAccount = widget.preselectedSourceAccount;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(cashierNotifierProvider.notifier).fetchClients();
      if (_selectedClient != null) {
        _initializeClientAccounts(_selectedClient!);
      }
    });

    _amountController.addListener(() {
      setState(() {});
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  void _initializeClientAccounts(ClientModel client) {
    final rawAccounts = (client.accounts ?? []).whereType<Map>().toList();
    final allAccounts = rawAccounts
        .map((a) => AccountModel.fromJson(Map<String, dynamic>.from(a)))
        .where((a) => a.status.toLowerCase() == 'active')
        .toList();

    final tontines = allAccounts
        .where((a) => a.accountType.toLowerCase() == 'tontine')
        .toList();

    final savings = allAccounts
        .where((a) =>
            a.accountType.toLowerCase() == 'savings' ||
            a.accountType.toLowerCase() == 'epargne')
        .toList();

    setState(() {
      _selectedClient = client;
      _tontineAccounts = tontines;
      _savingsAccounts = savings;

      // Auto-select source
      if (widget.preselectedSourceAccount != null &&
          tontines.any((t) => t.id == widget.preselectedSourceAccount!.id)) {
        _selectedSourceAccount = tontines.firstWhere((t) => t.id == widget.preselectedSourceAccount!.id);
      } else if (tontines.isNotEmpty) {
        // Pick tontine with highest balance
        tontines.sort((a, b) => b.balance.compareTo(a.balance));
        _selectedSourceAccount = tontines.first;
      } else {
        _selectedSourceAccount = null;
      }

      // Auto-select destination
      if (savings.isNotEmpty) {
        _selectedDestAccount = savings.first;
      } else {
        _selectedDestAccount = null;
      }
    });
  }

  void _selectClient(ClientModel client) {
    _initializeClientAccounts(client);
  }

  void _resetClient() {
    setState(() {
      _selectedClient = null;
      _selectedSourceAccount = null;
      _selectedDestAccount = null;
      _tontineAccounts = [];
      _savingsAccounts = [];
      _amountController.clear();
      _noteController.clear();
    });
  }

  void _setPercentageAmount(double ratio) {
    if (_selectedSourceAccount == null) return;
    final maxAvailable = _selectedSourceAccount!.balance;
    if (maxAvailable <= 0) return;

    final target = (maxAvailable * ratio).floorToDouble();
    _amountController.text = target.toInt().toString();
  }

  Future<void> _submitTransfer() async {
    if (!_formKey.currentState!.validate()) return;

    if (_selectedSourceAccount == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez sélectionner le compte Tontine source.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    if (_selectedDestAccount == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez sélectionner le compte Épargne destinataire.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final amount = double.tryParse(_amountController.text.replaceAll(' ', '')) ?? 0.0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez saisir un montant supérieur à 0 FCFA.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    if (amount > _selectedSourceAccount!.balance) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Solde insuffisant sur la Tontine (${CurrencyFormatter.formatSimple(_selectedSourceAccount!.balance)} dispo).'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final cashierState = ref.read(cashierNotifierProvider);
    if (!cashierState.isSessionOpen) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Opération refusée : Votre session de caisse est fermée. Veuillez d\'abord ouvrir votre caisse.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final transaction = await ref.read(cashierNotifierProvider.notifier).processTransfer(
      sourceAccountId: _selectedSourceAccount!.id,
      destinationAccountId: _selectedDestAccount!.id,
      amount: amount,
      note: _noteController.text.trim().isNotEmpty
          ? _noteController.text.trim()
          : 'Virement interne Tontine ${_selectedSourceAccount!.accountNumber} -> Épargne ${_selectedDestAccount!.accountNumber}',
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (transaction != null) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => ReceiptScreen(transaction: transaction),
        ),
      );
    } else {
      final err = ref.read(cashierNotifierProvider).errorMessage;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(err ?? 'Erreur lors du virement.'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final cashier = ref.watch(cashierNotifierProvider);
    final hasClient = _selectedClient != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FB),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        foregroundColor: const Color(0xFF1D2A3A),
        elevation: 0,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Virement Inter-Comptes',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
            ),
            Text(
              'Transférer des fonds de Tontine vers Épargne',
              style: TextStyle(fontSize: 11, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
            ),
          ],
        ),
        actions: [
          if (hasClient && widget.preselectedClient == null)
            TextButton.icon(
              onPressed: _resetClient,
              icon: const Icon(LucideIcons.userX, size: 15, color: Color(0xFF64748B)),
              label: const Text('Changer', style: TextStyle(color: Color(0xFF64748B), fontSize: 12, fontWeight: FontWeight.w700)),
            ),
          const SizedBox(width: 8),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: const Color(0xFFE2E8F0), height: 1),
        ),
      ),
      body: SafeArea(
        child: hasClient
            ? _buildTransferForm()
            : _buildClientSelector(cashier.clients, cashier.isLoading),
      ),
    );
  }


  // -------------------------------------------------------------
  // Step 1: Client Selector View
  // -------------------------------------------------------------
  Widget _buildClientSelector(List<ClientModel> clients, bool isLoading) {
    final filtered = clients.where((c) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      return c.fullName.toLowerCase().contains(q) ||
          (c.phone != null && c.phone!.contains(q));
    }).toList();

    return Column(
      children: [
        Container(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
          color: Colors.white,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Sélectionner le Client pour le Virement',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: TextField(
                  controller: _searchController,
                  onChanged: (val) => setState(() => _searchQuery = val),
                  decoration: InputDecoration(
                    hintText: 'Rechercher par nom, téléphone...',
                    hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                    prefixIcon: const Icon(LucideIcons.search, size: 18, color: Color(0xFF64748B)),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(LucideIcons.x, size: 16),
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _searchQuery = '');
                            },
                          )
                        : null,
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  ),
                ),
              ),
            ],
          ),
        ),
        const Divider(height: 1, color: Color(0xFFE2E8F0)),
        Expanded(
          child: isLoading && clients.isEmpty
              ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
              : filtered.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(LucideIcons.users, size: 48, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          Text(
                            _searchQuery.isEmpty ? 'Aucun client trouvé' : 'Aucun résultat pour "$_searchQuery"',
                            style: const TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: filtered.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (ctx, idx) {
                        final client = filtered[idx];
                        final rawAccs = (client.accounts ?? []).whereType<Map>().toList();
                        final hasTontine = rawAccs.any((a) => a['account_type'] == 'tontine');
                        final hasSavings = rawAccs.any((a) => a['account_type'] == 'savings' || a['account_type'] == 'epargne');

                        return Material(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(14),
                            onTap: () => _selectClient(client),
                            child: Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    radius: 22,
                                    backgroundColor: const Color(0xFF6366F1).withValues(alpha: 0.12),
                                    child: Text(
                                      client.firstName.isNotEmpty ? client.firstName[0].toUpperCase() : 'C',
                                      style: const TextStyle(
                                        color: Color(0xFF4338CA),
                                        fontWeight: FontWeight.w800,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          client.fullName,
                                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14, color: Color(0xFF0F172A)),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          client.phone ?? 'Téléphone non renseigné',
                                          style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                                        ),
                                        const SizedBox(height: 4),
                                        Row(
                                          children: [
                                            if (hasTontine)
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                margin: const EdgeInsets.only(right: 6),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFEEF2FF),
                                                  borderRadius: BorderRadius.circular(4),
                                                ),
                                                child: const Text('Tontine', style: TextStyle(color: Color(0xFF4F46E5), fontSize: 9.5, fontWeight: FontWeight.w700)),
                                              ),
                                            if (hasSavings)
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFD1FAE5),
                                                  borderRadius: BorderRadius.circular(4),
                                                ),
                                                child: const Text('Épargne', style: TextStyle(color: Color(0xFF059669), fontSize: 9.5, fontWeight: FontWeight.w700)),
                                              ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                  const Icon(LucideIcons.chevronRight, size: 18, color: Color(0xFF94A3B8)),
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),
        ),
      ],
    );
  }

  // -------------------------------------------------------------
  // Step 2: Transfer Form View
  // -------------------------------------------------------------
  Widget _buildTransferForm() {
    final client = _selectedClient!;
    final double inputAmount = double.tryParse(_amountController.text.replaceAll(' ', '')) ?? 0.0;
    final double sourceBal = _selectedSourceAccount?.balance ?? 0.0;
    final double destBal = _selectedDestAccount?.balance ?? 0.0;
    final double newSourceBal = (sourceBal - inputAmount).clamp(0.0, double.infinity);
    final double newDestBal = destBal + inputAmount;

    final hasNoTontine = _tontineAccounts.isEmpty;
    final hasNoSavings = _savingsAccounts.isEmpty;

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        children: [
          // Client Header Card
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: const Color(0xFF6366F1).withValues(alpha: 0.12),
                  child: Text(
                    client.firstName.isNotEmpty ? client.firstName[0].toUpperCase() : 'C',
                    style: const TextStyle(color: Color(0xFF4338CA), fontWeight: FontWeight.w800, fontSize: 15),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        client.fullName,
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14, color: Color(0xFF0F172A)),
                      ),
                      Text(
                        client.phone ?? 'Client de l\'agence',
                        style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Warnings if accounts are missing
          if (hasNoTontine) ...[
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFFEE2E2),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFCA5A5)),
              ),
              child: const Row(
                children: [
                  Icon(LucideIcons.alertCircle, color: Color(0xFFDC2626), size: 18),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Ce client ne possède aucun compte Tontine actif.',
                      style: TextStyle(color: Color(0xFF991B1B), fontSize: 12, fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],

          if (hasNoSavings) ...[
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFCD34D)),
              ),
              child: const Row(
                children: [
                  Icon(LucideIcons.alertTriangle, color: Color(0xFFD97706), size: 18),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Ce client ne possède pas encore de compte Épargne. Veuillez lui en créer un pour recevoir les fonds.',
                      style: TextStyle(color: Color(0xFF92400E), fontSize: 12, fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],

          // -------------------------------------------------------------
          // DUAL ACCOUNT FLOW VISUALIZER
          // -------------------------------------------------------------
          const Text(
            'CIRCUIT DU VIREMENT',
            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.8),
          ),
          const SizedBox(height: 8),

          // 1. Source Account (Tontine)
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFC7D2FE), width: 1.5),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(LucideIcons.arrowUpRight, size: 14, color: Color(0xFF6366F1)),
                        SizedBox(width: 6),
                        Text(
                          'COMPTE SOURCE (DÉBITÉ)',
                          style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF6366F1)),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEEF2FF),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('Tontine', style: TextStyle(fontSize: 10, color: Color(0xFF4F46E5), fontWeight: FontWeight.w800)),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                if (_tontineAccounts.length > 1)
                  DropdownButtonFormField<AccountModel>(
                    value: _selectedSourceAccount,
                    decoration: InputDecoration(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    items: _tontineAccounts.map((acc) {
                      return DropdownMenuItem(
                        value: acc,
                        child: Text('${acc.accountNumber} (${CurrencyFormatter.formatSimple(acc.balance)})', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                      );
                    }).toList(),
                    onChanged: (acc) => setState(() => _selectedSourceAccount = acc),
                  )
                else if (_selectedSourceAccount != null) ...[
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _selectedSourceAccount!.accountNumber,
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: Color(0xFF0F172A)),
                          ),
                          if (_selectedSourceAccount!.dailyAmount != null && _selectedSourceAccount!.dailyAmount! > 0)
                            Text(
                              'Cotisation : ${_selectedSourceAccount!.dailyAmount!.toInt()} FCFA/j',
                              style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                            ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Solde disponible', style: TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
                          Text(
                            CurrencyFormatter.formatSimple(_selectedSourceAccount!.balance),
                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: Color(0xFF4F46E5)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ] else
                  const Text('Aucun compte tontine disponible.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
              ],
            ),
          ),

          // Central Arrow Divider
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Center(
              child: Container(
                padding: const EdgeInsets.all(8),
                decoration: const BoxDecoration(
                  color: Color(0xFF0F172A),
                  shape: BoxShape.circle,
                ),
                child: const Icon(LucideIcons.arrowDown, color: Colors.white, size: 16),
              ),
            ),
          ),

          // 2. Destination Account (Savings)
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFA7F3D0), width: 1.5),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Row(
                      children: [
                        Icon(LucideIcons.arrowDownLeft, size: 14, color: Color(0xFF059669)),
                        SizedBox(width: 6),
                        Text(
                          'COMPTE DESTINATION (CRÉDITÉ)',
                          style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF059669)),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFD1FAE5),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text('Épargne', style: TextStyle(fontSize: 10, color: Color(0xFF059669), fontWeight: FontWeight.w800)),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                if (_savingsAccounts.length > 1)
                  DropdownButtonFormField<AccountModel>(
                    value: _selectedDestAccount,
                    decoration: InputDecoration(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                    items: _savingsAccounts.map((acc) {
                      return DropdownMenuItem(
                        value: acc,
                        child: Text('${acc.accountNumber} (${CurrencyFormatter.formatSimple(acc.balance)})', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
                      );
                    }).toList(),
                    onChanged: (acc) => setState(() => _selectedDestAccount = acc),
                  )
                else if (_selectedDestAccount != null) ...[
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _selectedDestAccount!.accountNumber,
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: Color(0xFF0F172A)),
                          ),
                          const Text(
                            'Compte Épargne Sécurisé',
                            style: TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Solde actuel', style: TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
                          Text(
                            CurrencyFormatter.formatSimple(_selectedDestAccount!.balance),
                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 15, color: Color(0xFF059669)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ] else
                  const Text('Aucun compte épargne disponible.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // -------------------------------------------------------------
          // AMOUNT SELECTION & QUICK CHIPS
          // -------------------------------------------------------------
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'MONTANT DU VIREMENT',
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 0.8),
                ),
                const SizedBox(height: 8),
                CustomTextField(
                  controller: _amountController,
                  label: 'Montant à transférer',
                  hint: '0',
                  lightTheme: true,
                  keyboardType: TextInputType.number,
                  prefixIcon: LucideIcons.coins,
                  suffixIcon: const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    child: Text(
                      'FCFA',
                      style: TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF1E293B)),
                    ),
                  ),
                  validator: (val) {
                    if (val == null || val.trim().isEmpty) return 'Saisir un montant';
                    final n = double.tryParse(val.replaceAll(' ', ''));
                    if (n == null || n <= 0) return 'Montant invalide';
                    if (_selectedSourceAccount != null && n > _selectedSourceAccount!.balance) {
                      return 'Dépasse le solde tontine disponible';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 10),

                // Quick ratio chips
                if (_selectedSourceAccount != null && _selectedSourceAccount!.balance > 0) ...[
                  Row(
                    children: [
                      _ratioChip('25%', 0.25),
                      const SizedBox(width: 6),
                      _ratioChip('50%', 0.50),
                      const SizedBox(width: 6),
                      _ratioChip('75%', 0.75),
                      const SizedBox(width: 6),
                      _ratioChip('Tout transférer', 1.0, isMax: true),
                    ],
                  ),
                  const SizedBox(height: 14),
                ],

                // Motif / Note
                CustomTextField(
                  controller: _noteController,
                  label: 'Motif / Observation (optionnel)',
                  hint: 'Ex: Clôture de cycle ou sécurisation de l\'épargne',
                  lightTheme: true,
                  prefixIcon: LucideIcons.fileText,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // -------------------------------------------------------------
          // SIMULATION PREVIEW CARD
          // -------------------------------------------------------------
          if (inputAmount > 0) ...[
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(LucideIcons.sparkles, color: Color(0xFF38BDF8), size: 14),
                      SizedBox(width: 6),
                      Text(
                        'APERÇU APRÈS OPÉRATION',
                        style: TextStyle(color: Color(0xFF38BDF8), fontSize: 10.5, fontWeight: FontWeight.w800, letterSpacing: 0.8),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Solde Tontine restant', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                          Text(
                            CurrencyFormatter.formatSimple(newSourceBal),
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14),
                          ),
                        ],
                      ),
                      const Icon(LucideIcons.arrowRight, color: Color(0xFF64748B), size: 16),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Nouveau solde Épargne', style: TextStyle(color: Color(0xFF34D399), fontSize: 11)),
                          Text(
                            CurrencyFormatter.formatSimple(newDestBal),
                            style: const TextStyle(color: Color(0xFF34D399), fontWeight: FontWeight.w900, fontSize: 15),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
          ],

          // CTA Submit Button
          CustomButton(
            text: 'Confirmer le Virement Interne',
            icon: LucideIcons.arrowLeftRight,
            isLoading: _isSubmitting,
            backgroundColor: const Color(0xFF4F46E5),
            onPressed: (_selectedSourceAccount == null || _selectedDestAccount == null)
                ? null
                : _submitTransfer,
          ),
        ],
      ),
    );
  }

  Widget _ratioChip(String label, double ratio, {bool isMax = false}) {
    return Expanded(
      child: InkWell(
        onTap: () => _setPercentageAmount(ratio),
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isMax ? const Color(0xFF4F46E5).withValues(alpha: 0.12) : const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isMax ? const Color(0xFF4F46E5).withValues(alpha: 0.4) : const Color(0xFFE2E8F0),
            ),
          ),
          child: Center(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.w800,
                color: isMax ? const Color(0xFF4F46E5) : const Color(0xFF475569),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
