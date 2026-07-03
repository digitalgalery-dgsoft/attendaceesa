import 'package:flutter/material.dart';

class HistoryScreen extends StatelessWidget {
  const HistoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F9FF),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Attendance Log',
          style: TextStyle(
            color: Color(0xFF111C2D),
            fontSize: 24,
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 16),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: const Color(0xFFE0E0E0)),
              borderRadius: BorderRadius.circular(8),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            child: Row(
              children: const [
                Icon(Icons.calendar_month, size: 18, color: Color(0xFF111C2D)),
                SizedBox(width: 8),
                Text('Oct 2023', style: TextStyle(color: Color(0xFF111C2D), fontWeight: FontWeight.w600, fontSize: 12)),
                SizedBox(width: 4),
                Icon(Icons.arrow_drop_down, size: 18, color: Color(0xFF111C2D)),
              ],
            ),
          )
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Stats Row
            Row(
              children: [
                Expanded(child: _buildStatCard('Days Present', '21', const Color(0xFF7367F0))),
                const SizedBox(width: 16),
                Expanded(child: _buildStatCard('Avg Hours', '8.2', const Color(0xFF111C2D))),
                const SizedBox(width: 16),
                Expanded(child: _buildStatCard('Late', '2', const Color(0xFFEA5455))),
              ],
            ),
            const SizedBox(height: 24),
            
            // Log Entries
            _buildLogEntry(
              date: 'Oct 24, Tue',
              status: 'On Time',
              statusColor: const Color(0xFF28C76F),
              checkIn: '08:55 AM',
              checkOut: '05:05 PM',
              duration: '8h 10m',
            ),
            const SizedBox(height: 16),
            _buildLogEntry(
              date: 'Oct 23, Mon',
              status: 'Late',
              statusColor: const Color(0xFFFF9F43),
              checkIn: '09:15 AM',
              checkOut: '05:30 PM',
              duration: '8h 15m',
            ),
            const SizedBox(height: 16),
            _buildLogEntry(
              date: 'Oct 20, Fri',
              status: 'On Time',
              statusColor: const Color(0xFF28C76F),
              checkIn: '08:58 AM',
              checkOut: '04:55 PM',
              duration: '7h 57m',
            ),
            const SizedBox(height: 16),
            _buildLogEntry(
              date: 'Oct 19, Thu',
              status: 'Absent',
              statusColor: const Color(0xFFEA5455),
              checkIn: '--:--',
              checkOut: '--:--',
              duration: '0h 0m',
              isAbsent: true,
            ),
            
            const SizedBox(height: 24),
            Center(
              child: TextButton(
                onPressed: () {},
                child: const Text('Load More', style: TextStyle(color: Color(0xFF7367F0), fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, String value, Color valueColor) {
    return Container(
      padding: const EdgeInsets.all(12),
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
          Text(
            title.toUpperCase(),
            style: const TextStyle(fontSize: 10, color: Color(0xFF6E6B7B), fontWeight: FontWeight.w600, letterSpacing: 0.5),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(fontSize: 18, color: valueColor, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildLogEntry({
    required String date,
    required String status,
    required Color statusColor,
    required String checkIn,
    required String checkOut,
    required String duration,
    bool isAbsent = false,
  }) {
    return Container(
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
      child: Opacity(
        opacity: isAbsent ? 0.6 : 1.0,
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                width: 4,
                decoration: BoxDecoration(
                  color: statusColor,
                  borderRadius: const BorderRadius.horizontal(left: Radius.circular(12)),
                ),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(date, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: statusColor.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Container(
                                        width: 6,
                                        height: 6,
                                        decoration: BoxDecoration(color: statusColor, shape: BoxShape.circle),
                                      ),
                                      const SizedBox(width: 4),
                                      Text(
                                        status,
                                        style: TextStyle(color: statusColor, fontSize: 12, fontWeight: FontWeight.w600),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text('CHECK IN', style: TextStyle(fontSize: 10, color: Color(0xFF6E6B7B), fontWeight: FontWeight.w600)),
                                      const SizedBox(height: 2),
                                      Row(
                                        children: [
                                          Icon(Icons.login, size: 16, color: isAbsent ? const Color(0xFF6E6B7B) : const Color(0xFF7367F0)),
                                          const SizedBox(width: 4),
                                          Text(checkIn, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text('CHECK OUT', style: TextStyle(fontSize: 10, color: Color(0xFF6E6B7B), fontWeight: FontWeight.w600)),
                                      const SizedBox(height: 2),
                                      Row(
                                        children: [
                                          const Icon(Icons.logout, size: 16, color: Color(0xFF6E6B7B)),
                                          const SizedBox(width: 4),
                                          Text(checkOut, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      Container(
                        width: 1,
                        margin: const EdgeInsets.symmetric(horizontal: 16),
                        color: const Color(0xFFE0E0E0),
                      ),
                      Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('DURATION', style: TextStyle(fontSize: 10, color: Color(0xFF6E6B7B), fontWeight: FontWeight.w600)),
                          const SizedBox(height: 4),
                          Text(duration, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
