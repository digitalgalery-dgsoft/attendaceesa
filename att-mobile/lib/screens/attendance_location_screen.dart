import 'dart:io';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:toastification/toastification.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:intl/intl.dart';
import 'package:att_mobile/utils/image_utils.dart';

class AttendanceLocationScreen extends StatefulWidget {
  final String type; // 'checkin', 'checkout', 'visit_in', 'visit_out'
  const AttendanceLocationScreen({super.key, required this.type});

  @override
  State<AttendanceLocationScreen> createState() => _AttendanceLocationScreenState();
}

class _AttendanceLocationScreenState extends State<AttendanceLocationScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  Position? _currentPosition;
  bool _isLoading = true;
  XFile? _selfieFile;

  // Visit In specific
  int? _selectedWorkLocationId;

  // Visit Out specific
  String? _visitType;
  final TextEditingController _noteController = TextEditingController();

  final MapController _mapController = MapController();

  final FaceDetector _faceDetector = FaceDetector(
    options: FaceDetectorOptions(
      enableContours: false,
      enableLandmarks: false,
      enableClassification: false,
      enableTracking: false,
      performanceMode: FaceDetectorMode.fast,
    ),
  );

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _getCurrentLocation();
    
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<AttendanceProvider>(context, listen: false).fetchWorkLocations();
    });
  }

  @override
  void dispose() {
    _faceDetector.close();
    _tabController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _getCurrentLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      setState(() => _isLoading = false);
      return;
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        setState(() => _isLoading = false);
        return;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      setState(() => _isLoading = false);
      return;
    }

    try {
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 20),
      );
      setState(() {
        _currentPosition = position;
        _isLoading = false;
      });
    } catch (e) {
      debugPrint('getCurrentPosition error: $e, trying last known position...');
      try {
        Position? lastPosition = await Geolocator.getLastKnownPosition();
        if (lastPosition != null) {
          setState(() {
            _currentPosition = lastPosition;
            _isLoading = false;
          });
          return;
        }
      } catch (e2) {
        debugPrint('getLastKnownPosition error: $e2');
      }

      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to get location: $e')),
        );
      }
    }
  }

  Future<void> _takeSelfie() async {
    final ImagePicker picker = ImagePicker();
    final XFile? photo = await picker.pickImage(
      source: ImageSource.camera,
      preferredCameraDevice: CameraDevice.front,
      imageQuality: 50,
    );

    if (photo != null) {
      if (!mounted) return;
      
      // Tampilkan indikator loading saat memproses wajah
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => const Center(child: CircularProgressIndicator()),
      );

      try {
        final inputImage = InputImage.fromFilePath(photo.path);
        final faces = await _faceDetector.processImage(inputImage);
        
        if (!mounted) return;
        Navigator.pop(context); // Tutup loading

        if (faces.isEmpty) {
          toastification.show(
            context: context,
            title: const Text('Wajah tidak terdeteksi, silakan foto ulang'),
            type: ToastificationType.error,
            style: ToastificationStyle.flat,
            alignment: Alignment.topRight,
            autoCloseDuration: const Duration(seconds: 4),
          );
          return;
        }

        setState(() {
          _selfieFile = photo;
        });
      } catch (e) {
        if (!mounted) return;
        Navigator.pop(context);
        toastification.show(
          context: context,
          title: Text('Gagal memproses foto: $e'),
          type: ToastificationType.error,
          style: ToastificationStyle.flat,
          alignment: Alignment.topRight,
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }

  Future<void> _submitAttendance() async {
    if (_selfieFile == null && (widget.type == 'checkin' || widget.type == 'visit_in')) {
      toastification.show(
        context: context,
        title: const Text('Silakan ambil foto terlebih dahulu'),
        type: ToastificationType.warning,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }
    if (_currentPosition == null) {
      toastification.show(
        context: context,
        title: const Text('Lokasi tidak ditemukan'),
        type: ToastificationType.error,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (widget.type == 'visit_in' && _selectedWorkLocationId == null) {
      toastification.show(
        context: context,
        title: const Text('Silakan pilih lokasi visit'),
        type: ToastificationType.warning,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }
    
    if (widget.type == 'visit_out' && (_visitType == null || _noteController.text.isEmpty)) {
      toastification.show(
        context: context,
        title: const Text('Jenis Visit dan Keterangan wajib diisi'),
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

    // Get location name for watermark
    String locationName = 'Lokasi: Tidak Diketahui';
    if (widget.type.contains('visit') && _selectedWorkLocationId != null) {
      final loc = attProvider.workLocations.cast<Map<String,dynamic>>().firstWhere(
        (e) => e['id'] == _selectedWorkLocationId, 
        orElse: () => {'name': 'Lokasi Visit'},
      );
      locationName = 'Lokasi: ${loc['name']}';
    } else {
      final schedule = attProvider.todaySchedule;
      if (schedule != null && schedule['work_location'] != null) {
        locationName = 'Lokasi: ${schedule['work_location']['name']}';
      }
    }

    final String datetime = 'Waktu: ${DateFormat('dd MMM yyyy, HH:mm').format(DateTime.now())}';
    final String coordinates = 'Lat: ${_currentPosition!.latitude.toStringAsFixed(6)}, Lng: ${_currentPosition!.longitude.toStringAsFixed(6)}';

    // Compress and add watermark if photo exists
    String? finalImagePath;
    if (_selfieFile != null) {
      finalImagePath = await ImageUtils.addWatermarkAndCompress(
        imagePath: _selfieFile!.path,
        locationName: locationName,
        datetime: datetime,
        coordinates: coordinates,
      );
    }

    final result = await attProvider.submitAttendance(
      type: widget.type,
      latitude: _currentPosition!.latitude,
      longitude: _currentPosition!.longitude,
      imagePath: finalImagePath,
      isWeb: kIsWeb,
      visitType: _visitType,
      note: _noteController.text.isNotEmpty ? _noteController.text : null,
      visitLocationId: _selectedWorkLocationId,
    );

    if (!mounted) return;
    Navigator.pop(context); // Close loading

    if (result['success']) {
      toastification.show(
        context: context,
        title: Text(result['message']),
        type: ToastificationType.success,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 4),
      );
      Navigator.pop(context); // Go back to dashboard after submit
    } else {
      toastification.show(
        context: context,
        title: Text(result['message']),
        type: ToastificationType.error,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 5),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    String title = 'Check In Location';
    if (widget.type == 'checkout') title = 'Check Out Location';
    if (widget.type == 'visit_in') title = 'Visit In';
    if (widget.type == 'visit_out') title = 'Visit Out Report';

    final attProvider = Provider.of<AttendanceProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    String scheduleLocationName = authProvider.employeeData?['branch']?['name'] ?? '-';
    String scheduleLocationAddress = authProvider.employeeData?['branch']?['address'] ?? '-';
    if (attProvider.todaySchedule != null && attProvider.todaySchedule!['work_location'] != null) {
      scheduleLocationName = attProvider.todaySchedule!['work_location']['name'] ?? scheduleLocationName;
      scheduleLocationAddress = attProvider.todaySchedule!['work_location']['address'] ?? '-';
    }

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        bottom: TabBar(
          controller: _tabController,
          labelColor: primaryColor,
          unselectedLabelColor: subtitleColor,
          indicatorColor: primaryColor,
          dividerColor: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200,
          tabs: [
            const Tab(text: 'ITINERARY (0)'),
            Tab(text: 'LOKASI SEKITAR (${attProvider.workLocations.length})'),
          ],
        ),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: primaryColor))
          : Column(
              children: [
                Expanded(
                  child: Stack(
                    children: [
                      FlutterMap(
                        mapController: _mapController,
                        options: MapOptions(
                          initialCenter: _currentPosition != null 
                              ? LatLng(_currentPosition!.latitude, _currentPosition!.longitude)
                              : const LatLng(-6.200000, 106.816666), // Jakarta fallback
                          initialZoom: 16.0,
                        ),
                        children: [
                          TileLayer(
                            urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                            userAgentPackageName: 'com.example.att_mobile',
                          ),
                          if (_currentPosition != null)
                            MarkerLayer(
                              markers: [
                                Marker(
                                  point: LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
                                  width: 80,
                                  height: 80,
                                  child: const Icon(Icons.location_on, color: Colors.red, size: 40),
                                ),
                                ...attProvider.workLocations.map((loc) {
                                  final lat = double.tryParse(loc['latitude'].toString()) ?? 0;
                                  final lng = double.tryParse(loc['longitude'].toString()) ?? 0;
                                  return Marker(
                                    point: LatLng(lat, lng),
                                    width: 40,
                                    height: 40,
                                    child: const Icon(Icons.store, color: Colors.blue, size: 30),
                                  );
                                }),
                              ],
                            ),
                        ],
                      ),
                      if (_currentPosition != null)
                        Positioned(
                          top: 16,
                          left: 16,
                          child: Container(
                            decoration: BoxDecoration(
                              color: cardColor,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: primaryColor.withValues(alpha: 0.1),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(Icons.my_location, color: primaryColor, size: 20),
                                ),
                                const SizedBox(width: 12),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text('Lokasi Anda', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor)),
                                    Text('Akurasi: ±${_currentPosition!.accuracy.toStringAsFixed(0)} m', style: TextStyle(color: isDarkMode ? Colors.green.shade400 : Colors.green, fontSize: 12)),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      Positioned(
                        top: 16,
                        right: 16,
                        child: FloatingActionButton(
                          heroTag: 'recenter',
                          mini: true,
                          backgroundColor: cardColor,
                          elevation: 1,
                          child: Icon(Icons.my_location, color: textColor),
                          onPressed: () {
                            if (_currentPosition != null) {
                              _mapController.move(LatLng(_currentPosition!.latitude, _currentPosition!.longitude), 16.0);
                            }
                          },
                        ),
                      ),
                    ],
                  ),
                ),

                SafeArea(
                  top: false,
                  minimum: const EdgeInsets.only(bottom: 16),
                  child: Container(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
                    decoration: BoxDecoration(
                      color: cardColor,
                      border: Border(top: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200)),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
            if (widget.type == 'checkin' || widget.type == 'checkout') ...[
              Container(
                padding: const EdgeInsets.all(12),
                margin: const EdgeInsets.only(bottom: 12),
                decoration: BoxDecoration(
                  color: elevatedColor,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.business, size: 16, color: primaryColor),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Jadwal Lokasi', style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 2),
                          Text(scheduleLocationName, style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                          const SizedBox(height: 2),
                          Text(scheduleLocationAddress, style: TextStyle(fontSize: 10, color: subtitleColor), maxLines: 2, overflow: TextOverflow.ellipsis),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
            AnimatedBuilder(
              animation: _tabController,
              builder: (context, child) {
                final isItinerary = _tabController.index == 0;
                return Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isItinerary ? 'Itinerary anda (0)' : 'Lokasi Sekitar (${attProvider.workLocations.length})', 
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor)
                    ),
                    TextButton.icon(
                      onPressed: () {
                        attProvider.fetchWorkLocations();
                      },
                      icon: Icon(Icons.refresh, size: 16, color: primaryColor),
                      label: Text('Reload Data', style: TextStyle(color: primaryColor)),
                    ),
                  ],
                );
              },
            ),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
              child: Row(
                children: [
                  Icon(Icons.info, color: primaryColor, size: 16),
                  const SizedBox(width: 8),
                  Text('Jarak berdasarkan estimasi lokasi', style: TextStyle(color: primaryColor, fontSize: 12)),
                ],
              ),
            ),
            const SizedBox(height: 16),
            if (widget.type == 'visit_in')
              Padding(
                padding: const EdgeInsets.only(bottom: 12.0),
                child: DropdownButtonFormField<int>(
                  dropdownColor: cardColor,
                  style: TextStyle(color: textColor),
                  decoration: InputDecoration(
                    labelText: 'Pilih Lokasi Visit',
                    labelStyle: TextStyle(color: subtitleColor),
                    filled: true,
                    fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: primaryColor)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  initialValue: _selectedWorkLocationId,
                  value: _selectedWorkLocationId,
                  items: attProvider.workLocations.map((loc) {
                    return DropdownMenuItem<int>(
                      value: loc['id'],
                      child: Text(loc['name']),
                    );
                  }).toList(),
                  onChanged: (val) {
                    setState(() {
                      _selectedWorkLocationId = val;
                    });
                  },
                ),
              ),
            if (widget.type == 'checkout')
              Padding(
                padding: const EdgeInsets.only(bottom: 12.0),
                child: TextField(
                  controller: _noteController,
                  maxLines: 2,
                  style: TextStyle(color: textColor),
                  decoration: InputDecoration(
                    labelText: 'Catatan / Alasan (Opsional)',
                    labelStyle: TextStyle(color: subtitleColor),
                    filled: true,
                    fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300)),
                    focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: primaryColor)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
              ),
            if (widget.type == 'visit_out')
              Padding(
                padding: const EdgeInsets.only(bottom: 12.0),
                child: Column(
                  children: [
                    DropdownButtonFormField<String>(
                      dropdownColor: cardColor,
                      style: TextStyle(color: textColor),
                      decoration: InputDecoration(
                        labelText: 'Jenis Visit',
                        labelStyle: TextStyle(color: subtitleColor),
                        filled: true,
                        fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300)),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: primaryColor)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                      initialValue: _visitType,
                      value: _visitType,
                      items: const [
                        DropdownMenuItem(value: 'store', child: Text('Store')),
                        DropdownMenuItem(value: 'prinsiple', child: Text('Prinsiple')),
                      ],
                      onChanged: (val) {
                        setState(() {
                          _visitType = val;
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _noteController,
                      maxLines: 2,
                      style: TextStyle(color: textColor),
                      decoration: InputDecoration(
                        labelText: 'Keterangan Kunjungan',
                        labelStyle: TextStyle(color: subtitleColor),
                        filled: true,
                        fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300)),
                        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: primaryColor)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                      ),
                    ),
                  ],
                ),
              ),
            if (_selfieFile != null)
              Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: kIsWeb 
                        ? Image.network(_selfieFile!.path, width: 60, height: 60, fit: BoxFit.cover)
                        : Image.file(File(_selfieFile!.path), width: 60, height: 60, fit: BoxFit.cover),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(child: Text('Selfie Captured', style: TextStyle(color: Colors.green))),
                  IconButton(icon: const Icon(Icons.close, color: Colors.red), onPressed: () => setState(() => _selfieFile = null)),
                ],
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _takeSelfie,
                    icon: Icon(Icons.camera_alt, color: primaryColor),
                    label: Text('Ambil Foto', style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold)),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      side: BorderSide(color: primaryColor),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12),
                      gradient: _selfieFile == null
                        ? LinearGradient(colors: [Colors.grey.shade400, Colors.grey.shade400])
                        : LinearGradient(
                            colors: widget.type == 'checkin'
                                ? [primaryColor, Colors.green]
                                : widget.type == 'checkout'
                                    ? [primaryColor, Colors.red]
                                    : widget.type == 'visit_in'
                                        ? [primaryColor, Colors.lightBlue]
                                        : [primaryColor, Colors.orange],
                            begin: Alignment.centerLeft,
                            end: Alignment.centerRight,
                          ),
                    ),
                    child: ElevatedButton(
                      onPressed: _selfieFile != null ? _submitAttendance : null,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        backgroundColor: Colors.transparent,
                        shadowColor: Colors.transparent,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text(
                        widget.type == 'checkin' ? 'Check In'
                        : widget.type == 'checkout' ? 'Check Out'
                        : widget.type == 'visit_in' ? 'Visit In'
                        : 'Visit Out',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    ),
  ],
),
    );
  }
}
