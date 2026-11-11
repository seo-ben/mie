import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/account_model.dart';
import '../../../data/models/client_model.dart';
import '../../../data/models/loan_model.dart';
import '../../../data/models/transaction_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../../../domain/notifiers/loan_notifier.dart';
import '../../../domain/notifiers/sync_notifier.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';
import '../receipts/receipt_screen.dart';

class DepositScreen extends ConsumerStatefulWidget {
  final AccountModel? preselectedAccount;
  final LoanModel? preselectedLoan;

  const DepositScreen({
    super.key,
    this.preselectedAccount,
    this.preselectedLoan,
  });

  @override
  ConsumerState<DepositScreen> createState() => _DepositScreenState();
}

class _DepositScreenState extends ConsumerState<DepositScreen> {
  final _searchController = TextEditingController();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  AccountModel? _selectedAccount;
  LoanModel? _selectedLoan;
  ClientModel? _selectedClient;
  List<AccountModel> _clientAccounts = [];
  List<LoanModel> _clientLoans = [];
  String _searchQuery = '';
  bool _isProcessingLoan = false;

  @override
  void initState() {
    super.initState();
    _selectedAccount = widget.preselectedAccount;
    _selectedLoan = widget.preselectedLoan;

    if (_selectedLoan != null && _selectedLoan!.monthlyPayment > 0) {
      _amountController.text = _selectedLoan!.monthlyPayment.toInt().toString();
    }

    // Load clients & loans on init
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(cashierNotifierProvider.notifier).fetchClients();
      ref.read(loanNotifierProvider.notifier).fetchLoans();
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
    // 1. Accounts
    final rawAccounts = (client.accounts ?? []).whereType<Map>().toList();
    final accounts = rawAccounts
        .map((a) => AccountModel.fromJson(Map<String, dynamic>.from(a)))
        .where((a) => a.status.toLowerCase() == 'active')
        .toList();

    // 2. Active Loans (Disbursed / In repayment)
    final allLoans = ref.read(loanNotifierProvider).loans;
    final Map<int, LoanModel> loanMap = {};

    if (client.loans != null) {
      for (final l in client.loans!) {
        if (l is LoanModel) {
          if (l.isDisbursed || l.status == 'active' || l.status == 'disbursed') {
            loanMap[l.id] = l;
          }
        } else if (l is Map) {
          final model = LoanModel.fromJson(Map<String, dynamic>.from(l));
          if (model.isDisbursed || model.status == 'active' || model.status == 'disbursed') {
            loanMap[model.id] = model;
          }
        }
      }
    }

    for (final l in allLoans) {
      if (l.clientId == client.id && (l.isDisbursed || l.status == 'active' || l.status == 'disbursed')) {
        loanMap[l.id] = l;
      }
    }

    final activeLoans = loanMap.values.toList();

    if (accounts.isEmpty && activeLoans.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Ce client n\'a aucun compte actif ni de prêt en cours de remboursement.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    if (accounts.length == 1 && activeLoans.isEmpty) {
      // Single account: select directly
      setState(() {
        _selectedClient = client;
        _selectedAccount = accounts.first;
        _selectedLoan = null;
        _clientAccounts = accounts;
        _clientLoans = [];
      });
    } else if (accounts.isEmpty && activeLoans.length == 1) {
      // Single loan: select directly
      final singleLoan = activeLoans.first;
      setState(() {
        _selectedClient = client;
        _selectedLoan = singleLoan;
        _selectedAccount = null;
        _clientAccounts = [];
        _clientLoans = activeLoans;
        if (singleLoan.monthlyPayment > 0) {
          _amountController.text = singleLoan.monthlyPayment.round().toString();
        }
      });
    } else {
      // Multiple destinations: show selection
      setState(() {
        _selectedClient = client;
        _clientAccounts = accounts;
        _clientLoans = activeLoans;
        _selectedAccount = null;
        _selectedLoan = null;
      });
    }
  }

  void _selectAccount(AccountModel account) {
    setState(() {
      _selectedAccount = account;
      _selectedLoan = null;
    });
  }

  void _selectLoan(LoanModel loan) {
    setState(() {
      _selectedLoan = loan;
      _selectedAccount = null;
      if (loan.monthlyPayment > 0) {
        _amountController.text = loan.monthlyPayment.round().toString();
      }
    });
  }

