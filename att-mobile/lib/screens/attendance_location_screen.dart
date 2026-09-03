import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:geolocator/geolocator.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:toastification/toastification.dart';
import 'package:intl/intl.dart';
import 'liveness_camera_screen.dart';
import 'package:att_mobile/utils/image_utils.dart';
import 'package:att_mobile/models/meeting_model.dart';
import 'meeting_report_screen.dart';
import 'profile_screen.dart';
import 'package:att_mobile/utils/constants.dart';

class ScheduledLocationItem {
  final String id;
  final String type; // 'visit' or 'meeting'
  final int? workLocationId;
  final int? meetingId;
  final String title;
  final String subtitle;
  final String? address;
  final double? latitude;
  final double? longitude;
  final int radiusMeter;
  final String? timeInfo;
  final String? notes;

  ScheduledLocationItem({
    required this.id,
    required this.type,
    this.workLocationId,
    this.meetingId,
    required this.title,
    required this.subtitle,
    this.address,
    this.latitude,
    this.longitude,
    this.radiusMeter = 100,
    this.timeInfo,
    this.notes,
  });
}

class AttendanceLocationScreen extends StatefulWidget {
  final String type; // 'checkin', 'checkout', 'visit_in', 'visit_out', 'meet_in'
  final int? initialWorkLocationId;
  final MeetingModel? meeting;
  const AttendanceLocationScreen({super.key, required this.type, this.initialWorkLocationId, this.meeting});

  @override
  State<AttendanceLocationScreen> createState() => _AttendanceLocationScreenState();
}

