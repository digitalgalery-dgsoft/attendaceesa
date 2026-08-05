import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/dashboard_provider.dart';
import '../providers/auth_provider.dart';

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
          Text('Team Overview (TL)', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
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
              _buildGridCard('Vacant (Kosong)', dashboardProvider.vacant.toString(), Icons.warning, Colors.redAccent, cardColor, textColor, onTap: () {
                if (dashboardProvider.vacantDetails.isNotEmpty) {
                  _showVacantDetails(context, dashboardProvider.vacantDetails, isDarkMode, primaryColor);
                } else if (dashboardProvider.vacant > 0) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Detail vacant belum tersedia, silakan refresh halaman.')),
                  );
                }
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

  void _showVacantDetails(BuildContext context, List<dynamic> details, bool isDarkMode, Color primaryColor) {
    showModalBottomSheet(
      context: context,
      backgroundColor: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade400,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Text(
                'Detail Vacant (Kosong)',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: isDarkMode ? Colors.white : const Color(0xFF111C2D),
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: details.length,
                  itemBuilder: (context, index) {
                    final item = details[index];
                    final name = item['name'] ?? 'Unknown';
                    final days = item['days'] ?? -1;
                    
                    String daysStr = '$days hari tidak hadir';
                    if (days == -1) {
                      daysStr = 'Belum pernah hadir';
                    }

                    return ListTile(
                      leading: CircleAvatar(
                        backgroundColor: primaryColor.withValues(alpha: 0.2),
                        child: Icon(Icons.person, color: primaryColor),
                      ),
                      title: Text(
                        name,
                        style: TextStyle(
                          color: isDarkMode ? Colors.white : Colors.black87,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      subtitle: Text(
                        daysStr,
                        style: TextStyle(
                          color: Colors.redAccent.shade200,
                        ),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
