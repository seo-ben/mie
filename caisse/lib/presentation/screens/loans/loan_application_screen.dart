import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/client_model.dart';
import '../../../data/models/client_eligibility_model.dart';
import '../../../domain/notifiers/loan_notifier.dart';
import '../../../domain/notifiers/cashier_notifier.dart';

class LoanApplicationScreen extends ConsumerStatefulWidget {
  final ClientModel? preselectedClient;

  const LoanApplicationScreen({super.key, this.preselectedClient});

  @override
  ConsumerState<LoanApplicationScreen> createState() => _LoanApplicationScreenState();
}

class _LoanApplicationScreenState extends ConsumerState<LoanApplicationScreen> {
  ClientModel? _selectedClient;
  ClientEligibilityModel? _eligibility;
  bool _isLoadingEligibility = false;

  final TextEditingController _amountController = TextEditingController(text: '100000');
  final TextEditingController _purposeController = TextEditingController(text: 'Achat de marchandises / Fonds de roulement');
  final TextEditingController _collateralController = TextEditingController(text: 'Garantie morale & solde épargne');

  int _selectedDurationMonths = 6;
  double _annualRate = 17.0;
  Map<String, dynamic>? _loanConfig;
  Map<String, dynamic>? _simulation;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      _loadLoanConfig();
      if (widget.preselectedClient != null) {
        _selectedClient = widget.preselectedClient;
        await _checkEligibility(_selectedClient!.id);
      } else {
        ref.read(cashierNotifierProvider.notifier).fetchClients();
      }
      _runSimulation();
    });
  }

  Future<void> _loadLoanConfig() async {
    final cfg = await ref.read(loanNotifierProvider.notifier).getLoanConfig();
    if (mounted) {
      setState(() {
        _loanConfig = cfg;
        if (cfg['default_interest_rate'] != null && _eligibility == null) {
          _annualRate = double.tryParse(cfg['default_interest_rate'].toString()) ?? 17.0;
        }
      });
      _runSimulation();
    }
  }

  @override
  void dispose() {
    _amountController.dispose();
    _purposeController.dispose();
    _collateralController.dispose();
    super.dispose();
  }

  Future<void> _checkEligibility(int clientId) async {
    setState(() => _isLoadingEligibility = true);
    final res = await ref.read(loanNotifierProvider.notifier).checkClientEligibility(clientId);
    if (mounted) {
      setState(() {
        _eligibility = res;
        _isLoadingEligibility = false;
        if (res != null && res.suggestedInterestRate > 0) {
          _annualRate = res.suggestedInterestRate;
        }
      });
      _runSimulation();
    }
  }

  Future<void> _runSimulation() async {
    final amount = double.tryParse(_amountController.text.trim()) ?? 100000;
    final res = await ref.read(loanNotifierProvider.notifier).simulateLoan(
          amount: amount,
          durationMonths: _selectedDurationMonths,
          interestRate: _annualRate,
        );
    if (mounted) {
      setState(() => _simulation = res);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cashierState = ref.watch(cashierNotifierProvider);
    final clients = cashierState.clients;

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
        title: const Text(
          'Nouvelle Demande de Prêt',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 16,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: ElevatedButton.icon(
          onPressed: _isSubmitting || _selectedClient == null ? null : _submitApplication,
          icon: _isSubmitting
              ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Icon(LucideIcons.check, size: 18),
          label: const Text(
            'Enregistrer la Demande de Prêt',
            style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
          ),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF0F172A),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            elevation: 0,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Section 1: Client Selection & Search
            _buildClientSelection(clients),
            const SizedBox(height: 16),

            // Section 2: Financial Eligibility Card
            if (_selectedClient != null) ...[
              _buildEligibilityCard(),
              const SizedBox(height: 16),
            ],

            // Section 3: Loan Parameters
            _buildLoanParametersCard(),
            const SizedBox(height: 16),

            // Section 4: Live Simulation Card
            _buildSimulationCard(),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildClientSelection(List<ClientModel> clients) {
    return Container(
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
            '1. Client Bénéficiaire',
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 12),
          if (widget.preselectedClient != null)
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  const Icon(LucideIcons.user, size: 18, color: Color(0xFF6366F1)),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      '${_selectedClient?.fullName} • ${_selectedClient?.phone}',
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13, color: Color(0xFF0F172A)),
                    ),
                  ),
                ],
              ),
            )
          else
            DropdownButtonFormField<ClientModel>(
              value: _selectedClient,
              hint: const Text('Sélectionner un client...', style: TextStyle(fontSize: 13)),
              isExpanded: true,
              items: clients.map((c) {
                return DropdownMenuItem<ClientModel>(
                  value: c,
                  child: Text('${c.fullName} (${c.phone})', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                );
              }).toList(),
              onChanged: (val) {
                if (val != null) {
                  setState(() => _selectedClient = val);
                  _checkEligibility(val.id);
                  _runSimulation();
                }
              },
              decoration: InputDecoration(
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildEligibilityCard() {
    if (_isLoadingEligibility) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: const Color(0xFFE2E8F0))),
        child: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_eligibility == null) return const SizedBox.shrink();

    final e = _eligibility!;
    final isEligible = e.isEligible;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isEligible ? const Color(0xFFECFDF5) : const Color(0xFFFEF2F2),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isEligible ? const Color(0xFF10B981).withValues(alpha: 0.4) : const Color(0xFFEF4444).withValues(alpha: 0.4),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(
                    isEligible ? LucideIcons.shieldCheck : LucideIcons.shieldAlert,
                    color: isEligible ? const Color(0xFF059669) : const Color(0xFFDC2626),
                    size: 20,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    isEligible ? 'Client Éligible au Prêt' : 'Éligibilité Restreinte',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w900,
                      color: isEligible ? const Color(0xFF065F46) : const Color(0xFF991B1B),
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isEligible ? const Color(0xFF10B981).withValues(alpha: 0.2) : const Color(0xFFEF4444).withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  'Score: ${e.eligibilityScore.toInt()}/100',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: isEligible ? const Color(0xFF047857) : const Color(0xFFB91C1C),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _eligibilityMetric('Épargne cumulée', CurrencyFormatter.format(e.savingsBalance)),
              ),
              Expanded(
                child: _eligibilityMetric('Tontine active', CurrencyFormatter.format(e.tontineBalance)),
              ),
              Expanded(
                child: _eligibilityMetric('Plafond conseillé', CurrencyFormatter.format(e.maxBorrowingCapacity)),
              ),
            ],
          ),
          if (e.criteria.isNotEmpty) ...[
            const Divider(height: 18, color: Color(0xFFE2E8F0)),
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: e.criteria.map((c) {
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: c.passed ? const Color(0xFFD1FAE5) : const Color(0xFFFEE2E2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        c.passed ? LucideIcons.check : LucideIcons.x,
                        size: 11,
                        color: c.passed ? const Color(0xFF065F46) : const Color(0xFF991B1B),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        c.label,
                        style: TextStyle(
                          fontSize: 10.5,
                          fontWeight: FontWeight.w700,
                          color: c.passed ? const Color(0xFF065F46) : const Color(0xFF991B1B),
                        ),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ],
        ],
      ),
    );
  }

  Widget _eligibilityMetric(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 10, color: Color(0xFF64748B), fontWeight: FontWeight.w600)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
      ],
    );
  }

  Widget _buildLoanParametersCard() {
    return Container(
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
            '2. Paramètres du Crédit',
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 14),

          // Montant demandé
          const Text('Montant Demandé (FCFA)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF475569))),
          const SizedBox(height: 6),
          TextField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            onChanged: (_) => _runSimulation(),
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
            decoration: InputDecoration(
              prefixIcon: const Icon(LucideIcons.banknote, color: Color(0xFF6366F1)),
              suffixText: 'FCFA',
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            ),
          ),
          const SizedBox(height: 10),

          // Quick Chips for Amount
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [50000, 100000, 250000, 500000, 1000000].map((val) {
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: ActionChip(
                    label: Text('${CurrencyFormatter.formatNumber(val)} F', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
                    backgroundColor: const Color(0xFFF1F5F9),
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                    onPressed: () {
                      _amountController.text = val.toString();
                      _runSimulation();
                    },
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 16),

          // Durée du prêt
          const Text('Durée de Remboursement', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF475569))),
          const SizedBox(height: 6),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [1, 3, 6, 12, 18, 24].map((m) {
                final isSel = _selectedDurationMonths == m;
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: ChoiceChip(
                    label: Text('$m Mois', style: TextStyle(fontSize: 11.5, fontWeight: isSel ? FontWeight.w800 : FontWeight.w600, color: isSel ? Colors.white : const Color(0xFF334155))),
                    selected: isSel,
                    selectedColor: const Color(0xFF0F172A),
                    onSelected: (val) {
                      if (val) {
                        setState(() => _selectedDurationMonths = m);
                        _runSimulation();
                      }
                    },
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 16),

          // Taux d'intérêt annuel (Configuration Admin & Profil Client)
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Taux d\'Intérêt Annuel', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF475569))),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFBFDBFE)),
                ),
                child: Text(
                  '$_annualRate% / an (Paramètre Système)',
                  style: const TextStyle(color: Color(0xFF2563EB), fontSize: 11, fontWeight: FontWeight.w800),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                12.0, 17.0, 20.0, 25.0
              ].map((rate) {
                final isSel = (_annualRate - rate).abs() < 0.01;
                final label = rate == 17.0 ? '17% (Défaut Admin)' : '$rate%';
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: ChoiceChip(
                    label: Text(label, style: TextStyle(fontSize: 11, fontWeight: isSel ? FontWeight.w800 : FontWeight.w600, color: isSel ? Colors.white : const Color(0xFF334155))),
                    selected: isSel,
                    selectedColor: const Color(0xFF2563EB),
                    onSelected: (val) {
                      if (val) {
                        setState(() => _annualRate = rate);
                        _runSimulation();
                      }
                    },
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 16),

          // Motif et Garantie
          const Text('Motif / Activité', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF475569))),
          const SizedBox(height: 6),
          TextField(
            controller: _purposeController,
            decoration: InputDecoration(
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            ),
          ),
          const SizedBox(height: 14),
          const Text('Garantie / Sûreté', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF475569))),
          const SizedBox(height: 6),
          TextField(
            controller: _collateralController,
            decoration: InputDecoration(
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSimulationCard() {
    if (_simulation == null) return const SizedBox.shrink();

    final monthly = double.tryParse(_simulation!['monthly_payment']?.toString() ?? '0') ?? 0.0;
    final totalDue = double.tryParse(_simulation!['total_amount_due']?.toString() ?? '0') ?? 0.0;
    final totalInterest = double.tryParse(_simulation!['total_interest']?.toString() ?? '0') ?? 0.0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'SIMULATION FINANCIÈRE',
                style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1),
              ),
              Text(
                '$_annualRate% / an',
                style: const TextStyle(color: Color(0xFF38BDF8), fontSize: 11.5, fontWeight: FontWeight.w800),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Mensualité estimée', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                    const SizedBox(height: 2),
                    Text(
                      CurrencyFormatter.format(monthly),
                      style: const TextStyle(color: Color(0xFF34D399), fontSize: 17, fontWeight: FontWeight.w900),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Total Intérêts', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                    const SizedBox(height: 2),
                    Text(
                      CurrencyFormatter.format(totalInterest),
                      style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const Divider(color: Colors.white12, height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Total à rembourser :', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w600)),
              Text(
                CurrencyFormatter.format(totalDue),
                style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w900),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _submitApplication() async {
    if (_selectedClient == null) return;
    final amount = double.tryParse(_amountController.text.trim()) ?? 0.0;
    if (amount < 10000) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Le montant minimum d\'un prêt est de 10 000 FCFA'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    final loan = await ref.read(loanNotifierProvider.notifier).applyLoan(
          clientId: _selectedClient!.id,
          requestedAmount: amount,
          durationMonths: _selectedDurationMonths,
          purpose: _purposeController.text.trim(),
          collateralDescription: _collateralController.text.trim(),
          interestRate: _annualRate,
        );

    setState(() => _isSubmitting = false);

    if (mounted) {
      if (loan != null) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Demande de prêt N° ${loan.loanNumber} enregistrée avec succès !'),
            backgroundColor: const Color(0xFF10B981),
          ),
        );
      } else {
        final err = ref.read(loanNotifierProvider).errorMessage ?? 'Erreur lors de l\'enregistrement';
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(err), backgroundColor: Colors.red),
        );
      }
    }
  }
}
