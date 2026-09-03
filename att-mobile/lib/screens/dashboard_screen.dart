import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/attendance_location_screen.dart';
import 'package:att_mobile/screens/history_screen.dart';
import 'package:att_mobile/screens/main_screen.dart';
import 'package:att_mobile/screens/permit_screen.dart';
import 'package:att_mobile/screens/itinerary_screen.dart';
import 'package:att_mobile/screens/coming_soon_screen.dart';
import 'package:att_mobile/screens/notification_screen.dart';
import 'package:att_mobile/screens/visit_report_screen.dart';
import 'package:att_mobile/screens/overtime_screen.dart';
import 'package:att_mobile/providers/notification_provider.dart';
import 'package:att_mobile/providers/dashboard_provider.dart';
import 'package:att_mobile/widgets/dashboard_stats_widget.dart';
import 'package:att_mobile/widgets/team_stats_widget.dart';
import 'package:att_mobile/screens/blast_info_screen.dart';

import 'package:att_mobile/services/location_service.dart';
import 'package:att_mobile/screens/payslip_screen.dart';
import 'package:att_mobile/screens/help_screen.dart';
import 'package:att_mobile/providers/locale_provider.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:att_mobile/screens/chat_screen.dart';
import 'package:att_mobile/providers/chat_provider.dart';
import 'package:att_mobile/models/meeting_model.dart';
import 'package:att_mobile/screens/meeting_report_screen.dart';
import 'package:att_mobile/screens/meeting_detail_screen.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:geolocator/geolocator.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/widgets/skeleton_loading.dart';
import 'package:att_mobile/screens/reporting_hub_screen.dart';
import 'package:att_mobile/screens/request_location_screen.dart';
import 'package:att_mobile/screens/bap_screen.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/services/offline_sync_service.dart';
import 'dart:io';
import 'package:att_mobile/screens/liveness_camera_screen.dart';


class DashboardScreen extends StatefulWidget {
  final Function(int)? switchTab;
  
