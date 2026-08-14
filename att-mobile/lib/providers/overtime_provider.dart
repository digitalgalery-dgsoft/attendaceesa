import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class OvertimeProvider with ChangeNotifier {
  bool _isLoading = false;
  bool _isRunning = false;
  bool _canStart = false;
  String _statusMessage = '';
  Map<String, dynamic>? _activeOvertime;

  bool get isLoading => _isLoading;
  bool get isRunning => _isRunning;
  bool get canStart => _canStart;
  String get statusMessage => _statusMessage;
  Map<String, dynamic>? get activeOvertime => _activeOvertime;

  Future<void> checkStatus() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/overtime/status'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _isRunning = data['data']['is_running'] ?? false;
        _activeOvertime = data['data']['overtime'];
        _canStart = data['data']['can_start'] ?? false;
        _statusMessage = data['data']['message'] ?? '';
      }
    } catch (e) {
      print('Error checking overtime status: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>> startOvertime(String startTime, String notes) async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/overtime/start'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'start_time': startTime,
          'notes': notes,
        }),
      );

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        await checkStatus(); // refresh
        return {'success': true, 'message': data['message']};
      } else {
        _isLoading = false;
        notifyListeners();
        return {'success': false, 'message': data['message'] ?? 'Gagal memulai lembur'};
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': 'Terjadi kesalahan jaringan'};
    }
  }

  Future<Map<String, dynamic>> finishOvertime(String endTime) async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/overtime/finish'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'end_time': endTime,
        }),
      );

      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        await checkStatus(); // refresh
        return {'success': true, 'message': data['message']};
      } else {
        _isLoading = false;
        notifyListeners();
        return {'success': false, 'message': data['message'] ?? 'Gagal menyelesaikan lembur'};
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': 'Terjadi kesalahan jaringan'};
    }
  }
}
