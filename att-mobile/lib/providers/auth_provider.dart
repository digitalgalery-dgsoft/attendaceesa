import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:io';
import '../utils/constants.dart';
import 'package:device_info_plus/device_info_plus.dart';
import '../services/push_notification_service.dart';

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
    final prefs = await SharedPreferences.getInstance();
    final cachedColor = prefs.getString('cached_principal_theme_color');
    if (cachedColor != null && cachedColor.isNotEmpty) {
      try {
        _appColor = Color(int.parse(cachedColor, radix: 16));
      } catch (_) {}
    }

    try {
      final response = await http.get(Uri.parse('${Constants.baseUrl}/settings'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['data'] != null) {
          if (data['data']['app_name'] != null) {
            _appName = data['data']['app_name'];
          }
          if (data['data']['theme_color'] != null && _appColor == null) {
            String hexStr = data['data']['theme_color'].toString().replaceAll('#', '');
            if (hexStr.length == 6) hexStr = 'FF$hexStr';
            _appColor = Color(int.parse(hexStr, radix: 16));
          }
          if (data['data']['tracking_interval_minutes'] != null) {
            await prefs.setInt('tracking_interval_minutes', int.parse(data['data']['tracking_interval_minutes'].toString()));
          }
          if (data['data']['tracking_distance_meters'] != null) {
            await prefs.setInt('tracking_distance_meters', int.parse(data['data']['tracking_distance_meters'].toString()));
          }
          notifyListeners();
        }
      }
    } catch (e) {
      // Ignore network errors for settings
    }
  }

  void _updateAppColorFromEmployee() {
    if (_employeeData != null) {
      final principalColor = _employeeData?['principal']?['theme_color']?.toString() ??
          _employeeData?['company']?['theme_color']?.toString();
      if (principalColor != null && principalColor.isNotEmpty) {
        String hexStr = principalColor.replaceAll('#', '');
        if (hexStr.length == 6) hexStr = 'FF$hexStr';
        try {
          _appColor = Color(int.parse(hexStr, radix: 16));
          SharedPreferences.getInstance().then((prefs) {
            prefs.setString('cached_principal_theme_color', hexStr);
          });
        } catch (_) {}
      }
    }
  }

  Future<bool> tryAutoLogin() async {
    await fetchSettings();
    final prefs = await SharedPreferences.getInstance();
    
    _token = prefs.getString('auth_token');
    
    // Muat data profil cache lokal terlebih dahulu agar aplikasi langsung responsif
    final cachedEmpStr = prefs.getString('cached_employee_data');
    if (cachedEmpStr != null && cachedEmpStr.isNotEmpty) {
      try {
        _employeeData = json.decode(cachedEmpStr);
        _updateAppColorFromEmployee();
      } catch (_) {}
    }
    final cachedUserStr = prefs.getString('cached_user');
    if (cachedUserStr != null && cachedUserStr.isNotEmpty) {
      try {
        _user = json.decode(cachedUserStr);
      } catch (_) {}
    }

    // Jika token lokal kosong, periksa apakah ada kredensial tersimpan untuk background login
    if (_token == null || _token!.isEmpty) {
      final savedId = prefs.getString('saved_login_id');
      final savedPass = prefs.getString('saved_login_password');
      if (savedId != null && savedId.isNotEmpty && savedPass != null && savedPass.isNotEmpty) {
        final reloginResult = await _silentRelogin(savedId, savedPass);
        if (reloginResult['success'] == true) {
          notifyListeners();
          return true;
        } else if (reloginResult['is_inactive'] == true) {
          await logout();
          return false;
        }
      }
      return false;
    }

    try {
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/me'),
        headers: {
          'Authorization': 'Bearer $_token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final emp = data['data']?['employee_data'];
        
        // Cek apakah karyawan berstatus tidak aktif dari server
        if (data['is_active'] == false || (emp != null && (emp['is_active'] == false || emp['is_active'] == 0))) {
          debugPrint('[AuthProvider] Server reports employee is INACTIVE (200 OK check). Logging out.');
          await logout();
          return false;
        }

        _user = data['data']?['user'] ?? _user;
        _employeeData = emp ?? _employeeData;
        _updateAppColorFromEmployee();

        // Perbarui cache profil lokal
        if (_employeeData != null) {
          await prefs.setString('cached_employee_data', json.encode(_employeeData));
        }
        if (_user != null) {
          await prefs.setString('cached_user', json.encode(_user));
        }

        notifyListeners();
        return true;
      } else if (response.statusCode == 401 || response.statusCode == 403) {
        // Cek secara spesifik apakah penolakan karena karyawan TIDAK AKTIF
        bool isExplicitlyInactive = false;
        try {
          final body = json.decode(response.body);
          if (body is Map) {
            if (body['is_active'] == false || body['is_active'] == 0 || body['account_status'] == 'inactive') {
              isExplicitlyInactive = true;
            }
            final msg = (body['message'] ?? '').toString().toLowerCase();
            if (msg.contains('tidak aktif') ||
                msg.contains('dinonaktifkan') ||
                msg.contains('nonaktif') ||
                msg.contains('resigned') ||
                msg.contains('telah dinonaktifkan')) {
              isExplicitlyInactive = true;
            }
          }
        } catch (_) {}

        if (isExplicitlyInactive) {
          debugPrint('[AuthProvider] Employee account is confirmed INACTIVE by server. Logging out.');
          await logout();
          return false;
        }

        // Jika bukan karena tidak aktif (misal token hangus di server / restart deploy / gateway desync),
        // coba perbarui token via silent background re-login menggunakan kredensial tersimpan
        debugPrint('[AuthProvider] Token rejected (HTTP ${response.statusCode}), attempting silent background re-login...');
        final savedId = prefs.getString('saved_login_id');
        final savedPass = prefs.getString('saved_login_password');
        if (savedId != null && savedId.isNotEmpty && savedPass != null && savedPass.isNotEmpty) {
          final reloginResult = await _silentRelogin(savedId, savedPass);
          if (reloginResult['success'] == true) {
            debugPrint('[AuthProvider] Silent background re-login successful! Token refreshed.');
            notifyListeners();
            return true;
          } else if (reloginResult['is_inactive'] == true) {
            debugPrint('[AuthProvider] Silent login detected inactive employee. Logging out.');
            await logout();
            return false;
          }
        }

        // ATURAN UTAMA: Aplikasi TIDAK BOLEH logout otomatis kecuali karyawan tidak aktif atau manual logout!
        // Sesi karyawan tetap dipertahankan menggunakan data cache lokal.
        debugPrint('[AuthProvider] Server returned HTTP ${response.statusCode}. Retaining authenticated local session.');
        notifyListeners();
        return true;
      } else {
        // Error server sementara (500, 502, 503 saat restart deploy): JANGAN LOGOUT!
        debugPrint('[AuthProvider] Server returned HTTP ${response.statusCode} during check. Retaining authenticated session.');
        notifyListeners();
        return true;
      }
    } catch (e) {
      // Kendala jaringan / timeout / offline: JANGAN LOGOUT! Sesi karyawan tetap aktif.
      debugPrint('[AuthProvider] Network error during tryAutoLogin: $e. Retaining authenticated session.');
      notifyListeners();
      return true;
    }
  }

  Future<bool> loginWithSavedToken(String savedToken) async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/me'),
        headers: {
          'Authorization': 'Bearer $savedToken',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final emp = data['data']?['employee_data'];
        if (data['is_active'] == false || (emp != null && (emp['is_active'] == false || emp['is_active'] == 0))) {
          await logout();
          _isLoading = false;
          notifyListeners();
          return false;
        }

        _token = savedToken;
        _user = data['data']['user'];
        _employeeData = emp ?? _employeeData;
        _updateAppColorFromEmployee();
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', savedToken);
        if (_employeeData != null) {
          await prefs.setString('cached_employee_data', json.encode(_employeeData));
        }
        if (_user != null) {
          await prefs.setString('cached_user', json.encode(_user));
        }
        _isLoading = false;
        notifyListeners();
        return true;
      } else if (response.statusCode == 401 || response.statusCode == 403) {
        bool isExplicitlyInactive = false;
        try {
          final body = json.decode(response.body);
          if (body is Map) {
            if (body['is_active'] == false || body['account_status'] == 'inactive') {
              isExplicitlyInactive = true;
            }
            final msg = (body['message'] ?? '').toString().toLowerCase();
            if (msg.contains('tidak aktif') || msg.contains('dinonaktifkan') || msg.contains('nonaktif')) {
              isExplicitlyInactive = true;
            }
          }
        } catch (_) {}

        if (isExplicitlyInactive) {
          await logout();
          _isLoading = false;
          notifyListeners();
          return false;
        }
        // Jika bukan tidak aktif, jangan logout
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      debugPrint('Biometric token login error: $e');
    }
    _isLoading = false;
    notifyListeners();
    return true; // Jangan batalkan sesi saat ada gangguan jaringan
  }

  Future<Map<String, dynamic>> _silentRelogin(String email, String password) async {
    try {
      final deviceInfo = await _getDeviceInfo();
      final pushService = PushNotificationService();
      final fcmToken = await pushService.getToken();

      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/login'),
        body: {
          'email': email,
          'password': password,
          if (deviceInfo['id'] != null) 'device_id': deviceInfo['id']!,
          if (deviceInfo['name'] != null) 'device_name': deviceInfo['name']!,
          if (fcmToken != null) 'fcm_token': fcmToken,
        },
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      final responseData = json.decode(response.body);

      if (response.statusCode == 200 && responseData['status'] == 'success') {
        _token = responseData['data']['access_token'];
        _user = responseData['data']['user'] ?? _user;
        _employeeData = responseData['data']['employee_data'] ?? _employeeData;
        _updateAppColorFromEmployee();

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', _token!);
        await prefs.setString('saved_login_id', email.trim());
        await prefs.setString('saved_login_password', password);
        if (_employeeData != null) {
          await prefs.setString('cached_employee_data', json.encode(_employeeData));
        }
        if (_user != null) {
          await prefs.setString('cached_user', json.encode(_user));
        }
        return {'success': true, 'is_inactive': false};
      } else {
        final msg = (responseData['message'] ?? '').toString().toLowerCase();
        final bool inactive = responseData['is_active'] == false || 
                               responseData['account_status'] == 'inactive' ||
                               msg.contains('tidak aktif') || 
                               msg.contains('dinonaktifkan') || 
                               msg.contains('nonaktif');
        return {'success': false, 'is_inactive': inactive};
      }
    } catch (e) {
      debugPrint('[AuthProvider] Silent relogin error: $e');
      return {'success': false, 'is_inactive': false};
    }
  }

  Future<Map<String, String?>> _getDeviceInfo() async {
    final deviceInfoPlugin = DeviceInfoPlugin();
    String? deviceId;
    String? deviceName;

    try {
      if (Platform.isAndroid) {
        final androidInfo = await deviceInfoPlugin.androidInfo;
        deviceId = androidInfo.id;
        deviceName = '${androidInfo.brand} ${androidInfo.model}';
      } else if (Platform.isIOS) {
        final iosInfo = await deviceInfoPlugin.iosInfo;
        deviceId = iosInfo.identifierForVendor;
        deviceName = iosInfo.utsname.machine;
      }
    } catch (e) {
      debugPrint('Failed to get device info: $e');
    }

    return {'id': deviceId, 'name': deviceName};
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    final deviceInfo = await _getDeviceInfo();

    try {
      final pushService = PushNotificationService();
      final fcmToken = await pushService.getToken();

      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/login'),
        body: {
          'email': email,
          'password': password,
          if (deviceInfo['id'] != null) 'device_id': deviceInfo['id']!,
          if (deviceInfo['name'] != null) 'device_name': deviceInfo['name']!,
          if (fcmToken != null) 'fcm_token': fcmToken,
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
        _updateAppColorFromEmployee();

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', _token!);
        await prefs.setString('saved_login_id', email.trim());
        await prefs.setString('saved_login_password', password);
        if (_employeeData != null) {
          await prefs.setString('cached_employee_data', json.encode(_employeeData));
        }
        if (_user != null) {
          await prefs.setString('cached_user', json.encode(_user));
        }

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
    await prefs.remove('cached_employee_data');
    await prefs.remove('cached_user');
    await prefs.remove('saved_login_id');
    await prefs.remove('saved_login_password');
    
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

      // Add image if exists (both multipart file and base64 fallback for gateway relay safety)
      if (imageBytes != null && imageFilename != null) {
        request.fields['has_photo_payload'] = '1';
        request.fields['photo_base64'] = base64Encode(imageBytes);
        request.files.add(http.MultipartFile.fromBytes(
          'photo', 
          imageBytes, 
          filename: imageFilename
        ));
      }

      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);
      
      dynamic responseData;
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
          _updateAppColorFromEmployee();
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('cached_employee_data', json.encode(_employeeData));
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
