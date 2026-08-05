import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'package:shared_preferences/shared_preferences.dart';

class PermitProvider with ChangeNotifier {
  bool _isLoading = false;
  bool get isLoading => _isLoading;

  List<dynamic> _permits = [];
  List<dynamic> get permits => _permits;

  // _baseUrl removed

  Future<void> fetchPermits() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      
      if (token == null) {
        _isLoading = false;
        notifyListeners();
        return;
      }

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/permits'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _permits = data['data'] ?? [];
      }
    } catch (e) {
      debugPrint('Error fetching permits: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>> submitPermit({
    required String type,
    String? subType,
    required String startDate,
    required String endDate,
    required String notes,
    String? imagePath,
    required bool isWeb,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');

      var request = http.MultipartRequest('POST', Uri.parse('${Constants.baseUrl}/permits'));
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';

      request.fields['type'] = type;
      if (subType != null) request.fields['sub_type'] = subType;
      request.fields['start_date'] = startDate;
      request.fields['end_date'] = endDate;
      request.fields['notes'] = notes;

      if (imagePath != null && imagePath.isNotEmpty) {
        if (isWeb) {
          final imgResponse = await http.get(Uri.parse(imagePath));
          final bytes = imgResponse.bodyBytes;
          request.files.add(http.MultipartFile.fromBytes('photo', bytes, filename: 'attachment.jpg'));
        } else {
          request.files.add(await http.MultipartFile.fromPath('photo', imagePath));
        }
      }

      final response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final decodedData = json.decode(responseBody);

      _isLoading = false;
      notifyListeners();

      if (response.statusCode == 200 || response.statusCode == 201) {
        await fetchPermits(); // refresh
        return {'success': true, 'message': decodedData['message'] ?? 'Berhasil mengajukan izin.'};
      } else {
        return {'success': false, 'message': decodedData['message'] ?? 'Gagal mengajukan izin.'};
      }
    } catch (e) {
      _isLoading = false;
      notifyListeners();
      return {'success': false, 'message': e.toString()};
    }
  }
}
