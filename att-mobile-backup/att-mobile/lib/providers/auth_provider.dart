import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:io';
import '../utils/constants.dart';
import 'package:device_info_plus/device_info_plus.dart';

class AuthProvider with ChangeNotifier {
  bool _isLoading = false;
  String? _token;
  Map<String, dynamic>? _user;
  Map<String, dynamic>? _employeeData;
  String _appName = 'Attendance App';
  Color? _appColor;

  bool get isLoading => _isLoading;
  bool get isAuthenticated => _token != null;
  Map<String, dynamic>? get user => _user;
  Map<String, dynamic>? get employeeData => _employeeData;
  String? get token => _token;
  String get appName => _appName;
  Color? get appColor => _appColor;

  Future<void> fetchSettings() async {
    try {
      final response = await http.get(Uri.parse('${Constants.baseUrl}/settings'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['data'] != null) {
          if (data['data']['app_name'] != null) {
            _appName = data['data']['app_name'];
          }
          if (data['data']['theme_color'] != null) {
            String hexStr = data['data']['theme_color'].toString().replaceAll('#', '');
            if (hexStr.length == 6) hexStr = 'FF' + hexStr;
            _appColor = Color(int.parse(hexStr, radix: 16));
          }
          if (data['data']['tracking_interval_minutes'] != null) {
            final prefs = await SharedPreferences.getInstance();
            await prefs.setInt('tracking_interval_minutes', int.parse(data['data']['tracking_interval_minutes'].toString()));
          }
          notifyListeners();
        }
      }
    } catch (e) {
      // Ignore network errors for settings
    }
  }

  Future<bool> tryAutoLogin() async {
    await fetchSettings();
    final prefs = await SharedPreferences.getInstance();
    if (!prefs.containsKey('auth_token')) {
      return false;
    }
    
    _token = prefs.getString('auth_token');
    
    try {
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/me'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _user = data['data']['user'];
        _employeeData = data['data']['employee_data'];
        notifyListeners();
        return true;
      }
    } catch (e) {
      // Token invalid or network error
    }
    
    await logout();
    return false;
  }

  Future<Map<String, String?>> _getDeviceInfo() async {
    final DeviceInfoPlugin deviceInfoPlugin = DeviceInfoPlugin();
    String? deviceId;
    String? deviceName;

    try {
      if (Platform.isAndroid) {
        final build = await deviceInfoPlugin.androidInfo;
        deviceId = build.id; // Or build.fingerprint, but build.id is typically used. For Android 8+, Settings.Secure.ANDROID_ID is best, but this is simplest without extra packages. Actually, id might change on factory reset, which is fine.
        deviceName = "${build.brand} ${build.model}";
      } else if (Platform.isIOS) {
        final data = await deviceInfoPlugin.iosInfo;
        deviceId = data.identifierForVendor;
        deviceName = data.name;
      }
    } catch (e) {
      // Ignore
    }
    return {'id': deviceId, 'name': deviceName};
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    final deviceInfo = await _getDeviceInfo();

    try {
      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/login'),
        body: {
          'email': email,
          'password': password,
          if (deviceInfo['id'] != null) 'device_id': deviceInfo['id']!,
          if (deviceInfo['name'] != null) 'device_name': deviceInfo['name']!,
        },
        headers: {
          'Accept': 'application/json',
        },
      );

      final responseData = json.decode(response.body);

      if (response.statusCode == 200 && responseData['status'] == 'success') {
        _token = responseData['data']['access_token'];
        _user = responseData['data']['user'];
        _employeeData = responseData['data']['employee_data'];

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', _token!);

        _isLoading = false;
        notifyListeners();
        return {'success': true, 'message': responseData['message']};
      } else {
        _isLoading = false;
        notifyListeners();
        return {'success': false, 'message': responseData['message'] ?? 'Login failed'};
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': 'Network error occurred. Please try again.'};
    }
  }

  Future<void> logout() async {
    if (_token != null) {
      try {
        await http.post(
          Uri.parse('${Constants.baseUrl}/logout'),
          headers: {
            'Authorization': 'Bearer $_token',
            'Accept': 'application/json',
          },
        );
      } catch (e) {
        // Ignore errors on logout
      }
    }
    
    _token = null;
    _user = null;
    _employeeData = null;
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    
    notifyListeners();
  }

  Future<Map<String, dynamic>> updateProfile(Map<String, String> data, {List<int>? imageBytes, String? imageFilename}) async {
    _isLoading = true;
    notifyListeners();

    try {
      var request = http.MultipartRequest('POST', Uri.parse('${Constants.baseUrl}/update-profile'));
      
      request.headers.addAll({
        'Authorization': 'Bearer $_token',
        'Accept': 'application/json',
      });

      // Add fields
      data.forEach((key, value) {
        request.fields[key] = value;
      });

      // Add image if exists
      if (imageBytes != null && imageFilename != null) {
        request.files.add(http.MultipartFile.fromBytes(
          'photo', 
          imageBytes, 
          filename: imageFilename
        ));
      }

      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);
      
      var responseData;
      try {
        responseData = json.decode(response.body);
      } catch (e) {
        _isLoading = false;
        notifyListeners();
        return {'success': false, 'message': 'Server Error (${response.statusCode}): ${response.body.length > 100 ? response.body.substring(0, 100) : response.body}'};
      }

      if (response.statusCode == 200 && responseData['status'] == 'success') {
        if (responseData['data'] != null && responseData['data']['employee_data'] != null) {
          _employeeData = responseData['data']['employee_data'];
        }
        _isLoading = false;
        notifyListeners();
        return {'success': true, 'message': responseData['message'] ?? 'Profile updated successfully'};
      } else {
        _isLoading = false;
        notifyListeners();
        return {'success': false, 'message': responseData['message'] ?? 'Failed to update profile'};
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': 'Client Error: $e'};
    }
  }
}
