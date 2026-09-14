import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class UpdateManager {
  static bool _isChecking = false;
  static bool _isDialogShowing = false;

  static Future<void> checkForUpdate(BuildContext context, {String? authToken}) async {
    if (_isChecking || _isDialogShowing) return;
    _isChecking = true;
    try {
      // Fetch settings from API with cache-busting
      final uri = Uri.parse('${Constants.baseUrl}/settings?_t=${DateTime.now().millisecondsSinceEpoch}');
      final headers = <String, String>{
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
        'Accept': 'application/json',
      };
      if (authToken != null && authToken.isNotEmpty) {
        headers['Authorization'] = 'Bearer $authToken';
      }

      final response = await http.get(uri, headers: headers).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        final data = decoded['data'];
        if (data == null) return;
        
        final String? serverVersion = data['mobile_app_version']?.toString();
        final String? downloadUrl = data['mobile_app_url']?.toString();
        final bool isForceUpdate = data['is_force_update'] == 1 || data['is_force_update'] == true || data['is_force_update'] == '1';

        debugPrint('[UpdateManager] BaseURL: ${Constants.baseUrl}, ServerVersion: $serverVersion, DownloadUrl: $downloadUrl, Force: $isForceUpdate');

        if (serverVersion != null && serverVersion.isNotEmpty) {
          final packageInfo = await PackageInfo.fromPlatform();
          final String currentVersion = packageInfo.version;
          debugPrint('[UpdateManager] Local Version: $currentVersion vs Server: $serverVersion');

          if (_isUpdateAvailable(currentVersion, serverVersion)) {
            if (context.mounted && !_isDialogShowing) {
              _isDialogShowing = true;
              _showUpdateDialog(context, serverVersion, downloadUrl, isForceUpdate);
            }
          }
        }
      }
    } catch (e) {
      debugPrint('[UpdateManager] Error checking for update: $e');
    } finally {
      _isChecking = false;
    }
  }

  static bool _isUpdateAvailable(String currentVersion, String serverVersion) {
    try {
      final currentParts = currentVersion.split('.').map((e) => int.tryParse(e) ?? 0).toList();
      final serverParts = serverVersion.split('.').map((e) => int.tryParse(e) ?? 0).toList();

      for (int i = 0; i < 3; i++) {
        final currentPart = i < currentParts.length ? currentParts[i] : 0;
        final serverPart = i < serverParts.length ? serverParts[i] : 0;

        if (serverPart > currentPart) {
          return true;
        } else if (serverParts[i] < currentPart) {
          return false;
        }
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  static void _showUpdateDialog(BuildContext context, String newVersion, String? customUrl, bool isForceUpdate) {
    showDialog(
      context: context,
      barrierDismissible: !isForceUpdate,
      builder: (context) {
        return PopScope(
          canPop: !isForceUpdate,
          onPopInvokedWithResult: (didPop, result) {
            _isDialogShowing = false;
          },
          child: AlertDialog(
            title: const Text('Update Tersedia'),
            content: Text('Versi terbaru ($newVersion) telah tersedia di Google Play Store. Silakan perbarui aplikasi Anda untuk kelancaran absensi dan kestabilan sistem.'),
            actions: [
              if (!isForceUpdate)
                TextButton(
                  onPressed: () {
                    _isDialogShowing = false;
                    Navigator.pop(context);
                  },
                  child: const Text('Nanti'),
                ),
              ElevatedButton(
                onPressed: () {
                  _isDialogShowing = false;
                  if (!isForceUpdate) Navigator.pop(context);
                  _openPlayStore(customUrl);
                },
                child: const Text('Buka Play Store'),
              ),
            ],
          ),
        );
      },
    ).then((_) {
      _isDialogShowing = false;
    });
  }

  static Future<void> _openPlayStore(String? customUrl) async {
    // 1. Prioritize Google Play Market protocol
    final playStoreUri = Uri.parse('market://details?id=com.attendance.att_mobile');
    final webPlayStoreUri = Uri.parse('https://play.google.com/store/apps/details?id=com.attendance.att_mobile');

    try {
      if (await canLaunchUrl(playStoreUri)) {
        await launchUrl(playStoreUri, mode: LaunchMode.externalApplication);
      } else if (await canLaunchUrl(webPlayStoreUri)) {
        await launchUrl(webPlayStoreUri, mode: LaunchMode.externalApplication);
      } else if (customUrl != null && customUrl.isNotEmpty) {
        final uri = Uri.parse(customUrl);
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      }
    } catch (e) {
      debugPrint('[UpdateManager] Error opening Play Store: $e');
    }
  }
}
