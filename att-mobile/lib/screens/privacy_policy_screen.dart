import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF6E6B7B);
    final auth = Provider.of<AuthProvider>(context);
    final locale = Provider.of<LocaleProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final isEn = locale.isEnglish;

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(
          locale.tr('privacy_policy'),
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
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Banner matching Profile / Help card style
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.security_rounded, color: primaryColor, size: 26),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isEn ? 'Data Protection & Privacy' : 'Perlindungan Data & Privasi',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: textColor,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          isEn
                              ? 'Our commitment to protecting your attendance and personal data.'
                              : 'Komitmen kami dalam menjaga kerahasiaan & keamanan data presensi Anda.',
                          style: TextStyle(
                            fontSize: 12,
                            color: subtitleColor,
                            height: 1.35,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            _buildSectionCard(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              primaryColor: primaryColor,
              isDarkMode: isDarkMode,
              icon: Icons.info_outline_rounded,
              title: isEn ? '1. Introduction' : '1. Pendahuluan',
              content: isEn
                  ? 'Enterprise Solution Apps (ESA) is dedicated to protecting the privacy of our employees. This policy outlines how information is gathered, utilized, and safeguarded throughout your attendance and field activity logging.'
                  : 'Aplikasi Enterprise Solution Apps (ESA) berkomitmen melindungi privasi data pribadi setiap pengguna/karyawan. Kebijakan ini menjelaskan bagaimana data dikumpulkan, digunakan, dan dilindungi selama Anda menggunakan aplikasi ini.',
            ),

            const SizedBox(height: 16),

            _buildSectionCard(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              primaryColor: primaryColor,
              isDarkMode: isDarkMode,
              icon: Icons.folder_shared_outlined,
              title: isEn ? '2. Collected Information' : '2. Data yang Dikumpulkan',
              content: isEn
                  ? '• User Identity: Full name, Employee ID (NIK), division, position, and assigned branch office.\n'
                    '• Attendance Data: Check-in/out timestamps, daily working duration, and shift schedule logs.\n'
                    '• Location Data (GPS): Precise coordinates recorded during attendance and active live tracking during field visits.\n'
                    '• Biometric Face Verification: Selfie photos taken during check-in/out for AI face detection.\n'
                    '• Device Integrity: Model information, operating system version, and security integrity verification (Mock Location & Root detection).'
                  : '• Identitas Diri: Nama lengkap, NIK/Nomor Karyawan, jabatan, divisi, dan kantor cabang.\n'
                    '• Data Kehadiran: Waktu check-in, check-out, durasi kerja, dan status jadwal dinas.\n'
                    '• Lokasi Geografis (GPS): Koordinat lokasi perangkat saat presensi dan pelacakan rute dinas aktif (Live Tracking).\n'
                    '• Foto Verifikasi Wajah: Foto selfie saat presensi untuk verifikasi biometrik deteksi wajah (Face Detection).\n'
                    '• Informasi Perangkat: Model perangkat, versi OS, dan status keamanan (deteksi Fake GPS / Root).',
            ),

            const SizedBox(height: 16),

            _buildSectionCard(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              primaryColor: primaryColor,
              isDarkMode: isDarkMode,
              icon: Icons.location_on_outlined,
              title: isEn ? '3. Location & Camera Permissions' : '3. Penggunaan Izin Lokasi & Kamera',
              content: isEn
                  ? '• High-Accuracy Location: Used exclusively to validate workplace radius and record official business itinerary routes.\n'
                    '• Camera Access: Used for taking selfie attendance photos and capturing evidence photos for field visit reports.'
                  : '• Izin Lokasi Presisi (Foreground & Background): Digunakan semata-mata untuk memverifikasi radius kantor yang sah serta mencatat histori rute saat kunjungan lapangan (Live Tracking).\n'
                    '• Izin Kamera: Digunakan untuk mengambil foto selfie saat check-in, check-out, atau menyertakan foto bukti laporan kunjungan kerja.',
            ),

            const SizedBox(height: 16),

            _buildSectionCard(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              primaryColor: primaryColor,
              isDarkMode: isDarkMode,
              icon: Icons.lock_outline_rounded,
              title: isEn ? '4. Security & Encryption' : '4. Keamanan & Kerahasiaan Data',
              content: isEn
                  ? 'All data transmitted between the mobile application and our servers is strictly encrypted using secure HTTPS/TLS protocols. Personal data is never shared with or sold to third parties.'
                  : 'Seluruh data yang dikirimkan antara aplikasi mobile dan server dienkripsi menggunakan protokol aman HTTPS/TLS. Kami tidak pernah membagikan atau menjual data pribadi karyawan kepada pihak ketiga di luar kepentingan operasional perusahaan.',
            ),

            const SizedBox(height: 16),

            _buildSectionCard(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              primaryColor: primaryColor,
              isDarkMode: isDarkMode,
              icon: Icons.support_agent_rounded,
              title: isEn ? '5. Contact & Support' : '5. Pertanyaan & Bantuan',
              content: isEn
                  ? 'If you have inquiries regarding privacy practices or your data, please reach out to HR / IT Helpdesk via the Help Center menu.'
                  : 'Jika Anda memiliki pertanyaan mengenai kebijakan privasi atau pengelolaan data pribadi Anda, silakan hubungi bagian HRD / IT Helpdesk melalui menu Pusat Bantuan (Help Center).',
            ),

            const SizedBox(height: 24),

            Center(
              child: Text(
                isEn
                    ? 'Last Updated: August 2026\nESA - Enterprise Solution Apps'
                    : 'Terakhir Diperbarui: Agustus 2026\nESA - Enterprise Solution Apps',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  color: subtitleColor,
                  height: 1.45,
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color primaryColor,
    required bool isDarkMode,
    required IconData icon,
    required String title,
    required String content,
  }) {
    return Container(
      width: double.infinity,
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
              Icon(icon, size: 20, color: primaryColor),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: textColor,
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Divider(
              color: isDarkMode ? Colors.grey.shade800 : const Color(0xFFE0E0E0),
              height: 1,
            ),
          ),
          Text(
            content,
            style: TextStyle(
              fontSize: 12.5,
              color: subtitleColor,
              height: 1.55,
            ),
          ),
        ],
      ),
    );
  }
}
