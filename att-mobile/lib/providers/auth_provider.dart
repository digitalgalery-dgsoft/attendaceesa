import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class AuthProvider with ChangeNotifier {
  bool _isLoading = false;
  String? _token;
  Map<String, dynamic>? _user;
  Map<String, dynamic>? _employeeData;

  bool get isLoading => _isLoading;
  bool get isAuthenticated => _token != null;
  Map<String, dynamic>? get user => _user;
  Map<String, dynamic>? get employeeData => _employeeData;
  String? get token => _token;

  Future<bool> tryAutoLogin() async {
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

  Future<Map<String, dynamic>> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/login'),
        body: {
          'email': email,
          'password': password,
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
}
