import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:att_mobile/models/report_template_model.dart';
import 'package:att_mobile/models/report_submission_model.dart';
import 'package:att_mobile/services/offline_reporting_sync_service.dart';
import 'package:att_mobile/utils/constants.dart';

class DynamicReportingProvider with ChangeNotifier {
  List<ReportTemplateModel> _templates = [];
  List<ReportSubmissionModel> _history = [];
  List<dynamic> _stores = [];
  Map<String, dynamic>? _cutoffInfo;
  String? _defaultArea;
  bool _isLoading = false;
  int _pendingOfflineCount = 0;
  String? _errorMessage;

  List<ReportTemplateModel> get templates => _templates;
  List<ReportSubmissionModel> get history => _history;
  List<dynamic> get stores => _stores;
  Map<String, dynamic>? get cutoffInfo => _cutoffInfo;
  String? get defaultArea => _defaultArea;
  bool get isLoading => _isLoading;
  int get pendingOfflineCount => _pendingOfflineCount;
  String? get errorMessage => _errorMessage;

  /**
   * Fetch templates from server or offline cache.
   */
  Future<void> fetchTemplates(String token, {bool forceRefresh = false, int? storeId}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final prefs = await SharedPreferences.getInstance();

    // Cek cache offline terlebih dahulu jika ada
    final cachedData = prefs.getString('cached_report_templates');
    final cachedCutoff = prefs.getString('cached_reporting_cutoff_info');
    if (cachedCutoff != null && !forceRefresh) {
      try {
        _cutoffInfo = jsonDecode(cachedCutoff) as Map<String, dynamic>;
      } catch (_) {}
    }
    if (cachedData != null && !forceRefresh) {
      try {
        final decoded = jsonDecode(cachedData) as List;
        _templates = ReportTemplateModel.applyDuluxSequence(
          decoded.map((item) => ReportTemplateModel.fromJson(item)).toList(),
        );
        notifyListeners();
      } catch (_) {}
    }

    try {
      var uriStr = '${Constants.baseUrl}/reporting/templates';
      if (storeId != null) {
        uriStr += '?store_id=$storeId';
      }

      final response = await http.get(
        Uri.parse(uriStr),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          final List rawList = data['data'] ?? [];
          _templates = ReportTemplateModel.applyDuluxSequence(
            rawList.map((item) => ReportTemplateModel.fromJson(item)).toList(),
          );
          
          if (data['cutoff_info'] != null) {
            _cutoffInfo = data['cutoff_info'] as Map<String, dynamic>;
            await prefs.setString('cached_reporting_cutoff_info', jsonEncode(_cutoffInfo));
          }
          
          // Simpan ke local cache offline
          await prefs.setString('cached_report_templates', jsonEncode(rawList));
        }
      }
    } catch (e) {
      debugPrint('Error fetching report templates: $e');
      if (_templates.isEmpty) {
        _errorMessage = 'Gagal memuat template laporan.';
      }
    } finally {
      _isLoading = false;
      await refreshPendingCount();
      notifyListeners();
    }
  }

  /**
   * Fetch submission history.
   */
  Future<void> fetchHistory(String token) async {
    try {
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/reporting/history'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          final List rawList = data['data'] ?? [];
          _history = rawList.map((item) => ReportSubmissionModel.fromJson(item)).toList();
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Error fetching report history: $e');
    }
  }

  /**
   * Fetch active reporting stores/locations for the employee's principal.
   */
  Future<void> fetchStores(String token, {bool forceRefresh = false}) async {
    final prefs = await SharedPreferences.getInstance();

    // Baca cache offline jika ada
    final cached = prefs.getString('cached_reporting_stores');
    final cachedArea = prefs.getString('cached_reporting_default_area');
    if (cachedArea != null) {
      _defaultArea = cachedArea;
    }
    if (cached != null && !forceRefresh) {
      try {
        _stores = jsonDecode(cached) as List;
        notifyListeners();
      } catch (_) {}
    }

    try {
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/reporting/stores'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _stores = data['data'] ?? [];
          _defaultArea = data['default_area']?.toString();
          await prefs.setString('cached_reporting_stores', jsonEncode(_stores));
          if (_defaultArea != null) {
            await prefs.setString('cached_reporting_default_area', _defaultArea!);
          }
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Error fetching reporting stores: $e');
    }
  }

  /**
   * Submit report with online-first, offline-fallback logic.
   */
  Future<Map<String, dynamic>> submitReport({
    required String token,
    required int templateId,
    required String templateTitle,
    String? storeName,
    int? workLocationId,
    int? itineraryItemId,
    double? latitude,
    double? longitude,
    String? address,
    bool isWithinRadius = true,
    required Map<String, dynamic> values,
    required Map<String, dynamic> photoFiles,
    required Map<String, String> watermarkTexts,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final uri = Uri.parse('${Constants.baseUrl}/reporting/submit');
      final request = http.MultipartRequest('POST', uri);

      request.headers.addAll({
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      });

      request.fields['report_template_id'] = templateId.toString();
      if (storeName != null) request.fields['store_name'] = storeName;
      if (workLocationId != null) request.fields['work_location_id'] = workLocationId.toString();
      if (itineraryItemId != null) request.fields['itinerary_item_id'] = itineraryItemId.toString();
      if (latitude != null) request.fields['latitude'] = latitude.toString();
      if (longitude != null) request.fields['longitude'] = longitude.toString();
      if (address != null) request.fields['address'] = address;
      request.fields['is_within_radius'] = isWithinRadius ? '1' : '0';

      // Values JSON
      request.fields['values'] = jsonEncode(values);

      // Watermarks
      watermarkTexts.forEach((key, val) {
        request.fields['wm_$key'] = val;
      });

      // Photos (support single File or List<File>)
      for (final entry in photoFiles.entries) {
        final fieldId = entry.key;
        final val = entry.value;
        if (val is List<File>) {
          for (int i = 0; i < val.length; i++) {
            final f = val[i];
            if (await f.exists()) {
              request.files.add(await http.MultipartFile.fromPath('photo_${fieldId}_$i', f.path));
            }
          }
        } else if (val is File) {
          if (await val.exists()) {
            request.files.add(await http.MultipartFile.fromPath('photo_$fieldId', val.path));
          }
        }
      }

      final streamedResponse = await request.send().timeout(const Duration(seconds: 40));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode >= 200 && response.statusCode < 300) {
        final resData = jsonDecode(response.body);
        _isLoading = false;
        fetchHistory(token);
        notifyListeners();
        return {
          'success': true,
          'message': resData['message'] ?? 'Laporan berhasil dikirim.',
          'is_offline': false,
        };
      } else {
        throw Exception('Server returned ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      debugPrint('Network submit failed, saving to offline queue: $e');

      // Simpan ke offline queue
      Map<String, String> photoPaths = {};
      photoFiles.forEach((key, val) {
        if (val is List<File>) {
          photoPaths[key] = jsonEncode(val.map((f) => f.path).toList());
        } else if (val is File) {
          photoPaths[key] = val.path;
        }
      });

      await OfflineReportingSyncService.saveToQueue(
        templateId: templateId,
        templateTitle: templateTitle,
        storeName: storeName,
        workLocationId: workLocationId,
        itineraryItemId: itineraryItemId,
        latitude: latitude,
        longitude: longitude,
        address: address,
        isWithinRadius: isWithinRadius,
        values: values,
        photoPaths: photoPaths,
        watermarkTexts: watermarkTexts,
      );

      await refreshPendingCount();
      _isLoading = false;
      notifyListeners();

      return {
        'success': true,
        'message': 'Laporan tersimpan di HP (Offline) dan akan terkirim otomatis saat online.',
        'is_offline': true,
      };
    }
  }

  /**
   * Update an existing report submission.
   */
  Future<Map<String, dynamic>> updateReport({
    required String token,
    required int submissionId,
    String? storeName,
    int? workLocationId,
    String? address,
    required Map<String, dynamic> values,
    required Map<String, dynamic> photoFiles,
    Map<String, List<String>>? existingPhotos,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final uri = Uri.parse('${Constants.baseUrl}/reporting/submissions/$submissionId');
      final request = http.MultipartRequest('POST', uri);

      request.headers.addAll({
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      });

      if (storeName != null) request.fields['store_name'] = storeName;
      if (workLocationId != null) request.fields['work_location_id'] = workLocationId.toString();
      if (address != null) request.fields['address'] = address;

      // Values JSON
      request.fields['values'] = jsonEncode(values);

      // Existing photos to retain
      if (existingPhotos != null) {
        existingPhotos.forEach((fieldId, urls) {
          request.fields['existing_photos_$fieldId'] = jsonEncode(urls);
        });
      }

      // Photos (support single File or List<File>)
      for (final entry in photoFiles.entries) {
        final fieldId = entry.key;
        final val = entry.value;
        if (val is List<File>) {
          for (int i = 0; i < val.length; i++) {
            final f = val[i];
            if (await f.exists()) {
              request.files.add(await http.MultipartFile.fromPath('photo_${fieldId}_$i', f.path));
            }
          }
        } else if (val is File) {
          if (await val.exists()) {
            request.files.add(await http.MultipartFile.fromPath('photo_$fieldId', val.path));
          }
        }
      }

      final streamedResponse = await request.send().timeout(const Duration(seconds: 40));
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode >= 200 && response.statusCode < 300) {
        final resData = jsonDecode(response.body);
        _isLoading = false;
        await fetchHistory(token);
        notifyListeners();
        return {
          'success': true,
          'message': resData['message'] ?? 'Laporan berhasil diperbarui.',
        };
      } else {
        final resData = jsonDecode(response.body);
        _isLoading = false;
        notifyListeners();
        return {
          'success': false,
          'message': resData['message'] ?? 'Gagal memperbarui laporan.',
        };
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {
        'success': false,
        'message': 'Gagal memperbarui laporan: $e',
      };
    }
  }

  /**
   * Refresh pending offline reports count.
   */
  Future<void> refreshPendingCount() async {
    _pendingOfflineCount = await OfflineReportingSyncService.getPendingCount();
    notifyListeners();
  }

  /**
   * Sync pending offline reports now.
   */
  Future<int> syncPending(String token) async {
    _isLoading = true;
    notifyListeners();

    final syncedCount = await OfflineReportingSyncService.syncAllPending(token: token);
    await refreshPendingCount();
    await fetchHistory(token);

    _isLoading = false;
    notifyListeners();
    return syncedCount;
  }

  /**
   * Check reporting compliance for Checkout or Visit-out.
   */
  Future<Map<String, dynamic>> checkCompliance(
    String token, {
    required String type,
    int? workLocationId,
  }) async {
    try {
      var url = '${Constants.baseUrl}/reporting/check-compliance?type=$type';
      if (workLocationId != null) {
        url += '&work_location_id=$workLocationId';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'can_proceed': data['can_proceed'] == true,
          'pending_reports': data['pending_reports'] is List ? List<String>.from(data['pending_reports']) : <String>[],
          'message': data['message']?.toString() ?? '',
        };
      }
    } catch (e) {
      debugPrint('Error checking reporting compliance: $e');
    }

    // Local fallback check based on loaded templates if offline or error
    final pending = <String>[];
    for (final t in _templates) {
      if (t.isTodayScheduled && !t.isCompletedToday) {
        pending.add(t.title);
      }
    }

    return {
      'can_proceed': pending.isEmpty,
      'pending_reports': pending,
      'message': pending.isEmpty ? 'Semua laporan wajib selesai.' : 'Masih ada laporan yang belum selesai.',
    };
  }

  /**
   * Fetch OOS reference/history for a specific store.
   */
  Future<Map<String, dynamic>?> fetchOosHistory(String token, int storeId) async {
    try {
      final url = '${Constants.baseUrl}/reporting/oos-history?store_id=$storeId';
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          return Map<String, dynamic>.from(data['data']);
        }
      }
    } catch (e) {
      debugPrint('Error fetching OOS history: $e');
    }
    return null;
  }

  /**
   * Cari profil pelanggan terdaftar berdasarkan No. HP / WhatsApp.
   */
  Future<Map<String, dynamic>?> lookupCustomer(String token, String phone) async {
    try {
      final clean = phone.replaceAll(RegExp(r'[^0-9]'), '');
      if (clean.length < 8) return null;
      final url = '${Constants.baseUrl}/reporting/customer-lookup?phone=$clean';
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['found'] == true && data['data'] != null) {
          return Map<String, dynamic>.from(data['data']);
        }
      }
    } catch (e) {
      debugPrint('Error lookup customer: $e');
    }
    return null;
  }
}
