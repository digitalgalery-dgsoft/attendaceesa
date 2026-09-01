import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/utils/constants.dart';

class BapScreen extends StatefulWidget {
  const BapScreen({super.key});

  @override
  State<BapScreen> createState() => _BapScreenState();
}

class _BapScreenState extends State<BapScreen> {
  int _currentTab = 0; // 0: Form Pengajuan, 1: Riwayat Pengajuan

  // Form State
  final _formKey = GlobalKey<FormState>();
  final _reasonController = TextEditingController();
  
  bool _isLoadingEligible = false;
  List<dynamic> _eligibleDates = [];
  Map<String, dynamic>? _selectedDateData;

  TimeOfDay _checkinTime = const TimeOfDay(hour: 8, minute: 0);
  TimeOfDay _checkoutTime = const TimeOfDay(hour: 17, minute: 0);
  bool _includeCheckout = true;

  String _selectedCategory = 'app_error';
  final Map<String, String> _categories = {
    'app_error': '📱 Kendala Aplikasi (Error / Force Close)',
    'gps_network': '📡 Kendala Sinyal / Jaringan / GPS Map',
    'device_issue': '🔋 Kendala Handphone (Baterai Habis / Kamera Rusak)',
    'server_down': '⚠️ Server Error / Sedang Maintenance',
    'other': '📝 Kendala Operasional Lainnya',
  };

  File? _selectedPhoto;
  bool _isSubmitting = false;

  // History State
  bool _isLoadingHistory = false;
  List<dynamic> _historyList = [];

  @override
  void initState() {
    super.initState();
    _fetchEligibleDates();
    _fetchHistory();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  // ─── API: AMBIL TANGGAL TERJADWAL BELUM ABSEN ─────────────────────────────
  Future<void> _fetchEligibleDates() async {
    setState(() => _isLoadingEligible = true);
    final auth = Provider.of<AuthProvider>(context, listen: false);

    try {
      final res = await http.get(
        Uri.parse('${Constants.baseUrl}/baps/eligible-dates'),
        headers: {
          'Authorization': 'Bearer ${auth.token}',
          'Accept': 'application/json',
        },
      );

      if (res.statusCode == 200) {
        final json = jsonDecode(res.body);
        final list = (json['data'] as List? ?? []);
        setState(() {
          _eligibleDates = list;
          if (list.isNotEmpty) {
            _selectDate(list.first);
          } else {
            _selectedDateData = null;
          }
        });
      }
    } catch (e) {
      debugPrint('Error fetch eligible dates: $e');
    } finally {
      if (mounted) setState(() => _isLoadingEligible = false);
    }
  }

  void _selectDate(Map<String, dynamic> data) {
    setState(() {
      _selectedDateData = data;
      // Set default checkin / checkout from schedule
      final defIn = data['default_checkin']?.toString() ?? '08:00';
      final defOut = data['default_checkout']?.toString() ?? '17:00';
      try {
        final inParts = defIn.split(':');
        _checkinTime = TimeOfDay(hour: int.parse(inParts[0]), minute: int.parse(inParts[1]));
        final outParts = defOut.split(':');
        _checkoutTime = TimeOfDay(hour: int.parse(outParts[0]), minute: int.parse(outParts[1]));
      } catch (_) {}
    });
  }

  // ─── API: AMBIL RIWAYAT PENGAJUAN BAP ─────────────────────────────────────
  Future<void> _fetchHistory() async {
    setState(() => _isLoadingHistory = true);
    final auth = Provider.of<AuthProvider>(context, listen: false);

    try {
      final res = await http.get(
        Uri.parse('${Constants.baseUrl}/baps/history'),
        headers: {
          'Authorization': 'Bearer ${auth.token}',
          'Accept': 'application/json',
        },
      );

      if (res.statusCode == 200) {
        final json = jsonDecode(res.body);
        setState(() {
          _historyList = json['data'] as List? ?? [];
        });
      }
    } catch (e) {
      debugPrint('Error fetch bap history: $e');
    } finally {
      if (mounted) setState(() => _isLoadingHistory = false);
    }
  }

  // ─── FOTO PICKER ──────────────────────────────────────────────────────────
  Future<void> _pickPhoto(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickImage(
        source: source,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 85,
      );

      if (picked != null) {
        setState(() {
          _selectedPhoto = File(picked.path);
        });
      }
    } catch (e) {
      _showToast('Gagal mengambil gambar: $e', ToastificationType.error);
    }
  }

