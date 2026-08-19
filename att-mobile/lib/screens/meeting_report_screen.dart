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

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Konfirmasi Meet-Out'),
        content: const Text('Apakah Anda yakin ingin menyelesaikan meeting dan mengirim laporan ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Ya, Selesaikan', style: TextStyle(color: Colors.white)),
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
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);

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
        appBar: AppBar(
          title: const Text('Laporan Hasil Meeting', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          automaticallyImplyLeading: false, // Hide back button for lock
          backgroundColor: primaryColor,
          foregroundColor: Colors.white,
          elevation: 0,
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Meeting Header Card
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: primaryColor.withOpacity(0.3)),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.04),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: widget.meeting.isOnline
                                  ? Colors.blue.withOpacity(0.15)
                                  : Colors.green.withOpacity(0.15),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  widget.meeting.isOnline ? Icons.videocam : Icons.location_on,
                                  size: 14,
                                  color: widget.meeting.isOnline ? Colors.blue : Colors.green,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  widget.meeting.isOnline ? 'MEETING ONLINE' : 'MEETING OFFLINE',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: widget.meeting.isOnline ? Colors.blue : Colors.green,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.orange.withOpacity(0.15),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Row(
                              children: [
                                Icon(Icons.radio_button_checked, size: 10, color: Colors.orange),
                                SizedBox(width: 4),
                                Text(
                                  'IN MEETING',
                                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.orange),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        widget.meeting.title,
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Icon(Icons.access_time, size: 14, color: subtitleColor),
                          const SizedBox(width: 5),
                          Text(
                            '${widget.meeting.startTime} ${widget.meeting.endTime != null ? '- ${widget.meeting.endTime}' : ''}',
                            style: TextStyle(fontSize: 12, color: subtitleColor, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(
                            widget.meeting.isOnline ? Icons.link : Icons.place,
                            size: 14,
                            color: subtitleColor,
                          ),
                          const SizedBox(width: 5),
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
                        const SizedBox(height: 10),
                        InkWell(
                          onTap: () => _openMeetingLink(widget.meeting.meetingLink!),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: Colors.blue.shade50,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.blue.shade200),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.open_in_new, size: 14, color: Colors.blue),
                                const SizedBox(width: 6),
                                Text(
                                  'Buka Link Meeting (Zoom / Meet)',
                                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.blue.shade800),
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

                // Live Duration Timer Card
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [primaryColor.withOpacity(0.08), primaryColor.withOpacity(0.02)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: primaryColor.withOpacity(0.3)),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.timer, size: 18, color: primaryColor),
                          const SizedBox(width: 6),
                          Text(
                            'DURASI MEETING BERJALAN',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1,
                              color: primaryColor,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _formatDuration(_duration),
                        style: TextStyle(
                          fontSize: 34,
                          fontWeight: FontWeight.w900,
                          color: primaryColor,
                          fontFamily: 'monospace',
                          letterSpacing: 2,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Timer otomatis menghitung durasi sejak Meet-In',
                        style: TextStyle(fontSize: 11, color: subtitleColor),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // Form Catatan / Notulensi
                Text(
                  'Catatan / Notulensi Meeting *',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _notesController,
                  maxLines: 4,
                  validator: (val) {
                    if (val == null || val.trim().isEmpty) {
                      return 'Catatan / hasil meeting wajib diisi.';
                    }
                    if (val.trim().length < 5) {
                      return 'Catatan minimal 5 karakter.';
                    }
                    return null;
                  },
                  decoration: InputDecoration(
                    hintText: 'Tuliskan hasil diskusi, kesepakatan, atau notulensi meeting...',
                    hintStyle: TextStyle(fontSize: 12, color: Colors.grey.shade400),
                    filled: true,
                    fillColor: cardColor,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey.shade300),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: primaryColor, width: 2),
                    ),
                  ),
                ),

                const SizedBox(height: 16),

                // Foto Bukti Meeting
                Text(
                  'Foto Bukti Meeting (Opsional / Kamera)',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                ),
                const SizedBox(height: 8),
                InkWell(
                  onTap: _takePhoto,
                  child: Container(
                    height: 130,
                    decoration: BoxDecoration(
                      color: cardColor,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300, style: BorderStyle.solid),
                    ),
                    child: _photoFile != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.file(
                              File(_photoFile!.path),
                              width: double.infinity,
                              height: 130,
                              fit: BoxFit.cover,
                            ),
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.camera_alt, size: 36, color: primaryColor),
                              const SizedBox(height: 8),
                              Text(
                                'Ambil Foto Bukti Meeting',
                                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: primaryColor),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Foto suasana rapat / screenshot online meeting',
                                style: TextStyle(fontSize: 10, color: subtitleColor),
                              ),
                            ],
                          ),
                  ),
                ),

                const SizedBox(height: 28),

                // Tombol Meet-Out & Kirim Laporan
                ElevatedButton(
                  onPressed: _isSubmitting ? null : _submitReport,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red.shade600,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    elevation: 2,
                  ),
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.stop_circle_outlined, color: Colors.white, size: 20),
                            SizedBox(width: 8),
                            Text(
                              'Meet-Out & Kirim Laporan',
                              style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
