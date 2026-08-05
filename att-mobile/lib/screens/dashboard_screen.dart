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
import 'package:att_mobile/providers/notification_provider.dart';
import 'package:att_mobile/providers/dashboard_provider.dart';
import 'package:att_mobile/widgets/dashboard_stats_widget.dart';
import 'package:att_mobile/widgets/team_stats_widget.dart';
import 'package:att_mobile/screens/blast_info_screen.dart';
import 'package:att_mobile/screens/sales_pipeline_screen.dart';
import 'package:att_mobile/services/location_service.dart';
import 'package:att_mobile/screens/payslip_screen.dart';
import 'package:package_info_plus/package_info_plus.dart';

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
      if (positionName == 'TL') {
        dashboardProvider.fetchTeamStats();
      }
      _syncLocationService(attProvider);
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

    if (authProvider.employeeData == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final employeeName = authProvider.employeeData?['full_name'] ?? 'User';
    final positionName = authProvider.employeeData?['position']?['name'] ?? '-';
    final branchName = authProvider.employeeData?['branch']?['name'] ?? '-';
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final hasSalesReporting = authProvider.employeeData?['department']?['has_sales_reporting'] == 1 || authProvider.employeeData?['department']?['has_sales_reporting'] == true;

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

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 17, vertical: 13),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Header (home-head)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Selamat pagi,', style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.w500)),
                          const SizedBox(height: 3),
                          Text(employeeName, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
                          const SizedBox(height: 2),
                          Text('$positionName · $branchName', style: TextStyle(fontSize: 10.5, color: subtitleColor, fontWeight: FontWeight.w500)),
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

                // Time Card
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 13, horizontal: 16),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFF132247), Color(0xFF0E1830)],
                    ),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        DateFormat('HH:mm:ss').format(_currentTime),
                        style: const TextStyle(fontSize: 21, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'monospace'),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        DateFormat('EEEE, dd MMMM yyyy').format(_currentTime),
                        style: const TextStyle(fontSize: 10, color: Color(0xFFB9C3DD)),
                      ),
                      const SizedBox(height: 7),
                      Row(
                        children: [
                          const Icon(Icons.location_on, size: 10, color: Color(0xFF8FE3F5)),
                          const SizedBox(width: 5),
                          Text(
                            '$branchName · Terverifikasi',
                            style: const TextStyle(fontSize: 9, color: Color(0xFF8FE3F5), fontWeight: FontWeight.w600),
                          ),
                        ],
                      )
                    ],
                  ),
                ),

                const SizedBox(height: 11),

                // Menu Lainnya
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Menu Lainnya', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                  ],
                ),
                const SizedBox(height: 8),
                Builder(builder: (context) {
                  List<Map<String, dynamic>> allMenus = [
                    {'title': 'Absensi', 'icon': Icons.calendar_today, 'color': primaryColor, 'onTap': () {
                      if (widget.switchTab != null) { widget.switchTab!(1); }
                      else { Navigator.push(context, MaterialPageRoute(builder: (_) => const HistoryScreen())); }
                    }},
                    {'title': 'Itinerary', 'icon': Icons.map, 'color': const Color(0xFF0FA8C4), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const ItineraryScreen()));
                    }},
                    {'title': 'Permit', 'icon': Icons.event_note, 'color': const Color(0xFFD98A2B), 'onTap': () {
                      if (widget.switchTab != null) { widget.switchTab!(2); }
                      else { Navigator.push(context, MaterialPageRoute(builder: (_) => const PermitScreen())); }
                    }},
                    {'title': 'Informasi', 'icon': Icons.campaign, 'color': const Color(0xFF149A6E), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const BlastInfoScreen()));
                    }},
                    {'title': 'Payslip', 'icon': Icons.receipt_long, 'color': const Color(0xFF4A90E2), 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const PayslipScreen()));
                    }},
                  ];
                  if (hasSalesReporting) {
                    allMenus.add({'title': 'Sales', 'icon': Icons.trending_up, 'color': Colors.purple, 'onTap': () {
                      if (widget.switchTab != null) { widget.switchTab!(3); }
                    }});
                    allMenus.add({'title': 'Pipeline', 'icon': Icons.pie_chart, 'color': Colors.cyan, 'onTap': () {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const SalesPipelineScreen()));
                    }});
                  }

                  List<Widget> displayMenus = [];
                  if (allMenus.length > 5) {
                     for (int i = 0; i < 4; i++) {
                       displayMenus.add(_buildMenuQItem(allMenus[i]['title'], allMenus[i]['icon'], allMenus[i]['color'], allMenus[i]['onTap'], cardColor, textColor, false));
                     }
                     displayMenus.add(_buildMenuQItem('More', Icons.more_horiz, subtitleColor, () {
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
                if (dashboardProvider.totalTeam > 0 || positionName.toUpperCase() == 'TL') ...[
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
                        InkWell(
                          onTap: () {
                            if (attProvider.hasCheckedOutToday) return;
                            if (!attProvider.canCheckin && !attProvider.isCheckedIn) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(attProvider.checkinBlockMessage), backgroundColor: Colors.red),
                              );
                              return;
                            }
                            if (attProvider.isCheckedIn) {
                              if (attProvider.isVisiting) return;
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'checkout')));
                            } else {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'checkin')));
                            }
                          },
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            decoration: BoxDecoration(
                              gradient: attProvider.hasCheckedOutToday
                                  ? const LinearGradient(colors: [Colors.grey, Colors.grey])
                                  : (!attProvider.canCheckin && !attProvider.isCheckedIn)
                                      ? const LinearGradient(colors: [Colors.grey, Colors.grey])
                                      : LinearGradient(colors: [primaryColor, primaryColor.withBlue(primaryColor.blue > 200 ? 255 : primaryColor.blue + 50)]),
                              borderRadius: BorderRadius.circular(13),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  attProvider.hasCheckedOutToday ? Icons.done_all : (attProvider.isCheckedIn ? Icons.logout : Icons.login),
                                  color: Colors.white, size: 14
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  attProvider.hasCheckedOutToday ? 'Selesai Bekerja' : (attProvider.isCheckedIn ? 'Check-out Sekarang' : 'Check-in Sekarang'),
                                  style: const TextStyle(color: Colors.white, fontSize: 12.5, fontWeight: FontWeight.bold)
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.info_outline, size: 10, color: subtitleColor),
                            const SizedBox(width: 5),
                            Text(
                              attProvider.isCheckedIn ? 'Sedang bekerja sejak $checkinTime • Durasi: $duration' : (attProvider.hasCheckedOutToday ? 'Selesai bekerja • Durasi: $duration' : 'Belum check-in'),
                              style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)
                            ),
                          ],
                        )
                      ],
                    ),
                  );
                }),
                
                const SizedBox(height: 15),

                // Kunjungan Lapangan
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Kunjungan Lapangan', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        onTap: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'visit_in')));
                        } : null,
                        child: Container(
                          padding: const EdgeInsets.all(11),
                          decoration: BoxDecoration(
                            gradient: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit)
                                ? const LinearGradient(colors: [Color(0xFF14BEDB), Color(0xFF0FA8C4)])
                                : LinearGradient(colors: [cardColor, cardColor]),
                            border: Border.all(color: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? Colors.transparent : Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                width: 27, height: 27,
                                decoration: BoxDecoration(
                                  color: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? Colors.white.withOpacity(0.2) : elevatedColor,
                                  borderRadius: BorderRadius.circular(8)
                                ),
                                child: Icon(Icons.transfer_within_a_station, size: 14, color: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? Colors.white : subtitleColor),
                              ),
                              const SizedBox(height: 7),
                              Text('Visit-in', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? Colors.white : textColor)),
                              const SizedBox(height: 2),
                              Text('Mulai kunjungan', style: TextStyle(fontSize: 9, color: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? const Color(0xFFDBF7FC) : subtitleColor, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: InkWell(
                        onTap: (!attProvider.isVisiting || !attProvider.canVisit) ? null : () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'visit_out')));
                        },
                        child: Container(
                          padding: const EdgeInsets.all(11),
                          decoration: BoxDecoration(
                            color: cardColor,
                            border: Border.all(color: (!attProvider.isVisiting || !attProvider.canVisit) ? Colors.grey.shade300 : primaryColor),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                width: 27, height: 27,
                                decoration: BoxDecoration(
                                  color: elevatedColor,
                                  borderRadius: BorderRadius.circular(8)
                                ),
                                child: Icon(Icons.directions_run, size: 14, color: (!attProvider.isVisiting || !attProvider.canVisit) ? subtitleColor : primaryColor),
                              ),
                              const SizedBox(height: 7),
                              Text('Visit-out', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: (!attProvider.isVisiting || !attProvider.canVisit) ? textColor : primaryColor)),
                              const SizedBox(height: 2),
                              Text(!attProvider.isVisiting ? 'Belum ada visit' : 'Selesai kunjungan', style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 15),

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
                  const SizedBox(height: 2),
                  Text('Semua fitur tambahan Nexa Attendance', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
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
      default:
        return Colors.blue;
    }
  }
}
