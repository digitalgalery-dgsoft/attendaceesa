import 'dart:async';
import 'package:permission_handler/permission_handler.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_background_service/flutter_background_service.dart';
import 'package:flutter_background_service_android/flutter_background_service_android.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class LocationService {
  static Future<void> initializeService() async {
    if (kIsWeb) return;
    
    // Create Notification Channel for Android 8+
    const AndroidNotificationChannel channel = AndroidNotificationChannel(
      'location_tracking', // id
      'Live Tracking Active', // title
      description: 'Merekam lokasi di latar belakang...', // description
      importance: Importance.low, 
    );

    final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
    if (Platform.isAndroid) {
      await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(channel);
    }

    final service = FlutterBackgroundService();

    await service.configure(
      androidConfiguration: AndroidConfiguration(
        onStart: onStart,
        autoStart: false,
        autoStartOnBoot: false,
        // FIX #1: isForegroundMode HARUS true agar Android tidak membunuh service
        // Background service (isForegroundMode: false) pada Android 12+ dibunuh OS
        // dalam beberapa menit karena battery optimization — inilah penyebab
        // tracking berhenti setelah beberapa saat.
        isForegroundMode: true,
        notificationChannelId: 'location_tracking',
        initialNotificationTitle: 'Live Tracking Active',
        initialNotificationContent: 'Merekam lokasi di latar belakang...',
        foregroundServiceNotificationId: 888,
        foregroundServiceTypes: [AndroidForegroundType.location],
      ),
      iosConfiguration: IosConfiguration(
        autoStart: false,
        onForeground: onStart,
        onBackground: onIosBackground,
      ),
    );
  }

  static Future<void> startService() async {
    if (kIsWeb) return;

    // Request notification permission for Android 13+
    var notifStatus = await Permission.notification.status;
    if (notifStatus.isDenied) {
      await Permission.notification.request();
    }
    
    // Request location permission (Required for Android 14 FGS)
    var locStatus = await Permission.location.status;
    if (locStatus.isDenied) {
      await Permission.location.request();
    }

    // We intentionally DO NOT request `locationAlways` (ACCESS_BACKGROUND_LOCATION) here.
    // Why? Because on Android 11+, requesting it forces the user to the Settings app,
    // and when they grant "Allow all the time", the Android OS intentionally FORCE KILLS 
    // the app process for security reasons. Since we are using a Foreground Service 
    // (isForegroundMode: true), we DO NOT need background location permissions. 
    // Regular foreground location permission is enough to track location in the background.

    // Verify location and notification permissions are granted before starting to avoid OS crash
    var notifGranted = await Permission.notification.isGranted;
    if (notifGranted && (await Permission.location.isGranted || await Permission.locationAlways.isGranted)) {
      final service = FlutterBackgroundService();
      var isRunning = await service.isRunning();
      if (!isRunning) {
        // Add a small delay to ensure permission dialog is fully closed
        // and app is in foreground state to prevent ForegroundServiceStartNotAllowedException
        await Future.delayed(const Duration(milliseconds: 500));
        try {
          await service.startService();
        } catch (e) {
          debugPrint('Failed to start service: $e');
        }
      }
    }
  }

  static Future<void> stopService() async {
    if (kIsWeb) return;
    final service = FlutterBackgroundService();
    var isRunning = await service.isRunning();
    if (isRunning) {
      service.invoke("stopService");
    }
  }
}

@pragma('vm:entry-point')
Future<bool> onIosBackground(ServiceInstance service) async {
  WidgetsFlutterBinding.ensureInitialized();
  DartPluginRegistrant.ensureInitialized();
  return true;
}