class _AttendanceLocationScreenState extends State<AttendanceLocationScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  Position? _currentPosition;
  bool _isLoading = true;
  XFile? _selfieFile;

  // Scheduled location selection (Check-in Tab 2)
  ScheduledLocationItem? _selectedScheduledLocation;

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
    _selectedWorkLocationId = widget.initialWorkLocationId;
    _getCurrentLocation();
    
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final att = Provider.of<AttendanceProvider>(context, listen: false);
      att.fetchWorkLocations();
      att.fetchTodayMeetings();
    });

    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        setState(() {
          if (_tabController.index == 0) {
            _selectedScheduledLocation = null;
          } else {
            final att = Provider.of<AttendanceProvider>(context, listen: false);
            final scheduledLocs = _getScheduledLocations(att);
            if (_selectedScheduledLocation == null && scheduledLocs.isNotEmpty) {
              _selectedScheduledLocation = scheduledLocs.first;
              if (_selectedScheduledLocation!.latitude != null && _selectedScheduledLocation!.longitude != null) {
                _mapController.move(
                  LatLng(_selectedScheduledLocation!.latitude!, _selectedScheduledLocation!.longitude!),
                  16.0,
                );
              }
            }
          }
        });
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  List<ScheduledLocationItem> _getScheduledLocations(AttendanceProvider attProvider) {
    final List<ScheduledLocationItem> list = [];
    final primaryWlId = attProvider.todaySchedule?['work_location_id'] ?? attProvider.todaySchedule?['work_location']?['id'];
    final double? primaryLat = double.tryParse('${attProvider.todaySchedule?['work_location']?['latitude'] ?? ''}');
    final double? primaryLng = double.tryParse('${attProvider.todaySchedule?['work_location']?['longitude'] ?? ''}');

    // 1. Dari Jadwal Visit / Itinerary
    final itineraryItems = attProvider.todayItinerary?['items'] as List? ?? [];
    for (var item in itineraryItems) {
      final wl = item['work_location'] as Map<String, dynamic>?;
      final wlId = item['work_location_id'] is int 
          ? item['work_location_id'] 
          : (wl?['id'] is int ? wl!['id'] : int.tryParse('${item['work_location_id'] ?? wl?['id']}'));

      // Lewati jika sama dengan lokasi check-in utama
      if (wlId != null && primaryWlId != null && wlId.toString() == primaryWlId.toString()) {
        continue;
      }

      final name = wl?['name'] ?? 'Lokasi Visit #${item['sequence'] ?? ''}';
      final address = wl?['address'] as String?;
      final lat = double.tryParse('${wl?['latitude'] ?? item['latitude'] ?? ''}');
      final lng = double.tryParse('${wl?['longitude'] ?? item['longitude'] ?? ''}');
      final radius = wl?['radius_meter'] is int ? wl!['radius_meter'] : int.tryParse('${wl?['radius_meter']}') ?? 100;

      list.add(
        ScheduledLocationItem(
          id: 'visit_${item['id'] ?? wlId ?? list.length}',
          type: 'visit',
          workLocationId: wlId,
          title: name,
          subtitle: 'Jadwal Visit${item['agenda'] != null && item['agenda'].toString().isNotEmpty ? ' • ${item['agenda']}' : ''}',
          address: address,
          latitude: lat,
          longitude: lng,
          radiusMeter: radius,
          notes: item['notes'] ?? item['agenda'],
        ),
      );
    }

    // 2. Dari Jadwal Meeting Hari Ini (Offline / dengan lokasi)
    for (var meeting in attProvider.todayMeetings) {
      // Lewati jika online tanpa koordinat
      if (meeting.isOnline && (meeting.latitude == null || meeting.longitude == null)) {
        continue;
      }

      // Lewati jika koordinat persis sama dengan lokasi utama
      if (meeting.latitude != null && meeting.longitude != null && primaryLat != null && primaryLng != null) {
        if ((meeting.latitude! - primaryLat).abs() < 0.0001 && (meeting.longitude! - primaryLng).abs() < 0.0001) {
          continue;
        }
      }

      list.add(
        ScheduledLocationItem(
          id: 'meeting_${meeting.id}',
          type: 'meeting',
          meetingId: meeting.id,
          title: meeting.title,
          subtitle: 'Jadwal Meeting (${meeting.isOnline ? 'Online' : 'Offline'})',
          address: meeting.isOnline ? (meeting.meetingLink ?? 'Online Meeting') : (meeting.locationName ?? '-'),
          latitude: meeting.latitude,
          longitude: meeting.longitude,
          radiusMeter: meeting.radiusMeter,
          timeInfo: '${meeting.startTime}${meeting.endTime != null ? ' - ${meeting.endTime}' : ''} WIB',
          notes: meeting.notes,
        ),
      );
    }

    return list;
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
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final posData = authProvider.employeeData?['position'];
    final bool isFaceRequired = (posData is Map)
        ? (posData['require_face_recognition'] == true || posData['require_face_recognition'] == 1 || posData['require_face_recognition'] == '1')
        : false;
    final String? masterPhoto = authProvider.employeeData?['photo_url'] ?? authProvider.employeeData?['photo'];
    final bool hasMasterPhoto = masterPhoto != null && masterPhoto.trim().isNotEmpty && !masterPhoto.contains('default.png') && !masterPhoto.contains('placeholder');

    if (isFaceRequired && !hasMasterPhoto) {
      toastification.show(
        context: context,
        title: const Text('⚠️ Wajib Master Wajah'),
        description: const Text('Jabatan Anda mewajibkan Face Recognition. Daftarkan foto master wajah Anda terlebih dahulu di menu Profil.'),
        type: ToastificationType.error,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    final String? masterPhotoUrl = hasMasterPhoto ? Constants.getImageUrl(masterPhoto!) : null;

    final String? photoPath = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => LivenessCameraScreen(
          isRequired: isFaceRequired,
          isEnrollment: false,
          masterPhotoUrl: masterPhotoUrl,
        ),
      ),
    );

    if (photoPath != null) {
      if (!mounted) return;
      setState(() {
        _selfieFile = XFile(photoPath);
      });
    }
  }

  Future<void> _submitAttendance() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final posData = authProvider.employeeData?['position'];
    final bool isFaceRequired = (posData is Map)
        ? (posData['require_face_recognition'] == true || posData['require_face_recognition'] == 1 || posData['require_face_recognition'] == '1')
        : false;
    final String? masterPhoto = authProvider.employeeData?['photo_url'] ?? authProvider.employeeData?['photo'];
    final bool hasMasterPhoto = masterPhoto != null && masterPhoto.trim().isNotEmpty && !masterPhoto.contains('default.png') && !masterPhoto.contains('placeholder');

    if (isFaceRequired && !hasMasterPhoto && (widget.type == 'checkin' || widget.type == 'visit_in' || widget.type == 'meet_in')) {
      toastification.show(
        context: context,
        title: const Text('⚠️ Presensi Ditolak'),
        description: const Text('Jabatan Anda mewajibkan Face Recognition, namun belum mendaftarkan Master Wajah. Silakan buka menu Profil untuk mendaftar.'),
        type: ToastificationType.error,
        style: ToastificationStyle.flat,
        alignment: Alignment.topRight,
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }


    if (_selfieFile == null && (widget.type == 'checkin' || widget.type == 'visit_in' || widget.type == 'meet_in')) {
      toastification.show(
        context: context,
        title: const Text('Foto Selfie Wajib'),
        description: const Text('Silakan ambil foto selfie liveness detection terlebih dahulu.'),
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

    // Validasi Check-In dengan Lokasi Terjadwal
    final isUsingScheduled = widget.type == 'checkin' && _tabController.index == 1;

    if (isUsingScheduled) {
      if (_selectedScheduledLocation == null) {
        toastification.show(
          context: context,
          title: const Text('Pilih Lokasi Terjadwal'),
          description: const Text('Silakan pilih salah satu Lokasi Terjadwal.'),
          type: ToastificationType.warning,
          style: ToastificationStyle.flat,
          alignment: Alignment.topRight,
          autoCloseDuration: const Duration(seconds: 3),
        );
        return;
      }

      if (_noteController.text.trim().isEmpty) {
        toastification.show(
          context: context,
          title: const Text('Catatan Wajib Diisi'),
          description: const Text('Check-in di Lokasi Terjadwal wajib mengisi Catatan / Alasan.'),
          type: ToastificationType.warning,
          style: ToastificationStyle.flat,
          alignment: Alignment.topRight,
          autoCloseDuration: const Duration(seconds: 4),
        );
        return;
      }

      // Validasi Radius Geofence Lokasi Terjadwal jika koordinat ada
      if (_selectedScheduledLocation!.latitude != null && _selectedScheduledLocation!.longitude != null) {
        final distance = Geolocator.distanceBetween(
          _currentPosition!.latitude,
          _currentPosition!.longitude,
          _selectedScheduledLocation!.latitude!,
          _selectedScheduledLocation!.longitude!,
        );
        if (distance > _selectedScheduledLocation!.radiusMeter) {
          toastification.show(
            context: context,
            type: ToastificationType.error,
            title: const Text('Di Luar Radius Lokasi Terjadwal'),
            description: Text('Anda berada ${distance.round()}m dari lokasi (Batas max ${_selectedScheduledLocation!.radiusMeter}m).'),
            autoCloseDuration: const Duration(seconds: 4),
          );
          return;
        }
      }
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
    if (widget.type == 'meet_in' && widget.meeting != null) {
      locationName = 'Meeting: ${widget.meeting!.title} (${widget.meeting!.isOnline ? 'Online' : widget.meeting!.locationName ?? 'Offline'})';
    } else if (isUsingScheduled && _selectedScheduledLocation != null) {
      locationName = 'Lokasi Terjadwal: ${_selectedScheduledLocation!.title} (${_selectedScheduledLocation!.type == 'meeting' ? 'Meeting' : 'Visit'})';
    } else if (widget.type.contains('visit') && _selectedWorkLocationId != null) {
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

    if (widget.type == 'meet_in') {
      if (widget.meeting != null && widget.meeting!.isOffline && widget.meeting!.latitude != null && widget.meeting!.longitude != null) {
        final distance = Geolocator.distanceBetween(
          _currentPosition!.latitude,
          _currentPosition!.longitude,
          widget.meeting!.latitude!,
          widget.meeting!.longitude!,
        );
        if (distance > widget.meeting!.radiusMeter) {
          if (!mounted) return;
          Navigator.pop(context); // Close loading
          toastification.show(
            context: context,
            type: ToastificationType.error,
            title: const Text('Di Luar Radius Meeting'),
            description: Text('Anda berada ${distance.round()}m dari lokasi meeting (Batas max ${widget.meeting!.radiusMeter}m).'),
            autoCloseDuration: const Duration(seconds: 4),
          );
          return;
        }
      }

      final result = await attProvider.meetIn(
        meetingId: widget.meeting!.id,
        latitude: _currentPosition!.latitude,
        longitude: _currentPosition!.longitude,
        photoPath: finalImagePath,
      );

      if (!mounted) return;
      Navigator.pop(context); // Close loading

      if (result['success'] == true) {
        toastification.show(
          context: context,
          title: Text(result['message'] ?? 'Meet-In Berhasil'),
          type: ToastificationType.success,
          style: ToastificationStyle.flat,
          alignment: Alignment.topRight,
          autoCloseDuration: const Duration(seconds: 3),
        );
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => MeetingReportScreen(meeting: widget.meeting!),
          ),
        );
      } else {
        toastification.show(
          context: context,
          title: const Text('Gagal Melakukan Meet-In'),
          description: Text(result['message'] ?? 'Terjadi kesalahan'),
          type: ToastificationType.error,
          style: ToastificationStyle.flat,
          alignment: Alignment.topRight,
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
      return;
    }

    final result = await attProvider.submitAttendance(
      type: widget.type,
      latitude: _currentPosition!.latitude,
      longitude: _currentPosition!.longitude,
      imagePath: finalImagePath,
      isWeb: kIsWeb,
      visitType: _visitType,
      note: _noteController.text.trim().isNotEmpty ? _noteController.text.trim() : null,
      visitLocationId: isUsingScheduled 
          ? _selectedScheduledLocation?.workLocationId 
          : _selectedWorkLocationId,
      scheduledType: isUsingScheduled ? _selectedScheduledLocation?.type : null,
      scheduledWorkLocationId: isUsingScheduled ? _selectedScheduledLocation?.workLocationId : null,
      scheduledMeetingId: isUsingScheduled ? _selectedScheduledLocation?.meetingId : null,
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
    if (widget.type == 'meet_in') title = 'Meet-In Presensi Meeting';

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
    double? primaryLatitude;
    double? primaryLongitude;
    int primaryRadius = 100;

    if (attProvider.todaySchedule != null && attProvider.todaySchedule!['work_location'] != null) {
      final wl = attProvider.todaySchedule!['work_location'];
      scheduleLocationName = wl['name'] ?? scheduleLocationName;
      scheduleLocationAddress = wl['address'] ?? '-';
      primaryLatitude = double.tryParse('${wl['latitude'] ?? ''}');
      primaryLongitude = double.tryParse('${wl['longitude'] ?? ''}');
      primaryRadius = wl['radius_meter'] is int ? wl['radius_meter'] : int.tryParse('${wl['radius_meter']}') ?? 100;
    }

    final scheduledLocations = _getScheduledLocations(attProvider);
    if (_tabController.index == 1 && _selectedScheduledLocation == null && scheduledLocations.isNotEmpty) {
      _selectedScheduledLocation = scheduledLocations.first;
    }

    // Determine target location for map circle & distance calculation
    double? targetLat = primaryLatitude;
    double? targetLng = primaryLongitude;
    int targetRadius = primaryRadius;
    String targetName = scheduleLocationName;

    if (widget.type == 'checkin' && _tabController.index == 1 && _selectedScheduledLocation != null) {
      targetLat = _selectedScheduledLocation!.latitude;
      targetLng = _selectedScheduledLocation!.longitude;
      targetRadius = _selectedScheduledLocation!.radiusMeter;
      targetName = _selectedScheduledLocation!.title;
    } else if (widget.type == 'meet_in' && widget.meeting != null) {
      targetLat = widget.meeting!.latitude;
      targetLng = widget.meeting!.longitude;
      targetRadius = widget.meeting!.radiusMeter;
      targetName = widget.meeting!.title;
    }

    final double? distanceToTarget = (_currentPosition != null && targetLat != null && targetLng != null)
        ? Geolocator.distanceBetween(_currentPosition!.latitude, _currentPosition!.longitude, targetLat, targetLng)
        : null;
    final bool isInsideRadius = distanceToTarget != null ? (distanceToTarget <= targetRadius) : true;

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        bottom: (widget.type == 'meet_in')
            ? null
            : TabBar(
                controller: _tabController,
                labelColor: primaryColor,
                unselectedLabelColor: subtitleColor,
                indicatorColor: primaryColor,
                dividerColor: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200,
                tabs: [
                  const Tab(text: 'LOKASI UTAMA'),
                  Tab(text: 'LOKASI TERJADWAL (${scheduledLocations.length})'),
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
                          if (targetLat != null && targetLng != null)
                            CircleLayer(
                              circles: [
                                CircleMarker(
                                  point: LatLng(targetLat, targetLng),
                                  color: primaryColor.withValues(alpha: 0.15),
                                  borderColor: primaryColor,
                                  borderStrokeWidth: 2,
                                  radius: targetRadius.toDouble(),
                                  useRadiusInMeter: true,
                                ),
                              ],
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
                                if (targetLat != null && targetLng != null)
                                  Marker(
                                    point: LatLng(targetLat, targetLng),
                                    width: 48,
                                    height: 48,
                                    child: Container(
                                      decoration: BoxDecoration(
                                        color: (_tabController.index == 1 && _selectedScheduledLocation?.type == 'meeting')
                                            ? Colors.purple.shade600
                                            : primaryColor,
                                        shape: BoxShape.circle,
                                        boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 4, offset: Offset(0, 2))],
                                      ),
                                      child: Icon(
                                        (_tabController.index == 1 && _selectedScheduledLocation?.type == 'meeting')
                                            ? Icons.video_camera_front
                                            : Icons.store,
                                        color: Colors.white,
                                        size: 24,
                                      ),
                                    ),
                                  ),
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
                        // ─── TAB 0 / DEFAULT CHECK-IN: LOKASI UTAMA ────────────────
                        if (widget.type == 'checkin' && _tabController.index == 0) ...[
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
                                Icon(Icons.business, size: 18, color: primaryColor),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text('Jadwal Lokasi Utama', style: TextStyle(fontSize: 9.5, color: subtitleColor, fontWeight: FontWeight.bold)),
                                      const SizedBox(height: 2),
                                      Text(scheduleLocationName, style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                                      const SizedBox(height: 2),
                                      Text(scheduleLocationAddress, style: TextStyle(fontSize: 10.5, color: subtitleColor), maxLines: 2, overflow: TextOverflow.ellipsis),
                                      if (distanceToTarget != null) ...[
                                        const SizedBox(height: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: isInsideRadius ? Colors.green.withValues(alpha: 0.12) : Colors.red.withValues(alpha: 0.12),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Icon(
                                                isInsideRadius ? Icons.check_circle : Icons.error,
                                                size: 12,
                                                color: isInsideRadius ? Colors.green.shade700 : Colors.red.shade700,
                                              ),
                                              const SizedBox(width: 4),
                                              Text(
                                                isInsideRadius
                                                    ? 'Dalam Radius: ${distanceToTarget.round()}m (Maks ${targetRadius}m)'
                                                    : 'Di Luar Radius: ${distanceToTarget.round()}m (Maks ${targetRadius}m)',
                                                style: TextStyle(
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.bold,
                                                  color: isInsideRadius ? Colors.green.shade700 : Colors.red.shade700,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
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
                        ],

                        // ─── TAB 1: LOKASI TERJADWAL (VISIT & MEETING) ─────────────
                        if (widget.type == 'checkin' && _tabController.index == 1) ...[
                          if (scheduledLocations.isEmpty) ...[
                            Container(
                              padding: const EdgeInsets.all(16),
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: elevatedColor,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                              ),
                              child: Row(
                                children: [
                                  Icon(Icons.info_outline, color: subtitleColor, size: 20),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      'Tidak ada jadwal visit atau jadwal meeting lain yang terjadwal untuk Anda hari ini.',
                                      style: TextStyle(fontSize: 11.5, color: subtitleColor),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ] else ...[
                            // Dropdown Pemilihan Lokasi Terjadwal
                            Container(
                              margin: const EdgeInsets.only(bottom: 10),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                              decoration: BoxDecoration(
                                color: elevatedColor,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: primaryColor.withValues(alpha: 0.5)),
                              ),
                              child: DropdownButtonHideUnderline(
                                child: DropdownButton<String>(
                                  isExpanded: true,
                                  dropdownColor: cardColor,
                                  value: _selectedScheduledLocation?.id ?? scheduledLocations.first.id,
                                  items: scheduledLocations.map((item) {
                                    return DropdownMenuItem<String>(
                                      value: item.id,
                                      child: Row(
                                        children: [
                                          Icon(
                                            item.type == 'meeting' ? Icons.video_camera_front : Icons.store,
                                            size: 16,
                                            color: item.type == 'meeting' ? Colors.purple : primaryColor,
                                          ),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: Text(
                                              '${item.title} (${item.type == 'meeting' ? 'Meeting' : 'Visit'})',
                                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor),
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ),
                                        ],
                                      ),
                                    );
                                  }).toList(),
                                  onChanged: (val) {
                                    if (val != null) {
                                      setState(() {
                                        _selectedScheduledLocation = scheduledLocations.firstWhere((e) => e.id == val);
                                        if (_selectedScheduledLocation!.latitude != null && _selectedScheduledLocation!.longitude != null) {
                                          _mapController.move(
                                            LatLng(_selectedScheduledLocation!.latitude!, _selectedScheduledLocation!.longitude!),
                                            16.0,
                                          );
                                        }
                                      });
                                    }
                                  },
                                ),
                              ),
                            ),

                            // Detail Card Lokasi Terjadwal Terpilih
                            if (_selectedScheduledLocation != null) ...[
                              Container(
                                padding: const EdgeInsets.all(12),
                                margin: const EdgeInsets.only(bottom: 12),
                                decoration: BoxDecoration(
                                  color: elevatedColor,
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Icon(
                                          _selectedScheduledLocation!.type == 'meeting' ? Icons.video_camera_front : Icons.place,
                                          size: 14,
                                          color: _selectedScheduledLocation!.type == 'meeting' ? Colors.purple : primaryColor,
                                        ),
                                        const SizedBox(width: 6),
                                        Expanded(
                                          child: Text(
                                            _selectedScheduledLocation!.subtitle,
                                            style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.bold),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 3),
                                    Text(
                                      _selectedScheduledLocation!.title,
                                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                                    ),
                                    if (_selectedScheduledLocation!.address != null) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        _selectedScheduledLocation!.address!,
                                        style: TextStyle(fontSize: 10.5, color: subtitleColor),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                    if (distanceToTarget != null) ...[
                                      const SizedBox(height: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                        decoration: BoxDecoration(
                                          color: isInsideRadius ? Colors.green.withValues(alpha: 0.12) : Colors.red.withValues(alpha: 0.12),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(
                                              isInsideRadius ? Icons.check_circle : Icons.error,
                                              size: 12,
                                              color: isInsideRadius ? Colors.green.shade700 : Colors.red.shade700,
                                            ),
                                            const SizedBox(width: 4),
                                            Text(
                                              isInsideRadius
                                                  ? 'Dalam Radius: ${distanceToTarget.round()}m (Maks ${targetRadius}m)'
                                                  : 'Di Luar Radius: ${distanceToTarget.round()}m (Maks ${targetRadius}m)',
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: isInsideRadius ? Colors.green.shade700 : Colors.red.shade700,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ],

                            // Catatan Check-in di Lokasi Terjadwal (WAJIB)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 12.0),
                              child: TextField(
                                controller: _noteController,
                                maxLines: 2,
                                style: TextStyle(color: textColor),
                                decoration: InputDecoration(
                                  labelText: 'Catatan Check-in di Lokasi Terjadwal *',
                                  labelStyle: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 12),
                                  hintText: 'Tuliskan alasan/kegiatan check-in di lokasi ini...',
                                  hintStyle: TextStyle(fontSize: 11, color: subtitleColor),
                                  helperText: 'Wajib diisi jika menggunakan Lokasi Terjadwal',
                                  helperStyle: const TextStyle(color: Colors.red, fontSize: 10),
                                  filled: true,
                                  fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.orange.shade300)),
                                  focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: primaryColor, width: 1.5)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                                ),
                              ),
                            ),
                          ],
                        ],

                        // ─── CHECKOUT / VISIT IN / VISIT OUT / MEET IN UI ─────────
                        if (widget.type == 'checkout') ...[
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
                        ],

                        if (widget.type == 'meet_in' && widget.meeting != null) ...[
                          Builder(
                            builder: (context) {
                              final m = widget.meeting!;
                              return Container(
                                padding: const EdgeInsets.all(12),
                                margin: const EdgeInsets.only(bottom: 12),
                                decoration: BoxDecoration(
                                  color: elevatedColor,
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.all(6),
                                          decoration: BoxDecoration(
                                            color: m.isOnline ? Colors.blue.withValues(alpha: 0.15) : Colors.green.withValues(alpha: 0.15),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Icon(
                                            m.isOnline ? Icons.video_camera_front : Icons.meeting_room,
                                            size: 16,
                                            color: m.isOnline ? Colors.blue.shade700 : Colors.green.shade700,
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(m.title, style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                                              Text(
                                                '${m.meetingDate} • ${m.startTime}${m.endTime != null ? ' - ${m.endTime}' : ''} WIB',
                                                style: TextStyle(fontSize: 10, color: subtitleColor),
                                              ),
                                            ],
                                          ),
                                        ),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: m.isOnline ? Colors.blue.shade100 : Colors.green.shade100,
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            m.meetingType.toUpperCase(),
                                            style: TextStyle(
                                              fontSize: 9.5,
                                              fontWeight: FontWeight.bold,
                                              color: m.isOnline ? Colors.blue.shade800 : Colors.green.shade800,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 8),
                                    Row(
                                      children: [
                                        Icon(m.isOnline ? Icons.link : Icons.place, size: 13, color: subtitleColor),
                                        const SizedBox(width: 5),
                                        Expanded(
                                          child: Text(
                                            m.isOnline ? (m.meetingLink ?? 'Link Online') : (m.locationName ?? '-'),
                                            style: TextStyle(fontSize: 11, color: subtitleColor),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                      ],
                                    ),
                                    if (m.isOffline && distanceToTarget != null) ...[
                                      const SizedBox(height: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: isInsideRadius ? Colors.green.withValues(alpha: 0.12) : Colors.red.withValues(alpha: 0.12),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(
                                              isInsideRadius ? Icons.check_circle : Icons.error,
                                              size: 12,
                                              color: isInsideRadius ? Colors.green.shade700 : Colors.red.shade700,
                                            ),
                                            const SizedBox(width: 4),
                                            Text(
                                              isInsideRadius
                                                  ? 'Dalam Radius: ${distanceToTarget.round()}m (Maks ${m.radiusMeter}m)'
                                                  : 'Di Luar Radius: ${distanceToTarget.round()}m (Maks ${m.radiusMeter}m)',
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: isInsideRadius ? Colors.green.shade700 : Colors.red.shade700,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              );
                            },
                          ),
                        ],

                        if (widget.type == 'visit_in') ...[
                          Builder(builder: (context) {
                            final itinerary = attProvider.todayItinerary;
                            final isStrict = itinerary?['is_strict_routing'] == true;
                            if (!isStrict) return const SizedBox.shrink();
                            return Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: Colors.amber.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: Colors.amber.shade700, width: 0.8),
                              ),
                              child: Row(
                                children: [
                                  Icon(Icons.lock, color: Colors.amber.shade900, size: 18),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      'Aturan Routing Aktif: Kunjungan wajib dilakukan berurutan sesuai list rute toko.',
                                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.amber.shade900),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          }),
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
                                final itemsList = (attProvider.todayItinerary?['items'] as List?) ?? [];
                                final itItem = itemsList.firstWhere(
                                  (e) => e['work_location_id'] == loc['id'],
                                  orElse: () => null,
                                );
                                final seq = itItem?['sequence'];
                                final isLocked = itItem?['is_locked'] == true;
                                final isTarget = itItem?['is_next_target'] == true;

                                String label = loc['name'] ?? 'Toko';
                                if (isTarget) {
                                  label = '🎯 $label (Urutan #$seq - Target)';
                                } else if (isLocked) {
                                  label = '🔒 $label (Urutan #$seq - Terkunci)';
                                } else if (seq != null) {
                                  label = '$label (Urutan #$seq)';
                                }

                                return DropdownMenuItem<int>(
                                  value: loc['id'],
                                  child: Text(
                                    label,
                                    style: TextStyle(
                                      fontSize: 12.5,
                                      fontWeight: isTarget ? FontWeight.bold : FontWeight.normal,
                                      color: isLocked ? subtitleColor : textColor,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                );
                              }).toList(),
                              onChanged: (val) {
                                setState(() {
                                  _selectedWorkLocationId = val;
                                });
                              },
                            ),
                          ),
                        ],

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

                        // ─── ADAPTIVE FACE RECOGNITION BADGE ───────────────────────
                        Builder(
                          builder: (context) {
                            final authProvider = Provider.of<AuthProvider>(context, listen: false);
                            final posData = authProvider.employeeData?['position'];
                            final bool isFaceRequired = (posData is Map) ? (posData['require_face_recognition'] ?? false) : false;
                            final posName = (posData is Map) ? (posData['name'] ?? 'Jabatan') : 'Jabatan';

                            return Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                              margin: const EdgeInsets.only(bottom: 10),
                              decoration: BoxDecoration(
                                color: isFaceRequired
                                    ? (isDarkMode ? const Color(0xFF1E293B) : const Color(0xFFEFF6FF))
                                    : (isDarkMode ? const Color(0xFF1E293B) : const Color(0xFFEFF6FF)),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: const Color(0xFF38BDF8).withValues(alpha: 0.4),
                                ),
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.face_retouching_natural,
                                    size: 16,
                                    color: Color(0xFF0284C7),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      isFaceRequired
                                          ? 'Face Recognition AI: Wajib Biometrik Master ($posName)'
                                          : 'Foto Presensi: Liveness AI Detection ($posName)',
                                      style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                        color: Color(0xFF0284C7),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),

                        // ─── SELFIE PREVIEW & ACTION BUTTONS ───────────────────────
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
                              const Expanded(child: Text('Selfie Berhasil Diambil', style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold))),
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
                              child: Builder(
                                builder: (context) {
                                  final authProvider = Provider.of<AuthProvider>(context, listen: false);
                                  final posData = authProvider.employeeData?['position'];
                                  final bool isFaceRequired = (posData is Map) ? (posData['require_face_recognition'] ?? false) : false;
                                  final String? masterPhoto = authProvider.employeeData?['photo_url'] ?? authProvider.employeeData?['photo'];
                                  final bool hasMasterPhoto = masterPhoto != null && masterPhoto.isNotEmpty && !masterPhoto.contains('default.png');
                                  final bool isPhotoMissing = isFaceRequired && _selfieFile == null && (widget.type == 'checkin' || widget.type == 'visit_in' || widget.type == 'meet_in');

                                  if (isFaceRequired && !hasMasterPhoto) {
                                    return Container(
                                      decoration: BoxDecoration(
                                        borderRadius: BorderRadius.circular(12),
                                        gradient: const LinearGradient(
                                          colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
                                        ),
                                      ),
                                      child: ElevatedButton.icon(
                                        onPressed: () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(builder: (_) => const ProfileScreen()),
                                          );
                                        },
                                        icon: const Icon(Icons.face_retouching_natural, color: Colors.white, size: 18),
                                        label: const Text(
                                          'Daftarkan Master Wajah',
                                          style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 12),
                                        ),
                                        style: ElevatedButton.styleFrom(
                                          padding: const EdgeInsets.symmetric(vertical: 14),
                                          backgroundColor: Colors.transparent,
                                          shadowColor: Colors.transparent,
                                          foregroundColor: Colors.white,
                                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                        ),
                                      ),
                                    );
                                  }

                                  return Container(
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(12),
                                      gradient: isPhotoMissing
                                        ? LinearGradient(colors: [Colors.grey.shade400, Colors.grey.shade400])
                                        : LinearGradient(
                                            colors: widget.type == 'checkin'
                                                ? [primaryColor, Colors.green]
                                                : widget.type == 'checkout'
                                                    ? [primaryColor, Colors.red]
                                                    : widget.type == 'visit_in'
                                                        ? [primaryColor, Colors.lightBlue]
                                                        : widget.type == 'meet_in'
                                                            ? [primaryColor, Colors.teal]
                                                            : [primaryColor, Colors.orange],
                                            begin: Alignment.centerLeft,
                                            end: Alignment.centerRight,
                                          ),
                                    ),
                                    child: ElevatedButton(
                                      onPressed: (!isPhotoMissing || widget.type == 'checkout' || widget.type == 'visit_out') ? _submitAttendance : null,
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
                                        : widget.type == 'meet_in' ? 'Meet-In Sekarang'
                                        : 'Visit Out',
                                        style: const TextStyle(fontWeight: FontWeight.bold),
                                      ),
                                    ),
                                  );
                                },
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
