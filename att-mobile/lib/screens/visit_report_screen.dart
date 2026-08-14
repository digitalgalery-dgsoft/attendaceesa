import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:toastification/toastification.dart';
import 'dart:io';
import 'package:intl/intl.dart';

import '../providers/attendance_provider.dart';
import '../providers/auth_provider.dart';
import '../providers/theme_provider.dart';
import '../utils/image_utils.dart';
import '../utils/constants.dart';

class VisitReportScreen extends StatefulWidget {
  const VisitReportScreen({super.key});

  @override
  State<VisitReportScreen> createState() => _VisitReportScreenState();
}

class _VisitReportScreenState extends State<VisitReportScreen> {
  XFile? _photoFile;
  final TextEditingController _noteController = TextEditingController();

  Future<void> _takePhoto() async {
    final ImagePicker picker = ImagePicker();
    final XFile? photo = await picker.pickImage(
      source: ImageSource.camera,
      preferredCameraDevice: CameraDevice.rear,
      imageQuality: 50,
    );

    if (photo != null) {
      setState(() {
        _photoFile = photo;
      });
    }
  }

  Future<void> _submitReport() async {
    if (_photoFile == null) {
      toastification.show(
        context: context,
        title: const Text('Silakan ambil foto kegiatan visit'),
        type: ToastificationType.warning,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (_noteController.text.isEmpty) {
      toastification.show(
        context: context,
        title: const Text('Silakan isi deskripsi / hasil visit'),
        type: ToastificationType.warning,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    
    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );

    final String datetime = 'Waktu: ${DateFormat('dd MMM yyyy, HH:mm').format(DateTime.now())}';
    final String locationName = 'Laporan Visit';

    // Compress and add watermark
    final String finalImagePath = await ImageUtils.addWatermarkAndCompress(
      imagePath: _photoFile!.path,
      locationName: locationName,
      datetime: datetime,
      coordinates: 'Laporan', // No coordinates required for report
    );

    final result = await attProvider.submitVisitReport(
      notes: _noteController.text,
      imagePath: finalImagePath,
      isWeb: kIsWeb,
    );

    if (!mounted) return;
    Navigator.pop(context); // Close loading

    if (result['success']) {
      toastification.show(
        context: context,
        title: Text(result['message'] ?? 'Berhasil'),
        type: ToastificationType.success,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 3),
      );
      Navigator.pop(context, true); // Return true to refresh dashboard
    } else {
      toastification.show(
        context: context,
        title: Text(result['message'] ?? 'Gagal'),
        type: ToastificationType.error,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final themeProvider = Provider.of<ThemeProvider>(context);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final inputFillColor = isDarkMode ? Colors.grey.shade800 : Colors.grey.shade50;

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          'Laporan Visit',
          style: TextStyle(
            color: textColor,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  if (!isDarkMode)
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: primaryColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(Icons.assignment, color: primaryColor, size: 24),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Form Laporan Visit',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Isi hasil visit dan unggah foto kegiatan',
                              style: TextStyle(color: subtitleColor, fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  
                  Text(
                    'Deskripsi / Hasil Visit',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _noteController,
                    maxLines: 4,
                    style: TextStyle(color: textColor),
                    decoration: InputDecoration(
                      hintText: 'Tuliskan hasil diskusi atau progres kunjungan di sini...',
                      hintStyle: TextStyle(color: subtitleColor),
                      filled: true,
                      fillColor: inputFillColor,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                    ),
                  ),
                  const SizedBox(height: 20),

                  Text(
                    'Foto Kegiatan',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                  ),
                  const SizedBox(height: 8),
                  if (_photoFile != null)
                    Stack(
                      alignment: Alignment.topRight,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: kIsWeb
                              ? Image.network(_photoFile!.path, height: 160, width: double.infinity, fit: BoxFit.cover)
                              : Image.file(File(_photoFile!.path), height: 160, width: double.infinity, fit: BoxFit.cover),
                        ),
                        Positioned(
                          top: 8,
                          right: 8,
                          child: InkWell(
                            onTap: _takePhoto,
                            child: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: const BoxDecoration(
                                color: Colors.black54,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.edit, color: Colors.white, size: 16),
                            ),
                          ),
                        ),
                      ],
                    )
                  else
                    InkWell(
                      onTap: _takePhoto,
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        height: 120,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          color: inputFillColor,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.grey.withOpacity(0.2)),
                        ),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.camera_alt, color: primaryColor, size: 32),
                            const SizedBox(height: 8),
                            Text('Ambil Foto', style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _submitReport,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryColor,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        elevation: 0,
                      ),
                      child: const Text(
                        'Kirim Laporan',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
