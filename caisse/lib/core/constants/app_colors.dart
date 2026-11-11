import 'package:flutter/material.dart';

class AppColors {
  // Brand Colors (YAYRA Microfinance)
  static const Color primary = Color(0xFF00D1B2); // Modern Teal
  static const Color primaryDark = Color(0xFF00A892);
  static const Color primaryLight = Color(0xFFE6FAF7);

  static const Color secondary = Color(0xFF6366F1); // Indigo Accent
  static const Color secondaryLight = Color(0xFFEEF2FF);

  // Background & Surfaces (Navy Dark & Clean White)
  static const Color backgroundDark = Color(0xFF0F1923);
  static const Color surfaceDark = Color(0xFF1A2332);
  static const Color surfaceLightDark = Color(0xFF243042);
  static const Color cardDark = Color(0xFF161E2E);

  static const Color backgroundLight = Color(0xFFF8FAFC);
  static const Color surfaceLight = Color(0xFFFFFFFF);
  static const Color borderLight = Color(0xFFE2E8F0);

  // Status & Financial Indicators
  static const Color success = Color(0xFF10B981); // Encaissement (Entrées)
  static const Color successLight = Color(0xFFD1FAE5);
  static const Color danger = Color(0xFFEF4444);  // Décaissement (Sorties)
  static const Color dangerLight = Color(0xFFFEE2E2);
  static const Color warning = Color(0xFFF59E0B);
  static const Color warningLight = Color(0xFFFEF3C7);
  static const Color info = Color(0xFF3B82F6);
  static const Color infoLight = Color(0xFFDBEAFE);

  // Text Colors
  static const Color textPrimaryDark = Color(0xFFFFFFFF);
  static const Color textSecondaryDark = Color(0xFF94A3B8);
  static const Color textMutedDark = Color(0xFF64748B);

  static const Color textPrimaryLight = Color(0xFF0F172A);
  static const Color textSecondaryLight = Color(0xFF475569);
  static const Color textMutedLight = Color(0xFF94A3B8);

  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF00D1B2), Color(0xFF00B4D8)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient darkCardGradient = LinearGradient(
    colors: [Color(0xFF1A2332), Color(0xFF141C28)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient sunsetGradient = LinearGradient(
    colors: [Color(0xFFFF6B4A), Color(0xFFFFA64D)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
