import 'package:shared_preferences/shared_preferences.dart';

class Constants {
  // Official Production Server Gateway URL
  static const String defaultProductionUrl = 'https://api.esa-solutions.id/api';
  static String baseUrl = defaultProductionUrl;

  /// Strict security whitelist: Only production domains (*.esa-solutions.id) are permitted
  static bool isProductionUrl(String url) {
    try {
      final uri = Uri.parse(url.trim());
      final host = uri.host.toLowerCase();
      return host.endsWith('.esa-solutions.id') || host == 'esa-solutions.id';
    } catch (_) {
      return false;
    }
  }

  static Future<void> loadBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    var savedUrl = prefs.getString('server_base_url') ?? '';

    // Strict enforcement: If unconfigured, or pointing to legacy/staging domains (like appsend.my.id),
    // automatically sanitize and reset to official production URL.
    if (savedUrl.isEmpty || !isProductionUrl(savedUrl)) {
      savedUrl = defaultProductionUrl;
      await prefs.setString('server_base_url', savedUrl);
    }
    baseUrl = savedUrl;
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

    if (!isProductionUrl(cleanUrl)) {
      throw Exception('Hanya server production (*.esa-solutions.id) yang diizinkan.');
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

