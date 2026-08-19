import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/dashboard_provider.dart';
import '../providers/auth_provider.dart';
import '../screens/team_unchecked_screen.dart';

class TeamStatsWidget extends StatelessWidget {
  const TeamStatsWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final dashboardProvider = Provider.of<DashboardProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 0.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Team Overview', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            shrinkWrap: true,
            childAspectRatio: 1.5,
            physics: const NeverScrollableScrollPhysics(),
            children: [
              _buildGridCard('Total Team', dashboardProvider.totalTeam.toString(), Icons.people, primaryColor, cardColor, textColor),
              _buildGridCard('Hadir Hari Ini', dashboardProvider.hadirHariIni.toString(), Icons.check_circle, Colors.green, cardColor, textColor),
              _buildGridCard('Sakit / Cuti', (dashboardProvider.sakitHariIni + dashboardProvider.cutiHariIni).toString(), Icons.local_hospital, Colors.orange, cardColor, textColor),
              _buildGridCard('Tim Belum Check-In', dashboardProvider.vacant.toString(), Icons.warning_amber_rounded, Colors.redAccent, cardColor, textColor, onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const TeamUncheckedScreen()),
                );
              }),
            ],
          ),
          const SizedBox(height: 12),
          Card(
            color: cardColor,
            elevation: 2,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Total Team Target (Bulan Ini)', style: TextStyle(fontSize: 12, color: isDarkMode ? Colors.grey.shade400 : Colors.grey)),
                        const SizedBox(height: 4),
                        Text('${dashboardProvider.teamTargetMandays} Mandays', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Column(
                      children: [
                        Text('Running Rate', style: TextStyle(fontSize: 10, color: primaryColor)),
                        Text('${dashboardProvider.teamRunningRate}%', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: primaryColor)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildGridCard(String title, String value, IconData icon, Color color, Color cardColor, Color textColor, {VoidCallback? onTap}) {
    return Card(
      color: cardColor,
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(icon, size: 18, color: color),
                  const SizedBox(width: 8),
                  Expanded(child: Text(title, style: TextStyle(fontSize: 11, color: textColor), maxLines: 1, overflow: TextOverflow.ellipsis)),
                ],
              ),
              const Spacer(),
              Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: textColor)),
            ],
          ),
        ),
      ),
    );
  }
}
