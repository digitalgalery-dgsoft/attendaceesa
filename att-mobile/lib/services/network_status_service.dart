import 'dart:async';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:att_mobile/utils/constants.dart';

class NetworkStatusService {
  static final ValueNotifier<bool> isOnline = ValueNotifier<bool>(true);
  static Timer? _timer;
  static bool _isChecking = false;

  /**
   * Start real-time background connectivity monitoring.
   */
  static void startMonitoring() {
    _timer?.cancel();
    checkConnection();
    _timer = Timer.periodic(const Duration(seconds: 10), (_) {
      checkConnection();
    });
  }

  /**
   * Stop background connectivity monitoring.
   */
  static void stopMonitoring() {
    _timer?.cancel();
    _timer = null;
  }

  /**
   * Actively check if device can resolve DNS & reach the internet.
   */
  static Future<bool> checkConnection() async {
    if (_isChecking) return isOnline.value;
    _isChecking = true;

    try {
      // 1. Primary DNS lookup (google.com)
      final result = await InternetAddress.lookup('google.com')
          .timeout(const Duration(seconds: 3));
      
      if (result.isNotEmpty && result[0].rawAddress.isNotEmpty) {
        if (!isOnline.value) {
          isOnline.value = true;
          debugPrint('Network status: ONLINE');
        }
        _isChecking = false;
        return true;
      }
    } catch (_) {
      // 2. Secondary fallback (dns.google)
      try {
        final fallbackResult = await InternetAddress.lookup('dns.google')
            .timeout(const Duration(seconds: 2));
        if (fallbackResult.isNotEmpty && fallbackResult[0].rawAddress.isNotEmpty) {
          if (!isOnline.value) {
            isOnline.value = true;
            debugPrint('Network status: ONLINE (via fallback)');
          }
          _isChecking = false;
          return true;
        }
      } catch (_) {
        // 3. Optional fallback: ping backend server host
        try {
          final uri = Uri.parse(Constants.baseUrl);
          if (uri.host.isNotEmpty) {
            final serverResult = await InternetAddress.lookup(uri.host)
                .timeout(const Duration(seconds: 2));
            if (serverResult.isNotEmpty && serverResult[0].rawAddress.isNotEmpty) {
              if (!isOnline.value) {
                isOnline.value = true;
                debugPrint('Network status: ONLINE (via backend host)');
              }
              _isChecking = false;
              return true;
            }
          }
        } catch (_) {}
      }
    }

    if (isOnline.value) {
      isOnline.value = false;
      debugPrint('Network status: OFFLINE');
    }
    _isChecking = false;
    return false;
  }
}
