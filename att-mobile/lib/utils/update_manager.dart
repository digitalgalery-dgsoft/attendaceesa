import 'dart:io';
import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class UpdateManager {
  static Future<void> checkForUpdate(BuildContext context) async {
    try {
      // Fetch settings from API
      final response = await http.get(Uri.parse('${Constants.baseUrl}/settings'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body)['data'];
        
        final String? serverVersion = data['mobile_app_version'];
        final String? downloadUrl = data['mobile_app_url'];
        final bool isForceUpdate = data['is_force_update'] == 1 || data['is_force_update'] == true;

        if (serverVersion != null && serverVersion.isNotEmpty && downloadUrl != null && downloadUrl.isNotEmpty) {
          final packageInfo = await PackageInfo.fromPlatform();
          final String currentVersion = packageInfo.version;

          if (_isUpdateAvailable(currentVersion, serverVersion)) {
            if (context.mounted) {
              _showUpdateDialog(context, serverVersion, downloadUrl, isForceUpdate);
            }
          }
        }
      }
    } catch (e) {
      debugPrint('Error checking for update: $e');
    }
  }

  static bool _isUpdateAvailable(String currentVersion, String serverVersion) {
    try {
      List<int> currentParts = currentVersion.split('.').map(int.parse).toList();
      List<int> serverParts = serverVersion.split('.').map(int.parse).toList();

      for (int i = 0; i < serverParts.length; i++) {
        int currentPart = i < currentParts.length ? currentParts[i] : 0;
        if (serverParts[i] > currentPart) {
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

  static void _showUpdateDialog(BuildContext context, String newVersion, String url, bool isForceUpdate) {
    showDialog(
      context: context,
      barrierDismissible: !isForceUpdate,
      builder: (context) {
        return PopScope(
          canPop: !isForceUpdate,
          child: AlertDialog(
            title: const Text('Update Tersedia'),
            content: Text('Versi terbaru ($newVersion) telah tersedia. Silakan update aplikasi Anda untuk pengalaman yang lebih baik.'),
            actions: [
              if (!isForceUpdate)
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Nanti'),
                ),
              ElevatedButton(
                onPressed: () {
                  if (!isForceUpdate) Navigator.pop(context);
                  _downloadAndInstall(context, url);
                },
                child: const Text('Update Sekarang'),
              ),
            ],
          ),
        );
      },
    );
  }

  static Future<void> _downloadAndInstall(BuildContext context, String url) async {
    String message = 'Memulai proses update...';
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext dialogContext) {
        double progress = 0.0;
        
        return StatefulBuilder(builder: (context, setState) {
          // Hanya jalankan download sekali
          if (progress == 0.0 && message == 'Memulai proses update...') {
            _performDownload(url, (received, total) {
              if (total != -1) {
                setState(() {
                  progress = received / total;
                  message = 'Mengunduh: ${(progress * 100).toStringAsFixed(0)}%';
                });
              }
            }).then((filePath) {
              if (dialogContext.mounted) {
                Navigator.pop(dialogContext); // Close progress dialog
              }
              if (filePath != null) {
                _installApk(filePath);
              }
            });
          }

          return AlertDialog(
            title: const Text('Mengunduh Update'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                LinearProgressIndicator(value: progress),
                const SizedBox(height: 16),
                Text(message),
              ],
            ),
          );
        });
      },
    );
  }

  static Future<String?> _performDownload(String url, Function(int, int) onReceiveProgress) async {
    try {
      final dio = Dio();
      final dir = await getExternalStorageDirectory();
      final savePath = '${dir?.path}/update_app.apk';

      await dio.download(
        url,
        savePath,
        onReceiveProgress: onReceiveProgress,
      );
      
      return savePath;
    } catch (e) {
      debugPrint('Download error: $e');
      return null;
    }
  }

  static Future<void> _installApk(String filePath) async {
    try {
      await OpenFilex.open(filePath);
    } catch (e) {
      debugPrint('Install error: $e');
    }
  }
}
