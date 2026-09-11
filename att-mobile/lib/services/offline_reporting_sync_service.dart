import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/utils/image_utils.dart';

class OfflineReportingSyncService {
  static const String _storageKey = 'pending_offline_reports';

  /**
   * Save an unsubmitted report to local queue.
   */
  static Future<void> saveToQueue({
    required int templateId,
    required String templateTitle,
    required String? storeName,
    int? workLocationId,
    int? itineraryItemId,
    double? latitude,
    double? longitude,
    String? address,
    bool isWithinRadius = true,
    required Map<String, dynamic> values,
    required Map<String, String> photoPaths,
    required Map<String, String> watermarkTexts,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    List<String> queue = prefs.getStringList(_storageKey) ?? [];

    // Check if an identical report is already in the offline queue
    final valuesJsonStr = jsonEncode(values);
    final bool isDuplicate = queue.any((itemStr) {
      try {
        final existing = jsonDecode(itemStr) as Map<String, dynamic>;
        return existing['template_id'] == templateId &&
               existing['work_location_id'] == workLocationId &&
               jsonEncode(existing['values']) == valuesJsonStr;
      } catch (_) {
        return false;
      }
    });

    if (isDuplicate) {
      debugPrint('Report already exists in offline queue, skipping duplicate.');
      return;
    }

    final reportItem = {
      'id': 'offline_${DateTime.now().millisecondsSinceEpoch}',
      'template_id': templateId,
      'template_title': templateTitle,
      'store_name': storeName,
      'work_location_id': workLocationId,
      'itinerary_item_id': itineraryItemId,
      'latitude': latitude,
      'longitude': longitude,
      'address': address,
      'is_within_radius': isWithinRadius,
      'values': values,
      'photo_paths': photoPaths,
      'watermark_texts': watermarkTexts,
      'created_at': DateTime.now().toIso8601String(),
    };

    queue.add(jsonEncode(reportItem));
    await prefs.setStringList(_storageKey, queue);
    debugPrint('Report saved to offline queue. Total pending: ${queue.length}');
  }

  /**
   * Get total pending offline reports count.
   */
  static Future<int> getPendingCount() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> queue = prefs.getStringList(_storageKey) ?? [];
    return queue.length;
  }

  /**
   * Get all pending items.
   */
  static Future<List<Map<String, dynamic>>> getPendingItems() async {
    final prefs = await SharedPreferences.getInstance();
    List<String> queue = prefs.getStringList(_storageKey) ?? [];
    return queue.map((e) => jsonDecode(e) as Map<String, dynamic>).toList();
  }

  /**
   * Sync all pending offline reports to server (returns success count).
   */
  static Future<int> syncAllPending({required String token}) async {
    final result = await syncAllPendingDetailed(token: token);
    return result['success'] as int? ?? 0;
  }

  /**
   * Sync all pending offline reports with detailed outcome statistics.
   */
  static Future<Map<String, dynamic>> syncAllPendingDetailed({required String token}) async {
    final prefs = await SharedPreferences.getInstance();
    List<String> queue = prefs.getStringList(_storageKey) ?? [];
    if (queue.isEmpty) {
      return {'success': 0, 'failed': 0, 'total': 0, 'last_error': null};
    }

    int successCount = 0;
    int failedCount = 0;
    List<String> remainingQueue = [];
    String? lastError;

    for (String itemStr in queue) {
      try {
        final item = jsonDecode(itemStr) as Map<String, dynamic>;
        final uploadResult = await _uploadSingleReport(item, token);
        if (uploadResult['success'] == true) {
          successCount++;
        } else {
          failedCount++;
          remainingQueue.add(itemStr);
          lastError = uploadResult['message']?.toString();
        }
      } catch (e) {
        debugPrint('Error syncing single report: $e');
        failedCount++;
        remainingQueue.add(itemStr);
        lastError = e.toString();
      }
    }

    await prefs.setStringList(_storageKey, remainingQueue);
    return {
      'success': successCount,
      'failed': failedCount,
      'total': queue.length,
      'last_error': lastError,
    };
  }

