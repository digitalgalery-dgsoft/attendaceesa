import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// ---------------------------------------------------------------------------
/// ESA Groups Dynamic Multi-Server Client (Flutter / Dart SDK)
/// ---------------------------------------------------------------------------
/// Menangani auto-discovery, dynamic base URL switching, local secure token caching,
/// dan direct S3 Object Storage photo upload untuk 3 Server Produksi ESA.
class DynamicServerClient {
  static final DynamicServerClient _instance = DynamicServerClient._internal();
  factory DynamicServerClient() => _instance;
  DynamicServerClient._internal();

  // 1. Central Gateway URL (Single Entrypoint)
  static const String defaultGatewayUrl = "https://api.esagroups.id";
  static const String defaultMediaStorageUrl = "https://storage.esagroups.id";

  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  late Dio _dio;

  String _currentBaseUrl = defaultGatewayUrl;
  String? _authToken;
  Map<String, dynamic>? _currentUser;
  Map<String, dynamic>? _currentEmployee;

  String get currentBaseUrl => _currentBaseUrl;
  String? get authToken => _authToken;
  Map<String, dynamic>? get currentUser => _currentUser;
  Map<String, dynamic>? get currentEmployee => _currentEmployee;

  /// Inisialisasi Dio dengan Dynamic Interceptor
  Future<void> init({String? customGatewayUrl}) async {
    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    // Load saved session if exists
    _authToken = await _storage.read(key: 'esa_auth_token');
    final savedBaseUrl = await _storage.read(key: 'esa_api_base_url');
    if (savedBaseUrl != null && savedBaseUrl.isNotEmpty) {
      _currentBaseUrl = savedBaseUrl;
    } else {
      _currentBaseUrl = customGatewayUrl ?? defaultGatewayUrl;
    }

    // Add Dynamic Routing & Auth Interceptor
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        // Jika request belum memiliki full path URL, gunakan _currentBaseUrl
        if (!options.path.startsWith('http')) {
          options.baseUrl = _currentBaseUrl;
        }

        // Attach Bearer Token jika tersedia
        if (_authToken != null && _authToken!.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $_authToken';
        }

        return handler.next(options);
      },
      onError: (DioException e, handler) {
        if (e.response?.statusCode == 401) {
          // Token expired, handle auto-logout
          logout();
        }
        return handler.next(e);
      },
    ));
  }

  /// 1. Auto-Discover Server Cluster by NIK
  Future<Map<String, dynamic>> discoverServer(String nik) async {
    try {
      final response = await _dio.post(
        '$defaultGatewayUrl/api/v1/gateway/discover',
        data: {'nik': nik.trim()},
      );

      if (response.data['status'] == 'success') {
        final data = response.data['data'];
        final String assignedApiUrl = data['api_base_url'] ?? defaultGatewayUrl;
        _currentBaseUrl = assignedApiUrl;
        await _storage.write(key: 'esa_api_base_url', value: _currentBaseUrl);
        return data;
      }
      throw Exception(response.data['message'] ?? 'Discovery failed');
    } catch (e) {
      rethrow;
    }
  }

  /// 2. Unified Login with Dynamic Server Assignment
  Future<Map<String, dynamic>> login(String loginIdentifier, String password) async {
    try {
      // Step 1: Login ke Gateway (atau server aktif)
      final response = await _dio.post(
        '$defaultGatewayUrl/api/v1/gateway/login',
        data: {
          'login': loginIdentifier.trim(),
          'password': password,
        },
      );

      if (response.data['status'] == 'success') {
        final data = response.data['data'];
        _authToken = data['token'];
        _currentUser = data['user'];
        _currentEmployee = data['employee'];

        // Step 2: Ambil dan Simpan Dynamic Base URL Server Tujuan
        final routing = data['routing'];
        if (routing != null && routing['api_base_url'] != null) {
          _currentBaseUrl = routing['api_base_url'];
          await _storage.write(key: 'esa_api_base_url', value: _currentBaseUrl);
        }

        await _storage.write(key: 'esa_auth_token', value: _authToken);
        await _storage.write(key: 'esa_user_data', value: jsonEncode(_currentUser));
        await _storage.write(key: 'esa_employee_data', value: jsonEncode(_currentEmployee));

        return data;
      }
      throw Exception(response.data['message'] ?? 'Login failed');
    } catch (e) {
      rethrow;
    }
  }

  /// 3. Kirim Presensi Selfie & GPS (Langsung ke Server Tujuan)
  Future<Response> submitAttendance({
    required double latitude,
    required double longitude,
    required String type, // 'in' or 'out'
    required String photoFilePath,
    int? workLocationId,
    String? notes,
  }) async {
    final formData = FormData.fromMap({
      'latitude': latitude,
      'longitude': longitude,
      'type': type,
      if (workLocationId != null) 'work_location_id': workLocationId,
      if (notes != null) 'notes': notes,
      'photo': await MultipartFile.fromFile(photoFilePath, filename: 'selfie_${DateTime.now().millisecondsSinceEpoch}.jpg'),
    });

    return await _dio.post('/api/attendance', data: formData);
  }

  /// 4. Ambil Daftar Bawahan Lintas Entitas (Untuk SPV / Head)
  Future<List<dynamic>> getCrossEntitySubordinates() async {
    final response = await _dio.get('/api/v1/cross-entity/subordinates');
    if (response.data['status'] == 'success') {
      return response.data['data']['subordinates'] ?? [];
    }
    return [];
  }

  /// 5. Eksekusi Approval Lintas Entitas (Cuti / Lembur / Kunjungan)
  Future<bool> submitCrossEntityApproval({
    required String type, // 'permit', 'visit_report', 'itinerary'
    required int id,
    required String action, // 'approve' or 'reject'
    String? note,
  }) async {
    final response = await _dio.post(
      '/api/v1/cross-entity/approve',
      data: {
        'type': type,
        'id': id,
        'action': action,
        if (note != null) 'note': note,
      },
    );

    return response.data['status'] == 'success';
  }

  /// Logout & Reset State
  Future<void> logout() async {
    try {
      if (_authToken != null) {
        await _dio.post('/api/logout');
      }
    } catch (_) {}

    _authToken = null;
    _currentUser = null;
    _currentEmployee = null;
    _currentBaseUrl = defaultGatewayUrl;

    await _storage.deleteAll();
  }
}
