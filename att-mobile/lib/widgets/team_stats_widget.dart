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
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF64748B);
    final borderColor = isDarkMode ? Colors.white.withOpacity(0.06) : const Color(0xFFE2E8F0);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 0.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('Team Overview', style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: textColor)),
            ],
          ),
          const SizedBox(height: 10),
          GridView.count(
            crossAxisCount: 2,
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
            shrinkWrap: true,
            childAspectRatio: 1.85,
            physics: const NeverScrollableScrollPhysics(),
            children: [
              _buildGridCard(
                'Total Team',
                dashboardProvider.totalTeam.toString(),
                Icons.people_alt_rounded,
                primaryColor,
                cardColor,
                textColor,
                subtitleColor,
                borderColor,
                isDarkMode,
              ),
              _buildGridCard(
                'Hadir Hari Ini',
                dashboardProvider.hadirHariIni.toString(),
                Icons.check_circle_rounded,
                const Color(0xFF10B981),
                cardColor,
                textColor,
                subtitleColor,
                borderColor,
                isDarkMode,
              ),
              _buildGridCard(
                'Sakit / Cuti',
                (dashboardProvider.sakitHariIni + dashboardProvider.cutiHariIni).toString(),
                Icons.healing_rounded,
                const Color(0xFFF59E0B),
                cardColor,
                textColor,
                subtitleColor,
                borderColor,
                isDarkMode,
              ),
              _buildGridCard(
                'Tim Belum Check-In',
                dashboardProvider.vacant.toString(),
                Icons.warning_amber_rounded,
                const Color(0xFFEF4444),
                cardColor,
                textColor,
                subtitleColor,
                borderColor,
                isDarkMode,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const TeamUncheckedScreen()),
                  );
                },
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: borderColor),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(isDarkMode ? 0.2 : 0.03),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(Icons.flag_rounded, size: 20, color: primaryColor),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Total Team Target (Bulan Ini)',
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w500, color: subtitleColor),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${dashboardProvider.teamTargetMandays} Mandays',
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: textColor),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        'Running Rate',
                        style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: primaryColor),
                      ),
                      Text(
                        '${dashboardProvider.teamRunningRate}%',
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: primaryColor),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGridCard(
    String title,
    String value,
    IconData icon,
    Color color,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color borderColor,
    bool isDarkMode, {
    VoidCallback? onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: borderColor),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(isDarkMode ? 0.2 : 0.03),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(5),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(7),
                    ),
                    child: Icon(icon, size: 14, color: color),
                  ),
                  const SizedBox(width: 7),
                  Expanded(
                    child: Text(
                      title,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: subtitleColor,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (onTap != null)
                    Icon(Icons.chevron_right_rounded, size: 14, color: subtitleColor.withOpacity(0.6)),
                ],
              ),
              const SizedBox(height: 6),
              Padding(
                padding: const EdgeInsets.only(left: 2),
                child: Text(
                  value,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: textColor,
                    letterSpacing: -0.5,
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
