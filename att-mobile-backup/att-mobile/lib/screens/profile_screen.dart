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

    showModalBottomSheet(
      context: context,
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
            const Text('Change Password', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 20),
            TextField(
              controller: oldPasswordController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Current Password', border: OutlineInputBorder()),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: newPasswordController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'New Password', border: OutlineInputBorder()),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: confirmPasswordController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Confirm New Password', border: OutlineInputBorder()),
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
                  backgroundColor: const Color(0xFF7367F0),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Save Password'),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  void _showLanguageSheet() {
    final List<String> languages = ['English (US)', 'Indonesian'];
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Padding(
            padding: EdgeInsets.all(20),
            child: Text('Select Language', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ),
          ...languages.map((lang) => ListTile(
            title: Text(lang),
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
    final List<String> timezones = ['Western Indonesia Time (WIB)', 'Central Indonesia Time (WITA)', 'Eastern Indonesia Time (WIT)'];
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Padding(
            padding: EdgeInsets.all(20),
            child: Text('Select Time Zone', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ),
          ...timezones.map((tz) => ListTile(
            title: Text(tz),
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
    final auth = Provider.of<AuthProvider>(context);
    final employeeName = auth.employeeData?['full_name'] ?? auth.user?['name'] ?? 'User';
    final branchName = auth.employeeData?['branch']?['name'] ?? 'Unknown Branch';
    final photo = auth.employeeData?['photo'];
    final language = auth.employeeData?['language'] ?? 'English (US)';
    final timezone = auth.employeeData?['timezone'] ?? 'Western Indonesia Time (WIB)';
    
    final primaryColor = auth.appColor ?? const Color(0xFF7367F0);
    String primaryHex = primaryColor.value.toRadixString(16).substring(2).toUpperCase();
    String imageUrl = 'https://ui-avatars.com/api/?name=$employeeName&background=$primaryHex&color=fff';
    if (photo != null && photo.toString().isNotEmpty) {
      imageUrl = Constants.getImageUrl(photo.toString());
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Profile & Settings',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
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
                            border: Border.all(color: const Color(0xFFE7EEFF), width: 3),
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
                              border: Border.all(color: Colors.white, width: 2),
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
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF111C2D)),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Employee',
                    style: TextStyle(color: Color(0xFF6E6B7B)),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF0F3FF),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.location_on, size: 16, color: Color(0xFF6E6B7B)),
                        const SizedBox(width: 4),
                        Text(
                          branchName,
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF6E6B7B)),
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
              children: [
                _buildToggleSetting('Shift Reminders', 'Get alerted before shift starts', true),
                _buildToggleSetting('Leave Approvals', 'Updates on time-off requests', true),
              ],
            ),
            const SizedBox(height: 24),
            
            // Account Security
            _buildSettingsSection(
              icon: Icons.security,
              title: 'Account Security',
              children: [
                _buildActionSetting(Icons.password, 'Change Password', onTap: _showChangePasswordSheet),
                _buildActionSetting(Icons.fingerprint, 'Biometric Login', value: 'Enabled', valueColor: primaryColor),
              ],
            ),
            const SizedBox(height: 24),
            
            // App Preferences
            _buildSettingsSection(
              icon: Icons.settings,
              title: 'App Preferences',
              children: [
                _buildDropdownSetting('Language', language, onTap: _showLanguageSheet),
                _buildDropdownSetting('Time Zone', timezone, onTap: _showTimeZoneSheet),
                _buildThemeToggleSetting(),
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
                label: const Text('Log Out', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFFFDAD6),
                  foregroundColor: const Color(0xFF93000A),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                    side: const BorderSide(color: Color(0x33BA1A1A)),
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

  Widget _buildSettingsSection({required IconData icon, required String title, required List<Widget> children}) {
    return Container(
      padding: const EdgeInsets.all(20),
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
          Row(
            children: [
              Icon(icon, color: Provider.of<AuthProvider>(context).appColor ?? const Color(0xFF7367F0), size: 20),
              const SizedBox(width: 8),
              Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF111C2D))),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(color: Color(0xFFE0E0E0)),
          ),
          ...children,
        ],
      ),
    );
  }

  Widget _buildToggleSetting(String title, String subtitle, bool value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF111C2D))),
              const SizedBox(height: 2),
              Text(subtitle, style: const TextStyle(fontSize: 12, color: Color(0xFF6E6B7B))),
            ],
          ),
          Switch(
            value: value,
            onChanged: (v) {},
            activeColor: Colors.white,
            activeTrackColor: Provider.of<AuthProvider>(context).appColor ?? const Color(0xFF0F52BA),
          ),
        ],
      ),
    );
  }

  Widget _buildThemeToggleSetting() {
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
              const Text('Dark Mode', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF111C2D))),
              const SizedBox(height: 2),
              const Text('Toggle dark mode theme', style: TextStyle(fontSize: 12, color: Color(0xFF6E6B7B))),
            ],
          ),
          Switch(
            value: isDark,
            onChanged: (v) {
              themeProvider.toggleTheme(v);
            },
            activeColor: Colors.white,
            activeTrackColor: Provider.of<AuthProvider>(context).appColor ?? const Color(0xFF0F52BA),
          ),
        ],
      ),
    );
  }

  Widget _buildActionSetting(IconData icon, String title, {String? value, Color? valueColor, VoidCallback? onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          border: Border.all(color: const Color(0xFFE0E0E0)),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Icon(icon, color: const Color(0xFF6E6B7B), size: 20),
                const SizedBox(width: 12),
                Text(title, style: const TextStyle(color: Color(0xFF111C2D))),
              ],
            ),
            Row(
              children: [
                if (value != null)
                  Text(value, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: valueColor ?? const Color(0xFF111C2D))),
                if (value != null) const SizedBox(width: 8),
                const Icon(Icons.chevron_right, color: Color(0xFF6E6B7B)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdownSetting(String label, String value, {VoidCallback? onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF6E6B7B))),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
              decoration: BoxDecoration(
                border: Border.all(color: const Color(0xFFE0E0E0)),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(value, style: const TextStyle(color: Color(0xFF111C2D))),
                  const Icon(Icons.expand_more, color: Color(0xFF6E6B7B)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
