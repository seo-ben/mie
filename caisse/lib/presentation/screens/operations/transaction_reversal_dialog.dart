import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/transaction_model.dart';
import '../../../domain/notifiers/cashier_notifier.dart';

class TransactionReversalDialog extends ConsumerStatefulWidget {
  final TransactionModel transaction;

  const TransactionReversalDialog({
    super.key,
    required this.transaction,
  });

  static Future<bool?> show(BuildContext context, {required TransactionModel transaction}) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => TransactionReversalDialog(transaction: transaction),
    );
  }

  @override
  ConsumerState<TransactionReversalDialog> createState() => _TransactionReversalDialogState();
}

class _TransactionReversalDialogState extends ConsumerState<TransactionReversalDialog> {
  final _pinController = TextEditingController();
  final _reasonController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _obscurePin = true;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _pinController.dispose();
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);

    final success = await ref.read(cashierNotifierProvider.notifier).reverseTransaction(
      transactionId: widget.transaction.id,
      supervisorPin: _pinController.text.trim(),
      reason: _reasonController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (success) {
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Transaction ${widget.transaction.reference} annulée avec succès.'),
          backgroundColor: const Color(0xFF0F172A),
        ),
      );
    } else {
      final err = ref.read(cashierNotifierProvider).errorMessage;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(err ?? 'Erreur lors de l\'annulation.'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final tx = widget.transaction;
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
                      color: const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(LucideIcons.undo2, color: Color(0xFFDC2626), size: 20),
                  ),
                  const SizedBox(width: 10),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Annulation d\'Opération (Void)',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        Text(
                          'Correction comptable avec autorisation superviseur',
                          style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              // Transaction summary card
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  children: [
                    _infoRow('Référence', tx.reference),
                    const Divider(height: 14),
                    _infoRow('Montant', CurrencyFormatter.format(tx.amount), isBold: true, color: const Color(0xFFDC2626)),
                    const SizedBox(height: 6),
                    _infoRow('Type', tx.isDeposit ? 'Encaissement / Dépôt' : (tx.isWithdrawal ? 'Décaissement / Retrait' : tx.transactionType)),
                    if (tx.description != null && tx.description!.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      _infoRow('Description', tx.description!),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 14),
              // Security Warning Alert
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFFBEB),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFFDE68A)),
                ),
                child: const Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(LucideIcons.shieldAlert, color: Color(0xFFD97706), size: 16),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Attention : Cette action annulera les effets financiers sur le compte client et réajustera le solde théorique de votre session de caisse.',
                        style: TextStyle(color: Color(0xFF92400E), fontSize: 11.5, fontWeight: FontWeight.w600, height: 1.3),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              // Motif
              const Text(
                'Motif de l\'annulation *',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
              ),
              const SizedBox(height: 6),
              TextFormField(
                controller: _reasonController,
                maxLines: 2,
                style: const TextStyle(fontSize: 13),
                decoration: InputDecoration(
                  hintText: 'Ex: Erreur de montant saisie par inadvertance...',
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                ),
                validator: (val) {
                  if (val == null || val.trim().isEmpty) return 'Veuillez justifier le motif de l\'annulation';
                  if (val.trim().length < 4) return 'Motif trop court (min 4 caractères)';
                  return null;
                },
              ),
              const SizedBox(height: 14),
              // Supervisor PIN
              const Text(
                'Code PIN d\'Autorisation Superviseur *',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
              ),
              const SizedBox(height: 6),
              TextFormField(
                controller: _pinController,
                obscureText: _obscurePin,
                keyboardType: TextInputType.number,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, letterSpacing: 3),
                decoration: InputDecoration(
                  hintText: '••••',
                  prefixIcon: const Icon(LucideIcons.keyRound, size: 18, color: Color(0xFF64748B)),
                  suffixIcon: IconButton(
                    icon: Icon(_obscurePin ? LucideIcons.eyeOff : LucideIcons.eye, size: 18, color: const Color(0xFF64748B)),
                    onPressed: () => setState(() => _obscurePin = !_obscurePin),
                  ),
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                ),
                validator: (val) {
                  if (val == null || val.trim().isEmpty) return 'Code PIN superviseur requis';
                  return null;
                },
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.of(context).pop(),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Abandonner', style: TextStyle(fontWeight: FontWeight.w700)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: _isSubmitting ? null : _submit,
                      icon: _isSubmitting
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Icon(LucideIcons.undo2, size: 16),
                      label: Text(
                        _isSubmitting ? 'Annulation...' : 'Confirmer Annulation',
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFDC2626),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _infoRow(String label, String value, {bool isBold = false, Color? color}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(fontSize: 12, color: const Color(0xFF64748B), fontWeight: isBold ? FontWeight.w700 : FontWeight.w500),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: TextStyle(fontSize: 12.5, fontWeight: isBold ? FontWeight.w900 : FontWeight.w700, color: color ?? const Color(0xFF0F172A)),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
