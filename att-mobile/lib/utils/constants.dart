import 'package:shared_preferences/shared_preferences.dart';

class Constants {
  static String baseUrl = '';

  static Future<void> loadBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    baseUrl = prefs.getString('server_base_url') ?? '';
  }

  static Future<void> setBaseUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('server_base_url', url);
    baseUrl = url;
  }

  static String getImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    final base = baseUrl.replaceAll(RegExp(r'/api/?$'), '');
    return '$base/storage/$path';
  }
}
