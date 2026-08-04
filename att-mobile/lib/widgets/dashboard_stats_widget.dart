import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/dashboard_provider.dart';
import '../providers/auth_provider.dart';

class DashboardStatsWidget extends StatelessWidget {
  const DashboardStatsWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final dashboardProvider = Provider.of<DashboardProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Target & Performa (${dashboardProvider.position})', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
          const SizedBox(height: 12),
          Card(
            color: cardColor,
            elevation: 2,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                children: [
                  // Circular Progress for Running Rate
                  Stack(
                    alignment: Alignment.center,
                    children: [
                      SizedBox(
                        width: 70, height: 70,
                        child: CircularProgressIndicator(
                          value: dashboardProvider.targetHk > 0 ? (dashboardProvider.kehadiran / dashboardProvider.targetHk) : 0,
                          color: primaryColor,
                          backgroundColor: Colors.grey.shade300,
                          strokeWidth: 8,
                        ),
                      ),
                      Text('${dashboardProvider.runningRate}%', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor)),
                    ],
                  ),
                  const SizedBox(width: 20),
                  // Details
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildStatRow('Kehadiran', '${dashboardProvider.kehadiran} / ${dashboardProvider.targetHk} HK', Colors.green),
                        const SizedBox(height: 8),
                        _buildStatRow('Sakit', '${dashboardProvider.sakit} Hari', Colors.orange),
                        const SizedBox(height: 8),
                        _buildStatRow('Cuti/Izin', '${dashboardProvider.cuti} Hari', Colors.redAccent),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatRow(String label, String value, Color color) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Container(
              width: 10,
              height: 10,
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            ),
            const SizedBox(width: 8),
            Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500)),
          ],
        ),
        Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
      ],
    );
  }
}
