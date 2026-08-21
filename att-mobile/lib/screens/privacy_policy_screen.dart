import 'package:flutter/material.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8FAFC);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0F172A);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF64748B);
    final primaryColor = Theme.of(context).primaryColor;

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: const Text('Kebijakan Privasi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: primaryColor,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Banner
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [primaryColor.withValues(alpha: 0.12), primaryColor.withValues(alpha: 0.04)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: primaryColor.withValues(alpha: 0.2)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: primaryColor,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.security_rounded, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Perlindungan Data Anda',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: textColor,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'Komitmen kami dalam menjaga kerahasiaan & keamanan data kehadiran Anda.',
                          style: TextStyle(
                            fontSize: 11,
                            color: subtitleColor,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            _buildSection(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              icon: Icons.info_outline_rounded,
              title: '1. Pendahuluan',
              content:
                  'Aplikasi Enterprise Solution Apps (ESA) berkomitmen melindungi privasi data pribadi setiap pengguna/karyawan. Kebijakan ini menjelaskan bagaimana data dikumpulkan, digunakan, dan dilindungi selama Anda menggunakan aplikasi ini.',
            ),

            const SizedBox(height: 14),

            _buildSection(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              icon: Icons.folder_shared_outlined,
              title: '2. Data yang Dikumpulkan',
              content:
                  '• Identitas Diri: Nama lengkap, NIK/Nomor Karyawan, jabatan, divisi, dan kantor cabang.\n'
                  '• Data Kehadiran: Waktu check-in, check-out, durasi kerja, dan status jadwal dinas.\n'
                  '• Lokasi Geografis (GPS): Koordinat lokasi perangkat saat melakukan presensi dan pelacakan rute dinas aktif (Live Tracking).\n'
                  '• Foto Verifikasi Wajah: Foto selfie saat presensi untuk verifikasi biometrik deteksi wajah (Face Detection).\n'
                  '• Informasi Perangkat: Model perangkat, versi sistem operasi, dan status keamanan (deteksi Fake GPS / Root).',
            ),

            const SizedBox(height: 14),

            _buildSection(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              icon: Icons.location_on_outlined,
              title: '3. Penggunaan Izin Lokasi & Kamera',
              content:
                  '• Izin Lokasi Presisi (Foreground & Background): Digunakan semata-mata untuk memverifikasi radius absensi dari titik kantor yang sah serta mencatat histori rute saat kunjungan lapangan (Live Tracking).\n'
                  '• Izin Kamera: Digunakan untuk mengambil foto selfie saat check-in, check-out, atau menyertakan foto bukti laporan kunjungan kerja.',
            ),

            const SizedBox(height: 14),

            _buildSection(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              icon: Icons.lock_outline_rounded,
              title: '4. Keamanan & Kerahasiaan Data',
              content:
                  'Seluruh data yang dikirimkan antara aplikasi mobile dan server dienkripsi menggunakan protokol aman HTTPS/TLS. Kami tidak pernah membagikan atau menjual data pribadi karyawan kepada pihak ketiga di luar kepentingan operasional perusahaan.',
            ),

            const SizedBox(height: 14),

            _buildSection(
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              icon: Icons.contact_support_outlined,
              title: '5. Pertanyaan & Bantuan',
              content:
                  'Jika Anda memiliki pertanyaan mengenai kebijakan privasi atau pengelolaan data pribadi Anda, silakan hubungi bagian HRD / IT Helpdesk melalui menu Pusat Bantuan (Help Center).',
            ),

            const SizedBox(height: 24),

            Center(
              child: Text(
                'Terakhir Diperbarui: Agustus 2026\nESA - Enterprise Solution Apps',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 11,
                  color: subtitleColor,
                  height: 1.4,
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required IconData icon,
    required String title,
    required String content,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.15)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: const Color(0xFF0F52BA)),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.bold,
                    color: textColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            content,
            style: TextStyle(
              fontSize: 11.5,
              color: subtitleColor,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
