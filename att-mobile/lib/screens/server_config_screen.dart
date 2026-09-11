import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/login_screen.dart';
import 'package:toastification/toastification.dart';
import 'dart:convert';

class ServerConfigScreen extends StatefulWidget {
  const ServerConfigScreen({super.key});

  @override
  State<ServerConfigScreen> createState() => _ServerConfigScreenState();
}

class _ServerConfigScreenState extends State<ServerConfigScreen> {
  final _subdomainController = TextEditingController();
  final _customUrlController = TextEditingController();
  bool _isLoading = false;
  bool _useCustomUrl = false;

  @override
  void initState() {
    super.initState();
    _initSubdomainFromCurrentBase();
  }

  void _initSubdomainFromCurrentBase() {
    final current = Constants.baseUrl;
    if (current.isNotEmpty) {
      String clean = current.trim().toLowerCase();
      clean = clean.replaceFirst(RegExp(r'^https?://'), '');
      clean = clean.replaceAll(RegExp(r'/api/?$'), '');
      clean = clean.replaceAll(RegExp(r'/.*$'), '');

      if (clean.endsWith('.esa-solutions.id')) {
        final sub = clean.replaceAll('.esa-solutions.id', '');
        _subdomainController.text = sub.isNotEmpty ? sub : 'api';
        _useCustomUrl = false;
      } else if (clean.isNotEmpty) {
        _customUrlController.text = current.replaceAll('/api', '');
        _subdomainController.text = 'api';
        // If current was appsend.my.id, keep subdomain as 'api' ready for esa-solutions.id
      } else {
        _subdomainController.text = 'api';
      }
    } else {
      _subdomainController.text = 'api';
    }
  }

  @override
  void dispose() {
    _subdomainController.dispose();
    _customUrlController.dispose();
    super.dispose();
  }

  String _cleanSubdomain(String input) {
    var s = input.trim().toLowerCase();
    s = s.replaceFirst(RegExp(r'^https?://'), '');
    s = s.replaceFirst(RegExp(r'\.esa-solutions\.id.*$'), '');
    s = s.replaceFirst(RegExp(r'/.*$'), '');
    return s.trim();
  }

  String _getEffectiveApiUrl() {
    if (_useCustomUrl) {
      var custom = _customUrlController.text.trim();
      if (custom.endsWith('/')) {
        custom = custom.substring(0, custom.length - 1);
      }
      if (!custom.endsWith('/api')) {
        custom = '$custom/api';
      }
      return custom;
    } else {
      final sub = _cleanSubdomain(_subdomainController.text);
      final finalSub = sub.isNotEmpty ? sub : 'api';
      return 'https://$finalSub.esa-solutions.id/api';
    }
  }

