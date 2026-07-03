import 'dart:io';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:toastification/toastification.dart';

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
  String _mapError = '';

  // Visit In specific
  int? _selectedWorkLocationId;

  // Visit Out specific
  String? _visitType;
  final TextEditingController _noteController = TextEditingController();

  final MapController _mapController = MapController();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _getCurrentLocation();
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
        timeLimit: const Duration(seconds: 10),
      );
      setState(() {
        _currentPosition = position;
        _isLoading = false;
      });
    } catch (e) {
      // Fallback location or show error
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
      setState(() {
        _selfieFile = photo;
      });
    }
  }

  Future<void> _submitAttendance() async {
    if (_selfieFile == null) {
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

    final result = await attProvider.submitAttendance(
      type: widget.type,
      latitude: _currentPosition!.latitude,
      longitude: _currentPosition!.longitude,
      imagePath: _selfieFile!.path,
      isWeb: kIsWeb,
      visitType: _visitType,
      note: _noteController.text.isNotEmpty ? _noteController.text : null,
      visitLocationId: _selectedWorkLocationId,
    );

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

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.blue,
          unselectedLabelColor: Colors.grey,
          indicatorColor: Colors.blue,
          tabs: const [
            Tab(text: 'ITINERARY (0)'),
            Tab(text: 'LOKASI SEKITAR (9)'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Stack(
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
                        ],
                      ),
                  ],
                ),
                if (_currentPosition != null)
                  Positioned(
                    top: 16,
                    left: 16,
                    child: Card(
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 4,
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: const BoxDecoration(
                                color: Colors.blue,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.my_location, color: Colors.white, size: 20),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Lokasi Anda', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                Text('Akurasi: ±${_currentPosition!.accuracy.toStringAsFixed(0)} m', style: const TextStyle(color: Colors.green, fontSize: 12)),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                Positioned(
                  top: 16,
                  right: 16,
                  child: FloatingActionButton(
                    heroTag: 'recenter',
                    mini: true,
                    backgroundColor: Colors.white,
                    child: const Icon(Icons.my_location, color: Colors.black54),
                    onPressed: () {
                      if (_currentPosition != null) {
                        _mapController.move(LatLng(_currentPosition!.latitude, _currentPosition!.longitude), 16.0);
                      }
                    },
                  ),
                ),
              ],
            ),
      bottomSheet: Container(
        color: Colors.white,
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Itinerary anda (0)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                TextButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.refresh, size: 16),
                  label: const Text('Reload Data'),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: Colors.blue.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
              child: const Row(
                children: [
                  Icon(Icons.info, color: Colors.blue, size: 16),
                  SizedBox(width: 8),
                  Text('Jarak berdasarkan estimasi lokasi', style: TextStyle(color: Colors.blue, fontSize: 12)),
                ],
              ),
            ),
            const SizedBox(height: 16),
            if (widget.type == 'visit_in')
              Padding(
                padding: const EdgeInsets.only(bottom: 12.0),
                child: DropdownButtonFormField<int>(
                  decoration: const InputDecoration(
                    labelText: 'Pilih Lokasi Visit',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
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
            if (widget.type == 'visit_out')
              Padding(
                padding: const EdgeInsets.only(bottom: 12.0),
                child: Column(
                  children: [
                    DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        labelText: 'Jenis Visit',
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
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
                      decoration: const InputDecoration(
                        labelText: 'Keterangan Kunjungan',
                        border: OutlineInputBorder(),
                        contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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
                    icon: const Icon(Icons.camera_alt),
                    label: const Text('Ambil Foto'),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: _selfieFile != null ? _submitAttendance : null,
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      backgroundColor: widget.type.contains('out') ? Colors.red : const Color(0xFF0F52BA),
                      foregroundColor: Colors.white,
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
              ],
            ),
          ],
        ),
      ),
    );
  }
}
