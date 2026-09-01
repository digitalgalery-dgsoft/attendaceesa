import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:toastification/toastification.dart';
import '../utils/constants.dart';

class HelpdeskChatScreen extends StatefulWidget {
  final String? initialNik;

  const HelpdeskChatScreen({super.key, this.initialNik});

  @override
  State<HelpdeskChatScreen> createState() => _HelpdeskChatScreenState();
}

class _HelpdeskChatScreenState extends State<HelpdeskChatScreen> {
  // Step 1: NIK Form state
  final TextEditingController _nikController = TextEditingController();
  final TextEditingController _descController = TextEditingController();
  bool _isCheckingNik = false;
  Map<String, dynamic>? _employeeData;
  String _selectedIssueType = 'unlock_device'; // 'unlock_device' or 'forgot_password' or 'other'
  bool _isStartingChat = false;

  // Step 2: Live Chat state
  bool _isInChatMode = false;
  int? _employeeId;
  String? _sessionToken;
  int? _conversationId;
  List<Map<String, dynamic>> _messages = [];
  bool _isLoadingMessages = false;
  bool _isSendingMessage = false;
  final TextEditingController _chatInputController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  Timer? _pollTimer;
  bool _hasDeviceBound = false;

  @override
  void initState() {
    super.initState();
    if (widget.initialNik != null && widget.initialNik!.isNotEmpty) {
      _nikController.text = widget.initialNik!;
      _checkNik();
    }
  }

