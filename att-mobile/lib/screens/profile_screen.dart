import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/theme_provider.dart';
import 'login_screen.dart';
import '../utils/constants.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final bytes = await image.readAsBytes();
      final result = await auth.updateProfile({}, imageBytes: bytes, imageFilename: image.name);
      if (result['success']) {
        toastification.show(
          context: context,
          title: const Text('Success'),
          description: Text(result['message']),
          type: ToastificationType.success,
          autoCloseDuration: const Duration(seconds: 3),
        );
      } else {
        toastification.show(
          context: context,
          title: const Text('Error'),
          description: Text(result['message']),
          type: ToastificationType.error,
          autoCloseDuration: const Duration(seconds: 3),
        );
      }
    }
  }

  void _showChangePasswordSheet() {
    final oldPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);

    showModalBottomSheet(
      context: context,
      backgroundColor: bgColor,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
          left: 20,
          right: 20,
          top: 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Change Password', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
            const SizedBox(height: 20),
            TextField(
              controller: oldPasswordController,
              obscureText: true,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                labelText: 'Current Password', 
                labelStyle: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: primaryColor, width: 1.5),
                  borderRadius: BorderRadius.circular(8),
                )
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: newPasswordController,
              obscureText: true,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                labelText: 'New Password', 
                labelStyle: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: primaryColor, width: 1.5),
                  borderRadius: BorderRadius.circular(8),
                )
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: confirmPasswordController,
              obscureText: true,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                labelText: 'Confirm New Password', 
                labelStyle: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: primaryColor, width: 1.5),
                  borderRadius: BorderRadius.circular(8),
                )
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () async {
                  if (newPasswordController.text != confirmPasswordController.text) {
                    toastification.show(
                      context: context,
                      title: const Text('Error'),
                      description: const Text('Passwords do not match'),
                      type: ToastificationType.error,
                      autoCloseDuration: const Duration(seconds: 3),
                    );
                    return;
                  }
                  
                  Navigator.pop(context); // Close sheet
                  
                  final auth = Provider.of<AuthProvider>(context, listen: false);
                  final result = await auth.updateProfile({
                    'current_password': oldPasswordController.text,
                    'password': newPasswordController.text,
                    'password_confirmation': confirmPasswordController.text,
                  });
                  
                  if (result['success']) {
                    toastification.show(
                      context: context,
                      title: const Text('Success'),
                      description: Text(result['message']),
                      type: ToastificationType.success,
                      autoCloseDuration: const Duration(seconds: 3),
                    );
                  } else {
                    toastification.show(
                      context: context,
                      title: const Text('Error'),
                      description: Text(result['message']),
                      type: ToastificationType.error,
                      autoCloseDuration: const Duration(seconds: 3),
                    );
                  }
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('Save Password', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  void _showLanguageSheet() {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    
    final List<String> languages = ['English (US)', 'Indonesian'];
    showModalBottomSheet(
      context: context,
      backgroundColor: bgColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Text('Select Language', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
          ),
          ...languages.map((lang) => ListTile(
            title: Text(lang, style: TextStyle(color: textColor)),
            onTap: () async {
              Navigator.pop(context);
              final auth = Provider.of<AuthProvider>(context, listen: false);
              final result = await auth.updateProfile({'language': lang});
              if (result['success']) {
                toastification.show(
                  context: context,
                  title: const Text('Success'),
                  description: const Text('Language updated'),
                  type: ToastificationType.success,
                  autoCloseDuration: const Duration(seconds: 3),
                );
              }
            },
          )),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  void _showTimeZoneSheet() {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    
    final List<String> timezones = ['Western Indonesia Time (WIB)', 'Central Indonesia Time (WITA)', 'Eastern Indonesia Time (WIT)'];
    showModalBottomSheet(
      context: context,
      backgroundColor: bgColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Text('Select Time Zone', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
          ),
          ...timezones.map((tz) => ListTile(
            title: Text(tz, style: TextStyle(color: textColor)),
            onTap: () async {
              Navigator.pop(context);
              final auth = Provider.of<AuthProvider>(context, listen: false);
              final result = await auth.updateProfile({'timezone': tz});
              if (result['success']) {
                toastification.show(
                  context: context,
                  title: const Text('Success'),
                  description: const Text('Time Zone updated'),
                  type: ToastificationType.success,
                  autoCloseDuration: const Duration(seconds: 3),
                );
              }
            },
          )),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF6E6B7B);
    
    final auth = Provider.of<AuthProvider>(context);
    final employeeName = auth.employeeData?['full_name'] ?? auth.user?['name'] ?? 'User';
    final branchName = auth.employeeData?['branch']?['name'] ?? 'Unknown Branch';
    final photo = auth.employeeData?['photo'];
    final language = auth.employeeData?['language'] ?? 'English (US)';
    final timezone = auth.employeeData?['timezone'] ?? 'Western Indonesia Time (WIB)';
    
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    String primaryHex = primaryColor.value.toRadixString(16).substring(2).toUpperCase();
    String imageUrl = 'https://ui-avatars.com/api/?name=$employeeName&background=$primaryHex&color=fff';
    if (photo != null && photo.toString().isNotEmpty) {
      imageUrl = Constants.getImageUrl(photo.toString());
    }

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(
          'Profile & Settings',
          style: TextStyle(
            color: textColor,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Profile Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
              ),
              child: Column(
                children: [
                  GestureDetector(
                    onTap: _pickImage,
                    child: Stack(
                      children: [
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            border: Border.all(color: isDarkMode ? Colors.grey.shade700 : const Color(0xFFE7EEFF), width: 3),
                            image: DecorationImage(
                              image: NetworkImage(imageUrl),
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: primaryColor,
                              shape: BoxShape.circle,
                              border: Border.all(color: cardColor, width: 2),
                            ),
                            child: const Icon(Icons.camera_alt, size: 14, color: Colors.white),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    employeeName,
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Employee',
                    style: TextStyle(color: subtitleColor, fontSize: 13),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: primaryColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.location_on, size: 14, color: primaryColor),
                        const SizedBox(width: 4),
                        Text(
                          branchName,
                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: primaryColor),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            
            // Notifications Settings
            _buildSettingsSection(
              icon: Icons.notifications_active,
              title: 'Notifications',
              cardColor: cardColor,
              textColor: textColor,
              isDarkMode: isDarkMode,
              primaryColor: primaryColor,
              children: [
                _buildToggleSetting('Shift Reminders', 'Get alerted before shift starts', true, textColor, subtitleColor, primaryColor),
                _buildToggleSetting('Leave Approvals', 'Updates on time-off requests', true, textColor, subtitleColor, primaryColor),
              ],
            ),
            const SizedBox(height: 24),
            
            // Account Security
            _buildSettingsSection(
              icon: Icons.security,
              title: 'Account Security',
              cardColor: cardColor,
              textColor: textColor,
              isDarkMode: isDarkMode,
              primaryColor: primaryColor,
              children: [
                _buildActionSetting(Icons.password, 'Change Password', textColor: textColor, subtitleColor: subtitleColor, isDarkMode: isDarkMode, onTap: _showChangePasswordSheet),
                _buildActionSetting(Icons.fingerprint, 'Biometric Login', value: 'Enabled', valueColor: primaryColor, textColor: textColor, subtitleColor: subtitleColor, isDarkMode: isDarkMode),
              ],
            ),
            const SizedBox(height: 24),
            
            // App Preferences
            _buildSettingsSection(
              icon: Icons.settings,
              title: 'App Preferences',
              cardColor: cardColor,
              textColor: textColor,
              isDarkMode: isDarkMode,
              primaryColor: primaryColor,
              children: [
                _buildDropdownSetting('Language', language, textColor, subtitleColor, isDarkMode, onTap: _showLanguageSheet),
                _buildDropdownSetting('Time Zone', timezone, textColor, subtitleColor, isDarkMode, onTap: _showTimeZoneSheet),
                _buildThemeToggleSetting(textColor, subtitleColor, primaryColor),
              ],
            ),
            const SizedBox(height: 32),
            
            // Logout Button
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                onPressed: () async {
                  await auth.logout();
                  if (context.mounted) {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (context) => const LoginScreen()),
                    );
                  }
                },
                icon: const Icon(Icons.logout),
                label: const Text('Log Out', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFFFDAD6),
                  foregroundColor: const Color(0xFF93000A),
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildSettingsSection({
    required IconData icon, 
    required String title, 
    required List<Widget> children,
    required Color cardColor,
    required Color textColor,
    required bool isDarkMode,
    required Color primaryColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: primaryColor, size: 20),
              const SizedBox(width: 8),
              Text(title, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
            ],
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Divider(color: isDarkMode ? Colors.grey.shade800 : const Color(0xFFE0E0E0)),
          ),
          ...children,
        ],
      ),
    );
  }

  Widget _buildToggleSetting(String title, String subtitle, bool value, Color textColor, Color subtitleColor, Color primaryColor) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
              const SizedBox(height: 2),
              Text(subtitle, style: TextStyle(fontSize: 12, color: subtitleColor)),
            ],
          ),
          Switch(
            value: value,
            onChanged: (v) {},
            activeColor: Colors.white,
            activeTrackColor: primaryColor,
          ),
        ],
      ),
    );
  }

  Widget _buildThemeToggleSetting(Color textColor, Color subtitleColor, Color primaryColor) {
    final themeProvider = Provider.of<ThemeProvider>(context);
    final isDark = themeProvider.themeMode == ThemeMode.dark || 
                   (themeProvider.themeMode == ThemeMode.system && 
                    MediaQuery.of(context).platformBrightness == Brightness.dark);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Dark Mode', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
              const SizedBox(height: 2),
              Text('Toggle dark mode theme', style: TextStyle(fontSize: 12, color: subtitleColor)),
            ],
          ),
          Switch(
            value: isDark,
            onChanged: (v) {
              themeProvider.toggleTheme(v);
            },
            activeColor: Colors.white,
            activeTrackColor: primaryColor,
          ),
        ],
      ),
    );
  }

  Widget _buildActionSetting(IconData icon, String title, {String? value, Color? valueColor, VoidCallback? onTap, required Color textColor, required Color subtitleColor, required bool isDarkMode}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Icon(icon, color: subtitleColor, size: 20),
                const SizedBox(width: 12),
                Text(title, style: TextStyle(color: textColor, fontSize: 14)),
              ],
            ),
            Row(
              children: [
                if (value != null)
                  Text(value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: valueColor ?? textColor)),
                if (value != null) const SizedBox(width: 8),
                Icon(Icons.chevron_right, color: subtitleColor, size: 18),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdownSetting(String label, String value, Color textColor, Color subtitleColor, bool isDarkMode, {VoidCallback? onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: subtitleColor)),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
              decoration: BoxDecoration(
                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(value, style: TextStyle(color: textColor, fontSize: 14)),
                  Icon(Icons.expand_more, color: subtitleColor, size: 18),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
