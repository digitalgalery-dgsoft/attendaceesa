import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/location_service.dart';

class AttendanceProvider with ChangeNotifier {
  bool _isLoading = false;
  bool get isLoading => _isLoading;

  bool _isCheckedIn = false;
  bool get isCheckedIn => _isCheckedIn;

  bool _isVisiting = false;
  bool get isVisiting => _isVisiting;

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

  String _checkinBlockMessage = '';
  String get checkinBlockMessage => _checkinBlockMessage;

  // Active permit for today
  bool _hasActivePermit = false;
  bool get hasActivePermit => _hasActivePermit;

  Map<String, dynamic>? _activePermit;
  Map<String, dynamic>? get activePermit => _activePermit;

  // ─── Load jadwal hari ini + status absensi ────────────────────────────────
  Future<void> loadDashboardData() async {
    _isLoading = true;
    notifyListeners();

    await Future.wait([
      _fetchTodaySchedule(),
      checkAttendanceStatus(),
    ]);

    _isLoading = false;
    notifyListeners();
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
      );

      final data = json.decode(response.body);

      // Permit aktif untuk hari ini
      if (data['has_active_permit'] == true && data['permit'] != null) {
        _hasActivePermit = true;
        _activePermit = data['permit'] as Map<String, dynamic>;
        _canCheckin = false;
        _canVisit = false;
        _checkinBlockMessage = data['message'] ?? 'Anda memiliki izin yang disetujui hari ini.';
        _todaySchedule = null;
        _todayItinerary = null;
        return;
      }

      _hasActivePermit = false;
      _activePermit = null;

      if (response.statusCode == 200 && data['can_checkin'] == true) {
        _canCheckin = true;
        _canVisit = data['can_visit'] ?? false;
        _checkinBlockMessage = '';
        _todaySchedule = data['data']?['schedule'];
        _todayItinerary = data['data']?['itinerary'];
      } else {
        _canCheckin = false;
        _canVisit = false;
        _checkinBlockMessage = data['message'] ?? 'Tidak bisa melakukan Check-In hari ini.';
        _todaySchedule = null;
        _todayItinerary = null;
      }
    } catch (e) {
      _canCheckin = false;
      _checkinBlockMessage = 'Gagal memuat jadwal: $e';
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
      );

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
        } else {
          _isVisiting = false;
        }

        // We CANNOT auto-start LocationService here because starting a Foreground Service 
        // during app initialization (before Activity is fully resumed) causes 
        // ForegroundServiceStartNotAllowedException on Android 12+.
      }
    } catch (e) {
      debugPrint('Error checking status: $e');
    }
    return _isCheckedIn;
  }

  Future<void> fetchHistory({String? startDate, String? endDate}) async {
    _isLoading = true;
    notifyListeners();
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) return;

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
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _monthlyHistory = data['data'] as List? ?? [];
        _stats = data['stats'] as Map<String, dynamic>? ?? {};
        _period = data['period'] as Map<String, dynamic>? ?? {};
        _logsByDate = data['logs_by_date'] as Map<String, dynamic>? ?? {};
      }
    } catch (e) {
      debugPrint('Error fetching history: $e');
    }
    _isLoading = false;
    notifyListeners();
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
    required String imagePath,
    required bool isWeb,
    String? visitType,
    String? note,
    int? visitLocationId,
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

      if (isWeb) {
        final imgResponse = await http.get(Uri.parse(imagePath));
        final bytes = imgResponse.bodyBytes;
        request.files.add(http.MultipartFile.fromBytes('photo', bytes, filename: 'selfie.jpg'));
      } else {
        request.files.add(await http.MultipartFile.fromPath('photo', imagePath));
      }

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final decodedData = json.decode(responseBody);

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
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': e.toString()};
    }
  }
}
