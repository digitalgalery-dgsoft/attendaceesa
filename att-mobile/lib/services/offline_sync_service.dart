import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class OfflineSyncService {
  static const String keyOfflineQueue = 'offline_attendance_actions_queue';
  static const String keyCacheDashboardSchedule = 'cache_today_schedule';
  static const String keyCacheAttendanceStatus = 'cache_attendance_status';
  static const String keyCacheMonthlyHistory = 'cache_monthly_history';

  // ───────────────────────────────────────────────────────────────────────────
  // 1. LOCAL DATA CACHING (Bebas Blank Screen)
  // ───────────────────────────────────────────────────────────────────────────

  /// Simpan data JSON ke cache lokal SharedPreferences
  static Future<void> saveToCache(String key, dynamic data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonString = jsonEncode(data);
      await prefs.setString(key, jsonString);
      debugPrint('[OfflineCache] Saved cache for $key');
    } catch (e) {
      debugPrint('[OfflineCache] Error saving cache for $key: $e');
    }
  }

  /// Ambil data dari cache lokal SharedPreferences
  static Future<dynamic> getFromCache(String key) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonString = prefs.getString(key);
      if (jsonString != null && jsonString.isNotEmpty) {
        return jsonDecode(jsonString);
      }
    } catch (e) {
      debugPrint('[OfflineCache] Error reading cache for $key: $e');
    }
    return null;
  }

  // ───────────────────────────────────────────────────────────────────────────
  // 2. OFFLINE QUEUE MANAGEMENT (Antrean Presensi Offline)
  // ───────────────────────────────────────────────────────────────────────────

  /// Dapatkan jumlah antrean data presensi offline yang belum terkirim
  static Future<int> getPendingQueueCount() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final queue = prefs.getStringList(keyOfflineQueue) ?? [];
      return queue.length;
    } catch (e) {
      return 0;
    }
  }

  /// Masukkan aksi presensi (Check-in, Check-out, Visit) ke antrean lokal
  static Future<void> enqueueAction({
    required String actionType,
    required Map<String, String> fields,
    String? localPhotoPath,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final queue = prefs.getStringList(keyOfflineQueue) ?? [];

      String? permanentPhotoPath;
      // Pindahkan foto ke direktori dokumen permanen aplikasi agar tidak terhapus cache OS
      if (localPhotoPath != null && File(localPhotoPath).existsSync()) {
        final docDir = await getApplicationDocumentsDirectory();
        final fileName = 'offline_${DateTime.now().millisecondsSinceEpoch}.jpg';
        final savedImage = await File(localPhotoPath).copy('${docDir.path}/$fileName');
        permanentPhotoPath = savedImage.path;
      }

      final actionItem = {
        'id': 'off_${DateTime.now().millisecondsSinceEpoch}_${fields['log_type'] ?? actionType}',
        'action_type': actionType,
        'fields': fields,
        'photo_path': permanentPhotoPath,
        'offline_timestamp': DateTime.now().toIso8601String(),
        'created_at': DateTime.now().toIso8601String(),
      };

      queue.add(jsonEncode(actionItem));
      await prefs.setStringList(keyOfflineQueue, queue);
      debugPrint('[OfflineQueue] Enqueued $actionType. Total antrean: ${queue.length}');
    } catch (e) {
      debugPrint('[OfflineQueue] Failed to enqueue action: $e');
    }
  }

  // ───────────────────────────────────────────────────────────────────────────
  // 3. BACKGROUND SYNCHRONIZATION (Sinkronisasi Otomatis saat Online)
  // ───────────────────────────────────────────────────────────────────────────

  static bool _isSyncing = false;

  /// Sinkronisasi seluruh antrean data presensi offline ke server
  static Future<int> syncAllPendingActions(String token) async {
    if (_isSyncing) return 0;
    if (token.isEmpty) return 0;

    _isSyncing = true;
    int syncedSuccessCount = 0;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.reload();
      final queue = prefs.getStringList(keyOfflineQueue) ?? [];

      if (queue.isEmpty) {
        _isSyncing = false;
        return 0;
      }

      debugPrint('[OfflineSync] Memulai sinkronisasi ${queue.length} data offline...');
      final List<String> remainingQueue = List.from(queue);

      for (String itemJson in queue) {
        final Map<String, dynamic> item = jsonDecode(itemJson);
        final String actionType = item['action_type'] ?? 'attendance';
        final Map<String, dynamic> fields = Map<String, dynamic>.from(item['fields'] ?? {});
        final String? photoPath = item['photo_path'];
        final String timestamp = item['offline_timestamp'] ?? item['created_at'];

        bool isSuccess = false;

        try {
          if (actionType == 'visit_report') {
            // Sinkronisasi Laporan Kunjungan (Visit Report)
            final uri = Uri.parse('${Constants.baseUrl}/attendance/visit-report');
            var request = http.MultipartRequest('POST', uri);
            request.headers['Authorization'] = 'Bearer $token';
            request.headers['Accept'] = 'application/json';

            fields.forEach((k, v) {
              request.fields[k] = v.toString();
            });
            request.fields['timestamp'] = timestamp;

            if (photoPath != null && File(photoPath).existsSync()) {
              request.files.add(await http.MultipartFile.fromPath('photo', photoPath));
            }

            final streamedRes = await request.send().timeout(const Duration(seconds: 25));
            final res = await http.Response.fromStream(streamedRes);

            if (res.statusCode == 200 || res.statusCode == 201) {
              isSuccess = true;
            }
          } else {
            // Sinkronisasi Presensi (Check-in, Check-out, Visit In/Out)
            final uri = Uri.parse('${Constants.baseUrl}/attendance');
            var request = http.MultipartRequest('POST', uri);
            request.headers['Authorization'] = 'Bearer $token';
            request.headers['Accept'] = 'application/json';

            fields.forEach((k, v) {
              request.fields[k] = v.toString();
            });
            request.fields['timestamp'] = timestamp;

            if (photoPath != null && File(photoPath).existsSync()) {
              request.files.add(await http.MultipartFile.fromPath('photo', photoPath));
            }

            final streamedRes = await request.send().timeout(const Duration(seconds: 25));
            final res = await http.Response.fromStream(streamedRes);

            if (res.statusCode == 200 || res.statusCode == 201) {
              isSuccess = true;
            } else if (res.statusCode == 422) {
              // Jika server mengembalikan sudah check-in/duplikat, anggap sudah selesai dan hapus dari antrean
              isSuccess = true;
            }
          }

          if (isSuccess) {
            // Hapus file foto lokal setelah berhasil disinkronkan ke server
            if (photoPath != null && File(photoPath).existsSync()) {
              try {
                await File(photoPath).delete();
              } catch (_) {}
            }
            remainingQueue.remove(itemJson);
            syncedSuccessCount++;
            debugPrint('[OfflineSync] Berhasil sinkronisasi: ${item['id']}');
          } else {
            // Jika gagal karena kendala jaringan, hentikan loop sementara
            debugPrint('[OfflineSync] Gagal mengirim: ${item['id']}, akan dicoba lagi nanti.');
            break;
          }
        } catch (e) {
          debugPrint('[OfflineSync] Exception saat sinkron: $e');
          break; // Putus loop saat koneksi drop
        }
      }

      // Update sisa antrean yang tersisa
      await prefs.setStringList(keyOfflineQueue, remainingQueue);
    } catch (e) {
      debugPrint('[OfflineSync] Error proses sinkronisasi: $e');
    } finally {
      _isSyncing = false;
    }

    return syncedSuccessCount;
  }
}
