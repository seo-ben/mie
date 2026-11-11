import 'package:flutter/material.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:printing/printing.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/currency_formatter.dart';
import '../../../data/models/transaction_model.dart';
import '../../widgets/custom_button.dart';
import '../operations/transaction_reversal_dialog.dart';

class ReceiptScreen extends StatelessWidget {
  final TransactionModel transaction;

  const ReceiptScreen({super.key, required this.transaction});

  Future<void> _printReceipt() async {
    final doc = pw.Document();
    final isOffline = transaction.status == 'pending_sync' || transaction.reference.contains('OFFLINE');

    doc.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.roll80,
        build: (pw.Context context) {
          return pw.Container(
            padding: const pw.EdgeInsets.all(12),
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.center,
              children: [
                pw.Text('FSD YAYRA MICROFINANCE', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 13)),
                pw.Text(isOffline ? 'REÇU PROVISOIRE HORS-LIGNE' : 'REÇU D\'OPÉRATION DE GUICHET', style: const pw.TextStyle(fontSize: 10)),
                pw.Divider(thickness: 1),
                pw.SizedBox(height: 6),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('Réf:', style: const pw.TextStyle(fontSize: 9)),
                    pw.Text(transaction.reference, style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9)),
                  ],
                ),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('Date:', style: const pw.TextStyle(fontSize: 9)),
                    pw.Text(CurrencyFormatter.formatDate(transaction.createdAt ?? DateTime.now()), style: const pw.TextStyle(fontSize: 9)),
                  ],
                ),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('Client:', style: const pw.TextStyle(fontSize: 9)),
                    pw.Text(transaction.clientName ?? 'Client', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9)),
                  ],
                ),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('Type:', style: const pw.TextStyle(fontSize: 9)),
                    pw.Text(
                      transaction.isTransfer
                          ? 'VIREMENT INTERNE (TONTINE ➔ ÉPARGNE)'
                          : (transaction.isDeposit ? 'ENCAISSEMENT (DÉPÔT)' : 'DÉCAISSEMENT (RETRAIT)'),
                      style: const pw.TextStyle(fontSize: 9),
                    ),
                  ],
                ),
                if (isOffline) ...[
                  pw.SizedBox(height: 4),
                  pw.Text('*** EN ATTENTE DE SYNCHRONISATION SERVEUR ***', style: pw.TextStyle(fontSize: 7.5, fontWeight: pw.FontWeight.bold)),
                ],
                pw.Divider(thickness: 1),
                pw.SizedBox(height: 6),
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text('MONTANT TOTAL:', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 11)),
                    pw.Text(CurrencyFormatter.format(transaction.amount), style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 12)),
                  ],
                ),
                pw.SizedBox(height: 12),
                pw.Text('Merci pour votre confiance !', style: const pw.TextStyle(fontSize: 8)),
                pw.Text('www.fsdyayra.be', style: const pw.TextStyle(fontSize: 8)),
              ],
            ),
          );
        },
      ),
    );

    await Printing.layoutPdf(onLayout: (PdfPageFormat format) async => doc.save());
  }

  void _shareReceipt() {
    final isOffline = transaction.status == 'pending_sync' || transaction.reference.contains('OFFLINE');
    final opType = transaction.isTransfer
        ? 'Virement Tontine ➔ Épargne'
        : (transaction.isDeposit ? 'Dépôt / Encaissement' : 'Retrait / Décaissement');
    final text = '''
🧾 *REÇU YAYRA MICROFINANCE*
${isOffline ? '⚠️ _[REÇU PROVISOIRE HORS-LIGNE - EN ATTENTE DE SYNCHRONISATION]_\n' : ''}━━━━━━━━━━━━━━━━━━━━━
*Réf :* ${transaction.reference}
*Date :* ${CurrencyFormatter.formatDate(transaction.createdAt ?? DateTime.now())}
*Client :* ${transaction.clientName ?? 'Client'}
*Opération :* $opType
*Montant :* ${CurrencyFormatter.format(transaction.amount)}
*Statut :* ${isOffline ? 'ENREGISTRÉ EN LOCAL (HORS-LIGNE) ⏳' : 'VALIDÉ & CERTIFIÉ ✅'}
━━━━━━━━━━━━━━━━━━━━━
_Merci pour votre confiance._
''';
    Share.share(text);
  }

  @override
  Widget build(BuildContext context) {
    final isOffline = transaction.status == 'pending_sync' || transaction.reference.contains('OFFLINE');

    return Scaffold(
      backgroundColor: AppColors.backgroundDark,
      appBar: AppBar(
        title: Text(isOffline ? 'Reçu Hors-Ligne (En Attente)' : 'Reçu d\'Opération'),
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(LucideIcons.x),
            onPressed: () => Navigator.of(context).pop(),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            // Receipt Card
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.35),
                    blurRadius: 20,
                    offset: const Offset(0, 10),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Header
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      color: isOffline
                          ? const Color(0xFFF59E0B).withValues(alpha: 0.15)
                          : AppColors.success.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      isOffline ? LucideIcons.cloudOff : LucideIcons.checkCheck,
                      color: isOffline ? const Color(0xFFD97706) : AppColors.success,
                      size: 28,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isOffline ? 'Enregistré Hors-Ligne' : 'Opération Réussie',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: AppColors.textPrimaryLight,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    transaction.reference,
                    style: TextStyle(
                      fontSize: 12,
                      color: isOffline ? const Color(0xFFD97706) : AppColors.textSecondaryLight,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  if (isOffline) ...[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Text(
                        'En attente de synchronisation automatique',
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFFB45309)),
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),

                  // Amount Box
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      children: [
                        Text(
                          transaction.isTransfer
                              ? 'MONTANT TRANSFÉRÉ'
                              : (transaction.isDeposit ? 'MONTANT ENCAISSÉ' : 'MONTANT DÉCAISSÉ'),
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textSecondaryLight,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          CurrencyFormatter.format(transaction.amount),
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w900,
                            color: AppColors.textPrimaryLight,
                            letterSpacing: -0.5,
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Details rows
                  _buildReceiptRow('Client', transaction.clientName ?? 'Client Guichet'),
                  _buildReceiptRow(
                    'Type',
                    transaction.isTransfer
                        ? 'Virement Inter-Comptes'
                        : (transaction.isDeposit ? 'Dépôt Espèces' : 'Retrait Espèces'),
                  ),
                  _buildReceiptRow('Date & Heure', CurrencyFormatter.formatDate(transaction.createdAt ?? DateTime.now())),
                  if (transaction.accountNumber != null)
                    _buildReceiptRow('N° Compte', transaction.accountNumber!),
                  _buildReceiptRow(
                    'Statut',
                    isOffline ? 'En attente de synchro (Local)' : 'Certifié & Validé',
                    valueColor: isOffline ? const Color(0xFFD97706) : AppColors.success,
                  ),

                  const Divider(height: 32, thickness: 1, color: Color(0xFFE2E8F0)),

                  const Text(
                    'FSD YAYRA • Système de Microfinance FSD',
                    style: TextStyle(fontSize: 10.5, color: AppColors.textMutedLight, fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 28),

            // Print & Share Actions
            Row(
              children: [
                Expanded(
                  child: CustomButton(
                    text: 'Imprimer Reçu',
                    icon: LucideIcons.printer,
                    backgroundColor: AppColors.primary,
                    onPressed: _printReceipt,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: CustomButton(
                    text: 'Partager',
                    icon: LucideIcons.share2,
                    backgroundColor: AppColors.secondary,
                    onPressed: _shareReceipt,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),

            if (transaction.status == 'completed')
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    TransactionReversalDialog.show(context, transaction: transaction).then((reversed) {
                      if (reversed == true) {
                        Navigator.of(context).pop(true);
                      }
                    });
                  },
                  icon: const Icon(LucideIcons.undo2, size: 15, color: Color(0xFFFCA5A5)),
                  label: const Text('Annuler cette opération (Void)', style: TextStyle(color: Color(0xFFFCA5A5), fontWeight: FontWeight.w700, fontSize: 12.5)),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: Color(0xFFEF4444)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),

            const SizedBox(height: 10),

            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Retour au Tableau de Bord', style: TextStyle(color: Colors.white70, fontSize: 13.5)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReceiptRow(String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 13, color: AppColors.textSecondaryLight)),
          Text(
            value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: valueColor ?? AppColors.textPrimaryLight,
            ),
          ),
        ],
      ),
    );
  }
}