  // ─── TIME PICKER DIALOG ───────────────────────────────────────────────────
  Future<void> _selectTime(BuildContext context, bool isCheckin) async {
    final initialTime = isCheckin ? _checkinTime : _checkoutTime;
    final picked = await showTimePicker(
      context: context,
      initialTime: initialTime,
      builder: (context, child) {
        return MediaQuery(
          data: MediaQuery.of(context).copyWith(alwaysUse24HourFormat: true),
          child: child!,
        );
      },
    );

    if (picked != null) {
      setState(() {
        if (isCheckin) {
          _checkinTime = picked;
        } else {
          _checkoutTime = picked;
        }
      });
    }
  }

  String _formatTimeOfDay(TimeOfDay tod) {
    final h = tod.hour.toString().padLeft(2, '0');
    final m = tod.minute.toString().padLeft(2, '0');
    return '$h:$m';
  }

  // ─── SUBMIT BAP ───────────────────────────────────────────────────────────
  Future<void> _submitBap() async {
    if (_selectedDateData == null) {
      _showToast('Silakan pilih tanggal jadwal kerja terlebih dahulu', ToastificationType.warning);
      return;
    }

    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_selectedPhoto == null) {
      _showToast('Harap lampirkan bukti foto screenshot timestamp camera!', ToastificationType.warning);
      return;
    }

    setState(() => _isSubmitting = true);
    final auth = Provider.of<AuthProvider>(context, listen: false);

