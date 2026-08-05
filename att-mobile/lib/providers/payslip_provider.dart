import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/payslip.dart';
import '../utils/constants.dart';

class PayslipProvider with ChangeNotifier {
  List<Payslip> _payslips = [];
  bool _isLoading = false;
  String _error = '';

  List<Payslip> get payslips => _payslips;
  bool get isLoading => _isLoading;
  String get error => _error;

  Future<void> fetchPayslips() async {
    _isLoading = true;
    _error = '';
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      if (token == null) {
        throw Exception('Token not found');
      }

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/payslips'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final Map<String, dynamic> responseData = json.decode(response.body);
        if (responseData['status'] == 'success') {
          final List<dynamic> payslipData = responseData['data'];
          _payslips = payslipData.map((data) => Payslip.fromJson(data)).toList();
        } else {
          _error = responseData['message'] ?? 'Failed to load payslips';
        }
      } else {
        _error = 'Server error: ${response.statusCode}';
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
