import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/location_service.dart';
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

  // ─── Load jadwal hari ini + status absensi ────────────────────────────────
  Future<void> loadDashboardData() async {
    _isLoading = true;
    notifyListeners();

    await Future.wait([
      _fetchTodaySchedule(),
      checkAttendanceStatus(),
      fetchTodayMeetings(),
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
      if (response.statusCode == 200 && data['has_active_permit'] == true) {
        _hasActivePermit = true;
        _activePermit = data['permit'] as Map<String, dynamic>;
        _canCheckin = false;
        _canVisit = false;
        _hasUnfinishedItinerary = false;
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
        _hasUnfinishedItinerary = data['has_unfinished_itinerary'] ?? false;
        _checkinBlockMessage = '';
        _todaySchedule = data['data']?['schedule'];
        _todayItinerary = data['data']?['itinerary'];
      } else {
        _canCheckin = false;
        _canVisit = false;
        _hasUnfinishedItinerary = false;
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
    String? imagePath,
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

      if (imagePath != null) {
        if (isWeb) {
          final imgResponse = await http.get(Uri.parse(imagePath));
          final bytes = imgResponse.bodyBytes;
          request.files.add(http.MultipartFile.fromBytes('photo', bytes, filename: 'selfie.jpg'));
        } else {
          request.files.add(await http.MultipartFile.fromPath('photo', imagePath));
        }
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

    try {
      final token = authProvider.token;
      if (token == null) throw Exception('Not authenticated');

      // Get current location
      double latitude = 0.0;
      double longitude = 0.0;
      try {
        final pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
        );
        latitude = pos.latitude;
        longitude = pos.longitude;
      } catch (e) {
        debugPrint('Failed to get location for visit report: $e');
        // We might want to require location, but for now we send 0.0 if failed
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

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final decodedData = json.decode(responseBody);

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
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
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

