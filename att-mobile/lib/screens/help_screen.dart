import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:url_launcher/url_launcher.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/privacy_policy_screen.dart';

class HelpScreen extends StatefulWidget {
  const HelpScreen({super.key});

  @override
  State<HelpScreen> createState() => _HelpScreenState();
}

class _HelpScreenState extends State<HelpScreen> {
  bool _isLoading = true;
  String _helpPhone = '';
  String _helpWhatsapp = '';
  String _helpEmail = '';
  String _helpHours = 'Senin - Jumat (08:00 - 17:00 WIB)';

  @override
  void initState() {
    super.initState();
    _fetchSettings();
  }

  Future<void> _fetchSettings() async {
    try {
      final response = await http.get(Uri.parse('${Constants.baseUrl}/settings'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final setting = data['data'];
        if (setting != null) {
          setState(() {
            _helpPhone = setting['help_phone'] ?? '';
            _helpWhatsapp = setting['help_whatsapp'] ?? '';
            _helpEmail = setting['help_email'] ?? '';
            if (setting['help_hours'] != null && setting['help_hours'].toString().isNotEmpty) {
              _helpHours = setting['help_hours'];
            }
          });
        }
      }
    } catch (e) {
      debugPrint('Error fetching help settings: $e');
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _launchUrl(String urlString) async {
    try {
      final uri = Uri.parse(urlString);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (e) {
      debugPrint('Could not launch $urlString: $e');
    }
  }

  void _openWhatsApp() {
    String cleanNumber = _helpWhatsapp.replaceAll(RegExp(r'[^0-9]'), '');
    if (cleanNumber.startsWith('0')) {
      cleanNumber = '62${cleanNumber.substring(1)}';
    }
    if (cleanNumber.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor WhatsApp belum dikonfigurasi di dashboard.')),
      );
      return;
    }
    _launchUrl('https://wa.me/$cleanNumber?text=Halo%20Helpdesk%20ESA,%20saya%20membutuhkan%20bantuan%20terkait%20aplikasi.');
  }

  void _openPhone() {
    String cleanNumber = _helpPhone.replaceAll(RegExp(r'[^0-9+]'), '');
    if (cleanNumber.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nomor telepon belum dikonfigurasi di dashboard.')),
      );
      return;
    }
    _launchUrl('tel:$cleanNumber');
  }

  void _openEmail() {
    if (_helpEmail.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Email bantuan belum dikonfigurasi di dashboard.')),
      );
      return;
    }
    _launchUrl('mailto:$_helpEmail?subject=Bantuan%20Aplikasi%20ESA');
  }

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
        title: const Text('Pusat Bantuan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: primaryColor,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Hero Card
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [primaryColor, primaryColor.withValues(alpha: 0.85)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: primaryColor.withValues(alpha: 0.25),
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
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(Icons.headset_mic_rounded, color: Colors.white, size: 28),
                            ),
                            const SizedBox(width: 14),
                            const Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Ada Kendala Presensi?',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  SizedBox(height: 2),
                                  Text(
                                    'Tim Helpdesk kami siap membantu Anda.',
                                    style: TextStyle(color: Colors.white70, fontSize: 11),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.access_time_rounded, color: Colors.white70, size: 14),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  _helpHours,
                                  style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w500),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 22),

                  // Hubungi Kami Section
                  Text(
                    'Hubungi Kami',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  const SizedBox(height: 12),

                  // WhatsApp Button
                  _buildContactTile(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    icon: Icons.chat_rounded,
                    iconBgColor: const Color(0xFF25D366),
                    title: 'WhatsApp Helpdesk',
                    subtitle: _helpWhatsapp.isNotEmpty ? _helpWhatsapp : 'Hubungi via WhatsApp resmi',
                    actionLabel: 'Chat Sekarang',
                    onTap: _openWhatsApp,
                  ),

                  const SizedBox(height: 10),

                  // Telepon Button
                  _buildContactTile(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    icon: Icons.phone_in_talk_rounded,
                    iconBgColor: const Color(0xFF0F52BA),
                    title: 'Telepon Langsung',
                    subtitle: _helpPhone.isNotEmpty ? _helpPhone : 'Layanan panggilan telepon',
                    actionLabel: 'Panggil',
                    onTap: _openPhone,
                  ),

                  const SizedBox(height: 10),

                  // Email Button
                  _buildContactTile(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    icon: Icons.email_rounded,
                    iconBgColor: const Color(0xFFEA4335),
                    title: 'Email Support',
                    subtitle: _helpEmail.isNotEmpty ? _helpEmail : 'Kirim tiket / email kendala',
                    actionLabel: 'Kirim Email',
                    onTap: _openEmail,
                  ),

                  const SizedBox(height: 24),

                  // FAQ Section
                  Text(
                    'Pertanyaan Umum (FAQ)',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  const SizedBox(height: 12),

                  _buildFaqTile(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    question: 'Bagaimana jika gagal check-in karena lokasi tidak terbaca?',
                    answer:
                        'Pastikan GPS perangkat Anda dalam mode Akurasi Tinggi (High Accuracy), izin lokasi untuk aplikasi diatur ke "Izinkan sepanjang waktu" atau "Saat aplikasi digunakan", dan matikan aplikasi Fake GPS / Mock Location.',
                  ),
                  const SizedBox(height: 8),

                  _buildFaqTile(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    question: 'Apakah saya bisa presensi saat tidak ada internet / offline?',
                    answer:
                        'Ya, aplikasi mendukung Presensi Offline. Data check-in, check-out, dan laporan kunjungan akan tersimpan di memori perangkat dan otomatis tersinkronisasi ke server saat perangkat terhubung internet kembali.',
                  ),
                  const SizedBox(height: 8),

                  _buildFaqTile(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    question: 'Bagaimana jika saya lupa melakukan Check-Out?',
                    answer:
                        'Anda dapat mengajukan permohonan Berita Acara Presensi (BAP) melalui menu Permit / Izin atau menghubungi atasan langsung untuk verifikasi kehadiran.',
                  ),

                  const SizedBox(height: 22),

                  // Privacy Policy Link
                  InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (_) => const PrivacyPolicyScreen()),
                      );
                    },
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.withValues(alpha: 0.15)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.privacy_tip_outlined, size: 20, color: primaryColor),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Kebijakan Privasi (Privacy Policy)',
                              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textColor),
                            ),
                          ),
                          Icon(Icons.chevron_right_rounded, color: subtitleColor),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
    );
  }

  Widget _buildContactTile({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required IconData icon,
    required Color iconBgColor,
    required String title,
    required String subtitle,
    required String actionLabel,
    required VoidCallback onTap,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.15)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: iconBgColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconBgColor, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: TextStyle(fontSize: 11, color: subtitleColor),
                ),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: onTap,
            style: ElevatedButton.styleFrom(
              backgroundColor: iconBgColor,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              minimumSize: const Size(60, 32),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: Text(actionLabel, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildFaqTile({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required String question,
    required String answer,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.15)),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 0),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        iconColor: const Color(0xFF0F52BA),
        collapsedIconColor: subtitleColor,
        shape: const Border(),
        title: Text(
          question,
          style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: textColor),
        ),
        children: [
          Text(
            answer,
            style: TextStyle(fontSize: 11.5, color: subtitleColor, height: 1.45),
          ),
        ],
      ),
    );
  }
}
