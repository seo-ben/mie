import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import '../../../core/constants/app_colors.dart';
import '../../../domain/notifiers/cashier_notifier.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';

class ExpressRegisterScreen extends ConsumerStatefulWidget {
  const ExpressRegisterScreen({super.key});

  @override
  ConsumerState<ExpressRegisterScreen> createState() => _ExpressRegisterScreenState();
}

class _ExpressRegisterScreenState extends ConsumerState<ExpressRegisterScreen> {
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController(text: 'Lomé');
  final _idNumberController = TextEditingController();
  final _tontineMiseController = TextEditingController(text: '1000');
  final _formKey = GlobalKey<FormState>();

  String _selectedIdType = 'cni';
  String _selectedGender = 'M';
  int _step = 0;
  bool _createTontine = false;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _idNumberController.dispose();
    _tontineMiseController.dispose();
    super.dispose();
  }

  Future<void> _submitRegister() async {
    if (!_formKey.currentState!.validate()) return;

    final cashierNotifier = ref.read(cashierNotifierProvider.notifier);
    final mise = double.tryParse(_tontineMiseController.text.replaceAll(' ', '')) ?? 1000.0;

    final client = await cashierNotifier.registerExpressClient(
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      phone: _phoneController.text.trim(),
      address: _addressController.text.trim(),
      gender: _selectedGender,
      idType: _selectedIdType,
      idNumber: _idNumberController.text.trim(),
      initialTontineAmount: mise,
      createTontine: _createTontine,
    );

    if (!mounted) return;

    if (client != null) {
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: Colors.white,
          title: const Row(
            children: [
              Icon(LucideIcons.checkCircle2, color: AppColors.success, size: 24),
              SizedBox(width: 10),
              Text('Client enregistré', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 17)),
            ],
          ),
          content: Text(
            'Le client ${client.fullName} a été créé avec son compte épargne actif${_createTontine ? ' et son compte tontine actif' : ''}.',
            style: const TextStyle(color: Color(0xFF72809A), fontSize: 13),
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(ctx).pop();
                Navigator.of(context).pop();
              },
              child: const Text('Retour au Guichet', style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      );
    } else {
      final cashierState = ref.read(cashierNotifierProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(cashierState.errorMessage ?? 'Erreur lors de l\'enregistrement.'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  void _goToAccountStep() {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _step = 1);
  }

  Widget _genderField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('GENRE', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF72809A), letterSpacing: 0.8)),
        const SizedBox(height: 7),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: const Color(0x1A00D1B2))),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: _selectedGender,
              isExpanded: true,
              dropdownColor: Colors.white,
              items: const [
                DropdownMenuItem(value: 'M', child: Text('Masculin', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
                DropdownMenuItem(value: 'F', child: Text('Féminin', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
                DropdownMenuItem(value: 'Other', child: Text('Autre', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
              ],
              onChanged: (value) => setState(() => _selectedGender = value ?? 'M'),
            ),
          ),
        ),
      ],
    );
  }

  Widget _stepHeader(String title, String subtitle) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(color: Color(0xFF1D2A3A), fontSize: 21, fontWeight: FontWeight.w800)),
          const SizedBox(height: 5),
          Text(subtitle, style: const TextStyle(color: Color(0xFF72809A), fontSize: 13)),
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
        title: const Text('Inscription Express Client'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: _step == 0
              ? Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _stepHeader('Informations personnelles', 'Renseignez les informations du nouveau client.'),
                    Row(
                      children: [
                        Expanded(
                          child: CustomTextField(
                            controller: _firstNameController,
                            label: 'Prénom',
                            hint: 'Ex: Jean',
                            prefixIcon: LucideIcons.user,
                            lightTheme: true,
                            validator: (val) => val == null || val.trim().isEmpty ? 'Requis' : null,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: CustomTextField(
                            controller: _lastNameController,
                            label: 'Nom',
                            hint: 'Ex: KOFFI',
                            lightTheme: true,
                            validator: (val) => val == null || val.trim().isEmpty ? 'Requis' : null,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _genderField(),
                    const SizedBox(height: 16),
                    CustomTextField(
                      controller: _phoneController,
                      label: 'Téléphone (WhatsApp / SMS)',
                      hint: 'Ex: 90 00 00 00',
                      prefixIcon: LucideIcons.phone,
                      lightTheme: true,
                      keyboardType: TextInputType.phone,
                      validator: (val) => val == null || val.trim().isEmpty ? 'Téléphone obligatoire' : null,
                    ),
                    const SizedBox(height: 16),
                    CustomTextField(
                      controller: _addressController,
                      label: 'Quartier / Adresse',
                      hint: 'Ex: Agoè Téléssou',
                      prefixIcon: LucideIcons.mapPin,
                      lightTheme: true,
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'PIÈCE D\'IDENTITÉ',
                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF72809A), letterSpacing: 0.8),
                              ),
                              const SizedBox(height: 7),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: const Color(0x1A00D1B2)),
                                ),
                                child: DropdownButtonHideUnderline(
                                  child: DropdownButton<String>(
                                    value: _selectedIdType,
                                    dropdownColor: Colors.white,
                                    isExpanded: true,
                                    items: const [
                                      DropdownMenuItem(value: 'cni', child: Text('CNI', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
                                      DropdownMenuItem(value: 'passport', child: Text('Passeport', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
                                      DropdownMenuItem(value: 'driving_license', child: Text('Permis', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
                                      DropdownMenuItem(value: 'other', child: Text('Autre', style: TextStyle(color: Color(0xFF1D2A3A), fontSize: 13))),
                                    ],
                                    onChanged: (val) => setState(() => _selectedIdType = val ?? 'cni'),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          flex: 3,
                          child: CustomTextField(
                            controller: _idNumberController,
                            label: 'N° de la pièce',
                            hint: 'Ex: 0123-456-789',
                            lightTheme: true,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    CustomButton(
                      text: 'Suivant',
                      icon: LucideIcons.arrowRight,
                      gradient: AppColors.primaryGradient,
                      onPressed: _goToAccountStep,
                    ),
                  ],
                )
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _stepHeader('Compte du client', 'Choisissez le compte à ouvrir après les informations personnelles.'),
                    InkWell(
                      onTap: () => setState(() => _createTontine = !_createTontine),
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: _createTontine ? AppColors.primary : const Color(0xFFE7ECF5),
                            width: _createTontine ? 1.5 : 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              _createTontine ? LucideIcons.checkCircle2 : LucideIcons.circle,
                              color: _createTontine ? AppColors.primary : const Color(0xFF9AA5B5),
                            ),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Créer aussi un compte tontine',
                                    style: TextStyle(color: Color(0xFF1D2A3A), fontWeight: FontWeight.w700),
                                  ),
                                  SizedBox(height: 4),
                                  Text(
                                    'Le compte épargne est créé automatiquement.',
                                    style: TextStyle(color: Color(0xFF72809A), fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    if (_createTontine) ...[
                      const SizedBox(height: 16),
                      CustomTextField(
                        controller: _tontineMiseController,
                        label: 'Mise quotidienne tontine (FCFA)',
                        hint: 'Ex: 1 000',
                        prefixIcon: LucideIcons.coins,
                        lightTheme: true,
                        keyboardType: TextInputType.number,
                      ),
                    ],
                    const SizedBox(height: 24),
                    CustomButton(
                      text: _createTontine ? 'Créer tontine et terminer' : 'Terminer',
                      icon: LucideIcons.userCheck,
                      gradient: AppColors.primaryGradient,
                      isLoading: cashier.isLoading,
                      onPressed: _submitRegister,
                    ),
                    const SizedBox(height: 10),
                    TextButton.icon(
                      onPressed: () => setState(() => _step = 0),
                      icon: const Icon(LucideIcons.arrowLeft, size: 17),
                      label: const Text('Retour aux informations'),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}
