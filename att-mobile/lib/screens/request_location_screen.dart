import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:toastification/toastification.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/utils/constants.dart';

class RequestLocationScreen extends StatefulWidget {
  const RequestLocationScreen({super.key});

  @override
  State<RequestLocationScreen> createState() => _RequestLocationScreenState();
}

class _RequestLocationScreenState extends State<RequestLocationScreen> {
  int _currentTab = 0; // 0: Form Pengajuan, 1: Riwayat Pengajuan

  // Form Controllers
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _mapsUrlController = TextEditingController();
  final _latController = TextEditingController();
  final _lngController = TextEditingController();
  final _addressController = TextEditingController();
  final _notesController = TextEditingController();
  String _selectedType = 'store';
  File? _selectedPhoto;

  bool _isExtracting = false;
  bool _isSubmitting = false;

  // History State
  bool _isLoadingHistory = false;
  List<dynamic> _historyList = [];

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _mapsUrlController.dispose();
    _latController.dispose();
    _lngController.dispose();
    _addressController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickImage(source: source, maxWidth: 1200, maxHeight: 1200, imageQuality: 80);
      if (picked != null) {
        setState(() {
          _selectedPhoto = File(picked.path);
        });
      }
    } catch (e) {
      _showToast('Gagal memilih gambar: $e', ToastificationType.error);
    }
  }

  Future<void> _extractCoordinates() async {
    final url = _mapsUrlController.text.trim();
    if (url.isEmpty) {
      _showToast('Silakan tempel Link Google Maps terlebih dahulu', ToastificationType.warning);
      return;
    }

    setState(() => _isExtracting = true);

    try {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/location-requests/parse-maps-url'),
        headers: {
          'Authorization': 'Bearer ${auth.token}',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'url': url}),
      );

      final data = jsonDecode(response.body);

      if (response.statusCode == 200 && data['status'] == 'success') {
        final coords = data['data'];
        setState(() {
          _latController.text = coords['latitude']?.toString() ?? '';
          _lngController.text = coords['longitude']?.toString() ?? '';
        });
        _showToast('Titik koordinat berhasil diekstrak!', ToastificationType.success);
      } else {
        _showToast(data['message'] ?? 'Gagal mengekstrak koordinat dari link tersebut', ToastificationType.error);
      }
    } catch (e) {
      _showToast('Terjadi kesalahan saat memproses link Google Maps', ToastificationType.error);
    } finally {
      setState(() => _isExtracting = false);
    }
  }

  Future<void> _submitRequest() async {
    if (!_formKey.currentState!.validate()) return;

    if (_latController.text.isEmpty || _lngController.text.isEmpty) {
      _showToast('Titik Latitude dan Longitude wajib diisi atau diekstrak dari Google Maps', ToastificationType.warning);
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final uri = Uri.parse('${Constants.baseUrl}/location-requests');
      final request = http.MultipartRequest('POST', uri);

      request.headers['Authorization'] = 'Bearer ${auth.token}';
      request.headers['Accept'] = 'application/json';

      request.fields['name'] = _nameController.text.trim();
      request.fields['type'] = _selectedType;
      request.fields['maps_url'] = _mapsUrlController.text.trim();
      request.fields['latitude'] = _latController.text.trim();
      request.fields['longitude'] = _lngController.text.trim();
      request.fields['address'] = _addressController.text.trim();
      request.fields['notes'] = _notesController.text.trim();

      if (_selectedPhoto != null) {
        request.files.add(await http.MultipartFile.fromPath('photo', _selectedPhoto!.path));
      }

      final streamedRes = await request.send();
      final res = await http.Response.fromStream(streamedRes);
      final data = jsonDecode(res.body);

      if (res.statusCode == 201 || (res.statusCode == 200 && data['status'] == 'success')) {
        _showToast('Pengajuan lokasi baru berhasil dikirim dan menunggu persetujuan Admin', ToastificationType.success);

        // Reset form
        _formKey.currentState?.reset();
        _nameController.clear();
        _mapsUrlController.clear();
        _latController.clear();
        _lngController.clear();
        _addressController.clear();
        _notesController.clear();
        setState(() {
          _selectedPhoto = null;
          _currentTab = 1;
        });

        _fetchHistory();
      } else {
        _showToast(data['message'] ?? 'Gagal mengirim pengajuan', ToastificationType.error);
      }
    } catch (e) {
      _showToast('Terjadi kesalahan: $e', ToastificationType.error);
    } finally {
      setState(() => _isSubmitting = false);
    }
  }

  Future<void> _fetchHistory() async {
    setState(() => _isLoadingHistory = true);

    try {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/location-requests'),
        headers: {
          'Authorization': 'Bearer ${auth.token}',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _historyList = data['data'] ?? [];
        });
      }
    } catch (_) {
      // Ignored
    } finally {
      setState(() => _isLoadingHistory = false);
    }
  }

  void _showToast(String message, ToastificationType type) {
    toastification.show(
      context: context,
      type: type,
      title: Text(message),
      autoCloseDuration: const Duration(seconds: 4),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF4F6F9);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade100;

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(
          'Request Lokasi Baru',
          style: TextStyle(
            color: textColor,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Column(
        children: [
          // ── Custom Segmented Tab Bar ────────────────────────────────────────
          Container(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _currentTab = 0),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _currentTab == 0 ? primaryColor : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.add_location_alt_rounded,
                              size: 16,
                              color: _currentTab == 0 ? Colors.white : subtitleColor,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              'Form Pengajuan',
                              style: TextStyle(
                                color: _currentTab == 0 ? Colors.white : textColor,
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: InkWell(
                    onTap: () {
                      setState(() => _currentTab = 1);
                      _fetchHistory();
                    },
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _currentTab == 1 ? primaryColor : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.history_rounded,
                              size: 16,
                              color: _currentTab == 1 ? Colors.white : subtitleColor,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              'Riwayat Pengajuan',
                              style: TextStyle(
                                color: _currentTab == 1 ? Colors.white : textColor,
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ── Content ────────────────────────────────────────────────────────
          Expanded(
            child: _currentTab == 0
                ? _buildFormView(primaryColor, isDarkMode, cardColor, textColor, subtitleColor, elevatedColor)
                : _buildHistoryView(primaryColor, isDarkMode, cardColor, textColor, subtitleColor),
          ),
        ],
      ),
    );
  }

  Widget _buildFormView(Color primaryColor, bool isDarkMode, Color cardColor, Color textColor, Color subtitleColor, Color elevatedColor) {
    final inputDecoration = InputDecoration(
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primaryColor, width: 1.5),
      ),
      labelStyle: TextStyle(color: subtitleColor, fontSize: 13),
      hintStyle: TextStyle(color: subtitleColor, fontSize: 13),
    );

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Instructions Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDarkMode ? const Color(0xFF1E293B) : const Color(0xFFF0FDF4),
                border: Border.all(color: isDarkMode ? const Color(0xFF334155) : const Color(0xFFBBF7D0)),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.lightbulb_rounded, color: isDarkMode ? const Color(0xFF38BDF8) : const Color(0xFF16A34A), size: 20),
                      const SizedBox(width: 8),
                      Text(
                        'Petunjuk Link Google Maps',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: isDarkMode ? const Color(0xFFF1F5F9) : const Color(0xFF166534),
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '1. Buka aplikasi Google Maps di HP Anda.\n'
                    '2. Cari nama toko / tahan titik lokasi toko di peta hingga muncul pin merah.\n'
                    '3. Tekan tombol Bagikan (Share) ➔ Salin Link (Copy Link).\n'
                    '4. Tempelkan link pada kolom di bawah lalu klik tombol "Ekstrak".',
                    style: TextStyle(
                      fontSize: 12.5,
                      color: isDarkMode ? const Color(0xFFCBD5E1) : const Color(0xFF15803D),
                      height: 1.45,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Form Fields Container
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.03),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Nama Toko
                  Text('Nama Toko / Lokasi Baru *', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _nameController,
                    style: TextStyle(color: textColor, fontSize: 14),
                    decoration: inputDecoration.copyWith(
                      hintText: 'Contoh: Toko Bangunan Sumber Rejeki',
                      prefixIcon: Icon(Icons.storefront_rounded, color: primaryColor, size: 20),
                    ),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Nama toko wajib diisi' : null,
                  ),
                  const SizedBox(height: 16),

                  // Tipe Lokasi
                  Text('Tipe Lokasi', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                  const SizedBox(height: 6),
                  DropdownButtonFormField<String>(
                    dropdownColor: cardColor,
                    style: TextStyle(color: textColor, fontSize: 14),
                    decoration: inputDecoration.copyWith(
                      prefixIcon: Icon(Icons.category_rounded, color: primaryColor, size: 20),
                    ),
                    value: _selectedType,
                    items: const [
                      DropdownMenuItem(value: 'store', child: Text('Toko / Outlet')),
                      DropdownMenuItem(value: 'client', child: Text('Client')),
                      DropdownMenuItem(value: 'office', child: Text('Kantor')),
                      DropdownMenuItem(value: 'project', child: Text('Project')),
                      DropdownMenuItem(value: 'warehouse', child: Text('Gudang')),
                      DropdownMenuItem(value: 'other', child: Text('Lainnya')),
                    ],
                    onChanged: (v) {
                      if (v != null) setState(() => _selectedType = v);
                    },
                  ),
                  const SizedBox(height: 16),

                  // Link Google Maps
                  Text('Link Google Maps', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _mapsUrlController,
                    style: TextStyle(color: textColor, fontSize: 13),
                    decoration: inputDecoration.copyWith(
                      hintText: 'https://maps.app.goo.gl/...',
                      prefixIcon: Icon(Icons.map_rounded, color: primaryColor, size: 20),
                      suffixIcon: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                        child: ElevatedButton(
                          onPressed: _isExtracting ? null : _extractCoordinates,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF16A34A),
                            foregroundColor: Colors.white,
                            elevation: 0,
                            padding: const EdgeInsets.symmetric(horizontal: 14),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          child: _isExtracting
                              ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Text('Ekstrak', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Coordinates (Latitude & Longitude)
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Latitude *', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _latController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true),
                              style: TextStyle(color: textColor, fontSize: 13),
                              decoration: inputDecoration.copyWith(
                                hintText: '-6.2088',
                                prefixIcon: Icon(Icons.explore_rounded, color: primaryColor, size: 18),
                              ),
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Wajib diisi' : null,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Longitude *', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _lngController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true),
                              style: TextStyle(color: textColor, fontSize: 13),
                              decoration: inputDecoration.copyWith(
                                hintText: '106.8456',
                                prefixIcon: Icon(Icons.explore_rounded, color: primaryColor, size: 18),
                              ),
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Wajib diisi' : null,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Alamat Lengkap
                  Text('Alamat Lengkap', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _addressController,
                    maxLines: 2,
                    style: TextStyle(color: textColor, fontSize: 13),
                    decoration: inputDecoration.copyWith(
                      hintText: 'Masukkan alamat lengkap toko...',
                      prefixIcon: Icon(Icons.location_on_rounded, color: primaryColor, size: 20),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Foto Toko
                  Text('Foto Toko / Lokasi (Opsional)', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                  const SizedBox(height: 8),
                  if (_selectedPhoto != null)
                    Stack(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Image.file(_selectedPhoto!, height: 160, width: double.infinity, fit: BoxFit.cover),
                        ),
                        Positioned(
                          top: 8,
                          right: 8,
                          child: GestureDetector(
                            onTap: () => setState(() => _selectedPhoto = null),
                            child: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                              child: const Icon(Icons.close, color: Colors.white, size: 16),
                            ),
                          ),
                        ),
                      ],
                    )
                  else
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _pickImage(ImageSource.camera),
                            icon: Icon(Icons.camera_alt_rounded, color: primaryColor, size: 18),
                            label: Text('Kamera', style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold, fontSize: 13)),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              side: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _pickImage(ImageSource.gallery),
                            icon: Icon(Icons.photo_library_rounded, color: primaryColor, size: 18),
                            label: Text('Galeri', style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold, fontSize: 13)),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              side: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                          ),
                        ),
                      ],
                    ),
                  const SizedBox(height: 16),

                  // Catatan
                  Text('Catatan Pengajuan (Opsional)', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13)),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: _notesController,
                    maxLines: 2,
                    style: TextStyle(color: textColor, fontSize: 13),
                    decoration: inputDecoration.copyWith(
                      hintText: 'Alasan atau keterangan penambahan toko...',
                      prefixIcon: Icon(Icons.notes_rounded, color: primaryColor, size: 20),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton.icon(
                onPressed: _isSubmitting ? null : _submitRequest,
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 2,
                ),
                icon: _isSubmitting
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.send_rounded, size: 18),
                label: Text(
                  _isSubmitting ? 'Mengirim Pengajuan...' : 'Kirim Pengajuan Lokasi',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                ),
              ),
            ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildHistoryView(Color primaryColor, bool isDarkMode, Color cardColor, Color textColor, Color subtitleColor) {
    if (_isLoadingHistory) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_historyList.isEmpty) {
      return RefreshIndicator(
        onRefresh: _fetchHistory,
        child: ListView(
          padding: const EdgeInsets.all(32.0),
          children: [
            const SizedBox(height: 80),
            Icon(Icons.location_off_rounded, size: 64, color: subtitleColor),
            const SizedBox(height: 16),
            Text(
              'Belum ada riwayat pengajuan lokasi',
              textAlign: TextAlign.center,
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: subtitleColor),
            ),
            const SizedBox(height: 8),
            Text(
              'Ajukan lokasi baru melalui tab Form Pengajuan',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12.5, color: subtitleColor),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchHistory,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _historyList.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final item = _historyList[index];
          final status = item['status'] ?? 'pending';
          final name = item['name'] ?? 'Toko';
          final address = item['address'] ?? '-';
          final notes = item['notes'];
          final adminNotes = item['admin_notes'];
          final mapsUrl = item['maps_url'];
          final lat = item['latitude'];
          final lng = item['longitude'];
          final createdAt = item['created_at'];

          String formattedDate = '';
          if (createdAt != null) {
            try {
              final dt = DateTime.parse(createdAt);
              formattedDate = DateFormat('dd MMM yyyy HH:mm').format(dt);
            } catch (_) {}
          }

          Color statusColor = const Color(0xFFD98A2B);
          String statusLabel = 'Menunggu Approval';
          IconData statusIcon = Icons.hourglass_top_rounded;

          if (status == 'approved') {
            statusColor = const Color(0xFF16A34A);
            statusLabel = 'Disetujui';
            statusIcon = Icons.check_circle_rounded;
          } else if (status == 'rejected') {
            statusColor = const Color(0xFFDC2626);
            statusLabel = 'Ditolak';
            statusIcon = Icons.cancel_rounded;
          }

          return Container(
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(statusIcon, color: statusColor, size: 14),
                          const SizedBox(width: 5),
                          Text(
                            statusLabel,
                            style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                    const Spacer(),
                    if (formattedDate.isNotEmpty)
                      Text(formattedDate, style: TextStyle(fontSize: 11.5, color: subtitleColor)),
                  ],
                ),
                const SizedBox(height: 12),
                Text(name, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor)),
                const SizedBox(height: 6),
                if (address != '-')
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.location_on_outlined, size: 16, color: subtitleColor),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(address, style: TextStyle(fontSize: 12.5, color: subtitleColor)),
                      ),
                    ],
                  ),
                if (notes != null && notes.toString().isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text('Catatan: $notes', style: TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: subtitleColor)),
                ],
                if (adminNotes != null && adminNotes.toString().isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: status == 'rejected' ? (isDarkMode ? const Color(0xFF3F1D1D) : const Color(0xFFFEF2F2)) : (isDarkMode ? const Color(0xFF1E293B) : const Color(0xFFF0F9FF)),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: status == 'rejected' ? Colors.red.shade200 : Colors.blue.shade200),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.admin_panel_settings_rounded, size: 16, color: status == 'rejected' ? Colors.red : Colors.blue),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'Catatan Admin: $adminNotes',
                            style: TextStyle(fontSize: 12, color: status == 'rejected' ? Colors.red.shade900 : Colors.blue.shade900, fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
                if (lat != null && lng != null) ...[
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: () async {
                      final gUrl = mapsUrl ?? 'https://www.google.com/maps?q=$lat,$lng';
                      final uri = Uri.parse(gUrl);
                      if (await canLaunchUrl(uri)) {
                        await launchUrl(uri, mode: LaunchMode.externalApplication);
                      }
                    },
                    child: Row(
                      children: [
                        Icon(Icons.open_in_new_rounded, size: 14, color: primaryColor),
                        const SizedBox(width: 5),
                        Text('Buka di Google Maps', style: TextStyle(color: primaryColor, fontSize: 12.5, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
