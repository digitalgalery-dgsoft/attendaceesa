import 'dart:async';
import 'package:permission_handler/permission_handler.dart';
import 'dart:convert';
import 'dart:io';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_background_service/flutter_background_service.dart';
import 'package:flutter_background_service_android/flutter_background_service_android.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class LocationService {
  static Future<void> initializeService() async {
    final service = FlutterBackgroundService();

    await service.configure(
      androidConfiguration: AndroidConfiguration(
        // this will be executed when app is in foreground or background in separated isolate
        onStart: onStart,
        autoStart: false,
        isForegroundMode: true,
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
    // Request notification permission for Android 13+
    var status = await Permission.notification.status;
    if (status.isDenied) {
      await Permission.notification.request();
    }
    
    final service = FlutterBackgroundService();
    var isRunning = await service.isRunning();
    if (!isRunning) {
      await service.startService();
    }
  }

  static Future<void> stopService() async {
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

  // Ambil interval dari SharedPreferences
  final prefs = await SharedPreferences.getInstance();
  int intervalMinutes = prefs.getInt('tracking_interval_minutes') ?? 5;
  String? token = prefs.getString('auth_token');

  Timer.periodic(Duration(minutes: intervalMinutes), (timer) async {
    if (service is AndroidServiceInstance) {
      if (await service.isForegroundService()) {
        service.setForegroundNotificationInfo(
          title: "Live Tracking Active",
          content: "Updated at ${DateTime.now().toString().substring(11, 16)}",
        );
      }
    }

    // Refresh token and interval if changed
    await prefs.reload();
    token = prefs.getString('auth_token');
    int currentInterval = prefs.getInt('tracking_interval_minutes') ?? 5;
    
    // Check if token exists
    if (token == null || token!.isEmpty) {
      // If user is logged out, stop service
      service.stopSelf();
      return;
    }
    
    // Change timer interval if it was updated in settings (this requires restarting timer, but we keep it simple for now)
    
    try {
      Position position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      
      // Kirim data ke API
      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/tracking'),
        headers: {
          'Authorization': 'Bearer $token',
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
      print("Error getting or sending location: $e");
    }
  });
}
