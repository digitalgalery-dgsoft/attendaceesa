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

class LocationService {
  static Future<void> initializeService() async {
    if (kIsWeb) return;
    final service = FlutterBackgroundService();

    await service.configure(
      androidConfiguration: AndroidConfiguration(
        onStart: onStart,
        autoStart: false,
        autoStartOnBoot: false,
        isForegroundMode: false,  // Changed: avoid foreground service type restriction on Android 14
        notificationChannelId: 'location_tracking',
        initialNotificationTitle: 'Live Tracking Active',
        initialNotificationContent: 'Merekam lokasi di latar belakang...',
        foregroundServiceNotificationId: 888,
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

@pragma('vm:entry-point')
void onStart(ServiceInstance service) async {
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

  // Ambil interval dari SharedPreferences dengan safe fallback
  int intervalMinutes = 5;
  String? token;
  
  try {
    final prefs = await SharedPreferences.getInstance();
    intervalMinutes = prefs.getInt('tracking_interval_minutes') ?? 5;
    token = prefs.getString('auth_token');
  } catch (e) {
    debugPrint('Error reading prefs in background service: $e');
  }

  // If no token, stop immediately to avoid unnecessary crash
  if (token == null || token.isEmpty) {
    service.stopSelf();
    return;
  }

  Timer.periodic(Duration(minutes: intervalMinutes), (timer) async {
    // Refresh token
    String? currentToken;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.reload();
      currentToken = prefs.getString('auth_token');
    } catch (e) {
      debugPrint('Error reloading prefs: $e');
      return;
    }

    // If token gone, stop service
    if (currentToken == null || currentToken.isEmpty) {
      service.stopSelf();
      return;
    }

    try {
      // Check location permission before requesting position
      final locEnabled = await Geolocator.isLocationServiceEnabled();
      if (!locEnabled) return;
      
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) return;

      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 15),
      );
      
      // Kirim data ke API
      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/tracking'),
        headers: {
          'Authorization': 'Bearer $currentToken',
          'Accept': 'application/json',
        },
        body: {
          'latitude': position.latitude.toString(),
          'longitude': position.longitude.toString(),
        },
      );
      
      if (response.statusCode == 401) {
        // Token expired/invalid, stop service
        service.stopSelf();
      }
    } catch (e) {
      debugPrint('Error in tracking timer: $e');
      // Don't rethrow - let timer continue on next interval
    }
  });
}
