import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/account_model.dart';
import '../../../data/models/client_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../../../domain/notifiers/sync_notifier.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';
import '../receipts/receipt_screen.dart';

class WithdrawalScreen extends ConsumerStatefulWidget {
  final AccountModel? preselectedAccount;

  const WithdrawalScreen({super.key, this.preselectedAccount});

  @override
  ConsumerState<WithdrawalScreen> createState() => _WithdrawalScreenState();
}

class _WithdrawalScreenState extends ConsumerState<WithdrawalScreen> {
  final _searchController = TextEditingController();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  AccountModel? _selectedAccount;
  ClientModel? _selectedClient;
  List<AccountModel> _clientAccounts = [];
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _selectedAccount = widget.preselectedAccount;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(cashierNotifierProvider.notifier).fetchClients();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  void _selectClient(ClientModel client) {
    final rawAccounts = (client.accounts ?? []).whereType<Map>().toList();
    final accounts = rawAccounts
        .map((a) => AccountModel.fromJson(Map<String, dynamic>.from(a)))
        .where((a) => a.status.toLowerCase() == 'active')
        .toList();

    if (accounts.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Ce client n\'a aucun compte actif.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    if (accounts.length == 1) {
      setState(() {
        _selectedClient = client;
        _selectedAccount = accounts.first;
        _clientAccounts = accounts;
      });
    } else {
      setState(() {
        _selectedClient = client;
        _clientAccounts = accounts;
        _selectedAccount = null;
      });
    }
  }

  void _selectAccount(AccountModel account) {
    setState(() {
      _selectedAccount = account;
    });
  }

  void _resetSelection() {
    setState(() {
      _selectedAccount = null;
      _selectedClient = null;
      _clientAccounts = [];
      _amountController.clear();
      _noteController.clear();
    });
  }

