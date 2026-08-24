import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:att_mobile/utils/constants.dart';

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
   * Sync all pending offline reports to server.
   */
  static Future<int> syncAllPending({required String token}) async {
    final prefs = await SharedPreferences.getInstance();
    List<String> queue = prefs.getStringList(_storageKey) ?? [];
    if (queue.isEmpty) return 0;

    int successCount = 0;
    List<String> remainingQueue = [];

    for (String itemStr in queue) {
      try {
        final item = jsonDecode(itemStr) as Map<String, dynamic>;
        bool success = await _uploadSingleReport(item, token);
        if (success) {
          successCount++;
        } else {
          remainingQueue.add(itemStr);
        }
      } catch (e) {
        debugPrint('Error syncing single report: $e');
        remainingQueue.add(itemStr);
      }
    }

    await prefs.setStringList(_storageKey, remainingQueue);
    return successCount;
  }

  static Future<bool> _uploadSingleReport(Map<String, dynamic> item, String token) async {
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

    // Photos
    final photosMap = item['photo_paths'] as Map<String, dynamic>? ?? {};
    for (final entry in photosMap.entries) {
      final fieldKey = entry.key;
      final filePath = entry.value.toString();
      final file = File(filePath);
      if (await file.exists()) {
        request.files.add(await http.MultipartFile.fromPath('photo_$fieldKey', file.path));
      }
    }

    final streamedResponse = await request.send().timeout(const Duration(seconds: 30));
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return true;
    } else {
      debugPrint('Upload failed: ${response.statusCode} - ${response.body}');
      return false;
    }
  }
}
