import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:dio/dio.dart';

class SalesProvider with ChangeNotifier {
  bool _isLoading = false;
  List<dynamic> _salesReports = [];
  List<dynamic> _salesPipelines = [];

  bool get isLoading => _isLoading;
  List<dynamic> get salesReports => _salesReports;
  List<dynamic> get salesPipelines => _salesPipelines;

  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  Future<void> fetchSalesReports() async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) return;

      final url = Uri.parse('${Constants.baseUrl}/sales-reports');
      final response = await http.get(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          _salesReports = data['data'];
        }
      }
    } catch (e) {
      debugPrint('Error fetching sales reports: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchSalesPipelines() async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) return;

      final url = Uri.parse('${Constants.baseUrl}/sales-pipelines');
      final response = await http.get(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          _salesPipelines = data['data'];
        }
      }
    } catch (e) {
      debugPrint('Error fetching sales pipelines: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> submitSalesReport(Map<String, dynamic> data, {String? imagePath}) async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      var dio = Dio();
      dio.options.headers['Authorization'] = 'Bearer $token';
      dio.options.headers['Accept'] = 'application/json';

      FormData formData = FormData.fromMap({
        'client_name': data['client_name'],
        'client_company': data['client_company'] ?? '',
        'revenue': data['revenue'] ?? '0',
        'notes': data['notes'] ?? '',
        'status': data['status'] ?? 'pending',
        'location': data['location'] ?? '',
        'create_pipeline': data['create_pipeline'] ? '1' : '0',
      });

      if (data['create_pipeline'] == true) {
        formData.fields.addAll([
          MapEntry('stage', data['stage'] ?? 'prospecting'),
          MapEntry('expected_revenue', data['expected_revenue'] ?? '0'),
          MapEntry('probability', data['probability'] ?? '0'),
          MapEntry('expected_close_date', data['expected_close_date'] ?? ''),
        ]);
      }

      if (imagePath != null && imagePath.isNotEmpty) {
        formData.files.add(
          MapEntry(
            'receipt_image',
            await MultipartFile.fromFile(imagePath),
          ),
        );
      }

      final response = await dio.post(
        '${Constants.baseUrl}/sales-reports',
        data: formData,
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        final result = response.data;
        if (result['status'] == 'success') {
          await fetchSalesReports(); // refresh list
          return {'success': true, 'message': result['message'] ?? 'Berhasil disimpan'};
        } else {
          return {'success': false, 'message': result['message'] ?? 'Terjadi kesalahan'};
        }
      }
      return {'success': false, 'message': 'Gagal menyimpan laporan (Error ${response.statusCode})'};
    } on DioException catch (e) {
      debugPrint('Dio Error: ${e.response?.data}');
      String msg = 'Gagal menyimpan laporan';
      if (e.response?.data != null && e.response?.data['message'] != null) {
        msg = e.response?.data['message'];
      }
      return {'success': false, 'message': msg};
    } catch (e) {
      debugPrint('Error submit sales report: $e');
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
  Future<Map<String, dynamic>> updateSalesReportStatus(int id, String status, {String? notes}) async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final url = Uri.parse('${Constants.baseUrl}/sales-reports/$id');
      final response = await http.put(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'status': status,
          if (notes != null) 'notes': notes,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          await fetchSalesReports(); // refresh list
          return {'success': true, 'message': data['message'] ?? 'Berhasil diupdate'};
        } else {
          return {'success': false, 'message': data['message'] ?? 'Terjadi kesalahan'};
        }
      }
      return {'success': false, 'message': 'Gagal update laporan (Error ${response.statusCode})'};
    } catch (e) {
      debugPrint('Error updating sales report: $e');
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> analyzeSalesReport(int id) async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final url = Uri.parse('${Constants.baseUrl}/sales-reports/$id/analyze');
      final response = await http.post(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          return {'success': true, 'analysis': data['data']['analysis']};
        } else {
          return {'success': false, 'message': data['message'] ?? 'Terjadi kesalahan saat analisa'};
        }
      }
      return {'success': false, 'message': 'Gagal melakukan analisa (Error ${response.statusCode})'};
    } catch (e) {
      debugPrint('Error analyzing sales report: $e');
      return {'success': false, 'message': 'Terjadi kesalahan: $e'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> updateSalesPipeline(int id, Map<String, dynamic> data) async {
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) {
        return {'success': false, 'message': 'Not authenticated'};
      }

      final url = Uri.parse('${Constants.baseUrl}/sales-pipelines/$id');
      final response = await http.put(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode(data),
      );

      final result = json.decode(response.body);
      
      if (response.statusCode == 200) {
        // Refresh pipelines after update
        await fetchSalesPipelines();
        return {'success': true, 'message': result['message'] ?? 'Pipeline updated successfully'};
      } else {
        return {
          'success': false,
          'message': result['message'] ?? 'Failed to update pipeline'
        };
      }
    } catch (e) {
      debugPrint('Error updating sales pipeline: $e');
      return {'success': false, 'message': 'Network error occurred'};
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