/// Mengirim lokasi ke API server
Future<void> _sendLocation(String token, double lat, double lng) async {
  try {
    final response = await http.post(
      Uri.parse('${Constants.baseUrl}/tracking'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
      body: {
        'latitude': lat.toString(),
        'longitude': lng.toString(),
      },
    ).timeout(const Duration(seconds: 20));

    debugPrint('[Tracking] Sent ($lat, $lng) → HTTP ${response.statusCode}');
  } catch (e) {
    debugPrint('[Tracking] Failed to send location: $e');
  }
}

/// Mengambil posisi GPS saat ini
Future<Position?> _getCurrentPosition() async {
  try {
    final locEnabled = await Geolocator.isLocationServiceEnabled();
    if (!locEnabled) {
      debugPrint('[Tracking] Location service disabled');
      return null;
    }

    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      debugPrint('[Tracking] Location permission denied: $permission');
      return null;
    }

    final position = await Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
      timeLimit: const Duration(seconds: 20),
    );
    return position;
  } catch (e) {
    debugPrint('[Tracking] Error getting position: $e');
    return null;
  }
}

@pragma('vm:entry-point')
void onStart(ServiceInstance service) async {
  WidgetsFlutterBinding.ensureInitialized();
  // Only available for flutter 3.0.0 and later
  DartPluginRegistrant.ensureInitialized();

  if (service is AndroidServiceInstance) {
    service.on('setAsForeground').listen((event) {
      service.setAsForegroundService();
    });

    service.on('setAsBackground').listen((event) {
      service.setAsBackgroundService();
    });
  }

  service.on('stopService').listen((event) {
    service.stopSelf();
  });

  // Ambil jarak filter dan token dari SharedPreferences
  int distanceMeters = 10;
  String? token;
  
  try {
    final prefs = await SharedPreferences.getInstance();
    distanceMeters = prefs.getInt('tracking_distance_meters') ?? 10;
    token = prefs.getString('auth_token');
    debugPrint('[Tracking] Service started. Distance Filter: ${distanceMeters}m, Token: ${token != null ? "found" : "missing"}');
  } catch (e) {
    debugPrint('[Tracking] Error reading prefs in background service: $e');
  }

  // If no token, stop immediately to avoid unnecessary crash
  if (token == null || token.isEmpty) {
    debugPrint('[Tracking] No auth token — stopping service');
    service.stopSelf();
    return;
  }

  // FIX #2: Kirim titik pertama SEGERA saat service start (tidak menunggu interval pertama)
  // Timer.periodic hanya ticking SETELAH interval pertama berlalu, sehingga jika
  // perjalanan pendek, titik pertama bisa terlewat.
  debugPrint('[Tracking] Capturing initial position immediately...');
  final initialPosition = await _getCurrentPosition();
  if (initialPosition != null) {
    // Refresh token sebelum mengirim
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.reload();
      final currentToken = prefs.getString('auth_token');
      if (currentToken != null && currentToken.isNotEmpty) {
        await _sendLocation(currentToken, initialPosition.latitude, initialPosition.longitude);
      }
    } catch (e) {
      debugPrint('[Tracking] Error on initial position send: $e');
    }
  }

  // Jalankan stream posisi berdasarkan pergerakan jarak
  final LocationSettings locationSettings = LocationSettings(
    accuracy: LocationAccuracy.high,
    distanceFilter: distanceMeters,
  );

  StreamSubscription<Position>? positionStream;

  positionStream = Geolocator.getPositionStream(locationSettings: locationSettings)
      .listen((Position? position) async {
    
    // Gunakan token yang sudah didapat saat onStart.
    // Jika user logout (token hilang), UI akan memanggil stopService.
    if (token == null || token.isEmpty) {
      debugPrint('[Tracking] Token is null — stopping service');
      positionStream?.cancel();
      service.stopSelf();
      return;
    }

    if (position != null) {
      try {
        await _sendLocation(token!, position.latitude, position.longitude);
        
        // Update notification dengan koordinat terbaru
        if (service is AndroidServiceInstance) {
          service.setForegroundNotificationInfo(
            title: 'Live Tracking Active',
            content: 'Lokasi terakhir: ${position.latitude.toStringAsFixed(4)}, ${position.longitude.toStringAsFixed(4)}',
          );
        }
      } catch (e) {
        debugPrint('[Tracking] Error in tracking stream: $e');
      }
    }
  });

  service.on('stopService').listen((event) {
    positionStream?.cancel();
    service.stopSelf();
  });
}
