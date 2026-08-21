import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:att_mobile/utils/translations.dart';

class LocaleProvider extends ChangeNotifier {
  static const String _keyLanguage = 'app_language';
  static const String _keyTimezone = 'app_timezone';

  String _languageCode = 'en'; // Default English
  String _timeZone = 'WIB'; // Default WIB (UTC+7)

  String get languageCode => _languageCode;
  String get timeZone => _timeZone;

  bool get isEnglish => _languageCode == 'en';
  bool get isIndonesian => _languageCode == 'id';

  String get languageDisplayName => _languageCode == 'id' ? 'Indonesian' : 'English (US)';

  LocaleProvider() {
    _loadFromPreferences();
  }

  Future<void> _loadFromPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    _languageCode = prefs.getString(_keyLanguage) ?? 'en';
    _timeZone = prefs.getString(_keyTimezone) ?? 'WIB';
    notifyListeners();
  }

  Future<void> setLanguage(String code) async {
    if (_languageCode == code) return;
    _languageCode = code;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyLanguage, code);
    notifyListeners();
  }

  Future<void> setTimeZone(String tz) async {
    if (_timeZone == tz) return;
    _timeZone = tz;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyTimezone, tz);
    notifyListeners();
  }

  /// Translation helper
  String tr(String key, {Map<String, String>? params}) {
    return AppTranslations.get(key, lang: _languageCode, params: params);
  }

  /// Offset in hours based on chosen timezone
  int get timezoneOffsetHours {
    switch (_timeZone) {
      case 'WITA':
        return 8;
      case 'WIT':
        return 9;
      case 'WIB':
      default:
        return 7;
    }
  }

  /// Get current time formatted according to selected timezone
  DateTime get nowInSelectedTimeZone {
    final nowUtc = DateTime.now().toUtc();
    return nowUtc.add(Duration(hours: timezoneOffsetHours));
  }

  /// Format DateTime into string using locale & timezone
  String formatDateTime(DateTime dateTime, {String pattern = 'HH:mm:ss'}) {
    // If dateTime is UTC or local, convert to target timezone offset
    final utc = dateTime.isUtc ? dateTime : dateTime.toUtc();
    final adjusted = utc.add(Duration(hours: timezoneOffsetHours));
    final localeName = _languageCode == 'id' ? 'id_ID' : 'en_US';
    return DateFormat(pattern, localeName).format(adjusted);
  }

  /// Format current live time
  String formatCurrentTime({String pattern = 'HH:mm:ss'}) {
    return formatDateTime(DateTime.now(), pattern: pattern);
  }
}
