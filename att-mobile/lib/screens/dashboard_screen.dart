import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/screens/attendance_location_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

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
      Provider.of<AttendanceProvider>(context, listen: false).checkAttendanceStatus();
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
    final auth = Provider.of<AuthProvider>(context);
    final attProvider = Provider.of<AttendanceProvider>(context);
    final employeeName = auth.employeeData?['full_name'] ?? auth.user?['name'] ?? 'User';
    final branchName = auth.employeeData?['branch']?['name'] ?? 'Unknown Branch';

    return Scaffold(
      backgroundColor: const Color(0xFFF9F9FF),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFFE0E0E0)),
                image: const DecorationImage(
                  image: NetworkImage('https://ui-avatars.com/api/?name=User&background=7367F0&color=fff'),
                  fit: BoxFit.cover,
                ),
              ),
            ),
            const SizedBox(width: 12),
            const Text(
              'Attendance App',
              style: TextStyle(
                color: Color(0xFF7367F0),
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none, color: Color(0xFF6E6B7B)),
            onPressed: () {},
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Welcome Section
            Text(
              'Hello, $employeeName',
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF111C2D)),
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.location_on, size: 16, color: Color(0xFFEA5455)),
                const SizedBox(width: 4),
                Text(
                  branchName,
                  style: const TextStyle(color: Color(0xFF6E6B7B), fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 24),
            
            // Check In Section
            Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Text(
                  DateFormat('EEEE, MMMM d, yyyy').format(_currentTime),
                  style: const TextStyle(
                    color: Color(0xFF6E6B7B),
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  DateFormat('h:mm a').format(_currentTime),
                  style: const TextStyle(
                    color: Color(0xFF111C2D),
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 16),
                if (!attProvider.isCheckedIn)
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (context) => const AttendanceLocationScreen(type: 'checkin')),
                        );
                      },
                      icon: const Icon(Icons.fingerprint, size: 24),
                      label: const Text('Check In', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F52BA),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                      ),
                    ),
                  )
                else if (attProvider.isCheckedIn && !attProvider.isVisiting)
                  Row(
                    children: [
                      Expanded(
                        child: SizedBox(
                          height: 54,
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (context) => const AttendanceLocationScreen(type: 'visit_in')),
                              );
                            },
                            icon: const Icon(Icons.store, size: 20),
                            label: const Text('Visit In', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.orange,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: SizedBox(
                          height: 54,
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (context) => const AttendanceLocationScreen(type: 'checkout')),
                              );
                            },
                            icon: const Icon(Icons.exit_to_app, size: 20),
                            label: const Text('Check Out', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                            ),
                          ),
                        ),
                      ),
                    ],
                  )
                else if (attProvider.isVisiting)
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(builder: (context) => const AttendanceLocationScreen(type: 'visit_out')),
                        );
                      },
                      icon: const Icon(Icons.assignment_turned_in, size: 24),
                      label: const Text('Visit Out', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.teal,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                      ),
                    ),
                  ),
                const SizedBox(height: 8),
                const Text(
                  'Your location is being recorded for accuracy.',
                  style: TextStyle(
                    color: Color(0xFF6E6B7B),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            
            // Quick Stats
            Row(
              children: [
                Expanded(child: _buildQuickStat('Present', '18 Days', Icons.check_circle_outline, const Color(0xFF28C76F))),
                const SizedBox(width: 16),
                Expanded(child: _buildQuickStat('Late', '2 Days', Icons.access_time, const Color(0xFFFF9F43))),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickStat(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE0E0E0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(height: 12),
          Text(title, style: const TextStyle(color: Color(0xFF6E6B7B), fontSize: 14)),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF111C2D))),
        ],
      ),
    );
  }
}
