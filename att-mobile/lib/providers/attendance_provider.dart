import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class AttendanceProvider with ChangeNotifier {
  bool _isLoading = false;
  bool get isLoading => _isLoading;

  bool _isCheckedIn = false;
  bool get isCheckedIn => _isCheckedIn;

  bool _isVisiting = false;
  bool get isVisiting => _isVisiting;

  List<dynamic> _workLocations = [];
  List<dynamic> get workLocations => _workLocations;

  String _baseUrl = 'http://127.0.0.1:8000/api'; // Adjust for your environment

  Future<bool> checkAttendanceStatus() async {
    _isLoading = true;
    notifyListeners();
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      if (token == null) return false;

      final response = await http.get(
        Uri.parse('$_baseUrl/attendance/history'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final history = data['data'] as List;
        final todayLogs = data['today_logs'] as List;

        if (history.isNotEmpty) {
          final lastAttendance = history.first;
          final today = DateTime.now().toIso8601String().split('T').first;
          if (lastAttendance['attendance_date'] == today) {
            _isCheckedIn = lastAttendance['checkout_at'] == null;
          } else {
            _isCheckedIn = false;
          }
        } else {
          _isCheckedIn = false;
        }

        // Determine if currently visiting
        if (todayLogs.isNotEmpty) {
          final lastLog = todayLogs.first;
          if (lastLog['log_type'] == 'visit_in') {
            _isVisiting = true;
          } else {
            _isVisiting = false;
          }
        } else {
          _isVisiting = false;
        }
      }
    } catch (e) {
      print('Error checking status: $e');
    }
    _isLoading = false;
    notifyListeners();
    return _isCheckedIn;
  }

  Future<void> fetchWorkLocations() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final response = await http.get(
        Uri.parse('$_baseUrl/work-locations'),
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
      print('Error fetching work locations: $e');
    }
  }

  Future<Map<String, dynamic>> submitAttendance({
    required String type, // 'checkin', 'checkout', 'visit_in', 'visit_out'
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

      var request = http.MultipartRequest('POST', Uri.parse('$_baseUrl/attendance'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['type'] = type;
      request.fields['latitude'] = latitude.toString();
      request.fields['longitude'] = longitude.toString();

      if (visitType != null) request.fields['visit_type'] = visitType;
      if (note != null) request.fields['note'] = note;
      if (visitLocationId != null) request.fields['visit_location_id'] = visitLocationId.toString();

      if (isWeb) {
        // Handle web image bytes
        final response = await http.get(Uri.parse(imagePath));
        final bytes = response.bodyBytes;
        request.files.add(http.MultipartFile.fromBytes('photo', bytes, filename: 'selfie.jpg'));
      } else {
        request.files.add(await http.MultipartFile.fromPath('photo', imagePath));
      }

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final decodedData = json.decode(responseBody);

      if (response.statusCode == 200 || response.statusCode == 201) {
        if (type == 'checkin') {
          _isCheckedIn = true;
          _isVisiting = false;
        } else if (type == 'checkout') {
          _isCheckedIn = false;
          _isVisiting = false;
        } else if (type == 'visit_in') {
          _isVisiting = true;
        } else if (type == 'visit_out') {
          _isVisiting = false;
        }
        _isLoading = false;
        notifyListeners();
        return {'success': true, 'message': decodedData['message'] ?? 'Berhasil'};
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