  const DashboardScreen({super.key, this.switchTab});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> with WidgetsBindingObserver {
  late Timer _timer;
  DateTime _currentTime = DateTime.now();
  String _appVersion = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
      await attProvider.loadDashboardData();
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      Provider.of<NotificationProvider>(context, listen: false).fetchNotifications(authProvider);
      
      final dashboardProvider = Provider.of<DashboardProvider>(context, listen: false);
      await dashboardProvider.fetchDashboardStats();
      final positionName = authProvider.employeeData?['position']?['name']?.toString().toUpperCase() ?? '';
      dashboardProvider.fetchTeamStats();
      if (authProvider.token != null) {
        Provider.of<DynamicReportingProvider>(context, listen: false).fetchTemplates(authProvider.token!);
      }
      _syncLocationService(attProvider);

      // Cek apakah jabatan wajib Face Recognition tapi foto master belum ada
      final bool isFaceReq = _isFaceRequired(authProvider);
      final bool hasPhoto = _hasMasterPhoto(authProvider);
      final pos = authProvider.employeeData?['position'];
      if (isFaceReq && !hasPhoto && mounted) {
        toastification.show(
          context: context,
          title: const Text('⚠️ Registrasi Wajah Master Diperlukan'),
          description: Text('Jabatan Anda (${pos is Map ? (pos['name'] ?? '') : ''}) wajib Face Recognition. Silakan ambil foto master wajah Anda.'),
          type: ToastificationType.warning,
          style: ToastificationStyle.flat,
          autoCloseDuration: const Duration(seconds: 6),
        );
      }
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          _currentTime = DateTime.now();
        });
      }
    });
    
    PackageInfo.fromPlatform().then((PackageInfo packageInfo) {
      if (mounted) {
        setState(() {
          _appVersion = packageInfo.version;
        });
      }
    });
  }

  bool _isFaceRequired(AuthProvider auth) {
    final pos = auth.employeeData?['position'];
    if (pos is! Map) return false;
    final val = pos['require_face_recognition'];
    return val == true || val == 1 || val == '1';
  }

  bool _hasMasterPhoto(AuthProvider auth) {
    final String? photo = auth.employeeData?['photo'];
    return photo != null && photo.trim().isNotEmpty && !photo.contains('default.png') && !photo.contains('placeholder');
  }

  bool _isFaceBlocked(AuthProvider auth) {
    return _isFaceRequired(auth) && !_hasMasterPhoto(auth);
  }

  Future<void> _enrollMasterFace(BuildContext context) async {
    final photoPath = await Navigator.push<String?>(
      context,
      MaterialPageRoute(
        builder: (_) => const LivenessCameraScreen(isRequired: true, isEnrollment: true),
      ),
    );

    if (photoPath != null && mounted) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final bytes = await File(photoPath).readAsBytes();

      toastification.show(
        context: context,
        title: const Text('Mengunggah Foto Master Wajah...'),
        type: ToastificationType.info,
        autoCloseDuration: const Duration(seconds: 2),
      );

      final result = await auth.updateProfile({}, imageBytes: bytes, imageFilename: 'master_face.jpg');

      if (mounted) {
        if (result['success'] == true) {
          toastification.show(
            context: context,
            title: const Text('Wajah Master Berhasil Didaftarkan!'),
            description: const Text('Foto wajah Anda telah tersimpan sebagai referensi Face Recognition.'),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 4),
          );
        } else {
          toastification.show(
            context: context,
            title: const Text('Gagal Menyimpan Wajah Master'),
            description: Text(result['message'] ?? 'Terjadi kesalahan saat mengunggah foto.'),
            type: ToastificationType.error,
            autoCloseDuration: const Duration(seconds: 4),
          );
        }
      }
    }
  }

  bool _checkFaceMasterOrBlock(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);

    if (_isFaceBlocked(authProvider)) {
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.red.shade100,
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.face_retouching_natural, color: Colors.red.shade700, size: 24),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Wajib Master Wajah',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          content: const Text(
            'Jabatan Anda mewajibkan Face Recognition (Liveness AI), namun Anda belum mendaftarkan Foto Master Wajah.\n\nAnda TIDAK DAPAT melakukan Check-In / Presensi sebelum mendaftarkan foto master wajah Anda.',
            style: TextStyle(fontSize: 13, height: 1.4),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Batal', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton.icon(
              onPressed: () {
                Navigator.pop(ctx);
                _enrollMasterFace(context);
              },
              icon: const Icon(Icons.camera_alt, size: 16),
              label: const Text('Daftarkan Wajah Sekarang', style: TextStyle(fontWeight: FontWeight.bold)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFEF4444),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
          ],
        ),
      );
      return false;
    }
    return true;
  }

  Future<void> _syncLocationService(AttendanceProvider attProvider) async {
    try {
      if (attProvider.isCheckedIn) {
        await LocationService.startService();
      } else {
        await LocationService.stopService();
      }
    } catch (e) {
      debugPrint('LocationService sync (non-fatal): $e');
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && mounted) {
      final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
      _syncLocationService(attProvider);
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _timer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final attProvider = Provider.of<AttendanceProvider>(context);
    final notificationProvider = Provider.of<NotificationProvider>(context);
    final dashboardProvider = Provider.of<DashboardProvider>(context);
    final locale = Provider.of<LocaleProvider>(context);

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    if (authProvider.employeeData == null) {
      return Scaffold(
        backgroundColor: backgroundColor,
        body: const SafeArea(child: DashboardSkeleton()),
      );
    }

    final employeeName = authProvider.employeeData?['full_name'] ?? 'User';
    final positionName = authProvider.employeeData?['position']?['name'] ?? '-';
    final branchName = authProvider.employeeData?['branch']?['name'] ?? '-';
    final principalName = authProvider.employeeData?['principal']?['name']?.toString().trim() ?? '';
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final repProvider = Provider.of<DynamicReportingProvider>(context);
    final hasSalesReporting = authProvider.employeeData?['department']?['has_sales_reporting'] == 1 || authProvider.employeeData?['department']?['has_sales_reporting'] == true;
    final hasCustomReporting = (authProvider.employeeData?['has_reporting_templates'] == 1 ||
        authProvider.employeeData?['has_reporting_templates'] == true) ||
        (authProvider.employeeData?['principal_id'] != null && repProvider.templates.isNotEmpty);

    String checkinTime = '--:--';
    String checkoutTime = '--:--';
    String duration = '-';
    
    List<dynamic> logs = attProvider.todayLogs;
    if (logs.isNotEmpty) {
      final checkinLog = logs.lastWhere((log) => log['log_type'] == 'checkin', orElse: () => null);
      if (checkinLog != null) {
        checkinTime = _extractTime(checkinLog['logged_at']);
      }
      
      final checkoutLog = logs.firstWhere((log) => log['log_type'] == 'checkout', orElse: () => null);
      if (checkoutLog != null) {
        checkoutTime = _extractTime(checkoutLog['logged_at']);
      }
      
      if (checkinLog != null) {
        try {
          DateTime cin = DateTime.parse('${checkinLog['logged_at']}');
          DateTime cout = checkoutLog != null ? DateTime.parse('${checkoutLog['logged_at']}') : DateTime.now();
          Duration diff = cout.difference(cin);
          String hours = diff.inHours.toString().padLeft(2, '0');
          String minutes = (diff.inMinutes % 60).toString().padLeft(2, '0');
          String seconds = (diff.inSeconds % 60).toString().padLeft(2, '0');
          duration = '$hours:$minutes:$seconds';
        } catch (_) {}
      }
    }
    
    String scheduleLocationName = branchName;
    String scheduleLocationAddress = authProvider.employeeData?['branch']?['address'] ?? '-';
    if (attProvider.todaySchedule != null && attProvider.todaySchedule!['work_location'] != null) {
      scheduleLocationName = attProvider.todaySchedule!['work_location']['name'] ?? branchName;
      final company = attProvider.todaySchedule!['work_location']['company'];
      if (company != null && company['name'] != null) {
        scheduleLocationName = '${company['name']} - $scheduleLocationName';
      }
      scheduleLocationAddress = attProvider.todaySchedule!['work_location']['address'] ?? '-';
    }

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 17, vertical: 13),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Offline Pending Sync Notification Banner
                if (attProvider.pendingOfflineCount > 0)
                  Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFFDE68A)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.amber.withOpacity(0.08),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.cloud_sync_rounded, color: Color(0xFFD97706), size: 22),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            '${attProvider.pendingOfflineCount} presensi tersimpan di HP (Offline). Mengirim otomatis saat online...',
                            style: const TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF92400E),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        InkWell(
                          onTap: () async {
                            final token = authProvider.token;
                            if (token != null) {
                              final count = await OfflineSyncService.syncAllPendingActions(token);
                              if (context.mounted) {
                                await attProvider.refreshPendingOfflineCount();
                                await attProvider.loadDashboardData();
                                if (count > 0) {
                                  toastification.show(
                                    context: context,
                                    title: Text('$count data offline berhasil disinkronkan ke server'),
                                    autoCloseDuration: const Duration(seconds: 3),
                                    type: ToastificationType.success,
                                  );
                                }
                              }
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: const Color(0xFFD97706),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text(
                              'Sync',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                // Header (home-head)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _currentTime.hour < 12
                                ? locale.tr('good_morning')
                                : (_currentTime.hour < 15
                                    ? locale.tr('good_afternoon')
                                    : (_currentTime.hour < 18
                                        ? locale.tr('good_evening')
                                        : locale.tr('good_night'))),
                            style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 3),
                          Text(employeeName, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
                          const SizedBox(height: 2),
                          Text(
                            [
                              if (positionName.isNotEmpty && positionName != '-') positionName,
                              if (branchName.isNotEmpty && branchName != '-') branchName,
                              if (principalName.isNotEmpty && principalName != '-') principalName,
                            ].join(' · '),
                            style: TextStyle(fontSize: 10.5, color: subtitleColor, fontWeight: FontWeight.w500),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    Row(
                      children: [
                        GestureDetector(
                          onTap: () {
                            Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationScreen()));
                          },
                          child: Container(
                            width: 32, height: 32,
                            decoration: BoxDecoration(
                              color: cardColor,
                              borderRadius: BorderRadius.circular(9),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: Stack(
                              alignment: Alignment.center,
                              clipBehavior: Clip.none,
                              children: [
                                Icon(Icons.notifications_none, size: 18, color: textColor),
                                if (notificationProvider.unreadCount > 0)
                                  Positioned(
                                    top: 4, right: 4,
                                    child: Container(
                                      width: 6, height: 6,
                                      decoration: BoxDecoration(
                                        color: Colors.red,
                                        shape: BoxShape.circle,
                                        border: Border.all(color: cardColor, width: 1.5)
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 7),
                        CircleAvatar(
                          radius: 16,
                          backgroundColor: elevatedColor,
                          backgroundImage: (authProvider.employeeData?['photo'] != null && authProvider.employeeData!['photo'].toString().isNotEmpty)
                              ? NetworkImage(Constants.getImageUrl(authProvider.employeeData!['photo']))
                              : NetworkImage('https://ui-avatars.com/api/?name=${Uri.encodeComponent(employeeName)}&background=random') as ImageProvider,
                        ),
                      ],
                    ),
                  ],
                ),
                
                const SizedBox(height: 11),

                // Time Card (Dengan Maskot 3D Superhero Sesuai Desain)
                Container(
                  height: 125,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        primaryColor,
                        HSLColor.fromColor(primaryColor)
                            .withLightness((HSLColor.fromColor(primaryColor).lightness * 0.65).clamp(0.0, 1.0))
                            .toColor(),
                      ],
                    ),
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: primaryColor.withValues(alpha: 0.35),
                        blurRadius: 14,
                        offset: const Offset(0, 5),
                      ),
                    ],
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Stack(
                    children: [
                      // Mascot Graphic Positioned on the Right
                      Positioned(
                        right: -4,
                        top: 0,
                        bottom: 0,
                        child: Image.asset(
                          'assets/images/time_card_mascot.png',
                          fit: BoxFit.contain,
                          alignment: Alignment.bottomRight,
                          errorBuilder: (context, error, stackTrace) => const SizedBox.shrink(),
                        ),
                      ),

                      // Left Information (Live Clock, Date, Location)
                      Positioned.fill(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 15),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                locale.formatDateTime(_currentTime, pattern: 'HH:mm:ss'),
                                style: const TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                  fontFamily: 'monospace',
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                locale.formatDateTime(_currentTime, pattern: 'EEEE, dd MMMM yyyy'),
                                style: TextStyle(
                                  fontSize: 10.5,
                                  color: Colors.white.withValues(alpha: 0.9),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              const SizedBox(height: 10),
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.location_on, size: 12, color: Colors.white),
                                  const SizedBox(width: 4),
                                  Flexible(
                                    child: Text(
                                      '$branchName · ${locale.timeZone}',
                                      style: const TextStyle(
                                        fontSize: 9.5,
                                        color: Colors.white,
                                        fontWeight: FontWeight.w600,
                                      ),
                                      overflow: TextOverflow.ellipsis,
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
                ),

                const SizedBox(height: 11),

                // ─── BANNER REGISTRASI WAJAH MASTER ───────────────────────
                Builder(builder: (context) {
                  final position = authProvider.employeeData?['position'];
                  final bool isFaceRequired = (position is Map) ? (position['require_face_recognition'] ?? false) : false;
                  final String? masterPhoto = authProvider.employeeData?['photo'];
                  final bool hasMasterPhoto = masterPhoto != null && masterPhoto.isNotEmpty && !masterPhoto.contains('default.png');

                  if (!isFaceRequired || hasMasterPhoto) return const SizedBox.shrink();

                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          const Color(0xFFEF4444).withValues(alpha: 0.12),
                          const Color(0xFFF97316).withValues(alpha: 0.08),
                        ],
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.red.shade400, width: 1.2),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.red.withValues(alpha: 0.08),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        )
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.red.shade600,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.face_retouching_natural, color: Colors.white, size: 20),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          'Registrasi Wajah Master Diperlukan',
                                          style: TextStyle(
                                            fontSize: 12.5,
                                            fontWeight: FontWeight.bold,
                                            color: isDarkMode ? Colors.red.shade300 : Colors.red.shade800,
                                          ),
                                        ),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: Colors.red.shade100,
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          'WAJIB',
                                          style: TextStyle(
                                            fontSize: 9,
                                            fontWeight: FontWeight.bold,
                                            color: Colors.red.shade800,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Jabatan Anda ($positionName) mewajibkan Face Recognition. Mohon daftarkan foto wajah Anda untuk absensi.',
                                    style: TextStyle(fontSize: 10.5, color: subtitleColor),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: () => _enrollMasterFace(context),
                            icon: const Icon(Icons.camera_alt, size: 16, color: Colors.white),
                            label: const Text(
                              '📸 Daftarkan Wajah Master Sekarang',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11.5),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red.shade600,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              elevation: 0,
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                }),

                // Menu Lainnya
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(locale.tr('other_menus'), style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                  ],
                ),
                const SizedBox(height: 8),
                Builder(builder: (context) {
                  List<Map<String, dynamic>> allMenus = [
                    {'title': locale.tr('menu_attendance'), 'icon': Icons.calendar_today, 'color': primaryColor, 'onTap': () {
                      if (widget.switchTab != null) { widget.switchTab!(1); }
                      else { Navigator.push(context, MaterialPageRoute(builder: (_) => const HistoryScreen())).then((_) { attProvider.loadDashboardData(); }); }
                    }},
                    {'title': locale.tr('menu_visit'), 'icon': Icons.map, 'color': const Color(0xFF0FA8C4), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const ItineraryScreen())).then((_) { attProvider.loadDashboardData(); });
                    }},
                  ];

                  if (hasCustomReporting) {
                    allMenus.add({
                      'title': locale.tr('menu_reporting'),
                      'icon': Icons.assignment_rounded,
                      'color': const Color(0xFF0F52BA),
                      'onTap': () {
                        Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportingHubScreen()));
                      },
                    });
                  }

                  allMenus.addAll([
                    {'title': locale.tr('menu_permit'), 'icon': Icons.event_note, 'color': const Color(0xFFD98A2B), 'onTap': () {
                      if (widget.switchTab != null) { widget.switchTab!(2); }
                      else { Navigator.push(context, MaterialPageRoute(builder: (_) => const PermitScreen())).then((_) { attProvider.loadDashboardData(); }); }
                    }},
                    {'title': locale.tr('menu_overtime'), 'icon': Icons.timer, 'color': Colors.redAccent, 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const OvertimeScreen())).then((_) { attProvider.loadDashboardData(); });
                    }},
                    {'title': locale.tr('menu_announcement'), 'icon': Icons.campaign, 'color': const Color(0xFF149A6E), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const BlastInfoScreen())).then((_) { attProvider.loadDashboardData(); });
                    }},
                    {'title': locale.tr('menu_payslip'), 'icon': Icons.receipt_long, 'color': const Color(0xFF4A90E2), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const PayslipScreen())).then((_) { attProvider.loadDashboardData(); });
                    }},
                    {'title': 'Wajah Master', 'icon': Icons.face_retouching_natural, 'color': const Color(0xFF6366F1), 'onTap': () {
                      _enrollMasterFace(context);
                    }},
                    {'title': 'Request Lokasi', 'icon': Icons.add_location_alt_rounded, 'color': const Color(0xFF10B981), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const RequestLocationScreen()));
                    }},
                    {'title': 'Pengajuan BAP', 'icon': Icons.assignment_turned_in_outlined, 'color': const Color(0xFF0284C7), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const BapScreen())).then((_) { attProvider.loadDashboardData(); });
                    }},
                    {'title': locale.tr('menu_help'), 'icon': Icons.support_agent_rounded, 'color': const Color(0xFFE65100), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const HelpScreen())).then((_) { attProvider.loadDashboardData(); });
                    }},
                  ]);

                  if (hasSalesReporting) {
                    allMenus.add({'title': locale.tr('menu_sales'), 'icon': Icons.trending_up, 'color': Colors.purple, 'onTap': () {
                      if (widget.switchTab != null) { widget.switchTab!(3); }
                    }});
                  }

                  List<Widget> displayMenus = [];
                  if (allMenus.length > 5) {
                     for (int i = 0; i < 4; i++) {
                       displayMenus.add(_buildMenuQItem(allMenus[i]['title'], allMenus[i]['icon'], allMenus[i]['color'], allMenus[i]['onTap'], cardColor, textColor, false));
                     }
                     displayMenus.add(_buildMenuQItem(locale.tr('menu_more'), Icons.more_horiz, subtitleColor, () {
                       _showMoreMenu(context, allMenus.sublist(4), isDarkMode, cardColor, elevatedColor, subtitleColor, textColor);
                     }, cardColor, textColor, true));
                  } else {
                     for (int i = 0; i < allMenus.length; i++) {
                       displayMenus.add(_buildMenuQItem(allMenus[i]['title'], allMenus[i]['icon'], allMenus[i]['color'], allMenus[i]['onTap'], cardColor, textColor, false));
                     }
                  }
                  
                  return Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: displayMenus,
                  );
                }),

                const SizedBox(height: 15),

                // Target & Performa (For All Users)
                const DashboardStatsWidget(),
                const SizedBox(height: 15),

                // Team Stats (For users with subordinates/team)
                if (dashboardProvider.totalTeam > 0) ...[
                  const TeamStatsWidget(),
                  const SizedBox(height: 15),
                ],

                // Attend Card
                Builder(builder: (context) {
                  // Jika ada permit aktif yang disetujui, tampilkan kartu permit
                  if (attProvider.hasActivePermit && attProvider.activePermit != null) {
                    final permit = attProvider.activePermit!;
                    final permitType = permit['type_label'] ?? permit['type'] ?? 'Izin';
                    final permitNotes = permit['notes'] ?? '';
                    Color permitColor;
                    IconData permitIcon;
                    Color permitBgColor;
                    switch ((permit['type'] ?? '').toString().toLowerCase()) {
                      case 'sakit':
                        permitColor = const Color(0xFFE0473E);
                        permitBgColor = const Color(0xFFFCEAE9);
                        permitIcon = Icons.local_hospital_outlined;
                        break;
                      case 'cuti':
                        permitColor = const Color(0xFF149A6E);
                        permitBgColor = const Color(0xFFE2F6EE);
                        permitIcon = Icons.beach_access_outlined;
                        break;
                      default: // izin
                        permitColor = const Color(0xFFD98A2B);
                        permitBgColor = const Color(0xFFFFF3E0);
                        permitIcon = Icons.event_note_outlined;
                    }
                    return Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: permitColor.withOpacity(0.4)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 48, height: 48,
                            decoration: BoxDecoration(color: permitBgColor, borderRadius: BorderRadius.circular(13)),
                            child: Icon(permitIcon, color: permitColor, size: 24),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(permitType.toUpperCase(),
                                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: permitColor)),
                                const SizedBox(height: 3),
                                Text('Disetujui – tidak perlu check-in hari ini',
                                  style: TextStyle(fontSize: 11, color: subtitleColor)),
                                if (permitNotes.isNotEmpty) ...[
                                  const SizedBox(height: 3),
                                  Text(permitNotes,
                                    style: TextStyle(fontSize: 11, color: textColor),
                                    maxLines: 2, overflow: TextOverflow.ellipsis),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  }

                  // Tampilan normal check-in/out
                  return Container(
                    padding: const EdgeInsets.fromLTRB(15, 14, 15, 13),
                    decoration: BoxDecoration(
                      color: cardColor,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Column(
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(8)),
                              child: Icon(Icons.business, size: 16, color: primaryColor),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(scheduleLocationName, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                                  const SizedBox(height: 3),
                                  Text(scheduleLocationAddress, style: TextStyle(fontSize: 10, color: subtitleColor), maxLines: 2, overflow: TextOverflow.ellipsis),
                                ],
                              ),
                            ),
                          ],
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          child: Divider(color: Colors.grey.shade300, height: 1),
                        ),
                        Row(
                          children: [
                            Expanded(
                              child: Row(
                                children: [
                                  Container(
                                    width: 30, height: 30,
                                    decoration: BoxDecoration(
                                      color: (attProvider.isCheckedIn || attProvider.hasCheckedOutToday) ? const Color(0xFFE2F6EE) : elevatedColor,
                                      borderRadius: BorderRadius.circular(9),
                                    ),
                                    child: Icon(Icons.login, size: 14, color: (attProvider.isCheckedIn || attProvider.hasCheckedOutToday) ? const Color(0xFF149A6E) : Colors.grey),
                                  ),
                                  const SizedBox(width: 9),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text('CHECK-IN', style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                                      const SizedBox(height: 2),
                                      Text(checkinTime, style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor)),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                            Container(width: 1, height: 30, color: Colors.grey.shade300, margin: const EdgeInsets.symmetric(horizontal: 8)),
                            Expanded(
                              child: Row(
                                children: [
                                  Container(
                                    width: 30, height: 30,
                                    decoration: BoxDecoration(
                                      color: attProvider.hasCheckedOutToday ? const Color(0xFFFCEAE9) : elevatedColor,
                                      borderRadius: BorderRadius.circular(9),
                                    ),
                                    child: Icon(Icons.logout, size: 14, color: attProvider.hasCheckedOutToday ? const Color(0xFFE0473E) : subtitleColor),
                                  ),
                                  const SizedBox(width: 9),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text('CHECK-OUT', style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                                      const SizedBox(height: 2),
                                      Text(checkoutTime, style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: attProvider.hasCheckedOutToday ? textColor : subtitleColor)),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Builder(
                          builder: (context) {
                            final bool isFaceBlockedForCheckin = !attProvider.isCheckedIn && _isFaceBlocked(authProvider);

                            return InkWell(
                              onTap: () {
                                if (attProvider.hasCheckedOutToday) return;
                                if (isFaceBlockedForCheckin) {
                                  _checkFaceMasterOrBlock(context);
                                  return;
                                }
                                if (!attProvider.canCheckin && !attProvider.isCheckedIn) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(attProvider.checkinBlockMessage.isNotEmpty
                                          ? attProvider.checkinBlockMessage
                                          : 'Anda tidak memiliki jadwal kerja untuk hari ini. Silakan hubungi Admin.'),
                                      backgroundColor: Colors.red,
                                    ),
                                  );
                                  return;
                                }
                                if (attProvider.isCheckedIn) {
                                  if (attProvider.isVisiting) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(content: Text('Selesaikan laporan kunjungan (visit-out) Anda terlebih dahulu.'), backgroundColor: Colors.orange),
                                    );
                                    return;
                                  }
                                  Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'checkout'))).then((_) { attProvider.loadDashboardData(); });
                                } else {
                                  Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'checkin'))).then((_) { attProvider.loadDashboardData(); });
                                }
                              },
                              child: Container(
                                width: double.infinity,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                decoration: BoxDecoration(
                                  gradient: attProvider.hasCheckedOutToday
                                      ? const LinearGradient(colors: [Colors.grey, Colors.grey])
                                      : isFaceBlockedForCheckin
                                          ? LinearGradient(colors: [Colors.grey.shade500, Colors.grey.shade600])
                                          : (!attProvider.canCheckin && !attProvider.isCheckedIn)
                                              ? LinearGradient(colors: [Colors.grey.shade500, Colors.grey.shade600])
                                              : (attProvider.isCheckedIn && attProvider.isVisiting)
                                                  ? const LinearGradient(colors: [Colors.grey, Colors.grey])
                                                  : LinearGradient(
                                                      colors: [
                                                        primaryColor,
                                                        attProvider.isCheckedIn ? Colors.red.shade400 : Colors.green.shade400
                                                      ],
                                                    ),
                                  borderRadius: BorderRadius.circular(13),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(
                                      attProvider.hasCheckedOutToday
                                          ? Icons.done_all
                                          : isFaceBlockedForCheckin
                                              ? Icons.face_retouching_off
                                              : (attProvider.isCheckedIn
                                                  ? Icons.logout
                                                  : (attProvider.canCheckin ? Icons.login : Icons.event_busy)),
                                      color: Colors.white,
                                      size: 14,
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      attProvider.hasCheckedOutToday
                                          ? 'Selesai Bekerja'
                                          : isFaceBlockedForCheckin
                                              ? 'Check-in Dinonaktifkan (Wajib Master Wajah)'
                                              : (attProvider.isCheckedIn
                                                  ? 'Check-out Sekarang'
                                                  : (attProvider.canCheckin ? 'Check-in Sekarang' : 'Tidak Ada Jadwal Kerja')),
                                      style: const TextStyle(color: Colors.white, fontSize: 12.5, fontWeight: FontWeight.bold)
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.info_outline, size: 10, color: subtitleColor),
                            const SizedBox(width: 5),
                            Text(
                              attProvider.isCheckedIn
                                  ? 'Sedang bekerja sejak $checkinTime • Durasi: $duration'
                                  : (attProvider.hasCheckedOutToday
                                      ? 'Selesai bekerja • Durasi: $duration'
                                      : (_isFaceBlocked(authProvider)
                                          ? 'Kewajiban Face Recognition aktif • Wajib master wajah'
                                          : (attProvider.canCheckin ? 'Belum check-in' : 'Tidak ada jadwal hari ini'))),
                              style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                }),
                
                const SizedBox(height: 15),

                // Kunjungan Lapangan (HANYA tampil jika sedang visit atau memiliki jadwal visit hari ini yang belum selesai)
                if (attProvider.isVisiting || (attProvider.canVisit && attProvider.todayItinerary != null && (attProvider.todayItinerary?['items'] as List? ?? []).isNotEmpty)) ...[
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Kunjungan Lapangan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      // ─── 1. Tombol Visit-in (Dapat ditekan jika sudah check-in ATAU jika Ratecard punya jadwal visit) ───
                      Builder(
                        builder: (context) {
                          final isRatecard = (authProvider.employeeData?['is_inhouse'] == false);
                          final isFaceBlocked = _isFaceBlocked(authProvider);
                          final canDoVisitIn = !isFaceBlocked && !attProvider.isVisiting && (attProvider.isCheckedIn || (isRatecard && attProvider.canVisit));

                          return Expanded(
                            child: InkWell(
                              onTap: isFaceBlocked
                                  ? () => _checkFaceMasterOrBlock(context)
                                  : (canDoVisitIn
                                      ? () {
                                          Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'visit_in'))).then((_) { attProvider.loadDashboardData(); });
                                        }
                                      : null),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 11),
                                decoration: BoxDecoration(
                                  gradient: canDoVisitIn
                                      ? LinearGradient(colors: [primaryColor, Colors.lightBlue.shade400])
                                      : LinearGradient(colors: [cardColor, cardColor]),
                                  border: Border.all(
                                    color: canDoVisitIn
                                        ? Colors.transparent
                                        : (isFaceBlocked ? Colors.red.shade300 : Colors.grey.shade300),
                                  ),
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Container(
                                      width: 27, height: 27,
                                      decoration: BoxDecoration(
                                        color: canDoVisitIn
                                            ? Colors.white.withOpacity(0.2)
                                            : (isFaceBlocked ? Colors.red.shade50 : elevatedColor),
                                        borderRadius: BorderRadius.circular(8)
                                      ),
                                      child: Icon(
                                        isFaceBlocked ? Icons.face_retouching_off : Icons.transfer_within_a_station,
                                        size: 14,
                                        color: canDoVisitIn ? Colors.white : (isFaceBlocked ? Colors.red.shade500 : subtitleColor),
                                      ),
                                    ),
                                    const SizedBox(height: 7),
                                    Text(
                                      isFaceBlocked ? 'Wajib Master' : 'Visit-in',
                                      style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.bold,
                                        color: canDoVisitIn ? Colors.white : (isFaceBlocked ? Colors.red.shade500 : textColor),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }
                      ),
                      const SizedBox(width: 8),

                      // ─── 2. Tombol Laporan (Mengunci ke Reporting Hub jika Ratecard with Templates, else Form Inhouse 7 Poin) ───
                      Builder(
                        builder: (context) {
                          final canDoReport = attProvider.isVisiting && !attProvider.hasFilledVisitReport;
                          final isRatecard = (authProvider.employeeData?['is_inhouse'] == false);
                          final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
                          final hasReportingTemplates = (authProvider.employeeData?['has_reporting_templates'] == 1 ||
                              authProvider.employeeData?['has_reporting_templates'] == true) ||
                              (authProvider.employeeData?['principal_id'] != null && repProvider.templates.isNotEmpty);

                          return Expanded(
                            child: InkWell(
                              onTap: canDoReport ? () async {
                                if (isRatecard && hasReportingTemplates) {
                                  // Lock ke Form Pelaporan Prinsiple (Reporting Hub)
                                  final destinationName = attProvider.todayItinerary?['name']?.toString() ?? attProvider.todayItinerary?['destination']?.toString();
                                  await Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (_) => ReportingHubScreen(
                                        storeName: destinationName,
                                      ),
                                    ),
                                  );
                                  attProvider.loadDashboardData();
                                } else {
                                  // Buka Form Visit Inhouse (7 Poin)
                                  final result = await Navigator.push(context, MaterialPageRoute(builder: (_) => const VisitReportScreen()));
                                  if (result == true) {
                                    attProvider.loadDashboardData();
                                  }
                                }
                              } : null,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 11),
                                decoration: BoxDecoration(
                                  gradient: canDoReport
                                      ? LinearGradient(colors: [primaryColor, Colors.purple.shade400])
                                      : LinearGradient(colors: [cardColor, cardColor]),
                                  border: Border.all(color: canDoReport ? Colors.transparent : Colors.grey.shade300),
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Container(
                                      width: 27, height: 27,
                                      decoration: BoxDecoration(
                                        color: canDoReport ? Colors.white.withOpacity(0.2) : elevatedColor,
                                        borderRadius: BorderRadius.circular(8)
                                      ),
                                      child: Icon(Icons.assignment, size: 14, color: canDoReport ? Colors.white : subtitleColor),
                                    ),
                                    const SizedBox(height: 7),
                                    Text('Laporan', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: canDoReport ? Colors.white : textColor)),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }
                      ),
                      const SizedBox(width: 8),

                      // ─── 3. Tombol Visit-out (Aktif setelah Laporan diisi) ───
                      Builder(
                        builder: (context) {
                          final canDoVisitOut = attProvider.isVisiting && attProvider.hasFilledVisitReport;

                          return Expanded(
                            child: InkWell(
                              onTap: canDoVisitOut ? () {
                                Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'visit_out'))).then((_) { attProvider.loadDashboardData(); });
                              } : null,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 11),
                                decoration: BoxDecoration(
                                  gradient: canDoVisitOut
                                      ? LinearGradient(colors: [primaryColor, Colors.orange.shade400])
                                      : LinearGradient(colors: [cardColor, cardColor]),
                                  border: Border.all(color: canDoVisitOut ? Colors.transparent : Colors.grey.shade300),
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Container(
                                      width: 27, height: 27,
                                      decoration: BoxDecoration(
                                        color: canDoVisitOut ? Colors.white.withOpacity(0.2) : elevatedColor,
                                        borderRadius: BorderRadius.circular(8)
                                      ),
                                      child: Icon(Icons.directions_run, size: 14, color: canDoVisitOut ? Colors.white : subtitleColor),
                                    ),
                                    const SizedBox(height: 7),
                                    Text('Visit-out', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: canDoVisitOut ? Colors.white : textColor)),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }
                      ),
                    ],
                  ),
                const SizedBox(height: 15),
                ],

                // ─── Jadwal Meeting Hari Ini ──────────────────────────────────
                if (attProvider.todayMeetings.isNotEmpty) ...[
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.video_camera_front, size: 15, color: primaryColor),
                          const SizedBox(width: 6),
                          Text('Jadwal Meeting Hari Ini', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: primaryColor.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          '${attProvider.todayMeetings.length} Meeting',
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: primaryColor),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ...attProvider.todayMeetings.where((meeting) {
                    // Hide meetings that have ended AND user has not attended/is not in meeting
                    if (!meeting.isInMeeting && !meeting.isCompleted) {
                      // Parse end time to check if meeting has passed
                      try {
                        final now = DateTime.now();
                        final endTimeStr = meeting.endTime ?? meeting.startTime;
                        final parts = endTimeStr.split(':');
                        if (parts.length >= 2) {
                          final meetingEnd = DateTime(
                            now.year, now.month, now.day,
                            int.parse(parts[0]),
                            int.parse(parts[1]),
                          );
                          // Add 30 min grace period after end time
                          if (now.isAfter(meetingEnd.add(const Duration(minutes: 30)))) {
                            return false; // hide expired unattended meeting
                          }
                        }
                      } catch (_) {}
                    }
                    return true;
                  }).map((meeting) {
                    return _buildMeetingCard(context, meeting, attProvider, primaryColor, cardColor, textColor, subtitleColor);
                  }).toList(),
                  const SizedBox(height: 14),
                ],

                // Log Aktivitas
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Log Aktivitas Hari Ini', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                    Text('Semua', style: TextStyle(fontSize: 10, color: primaryColor, fontWeight: FontWeight.bold)),
                  ],
                ),
                const SizedBox(height: 8),
                if (logs.isEmpty)
                   Padding(
                     padding: const EdgeInsets.all(16.0),
                     child: Center(child: Text('Tidak ada aktivitas hari ini', style: TextStyle(color: subtitleColor, fontSize: 11))),
                   )
                else
                   ListView.builder(
                     shrinkWrap: true,
                     physics: const NeverScrollableScrollPhysics(),
                     itemCount: logs.length,
                     itemBuilder: (context, index) {
                       final log = logs[index];
                       final timeStr = _extractTime(log['logged_at']);
                       final typeStr = log['log_type'].toString();
                       final locationName = (log['visit_location'] != null && log['visit_location']['name'] != null)
                           ? log['visit_location']['name']
                           : branchName;
                       final Color dotColor = _getLogColor(log['log_type']);
                       
                       String title = typeStr.toUpperCase();
                       if (typeStr == 'checkin') title = 'Check-in Kantor';
                       if (typeStr == 'checkout') title = 'Check-out Kantor';
                       if (typeStr == 'visit_in') title = 'Visit-in — $locationName';
                       if (typeStr == 'visit_out') title = 'Visit-out — $locationName';
                       if (typeStr == 'meet_in') title = 'Meet-In — $locationName';
                       if (typeStr == 'meet_out') title = 'Meet-Out / Selesai — $locationName';

                       return Container(
                         padding: const EdgeInsets.symmetric(vertical: 7),
                         decoration: BoxDecoration(
                           border: index != logs.length - 1 ? Border(bottom: BorderSide(color: Colors.grey.shade300)) : null
                         ),
                         child: Row(
                           children: [
                             Container(
                               width: 7, height: 7,
                               decoration: BoxDecoration(color: dotColor, shape: BoxShape.circle),
                             ),
                             const SizedBox(width: 9),
                             Expanded(
                               child: Column(
                                 crossAxisAlignment: CrossAxisAlignment.start,
                                 children: [
                                   Text(title, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: textColor)),
                                   const SizedBox(height: 1),
                                   Text(locationName.toString(), style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                                 ],
                               ),
                             ),
                             Text(timeStr, style: TextStyle(fontSize: 9.5, color: subtitleColor, fontWeight: FontWeight.bold)),
                           ],
                         ),
                       );
                     },
                   ),


                const SizedBox(height: 20),
                if (_appVersion.isNotEmpty)
                  Center(
                    child: Text(
                      'v$_appVersion',
                      style: TextStyle(color: subtitleColor, fontSize: 10),
                    ),
                  ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
      floatingActionButton: Consumer<ChatProvider>(
        builder: (context, chatProvider, child) {
          return Stack(
            children: [
              FloatingActionButton(
                backgroundColor: primaryColor,
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => ChatScreen()));
                },
                child: const Icon(Icons.chat, color: Colors.white),
              ),
              if (chatProvider.unreadCount > 0)
                Positioned(
                  right: 0,
                  top: 0,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: const BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                    constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                    child: Text(
                      '${chatProvider.unreadCount}',
                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildMenuQItem(String title, IconData icon, Color color, VoidCallback onTap, Color cardColor, Color textColor, bool isMore) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Column(
          children: [
            Container(
              width: 44, height: 44,
              decoration: BoxDecoration(
                color: isMore ? (Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade800 : const Color(0xFFEDF1F8)) : cardColor,
                borderRadius: BorderRadius.circular(13),
                border: Border.all(color: Colors.grey.shade300),
              ),
              child: Icon(icon, color: color, size: 19),
            ),
            const SizedBox(height: 6),
            Text(title, style: TextStyle(fontSize: 9, fontWeight: FontWeight.w600, color: textColor), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  void _showMoreMenu(BuildContext context, List<Map<String, dynamic>> additionalMenus, bool isDarkMode, Color cardColor, Color elevatedColor, Color subtitleColor, Color textColor) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.fromLTRB(17, 16, 17, 14),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: const BorderRadius.only(topLeft: Radius.circular(22), topRight: Radius.circular(22)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(width: 36, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(3))),
              const SizedBox(height: 12),
              Column(
                children: [
                  Text('Menu Lainnya', style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: textColor)),
                  const SizedBox(height: 4),
                  Text('Semua fitur tambahan ESA groups', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                ],
              ),
              const SizedBox(height: 16),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  childAspectRatio: 1.1,
                ),
                itemCount: additionalMenus.length,
                itemBuilder: (context, index) {
                  final menu = additionalMenus[index];
                  return GestureDetector(
                    onTap: () {
                      Navigator.pop(ctx);
                      menu['onTap']();
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
                      decoration: BoxDecoration(
                        color: elevatedColor,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            width: 38, height: 38,
                            decoration: BoxDecoration(
                              color: cardColor,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.grey.shade300),
                            ),
                            child: Icon(menu['icon'], color: menu['color'], size: 18),
                          ),
                          const SizedBox(height: 7),
                          Text(menu['title'], style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: textColor), textAlign: TextAlign.center),
                        ],
                      ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 16),
              InkWell(
                onTap: () => Navigator.pop(ctx),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(11),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: Text('Tutup', textAlign: TextAlign.center, style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor)),
                ),
              ),
            ],
          ),
        );
      }
    );
  }

  String _extractTime(String? datetimeStr) {
    if (datetimeStr == null || datetimeStr.isEmpty) return '--:--';
    try {
      final dt = DateTime.parse('${datetimeStr}');
      return DateFormat('HH:mm').format(dt);
    } catch (_) {
      return '--:--';
    }
  }

  Color _getLogColor(String logType) {
    switch (logType.toLowerCase()) {
      case 'checkin':
        return const Color(0xFF149A6E);
      case 'checkout':
        return const Color(0xFFE0473E);
      case 'visit_in':
        return const Color(0xFF0FA8C4);
      case 'visit_out':
        return const Color(0xFFD98A2B);
      case 'meet_in':
        return const Color(0xFF10B981);
      case 'meet_out':
        return const Color(0xFF8B5CF6);
      default:
        return Colors.blue;
    }
  }

  Widget _buildMeetingCard(
    BuildContext context,
    MeetingModel meeting,
    AttendanceProvider attProvider,
    Color primaryColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
  ) {
    final bool isOnline = meeting.isOnline;
    final bool isInMeeting = meeting.isInMeeting;
    final bool isCompleted = meeting.isCompleted;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isInMeeting
              ? Colors.orange
              : isCompleted
                  ? Colors.green.shade300
                  : Colors.grey.shade300,
          width: isInMeeting ? 1.5 : 1.0,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: isOnline ? Colors.blue.withOpacity(0.12) : Colors.green.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Row(
                  children: [
                    Icon(
                      isOnline ? Icons.videocam : Icons.location_on,
                      size: 12,
                      color: isOnline ? Colors.blue : Colors.green,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      isOnline ? 'Online' : 'Offline',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: isOnline ? Colors.blue : Colors.green,
                      ),
                    ),
                  ],
                ),
              ),
              if (isInMeeting)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.radio_button_checked, size: 10, color: Colors.orange),
                      SizedBox(width: 4),
                      Text('Sedang Berlangsung', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.orange)),
                    ],
                  ),
                )
              else if (isCompleted)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.check_circle, size: 10, color: Colors.green),
                      SizedBox(width: 4),
                      Text('Selesai (Hadir)', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.green)),
                    ],
                  ),
                ),
            ],
          ),
          const SizedBox(height: 8),
          Text(meeting.title, style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor)),
          const SizedBox(height: 4),
          Row(
            children: [
              Icon(Icons.access_time, size: 12, color: subtitleColor),
              const SizedBox(width: 4),
              Text(
                '${meeting.startTime} ${meeting.endTime != null ? '- ${meeting.endTime}' : ''}',
                style: TextStyle(fontSize: 11, color: subtitleColor, fontWeight: FontWeight.w600),
              ),
              const SizedBox(width: 12),
              Icon(isOnline ? Icons.link : Icons.place, size: 12, color: subtitleColor),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  isOnline ? (meeting.meetingLink ?? 'Link Online') : (meeting.locationName ?? '-'),
                  style: TextStyle(fontSize: 11, color: subtitleColor),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // Action Buttons
          if (isInMeeting) ...[
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => MeetingReportScreen(meeting: meeting),
                    ),
                  ).then((_) => attProvider.loadDashboardData());
                },
                icon: const Icon(Icons.assignment, size: 14, color: Colors.white),
                label: const Text('Buka Laporan Meeting', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Colors.white)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.orange.shade700,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
              ),
            ),
          ] else if (isCompleted) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
              decoration: BoxDecoration(
                color: Colors.green.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.green.shade300),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.check_circle, size: 15, color: Colors.green.shade700),
                  const SizedBox(width: 6),
                  Text(
                    'Selesai (Hadir)',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.green.shade700),
                  ),
                ],
              ),
            ),
          ] else ...[
            Row(
              children: [
                if (isOnline && meeting.meetingLink != null && meeting.meetingLink!.isNotEmpty) ...[
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final uri = Uri.parse(meeting.meetingLink!.startsWith('http') ? meeting.meetingLink! : 'https://${meeting.meetingLink}');
                        if (await canLaunchUrl(uri)) {
                          await launchUrl(uri, mode: LaunchMode.externalApplication);
                        }
                      },
                      icon: const Icon(Icons.open_in_new, size: 13, color: Colors.blue),
                      label: const Text('Link', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.blue)),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        side: const BorderSide(color: Colors.blue),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                ],
                Expanded(
                  flex: 2,
                  child: Builder(builder: (context) {
                    // Check if meeting time has already passed
                    bool isMeetingExpired = false;
                    try {
                      final now = DateTime.now();
                      final endTimeStr = meeting.endTime ?? meeting.startTime;
                      final parts = endTimeStr.split(':');
                      if (parts.length >= 2) {
                        final meetingEnd = DateTime(
                          now.year, now.month, now.day,
                          int.parse(parts[0]),
                          int.parse(parts[1]),
                        );
                        isMeetingExpired = now.isAfter(meetingEnd.add(const Duration(minutes: 30)));
                      }
                    } catch (_) {}

                    if (isMeetingExpired) {
                      return Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 12),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.grey.shade300),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.timer_off, size: 14, color: Colors.grey.shade500),
                            const SizedBox(width: 6),
                            Text(
                              'Waktu Meeting Telah Lewat',
                              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Colors.grey.shade500),
                            ),
                          ],
                        ),
                      );
                    }

                    final auth = Provider.of<AuthProvider>(context, listen: false);
                    final bool isFaceBlockedForMeet = _isFaceBlocked(auth);

                    if (isFaceBlockedForMeet) {
                      return ElevatedButton.icon(
                        onPressed: () => _checkFaceMasterOrBlock(context),
                        icon: const Icon(Icons.face_retouching_off, size: 14, color: Colors.white70),
                        label: const Text('Meet-In (Wajib Master)', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white70)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.grey.shade500,
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      );
                    }

                    return ElevatedButton.icon(
                      onPressed: () {
                        if (!attProvider.isCheckedIn) {
                          toastification.show(
                            context: context,
                            type: ToastificationType.warning,
                            title: const Text('Check-in Terlebih Dahulu'),
                            description: const Text('Silakan lakukan Check-in masuk kerja sebelum memulai Meet-In.'),
                            autoCloseDuration: const Duration(seconds: 3),
                          );
                          return;
                        }

                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => AttendanceLocationScreen(
                              type: 'meet_in',
                              meeting: meeting,
                            ),
                          ),
                        ).then((_) => attProvider.loadDashboardData());
                      },
                      icon: const Icon(Icons.login, size: 14, color: Colors.white),
                      label: const Text('Meet-In', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Colors.white)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.green.shade600,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    );
                  }),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

