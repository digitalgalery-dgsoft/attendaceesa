import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/location_service.dart';
import '../services/offline_sync_service.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:geolocator/geolocator.dart';
import '../models/meeting_model.dart';

class AttendanceProvider with ChangeNotifier {
  bool _isLoading = false;
  bool get isLoading => _isLoading;

  String _error = '';
  String get error => _error;

  bool _isCheckedIn = false;
  bool get isCheckedIn => _isCheckedIn;

  bool _isVisiting = false;
  bool get isVisiting => _isVisiting;

  bool _hasFilledVisitReport = false;
  bool get hasFilledVisitReport => _hasFilledVisitReport;

  DateTime? _visitStartTime;
  DateTime? get visitStartTime => _visitStartTime;

  bool _hasCheckedOutToday = false;
  bool get hasCheckedOutToday => _hasCheckedOutToday;

  List<dynamic> _workLocations = [];
  List<dynamic> get workLocations => _workLocations;

  List<dynamic> _monthlyHistory = [];
  Map<String, dynamic> _logsByDate = {};
  Map<String, dynamic> _stats = {};
  Map<String, dynamic> _period = {};

  List<dynamic> get monthlyHistory => _monthlyHistory;
  Map<String, dynamic> get logsByDate => _logsByDate;
  Map<String, dynamic> get stats => _stats;
  Map<String, dynamic> get period => _period;

  List<dynamic> _todayLogs = [];
  List<dynamic> get todayLogs => _todayLogs;

  // ─── Schedule & Itinerary ─────────────────────────────────────────────────
  Map<String, dynamic>? _todaySchedule;
  Map<String, dynamic>? get todaySchedule => _todaySchedule;

  Map<String, dynamic>? _todayItinerary;
  Map<String, dynamic>? get todayItinerary => _todayItinerary;

  bool _canCheckin = false;
  bool get canCheckin => _canCheckin;

  bool _canVisit = false;
  bool get canVisit => _canVisit;

  bool _hasUnfinishedItinerary = false;
  bool get hasUnfinishedItinerary => _hasUnfinishedItinerary;

  String _checkinBlockMessage = '';
  String get checkinBlockMessage => _checkinBlockMessage;

  // Active permit for today
  bool _hasActivePermit = false;
  bool get hasActivePermit => _hasActivePermit;

  Map<String, dynamic>? _activePermit;
  Map<String, dynamic>? get activePermit => _activePermit;

  // ─── Meetings ─────────────────────────────────────────────────────────────
  List<MeetingModel> _todayMeetings = [];
  List<MeetingModel> get todayMeetings => _todayMeetings;

  MeetingModel? _activeMeeting;
  MeetingModel? get activeMeeting => _activeMeeting;

  bool get isInMeeting => _activeMeeting != null;

  DateTime? _meetingStartTime;
  DateTime? get meetingStartTime => _meetingStartTime;

  int _pendingOfflineCount = 0;
  int get pendingOfflineCount => _pendingOfflineCount;

  Future<void> refreshPendingOfflineCount() async {
    _pendingOfflineCount = await OfflineSyncService.getPendingQueueCount();
    notifyListeners();
  }

