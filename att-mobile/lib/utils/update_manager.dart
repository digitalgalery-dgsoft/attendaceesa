import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
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

        if (serverVersion != null && serverVersion.isNotEmpty && downloadUrl != null && downloadUrl.isNotEmpty) {
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
          onPopInvokedWithResult: (didPop, result) {
            _isDialogShowing = false;
          },
          child: AlertDialog(
            title: const Text('Update Tersedia'),
            content: Text('Versi terbaru ($newVersion) telah tersedia. Silakan update aplikasi Anda untuk kelancaran absensi dan kestabilan sistem.'),
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
                  _downloadAndInstall(context, url);
                },
                child: const Text('Update Sekarang'),
              ),
            ],
          ),
        );
      },
    ).then((_) {
      _isDialogShowing = false;
    });
  }

  static Future<void> _downloadAndInstall(BuildContext context, String url) async {
    String message = 'Memulai proses update...';
    bool hasError = false;
    
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext dialogContext) {
        double progress = 0.0;
        
        return StatefulBuilder(builder: (context, setState) {
          // Hanya jalankan download sekali
          if (progress == 0.0 && message == 'Memulai proses update...' && !hasError) {
            _performDownload(url, (received, total) {
              if (total != -1) {
                setState(() {
                  progress = received / total;
                  message = 'Mengunduh: ${(progress * 100).toStringAsFixed(0)}%';
                });
              }
            }).then((filePath) {
              if (filePath != null) {
                if (dialogContext.mounted) {
                  Navigator.pop(dialogContext); // Close progress dialog
                }
                _installApk(filePath);
              } else {
                if (dialogContext.mounted) {
                  setState(() {
                    hasError = true;
                    message = 'Koneksi terputus saat aplikasi diminimize atau jaringan tidak stabil.';
                  });
                }
              }
            });
          }

          return PopScope(
            canPop: hasError,
            child: AlertDialog(
              title: const Text('Mengunduh Update'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (!hasError) LinearProgressIndicator(value: progress),
                  const SizedBox(height: 16),
                  Text(message, textAlign: TextAlign.center),
                ],
              ),
              actions: hasError
                  ? [
                      TextButton(
                        onPressed: () => Navigator.pop(dialogContext),
                        child: const Text('Tutup'),
                      ),
                      ElevatedButton(
                        onPressed: () {
                          setState(() {
                            hasError = false;
                            message = 'Memulai proses update...';
                            progress = 0.0;
                          });
                        },
                        child: const Text('Coba Lagi'),
                      ),
                    ]
                  : null,
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