  Future<void> _testAndSaveUrl() async {
    final apiUrl = _getEffectiveApiUrl();

    if (!_useCustomUrl && _cleanSubdomain(_subdomainController.text).isEmpty) {
      toastification.show(
        context: context,
        title: const Text('Subdomain server tidak boleh kosong'),
        type: ToastificationType.error,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      // Test the URL by fetching settings
      final response = await http
          .get(Uri.parse('$apiUrl/settings'))
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          await Constants.setBaseUrl(apiUrl);

          if (!mounted) return;
          toastification.show(
            context: context,
            title: const Text('Berhasil terhubung ke server!'),
            description: Text(apiUrl.replaceAll('/api', '')),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 2),
          );

          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const LoginScreen()),
          );
        } else {
          throw Exception('Format respon server tidak valid');
        }
      } else {
        throw Exception('Server merespon status ${response.statusCode}');
      }
    } catch (e) {
      if (!mounted) return;
      toastification.show(
        context: context,
        title: const Text('Gagal terhubung ke server'),
        description: Text(e.toString().replaceAll('Exception: ', '')),
        type: ToastificationType.error,
        autoCloseDuration: const Duration(seconds: 5),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final canPop = Navigator.canPop(context);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Theme.of(context).primaryColor;
    final cardColor = isDarkMode ? const Color(0xFF1E1E2D) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF1E293B);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF64748B);
    final inputBg = isDarkMode ? const Color(0xFF14141E) : const Color(0xFFF8FAFC);
    final borderColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFCBD5E1);

    final currentTarget = _getEffectiveApiUrl();

    return Scaffold(
      backgroundColor: isDarkMode ? const Color(0xFF0F0F17) : const Color(0xFFF1F5F9),
      appBar: canPop
          ? AppBar(
              backgroundColor: Colors.transparent,
              elevation: 0,
              leading: IconButton(
                icon: Icon(
                  Icons.arrow_back,
                  color: isDarkMode ? Colors.white : Colors.black,
                ),
                onPressed: () => Navigator.pop(context),
              ),
            )
          : null,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16.0),
            child: Container(
              padding: const EdgeInsets.all(24.0),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: borderColor),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: isDarkMode ? 0.3 : 0.05),
                    blurRadius: 20,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Icon Header
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: primaryColor.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      Icons.dns_rounded,
                      size: 38,
                      color: primaryColor,
                    ),
                  ),
                  const SizedBox(height: 20),

                  Text(
                    'Server Configuration',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: textColor,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Masukkan kode server perusahaan Anda untuk menghubungkan aplikasi.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 13,
                      color: subtitleColor,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 24),

                  if (!_useCustomUrl) ...[
                    // Permanent Prefix & Suffix URL Field
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'URL Domain Server ESA',
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: textColor,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),

                    Container(
                      decoration: BoxDecoration(
                        color: inputBg,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: borderColor, width: 1.5),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      child: Row(
                        children: [
                          // Permanent Prefix https://
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                            decoration: BoxDecoration(
                              color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.lock_rounded,
                                  size: 13,
                                  color: isDarkMode ? Colors.lightBlueAccent : const Color(0xFF0284C7),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  'https://',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 12.5,
                                    color: isDarkMode ? Colors.white : const Color(0xFF0F172A),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),

                          // Subdomain editable field
                          Expanded(
                            child: TextField(
                              controller: _subdomainController,
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: isDarkMode ? Colors.white : Colors.black87,
                              ),
                              decoration: InputDecoration(
                                hintText: 'api',
                                hintStyle: TextStyle(
                                  color: Colors.grey.shade400,
                                  fontWeight: FontWeight.normal,
                                ),
                                border: InputBorder.none,
                                isDense: true,
                                contentPadding: const EdgeInsets.symmetric(vertical: 8),
                              ),
                              keyboardType: TextInputType.text,
                              autocorrect: false,
                              enableSuggestions: false,
                              onChanged: (_) => setState(() {}),
                            ),
                          ),

                          // Permanent Suffix .esa-solutions.id
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                            decoration: BoxDecoration(
                              color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              '.esa-solutions.id',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 12.5,
                                color: isDarkMode ? Colors.white70 : const Color(0xFF475569),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 10),

                    // Quick Select Server Chips
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Wrap(
                        spacing: 8,
                        runSpacing: 6,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [
                          Text(
                            'Pilihan Cepat:',
                            style: TextStyle(fontSize: 11.5, color: subtitleColor),
                          ),
                          ...['api', 'amk', 'akp', 'atk', 'dulux'].map((sub) {
                            final isSelected = _cleanSubdomain(_subdomainController.text) == sub;
                            return InkWell(
                              onTap: () {
                                setState(() {
                                  _subdomainController.text = sub;
                                });
                              },
                              borderRadius: BorderRadius.circular(6),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: isSelected
                                      ? primaryColor
                                      : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  sub,
                                  style: TextStyle(
                                    fontSize: 11.5,
                                    fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                                    color: isSelected
                                        ? Colors.white
                                        : (isDarkMode ? Colors.white70 : Colors.black87),
                                  ),
                                ),
                              ),
                            );
                          }),
                        ],
                      ),
                    ),
                  ] else ...[
                    // Custom URL input mode
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'URL Server Lengkap (Custom)',
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w600,
                          color: textColor,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _customUrlController,
                      decoration: InputDecoration(
                        hintText: 'https://appsend.my.id',
                        filled: true,
                        fillColor: inputBg,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: borderColor),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: borderColor),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: primaryColor, width: 1.5),
                        ),
                        prefixIcon: const Icon(Icons.link_rounded),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      ),
                      keyboardType: TextInputType.url,
                      onChanged: (_) => setState(() {}),
                    ),
                  ],

                  const SizedBox(height: 14),

                  // Target Preview Banner
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0284C7).withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFF0284C7).withValues(alpha: 0.2)),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.link_rounded, size: 16, color: Color(0xFF0284C7)),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            currentTarget,
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF0284C7),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),

                  // Connect Button
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _testAndSaveUrl,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryColor,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: _isLoading
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(
                                color: Colors.white,
                                strokeWidth: 2.2,
                              ),
                            )
                          : const Text(
                              'Hubungkan ke Server',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                            ),
                    ),
                  ),

                  const SizedBox(height: 12),

                  // Toggle Custom URL Mode
                  TextButton.icon(
                    onPressed: () {
                      setState(() {
                        _useCustomUrl = !_useCustomUrl;
                      });
                    },
                    icon: Icon(
                      _useCustomUrl ? Icons.dns_rounded : Icons.tune_rounded,
                      size: 15,
                      color: subtitleColor,
                    ),
                    label: Text(
                      _useCustomUrl
                          ? 'Kembali ke Format Standar (.esa-solutions.id)'
                          : 'Gunakan Domain Kustom Lainnya',
                      style: TextStyle(fontSize: 12, color: subtitleColor),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
