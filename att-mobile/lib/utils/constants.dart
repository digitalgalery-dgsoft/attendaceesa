import 'package:shared_preferences/shared_preferences.dart';

class Constants {
  static String baseUrl = 'https://appsend.my.id/api';

  static Future<void> loadBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    baseUrl = prefs.getString('server_base_url') ?? 'https://appsend.my.id/api';
    if (baseUrl.isEmpty) {
      baseUrl = 'https://appsend.my.id/api';
    }
  }

  static Future<void> setBaseUrl(String url) async {
    final prefs = await SharedPreferences.getInstance();
    var cleanUrl = url.trim();
    if (cleanUrl.endsWith('/')) {
      cleanUrl = cleanUrl.substring(0, cleanUrl.length - 1);
    }
    if (!cleanUrl.endsWith('/api')) {
      cleanUrl = '$cleanUrl/api';
    }
    await prefs.setString('server_base_url', cleanUrl);
    baseUrl = cleanUrl;
  }

  static String getImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    final base = baseUrl.replaceAll(RegExp(r'/api/?$'), '');
    return '$base/storage/$path';
  }
}