  Future<void> _submitWithdrawal() async {
    if (!_formKey.currentState!.validate() || _selectedAccount == null) return;

    final amount = double.tryParse(_amountController.text.replaceAll(' ', '')) ?? 0.0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez entrer un montant valide.')),
      );
      return;
    }

    if (amount > _selectedAccount!.balance) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Solde insuffisant pour effectuer ce retrait.'),
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

    final cashierNotifier = ref.read(cashierNotifierProvider.notifier);
    final transaction = await cashierNotifier.processWithdrawal(
      accountId: _selectedAccount!.id,
      amount: amount,
      note: _noteController.text.trim(),
    );

    if (!mounted) return;

    if (transaction != null) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => ReceiptScreen(transaction: transaction),
        ),
      );
    } else {
      final cashierState = ref.read(cashierNotifierProvider);
      final shouldQueueOffline = await _askSaveOffline(
        context,
        title: 'Serveur ou Réseau indisponible',
        message: 'Impossible de joindre le serveur pour valider ce retrait. Souhaitez-vous enregistrer ce décaissement en Mode Hors-Ligne (reçu provisoire et synchronisation automatique) ?',
      );

      if (shouldQueueOffline == true && mounted) {
        final offlineTx = await ref.read(syncNotifierProvider.notifier).queueOfflineOperation(
          type: 'withdrawal',
          accountId: _selectedAccount!.id,
          amount: amount,
          clientName: _selectedClient?.fullName ?? _selectedAccount!.clientName,
          accountNumber: _selectedAccount!.accountNumber,
          description: _noteController.text.trim().isNotEmpty
              ? _noteController.text.trim()
              : 'Décaissement sur ${_selectedAccount!.accountNumber}',
        );
        if (mounted) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => ReceiptScreen(transaction: offlineTx)),
          );
        }
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(cashierState.errorMessage ?? 'Erreur lors du décaissement.'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  Future<bool?> _askSaveOffline(BuildContext context, {required String title, required String message}) {
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(LucideIcons.wifiOff, color: Color(0xFFD97706), size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: Color(0xFF0F172A)),
              ),
            ),
          ],
        ),
        content: Text(
          message,
          style: const TextStyle(fontSize: 13, height: 1.4, color: Color(0xFF475569)),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Annuler', style: TextStyle(color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
          ),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryDark,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            icon: const Icon(LucideIcons.save, size: 16),
            label: const Text('Enregistrer Hors-Ligne', style: TextStyle(fontWeight: FontWeight.w700)),
            onPressed: () => Navigator.pop(ctx, true),
          ),
        ],
      ),
    );
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
        title: Text(_selectedAccount != null
            ? 'Décaissement'
            : _selectedClient != null
                ? 'Comptes de ${_selectedClient!.fullName}'
                : 'Décaissement (Retrait)'),
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft),
          onPressed: () {
            if (_selectedAccount != null && widget.preselectedAccount == null) {
              if (_clientAccounts.length > 1) {
                setState(() => _selectedAccount = null);
              } else {
                _resetSelection();
              }
            } else if (_selectedClient != null && widget.preselectedAccount == null) {
              _resetSelection();
            } else {
              Navigator.of(context).pop();
            }
          },
        ),
      ),
      body: widget.preselectedAccount != null || _selectedAccount != null
          ? _buildAmountForm(cashier)
          : _selectedClient != null
              ? _buildAccountSelector()
              : _buildClientList(cashier),
    );
  }

  // ─────────────────────────────────────────────
  // STEP 1: Client List with Search
  // ─────────────────────────────────────────────
  Widget _buildClientList(CashierState cashier) {
    final clients = cashier.clients.where((client) {
      if (_searchQuery.isEmpty) return true;
      final q = _searchQuery.toLowerCase();
      return client.fullName.toLowerCase().contains(q) ||
          (client.phone ?? '').toLowerCase().contains(q) ||
          (client.idNumber ?? '').toLowerCase().contains(q);
    }).toList();

    return Column(
      children: [
        Container(
          color: Colors.white,
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: TextField(
            controller: _searchController,
            style: const TextStyle(color: Color(0xFF1D2A3A), fontSize: 14),
            decoration: InputDecoration(
              hintText: 'Rechercher un client (nom, téléphone...)',
              hintStyle: const TextStyle(color: Color(0xFF9AA5B5), fontSize: 13),
              prefixIcon: const Icon(LucideIcons.search, size: 18, color: Color(0xFF9AA5B5)),
              suffixIcon: _searchQuery.isNotEmpty
                  ? IconButton(
                      icon: const Icon(LucideIcons.x, size: 16),
                      onPressed: () {
                        _searchController.clear();
                        setState(() => _searchQuery = '');
                      },
                    )
                  : null,
              filled: true,
              fillColor: const Color(0xFFF5F7FB),
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
            ),
            onChanged: (val) => setState(() => _searchQuery = val.trim()),
          ),
        ),
        const Divider(height: 1, color: Color(0xFFE2E8F0)),

        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          color: const Color(0xFFF5F7FB),
          child: Text(
            '${clients.length} client${clients.length > 1 ? 's' : ''} trouvé${clients.length > 1 ? 's' : ''}',
            style: const TextStyle(
              color: Color(0xFF64748B),
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),

        Expanded(
          child: cashier.isLoading && cashier.clients.isEmpty
              ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
              : clients.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(LucideIcons.userX, size: 40, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          const Text(
                            'Aucun client trouvé.',
                            style: TextStyle(color: Color(0xFF64748B), fontSize: 14, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () => ref.read(cashierNotifierProvider.notifier).fetchClients(),
                      color: AppColors.primary,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        itemCount: clients.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 6),
                        itemBuilder: (_, index) {
                          final client = clients[index];
                          final accountCount = (client.accounts ?? []).whereType<Map>().length;
                          return Material(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            child: InkWell(
                              borderRadius: BorderRadius.circular(14),
                              onTap: () => _selectClient(client),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                child: Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 22,
                                      backgroundColor: AppColors.danger.withValues(alpha: 0.12),
                                      child: Text(
                                        client.firstName.isNotEmpty
                                            ? client.firstName[0].toUpperCase()
                                            : 'C',
                                        style: const TextStyle(
                                          color: AppColors.danger,
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
                                            style: const TextStyle(
                                              fontSize: 14,
                                              fontWeight: FontWeight.w700,
                                              color: Color(0xFF0F172A),
                                            ),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            client.phone ?? 'Sans téléphone',
                                            style: const TextStyle(
                                              fontSize: 12,
                                              color: Color(0xFF64748B),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFF1F5F9),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        '$accountCount cpt${accountCount > 1 ? 's' : ''}',
                                        style: const TextStyle(
                                          color: Color(0xFF64748B),
                                          fontSize: 11,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    const Icon(LucideIcons.chevronRight, size: 18, color: Color(0xFF94A3B8)),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  // ─────────────────────────────────────────────
  // STEP 2: Account Selector
  // ─────────────────────────────────────────────
  Widget _buildAccountSelector() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: AppColors.danger.withValues(alpha: 0.12),
                  child: Text(
                    _selectedClient!.firstName.isNotEmpty
                        ? _selectedClient!.firstName[0].toUpperCase()
                        : 'C',
                    style: const TextStyle(
                      color: AppColors.danger,
                      fontWeight: FontWeight.w800,
                      fontSize: 18,
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _selectedClient!.fullName,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF0F172A),
                        ),
                      ),
                      if (_selectedClient!.phone != null)
                        Text(
                          _selectedClient!.phone!,
                          style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          const Text(
            'SÉLECTIONNEZ UN COMPTE POUR LE RETRAIT',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Color(0xFF72809A),
              letterSpacing: 0.8,
            ),
          ),
          const SizedBox(height: 10),

          ..._clientAccounts.map((account) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Material(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(16),
                    onTap: () => _selectAccount(account),
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: AppColors.danger.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(LucideIcons.wallet, color: AppColors.danger, size: 20),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  account.typeLabel,
                                  style: const TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                    color: Color(0xFF0F172A),
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  account.accountNumber,
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                CurrencyFormatter.formatSimple(account.balance),
                                style: const TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                              const SizedBox(height: 2),
                              const Text(
                                'FCFA',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF94A3B8),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(width: 6),
                          const Icon(LucideIcons.arrowUpRight, size: 18, color: AppColors.danger),
                        ],
                      ),
                    ),
                  ),
                ),
              )),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // STEP 3: Amount Form
  // ─────────────────────────────────────────────
  Widget _buildAmountForm(CashierState cashier) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!cashier.isSessionOpen)
              Container(
                margin: const EdgeInsets.only(bottom: 18),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFFCA5A5)),
                ),
                child: const Row(
                  children: [
                    Icon(LucideIcons.alertTriangle, color: AppColors.danger, size: 20),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Attention : Votre session de caisse est fermée. Ouvrez la session journalière pour pouvoir effectuer un retrait.',
                        style: TextStyle(
                          color: Color(0xFF991B1B),
                          fontSize: 12.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

            // Selected Account Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.danger.withValues(alpha: 0.4), width: 1.5),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.danger.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          _selectedAccount!.typeLabel,
                          style: const TextStyle(
                            color: AppColors.danger,
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      Text(
                        _selectedAccount!.accountNumber,
                        style: const TextStyle(
                          color: Color(0xFF1D2A3A),
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Text(
                    _selectedClient?.fullName ?? _selectedAccount!.clientName ?? 'Client',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1D2A3A),
                    ),
                  ),
                  const Divider(color: Color(0xFFE7ECF5), height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Solde Disponible',
                        style: TextStyle(color: Color(0xFF72809A), fontSize: 13),
                      ),
                      Text(
                        CurrencyFormatter.format(_selectedAccount!.balance),
                        style: const TextStyle(
                          color: AppColors.success,
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 22),

            // Amount Input
            CustomTextField(
              controller: _amountController,
              label: 'Montant à décaisser (FCFA)',
              hint: 'Ex: 25 000',
              prefixIcon: LucideIcons.arrowUpCircle,
              lightTheme: true,
              keyboardType: TextInputType.number,
              validator: (val) {
                if (val == null || val.trim().isEmpty) return 'Montant obligatoire';
                final n = double.tryParse(val.replaceAll(' ', ''));
                if (n == null || n <= 0) return 'Montant invalide';
                return null;
              },
            ),
            const SizedBox(height: 18),

            // Note
            CustomTextField(
              controller: _noteController,
              label: 'Motif du retrait (Optionnel)',
              hint: 'Ex: Retrait partiel épargne',
              prefixIcon: LucideIcons.fileText,
              lightTheme: true,
            ),
            const SizedBox(height: 28),

            // Submit Button
            CustomButton(
              text: 'Valider le Décaissement',
              icon: LucideIcons.arrowUpRight,
              backgroundColor: AppColors.danger,
              isLoading: cashier.isLoading,
              onPressed: _submitWithdrawal,
            ),
          ],
        ),
      ),
    );
  }
}
