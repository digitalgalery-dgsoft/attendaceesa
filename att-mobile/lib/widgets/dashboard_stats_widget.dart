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
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF6E6B7B);

    final kehadiran = dashboardProvider.kehadiran;
    final targetHk = dashboardProvider.targetHk;
    final runningRate = dashboardProvider.runningRate;
    final progressValue = targetHk > 0 ? (kehadiran / targetHk).clamp(0.0, 1.0) : 0.0;
    final hasTarget = targetHk > 0;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 0.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Target & Performa (${dashboardProvider.position})', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
          const SizedBox(height: 8),
          Container(
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
            ),
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
                          value: progressValue,
                          color: primaryColor,
                          backgroundColor: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade200,
                          strokeWidth: 7,
                        ),
                      ),
                      Text(
                        hasTarget ? '$runningRate%' : '${kehadiran}h',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: textColor),
                      ),
                    ],
                  ),
                  const SizedBox(width: 20),
                  // Details
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildStatRow('Kehadiran', '${dashboardProvider.kehadiran} / ${hasTarget ? targetHk : '?'} HK', Colors.green, textColor, subtitleColor),
                        const SizedBox(height: 8),
                        _buildStatRow('Sakit', '${dashboardProvider.sakit} Hari', Colors.orange, textColor, subtitleColor),
                        const SizedBox(height: 8),
                        _buildStatRow('Cuti/Izin', '${dashboardProvider.cuti} Hari', Colors.redAccent, textColor, subtitleColor),
                        if (!hasTarget) ...[
                          const SizedBox(height: 6),
                          Text('* Target HK belum diset', style: TextStyle(fontSize: 10, color: subtitleColor, fontStyle: FontStyle.italic)),
                        ],
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

  Widget _buildStatRow(String label, String value, Color color, Color textColor, Color subtitleColor) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Container(
              width: 9,
              height: 9,
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            ),
            const SizedBox(width: 7),
            Text(label, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w500, color: subtitleColor)),
          ],
        ),
        Text(value, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
      ],
    );
  }
}
