import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/loan_model.dart';
import '../../../domain/notifiers/loan_notifier.dart';
import '../../../domain/notifiers/cashier_notifier.dart';

class LoanDetailScreen extends ConsumerStatefulWidget {
  final LoanModel loan;
  final bool autoOpenRepay;

  const LoanDetailScreen({
    super.key,
    required this.loan,
    this.autoOpenRepay = false,
  });

  @override
  ConsumerState<LoanDetailScreen> createState() => _LoanDetailScreenState();
}

class _LoanDetailScreenState extends ConsumerState<LoanDetailScreen> {
  late LoanModel _currentLoan;
  List<LoanPaymentModel> _schedule = [];
  bool _isLoadingSchedule = true;

  @override
  void initState() {
    super.initState();
    _currentLoan = widget.loan;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadSchedule();
      if (widget.autoOpenRepay && _currentLoan.isDisbursed) {
        _showRepayDialog(context);
      }
    });
  }

  Future<void> _loadSchedule() async {
    setState(() => _isLoadingSchedule = true);
    final list = await ref.read(loanNotifierProvider.notifier).getLoanSchedule(_currentLoan.id);
    if (mounted) {
      setState(() {
        _schedule = list;
        _isLoadingSchedule = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final clientName = _currentLoan.client?.fullName ?? 'Client';
    final clientPhone = _currentLoan.client?.phone ?? '';
    final progress = _currentLoan.repaymentProgress;

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
              _currentLoan.loanNumber,
              style: const TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 16,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              clientName,
              style: const TextStyle(
                color: Color(0xFF64748B),
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(LucideIcons.refreshCw, size: 18, color: Color(0xFF1E293B)),
            onPressed: _loadSchedule,
          ),
        ],
      ),
      bottomNavigationBar: _buildBottomActions(context),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 30),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status & Overview Card
            _buildOverviewCard(clientName, clientPhone, progress),
            const SizedBox(height: 16),

            // Financial Summary Grid
            _buildFinancialBreakdown(),
            const SizedBox(height: 16),

            // Loan Parameters & Purpose
            _buildParametersCard(),
            const SizedBox(height: 20),

            // Amortization Schedule Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'ÉCHÉANCIER DE REMBOURSEMENT',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF64748B),
                    letterSpacing: 1,
                  ),
                ),
                Text(
                  '${_schedule.length} mensualités',
                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 10),

            // Schedule List
            _buildScheduleSection(),
          ],
        ),
      ),
    );
  }

  Widget _buildOverviewCard(String clientName, String clientPhone, double progress) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0F172A).withValues(alpha: 0.15),
            blurRadius: 14,
            offset: const Offset(0, 6),
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
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Center(
                      child: Icon(LucideIcons.userCheck, color: Color(0xFF38BDF8), size: 18),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        clientName,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      Text(
                        clientPhone,
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.6),
                          fontSize: 11.5,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              _buildStatusBadge(_currentLoan.status),
            ],
          ),
          const SizedBox(height: 18),
          const Text(
            'SOLDE RESTANT DÛ',
            style: TextStyle(
              color: Color(0xFF94A3B8),
              fontSize: 10.5,
              fontWeight: FontWeight.w800,
              letterSpacing: 1,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            CurrencyFormatter.format(_currentLoan.remainingDue),
            style: TextStyle(
              color: _currentLoan.isCompleted ? const Color(0xFF34D399) : Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Remboursé : ${CurrencyFormatter.format(_currentLoan.totalPaid)}',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.75),
                  fontSize: 11.5,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Text(
                '${(progress * 100).toInt()}%',
                style: const TextStyle(
                  color: Color(0xFF38BDF8),
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 6,
              backgroundColor: Colors.white.withValues(alpha: 0.1),
              valueColor: AlwaysStoppedAnimation<Color>(
                _currentLoan.isCompleted ? const Color(0xFF34D399) : const Color(0xFF38BDF8),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFinancialBreakdown() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          _infoRow('Capital Demandé', CurrencyFormatter.format(_currentLoan.requestedAmount)),
          const Divider(color: Color(0xFFF1F5F9), height: 16),
          _infoRow('Capital Approuvé', CurrencyFormatter.format(_currentLoan.effectiveAmount), isBold: true),
          const Divider(color: Color(0xFFF1F5F9), height: 16),
          _infoRow('Taux d\'intérêt annuel', '${_currentLoan.interestRate}% / an'),
          const Divider(color: Color(0xFFF1F5F9), height: 16),
          _infoRow('Mensualité estimée', CurrencyFormatter.format(_currentLoan.monthlyPayment)),
          const Divider(color: Color(0xFFF1F5F9), height: 16),
          _infoRow('Total Intérêts + Principal', CurrencyFormatter.format(_currentLoan.effectiveTotalDue), isBold: true, color: const Color(0xFF0F172A)),
        ],
      ),
    );
  }

  Widget _buildParametersCard() {
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
            'Détails & Garanties du dossier',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 12),
          _infoRow('Durée de remboursement', '${_currentLoan.durationMonths} Mois'),
          const SizedBox(height: 8),
          _infoRow('Motif du prêt', _currentLoan.purpose ?? 'Activité commerciale / Fonds de roulement'),
          const SizedBox(height: 8),
          _infoRow('Garantie / Sûreté', _currentLoan.collateralDescription ?? 'Garantie morale & épargne client'),
          if (_currentLoan.disbursedAt != null) ...[
            const SizedBox(height: 8),
            _infoRow('Date de décaissement', _currentLoan.disbursedAt.toString().split(' ')[0]),
          ],
        ],
      ),
    );
  }

  Widget _buildScheduleSection() {
    if (_isLoadingSchedule) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (_schedule.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: const Center(
          child: Text(
            'L\'échéancier sera généré dès l\'approbation du prêt.',
            style: TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8)),
          ),
        ),
      );
    }

    return Column(
      children: _schedule.map((p) {
        Color badgeBg;
        Color badgeColor;
        String badgeLabel;

        if (p.isPaid) {
          badgeBg = const Color(0xFFD1FAE5);
          badgeColor = const Color(0xFF065F46);
          badgeLabel = 'Payé';
        } else if (p.isOverdue) {
          badgeBg = const Color(0xFFFEE2E2);
          badgeColor = const Color(0xFF991B1B);
          badgeLabel = 'En retard';
        } else if (p.isPartial) {
          badgeBg = const Color(0xFFFEF3C7);
          badgeColor = const Color(0xFF92400E);
          badgeLabel = 'Partiel';
        } else {
          badgeBg = const Color(0xFFF1F5F9);
          badgeColor = const Color(0xFF475569);
          badgeLabel = 'En attente';
        }

        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Row(
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Center(
                  child: Text(
                    '#${p.paymentNumber}',
                    style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF475569)),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Échéance du ${p.dueDate}',
                      style: const TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    Text(
                      'Principal: ${CurrencyFormatter.format(p.principalAmount)} • Intérêts: ${CurrencyFormatter.format(p.interestAmount)}',
                      style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    CurrencyFormatter.format(p.expectedAmount),
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  if (p.isPartial && p.paidAmount > 0)
                    Text(
                      'Payé: ${CurrencyFormatter.format(p.paidAmount)}',
                      style: const TextStyle(fontSize: 10, color: Color(0xFF059669), fontWeight: FontWeight.w700),
                    ),
                  const SizedBox(height: 2),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(color: badgeBg, borderRadius: BorderRadius.circular(6)),
                    child: Text(
                      badgeLabel,
                      style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w800, color: badgeColor),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget? _buildBottomActions(BuildContext context) {
    if (_currentLoan.isPending) {
      return Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: ElevatedButton.icon(
          onPressed: () => _showApproveDialog(context),
          icon: const Icon(LucideIcons.checkCheck, size: 16),
          label: const Text('Approuver et Valider ce Prêt', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800)),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF2563EB),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            elevation: 0,
          ),
        ),
      );
    }

    if (_currentLoan.isApproved) {
      return Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: ElevatedButton.icon(
          onPressed: () {
            final isSessionOpen = ref.read(cashierNotifierProvider).isSessionOpen;
            if (!isSessionOpen) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Opération refusée : Votre session de caisse est fermée. Veuillez d\'abord ouvrir votre caisse.'),
                  backgroundColor: Color(0xFFEF4444),
                ),
              );
              return;
            }
            _showDisburseDialog(context);
          },
          icon: const Icon(LucideIcons.arrowUpRight, size: 16),
          label: const Text('Décaisser les Espèces au Client', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800)),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF059669),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            elevation: 0,
          ),
        ),
      );
    }

    if (_currentLoan.isDisbursed && !_currentLoan.isCompleted) {
      return Container(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
        ),
        child: ElevatedButton.icon(
          onPressed: () {
            final isSessionOpen = ref.read(cashierNotifierProvider).isSessionOpen;
            if (!isSessionOpen) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Opération refusée : Votre session de caisse est fermée. Veuillez d\'abord ouvrir votre caisse.'),
                  backgroundColor: Color(0xFFEF4444),
                ),
              );
              return;
            }
            _showRepayDialog(context);
          },
          icon: const Icon(LucideIcons.arrowDownLeft, size: 16),
          label: const Text('Encaisser un Remboursement', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800)),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF0F172A),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            elevation: 0,
          ),
        ),
      );
    }

    return null;
  }

  void _showApproveDialog(BuildContext context) {
    final amountController = TextEditingController(text: _currentLoan.requestedAmount.toInt().toString());
    final notesController = TextEditingController();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                ),
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 30),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(2)))),
                    const SizedBox(height: 16),
                    const Text(
                      'Approbation du Prêt',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 4),
                    const Text('Validez le montant accordé et générez l\'échéancier officiel.', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                    const SizedBox(height: 18),
                    const Text('Montant Approuvé (FCFA)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF334155))),
                    const SizedBox(height: 8),
                    TextField(
                      controller: amountController,
                      keyboardType: TextInputType.number,
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
                      decoration: InputDecoration(
                        suffixText: 'FCFA',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 14),
                    const Text('Notes d\'approbation', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF334155))),
                    const SizedBox(height: 8),
                    TextField(
                      controller: notesController,
                      maxLines: 2,
                      decoration: InputDecoration(
                        hintText: 'Ex: Dossier complet, garant validé...',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final approvedAmount = double.tryParse(amountController.text.trim()) ?? _currentLoan.requestedAmount;
                                setModalState(() => isSubmitting = true);
                                final updated = await ref.read(loanNotifierProvider.notifier).approveLoan(
                                      loanId: _currentLoan.id,
                                      approvedAmount: approvedAmount,
                                      notes: notesController.text.trim(),
                                    );
                                if (!context.mounted) return;
                                Navigator.pop(ctx);
                                if (updated != null) {
                                  setState(() => _currentLoan = updated);
                                  _loadSchedule();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Prêt approuvé avec succès ! Prêt pour décaissement.'), backgroundColor: Color(0xFF2563EB)),
                                  );
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF2563EB),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: isSubmitting ? const CircularProgressIndicator(color: Colors.white) : const Text('Confirmer l\'Approbation', style: TextStyle(fontWeight: FontWeight.w800)),
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

  void _showDisburseDialog(BuildContext context) {
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 30),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(2)))),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(color: const Color(0xFF059669).withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
                        child: const Icon(LucideIcons.banknote, color: Color(0xFF059669), size: 22),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Décaissement Espèces', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A))),
                            Text('Remise des fonds physiques au client', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      children: [
                        _infoRow('Client bénéficiaire :', _currentLoan.client?.fullName ?? ''),
                        const SizedBox(height: 8),
                        _infoRow('N° de prêt :', _currentLoan.loanNumber),
                        const Divider(height: 16),
                        _infoRow('Montant net à décaisser :', CurrencyFormatter.format(_currentLoan.effectiveAmount), isBold: true, color: const Color(0xFF059669)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 22),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: isSubmitting
                          ? null
                          : () async {
                              setModalState(() => isSubmitting = true);
                              final res = await ref.read(loanNotifierProvider.notifier).disburseLoan(loanId: _currentLoan.id);
                              // Refresh dashboard stats so cashier cash balance updates instantly
                              await ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                              if (!context.mounted) return;
                              Navigator.pop(ctx);
                              if (res != null) {
                                ref.read(loanNotifierProvider.notifier).fetchLoans();
                                Navigator.pop(context);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text('Fonds décaissés ! ${CurrencyFormatter.format(_currentLoan.effectiveAmount)} remis au client.'),
                                    backgroundColor: const Color(0xFF059669),
                                  ),
                                );
                              }
                            },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF059669),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: isSubmitting ? const CircularProgressIndicator(color: Colors.white) : const Text('Confirmer le Décaissement et Imprimer Reçu', style: TextStyle(fontWeight: FontWeight.w800)),
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

  void _showRepayDialog(BuildContext context) {
    double suggestedAmount = _currentLoan.monthlyPayment;
    for (final p in _schedule) {
      if (!p.isPaid) {
        final rem = p.expectedAmount - p.paidAmount;
        if (rem > 0) {
          suggestedAmount = rem;
          break;
        }
      }
    }
    if (suggestedAmount <= 0) suggestedAmount = _currentLoan.monthlyPayment;

    final amountController = TextEditingController(
      text: suggestedAmount > 0 ? suggestedAmount.round().toString() : '5000',
    );
    final notesController = TextEditingController();
    bool isSubmitting = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                ),
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 30),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(child: Container(width: 40, height: 4, decoration: BoxDecoration(color: const Color(0xFFCBD5E1), borderRadius: BorderRadius.circular(2)))),
                    const SizedBox(height: 16),
                    const Text('Encaissement Remboursement', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A))),
                    const SizedBox(height: 4),
                    Text('Reste dû actuel : ${CurrencyFormatter.format(_currentLoan.remainingDue)}', style: const TextStyle(fontSize: 12, color: Color(0xFFD97706), fontWeight: FontWeight.w700)),
                    const SizedBox(height: 18),
                    const Text('Montant versé par le client (FCFA)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF334155))),
                    const SizedBox(height: 8),
                    TextField(
                      controller: amountController,
                      keyboardType: TextInputType.number,
                      style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900),
                      decoration: InputDecoration(
                        prefixIcon: const Icon(LucideIcons.banknote, color: Color(0xFF10B981)),
                        suffixText: 'FCFA',
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final amount = double.tryParse(amountController.text.trim()) ?? 0.0;
                                setModalState(() => isSubmitting = true);
                                final res = await ref.read(loanNotifierProvider.notifier).repayLoan(
                                      loanId: _currentLoan.id,
                                      amount: amount,
                                      notes: notesController.text.trim(),
                                    );
                                await ref.read(cashierNotifierProvider.notifier).fetchDashboardStats();
                                if (!context.mounted) return;
                                Navigator.pop(ctx);
                                if (res != null) {
                                  _loadSchedule();
                                  ref.read(loanNotifierProvider.notifier).fetchLoans();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text('Remboursement de ${CurrencyFormatter.format(amount)} enregistré avec succès.'),
                                      backgroundColor: const Color(0xFF0F172A),
                                    ),
                                  );
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF0F172A),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: isSubmitting ? const CircularProgressIndicator(color: Colors.white) : const Text('Valider le Remboursement', style: TextStyle(fontWeight: FontWeight.w800)),
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

  Widget _infoRow(String label, String value, {bool isBold = false, Color? color}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: isBold ? 13 : 12,
            fontWeight: isBold ? FontWeight.w800 : FontWeight.w500,
            color: const Color(0xFF64748B),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            value,
            textAlign: TextAlign.end,
            style: TextStyle(
              fontSize: isBold ? 13.5 : 12.5,
              fontWeight: isBold ? FontWeight.w900 : FontWeight.w700,
              color: color ?? const Color(0xFF1E293B),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStatusBadge(String status) {
    Color bg;
    Color text;
    String label;

    switch (status) {
      case 'pending':
      case 'under_review':
        bg = const Color(0xFFFEF3C7);
        text = const Color(0xFFB45309);
        label = 'En attente';
        break;
      case 'approved':
        bg = const Color(0xFFDBEAFE);
        text = const Color(0xFF1D4ED8);
        label = 'Approuvé';
        break;
      case 'disbursed':
      case 'active':
        bg = const Color(0xFFECFDF5);
        text = const Color(0xFF047857);
        label = 'En cours';
        break;
      case 'completed':
      case 'repaid':
        bg = const Color(0xFFF3E8FF);
        text = const Color(0xFF7E22CE);
        label = 'Soldé 🎉';
        break;
      default:
        bg = const Color(0xFFF1F5F9);
        text = const Color(0xFF475569);
        label = status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(8)),
      child: Text(label, style: TextStyle(color: text, fontSize: 10.5, fontWeight: FontWeight.w800)),
    );
  }
}
