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
import 'package:att_mobile/screens/blast_info_screen.dart';
import 'package:att_mobile/screens/sales_pipeline_screen.dart';

class DashboardScreen extends StatefulWidget {
  final Function(int)? switchTab;
  
  const DashboardScreen({super.key, this.switchTab});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late Timer _timer;
  DateTime _currentTime = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<AttendanceProvider>(context, listen: false).loadDashboardData();
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      Provider.of<NotificationProvider>(context, listen: false).fetchNotifications(authProvider);
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          _currentTime = DateTime.now();
        });
      }
    });
  }

  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final attProvider = Provider.of<AttendanceProvider>(context);
    final notificationProvider = Provider.of<NotificationProvider>(context);

    if (authProvider.employeeData == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final employeeName = authProvider.employeeData?['full_name'] ?? 'User';
    final positionName = authProvider.employeeData?['position']?['name'] ?? '-';
    final branchName = authProvider.employeeData?['branch']?['name'] ?? '-';
    final email = authProvider.employeeData?['email'] ?? '-'; 
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA); // Use admin color with fallback
    final hasSalesReporting = authProvider.employeeData?['department']?['has_sales_reporting'] == 1 || authProvider.employeeData?['department']?['has_sales_reporting'] == true;

    // Determine current checkin status from todayLogs
    String checkinTime = '-';
    String checkoutTime = '-';
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
      
      // Calculate duration if checkin exists
      if (checkinLog != null) {
        try {
          DateTime cin = DateTime.parse('${checkinLog['logged_at']}Z');
          DateTime cout = checkoutLog != null ? DateTime.parse('${checkoutLog['logged_at']}Z') : DateTime.now();
          Duration diff = cout.difference(cin);
          String hours = diff.inHours.toString().padLeft(2, '0');
          String minutes = (diff.inMinutes % 60).toString().padLeft(2, '0');
          String seconds = (diff.inSeconds % 60).toString().padLeft(2, '0');
          duration = '$hours:$minutes:$seconds';
        } catch (_) {}
      }
    }

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF3F4F6);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey;

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Header and Grid
            Stack(
              clipBehavior: Clip.none,
              children: [
                // Curved Background
                Container(
                  width: double.infinity,
                  height: 220,
                  decoration: BoxDecoration(
                    color: primaryColor,
                    borderRadius: const BorderRadius.only(
                      bottomLeft: Radius.circular(30),
                      bottomRight: Radius.circular(30),
                    ),
                  ),
                  child: SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 20.0, vertical: 10),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            radius: 25,
                            backgroundImage: (authProvider.employeeData?['photo'] != null && authProvider.employeeData!['photo'].toString().isNotEmpty)
                                ? NetworkImage(Constants.getImageUrl(authProvider.employeeData!['photo']))
                                : NetworkImage('https://ui-avatars.com/api/?name=${Uri.encodeComponent(employeeName)}&background=random') as ImageProvider,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  employeeName.toUpperCase(),
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                                  maxLines: 1, overflow: TextOverflow.ellipsis,
                                ),
                                Text(
                                  '$positionName - $branchName'.toUpperCase(),
                                  style: const TextStyle(color: Colors.white70, fontSize: 12),
                                  maxLines: 1, overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  email,
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13),
                                  maxLines: 1, overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                          Stack(
                            clipBehavior: Clip.none,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.notifications_outlined, color: Colors.white, size: 28),
                                onPressed: () {
                                  Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationScreen()));
                                },
                              ),
                              if (notificationProvider.unreadCount > 0)
                                Positioned(
                                  right: 8,
                                  top: 8,
                                  child: Container(
                                    padding: const EdgeInsets.all(4),
                                    decoration: const BoxDecoration(
                                      color: Colors.red,
                                      shape: BoxShape.circle,
                                    ),
                                    child: Text(
                                      notificationProvider.unreadCount.toString(),
                                      style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                DateFormat('HH:mm:ss').format(_currentTime),
                                style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                DateFormat('dd MMM yyyy').format(_currentTime),
                                style: const TextStyle(color: Colors.white70, fontSize: 12),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                // Floating Menu Card
                Padding(
                  padding: const EdgeInsets.only(top: 130, left: 16, right: 16),
                  child: Card(
                    color: cardColor,
                    elevation: 4,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildMenuItem('Attendance', Icons.calendar_today, Colors.blue, () {
                            if (widget.switchTab != null) {
                              widget.switchTab!(1);
                            } else {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const HistoryScreen()));
                            }
                          }),
                          _buildMenuItem('Itinerary', Icons.map, Colors.indigo, () {
                            Navigator.push(context, MaterialPageRoute(builder: (_) => const ItineraryScreen()));
                          }),
                          _buildMenuItem('Permit', Icons.event_note, Colors.orange, () {
                            if (widget.switchTab != null) {
                              widget.switchTab!(2);
                            } else {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const PermitScreen()));
                            }
                          }),
                          _buildMenuItem('Informasi', Icons.campaign, Colors.teal, () {
                            Navigator.push(context, MaterialPageRoute(builder: (_) => const BlastInfoScreen()));
                          }),
                          // _buildMenuItem('Overtime', Icons.access_time, Colors.red, () {
                          //   Navigator.push(context, MaterialPageRoute(builder: (_) => const ComingSoonScreen(title: 'Overtime')));
                          // }),
                          if (hasSalesReporting) ...[
                            _buildMenuItem('Sales', Icons.trending_up, Colors.purple, () {
                              if (widget.switchTab != null) {
                                widget.switchTab!(3);
                              }
                            }),
                            _buildMenuItem('Pipeline', Icons.pie_chart, Colors.cyan, () {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const SalesPipelineScreen()));
                            }),
                          ],
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 20),
            
            // Informasi Section
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Informasi', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
                  const SizedBox(height: 12),
                  Card(
                    color: cardColor,
                    elevation: 2,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.store, color: primaryColor, size: 28),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      branchName.toUpperCase(),
                                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                                    ),
                                    Text('INHOUSE', style: TextStyle(fontSize: 12, color: subtitleColor)),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(color: Colors.green.withOpacity(0.2), borderRadius: BorderRadius.circular(4)),
                                child: const Text('Online', style: TextStyle(color: Colors.green, fontSize: 10, fontWeight: FontWeight.bold)),
                              ),
                            ],
                          ),
                          const Divider(height: 24),
                          Row(
                            children: [
                              const Icon(Icons.access_time, color: Colors.green, size: 20),
                              const SizedBox(width: 8),
                              const Text('CHECK IN:', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                              const SizedBox(width: 8),
                              Text('$checkinTime WIB', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
                              const Spacer(),
                              Text('Duration : $duration', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
                            ],
                          ),
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            height: 50,
                            child: attProvider.isCheckedIn
                                ? ElevatedButton.icon(
                                    onPressed: attProvider.isVisiting ? null : () {
                                      Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'checkout')));
                                    },
                                    icon: const Icon(Icons.arrow_forward),
                                    label: const Text('Checkout >>', style: TextStyle(fontSize: 16)),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.red,
                                      disabledBackgroundColor: Colors.grey.shade400,
                                      disabledForegroundColor: Colors.white70,
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
                                    ),
                                  )
                                : ElevatedButton.icon(
                                    onPressed: (attProvider.hasCheckedOutToday || !attProvider.canCheckin) ? null : () {
                                      if (!attProvider.canCheckin) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(
                                            content: Text(attProvider.checkinBlockMessage),
                                            backgroundColor: Colors.red,
                                          ),
                                        );
                                        return;
                                      }
                                      Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'checkin')));
                                    },
                                    icon: const Icon(Icons.login),
                                    label: Text(
                                      attProvider.hasCheckedOutToday
                                        ? 'Sudah Selesai'
                                        : !attProvider.canCheckin
                                          ? 'Tidak Ada Jadwal'
                                          : 'Checkin >>',
                                      style: const TextStyle(fontSize: 16),
                                    ),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: !attProvider.canCheckin ? Colors.grey.shade400 : primaryColor,
                                      disabledBackgroundColor: Colors.grey.shade400,
                                      disabledForegroundColor: Colors.white70,
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
                                    ),
                                  ),
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: (attProvider.isCheckedIn && !attProvider.isVisiting && attProvider.canVisit) ? () {
                                    Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'visit_in')));
                                  } : null,
                                  icon: const Icon(Icons.transfer_within_a_station, size: 18),
                                  label: const Text('Visit In', style: TextStyle(fontSize: 13)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.teal,
                                    disabledBackgroundColor: Colors.grey.shade300,
                                    disabledForegroundColor: Colors.grey.shade600,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
                                    padding: const EdgeInsets.symmetric(vertical: 12),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: (!attProvider.isVisiting || !attProvider.canVisit) ? null : () {
                                    Navigator.push(context, MaterialPageRoute(builder: (_) => const AttendanceLocationScreen(type: 'visit_out')));
                                  },
                                  icon: const Icon(Icons.directions_run, size: 18),
                                  label: const Text('Visit Out', style: TextStyle(fontSize: 13)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.orange,
                                    disabledBackgroundColor: Colors.grey.shade300,
                                    disabledForegroundColor: Colors.grey.shade600,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
                                    padding: const EdgeInsets.symmetric(vertical: 12),
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
            ),

            const SizedBox(height: 20),



            // Aktivitas
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Aktivitas', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
                  const SizedBox(height: 12),
                  Card(
                    color: cardColor,
                    elevation: 2,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Stack(
                                alignment: Alignment.center,
                                children: [
                                  SizedBox(
                                    width: 60, height: 60,
                                    child: CircularProgressIndicator(value: attProvider.isCheckedIn ? 0.5 : 1.0, color: primaryColor, backgroundColor: Colors.grey.shade300, strokeWidth: 6),
                                  ),
                                  Text('100%', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: textColor)),
                                ],
                              ),
                              const SizedBox(width: 20),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text('1 dari 1 -', style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold, fontSize: 16)),
                                    const SizedBox(height: 8),
                                    Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(color: Colors.blueAccent, borderRadius: BorderRadius.circular(6)),
                                          child: Text('IN: $checkinTime', style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                                        ),
                                        const SizedBox(width: 8),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(color: Colors.redAccent, borderRadius: BorderRadius.circular(6)),
                                          child: Text('OUT: $checkoutTime', style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const Divider(height: 30),
                          // Timeline
                          if (logs.isEmpty)
                            const Center(child: Text('No activity today', style: TextStyle(color: Colors.grey)))
                          else
                            ListView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: logs.length,
                              itemBuilder: (context, index) {
                                final log = logs[index];
                                final timeStr = _extractTime(log['logged_at']);
                                final typeStr = log['log_type'].toString().toUpperCase();
                                final locationName = (log['visit_location'] != null && log['visit_location']['name'] != null)
                                    ? log['visit_location']['name']
                                    : branchName;
                                final Color dotColor = _getLogColor(log['log_type']);
                                
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 16.0),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      SizedBox(width: 40, child: Text(timeStr, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: textColor))),
                                      Column(
                                        children: [
                                          Container(width: 10, height: 10, decoration: BoxDecoration(color: dotColor, shape: BoxShape.circle)),
                                          if (index != logs.length - 1)
                                            Container(width: 2, height: 40, color: Colors.grey.shade300),
                                        ],
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(locationName.toString().toUpperCase(), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: textColor)),
                                            Text('$typeStr RECORDED', style: TextStyle(color: subtitleColor, fontSize: 11)),
                                          ],
                                        ),
                                      ),
                                      const Text('ONLINE', style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 10)),
                                    ],
                                  ),
                                );
                              },
                            ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuItem(String title, IconData icon, Color color, VoidCallback onTap) {
    return Expanded(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(8),
          splashColor: color.withValues(alpha: 0.2),
          highlightColor: color.withValues(alpha: 0.1),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 8.0),
            child: Column(
              children: [
                Container(
                  width: 45, height: 45,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: color, size: 24),
                ),
                const SizedBox(height: 8),
                Text(
                  title, 
                  textAlign: TextAlign.center, 
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Theme.of(context).brightness == Brightness.dark ? Colors.white : const Color(0xFF111C2D)), 
                  maxLines: 1, 
                  overflow: TextOverflow.ellipsis
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _extractTime(String? datetimeStr) {
    if (datetimeStr == null || datetimeStr.isEmpty) return '-';
    try {
      final dt = DateTime.parse('${datetimeStr}Z').toLocal();
      return DateFormat('HH:mm').format(dt);
    } catch (_) {
      return '-';
    }
  }

  Color _getLogColor(String logType) {
    switch (logType.toLowerCase()) {
      case 'checkin':
        return Colors.green;
      case 'checkout':
        return Colors.red;
      case 'visit_in':
        return Colors.teal;
      case 'visit_out':
        return Colors.orange;
      default:
        return Colors.blue;
    }
  }
}