    try {
      final uri = Uri.parse('${Constants.baseUrl}/baps');
      final request = http.MultipartRequest('POST', uri);

      request.headers.addAll({
        'Authorization': 'Bearer ${auth.token}',
        'Accept': 'application/json',
      });

      request.fields['date'] = _selectedDateData!['date'].toString();
      request.fields['checkin_time'] = _formatTimeOfDay(_checkinTime);
      if (_includeCheckout) {
        request.fields['checkout_time'] = _formatTimeOfDay(_checkoutTime);
      }
      request.fields['issue_category'] = _selectedCategory;
      request.fields['reason'] = _reasonController.text.trim();
      if (_selectedDateData!['work_location_id'] != null) {
        request.fields['work_location_id'] = _selectedDateData!['work_location_id'].toString();
      }
      if (_selectedDateData!['schedule_id'] != null) {
        request.fields['schedule_id'] = _selectedDateData!['schedule_id'].toString();
      }

      request.files.add(await http.MultipartFile.fromPath('evidence', _selectedPhoto!.path));

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      final json = jsonDecode(response.body);

      if (response.statusCode == 200 || response.statusCode == 201) {
        _showToast('✅ Pengajuan BAP Berhasil Dikirim!', ToastificationType.success);
        
        // Reset form
        _reasonController.clear();
        setState(() {
          _selectedPhoto = null;
          _currentTab = 1; // Pindah ke tab riwayat
        });

        _fetchEligibleDates();
        _fetchHistory();
      } else {
        final msg = json['message'] ?? 'Gagal mengirim pengajuan BAP.';
        _showToast(msg, ToastificationType.error);
      }
    } catch (e) {
      _showToast('Terjadi kesalahan jaringan: $e', ToastificationType.error);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _showToast(String message, ToastificationType type) {
    toastification.show(
      context: context,
      title: Text(message),
      type: type,
      style: ToastificationStyle.flat,
      autoCloseDuration: const Duration(seconds: 4),
    );
  }

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
        title: Text(
          'Pengajuan BAP (Bukti Absen)',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 17,
            color: textColor,
          ),
        ),
        centerTitle: true,
        elevation: 0,
        backgroundColor: cardColor,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, color: textColor, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded, color: textColor),
            tooltip: 'Segarkan',
            onPressed: () {
              _fetchEligibleDates();
              _fetchHistory();
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Segmented Tab Selector
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 6),
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: isDarkMode ? Colors.white10 : Colors.grey.shade200,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.03),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: _buildTabButton(
                        title: 'Form Pengajuan',
                        icon: Icons.edit_document,
                        isSelected: _currentTab == 0,
                        primaryColor: primaryColor,
                        textColor: textColor,
                        onTap: () => setState(() => _currentTab = 0),
                      ),
                    ),
                    Expanded(
                      child: _buildTabButton(
                        title: 'Riwayat Pengajuan',
                        icon: Icons.history_rounded,
                        isSelected: _currentTab == 1,
                        primaryColor: primaryColor,
                        textColor: textColor,
                        onTap: () {
                          setState(() => _currentTab = 1);
                          _fetchHistory();
                        },
                      ),
                    ),
                  ],
                ),
              ),
            ),
            // Tab Content
            Expanded(
              child: _currentTab == 0
                  ? _buildFormTab(isDarkMode, cardColor, elevatedColor, textColor, subtitleColor, primaryColor)
                  : _buildHistoryTab(isDarkMode, cardColor, elevatedColor, textColor, subtitleColor, primaryColor),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTabButton({
    required String title,
    required IconData icon,
    required bool isSelected,
    required Color primaryColor,
    required Color textColor,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? primaryColor : Colors.transparent,
          borderRadius: BorderRadius.circular(9),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: primaryColor.withOpacity(0.3),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  )
                ]
              : null,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 15, color: isSelected ? Colors.white : textColor.withOpacity(0.6)),
            const SizedBox(width: 6),
            Text(
              title,
              style: TextStyle(
                fontSize: 11.5,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                color: isSelected ? Colors.white : textColor.withOpacity(0.6),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─── TAB 1: FORM PENGAJUAN BAP ────────────────────────────────────────────
  Widget _buildFormTab(
    bool isDarkMode,
    Color cardColor,
    Color elevatedColor,
    Color textColor,
    Color subtitleColor,
    Color primaryColor,
  ) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Banner Info Edukasi
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF0284C7).withOpacity(0.08),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFF0284C7).withOpacity(0.3)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.info_outline, color: Color(0xFF0284C7), size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Kapan menggunakan Formulir BAP?',
                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0284C7)),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'Gunakan formulir ini jika terjadi kendala teknis (aplikasi error, no signal, GPS/kamera gagal) sehingga tidak bisa check-in pada hari jadwal kerja Anda. Lampirkan screenshot timestamp camera sebagai bukti valid.',
                          style: TextStyle(fontSize: 10.5, color: subtitleColor, height: 1.35),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // ─── 1. PILIH TANGGAL JADWAL KERJA ─────────────────────────────
            _buildSectionHeader('1. Pilih Tanggal Jadwal Kerja', Icons.calendar_month, primaryColor, textColor),
            const SizedBox(height: 8),

            if (_isLoadingEligible)
              Container(
                padding: const EdgeInsets.all(20),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.grey.withOpacity(0.2)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: primaryColor)),
                    const SizedBox(width: 10),
                    Text('Memeriksa jadwal kerja Anda...', style: TextStyle(fontSize: 11.5, color: subtitleColor)),
                  ],
                ),
              )
            else if (_eligibleDates.isEmpty)
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.green.withOpacity(0.3)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.check_circle_outline, color: Colors.green, size: 28),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Tidak Ada Jadwal Terlewat',
                            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.green),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Semua tanggal jadwal kerja Anda dalam 30 hari terakhir telah memiliki absensi atau belum ada jadwal terlewat.',
                            style: TextStyle(fontSize: 10.5, color: subtitleColor),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              )
            else
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.grey.withOpacity(0.2)),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<Map<String, dynamic>>(
                    isExpanded: true,
                    value: _selectedDateData,
                    icon: Icon(Icons.keyboard_arrow_down, color: primaryColor),
                    items: _eligibleDates.map((item) {
                      final dateStr = item['formatted_date'] ?? item['date'];
                      final locName = item['work_location_name'] ?? '-';
                      final shiftName = item['shift_name'] ?? 'Shift';

                      return DropdownMenuItem<Map<String, dynamic>>(
                        value: item as Map<String, dynamic>,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              dateStr,
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '$locName • $shiftName',
                              style: TextStyle(fontSize: 10, color: subtitleColor),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) _selectDate(val);
                    },
                  ),
                ),
              ),

            const SizedBox(height: 16),

            // ─── 2. WAKTU PRESENSI ──────────────────────────────────────────
            _buildSectionHeader('2. Waktu Presensi yang Diajukan', Icons.access_time, primaryColor, textColor),
            const SizedBox(height: 8),

            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.grey.withOpacity(0.2)),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      // Jam Masuk
                      Expanded(
                        child: InkWell(
                          onTap: () => _selectTime(context, true),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                            decoration: BoxDecoration(
                              color: elevatedColor,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Colors.grey.withOpacity(0.2)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.login, size: 14, color: Colors.green.shade600),
                                    const SizedBox(width: 5),
                                    Text('Jam Masuk (In)', style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.bold)),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _formatTimeOfDay(_checkinTime),
                                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      // Jam Pulang
                      Expanded(
                        child: InkWell(
                          onTap: _includeCheckout ? () => _selectTime(context, false) : null,
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                            decoration: BoxDecoration(
                              color: elevatedColor,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Colors.grey.withOpacity(0.2)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.logout, size: 14, color: _includeCheckout ? Colors.red.shade600 : Colors.grey),
                                    const SizedBox(width: 5),
                                    Text('Jam Pulang (Out)', style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.bold)),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _includeCheckout ? _formatTimeOfDay(_checkoutTime) : '-',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: _includeCheckout ? textColor : Colors.grey,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      SizedBox(
                        height: 24,
                        width: 24,
                        child: Checkbox(
                          value: _includeCheckout,
                          activeColor: primaryColor,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
                          onChanged: (val) => setState(() => _includeCheckout = val ?? true),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text('Sertakan Jam Pulang (Check-out)', style: TextStyle(fontSize: 11, color: textColor)),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // ─── 3. KATEGORI KENDALA & ALASAN ──────────────────────────────
            _buildSectionHeader('3. Detail Kendala Teknis', Icons.error_outline, primaryColor, textColor),
            const SizedBox(height: 8),

            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.grey.withOpacity(0.2)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Kategori Kendala', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: textColor)),
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      color: elevatedColor,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.grey.withOpacity(0.2)),
                    ),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        isExpanded: true,
                        value: _selectedCategory,
                        items: _categories.entries.map((e) {
                          return DropdownMenuItem(
                            value: e.key,
                            child: Text(e.value, style: TextStyle(fontSize: 11.5, color: textColor)),
                          );
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) setState(() => _selectedCategory = val);
                        },
                      ),
                    ),
                  ),

                  const SizedBox(height: 12),

                  Text('Penjelasan / Catatan Kendala', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: textColor)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _reasonController,
                    maxLines: 3,
                    style: TextStyle(fontSize: 12, color: textColor),
                    decoration: InputDecoration(
                      hintText: 'Contoh: Sinyal hilang saat di dalam gedung dan aplikasi error saat menekan tombol check-in...',
                      hintStyle: TextStyle(fontSize: 11, color: subtitleColor),
                      filled: true,
                      fillColor: elevatedColor,
                      contentPadding: const EdgeInsets.all(12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.grey.withOpacity(0.2))),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: Colors.grey.withOpacity(0.2))),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide(color: primaryColor)),
                    ),
                    validator: (val) {
                      if (val == null || val.trim().length < 5) {
                        return 'Harap berikan penjelasan kendala minimal 5 karakter.';
                      }
                      return null;
                    },
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // ─── 4. UPLOAD BUKTI SCREENSHOT ────────────────────────────────
            _buildSectionHeader('4. Upload Bukti Screenshot', Icons.camera_alt_outlined, primaryColor, textColor),
            const SizedBox(height: 4),
            Text(
              'Unggah tangkapan layar (screenshot) dari aplikasi Timestamp Camera / GPS Map Camera yang memuat tanggal & jam kejadian.',
              style: TextStyle(fontSize: 10, color: subtitleColor),
            ),
            const SizedBox(height: 8),

            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: Colors.grey.withOpacity(0.2)),
              ),
              child: Column(
                children: [
                  if (_selectedPhoto != null) ...[
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: Stack(
                        children: [
                          Image.file(
                            _selectedPhoto!,
                            width: double.infinity,
                            height: 200,
                            fit: BoxFit.cover,
                          ),
                          Positioned(
                            top: 8,
                            right: 8,
                            child: GestureDetector(
                              onTap: () => setState(() => _selectedPhoto = null),
                              child: Container(
                                padding: const EdgeInsets.all(6),
                                decoration: const BoxDecoration(
                                  color: Colors.black54,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.close, color: Colors.white, size: 16),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 10),
                  ],

                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _pickPhoto(ImageSource.gallery),
                          icon: const Icon(Icons.photo_library, size: 16),
                          label: const Text('Pilih dari Galeri', style: TextStyle(fontSize: 11)),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: primaryColor,
                            side: BorderSide(color: primaryColor.withOpacity(0.4)),
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _pickPhoto(ImageSource.camera),
                          icon: const Icon(Icons.camera_alt, size: 16),
                          label: const Text('Ambil Kamera', style: TextStyle(fontSize: 11)),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: primaryColor,
                            side: BorderSide(color: primaryColor.withOpacity(0.4)),
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // ─── TOMBOL KIRIM PENGAJUAN ───────────────────────────────────
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: (_isSubmitting || _eligibleDates.isEmpty) ? null : _submitBap,
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  disabledBackgroundColor: Colors.grey.shade400,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 2,
                ),
                child: _isSubmitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.send_rounded, size: 18),
                          SizedBox(width: 8),
                          Text('Kirim Pengajuan BAP', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                        ],
                      ),
              ),
            ),

            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  // ─── TAB 2: RIWAYAT PENGAJUAN BAP ─────────────────────────────────────────
  Widget _buildHistoryTab(
    bool isDarkMode,
    Color cardColor,
    Color elevatedColor,
    Color textColor,
    Color subtitleColor,
    Color primaryColor,
  ) {
    if (_isLoadingHistory) {
      return Center(
        child: CircularProgressIndicator(color: primaryColor),
      );
    }

    if (_historyList.isEmpty) {
      return RefreshIndicator(
        onRefresh: _fetchHistory,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            SizedBox(height: MediaQuery.of(context).size.height * 0.25),
            Icon(Icons.assignment_turned_in_outlined, size: 64, color: Colors.grey.withOpacity(0.4)),
            const SizedBox(height: 12),
            Center(
              child: Text(
                'Belum Ada Pengajuan BAP',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
              ),
            ),
            const SizedBox(height: 4),
            Center(
              child: Text(
                'Riwayat pengajuan bukti absensi manual Anda akan muncul di sini.',
                style: TextStyle(fontSize: 11, color: subtitleColor),
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchHistory,
      child: ListView.builder(
        padding: const EdgeInsets.all(14),
        itemCount: _historyList.length,
        itemBuilder: (context, index) {
          final bap = _historyList[index];
          final dateStr = bap['formatted_date'] ?? bap['date'];
          final status = bap['status'] ?? 'pending';
          final statusLabel = bap['status_label'] ?? 'Menunggu Verifikasi';
          final checkinTime = bap['checkin_time'] ?? '-';
          final checkoutTime = bap['checkout_time'] ?? '-';
          final categoryLabel = bap['issue_category_label'] ?? 'Kendala Teknis';
          final reason = bap['reason'] ?? '-';
          final evidenceUrl = bap['evidence_url'];
          final rejectionReason = bap['rejection_reason'];
          final approvedAt = bap['approved_at'];
          final approvedBy = bap['approved_by_name'];

          Color badgeBg;
          Color badgeText;
          IconData statusIcon;

          if (status == 'approved') {
            badgeBg = Colors.green.shade50;
            badgeText = Colors.green.shade700;
            statusIcon = Icons.check_circle;
          } else if (status == 'rejected') {
            badgeBg = Colors.red.shade50;
            badgeText = Colors.red.shade700;
            statusIcon = Icons.cancel;
          } else {
            badgeBg = Colors.amber.shade50;
            badgeText = Colors.amber.shade800;
            statusIcon = Icons.hourglass_top;
          }

          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.grey.withOpacity(0.2)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header: Tanggal & Badge Status
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      dateStr,
                      style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: badgeBg,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(statusIcon, size: 12, color: badgeText),
                          const SizedBox(width: 4),
                          Text(
                            statusLabel,
                            style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: badgeText),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 8),

                // Jam In & Out Card
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.login, size: 12, color: Colors.green),
                          const SizedBox(width: 4),
                          Text('In: $checkinTime', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.green)),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    if (checkoutTime != null && checkoutTime != '-')
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.orange.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.logout, size: 12, color: Colors.orange),
                            const SizedBox(width: 4),
                            Text('Out: $checkoutTime', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.orange)),
                          ],
                        ),
                      ),
                    const Spacer(),
                    Text(categoryLabel, style: TextStyle(fontSize: 9.5, color: subtitleColor, fontWeight: FontWeight.w500)),
                  ],
                ),

                const SizedBox(height: 8),
                Text(
                  'Catatan: $reason',
                  style: TextStyle(fontSize: 10.5, color: textColor.withOpacity(0.85)),
                ),

                if (evidenceUrl != null) ...[
                  const SizedBox(height: 8),
                  InkWell(
                    onTap: () {
                      _showImageDialog(context, evidenceUrl);
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: elevatedColor,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: Colors.grey.withOpacity(0.2)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.image, size: 13, color: primaryColor),
                          const SizedBox(width: 5),
                          Text('Lihat Bukti Foto Screenshot ↗', style: TextStyle(fontSize: 10, color: primaryColor, fontWeight: FontWeight.bold)),
                        ],
                      ),
                    ),
                  ),
                ],

                // Jika Ditolak: Kotak Alasan Penolakan
                if (status == 'rejected' && rejectionReason != null && rejectionReason.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Alasan Penolakan:', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.red.shade800)),
                        const SizedBox(height: 2),
                        Text(rejectionReason, style: TextStyle(fontSize: 10, color: Colors.red.shade900)),
                      ],
                    ),
                  ),
                ],

                // Jika Disetujui: Info Verifikator
                if (status == 'approved' && approvedAt != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    'Disetujui oleh: ${approvedBy ?? 'Admin'} pada $approvedAt',
                    style: TextStyle(fontSize: 9, color: Colors.green.shade700, fontStyle: FontStyle.italic),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  void _showImageDialog(BuildContext context, String imageUrl) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: Image.network(
                imageUrl,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) => Container(
                  padding: const EdgeInsets.all(20),
                  color: Colors.white,
                  child: const Text('Gagal memuat gambar bukti.'),
                ),
              ),
            ),
            IconButton(
              icon: const Icon(Icons.close, color: Colors.white),
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon, Color primaryColor, Color textColor) {
    return Row(
      children: [
        Icon(icon, size: 16, color: primaryColor),
        const SizedBox(width: 6),
        Text(title, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
      ],
    );
  }
}
