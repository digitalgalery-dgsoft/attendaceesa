import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';

class WatermarkCaptureResult {
  final File file;
  final String watermarkText;
  final double? latitude;
  final double? longitude;
  final DateTime timestamp;

  WatermarkCaptureResult({
    required this.file,
    required this.watermarkText,
    this.latitude,
    this.longitude,
    required this.timestamp,
  });
}

class WatermarkCameraService {
  static final ImagePicker _picker = ImagePicker();

  /**
   * Capture photo from camera with live geotagging and metadata stamp.
   */
  static Future<WatermarkCaptureResult?> captureWithWatermark({
    required String employeeName,
    String? employeeNik,
    String? storeName,
    double? latitude,
    double? longitude,
  }) async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 75,
        maxWidth: 1600,
        maxHeight: 1600,
      );

      if (photo == null) return null;

      DateTime now = DateTime.now();
      String dateFormatted = DateFormat('dd/MM/yyyy HH:mm:ss').format(now);
      
      // Ambil GPS jika belum disediakan
      double? lat = latitude;
      double? lng = longitude;
      if (lat == null || lng == null) {
        try {
          Position pos = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.medium,
            timeLimit: const Duration(seconds: 5),
          );
          lat = pos.latitude;
          lng = pos.longitude;
        } catch (_) {}
      }

      String gpsText = (lat != null && lng != null) 
          ? '${lat.toStringAsFixed(6)}, ${lng.toStringAsFixed(6)}' 
          : 'GPS N/A';

      String storeText = storeName != null && storeName.isNotEmpty ? storeName : 'Lokasi Kunjungan';
      String nikText = employeeNik != null && employeeNik.isNotEmpty ? ' ($employeeNik)' : '';

      String watermarkText = '📍 $storeText | 👤 $employeeName$nikText\n🕒 $dateFormatted | 🌐 $gpsText';

      return WatermarkCaptureResult(
        file: File(photo.path),
        watermarkText: watermarkText,
        latitude: lat,
        longitude: lng,
        timestamp: now,
      );
    } catch (e) {
      debugPrint('Error capturing photo with watermark: $e');
      return null;
    }
  }

  /**
   * Pick photo from gallery with metadata.
   */
  static Future<WatermarkCaptureResult?> pickFromGallery({
    required String employeeName,
    String? storeName,
  }) async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 75,
        maxWidth: 1600,
        maxHeight: 1600,
      );

      if (photo == null) return null;

      DateTime now = DateTime.now();
      String dateFormatted = DateFormat('dd/MM/yyyy HH:mm:ss').format(now);
      String storeText = storeName != null && storeName.isNotEmpty ? storeName : 'Upload Galeri';

      String watermarkText = '📁 $storeText | 👤 $employeeName | 🕒 $dateFormatted';

      return WatermarkCaptureResult(
        file: File(photo.path),
        watermarkText: watermarkText,
        timestamp: now,
      );
    } catch (e) {
      debugPrint('Error picking from gallery: $e');
      return null;
    }
  }
}
