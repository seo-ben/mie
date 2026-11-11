import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../domain/notifiers/cashier_notifier.dart';

class VaultTransferModal extends ConsumerStatefulWidget {
  final String initialType; // 'supply' or 'discharge'
  final double? suggestedAmount;

  const VaultTransferModal({
    super.key,
    this.initialType = 'discharge',
    this.suggestedAmount,
  });

  static Future<bool?> show(BuildContext context, {String initialType = 'discharge', double? suggestedAmount}) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => VaultTransferModal(
        initialType: initialType,
        suggestedAmount: suggestedAmount,
      ),
    );
  }

  @override
  ConsumerState<VaultTransferModal> createState() => _VaultTransferModalState();
}

class _VaultTransferModalState extends ConsumerState<VaultTransferModal> {
  late String _type;
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isSubmitting = false;

  final List<double> _quickAmounts = [50000, 100000, 250000, 500000, 1000000];

  @override
  void initState() {
    super.initState();
    _type = widget.initialType;
    if (widget.suggestedAmount != null && widget.suggestedAmount! > 0) {
      _amountController.text = widget.suggestedAmount!.toInt().toString();
    }
    _amountController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _amountController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final cashier = ref.read(cashierNotifierProvider);
    if (!cashier.isSessionOpen) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Impossible : Aucune session de caisse ouverte.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final amount = double.tryParse(_amountController.text.trim()) ?? 0;
    if (amount <= 0) return;

    if (_type == 'discharge' && amount > cashier.cashBalance) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Montant supérieur au solde en caisse (${CurrencyFormatter.format(cashier.cashBalance)}).'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final success = await ref.read(cashierNotifierProvider.notifier).performVaultTransfer(
      type: _type,
      amount: amount,
      notes: _notesController.text.trim().isNotEmpty ? _notesController.text.trim() : null,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (success) {
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _type == 'supply'
                ? 'Approvisionnement de ${CurrencyFormatter.format(amount)} enregistré avec succès.'
                : 'Décharge vers le coffre de ${CurrencyFormatter.format(amount)} enregistrée.',
          ),
          backgroundColor: const Color(0xFF0F172A),
        ),
      );
    } else {
      final err = ref.read(cashierNotifierProvider).errorMessage;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(err ?? 'Erreur lors du transfert de coffre.'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final cashier = ref.watch(cashierNotifierProvider);
    final isSupply = _type == 'supply';
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    final maxHeight = MediaQuery.of(context).size.height * 0.90;

    return Container(
      constraints: BoxConstraints(maxHeight: maxHeight),
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottomInset),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        physics: const ClampingScrollPhysics(),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: const Color(0xFFCBD5E1),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: isSupply ? const Color(0xFFECFDF5) : const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      isSupply ? LucideIcons.arrowDownToDot : LucideIcons.arrowUpFromDot,
                      color: isSupply ? const Color(0xFF059669) : const Color(0xFFD97706),
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Transfert Coffre ↔ Guichet',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        Text(
                          'Mouvements de liquidités internes à l\'agence',
                          style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              // Segmented selector
              Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        onTap: () => setState(() => _type = 'supply'),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          decoration: BoxDecoration(
                            color: isSupply ? Colors.white : Colors.transparent,
                            borderRadius: BorderRadius.circular(10),
                            boxShadow: isSupply
                                ? [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.06),
                                      blurRadius: 4,
                                      offset: const Offset(0, 1),
                                    ),
                                  ]
                                : null,
                          ),
                          child: const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(LucideIcons.arrowDownToDot, size: 15, color: Color(0xFF059669)),
                              SizedBox(width: 6),
                              Text(
                                'Approvisionnement',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    Expanded(
                      child: InkWell(
                        onTap: () => setState(() => _type = 'discharge'),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          decoration: BoxDecoration(
                            color: !isSupply ? Colors.white : Colors.transparent,
                            borderRadius: BorderRadius.circular(10),
                            boxShadow: !isSupply
                                ? [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.06),
                                      blurRadius: 4,
                                      offset: const Offset(0, 1),
                                    ),
                                  ]
                                : null,
                          ),
                          child: const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(LucideIcons.arrowUpFromDot, size: 15, color: Color(0xFFD97706)),
                              SizedBox(width: 6),
                              Text(
                                'Décharge Coffre',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              // Current cash balance indicator
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Solde actuel au tiroir-caisse :',
                      style: TextStyle(fontSize: 12, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                    ),
                    Text(
                      CurrencyFormatter.format(cashier.cashBalance),
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              // Amount field
              const Text(
                'Montant du transfert (FCFA) *',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
              ),
              const SizedBox(height: 6),
              TextFormField(
                controller: _amountController,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                decoration: InputDecoration(
                  hintText: '0',
                  suffixText: 'FCFA',
                  suffixStyle: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF64748B)),
                  prefixIcon: const Icon(LucideIcons.banknote, color: Color(0xFF64748B), size: 18),
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                ),
                validator: (val) {
                  if (val == null || val.trim().isEmpty) return 'Veuillez saisir un montant';
                  final num = double.tryParse(val.trim());
                  if (num == null || num < 100) return 'Montant minimum 100 FCFA';
                  if (!isSupply && num > cashier.cashBalance) {
                    return 'Montant supérieur au solde disponible';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 10),
              // Quick amounts chips
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: _quickAmounts.map((q) {
                    return Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: ActionChip(
                        label: Text(
                          CurrencyFormatter.formatSimple(q),
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
                        ),
                        backgroundColor: const Color(0xFFF1F5F9),
                        side: BorderSide.none,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        onPressed: () {
                          _amountController.text = q.toInt().toString();
                        },
                      ),
                    );
                  }).toList(),
                ),
              ),
              const SizedBox(height: 14),
              // Notes / motif
              const Text(
                'Référence / Motif du transfert (optionnel)',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
              ),
              const SizedBox(height: 6),
              TextFormField(
                controller: _notesController,
                maxLines: 2,
                style: const TextStyle(fontSize: 13),
                decoration: InputDecoration(
                  hintText: isSupply ? 'Ex: Bon d\'approvisionnement N°042...' : 'Ex: Décharge d\'excédent de caisse vers coffre...',
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _isSubmitting ? null : _submit,
                  icon: _isSubmitting
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Icon(isSupply ? LucideIcons.arrowDownToDot : LucideIcons.arrowUpFromDot, size: 18),
                  label: Text(
                    _isSubmitting
                        ? 'Enregistrement...'
                        : (isSupply ? 'Valider l\'Approvisionnement' : 'Valider la Décharge vers le Coffre'),
                    style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13.5),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: isSupply ? const Color(0xFF059669) : const Color(0xFFD97706),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