  /**
   * Ensure any file being uploaded is compressed to WebP if > 350 KB or not .webp.
   */
  static Future<File> _ensureCompressedWebP(File file) async {
    try {
      final isWebP = file.path.toLowerCase().endsWith('.webp');
      final length = await file.length();
      if (!isWebP || length > 350 * 1024) {
        final compressed = await ImageUtils.compressAndGetWebP(file);
        if (compressed != null && await compressed.exists()) {
          debugPrint('Offline photo compressed to WebP: ${file.path} -> ${compressed.path}');
          return compressed;
        }
      }
    } catch (e) {
      debugPrint('Error compressing offline photo: $e');
    }
    return file;
  }

  static Future<Map<String, dynamic>> _uploadSingleReport(Map<String, dynamic> item, String token) async {
    final uri = Uri.parse('${Constants.baseUrl}/reporting/submit');
    final request = http.MultipartRequest('POST', uri);

    request.headers.addAll({
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    });

    request.fields['report_template_id'] = item['template_id'].toString();
    if (item['store_name'] != null) request.fields['store_name'] = item['store_name'].toString();
    if (item['work_location_id'] != null) request.fields['work_location_id'] = item['work_location_id'].toString();
    if (item['itinerary_item_id'] != null) request.fields['itinerary_item_id'] = item['itinerary_item_id'].toString();
    if (item['latitude'] != null) request.fields['latitude'] = item['latitude'].toString();
    if (item['longitude'] != null) request.fields['longitude'] = item['longitude'].toString();
    if (item['address'] != null) request.fields['address'] = item['address'].toString();
    if (item['created_at'] != null) request.fields['created_at'] = item['created_at'].toString();
    request.fields['is_within_radius'] = (item['is_within_radius'] == true) ? '1' : '0';

    // Values JSON
    if (item['values'] != null) {
      request.fields['values'] = jsonEncode(item['values']);
    }

    // Watermark texts
    final wmMap = item['watermark_texts'] as Map<String, dynamic>? ?? {};
    wmMap.forEach((key, val) {
      request.fields['wm_$key'] = val.toString();
    });

    // Photos (supports single file or JSON list of files, automatically compressed to WebP)
    final photosMap = item['photo_paths'] as Map<String, dynamic>? ?? {};
    for (final entry in photosMap.entries) {
      final fieldKey = entry.key;
      List<String> paths = [];
      final rawVal = entry.value;

      if (rawVal is List) {
        paths = rawVal.map((e) => e.toString()).toList();
      } else {
        final str = rawVal.toString().trim();
        if (str.startsWith('[') && str.endsWith(']')) {
          try {
            final decoded = jsonDecode(str);
            if (decoded is List) {
              paths = decoded.map((e) => e.toString()).toList();
            }
          } catch (_) {
            paths = [str];
          }
        } else {
          paths = [str];
        }
      }

      if (paths.length > 1) {
        for (int i = 0; i < paths.length; i++) {
          final f = File(paths[i]);
          if (await f.exists()) {
            final uploadFile = await _ensureCompressedWebP(f);
            request.files.add(await http.MultipartFile.fromPath('photo_${fieldKey}_$i', uploadFile.path));
          }
        }
      } else if (paths.isNotEmpty) {
        final f = File(paths.first);
        if (await f.exists()) {
          final uploadFile = await _ensureCompressedWebP(f);
          request.files.add(await http.MultipartFile.fromPath('photo_$fieldKey', uploadFile.path));
          if (!fieldKey.startsWith('photo_')) {
            request.files.add(await http.MultipartFile.fromPath(fieldKey, uploadFile.path));
          }
        }
      }
    }

    try {
      final streamedResponse = await request.send().timeout(const Duration(seconds: 90));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode >= 200 && response.statusCode < 300) {
        return {'success': true, 'message': 'OK'};
      } else {
        String errorMsg = 'Server mengembalikan status ${response.statusCode}';
        try {
          final resData = jsonDecode(response.body);
          errorMsg = resData['message'] ?? resData['error'] ?? errorMsg;
        } catch (_) {}
        debugPrint('Upload failed: ${response.statusCode} - ${response.body}');
        return {'success': false, 'message': errorMsg};
      }
    } catch (e) {
      debugPrint('Upload exception during offline sync: $e');
      return {'success': false, 'message': 'Koneksi gagal atau timeout: $e'};
    }
  }
}
