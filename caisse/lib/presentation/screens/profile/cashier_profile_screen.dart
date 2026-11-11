import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../domain/notifiers/auth_notifier.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../auth/login_screen.dart';

class CashierProfileScreen extends ConsumerWidget {
  const CashierProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authNotifierProvider);
    final cashier = ref.watch(cashierNotifierProvider);
    final user = auth.user;

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
          'Mon Profil Caissier',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 16,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 30),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Hero Profile Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF0F172A).withValues(alpha: 0.25),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    width: 58,
                    height: 58,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppColors.primary, Color(0xFF00A892)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.primary.withValues(alpha: 0.35),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Text(
                        (user?.firstName?.isNotEmpty == true ? user!.firstName![0] : 'C').toUpperCase(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user?.fullName ?? 'Caissier Principal',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          user?.email ?? 'caissier@yayra.com',
                          style: const TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF10B981).withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: const Text(
                            'SESSION ACTIVE • EN SERVICE',
                            style: TextStyle(
                              color: Color(0xFF34D399),
                              fontSize: 9.5,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Account & Agency Info
            _sectionHeader('INFORMATIONS DE POSTE & GUICHET'),
            const SizedBox(height: 8),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: [
                  _infoRow('Nom d\'utilisateur', user?.username ?? 'N/A', LucideIcons.user),
                  _divider(),
                  _infoRow('Rôle / Fonction', 'Caissier Guichetier POS', LucideIcons.shieldCheck),
                  _divider(),
                  _infoRow('Agence de rattachement', user?.agencyName ?? 'Agence Principale', LucideIcons.building2),
                  _divider(),
                  _infoRow('Téléphone', user?.phone ?? 'Non renseigné', LucideIcons.phone),
                  _divider(),
                  _infoRow('Statut de caisse du jour', cashier.isSessionOpen ? 'Ouverte' : 'Fermée', LucideIcons.wallet,
                      valueColor: cashier.isSessionOpen ? const Color(0xFF059669) : const Color(0xFFDC2626)),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Security & App Info
            _sectionHeader('SÉCURITÉ & APPLICATION'),
            const SizedBox(height: 8),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                children: [
                  _infoRow('Version Terminal POS', 'v1.4.0 (Enterprise)', LucideIcons.cpu),
                  _divider(),
                  _infoRow('Sécurité Transactions', 'Chiffrement SHA-256 / SSL', LucideIcons.lock),
                  _divider(),
                  _infoRow('Environnement', 'Production Sécurisée', LucideIcons.shieldAlert, valueColor: const Color(0xFF059669)),
                ],
              ),
            ),
            const SizedBox(height: 26),

            // Logout Button
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () async {
                  final navigator = Navigator.of(context);
                  await ref.read(authNotifierProvider.notifier).logout();
                  if (!context.mounted) return;
                  navigator.pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                    (route) => false,
                  );
                },
                icon: const Icon(LucideIcons.logOut, size: 18),
                label: const Text('Se Déconnecter', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.danger,
                  side: const BorderSide(color: Color(0xFFFECACA), width: 1.5),
                  backgroundColor: const Color(0xFFFEF2F2),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionHeader(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w800,
        color: Color(0xFF64748B),
        letterSpacing: 0.8,
      ),
    );
  }

  Widget _divider() => const Divider(color: Color(0xFFF1F5F9), height: 20);

  Widget _infoRow(String label, String value, IconData icon, {Color? valueColor}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Icon(icon, size: 16, color: const Color(0xFF94A3B8)),
        const SizedBox(width: 10),
        Expanded(
          flex: 6,
          child: Text(
            label,
            style: const TextStyle(
              color: Color(0xFF64748B),
              fontSize: 12.5,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          flex: 6,
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: TextStyle(
              color: valueColor ?? const Color(0xFF0F172A),
              fontSize: 12.5,
              fontWeight: FontWeight.w700,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
