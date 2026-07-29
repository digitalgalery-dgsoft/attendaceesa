import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/providers/auth_provider.dart';

class ItineraryProvider with ChangeNotifier {
  List<dynamic> _itineraries = [];
  List<dynamic> _workLocations = [];
  bool _isLoading = false;
  String _error = '';

  List<dynamic> get itineraries => _itineraries;
  List<dynamic> get workLocations => _workLocations;
  bool get isLoading => _isLoading;
  String get error => _error;

  final Dio _dio = Dio(BaseOptions(
    baseUrl: Constants.baseUrl,
    headers: {
      'Accept': 'application/json',
    },
  ));

  void _setAuthToken(AuthProvider authProvider) {
    if (authProvider.token != null) {
      _dio.options.headers['Authorization'] = 'Bearer ${authProvider.token}';
    }
  }

  Future<void> fetchItineraries(AuthProvider authProvider, {String? startDate, String? endDate}) async {
    _setAuthToken(authProvider);
    _isLoading = true;
    _error = '';
    notifyListeners();

    try {
      Map<String, dynamic> queryParams = {};
      if (startDate != null && endDate != null) {
        queryParams['start_date'] = startDate;
        queryParams['end_date'] = endDate;
      }

      final response = await _dio.get('/itineraries', queryParameters: queryParams);
      
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        _itineraries = response.data['data'] ?? [];
      } else {
        _error = response.data['message'] ?? 'Failed to fetch itineraries';
      }
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? e.message ?? 'Unknown error occurred';
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchWorkLocations(AuthProvider authProvider) async {
    _setAuthToken(authProvider);
    _isLoading = true;
    _error = '';
    notifyListeners();

    try {
      final response = await _dio.get('/itineraries/work-locations');
      
      if (response.statusCode == 200 && response.data['status'] == 'success') {
        _workLocations = response.data['data'] ?? [];
      } else {
        _error = response.data['message'] ?? 'Failed to fetch work locations';
      }
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? e.message ?? 'Unknown error occurred';
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> createItinerary(AuthProvider authProvider, String date, List<Map<String, dynamic>> locations) async {
    _setAuthToken(authProvider);
    _isLoading = true;
    _error = '';
    notifyListeners();

    try {
      final response = await _dio.post('/itineraries', data: {
        'date': date,
        'locations': locations,
      });

      if (response.statusCode == 200 && response.data['status'] == 'success') {
        _isLoading = false;
        notifyListeners();
        // Refresh itineraries after creation
        await fetchItineraries(authProvider);
        return true;
      } else {
        _error = response.data['message'] ?? 'Failed to create itinerary';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? e.message ?? 'Unknown error occurred';
      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
}
