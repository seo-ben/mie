import 'package:intl/intl.dart';

class CurrencyFormatter {
  static final NumberFormat _formatter = NumberFormat('#,##0', 'fr_FR');

  static String format(num? amount, {String currency = 'FCFA'}) {
    if (amount == null) return '0 $currency';
    return '${_formatter.format(amount).replaceAll(',', ' ')} $currency';
  }

  static String formatSimple(num? amount) {
    if (amount == null) return '0';
    return _formatter.format(amount).replaceAll(',', ' ');
  }

  static String formatNumber(num? amount) => formatSimple(amount);

  static String formatDate(DateTime? date) {
    if (date == null) return '-';
    return DateFormat('dd/MM/yyyy HH:mm').format(date);
  }

  static String formatDateSimple(DateTime? date) {
    if (date == null) return '-';
    return DateFormat('dd MMMM yyyy', 'fr_FR').format(date);
  }
}