  // ─── Load jadwal hari ini + status absensi (Cache-First) ───────────────────
  Future<void> loadDashboardData() async {
    // 1. Coba muat data dari Cache Lokal terlebih dahulu agar layar tidak blank/loading lama
    await _loadFromLocalCache();
    await refreshPendingOfflineCount();

    if (_todaySchedule == null && _monthlyHistory.isEmpty) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      // 2. Jika ada token, coba sinkronisasikan antrean offline secara otomatis
      if (token != null && token.isNotEmpty) {
        final synced = await OfflineSyncService.syncAllPendingActions(token);
        if (synced > 0) {
          await refreshPendingOfflineCount();
        }
      }

      // 3. Ambil data terbaru dari server
      await Future.wait([
        _fetchTodaySchedule(),
        checkAttendanceStatus(),
        fetchTodayMeetings(),
      ]);
    } catch (e) {
      debugPrint('[Dashboard] Offline mode active: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> _loadFromLocalCache() async {
    try {
      final cachedSchedule = await OfflineSyncService.getFromCache(OfflineSyncService.keyCacheDashboardSchedule);
      if (cachedSchedule != null) {
        _canCheckin = cachedSchedule['can_checkin'] ?? false;
        _canVisit = cachedSchedule['can_visit'] ?? false;
        _hasUnfinishedItinerary = cachedSchedule['has_unfinished_itinerary'] ?? false;
        _checkinBlockMessage = cachedSchedule['message'] ?? '';
        _todaySchedule = cachedSchedule['schedule'];
        _todayItinerary = cachedSchedule['itinerary'];
      }

      final cachedStatus = await OfflineSyncService.getFromCache(OfflineSyncService.keyCacheAttendanceStatus);
      if (cachedStatus != null) {
        _isCheckedIn = cachedStatus['is_checked_in'] ?? false;
        _hasCheckedOutToday = cachedStatus['has_checked_out_today'] ?? false;
        _isVisiting = cachedStatus['is_visiting'] ?? false;
        _hasFilledVisitReport = cachedStatus['has_filled_visit_report'] ?? false;
      }
      notifyListeners();
    } catch (e) {
      debugPrint('[DashboardCache] Error reading local cache: $e');
    }
  }

  Future<void> _fetchTodaySchedule() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) {
        _canCheckin = false;
        _checkinBlockMessage = 'Token tidak ditemukan, silakan login ulang.';
        return;
      }

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/today-schedule'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));

      final data = json.decode(response.body);

      // Permit aktif untuk hari ini
      if (response.statusCode == 200 && data['has_active_permit'] == true) {
        _hasActivePermit = true;
        _activePermit = data['permit'] as Map<String, dynamic>;
        _canCheckin = false;
        _canVisit = false;
        _hasUnfinishedItinerary = false;
        _checkinBlockMessage = data['message'] ?? 'Anda memiliki izin yang disetujui hari ini.';
        _todaySchedule = null;
        _todayItinerary = null;
        await OfflineSyncService.saveToCache(OfflineSyncService.keyCacheDashboardSchedule, {
          'can_checkin': false,
          'can_visit': false,
          'has_unfinished_itinerary': false,
          'message': _checkinBlockMessage,
          'schedule': null,
          'itinerary': null,
        });
        return;
      }

      _hasActivePermit = false;
      _activePermit = null;

