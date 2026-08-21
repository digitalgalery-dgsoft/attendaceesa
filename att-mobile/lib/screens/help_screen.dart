import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/privacy_policy_screen.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';

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
          locale.tr('help_center'),
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
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: primaryColor))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Support Hero Header Card (Consistent with app design language)
                  Container(
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
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: primaryColor.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(Icons.support_agent_rounded, color: primaryColor, size: 28),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    isEn ? 'Need Assistance?' : 'Ada Kendala Presensi?',
                                    style: TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.bold,
                                      color: textColor,
                                    ),
                                  ),
                                  const SizedBox(height: 3),
                                  Text(
                                    isEn
                                        ? 'Our IT & HR Support team is ready to help you.'
                                        : 'Tim Helpdesk kami siap membantu kendala Anda.',
                                    style: TextStyle(fontSize: 12, color: subtitleColor),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          child: Divider(
                            color: isDarkMode ? Colors.grey.shade800 : const Color(0xFFE0E0E0),
                            height: 1,
                          ),
                        ),
                        Row(
                          children: [
                            Icon(Icons.schedule_rounded, size: 16, color: primaryColor),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _helpHours,
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: textColor,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Section Contact
                  Text(
                    isEn ? 'Contact Channels' : 'Hubungi Kami',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: textColor,
                    ),
                  ),
                  const SizedBox(height: 12),

                  // WhatsApp
                  _buildContactCard(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                    icon: Icons.chat_rounded,
                    iconColor: const Color(0xFF25D366),
                    title: 'WhatsApp Helpdesk',
                    subtitle: _helpWhatsapp.isNotEmpty ? _helpWhatsapp : (isEn ? 'Chat via Official WhatsApp' : 'Hubungi via WhatsApp resmi'),
                    buttonText: isEn ? 'Chat' : 'Chat',
                    onTap: _openWhatsApp,
                  ),

                  const SizedBox(height: 12),

                  // Phone
                  _buildContactCard(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                    icon: Icons.phone_in_talk_rounded,
                    iconColor: const Color(0xFF0F52BA),
                    title: isEn ? 'Phone Support' : 'Telepon Langsung',
                    subtitle: _helpPhone.isNotEmpty ? _helpPhone : (isEn ? 'Direct customer call' : 'Layanan panggilan telepon'),
                    buttonText: isEn ? 'Call' : 'Panggil',
                    onTap: _openPhone,
                  ),

                  const SizedBox(height: 12),

                  // Email
                  _buildContactCard(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                    icon: Icons.email_rounded,
                    iconColor: const Color(0xFFEA4335),
                    title: isEn ? 'Email Support' : 'Email Support',
                    subtitle: _helpEmail.isNotEmpty ? _helpEmail : (isEn ? 'Send support ticket' : 'Kirim tiket / email kendala'),
                    buttonText: isEn ? 'Email' : 'Email',
                    onTap: _openEmail,
                  ),

                  const SizedBox(height: 24),

                  // Section FAQ
                  Text(
                    isEn ? 'Frequently Asked Questions (FAQ)' : 'Pertanyaan Umum (FAQ)',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: textColor,
                    ),
                  ),
                  const SizedBox(height: 12),

                  _buildFaqCard(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                    primaryColor: primaryColor,
                    question: isEn
                        ? 'Why did check-in fail due to location coordinates?'
                        : 'Bagaimana jika gagal check-in karena lokasi tidak terbaca?',
                    answer: isEn
                        ? 'Ensure your device GPS is in High Accuracy mode, location permission is set to "Allow all the time" or "While using app", and disable Fake GPS / Mock Location apps.'
                        : 'Pastikan GPS perangkat dalam mode Akurasi Tinggi (High Accuracy), izin lokasi diatur ke "Izinkan saat aplikasi digunakan", dan matikan aplikasi Fake GPS / Mock Location.',
                  ),
                  const SizedBox(height: 10),

                  _buildFaqCard(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                    primaryColor: primaryColor,
                    question: isEn
                        ? 'Can I record attendance while offline?'
                        : 'Apakah saya bisa presensi saat tidak ada internet / offline?',
                    answer: isEn
                        ? 'Yes, the app supports Offline Attendance. Check-in, check-out, and visit logs are safely cached locally and will sync automatically once connection is restored.'
                        : 'Ya, aplikasi mendukung Presensi Offline. Data check-in, check-out, dan laporan kunjungan akan tersimpan di memori perangkat dan otomatis tersinkronisasi ke server saat perangkat terhubung internet kembali.',
                  ),
                  const SizedBox(height: 10),

                  _buildFaqCard(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                    primaryColor: primaryColor,
                    question: isEn
                        ? 'What should I do if I forgot to Check-Out?'
                        : 'Bagaimana jika saya lupa melakukan Check-Out?',
                    answer: isEn
                        ? 'You can submit an Attendance Correction (BAP) through the Permit menu or contact your supervisor for verification.'
                        : 'Anda dapat mengajukan permohonan Berita Acara Presensi (BAP) melalui menu Permit / Izin atau menghubungi atasan langsung untuk verifikasi kehadiran.',
                  ),

                  const SizedBox(height: 20),

                  // Privacy Policy link
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
                        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.privacy_tip_outlined, size: 20, color: primaryColor),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              locale.tr('privacy_policy'),
                              style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: textColor),
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

  Widget _buildContactCard({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required String buttonText,
    required VoidCallback onTap,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: iconColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: TextStyle(fontSize: 12, color: subtitleColor),
                ),
              ],
            ),
          ),
          ElevatedButton(
            onPressed: onTap,
            style: ElevatedButton.styleFrom(
              backgroundColor: iconColor,
              foregroundColor: Colors.white,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              minimumSize: const Size(60, 34),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: Text(buttonText, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildFaqCard({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
    required Color primaryColor,
    required String question,
    required String answer,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 2),
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        iconColor: primaryColor,
        collapsedIconColor: subtitleColor,
        shape: const Border(),
        title: Text(
          question,
          style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textColor),
        ),
        children: [
          Text(
            answer,
            style: TextStyle(fontSize: 12, color: subtitleColor, height: 1.5),
          ),
        ],
      ),
    );
  }
}