  void _resetSelection() {
    setState(() {
      _selectedAccount = null;
      _selectedLoan = null;
      _selectedClient = null;
      _clientAccounts = [];
      _clientLoans = [];
      _amountController.clear();
      _noteController.clear();
    });
  }

  Future<void> _submitDeposit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedAccount == null && _selectedLoan == null) return;

    final amount = double.tryParse(_amountController.text.replaceAll(' ', '')) ?? 0.0;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez entrer un montant valide.')),
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

    if (_selectedLoan != null) {
      // LOAN REPAYMENT FLOW
      setState(() => _isProcessingLoan = true);
      final loan = _selectedLoan!;
      final res = await ref.read(loanNotifierProvider.notifier).repayLoan(
        loanId: loan.id,
        amount: amount,
        paymentMethod: 'cash',
        notes: _noteController.text.trim().isNotEmpty
            ? _noteController.text.trim()
            : 'Remboursement prêt ${loan.loanNumber}',
      );

      if (!mounted) return;
      setState(() => _isProcessingLoan = false);

      if (res != null) {
        // Refresh state
        ref.read(loanNotifierProvider.notifier).fetchLoans();
        ref.read(cashierNotifierProvider.notifier).fetchClients();
        ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();

        // Create transaction model for receipt
        TransactionModel tx;
        if (res['transaction'] is Map) {
          tx = TransactionModel.fromJson(Map<String, dynamic>.from(res['transaction']));
        } else {
          tx = TransactionModel(
            id: DateTime.now().millisecondsSinceEpoch,
            reference: 'REM-${DateTime.now().millisecondsSinceEpoch.toString().substring(7)}',
            transactionType: 'deposit',
            amount: amount,
            status: 'completed',
            description: 'Remboursement prêt N° ${loan.loanNumber}',
            accountType: 'Prêt & Crédit',
            accountNumber: loan.loanNumber,
            clientName: _selectedClient?.fullName ?? loan.client?.fullName ?? 'Client',
            createdAt: DateTime.now(),
          );
        }

        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => ReceiptScreen(transaction: tx),
          ),
        );
      } else {
        final err = ref.read(loanNotifierProvider).errorMessage;
        final shouldQueueOffline = await _askSaveOffline(
          context,
          title: 'Serveur indisponible',
          message: 'Impossible de joindre le serveur pour le remboursement. Souhaitez-vous enregistrer cette opération en Mode Hors-Ligne (reçu provisoire et synchronisation automatique) ?',
        );

        if (shouldQueueOffline == true && mounted) {
          final offlineTx = await ref.read(syncNotifierProvider.notifier).queueOfflineOperation(
            type: 'deposit',
            accountId: _selectedAccount?.id ?? 0,
            loanId: loan.id,
            amount: amount,
            clientName: _selectedClient?.fullName ?? loan.client?.fullName ?? 'Client',
            accountNumber: loan.loanNumber,
            description: _noteController.text.trim().isNotEmpty
                ? _noteController.text.trim()
                : 'Remboursement prêt ${loan.loanNumber}',
          );
          if (mounted) {
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (_) => ReceiptScreen(transaction: offlineTx)),
            );
          }
        } else if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(err ?? 'Erreur lors du remboursement du prêt.'),
              backgroundColor: AppColors.danger,
            ),
          );
        }
      }
    } else if (_selectedAccount != null) {
      // STANDARD ACCOUNT DEPOSIT FLOW
      final cashierNotifier = ref.read(cashierNotifierProvider.notifier);
      final transaction = await cashierNotifier.processDeposit(
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
        final err = ref.read(cashierNotifierProvider).errorMessage;
        final shouldQueueOffline = await _askSaveOffline(
          context,
          title: 'Serveur ou Réseau indisponible',
          message: 'Impossible de joindre le serveur pour valider l\'encaissement. Souhaitez-vous enregistrer ce dépôt en Mode Hors-Ligne (reçu provisoire et synchronisation automatique) ?',
        );

        if (shouldQueueOffline == true && mounted) {
          final offlineTx = await ref.read(syncNotifierProvider.notifier).queueOfflineOperation(
            type: 'deposit',
            accountId: _selectedAccount!.id,
            amount: amount,
            clientName: _selectedClient?.fullName ?? _selectedAccount!.clientName,
            accountNumber: _selectedAccount!.accountNumber,
            description: _noteController.text.trim().isNotEmpty
                ? _noteController.text.trim()
                : 'Encaissement sur ${_selectedAccount!.accountNumber}',
          );
          if (mounted) {
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (_) => ReceiptScreen(transaction: offlineTx)),
            );
          }
        } else if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(err ?? 'Erreur lors de l\'encaissement.'),
              backgroundColor: AppColors.danger,
            ),
          );
        }
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
              backgroundColor: AppColors.success,
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
    final hasSelection = widget.preselectedAccount != null ||
        widget.preselectedLoan != null ||
        _selectedAccount != null ||
        _selectedLoan != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FB),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        foregroundColor: const Color(0xFF1D2A3A),
        elevation: 0,
        title: Text(_selectedLoan != null
            ? 'Remboursement de prêt'
            : _selectedAccount != null
                ? 'Encaissement sur compte'
                : _selectedClient != null
                    ? 'Opération pour ${_selectedClient!.fullName}'
                    : 'Encaissement (Dépôt & Prêt)'),
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft),
          onPressed: () {
            if (hasSelection && widget.preselectedAccount == null && widget.preselectedLoan == null) {
              if (_clientAccounts.length + _clientLoans.length > 1) {
                setState(() {
                  _selectedAccount = null;
                  _selectedLoan = null;
                });
              } else {
                _resetSelection();
              }
            } else if (_selectedClient != null && widget.preselectedAccount == null && widget.preselectedLoan == null) {
              _resetSelection();
            } else {
              Navigator.of(context).pop();
            }
          },
        ),
      ),
      body: hasSelection
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
        // Search bar
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

        // Client count
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

        // List
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
                      onRefresh: () async {
                        await ref.read(cashierNotifierProvider.notifier).fetchClients();
                        await ref.read(loanNotifierProvider.notifier).fetchLoans();
                      },
                      color: AppColors.primary,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        itemCount: clients.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 6),
                        itemBuilder: (_, index) {
                          final client = clients[index];
                          final accountCount = (client.accounts ?? []).whereType<Map>().length;
                          final hasActiveLoans = client.hasActiveLoan;

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
                                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                                      child: Text(
                                        client.firstName.isNotEmpty
                                            ? client.firstName[0].toUpperCase()
                                            : 'C',
                                        style: const TextStyle(
                                          color: AppColors.primaryDark,
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
                                          Row(
                                            children: [
                                              Flexible(
                                                child: Text(
                                                  client.fullName,
                                                  style: const TextStyle(
                                                    fontSize: 14,
                                                    fontWeight: FontWeight.w700,
                                                    color: Color(0xFF0F172A),
                                                  ),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                              if (hasActiveLoans) ...[
                                                const SizedBox(width: 6),
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: const Color(0xFFEEF2FF),
                                                    borderRadius: BorderRadius.circular(6),
                                                  ),
                                                  child: const Text(
                                                    'Prêt actif',
                                                    style: TextStyle(
                                                      color: Color(0xFF4F46E5),
                                                      fontSize: 9.5,
                                                      fontWeight: FontWeight.w800,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ],
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
  // STEP 2: Account & Loan Selector
  // ─────────────────────────────────────────────
  Widget _buildAccountSelector() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Client header card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.primary.withValues(alpha: 0.3)),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                  child: Text(
                    _selectedClient!.firstName.isNotEmpty
                        ? _selectedClient!.firstName[0].toUpperCase()
                        : 'C',
                    style: const TextStyle(
                      color: AppColors.primaryDark,
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

          // SECTION A: ACTIVE LOANS (If any)
          if (_clientLoans.isNotEmpty) ...[
            const Row(
              children: [
                Icon(LucideIcons.handCoins, size: 15, color: Color(0xFF4F46E5)),
                SizedBox(width: 6),
                Text(
                  'REMBOURSEMENTS DE PRÊTS EN COURS',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF4F46E5),
                    letterSpacing: 0.8,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            ..._clientLoans.map((loan) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Material(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(16),
                      onTap: () => _selectLoan(loan),
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFC7D2FE), width: 1.5),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: const Color(0xFF4F46E5).withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(LucideIcons.handCoins, color: Color(0xFF4F46E5), size: 20),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Flexible(
                                        child: Text(
                                          'Dossier ${loan.loanNumber}',
                                          style: const TextStyle(
                                            fontSize: 14,
                                            fontWeight: FontWeight.w800,
                                            color: Color(0xFF0F172A),
                                          ),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Échéance : ${CurrencyFormatter.formatSimple(loan.monthlyPayment)} / mois',
                                    style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                                  ),
                                ],
                              ),
                            ),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  CurrencyFormatter.formatSimple(loan.remainingDue),
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800,
                                    color: Color(0xFFEF4444),
                                  ),
                                ),
                                const SizedBox(height: 2),
                                const Text(
                                  'Reste dû',
                                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF94A3B8)),
                                ),
                              ],
                            ),
                            const SizedBox(width: 6),
                            const Icon(LucideIcons.arrowDownLeft, size: 18, color: Color(0xFF4F46E5)),
                          ],
                        ),
                      ),
                    ),
                  ),
                )),
            const SizedBox(height: 12),
          ],

          // SECTION B: STANDARD ACCOUNTS
          if (_clientAccounts.isNotEmpty) ...[
            const Row(
              children: [
                Icon(LucideIcons.wallet, size: 15, color: Color(0xFF72809A)),
                SizedBox(width: 6),
                Text(
                  'COMPTES D\'ÉPARGNE & TONTINE',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF72809A),
                    letterSpacing: 0.8,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Account cards
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
                                color: AppColors.success.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(LucideIcons.wallet, color: AppColors.success, size: 20),
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
                                  'Solde',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF94A3B8),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(width: 6),
                            const Icon(LucideIcons.arrowDownLeft, size: 18, color: AppColors.success),
                          ],
                        ),
                      ),
                    ),
                  ),
                )),
          ],
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // STEP 3: Amount Form (Accounts or Loans)
  // ─────────────────────────────────────────────
  Widget _buildAmountForm(CashierState cashier) {
    final isLoan = _selectedLoan != null;
    final primaryColor = isLoan ? const Color(0xFF4F46E5) : AppColors.success;
    final isSubmitting = isLoan ? _isProcessingLoan : cashier.isLoading;

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
                        'Attention : Votre session de caisse est fermée. Ouvrez la session journalière pour pouvoir valider un encaissement.',
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

            // Target Details Card (Loan or Account)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: primaryColor.withValues(alpha: 0.4), width: 1.5),
                boxShadow: [
                  BoxShadow(
                    color: primaryColor.withValues(alpha: 0.05),
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
                      Flexible(
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                          decoration: BoxDecoration(
                            color: primaryColor.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(isLoan ? LucideIcons.handCoins : LucideIcons.wallet, color: primaryColor, size: 14),
                              const SizedBox(width: 5),
                              Flexible(
                                child: Text(
                                  isLoan ? 'Remboursement de prêt' : _selectedAccount!.typeLabel,
                                  style: TextStyle(
                                    color: primaryColor,
                                    fontSize: 11.5,
                                    fontWeight: FontWeight.w800,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        isLoan ? _selectedLoan!.loanNumber : _selectedAccount!.accountNumber,
                        style: const TextStyle(
                          color: Color(0xFF1D2A3A),
                          fontSize: 12.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Text(
                    _selectedClient?.fullName ??
                        (isLoan ? _selectedLoan!.client?.fullName : _selectedAccount!.clientName) ??
                        'Client',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1D2A3A),
                    ),
                  ),
                  if (_selectedClient?.phone != null ||
                      (isLoan ? _selectedLoan!.client?.phone : _selectedAccount!.clientPhone) != null)
                    Text(
                      _selectedClient?.phone ??
                          (isLoan ? _selectedLoan!.client?.phone : _selectedAccount!.clientPhone!) ??
                          '',
                      style: const TextStyle(
                        fontSize: 13,
                        color: Color(0xFF72809A),
                      ),
                    ),
                  const Divider(color: Color(0xFFE7ECF5), height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        isLoan ? 'Reste à solder' : 'Solde Actuel',
                        style: const TextStyle(color: Color(0xFF72809A), fontSize: 13, fontWeight: FontWeight.w600),
                      ),
                      Text(
                        CurrencyFormatter.format(isLoan ? _selectedLoan!.remainingDue : _selectedAccount!.balance),
                        style: TextStyle(
                          color: isLoan ? const Color(0xFFEF4444) : const Color(0xFF1D2A3A),
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                  if (isLoan && _selectedLoan!.monthlyPayment > 0) ...[
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Échéance mensuelle',
                          style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                        ),
                        Text(
                          CurrencyFormatter.format(_selectedLoan!.monthlyPayment),
                          style: const TextStyle(color: Color(0xFF4F46E5), fontSize: 13, fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 22),

            // Amount Input
            CustomTextField(
              controller: _amountController,
              label: isLoan ? 'Montant du remboursement (FCFA)' : 'Montant à encaisser (FCFA)',
              hint: isLoan ? 'Ex: ${_selectedLoan!.monthlyPayment.toInt()}' : 'Ex: 10 000',
              prefixIcon: isLoan ? LucideIcons.coins : LucideIcons.banknote,
              lightTheme: true,
              keyboardType: TextInputType.number,
              validator: (val) {
                if (val == null || val.trim().isEmpty) return 'Montant obligatoire';
                final n = double.tryParse(val.replaceAll(' ', ''));
                if (n == null || n <= 0) return 'Montant invalide';
                if (isLoan && n > (_selectedLoan!.remainingDue + 100)) {
                  return 'Le montant dépasse le solde restant (${CurrencyFormatter.formatSimple(_selectedLoan!.remainingDue)})';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Quick Amount Chips
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (isLoan && _selectedLoan!.monthlyPayment > 0)
                  ActionChip(
                    label: Text('Mensualité (${CurrencyFormatter.formatSimple(_selectedLoan!.monthlyPayment)})'),
                    backgroundColor: const Color(0xFFEEF2FF),
                    side: const BorderSide(color: Color(0xFFC7D2FE)),
                    labelStyle: const TextStyle(color: Color(0xFF4F46E5), fontSize: 11.5, fontWeight: FontWeight.w700),
                    onPressed: () {
                      _amountController.text = _selectedLoan!.monthlyPayment.toInt().toString();
                    },
                  ),
                if (isLoan && _selectedLoan!.remainingDue > 0)
                  ActionChip(
                    label: Text('Totalité (${CurrencyFormatter.formatSimple(_selectedLoan!.remainingDue)})'),
                    backgroundColor: const Color(0xFFFEF2F2),
                    side: const BorderSide(color: Color(0xFFFCA5A5)),
                    labelStyle: const TextStyle(color: Color(0xFFDC2626), fontSize: 11.5, fontWeight: FontWeight.w700),
                    onPressed: () {
                      _amountController.text = _selectedLoan!.remainingDue.toInt().toString();
                    },
                  ),
                ...[1000, 2000, 5000, 10000, 25000, 50000].map((amt) {
                  return ActionChip(
                    label: Text('+ ${CurrencyFormatter.formatSimple(amt)}'),
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: Color(0xFFE7ECF5)),
                    labelStyle: TextStyle(color: primaryColor, fontSize: 12, fontWeight: FontWeight.w600),
                    onPressed: () {
                      _amountController.text = amt.toString();
                    },
                  );
                }),
              ],
            ),
            const SizedBox(height: 18),

            // Note
            CustomTextField(
              controller: _noteController,
              label: 'Motif / Description (Optionnel)',
              hint: isLoan ? 'Ex: Remboursement mensualité N°...' : 'Ex: Cotisation journalière',
              prefixIcon: LucideIcons.fileText,
              lightTheme: true,
            ),
            const SizedBox(height: 28),

            // Submit Button
            CustomButton(
              text: isLoan ? 'Valider le Remboursement du Prêt' : 'Valider l\'Encaissement',
              icon: LucideIcons.checkCircle,
              backgroundColor: primaryColor,
              isLoading: isSubmitting,
              onPressed: _submitDeposit,
            ),
          ],
        ),
      ),
    );
  }
}