      if (response.statusCode == 200 && data['can_checkin'] == true) {
        _canCheckin = true;
        _todaySchedule = data['data']?['schedule'];
        _todayItinerary = data['data']?['itinerary'] ?? data['data']?['meta']?['itinerary'];
        _hasUnfinishedItinerary = data['has_unfinished_itinerary'] ?? data['data']?['has_unfinished_itinerary'] ?? false;
        
        // canVisit HANYA true jika karyawan memang memiliki itinerary hari ini dan masih ada yang belum selesai dikunjungi
        _canVisit = _hasUnfinishedItinerary && _todayItinerary != null && (_todayItinerary?['items'] as List? ?? []).isNotEmpty;
        _checkinBlockMessage = '';

        // Simpan ke Cache Lokal
        await OfflineSyncService.saveToCache(OfflineSyncService.keyCacheDashboardSchedule, {
          'can_checkin': true,
          'can_visit': _canVisit,
          'has_unfinished_itinerary': _hasUnfinishedItinerary,
          'message': '',
          'schedule': _todaySchedule,
          'itinerary': _todayItinerary,
        });
      } else {
        // Status code 403, 400, 422, atau response tidak memiliki jadwal
        _canCheckin = false;
        _canVisit = false;
        _hasUnfinishedItinerary = false;
        _checkinBlockMessage = data['message'] ?? 'Anda tidak memiliki jadwal kerja untuk hari ini. Silakan hubungi Admin.';
        _todaySchedule = null;
        _todayItinerary = null;

        await OfflineSyncService.saveToCache(OfflineSyncService.keyCacheDashboardSchedule, {
          'can_checkin': false,
          'can_visit': false,
          'has_unfinished_itinerary': false,
          'message': _checkinBlockMessage,
          'schedule': null,
          'itinerary': null,
        });
      }
    } catch (e) {
      debugPrint('Gagal fetch today schedule (mungkin offline): $e');
      // Jika offline, pertahankan data cache yang sudah dibaca di _loadFromLocalCache()
    }
  }

  Future<bool> checkAttendanceStatus() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) return false;

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/attendance/history'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _monthlyHistory = data['data'] as List;
        _todayLogs = data['today_logs'] as List;

        if (_monthlyHistory.isNotEmpty) {
          final lastAttendance = _monthlyHistory.first;
          final today = DateTime.now().toIso8601String().split('T').first;
          if (lastAttendance['attendance_date'] == today) {
            _isCheckedIn = lastAttendance['checkout_at'] == null;
            _hasCheckedOutToday = lastAttendance['checkout_at'] != null;
          } else {
            _isCheckedIn = false;
            _hasCheckedOutToday = false;
          }
        } else {
          _isCheckedIn = false;
          _hasCheckedOutToday = false;
        }

        if (_todayLogs.isNotEmpty) {
          final lastLog = _todayLogs.first;
          _isVisiting = lastLog['log_type'] == 'visit_in';
          _hasFilledVisitReport = lastLog['log_type'] == 'visit_report' || lastLog['log_type'] == 'visit_out';
          if (_isVisiting) {
            _visitStartTime = DateTime.tryParse(lastLog['logged_at'] ?? '');
          } else {
            _visitStartTime = null;
          }
        } else {
          _isVisiting = false;
          _hasFilledVisitReport = false;
          _visitStartTime = null;
        }

        // Simpan status absensi ke cache lokal
        await OfflineSyncService.saveToCache(OfflineSyncService.keyCacheAttendanceStatus, {
          'is_checked_in': _isCheckedIn,
          'has_checked_out_today': _hasCheckedOutToday,
          'is_visiting': _isVisiting,
          'has_filled_visit_report': _hasFilledVisitReport,
        });
      }
    } catch (e) {
      debugPrint('Error checking status (offline): $e');
    }
    return _isCheckedIn;
  }

  Future<void> fetchHistory({String? startDate, String? endDate}) async {
    final cacheKey = '${OfflineSyncService.keyCacheMonthlyHistory}_${startDate ?? "curr"}_${endDate ?? "curr"}';
    
    // Muat data dari cache terlebih dahulu jika ada
    final cached = await OfflineSyncService.getFromCache(cacheKey);
    if (cached != null) {
      _monthlyHistory = cached['data'] as List? ?? [];
      _stats = cached['stats'] as Map<String, dynamic>? ?? {};
      _period = cached['period'] as Map<String, dynamic>? ?? {};
      _logsByDate = cached['logs_by_date'] as Map<String, dynamic>? ?? {};
      notifyListeners();
    } else if (_monthlyHistory.isEmpty) {
      _isLoading = true;
      notifyListeners();
    }

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) {
        _isLoading = false;
        notifyListeners();
        return;
      }

      String query = '';
      if (startDate != null && endDate != null) {
        query = '?start_date=$startDate&end_date=$endDate';
      }

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/attendance/history$query'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _monthlyHistory = data['data'] as List? ?? [];
        _stats = data['stats'] as Map<String, dynamic>? ?? {};
        _period = data['period'] as Map<String, dynamic>? ?? {};
        _logsByDate = data['logs_by_date'] as Map<String, dynamic>? ?? {};

        // Simpan hasil ke cache lokal
        await OfflineSyncService.saveToCache(cacheKey, {
          'data': _monthlyHistory,
          'stats': _stats,
          'period': _period,
          'logs_by_date': _logsByDate,
        });
      }
    } catch (e) {
      debugPrint('Error fetching history (offline): $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchWorkLocations() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/work-locations'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _workLocations = data['data'];
        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error fetching work locations: $e');
    }
  }

  Future<Map<String, dynamic>> submitAttendance({
    required String type,
    required double latitude,
    required double longitude,
    String? imagePath,
    required bool isWeb,
    String? visitType,
    String? note,
    int? visitLocationId,
    String? scheduledType,
    int? scheduledWorkLocationId,
    int? scheduledMeetingId,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      var request = http.MultipartRequest('POST', Uri.parse('${Constants.baseUrl}/attendance'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['type'] = type;
      request.fields['latitude'] = latitude.toString();
      request.fields['longitude'] = longitude.toString();

      if (visitType != null) request.fields['visit_type'] = visitType;
      if (note != null) request.fields['note'] = note;
      if (visitLocationId != null) request.fields['visit_location_id'] = visitLocationId.toString();
      if (scheduledType != null) request.fields['scheduled_type'] = scheduledType;
      if (scheduledWorkLocationId != null) request.fields['scheduled_work_location_id'] = scheduledWorkLocationId.toString();
      if (scheduledMeetingId != null) request.fields['scheduled_meeting_id'] = scheduledMeetingId.toString();

      if (imagePath != null) {
        if (isWeb) {
          final imgResponse = await http.get(Uri.parse(imagePath));
          final bytes = imgResponse.bodyBytes;
          request.files.add(http.MultipartFile.fromBytes('photo', bytes, filename: 'selfie.jpg'));
        } else {
          request.files.add(await http.MultipartFile.fromPath('photo', imagePath));
        }
      }

      final streamedRes = await request.send().timeout(const Duration(seconds: 15));
      final response = await http.Response.fromStream(streamedRes);
      final decodedData = json.decode(response.body);

      if (response.statusCode == 200 || response.statusCode == 201) {
        await checkAttendanceStatus();

        _isLoading = false;
        notifyListeners();
        // Return type so the UI layer can start/stop LocationService AFTER navigation
        return {'success': true, 'message': decodedData['message'] ?? 'Berhasil', 'type': type};
      } else {
        _isLoading = false;
        notifyListeners();
        return {'success': false, 'message': decodedData['message'] ?? 'Gagal'};
      }
    } catch (e) {
      debugPrint('[Attendance] Jaringan offline/gagal: $e. Menyimpan aksi ke antrean lokal...');

      final fields = <String, String>{
        'type': type,
        'latitude': latitude.toString(),
        'longitude': longitude.toString(),
      };
      if (visitType != null) fields['visit_type'] = visitType;
      if (note != null) fields['note'] = note;
      if (visitLocationId != null) fields['visit_location_id'] = visitLocationId.toString();
      if (scheduledType != null) fields['scheduled_type'] = scheduledType;
      if (scheduledWorkLocationId != null) fields['scheduled_work_location_id'] = scheduledWorkLocationId.toString();
      if (scheduledMeetingId != null) fields['scheduled_meeting_id'] = scheduledMeetingId.toString();

      await OfflineSyncService.enqueueAction(
        actionType: 'attendance',
        fields: fields,
        localPhotoPath: imagePath,
      );

      // Perbarui status tampilan secara lokal
      if (type == 'check_in') {
        _isCheckedIn = true;
        _hasCheckedOutToday = false;
      } else if (type == 'check_out') {
        _isCheckedIn = false;
        _hasCheckedOutToday = true;
      } else if (type == 'visit_in') {
        _isVisiting = true;
        _hasFilledVisitReport = false;
        _visitStartTime = DateTime.now();
      } else if (type == 'visit_out') {
        _isVisiting = false;
        _visitStartTime = null;
      }

      await OfflineSyncService.saveToCache(OfflineSyncService.keyCacheAttendanceStatus, {
        'is_checked_in': _isCheckedIn,
        'has_checked_out_today': _hasCheckedOutToday,
        'is_visiting': _isVisiting,
        'has_filled_visit_report': _hasFilledVisitReport,
      });

      await refreshPendingOfflineCount();

      _isLoading = false;
      notifyListeners();

      return {
        'success': true,
        'is_offline': true,
        'message': 'Presensi tersimpan di perangkat (Mode Offline). Akan otomatis disinkronkan saat terhubung internet.',
        'type': type,
      };
    }
  }

  Future<bool> submitVisitReport({
    required AuthProvider authProvider,
    String? issue,
    String? actionTaken,
    String? notes,
    required String photoPath,
    required String metWith,
    required String position,
    String? targetType,
    String? targetQty,
    String? actualQty,
    String? targetValue,
    String? actualValue,
    String? deadline,
  }) async {
    _isLoading = true;
    _error = '';
    notifyListeners();

    double latitude = 0.0;
    double longitude = 0.0;

    try {
      final token = authProvider.token;
      if (token == null) throw Exception('Not authenticated');

      // Get current location
      try {
        final pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
        );
        latitude = pos.latitude;
        longitude = pos.longitude;
      } catch (e) {
        debugPrint('Failed to get location for visit report: $e');
      }

      var request = http.MultipartRequest('POST', Uri.parse('${Constants.baseUrl}/attendance/visit-report'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['met_with'] = metWith;
      request.fields['position'] = position;
      if (issue != null && issue.isNotEmpty) {
        request.fields['is_issue'] = '1';
        request.fields['issue'] = issue;
      } else {
        request.fields['is_issue'] = '0';
      }
      if (actionTaken != null && actionTaken.isNotEmpty) {
        request.fields['action_taken'] = actionTaken;
      }
      if (notes != null && notes.isNotEmpty) {
        request.fields['notes'] = notes;
      }
      if (targetType != null && targetType.isNotEmpty) {
        request.fields['target_type'] = targetType;
      }
      if (targetQty != null && targetQty.isNotEmpty) {
        request.fields['target_qty'] = targetQty;
      }
      if (actualQty != null && actualQty.isNotEmpty) {
        request.fields['actual_qty'] = actualQty;
      }
      if (targetValue != null && targetValue.isNotEmpty) {
        request.fields['target_value'] = targetValue;
      }
      if (actualValue != null && actualValue.isNotEmpty) {
        request.fields['actual_value'] = actualValue;
      }
      if (deadline != null && deadline.isNotEmpty) {
        request.fields['deadline'] = deadline;
      }
      request.fields['latitude'] = latitude.toString();
      request.fields['longitude'] = longitude.toString();

      request.files.add(await http.MultipartFile.fromPath('photo', photoPath));

      final streamedRes = await request.send().timeout(const Duration(seconds: 15));
      final response = await http.Response.fromStream(streamedRes);
      final decodedData = json.decode(response.body);

      if (response.statusCode == 200 || response.statusCode == 201) {
        await checkAttendanceStatus();
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = decodedData['message'] ?? 'Gagal menyimpan laporan';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      debugPrint('[VisitReport] Jaringan offline/gagal: $e. Menyimpan ke antrean lokal...');

      final fields = <String, String>{
        'met_with': metWith,
        'position': position,
        'is_issue': (issue != null && issue.isNotEmpty) ? '1' : '0',
        'latitude': latitude.toString(),
        'longitude': longitude.toString(),
      };
      if (issue != null && issue.isNotEmpty) fields['issue'] = issue;
      if (actionTaken != null && actionTaken.isNotEmpty) fields['action_taken'] = actionTaken;
      if (notes != null && notes.isNotEmpty) fields['notes'] = notes;
      if (targetType != null && targetType.isNotEmpty) fields['target_type'] = targetType;
      if (targetQty != null && targetQty.isNotEmpty) fields['target_qty'] = targetQty;
      if (actualQty != null && actualQty.isNotEmpty) fields['actual_qty'] = actualQty;
      if (targetValue != null && targetValue.isNotEmpty) fields['target_value'] = targetValue;
      if (actualValue != null && actualValue.isNotEmpty) fields['actual_value'] = actualValue;
      if (deadline != null && deadline.isNotEmpty) fields['deadline'] = deadline;

      await OfflineSyncService.enqueueAction(
        actionType: 'visit_report',
        fields: fields,
        localPhotoPath: photoPath,
      );

      _hasFilledVisitReport = true;
      await refreshPendingOfflineCount();

      _isLoading = false;
      notifyListeners();
      return true;
    }
  }

  // ─── Meeting Operations ───────────────────────────────────────────────────
  Future<void> fetchTodayMeetings() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) return;

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/meetings/today'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        if (decoded['status'] == 'success' && decoded['data'] is List) {
          _todayMeetings = (decoded['data'] as List)
              .map((item) => MeetingModel.fromJson(item))
              .toList();

          // Check if there is an active meeting in progress
          try {
            _activeMeeting = _todayMeetings.firstWhere((m) => m.isInMeeting);
            if (_activeMeeting?.myAttendance?['meet_in_at'] != null) {
              _meetingStartTime = DateTime.tryParse(_activeMeeting!.myAttendance!['meet_in_at']);
            }
          } catch (_) {
            _activeMeeting = null;
            _meetingStartTime = null;
          }
        }
      }
      notifyListeners();
    } catch (e) {
      debugPrint('Error fetching today meetings: $e');
    }
  }

  Future<Map<String, dynamic>> meetIn({
    required int meetingId,
    required double latitude,
    required double longitude,
    String? photoPath,
  }) async {
    _isLoading = true;
    _error = '';
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Not authenticated');

      var request = http.MultipartRequest('POST', Uri.parse('${Constants.baseUrl}/meetings/meet-in'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['meeting_id'] = meetingId.toString();
      request.fields['latitude'] = latitude.toString();
      request.fields['longitude'] = longitude.toString();

      if (photoPath != null && photoPath.isNotEmpty) {
        request.files.add(await http.MultipartFile.fromPath('photo', photoPath));
      }

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final decodedData = json.decode(responseBody);

      if (response.statusCode == 200 || response.statusCode == 201) {
        await fetchTodayMeetings();
        await checkAttendanceStatus();
        _isLoading = false;
        notifyListeners();
        return {
          'success': true,
          'message': decodedData['message'] ?? 'Meet-In berhasil dicatat',
          'data': decodedData['data'],
        };
      } else {
        _isLoading = false;
        _error = decodedData['message'] ?? 'Gagal melakukan Meet-In';
        notifyListeners();
        return {
          'success': false,
          'message': _error,
        };
      }
    } catch (e) {
      _isLoading = false;
      _error = e.toString();
      notifyListeners();
      return {
        'success': false,
        'message': e.toString(),
      };
    }
  }

  Future<Map<String, dynamic>> meetOut({
    required int meetingId,
    required String notes,
    String? photoPath,
    double? latitude,
    double? longitude,
    int? durationSeconds,
  }) async {
    _isLoading = true;
    _error = '';
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Not authenticated');

      var request = http.MultipartRequest('POST', Uri.parse('${Constants.baseUrl}/meetings/meet-out'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['meeting_id'] = meetingId.toString();
      request.fields['report_notes'] = notes;
      if (durationSeconds != null) {
        request.fields['duration_seconds'] = durationSeconds.toString();
      }
      if (latitude != null) {
        request.fields['latitude'] = latitude.toString();
      }
      if (longitude != null) {
        request.fields['longitude'] = longitude.toString();
      }

      if (photoPath != null && photoPath.isNotEmpty) {
        request.files.add(await http.MultipartFile.fromPath('photo', photoPath));
      }

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final decodedData = json.decode(responseBody);

      if (response.statusCode == 200 || response.statusCode == 201) {
        _activeMeeting = null;
        _meetingStartTime = null;
        await fetchTodayMeetings();
        await checkAttendanceStatus();
        _isLoading = false;
        notifyListeners();
        return {
          'success': true,
          'message': decodedData['message'] ?? 'Laporan meeting berhasil dikirim',
          'data': decodedData['data'],
        };
      } else {
        _isLoading = false;
        _error = decodedData['message'] ?? 'Gagal mengirim laporan meeting';
        notifyListeners();
        return {
          'success': false,
          'message': _error,
        };
      }
    } catch (e) {
      _isLoading = false;
      _error = e.toString();
      notifyListeners();
      return {
        'success': false,
        'message': e.toString(),
      };
    }
  }

  Future<MeetingDetailModel?> fetchMeetingDetail(int meetingId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) throw Exception('Not authenticated');

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/meetings/$meetingId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        if (decoded['status'] == 'success' && decoded['data'] != null) {
          return MeetingDetailModel.fromJson(decoded['data']);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error fetchMeetingDetail: $e');
      return null;
    }
  }
}

