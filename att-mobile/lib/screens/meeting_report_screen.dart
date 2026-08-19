import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:toastification/toastification.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/meeting_model.dart';
import '../providers/attendance_provider.dart';
import '../providers/auth_provider.dart';

class MeetingReportScreen extends StatefulWidget {
  final MeetingModel meeting;

  const MeetingReportScreen({super.key, required this.meeting});

  @override
  State<MeetingReportScreen> createState() => _MeetingReportScreenState();
}

class _MeetingReportScreenState extends State<MeetingReportScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notesController = TextEditingController();

  XFile? _photoFile;
  bool _isSubmitting = false;

  Timer? _timer;
  Duration _duration = Duration.zero;

  @override
  void initState() {
    super.initState();
    _startTimer();
  }

  void _startTimer() {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    if (attProvider.meetingStartTime != null) {
      _duration = DateTime.now().difference(attProvider.meetingStartTime!);
    }

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          if (attProvider.meetingStartTime != null) {
            _duration = DateTime.now().difference(attProvider.meetingStartTime!);
          } else {
            _duration += const Duration(seconds: 1);
          }
        });
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _takePhoto() async {
    final ImagePicker picker = ImagePicker();
    try {
      final XFile? photo = await picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 60,
      );
      if (photo != null) {
        setState(() {
          _photoFile = photo;
        });
      }
    } catch (e) {
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Error'),
          description: const Text('Gagal membuka kamera'),
          autoCloseDuration: const Duration(seconds: 3),
        );
      }
    }
  }

  Future<void> _openMeetingLink(String url) async {
    try {
      final uri = Uri.parse(url.startsWith('http') ? url : 'https://$url');
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (e) {
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Gagal membuka link'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 3),
        );
      }
    }
  }

  Future<void> _submitReport() async {
    if (!_formKey.currentState!.validate()) return;

    if (_photoFile == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Silakan ambil foto bukti meeting terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Konfirmasi Meet-Out', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: const Text(
          'Apakah Anda yakin ingin menyelesaikan meeting dan mengirim laporan ini?',
          style: TextStyle(fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFE0473E),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Ya, Selesaikan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isSubmitting = true);

    try {
      double? lat;
      double? lng;
      try {
        final pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.medium);
        lat = pos.latitude;
        lng = pos.longitude;
      } catch (_) {}

      final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
      final result = await attProvider.meetOut(
        meetingId: widget.meeting.id,
        notes: _notesController.text.trim(),
        photoPath: _photoFile?.path,
        latitude: lat,
        longitude: lng,
        durationSeconds: _duration.inSeconds,
      );

      setState(() => _isSubmitting = false);

      if (result['success'] == true) {
        if (mounted) {
          toastification.show(
            context: context,
            type: ToastificationType.success,
            title: const Text('Sukses'),
            description: Text(result['message'] ?? 'Meeting selesai dicatat.'),
            autoCloseDuration: const Duration(seconds: 4),
          );
          Navigator.pop(context, true);
        }
      } else {
        if (mounted) {
          toastification.show(
            context: context,
            type: ToastificationType.error,
            title: const Text('Gagal'),
            description: Text(result['message'] ?? 'Terjadi kesalahan saat meet-out'),
            autoCloseDuration: const Duration(seconds: 4),
          );
        }
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Error'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }

  String _formatDuration(Duration d) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    final hours = twoDigits(d.inHours);
    final minutes = twoDigits(d.inMinutes.remainder(60));
    final seconds = twoDigits(d.inSeconds.remainder(60));
    return '$hours:$minutes:$seconds';
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;

    final inputDecoration = InputDecoration(
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
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
      hintStyle: TextStyle(color: isDarkMode ? Colors.grey.shade600 : Colors.grey.shade400, fontSize: 13),
    );

    return PopScope(
      canPop: false, // Locked screen during meet-in
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        toastification.show(
          context: context,
          type: ToastificationType.warning,
          title: const Text('Selesaikan Meeting'),
          description: const Text('Kirimkan laporan dan tekan tombol Meet-Out untuk menyelesaikan meeting.'),
          autoCloseDuration: const Duration(seconds: 3),
        );
      },
      child: Scaffold(
        backgroundColor: bgColor,
        appBar: AppBar(
          title: Text('Laporan Meeting', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor)),
          backgroundColor: bgColor,
          elevation: 0,
          automaticallyImplyLeading: false, // Hide back button for lock
          centerTitle: false,
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // 1. Durasi Timer Card
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                  decoration: BoxDecoration(
                    color: primaryColor.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: primaryColor.withValues(alpha: 0.25)),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.timer_outlined, size: 16, color: primaryColor),
                          const SizedBox(width: 6),
                          Text(
                            'DURASI MEETING BERJALAN',
                            style: TextStyle(
                              color: primaryColor,
                              fontWeight: FontWeight.bold,
                              fontSize: 11,
                              letterSpacing: 0.8,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Text(
                        _formatDuration(_duration),
                        style: TextStyle(
                          fontSize: 34,
                          fontWeight: FontWeight.bold,
                          color: primaryColor,
                          letterSpacing: 2,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Timer otomatis menghitung durasi sejak Meet-In',
                        style: TextStyle(color: subtitleColor, fontSize: 11),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                // 2. Info Jadwal Meeting Card
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                            decoration: BoxDecoration(
                              color: widget.meeting.isOnline
                                  ? const Color(0xFF0284C7).withValues(alpha: 0.12)
                                  : const Color(0xFF10B981).withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  widget.meeting.isOnline ? Icons.videocam : Icons.location_on,
                                  size: 13,
                                  color: widget.meeting.isOnline ? const Color(0xFF0284C7) : const Color(0xFF10B981),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  widget.meeting.isOnline ? 'MEETING ONLINE' : 'MEETING OFFLINE',
                                  style: TextStyle(
                                    fontSize: 10.5,
                                    fontWeight: FontWeight.bold,
                                    color: widget.meeting.isOnline ? const Color(0xFF0284C7) : const Color(0xFF10B981),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF59E0B).withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Row(
                              children: [
                                Icon(Icons.radio_button_checked, size: 10, color: Color(0xFFF59E0B)),
                                SizedBox(width: 4),
                                Text(
                                  'IN MEETING',
                                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Color(0xFFF59E0B)),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        widget.meeting.title,
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.schedule, size: 14, color: subtitleColor),
                          const SizedBox(width: 6),
                          Text(
                            '${widget.meeting.startTime} ${widget.meeting.endTime != null ? '- ${widget.meeting.endTime}' : ''} WIB',
                            style: TextStyle(fontSize: 12, color: subtitleColor, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(
                            widget.meeting.isOnline ? Icons.link : Icons.place_outlined,
                            size: 14,
                            color: subtitleColor,
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              widget.meeting.isOnline
                                  ? (widget.meeting.meetingLink ?? 'Link Meeting Online')
                                  : (widget.meeting.locationName ?? '-'),
                              style: TextStyle(fontSize: 12, color: subtitleColor),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      if (widget.meeting.isOnline &&
                          widget.meeting.meetingLink != null &&
                          widget.meeting.meetingLink!.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        InkWell(
                          onTap: () => _openMeetingLink(widget.meeting.meetingLink!),
                          borderRadius: BorderRadius.circular(10),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0284C7).withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFF0284C7).withValues(alpha: 0.3)),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.open_in_new, size: 14, color: Color(0xFF0284C7)),
                                const SizedBox(width: 6),
                                const Text(
                                  'Buka Link Meeting (Zoom/GMeet)',
                                  style: TextStyle(fontSize: 12, color: Color(0xFF0284C7), fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                // 3. Form Input Laporan Meeting
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Catatan / Notulensi Meeting *',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: textColor),
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _notesController,
                        maxLines: 4,
                        minLines: 3,
                        style: TextStyle(color: textColor, fontSize: 13),
                        decoration: inputDecoration.copyWith(
                          hintText: 'Tuliskan poin pembahasan, hasil keputusan, atau notulensi rapat...',
                        ),
                        validator: (value) =>
                            value == null || value.trim().isEmpty ? 'Catatan meeting wajib diisi' : null,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Foto Bukti Meeting (Kamera) *',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: textColor),
                      ),
                      const SizedBox(height: 8),

                      if (_photoFile != null) ...[
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Stack(
                            children: [
                              Image.file(
                                File(_photoFile!.path),
                                height: 180,
                                width: double.infinity,
                                fit: BoxFit.cover,
                              ),
                              Positioned(
                                top: 8,
                                right: 8,
                                child: InkWell(
                                  onTap: _takePhoto,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: Colors.black.withValues(alpha: 0.7),
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: const Row(
                                      children: [
                                        Icon(Icons.camera_alt, color: Colors.white, size: 14),
                                        SizedBox(width: 4),
                                        Text('Ambil Ulang', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ] else ...[
                        InkWell(
                          onTap: _takePhoto,
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
                            decoration: BoxDecoration(
                              color: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300, style: BorderStyle.solid),
                            ),
                            child: Column(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: primaryColor.withValues(alpha: 0.1),
                                    shape: BoxShape.circle,
                                  ),
                                  child: Icon(Icons.camera_alt, color: primaryColor, size: 24),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Ambil Foto Suasana / Hasil Meeting',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: textColor),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Wajib menggunakan kamera langsung sebagai bukti kehadiran rapat',
                                  style: TextStyle(fontSize: 10.5, color: subtitleColor),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // 4. Tombol Meet-Out & Kirim Laporan
                ElevatedButton.icon(
                  onPressed: _isSubmitting ? null : _submitReport,
                  icon: _isSubmitting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.stop_circle, color: Colors.white, size: 20),
                  label: Text(
                    _isSubmitting ? 'Mengirim Laporan...' : 'Meet-Out & Kirim Laporan',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.white),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFE0473E),
                    elevation: 2,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                ),

                const SizedBox(height: 30),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
