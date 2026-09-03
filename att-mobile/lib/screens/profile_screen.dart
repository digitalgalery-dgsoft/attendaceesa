import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/theme_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';
import 'package:att_mobile/services/biometric_service.dart';
import 'login_screen.dart';
import 'help_screen.dart';
import 'privacy_policy_screen.dart';
import 'onboarding_screen.dart';
import '../utils/constants.dart';
import 'dart:io';
import 'package:att_mobile/screens/liveness_camera_screen.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:att_mobile/services/face_matcher_service.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final ImagePicker _picker = ImagePicker();
  bool _isBiometricSupported = false;
  bool _isBiometricEnabled = false;

  @override
  void initState() {
    super.initState();
    _loadBiometricState();
  }

  Future<void> _loadBiometricState() async {
    final isSupported = await BiometricService.isBiometricAvailable();
    final isEnabled = await BiometricService.isBiometricEnabled();
    if (mounted) {
      setState(() {
        _isBiometricSupported = isSupported;
        _isBiometricEnabled = isEnabled;
      });
    }
  }

  Future<void> _toggleBiometric(bool value) async {
    final locale = Provider.of<LocaleProvider>(context, listen: false);
    if (value) {
      final authenticated = await BiometricService.authenticate(
        localizedReason: locale.tr('biometric_prompt_enable'),
      );
      if (authenticated) {
        final auth = Provider.of<AuthProvider>(context, listen: false);
        final email = auth.user?['email'] ?? auth.employeeData?['email'] ?? '';
        final token = auth.token;
        await BiometricService.setBiometricEnabled(true, email: email, token: token);
        if (mounted) {
          setState(() {
            _isBiometricEnabled = true;
          });
          toastification.show(
            context: context,
            title: Text(locale.tr('success')),
            description: Text(locale.tr('biometric_enabled_success')),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 3),
          );
        }
      } else {
        if (mounted) {
          toastification.show(
            context: context,
            title: Text(locale.tr('error')),
            description: Text(locale.tr('biometric_auth_failed')),
            type: ToastificationType.error,
            autoCloseDuration: const Duration(seconds: 3),
          );
        }
      }
    } else {
      await BiometricService.setBiometricEnabled(false);
      if (mounted) {
        setState(() {
          _isBiometricEnabled = false;
        });
        toastification.show(
          context: context,
          title: Text(locale.tr('info')),
          description: Text(locale.tr('biometric_disabled_success')),
          type: ToastificationType.info,
          autoCloseDuration: const Duration(seconds: 3),
        );
      }
    }
  }

  Future<void> _enrollMasterFaceLiveness() async {
    final photoPath = await Navigator.push<String?>(
      context,
      MaterialPageRoute(
        builder: (_) => const LivenessCameraScreen(isRequired: true, isEnrollment: true),
      ),
    );

    if (photoPath != null && mounted) {
      final auth = Provider.of<AuthProvider>(context, listen: false);

      toastification.show(
        context: context,
        title: const Text('Mengompresi & Menyimpan Wajah...'),
        type: ToastificationType.info,
        autoCloseDuration: const Duration(seconds: 2),
      );

      List<int> bytes;
      try {
        final compressed = await FlutterImageCompress.compressWithFile(
          photoPath,
          minWidth: 960,
          minHeight: 960,
          quality: 85,
          format: CompressFormat.jpeg,
        );
        bytes = compressed ?? await File(photoPath).readAsBytes();
      } catch (_) {
        bytes = await File(photoPath).readAsBytes();
      }

      final result = await auth.updateProfile({
        'has_photo_payload': '1',
      }, imageBytes: bytes, imageFilename: 'master_face.jpg');

      if (mounted) {
        if (result['success'] == true) {
          await FaceMatcherService.clearCachedMasterProfile();
          await auth.tryAutoLogin();
          if (mounted) {
            setState(() {});
          }

          toastification.show(
            context: context,
            title: const Text('Wajah Master Berhasil Disimpan!'),
            description: const Text('Foto wajah Anda telah terdaftar sebagai referensi biometrik Face Recognition.'),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 4),
          );
        } else {
          toastification.show(
            context: context,
            title: const Text('Gagal Menyimpan Wajah'),
            description: Text(result['message'] ?? 'Terjadi kesalahan saat mengunggah foto.'),
            type: ToastificationType.error,
            autoCloseDuration: const Duration(seconds: 4),
          );
        }
      }
    }
  }

  void _pickImage() {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);

    showModalBottomSheet(
      context: context,
      backgroundColor: bgColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade400,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Perbarui Foto Profil / Wajah Master',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.purple.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.face_retouching_natural, color: Colors.purple),
                ),
                title: const Text('Ambil Foto Master (Deteksi AI & Liveness)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                subtitle: const Text('Direkomendasikan untuk verifikasi presensi Face Recognition', style: TextStyle(fontSize: 11.5)),
                onTap: () {
                  Navigator.pop(ctx);
                  _enrollMasterFaceLiveness();
                },
              ),
              const Divider(height: 1),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.camera_alt, color: Colors.blue),
                ),
                title: const Text('Kamera Biasa', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                onTap: () async {
                  Navigator.pop(ctx);
                  final XFile? image = await _picker.pickImage(
                    source: ImageSource.camera,
                    imageQuality: 85,
                    maxWidth: 1080,
                    maxHeight: 1080,
                  );
                  if (image != null && mounted) {
                    final auth = Provider.of<AuthProvider>(context, listen: false);
                    final bytes = await image.readAsBytes();
                    final result = await auth.updateProfile({
                      'has_photo_payload': '1',
                    }, imageBytes: bytes, imageFilename: image.name);
                    if (mounted) {
                      if (result['success']) {
                        await FaceMatcherService.clearCachedMasterProfile();
                        await auth.tryAutoLogin();
                        if (mounted) setState(() {});
                      }
                      toastification.show(
                        context: context,
                        title: Text(result['success'] ? 'Foto berhasil diperbarui' : 'Gagal memperbarui foto'),
                        type: result['success'] ? ToastificationType.success : ToastificationType.error,
                        autoCloseDuration: const Duration(seconds: 3),
                      );
                    }
                  }
                },
              ),
              const Divider(height: 1),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.photo_library, color: Colors.green),
                ),
                title: const Text('Pilih dari Galeri', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                onTap: () async {
                  Navigator.pop(ctx);
                  final XFile? image = await _picker.pickImage(
                    source: ImageSource.gallery,
                    imageQuality: 85,
                    maxWidth: 1080,
                    maxHeight: 1080,
                  );
                  if (image != null && mounted) {
                    final auth = Provider.of<AuthProvider>(context, listen: false);
                    final bytes = await image.readAsBytes();
                    final result = await auth.updateProfile({
                      'has_photo_payload': '1',
                    }, imageBytes: bytes, imageFilename: image.name);
                    if (mounted) {
                      if (result['success']) {
                        await FaceMatcherService.clearCachedMasterProfile();
                        await auth.tryAutoLogin();
                        if (mounted) setState(() {});
                      }
                      toastification.show(
                        context: context,
                        title: Text(result['success'] ? 'Foto berhasil diperbarui' : 'Gagal memperbarui foto'),
                        type: result['success'] ? ToastificationType.success : ToastificationType.error,
                        autoCloseDuration: const Duration(seconds: 3),
                      );
                    }
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showChangePasswordSheet() {
    final oldPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    final locale = Provider.of<LocaleProvider>(context, listen: false);
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
            Text(
              locale.tr('change_password'),
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
            ),
            const SizedBox(height: 20),
            TextField(
              controller: oldPasswordController,
              obscureText: true,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                labelText: locale.tr('current_password'),
                labelStyle: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: primaryColor, width: 1.5),
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: newPasswordController,
              obscureText: true,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                labelText: locale.tr('new_password'),
                labelStyle: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: primaryColor, width: 1.5),
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: confirmPasswordController,
              obscureText: true,
              style: TextStyle(color: textColor),
              decoration: InputDecoration(
                labelText: locale.tr('confirm_password'),
                labelStyle: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
                enabledBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderSide: BorderSide(color: primaryColor, width: 1.5),
                  borderRadius: BorderRadius.circular(8),
                ),
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
                      title: Text(locale.tr('error')),
                      description: Text(locale.tr('password_mismatch')),
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

                  if (!context.mounted) return;
                  if (result['success']) {
                    toastification.show(
                      context: context,
                      title: Text(locale.tr('success')),
                      description: Text(locale.tr('password_updated')),
                      type: ToastificationType.success,
                      autoCloseDuration: const Duration(seconds: 3),
                    );
                  } else {
                    toastification.show(
                      context: context,
                      title: Text(locale.tr('error')),
                      description: Text(result['message'] ?? 'Failed to update password'),
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
                child: Text(locale.tr('save_password'), style: const TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  void _showLanguageSheet() {
    final locale = Provider.of<LocaleProvider>(context, listen: false);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);

    final List<Map<String, String>> languages = [
      {'code': 'en', 'name': 'English (US)'},
      {'code': 'id', 'name': 'Indonesian'},
    ];

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
            child: Text(
              locale.tr('language'),
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
            ),
          ),
          ...languages.map((lang) {
            final isSelected = locale.languageCode == lang['code'];
            return ListTile(
              title: Text(
                lang['name']!,
                style: TextStyle(
                  color: isSelected ? primaryColor : textColor,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                ),
              ),
              trailing: isSelected ? Icon(Icons.check_circle, color: primaryColor) : null,
              onTap: () async {
                Navigator.pop(context);
                await locale.setLanguage(lang['code']!);
                if (context.mounted) {
                  final auth = Provider.of<AuthProvider>(context, listen: false);
                  await auth.updateProfile({'language': lang['name']!});
                  toastification.show(
                    context: context,
                    title: Text(locale.tr('success')),
                    description: Text('Language changed to ${lang['name']}'),
                    type: ToastificationType.success,
                    autoCloseDuration: const Duration(seconds: 2),
                  );
                }
              },
            );
          }),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  void _showTimeZoneSheet() {
    final locale = Provider.of<LocaleProvider>(context, listen: false);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);

    final List<Map<String, String>> timezones = [
      {'short': 'WIB', 'name': 'Western Indonesia Time (WIB / UTC+7)'},
      {'short': 'WITA', 'name': 'Central Indonesia Time (WITA / UTC+8)'},
      {'short': 'WIT', 'name': 'Eastern Indonesia Time (WIT / UTC+9)'},
    ];

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
            child: Text(
              locale.tr('timezone'),
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
            ),
          ),
          ...timezones.map((tz) {
            final isSelected = locale.timeZone == tz['short'];
            return ListTile(
              title: Text(
                tz['name']!,
                style: TextStyle(
                  color: isSelected ? primaryColor : textColor,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                ),
              ),
              trailing: isSelected ? Icon(Icons.check_circle, color: primaryColor) : null,
              onTap: () async {
                Navigator.pop(context);
                await locale.setTimeZone(tz['short']!);
                if (context.mounted) {
                  final auth = Provider.of<AuthProvider>(context, listen: false);
                  await auth.updateProfile({'timezone': tz['name']!});
                  toastification.show(
                    context: context,
                    title: Text(locale.tr('success')),
                    description: Text('Time Zone changed to ${tz['short']}'),
                    type: ToastificationType.success,
                    autoCloseDuration: const Duration(seconds: 2),
                  );
                }
              },
            );
          }),
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

    final locale = Provider.of<LocaleProvider>(context);
    final auth = Provider.of<AuthProvider>(context);
    final employeeName = auth.employeeData?['full_name'] ?? auth.user?['name'] ?? 'User';
    final branchName = auth.employeeData?['branch']?['name'] ?? 'Unknown Branch';
    final roleName = auth.employeeData?['position']?['name'] ?? auth.employeeData?['department']?['name'] ?? 'Employee';
    final photo = auth.employeeData?['photo_url'] ?? auth.employeeData?['photo'];

    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    String primaryHex = primaryColor.value.toRadixString(16).substring(2).toUpperCase();
    String imageUrl = 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(employeeName)}&background=$primaryHex&color=fff';
    if (photo != null && photo.toString().isNotEmpty) {
      imageUrl = Constants.getImageUrl(photo.toString());
    }

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(
          locale.tr('profile_title'),
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
                          ),
                          child: ClipOval(
                            child: Image.network(
                              imageUrl,
                              fit: BoxFit.cover,
                              width: 80,
                              height: 80,
                              errorBuilder: (context, error, stackTrace) {
                                return Image.network(
                                  'https://ui-avatars.com/api/?name=${Uri.encodeComponent(employeeName)}&background=$primaryHex&color=fff',
                                  fit: BoxFit.cover,
                                  width: 80,
                                  height: 80,
                                );
                              },
                              loadingBuilder: (context, child, loadingProgress) {
                                if (loadingProgress == null) return child;
                                return const Center(child: CircularProgressIndicator(strokeWidth: 2));
                              },
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
                    roleName,
                    style: TextStyle(color: subtitleColor, fontSize: 13),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: primaryColor.withValues(alpha: 0.1),
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

            // Foto Master Face ID Biometric Card
            Builder(builder: (context) {
              final auth = Provider.of<AuthProvider>(context);
              final position = auth.employeeData?['position'];
              final bool isFaceRequired = (position is Map) ? (position['require_face_recognition'] ?? false) : false;
              final String? masterPhoto = auth.employeeData?['photo_url'] ?? auth.employeeData?['photo'];
              final bool hasMasterPhoto = masterPhoto != null && masterPhoto.isNotEmpty && !masterPhoto.contains('default.png');

              return Container(
                width: double.infinity,
                margin: const EdgeInsets.only(bottom: 24),
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: !hasMasterPhoto && isFaceRequired 
                        ? Colors.red.shade300 
                        : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                    width: !hasMasterPhoto && isFaceRequired ? 1.5 : 1,
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: primaryColor.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(Icons.face_retouching_natural, color: primaryColor, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Foto Master Face Recognition',
                                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Referensi biometrik wajah untuk verifikasi absensi',
                                style: TextStyle(fontSize: 11, color: subtitleColor),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: hasMasterPhoto
                            ? const Color(0xFF10B981).withValues(alpha: 0.1)
                            : (isFaceRequired ? const Color(0xFFEF4444).withValues(alpha: 0.1) : Colors.grey.withValues(alpha: 0.1)),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            hasMasterPhoto ? Icons.check_circle_rounded : (isFaceRequired ? Icons.warning_amber_rounded : Icons.info_outline),
                            size: 16,
                            color: hasMasterPhoto ? const Color(0xFF10B981) : (isFaceRequired ? const Color(0xFFEF4444) : Colors.grey),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              hasMasterPhoto
                                  ? 'Wajah Master Terdaftar ✅'
                                  : (isFaceRequired ? 'Belum Terdaftar ⚠️ (Wajib untuk Jabatan Anda)' : 'Belum Terdaftar (Opsional)'),
                              style: TextStyle(
                                fontSize: 11.5,
                                fontWeight: FontWeight.w600,
                                color: hasMasterPhoto ? const Color(0xFF10B981) : (isFaceRequired ? const Color(0xFFEF4444) : subtitleColor),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: _pickImage,
                        icon: const Icon(Icons.camera_enhance_rounded, size: 16),
                        label: Text(
                          hasMasterPhoto ? 'Perbarui Foto Master Wajah' : 'Daftarkan Foto Master Wajah',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                        ),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: primaryColor,
                          side: BorderSide(color: primaryColor),
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }),

            // Account Security (Change Password & Functional Biometric Login)
            _buildSettingsSection(
              icon: Icons.security,
              title: locale.tr('sec_account_security'),
              cardColor: cardColor,
              textColor: textColor,
              isDarkMode: isDarkMode,
              primaryColor: primaryColor,
              children: [
                _buildActionSetting(
                  Icons.password,
                  locale.tr('change_password'),
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                  onTap: _showChangePasswordSheet,
                ),
                _buildBiometricToggleSetting(
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  primaryColor: primaryColor,
                  isSupported: _isBiometricSupported,
                  isEnabled: _isBiometricEnabled,
                  onChanged: _toggleBiometric,
                  locale: locale,
                ),
              ],
            ),
            const SizedBox(height: 20),

            // App Preferences (Language, Timezone & Dark Mode)
            _buildSettingsSection(
              icon: Icons.settings,
              title: locale.tr('sec_app_preferences'),
              cardColor: cardColor,
              textColor: textColor,
              isDarkMode: isDarkMode,
              primaryColor: primaryColor,
              children: [
                _buildDropdownSetting(
                  locale.tr('language'),
                  locale.languageDisplayName,
                  textColor,
                  subtitleColor,
                  isDarkMode,
                  onTap: _showLanguageSheet,
                ),
                _buildDropdownSetting(
                  locale.tr('timezone'),
                  '${locale.timeZone} (UTC+${locale.timezoneOffsetHours})',
                  textColor,
                  subtitleColor,
                  isDarkMode,
                  onTap: _showTimeZoneSheet,
                ),
                _buildThemeToggleSetting(textColor, subtitleColor, primaryColor, locale),
              ],
            ),
            const SizedBox(height: 20),

            // Help & Policies
            _buildSettingsSection(
              icon: Icons.help_outline_rounded,
              title: locale.tr('sec_help_policy'),
              cardColor: cardColor,
              textColor: textColor,
              isDarkMode: isDarkMode,
              primaryColor: primaryColor,
              children: [
                _buildActionSetting(
                  Icons.menu_book_rounded,
                  locale.tr('user_guide'),
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const OnboardingScreen(isFromSettings: true),
                      ),
                    );
                  },
                ),
                _buildActionSetting(
                  Icons.support_agent_rounded,
                  locale.tr('help_center'),
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const HelpScreen()),
                    );
                  },
                ),
                _buildActionSetting(
                  Icons.privacy_tip_outlined,
                  locale.tr('privacy_policy'),
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const PrivacyPolicyScreen()),
                    );
                  },
                ),
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
                label: Text(locale.tr('logout'), style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
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

  Widget _buildBiometricToggleSetting({
    required Color textColor,
    required Color subtitleColor,
    required Color primaryColor,
    required bool isSupported,
    required bool isEnabled,
    required ValueChanged<bool> onChanged,
    required LocaleProvider locale,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Row(
              children: [
                Icon(Icons.fingerprint, color: primaryColor, size: 22),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        locale.tr('biometric_login'),
                        style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        isSupported ? locale.tr('biometric_subtitle') : locale.tr('biometric_not_available'),
                        style: TextStyle(fontSize: 11.5, color: subtitleColor),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Switch(
            value: isEnabled,
            onChanged: isSupported ? onChanged : null,
            activeThumbColor: Colors.white,
            activeTrackColor: primaryColor,
          ),
        ],
      ),
    );
  }

  Widget _buildThemeToggleSetting(Color textColor, Color subtitleColor, Color primaryColor, LocaleProvider locale) {
    final themeProvider = Provider.of<ThemeProvider>(context);
    final isDark = themeProvider.themeMode == ThemeMode.dark ||
        (themeProvider.themeMode == ThemeMode.system && MediaQuery.of(context).platformBrightness == Brightness.dark);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(locale.tr('dark_mode'), style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
              const SizedBox(height: 2),
              Text(locale.tr('dark_mode_sub'), style: TextStyle(fontSize: 12, color: subtitleColor)),
            ],
          ),
          Switch(
            value: isDark,
            onChanged: (v) {
              themeProvider.toggleTheme(v);
            },
            activeThumbColor: Colors.white,
            activeTrackColor: primaryColor,
          ),
        ],
      ),
    );
  }

  Widget _buildActionSetting(
    IconData icon,
    String title, {
    String? value,
    Color? valueColor,
    VoidCallback? onTap,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
  }) {
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

  Widget _buildDropdownSetting(
    String label,
    String value,
    Color textColor,
    Color subtitleColor,
    bool isDarkMode, {
    VoidCallback? onTap,
  }) {
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
