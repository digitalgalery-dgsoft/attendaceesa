import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'package:path_provider/path_provider.dart';

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
   * Capture photo from camera with permanent burned-in Geotag Watermark.
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
        imageQuality: 85,
        maxWidth: 1600,
        maxHeight: 1600,
      );

      if (photo == null) return null;

      DateTime now = DateTime.now();
      String dateFormatted = DateFormat('dd MMM yyyy, HH:mm:ss').format(now);
      
      // Ambil GPS jika belum ada
      double? lat = latitude;
      double? lng = longitude;
      if (lat == null || lng == null) {
        try {
          Position pos = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.high,
            timeLimit: const Duration(seconds: 6),
          );
          lat = pos.latitude;
          lng = pos.longitude;
        } catch (_) {}
      }

      String gpsText = (lat != null && lng != null) 
          ? '${lat.toStringAsFixed(6)}, ${lng.toStringAsFixed(6)}' 
          : 'GPS N/A';

      String targetStore = (storeName != null && storeName.trim().isNotEmpty) ? storeName.trim() : 'Lokasi Kunjungan Lapangan';
      String nikText = (employeeNik != null && employeeNik.isNotEmpty) ? ' ($employeeNik)' : '';

      String watermarkSummary = '📍 $targetStore | 👤 $employeeName$nikText | 🕒 $dateFormatted | 🌐 $gpsText';

      // ─── Proses Stempel Watermark Permanen pada Gambar ───────────────────
      final rawBytes = await File(photo.path).readAsBytes();
      final watermarkedFile = await _burnWatermark(
        imageBytes: rawBytes,
        storeName: targetStore,
        employeeName: employeeName,
        employeeNik: employeeNik,
        timestampStr: dateFormatted,
        gpsStr: gpsText,
      );

      return WatermarkCaptureResult(
        file: watermarkedFile ?? File(photo.path),
        watermarkText: watermarkSummary,
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
   * Pick photo from gallery with watermark stamp.
   */
  static Future<WatermarkCaptureResult?> pickFromGallery({
    required String employeeName,
    String? employeeNik,
    String? storeName,
  }) async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 85,
        maxWidth: 1600,
        maxHeight: 1600,
      );

      if (photo == null) return null;

      DateTime now = DateTime.now();
      String dateFormatted = DateFormat('dd MMM yyyy, HH:mm:ss').format(now);
      String targetStore = (storeName != null && storeName.trim().isNotEmpty) ? storeName.trim() : 'Upload Galeri';
      String watermarkSummary = '📁 $targetStore | 👤 $employeeName | 🕒 $dateFormatted';

      final rawBytes = await File(photo.path).readAsBytes();
      final watermarkedFile = await _burnWatermark(
        imageBytes: rawBytes,
        storeName: targetStore,
        employeeName: employeeName,
        employeeNik: employeeNik,
        timestampStr: dateFormatted,
        gpsStr: 'Upload dari Galeri HP',
      );

      return WatermarkCaptureResult(
        file: watermarkedFile ?? File(photo.path),
        watermarkText: watermarkSummary,
        timestamp: now,
      );
    } catch (e) {
      debugPrint('Error picking from gallery: $e');
      return null;
    }
  }

  /**
   * Menggambar stempel watermark permanen di atas pixel foto (Anti-Fraud GPS Watermark).
   */
  static Future<File?> _burnWatermark({
    required Uint8List imageBytes,
    required String storeName,
    required String employeeName,
    String? employeeNik,
    required String timestampStr,
    required String gpsStr,
  }) async {
    try {
      final codec = await ui.instantiateImageCodec(imageBytes);
      final frame = await codec.getNextFrame();
      final originalImage = frame.image;

      final width = originalImage.width.toDouble();
      final height = originalImage.height.toDouble();

      final recorder = ui.PictureRecorder();
      final canvas = Canvas(recorder, Rect.fromLTWH(0, 0, width, height));

      // 1. Gambar foto asli
      canvas.drawImage(originalImage, Offset.zero, Paint());

      // 2. Tentukan ukuran watermark proporsional terhadap resolusi foto
      final bannerHeight = (height * 0.16).clamp(140.0, 260.0);
      final bannerTop = height - bannerHeight;

      // 3. Background semi-transparan hitam dengan gradien elegan
      final bgPaint = Paint()
        ..shader = ui.Gradient.linear(
          Offset(0, bannerTop),
          Offset(0, height),
          [
            Colors.black.withOpacity(0.0),
            Colors.black.withOpacity(0.85),
            Colors.black.withOpacity(0.95),
          ],
          [0.0, 0.25, 1.0],
        );

      canvas.drawRect(Rect.fromLTWH(0, bannerTop, width, bannerHeight), bgPaint);

      // Garis aksen biru di atas banner
      final accentPaint = Paint()
        ..color = const Color(0xFF0F52BA)
        ..strokeWidth = (width * 0.005).clamp(3.0, 8.0);
      canvas.drawLine(Offset(0, bannerTop + (bannerHeight * 0.25)), Offset(width, bannerTop + (bannerHeight * 0.25)), accentPaint);

      // 4. Hitung skala font berdasarkan lebar gambar
      final baseFontSize = (width * 0.024).clamp(14.0, 32.0);
      final padding = (width * 0.03).clamp(16.0, 40.0);

      // ─── Teks 1: Nama Toko / Outlet (Bold Putih) ───
      final p1Builder = ui.ParagraphBuilder(ui.ParagraphStyle(
        textAlign: TextAlign.left,
        fontSize: baseFontSize * 1.25,
        fontWeight: FontWeight.bold,
        maxLines: 1,
        ellipsis: '...',
      ))
        ..pushStyle(ui.TextStyle(color: Colors.white, fontWeight: FontWeight.bold))
        ..addText('📍 $storeName');
      final p1 = p1Builder.build()..layout(ui.ParagraphConstraints(width: width - (padding * 2)));

      // ─── Teks 2: Nama SPG & NIK (Kuning Terang / Biru Muda) ───
      final nikLabel = (employeeNik != null && employeeNik.isNotEmpty) ? ' (NIK: $employeeNik)' : '';
      final p2Builder = ui.ParagraphBuilder(ui.ParagraphStyle(
        textAlign: TextAlign.left,
        fontSize: baseFontSize * 0.95,
        maxLines: 1,
        ellipsis: '...',
      ))
        ..pushStyle(ui.TextStyle(color: const Color(0xFF38BDF8), fontWeight: FontWeight.w600))
        ..addText('👤 $employeeName$nikLabel  •  🛡️ Verified Report');
      final p2 = p2Builder.build()..layout(ui.ParagraphConstraints(width: width - (padding * 2)));

      // ─── Teks 3: Waktu & GPS Lat/Long (Putih / Abu-abu Terang) ───
      final p3Builder = ui.ParagraphBuilder(ui.ParagraphStyle(
        textAlign: TextAlign.left,
        fontSize: baseFontSize * 0.85,
        maxLines: 1,
        ellipsis: '...',
      ))
        ..pushStyle(ui.TextStyle(color: const Color(0xFFE2E8F0)))
        ..addText('🕒 $timestampStr   |   🌐 GPS: $gpsStr');
      final p3 = p3Builder.build()..layout(ui.ParagraphConstraints(width: width - (padding * 2)));

      // Render teks ke atas canvas
      final textStartY = bannerTop + (bannerHeight * 0.32);
      final spacing = baseFontSize * 0.35;

      canvas.drawParagraph(p1, Offset(padding, textStartY));
      canvas.drawParagraph(p2, Offset(padding, textStartY + p1.height + spacing));
      canvas.drawParagraph(p3, Offset(padding, textStartY + p1.height + p2.height + (spacing * 2)));

      // 5. Ekspor ke file gambar baru
      final picture = recorder.endRecording();
      final watermarkedImage = await picture.toImage(width.toInt(), height.toInt());
      final byteData = await watermarkedImage.toByteData(format: ui.ImageByteFormat.png);

      if (byteData == null) return null;

      final tempDir = await getTemporaryDirectory();
      final outFile = File('${tempDir.path}/wm_${DateTime.now().millisecondsSinceEpoch}.jpg');
      await outFile.writeAsBytes(byteData.buffer.asUint8List());

      return outFile;
    } catch (e) {
      debugPrint('Error burning watermark: $e');
      return null;
    }
  }
}
