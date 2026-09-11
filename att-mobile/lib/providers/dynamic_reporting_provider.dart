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

  List<String> _competitorBrands = [];
  Map<String, List<Map<String, dynamic>>> _competitorSubbrandsByBrand = {};
  List<Map<String, dynamic>> _allCompetitorProducts = [];

  List<ReportTemplateModel> get templates => _templates;
  List<ReportSubmissionModel> get history => _history;
  List<dynamic> get stores => _stores;
  Map<String, dynamic>? get cutoffInfo => _cutoffInfo;
  String? get defaultArea => _defaultArea;
  bool get isLoading => _isLoading;
  int get pendingOfflineCount => _pendingOfflineCount;
  String? get errorMessage => _errorMessage;

  List<String> get competitorBrands => _competitorBrands;
  Map<String, List<Map<String, dynamic>>> get competitorSubbrandsByBrand => _competitorSubbrandsByBrand;
  List<Map<String, dynamic>> get allCompetitorProducts => _allCompetitorProducts;


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
            if (!fieldId.startsWith('photo_')) {
              request.files.add(await http.MultipartFile.fromPath(fieldId, val.path));
            }
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
      } else if (response.statusCode == 422) {
        _isLoading = false;
        notifyListeners();
        String errorMsg = 'Validasi form gagal.';
        try {
          final errData = jsonDecode(response.body);
          errorMsg = errData['message'] ?? errData['error'] ?? errorMsg;
        } catch (_) {}
        return {
          'success': false,
          'message': errorMsg,
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
            if (!fieldId.startsWith('photo_')) {
              request.files.add(await http.MultipartFile.fromPath(fieldId, val.path));
            }
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
      if (clean.length < 4) return null;
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
        if (data is Map<String, dynamic>) {
          return data;
        }
      }
    } catch (e) {
      debugPrint('Error lookup customer: $e');
    }
    return null;
  }

  /**
   * Fetch master data produk kompetitor (Brands & Subbrands) untuk formulir CBP.
   */
  Future<void> fetchCompetitorProducts(String token, {bool forceRefresh = false}) async {
    final prefs = await SharedPreferences.getInstance();

    // 1. Coba baca dari cache lokal terlebih dahulu jika tidak dipaksa refresh
    final cachedData = prefs.getString('cached_competitor_products');
    if (cachedData != null && !forceRefresh) {
      try {
        final decoded = jsonDecode(cachedData) as Map<String, dynamic>;
        _applyCompetitorData(decoded);
        notifyListeners();
      } catch (_) {}
    }

    try {
      final url = '${Constants.baseUrl}/reporting/competitor-products';
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          _applyCompetitorData(data);
          await prefs.setString('cached_competitor_products', response.body);
          notifyListeners();
          return;
        }
      }
    } catch (e) {
      debugPrint('Error fetching competitor products from server: $e');
    }

    // 2. Fallback jika offline dan cache belum ada
    if (_competitorBrands.isEmpty) {
      _applyDefaultCompetitorMatrix();
      notifyListeners();
    }
  }

  void _applyCompetitorData(Map<String, dynamic> data) {
    if (data['brands'] is List) {
      _competitorBrands = (data['brands'] as List).map((e) => e.toString()).toList();
    }
    if (data['subbrands_by_brand'] is Map) {
      final map = <String, List<Map<String, dynamic>>>{};
      (data['subbrands_by_brand'] as Map).forEach((k, v) {
        if (v is List) {
          map[k.toString()] = v.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
        }
      });
      _competitorSubbrandsByBrand = map;
    }
    if (data['products'] is List) {
      _allCompetitorProducts = (data['products'] as List).whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    }
  }

  void _applyDefaultCompetitorMatrix() {
    _competitorBrands = [
      'JOTUN',
      'NIPPON PAINT',
      'AVIAN / NO DROP / LENKOTE',
      'MOWILEX',
      'PROPAN',
      'KANSAI / DANAPAINT',
      'PACIFIC PAINT',
      'MERK LAINNYA',
    ];

    _competitorSubbrandsByBrand = {
      'JOTUN': [
        {'subbrand': 'Majestic True Beauty Matt / Sheen', 'category': 'Interior Premium', 'benchmark_price_tin': 125000, 'benchmark_price_galon': 285000, 'benchmark_price_pail': 2150000},
        {'subbrand': 'Majestic Perfect Beauty & Care', 'category': 'Interior Super Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 340000, 'benchmark_price_pail': 2580000},
        {'subbrand': 'Jotashield Antifade Colours', 'category': 'Eksterior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 375000, 'benchmark_price_pail': 2850000},
        {'subbrand': 'Essence Cover Plus', 'category': 'Interior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 195000, 'benchmark_price_pail': 980000},
        {'subbrand': 'Essence Tough Shield', 'category': 'Eksterior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 245000, 'benchmark_price_pail': 1250000},
        {'subbrand': 'WaterGuard', 'category': 'Waterproofing', 'benchmark_price_tin': 0, 'benchmark_price_galon': 215000, 'benchmark_price_pail': 1020000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'NIPPON PAINT': [
        {'subbrand': 'Vinilex Regular (White & Color)', 'category': 'Interior Medium', 'benchmark_price_tin': 45000, 'benchmark_price_galon': 165000, 'benchmark_price_pail': 765000},
        {'subbrand': 'Vinilex Silver-Ion', 'category': 'Interior Medium Plus', 'benchmark_price_tin': 0, 'benchmark_price_galon': 195000, 'benchmark_price_pail': 895000},
        {'subbrand': 'Spot-less (Anti Noda)', 'category': 'Interior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 275000, 'benchmark_price_pail': 2050000},
        {'subbrand': 'Weatherbond', 'category': 'Eksterior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 345000, 'benchmark_price_pail': 2650000},
        {'subbrand': 'Elastex Waterproof 3-in-1', 'category': 'Waterproofing', 'benchmark_price_tin': 0, 'benchmark_price_galon': 210000, 'benchmark_price_pail': 990000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'AVIAN / NO DROP / LENKOTE': [
        {'subbrand': 'Sunguard All-in-One', 'category': 'Eksterior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 335000, 'benchmark_price_pail': 2550000},
        {'subbrand': 'Supersilk Anti Noda', 'category': 'Interior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 265000, 'benchmark_price_pail': 1950000},
        {'subbrand': 'Avitex Interior', 'category': 'Interior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 145000, 'benchmark_price_pail': 675000},
        {'subbrand': 'No Drop (Pelapis Anti Bocor)', 'category': 'Waterproofing', 'benchmark_price_tin': 58000, 'benchmark_price_galon': 205000, 'benchmark_price_pail': 975000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'MOWILEX': [
        {'subbrand': 'Weathercoat Regular', 'category': 'Eksterior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 365000, 'benchmark_price_pail': 2750000},
        {'subbrand': 'Emulsion (Interior Premium)', 'category': 'Interior Premium', 'benchmark_price_tin': 110000, 'benchmark_price_galon': 255000, 'benchmark_price_pail': 1950000},
        {'subbrand': 'Cendana Interior', 'category': 'Interior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 160000, 'benchmark_price_pail': 740000},
        {'subbrand': 'WP02 Waterproof', 'category': 'Waterproofing', 'benchmark_price_tin': 0, 'benchmark_price_galon': 225000, 'benchmark_price_pail': 1050000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'PROPAN': [
        {'subbrand': 'Decorshield', 'category': 'Eksterior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 325000, 'benchmark_price_pail': 2450000},
        {'subbrand': 'Decorcryl', 'category': 'Interior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 245000, 'benchmark_price_pail': 1850000},
        {'subbrand': 'Eco Emulsion', 'category': 'Interior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 140000, 'benchmark_price_pail': 650000},
        {'subbrand': 'Ultraproof', 'category': 'Waterproofing', 'benchmark_price_tin': 0, 'benchmark_price_galon': 205000, 'benchmark_price_pail': 960000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'KANSAI / DANAPAINT': [
        {'subbrand': 'Sincere', 'category': 'Interior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 240000, 'benchmark_price_pail': 1800000},
        {'subbrand': 'Ruby', 'category': 'Interior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 135000, 'benchmark_price_pail': 620000},
        {'subbrand': 'Danacryl', 'category': 'Interior Premium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 260000, 'benchmark_price_pail': 1950000},
        {'subbrand': 'Rainkote', 'category': 'Waterproofing', 'benchmark_price_tin': 0, 'benchmark_price_galon': 195000, 'benchmark_price_pail': 920000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'PACIFIC PAINT': [
        {'subbrand': 'Metrolite', 'category': 'Interior Medium', 'benchmark_price_tin': 0, 'benchmark_price_galon': 130000, 'benchmark_price_pail': 600000},
        {'subbrand': 'Glotex', 'category': 'Wood & Metal', 'benchmark_price_tin': 68000, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
        {'subbrand': 'Finatex', 'category': 'Interior Economy', 'benchmark_price_tin': 0, 'benchmark_price_galon': 70000, 'benchmark_price_pail': 260000},
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
      'MERK LAINNYA': [
        {'subbrand': 'LAINNYA / INPUT MANUAL', 'category': 'Umum', 'benchmark_price_tin': 0, 'benchmark_price_galon': 0, 'benchmark_price_pail': 0},
      ],
    };
  }
}