  @override
  void dispose() {
    _stopPolling();
    _nikController.dispose();
    _descController.dispose();
    _chatInputController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _startPolling() {
    _stopPolling();
    _pollTimer = Timer.periodic(const Duration(seconds: 2), (_) {
      _fetchMessagesSilently();
    });
  }

  void _stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      Future.delayed(const Duration(milliseconds: 100), () {
        if (_scrollController.hasClients) {
          _scrollController.animateTo(
            _scrollController.position.maxScrollExtent,
            duration: const Duration(milliseconds: 250),
            curve: Curves.easeOut,
          );
        }
      });
    }
  }

  void _showToast(String message, ToastificationType type) {
    toastification.show(
      context: context,
      title: Text(message, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      type: type,
      style: ToastificationStyle.flat,
      autoCloseDuration: const Duration(seconds: 4),
    );
  }

  // ─── API: CHECK NIK ────────────────────────────────────────────────────────
  Future<void> _checkNik() async {
    final nik = _nikController.text.trim();
    if (nik.isEmpty) {
      _showToast('Silakan masukkan NIK atau Nomor Karyawan Anda.', ToastificationType.warning);
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _isCheckingNik = true;
      _employeeData = null;
    });

    try {
      final uri = Uri.parse('${Constants.baseUrl}/helpdesk/check-nik');
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'nik': nik}),
      );

      final data = jsonDecode(res.body);

      if (res.statusCode == 200 && data['status'] == 'success') {
        setState(() {
          _employeeData = data['data'];
          _employeeId = _employeeData!['employee_id'];
          _sessionToken = _employeeData!['session_token'];
          _hasDeviceBound = _employeeData!['has_device_bound'] == true;
        });
        _showToast('Data karyawan ditemukan: ${_employeeData!['name']}', ToastificationType.success);
      } else {
        final msg = data['message'] ?? 'NIK tidak ditemukan atau data non-aktif.';
        _showToast(msg, ToastificationType.error);
      }
    } catch (e) {
      _showToast('Gagal menghubungi server: $e', ToastificationType.error);
    } finally {
      if (mounted) setState(() => _isCheckingNik = false);
    }
  }

  // ─── API: INITIATE CHAT ───────────────────────────────────────────────────
  Future<void> _initiateChat() async {
    if (_employeeData == null || _employeeId == null || _sessionToken == null) {
      _showToast('Silakan cek dan validasi NIK terlebih dahulu.', ToastificationType.warning);
      return;
    }

    setState(() => _isStartingChat = true);

    try {
      final uri = Uri.parse('${Constants.baseUrl}/helpdesk/initiate-chat');
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'employee_id': _employeeId,
          'session_token': _sessionToken,
          'issue_type': _selectedIssueType,
          'description': _descController.text.trim(),
        }),
      );

      final data = jsonDecode(res.body);

      if (res.statusCode == 200 && data['status'] == 'success') {
        final chatData = data['data'];
        setState(() {
          _isInChatMode = true;
          _conversationId = chatData['conversation_id'];
          _messages = List<Map<String, dynamic>>.from(chatData['messages'] ?? []);
          _hasDeviceBound = chatData['has_device_bound'] == true;
        });

        _startPolling();
        _scrollToBottom();
      } else {
        final msg = data['message'] ?? 'Gagal memulai percakapan bantuan.';
        _showToast(msg, ToastificationType.error);
      }
    } catch (e) {
      _showToast('Terjadi kesalahan koneksi: $e', ToastificationType.error);
    } finally {
      if (mounted) setState(() => _isStartingChat = false);
    }
  }

  // ─── API: SILENT MESSAGE POLLING ──────────────────────────────────────────
  Future<void> _fetchMessagesSilently() async {
    if (_employeeId == null || _sessionToken == null || !_isInChatMode) return;

    try {
      final uri = Uri.parse('${Constants.baseUrl}/helpdesk/messages?employee_id=$_employeeId&session_token=$_sessionToken');
      final res = await http.get(uri, headers: {'Accept': 'application/json'});

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'success') {
          final newMsgs = List<Map<String, dynamic>>.from(data['data']['messages'] ?? []);
          final newDeviceBound = data['data']['has_device_bound'] == true;

          if (mounted) {
            final prevCount = _messages.length;
            setState(() {
              _messages = newMsgs;
              _hasDeviceBound = newDeviceBound;
            });

            if (newMsgs.length > prevCount) {
              _scrollToBottom();
            }
          }
        }
      }
    } catch (_) {}
  }

  // ─── API: SEND MESSAGE IN CHAT ────────────────────────────────────────────
  Future<void> _sendMessage() async {
    final text = _chatInputController.text.trim();
    if (text.isEmpty || _employeeId == null || _sessionToken == null) return;

    _chatInputController.clear();
    setState(() => _isSendingMessage = true);

    try {
      final uri = Uri.parse('${Constants.baseUrl}/helpdesk/send-message');
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'employee_id': _employeeId,
          'session_token': _sessionToken,
          'message': text,
        }),
      );

      final data = jsonDecode(res.body);
      if (res.statusCode == 200 && data['status'] == 'success') {
        _fetchMessagesSilently();
      } else {
        _showToast(data['message'] ?? 'Gagal mengirim pesan.', ToastificationType.error);
      }
    } catch (e) {
      _showToast('Gagal mengirim pesan: $e', ToastificationType.error);
    } finally {
      if (mounted) setState(() => _isSendingMessage = false);
    }
  }

  // ─── BUILD SCREEN ─────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = const Color(0xFF0284C7);
    final cardColor = isDarkMode ? const Color(0xFF1E293B) : Colors.white;
    final elevatedColor = isDarkMode ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC);
    final textColor = isDarkMode ? Colors.white : const Color(0xFF1E293B);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;

    return Scaffold(
      backgroundColor: elevatedColor,
      appBar: AppBar(
        backgroundColor: cardColor,
        elevation: 0.5,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: textColor, size: 20),
          onPressed: () {
            if (_isInChatMode) {
              _stopPolling();
              setState(() => _isInChatMode = false);
            } else {
              Navigator.pop(context);
            }
          },
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _isInChatMode ? 'Live Chat Bantuan IT' : 'Pusat Bantuan & Tiket',
              style: TextStyle(color: textColor, fontSize: 16, fontWeight: FontWeight.bold),
            ),
            Row(
              children: [
                Container(
                  width: 7,
                  height: 7,
                  decoration: const BoxDecoration(
                    color: Color(0xFF22C55E),
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 5),
                Text(
                  _isInChatMode
                      ? '${_employeeData?['name'] ?? ''} (NIK: ${_employeeData?['employee_no'] ?? ''})'
                      : 'Layanan Kendala Login & Akun',
                  style: TextStyle(color: subtitleColor, fontSize: 11),
                ),
              ],
            ),
          ],
        ),
        actions: [
          if (_isInChatMode)
            IconButton(
              icon: Icon(Icons.refresh_rounded, color: textColor),
              tooltip: 'Segarkan Chat',
              onPressed: _fetchMessagesSilently,
            ),
        ],
      ),
      body: SafeArea(
        child: _isInChatMode
            ? _buildLiveChatView(isDarkMode, cardColor, elevatedColor, textColor, subtitleColor, primaryColor)
            : _buildNikFormView(isDarkMode, cardColor, elevatedColor, textColor, subtitleColor, primaryColor),
      ),
    );
  }

  // ─── STEP 1 VIEW: NIK INPUT & CASE SELECTOR ───────────────────────────────
  Widget _buildNikFormView(
    bool isDarkMode,
    Color cardColor,
    Color elevatedColor,
    Color textColor,
    Color subtitleColor,
    Color primaryColor,
  ) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Banner Edukasi
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [primaryColor.withOpacity(0.12), const Color(0xFF38BDF8).withOpacity(0.06)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: primaryColor.withOpacity(0.3)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: primaryColor.withOpacity(0.15),
                  child: Icon(Icons.support_agent_rounded, color: primaryColor, size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Mengalami Kendala Login?',
                        style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                      ),
                      const SizedBox(height: 3),
                      Text(
                        'Masukkan NIK Anda untuk membuka sesi Live Chat Bantuan. Admin IT / HR siap mereset kata sandi atau membuka kunci perangkat Anda secara real-time.',
                        style: TextStyle(fontSize: 11.5, color: subtitleColor, height: 1.4),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Card Input NIK
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDarkMode ? Colors.white10 : Colors.grey.shade200),
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 8, offset: const Offset(0, 3)),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '1. Masukkan NIK / Nomor Karyawan',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _nikController,
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: textColor),
                        keyboardType: TextInputType.text,
                        decoration: InputDecoration(
                          hintText: 'Contoh: 123456 atau email',
                          hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                          prefixIcon: Icon(Icons.badge_outlined, color: primaryColor, size: 20),
                          filled: true,
                          fillColor: isDarkMode ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: isDarkMode ? Colors.white12 : Colors.grey.shade300),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: isDarkMode ? Colors.white12 : Colors.grey.shade300),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: primaryColor, width: 1.5),
                          ),
                        ),
                        onSubmitted: (_) => _checkNik(),
                      ),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: _isCheckingNik ? null : _checkNik,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryColor,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      child: _isCheckingNik
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Cek Data', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Preview Data Karyawan (Jika NIK valid)
          if (_employeeData != null) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF22C55E).withOpacity(0.4)),
                boxShadow: [
                  BoxShadow(color: const Color(0xFF22C55E).withOpacity(0.04), blurRadius: 8, offset: const Offset(0, 3)),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 20,
                        backgroundColor: const Color(0xFF22C55E).withOpacity(0.15),
                        child: const Icon(Icons.check_circle_rounded, color: Color(0xFF22C55E), size: 22),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _employeeData!['name'] ?? '-',
                              style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800, color: textColor),
                            ),
                            Text(
                              'NIK: ${_employeeData!['employee_no']} • ${_employeeData!['position_name']}',
                              style: TextStyle(fontSize: 11.5, color: subtitleColor),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const Divider(height: 20),
                  _buildProfileRow(Icons.business_rounded, 'Prinsiple / Cabang', '${_employeeData!['principal_name']} (${_employeeData!['branch_name']})', textColor, subtitleColor),
                  const SizedBox(height: 6),
                  _buildProfileRow(
                    Icons.phone_android_rounded,
                    'Status Perangkat HP',
                    _hasDeviceBound ? '⚠️ Terikat (${_employeeData!['device_name']})' : '✅ Bebas (Belum terikat HP lain)',
                    _hasDeviceBound ? const Color(0xFFE11D48) : const Color(0xFF16A34A),
                    subtitleColor,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Step 2: Pilih Kasus Kendala
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isDarkMode ? Colors.white10 : Colors.grey.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '2. Pilih Jenis Kendala Login',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  const SizedBox(height: 12),
                  _buildCaseOption(
                    title: 'Unlock Device (Ganti Handphone / Reset ID)',
                    subtitle: 'Gunakan ini jika Anda berganti smartphone atau muncul pesan "Akun sudah ditautkan perangkat lain".',
                    icon: Icons.phone_locked_rounded,
                    isSelected: _selectedIssueType == 'unlock_device',
                    accentColor: const Color(0xFF0284C7),
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    onTap: () => setState(() => _selectedIssueType = 'unlock_device'),
                  ),
                  const SizedBox(height: 10),
                  _buildCaseOption(
                    title: 'Lupa Password (Reset Kata Sandi)',
                    subtitle: 'Gunakan ini jika Anda lupa kata sandi akun dan tidak bisa login.',
                    icon: Icons.password_rounded,
                    isSelected: _selectedIssueType == 'forgot_password',
                    accentColor: const Color(0xFFEAB308),
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    onTap: () => setState(() => _selectedIssueType = 'forgot_password'),
                  ),
                  const SizedBox(height: 10),
                  _buildCaseOption(
                    title: 'Kendala Teknis Lainnya',
                    subtitle: 'Pertanyaan umum, akun terkunci, atau bantuan teknis operasional aplikasi.',
                    icon: Icons.help_outline_rounded,
                    isSelected: _selectedIssueType == 'other',
                    accentColor: const Color(0xFF8B5CF6),
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    onTap: () => setState(() => _selectedIssueType = 'other'),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Keterangan / Pesan Awal (Opsional)',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: textColor),
                  ),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _descController,
                    maxLines: 3,
                    style: TextStyle(fontSize: 13, color: textColor),
                    decoration: InputDecoration(
                      hintText: 'Contoh: HP lama saya hilang/rusak, mohon bantu reset device ID ke HP baru...',
                      hintStyle: TextStyle(fontSize: 12, color: Colors.grey.shade400),
                      filled: true,
                      fillColor: isDarkMode ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDarkMode ? Colors.white10 : Colors.grey.shade300)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: primaryColor)),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Tombol Mulai Chat
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isStartingChat ? null : _initiateChat,
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  elevation: 2,
                ),
                child: _isStartingChat
                    ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white))
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.chat_bubble_rounded, size: 18),
                          SizedBox(width: 8),
                          Text('Mulai Live Chat Bantuan', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                        ],
                      ),
              ),
            ),
            const SizedBox(height: 30),
          ],
        ],
      ),
    );
  }

  Widget _buildProfileRow(IconData icon, String label, String value, Color textColor, Color subtitleColor) {
    return Row(
      children: [
        Icon(icon, size: 16, color: subtitleColor),
        const SizedBox(width: 8),
        Text('$label: ', style: TextStyle(fontSize: 11.5, color: subtitleColor)),
        Expanded(
          child: Text(
            value,
            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: textColor),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildCaseOption({
    required String title,
    required String subtitle,
    required IconData icon,
    required bool isSelected,
    required Color accentColor,
    required Color textColor,
    required Color subtitleColor,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? accentColor.withOpacity(0.08) : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? accentColor : Colors.grey.withOpacity(0.25),
            width: isSelected ? 1.8 : 1,
          ),
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: accentColor.withOpacity(isSelected ? 0.2 : 0.1),
              child: Icon(icon, color: accentColor, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: isSelected ? accentColor : textColor),
                  ),
                  const SizedBox(height: 2),
                  Text(subtitle, style: TextStyle(fontSize: 10.5, color: subtitleColor, height: 1.3)),
                ],
              ),
            ),
            Radio<bool>(
              value: true,
              groupValue: isSelected,
              activeColor: accentColor,
              onChanged: (_) => onTap(),
            ),
          ],
        ),
      ),
    );
  }

  // ─── STEP 2 VIEW: LIVE CHAT STREAM ─────────────────────────────────────────
  Widget _buildLiveChatView(
    bool isDarkMode,
    Color cardColor,
    Color elevatedColor,
    Color textColor,
    Color subtitleColor,
    Color primaryColor,
  ) {
    return Column(
      children: [
        // Status Bar Banner: Perangkat Unlocked Notification
        if (!_hasDeviceBound) ...[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            color: const Color(0xFF22C55E).withOpacity(0.12),
            child: Row(
              children: [
                const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 18),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Perangkat HP Anda saat ini Bebas / Siap Login!',
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
                  ),
                ),
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  child: const Text('Login Sekarang', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF15803D))),
                ),
              ],
            ),
          ),
        ],

        // Messages List
        Expanded(
          child: _messages.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.mark_chat_read_outlined, size: 48, color: Colors.grey.withOpacity(0.4)),
                      const SizedBox(height: 8),
                      Text('Memulai sesi percakapan...', style: TextStyle(color: subtitleColor, fontSize: 12)),
                    ],
                  ),
                )
              : ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(16),
                  itemCount: _messages.length,
                  itemBuilder: (context, index) {
                    final msg = _messages[index];
                    final isEmployee = (msg['sender_type'] ?? '') == 'employee';
                    final text = msg['message'] ?? '';
                    final isTicketHeader = text.contains('[TIKET BANTUAN KARYAWAN]') || text.contains('[SISTEM HELPDESK]');
                    final timeStr = _formatMessageTime(msg['created_at']);

                    if (isTicketHeader) {
                      return _buildTicketCard(text, timeStr, isDarkMode, cardColor, primaryColor);
                    }

                    return Align(
                      alignment: isEmployee ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.78),
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color: isEmployee ? primaryColor : cardColor,
                          borderRadius: BorderRadius.only(
                            topLeft: const Radius.circular(16),
                            topRight: const Radius.circular(16),
                            bottomLeft: Radius.circular(isEmployee ? 16 : 4),
                            bottomRight: Radius.circular(isEmployee ? 4 : 16),
                          ),
                          boxShadow: [
                            BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 4, offset: const Offset(0, 2)),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: isEmployee ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                          children: [
                            if (!isEmployee) ...[
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.support_agent_rounded, size: 13, color: primaryColor),
                                  const SizedBox(width: 4),
                                  Text('Admin IT / HR', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: primaryColor)),
                                ],
                              ),
                              const SizedBox(height: 4),
                            ],
                            Text(
                              text,
                              style: TextStyle(
                                fontSize: 13.5,
                                color: isEmployee ? Colors.white : textColor,
                                height: 1.35,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              timeStr,
                              style: TextStyle(
                                fontSize: 9.5,
                                color: isEmployee ? Colors.white70 : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
        ),

        // Chat Input Box
        Container(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
          decoration: BoxDecoration(
            color: cardColor,
            border: Border(top: BorderSide(color: isDarkMode ? Colors.white10 : Colors.grey.shade200)),
          ),
          child: Row(
            children: [
              Expanded(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: TextField(
                    controller: _chatInputController,
                    style: TextStyle(fontSize: 13.5, color: textColor),
                    decoration: InputDecoration(
                      hintText: 'Ketik pesan balasan...',
                      hintStyle: TextStyle(fontSize: 12.5, color: subtitleColor),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                    onSubmitted: (_) => _sendMessage(),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: _isSendingMessage ? null : _sendMessage,
                borderRadius: BorderRadius.circular(24),
                child: CircleAvatar(
                  radius: 20,
                  backgroundColor: primaryColor,
                  child: _isSendingMessage
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.send_rounded, color: Colors.white, size: 18),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildTicketCard(String text, String timeStr, bool isDarkMode, Color cardColor, Color primaryColor) {
    final isResolvedMsg = text.contains('berhasil');

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isResolvedMsg ? const Color(0xFF22C55E).withOpacity(0.08) : const Color(0xFF0284C7).withOpacity(0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isResolvedMsg ? const Color(0xFF22C55E).withOpacity(0.4) : primaryColor.withOpacity(0.3),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                isResolvedMsg ? Icons.check_circle_outline_rounded : Icons.confirmation_number_outlined,
                size: 16,
                color: isResolvedMsg ? const Color(0xFF16A34A) : primaryColor,
              ),
              const SizedBox(width: 6),
              Text(
                isResolvedMsg ? 'TINDAKAN ADMIN SELESAI' : 'TIKET BANTUAN LOGIN',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w800,
                  color: isResolvedMsg ? const Color(0xFF16A34A) : primaryColor,
                  letterSpacing: 0.3,
                ),
              ),
              const Spacer(),
              Text(timeStr, style: TextStyle(fontSize: 9.5, color: Colors.grey.shade500)),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            text,
            style: TextStyle(
              fontSize: 12.5,
              color: isDarkMode ? Colors.white70 : const Color(0xFF1E293B),
              height: 1.4,
              fontFamily: 'monospace',
            ),
          ),
        ],
      ),
    );
  }

  String _formatMessageTime(dynamic rawDate) {
    if (rawDate == null) return '';
    try {
      final dt = DateTime.parse(rawDate.toString()).toLocal();
      return DateFormat('HH:mm').format(dt);
    } catch (_) {
      return '';
    }
  }
}
