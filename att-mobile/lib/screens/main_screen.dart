import 'package:flutter/material.dart';
import 'package:att_mobile/screens/dashboard_screen.dart';
import 'package:att_mobile/screens/history_screen.dart';
import 'package:att_mobile/screens/permit_screen.dart';
import 'package:att_mobile/screens/profile_screen.dart';
import 'package:att_mobile/screens/sales_report_screen.dart';

import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';

import 'package:att_mobile/providers/blast_info_provider.dart';

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkBlastInfo();
    });
  }

  Future<void> _checkBlastInfo() async {
    final provider = Provider.of<BlastInfoProvider>(context, listen: false);
    await provider.fetchBlastInfos();
    final unread = await provider.getUnreadInfos();
    
    if (unread.isNotEmpty && mounted) {
      _showBlastInfoDialog(unread.first);
    }
  }

  void _showBlastInfoDialog(blastInfo) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        final auth = Provider.of<AuthProvider>(context, listen: false);
        final primaryColor = auth.appColor ?? const Color(0xFF7367F0);
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          title: Row(
            children: [
              Icon(Icons.campaign, color: primaryColor),
              const SizedBox(width: 8),
              Expanded(child: Text(blastInfo.title, style: const TextStyle(fontWeight: FontWeight.bold))),
            ],
          ),
          content: SingleChildScrollView(
            child: Text(blastInfo.content),
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: primaryColor),
              onPressed: () {
                Provider.of<BlastInfoProvider>(context, listen: false).markAsRead(blastInfo.id);
                Navigator.of(context).pop();
                
                // Show next if any
                _checkBlastInfo();
              },
              child: const Text('OK, Saya Mengerti'),
            ),
          ],
        );
      }
    );
  }

  List<Widget> _buildScreens(bool hasSales) {
    List<Widget> screens = [
      DashboardScreen(
        switchTab: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
      ),
      const HistoryScreen(),
      const PermitScreen(),
    ];
    if (hasSales) {
      screens.add(const SalesReportScreen());
    }
    screens.add(const ProfileScreen());
    return screens;
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF7367F0);
    final hasSalesReporting = auth.employeeData?['department']?['has_sales_reporting'] == 1 || auth.employeeData?['department']?['has_sales_reporting'] == true;

    final screens = _buildScreens(hasSalesReporting);
    
    // Ensure index doesn't go out of bounds if switching states
    if (_currentIndex >= screens.length) {
      _currentIndex = 0;
    }

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final navBgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final unselectedColor = isDarkMode ? Colors.grey.shade500 : const Color(0xFF6E6B7B);

    return Scaffold(
      body: screens[_currentIndex],
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, -5),
            ),
          ],
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) {
            setState(() {
              _currentIndex = index;
            });
          },
          type: BottomNavigationBarType.fixed,
          backgroundColor: navBgColor,
          selectedItemColor: primaryColor,
          unselectedItemColor: unselectedColor,
          selectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12),
          unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 12),
          items: [
            const BottomNavigationBarItem(
              icon: Icon(Icons.dashboard_outlined),
              activeIcon: Icon(Icons.dashboard),
              label: 'Dashboard',
            ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.history_outlined),
              activeIcon: Icon(Icons.history),
              label: 'History',
            ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.event_note_outlined),
              activeIcon: Icon(Icons.event_note),
              label: 'Permit',
            ),
            if (hasSalesReporting)
              const BottomNavigationBarItem(
                icon: Icon(Icons.trending_up_outlined),
                activeIcon: Icon(Icons.trending_up),
                label: 'Sales',
              ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person),
              label: 'Profile',
            ),
          ],
        ),
      ),
    );
  }
}
