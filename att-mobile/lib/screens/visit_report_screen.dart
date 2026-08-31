import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/screens/main_screen.dart';
import 'package:att_mobile/screens/reporting_hub_screen.dart';
import 'package:intl/intl.dart';

class VisitReportScreen extends StatefulWidget {
  const VisitReportScreen({super.key});

  @override
  State<VisitReportScreen> createState() => _VisitReportScreenState();
}

class _VisitReportScreenState extends State<VisitReportScreen> {
  final _formKey = GlobalKey<FormState>();

  // ─── 7 Point Inhouse Form Controllers ───
  // 1. BA yang Ditemui
  final _metWithController = TextEditingController();
  
  // 2. Prinsiple
  final _principalController = TextEditingController();
  
  // 3. Grooming bagaimana?
  String _groomingCondition = 'Rapi';
  
  // 4. Target vs Actual Pencapaian
  String _targetType = 'Target Qty';
  final _targetQtyController = TextEditingController();
  final _actualQtyController = TextEditingController();
  final _targetValueController = TextEditingController();
  final _actualValueController = TextEditingController();
  DateTime? _deadline;
  
  // 5. Promo yang berlangsung apa
  final _activePromoController = TextEditingController();
  
  // 6. Barang yang OOS?
  final _oosProductsController = TextEditingController();
  
  // 7. Issue lainnya
  final _otherIssuesController = TextEditingController();
  bool _isIssue = false;
  final _actionController = TextEditingController();

  XFile? _photoFile;
  bool _isSubmitting = false;

  Timer? _timer;
  Duration _duration = Duration.zero;

  @override
  void initState() {
    super.initState();
    _startTimer();
    _initDefaults();
  }

  void _initDefaults() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final principalName = auth.employeeData?['principal']?['name'] ?? auth.employeeData?['principal_name'];
      if (principalName != null && principalName.toString().isNotEmpty) {
        _principalController.text = principalName.toString();
      }
    });
  }

  void _startTimer() {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    if (attProvider.visitStartTime != null) {
      _duration = DateTime.now().difference(attProvider.visitStartTime!);
    }

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          if (attProvider.visitStartTime != null) {
            _duration = DateTime.now().difference(attProvider.visitStartTime!);
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
    _metWithController.dispose();
    _principalController.dispose();
    _targetQtyController.dispose();
    _actualQtyController.dispose();
    _targetValueController.dispose();
    _actualValueController.dispose();
    _activePromoController.dispose();
    _oosProductsController.dispose();
    _otherIssuesController.dispose();
    _actionController.dispose();
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

  Future<void> _submitReport() async {
    if (!_formKey.currentState!.validate()) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Lengkapi Form'),
        description: const Text('Mohon isi kolom yang wajib diisi.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (_photoFile == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Silakan ambil foto bukti kunjungan terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);

    try {
      final success = await attProvider.submitVisitReport(
        authProvider: authProvider,
        metWith: _metWithController.text.trim(),
        position: _principalController.text.trim().isNotEmpty ? 'Prinsiple: ${_principalController.text.trim()}' : 'BA Toko',
        groomingCondition: _groomingCondition,
        activePromo: _activePromoController.text.trim(),
        oosProducts: _oosProductsController.text.trim(),
        otherIssues: _otherIssuesController.text.trim(),
        issue: _isIssue ? _otherIssuesController.text.trim() : null,
        actionTaken: _isIssue ? _actionController.text.trim() : null,
        notes: _otherIssuesController.text.trim(),
        photoPath: _photoFile!.path,
        targetType: _targetType,
        targetQty: (_targetType == 'Target Qty' || _targetType == 'Keduanya') ? _targetQtyController.text.trim() : null,
        actualQty: (_targetType == 'Target Qty' || _targetType == 'Keduanya') ? _actualQtyController.text.trim() : null,
        targetValue: (_targetType == 'Target Value' || _targetType == 'Keduanya') ? _targetValueController.text.trim() : null,
        actualValue: (_targetType == 'Target Value' || _targetType == 'Keduanya') ? _actualValueController.text.trim() : null,
        deadline: _deadline != null ? DateFormat('yyyy-MM-dd').format(_deadline!) : null,
      );

      if (!mounted) return;

      if (success) {
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Berhasil'),
          description: const Text('Laporan Visit & Visit-Out berhasil disimpan'),
          autoCloseDuration: const Duration(seconds: 3),
        );
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const MainScreen()),
          (route) => false,
        );
      } else {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Gagal'),
          description: Text(attProvider.error),
          autoCloseDuration: const Duration(seconds: 5),
        );
      }
    } catch (e) {
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Error'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 3),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  String _formatDuration(Duration d) {
    String twoDigits(int n) => n.toString().padLeft(2, "0");
    String hours = twoDigits(d.inHours);
    String minutes = twoDigits(d.inMinutes.remainder(60));
    String seconds = twoDigits(d.inSeconds.remainder(60));
    return "$hours:$minutes:$seconds";
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final auth = Provider.of<AuthProvider>(context);
    final repProvider = Provider.of<DynamicReportingProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final isRatecard = (auth.employeeData?['is_inhouse'] == false);
    final hasCustomReporting = isRatecard &&
        ((auth.employeeData?['has_reporting_templates'] == 1 || auth.employeeData?['has_reporting_templates'] == true) ||
            (auth.employeeData?['principal_id'] != null && repProvider.templates.isNotEmpty));

    final inputDecoration = InputDecoration(
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
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
    );

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        toastification.show(
          context: context,
          type: ToastificationType.warning,
          title: const Text('Peringatan'),
          description: const Text('Silakan submit laporan untuk menyelesaikan Visit (Visit-Out).'),
          autoCloseDuration: const Duration(seconds: 3),
        );
      },
      child: Scaffold(
        backgroundColor: bgColor,
        appBar: AppBar(
          title: Text(
            isRatecard ? 'Laporan Kunjungan' : 'Laporan Visit Inhouse',
            style: TextStyle(fontWeight: FontWeight.bold, color: textColor),
          ),
          backgroundColor: bgColor,
          elevation: 0,
          automaticallyImplyLeading: false,
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Timer Card
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: primaryColor.withOpacity(0.25)),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.timer_outlined, size: 16, color: primaryColor),
                          const SizedBox(width: 6),
                          Text(
                            'Durasi Visit Kunjungan',
                            style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _formatDuration(_duration),
                        style: TextStyle(
                          fontSize: 30,
                          fontWeight: FontWeight.bold,
                          color: primaryColor,
                          letterSpacing: 2,
                        ),
                      ),
                    ],
                  ),
                ),

                // Banner Pengalihan untuk Ratecard jika memiliki Custom Reporting
                if (hasCustomReporting) ...[
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.amber.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Colors.amber.shade400.withOpacity(0.5)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.amber.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Icon(Icons.assignment_rounded, color: Colors.amber, size: 22),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Form Pelaporan Prinsiple Tersedia',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: textColor),
                              ),
                              Text(
                                'Anda dapat mengisi form pelaporan spesifik prinsiple di menu Field Reporting.',
                                style: TextStyle(fontSize: 11, color: subtitleColor),
                              ),
                            ],
                          ),
                        ),
                        ElevatedButton(
                          onPressed: () {
                            final att = Provider.of<AttendanceProvider>(context, listen: false);
                            final destinationName = att.todayItinerary?['name']?.toString() ?? att.todayItinerary?['destination']?.toString();
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => ReportingHubScreen(
                                  storeName: destinationName,
                                ),
                              ),
                            );
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.amber.shade700,
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            elevation: 0,
                          ),
                          child: const Text('Buka Reporting', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  ),
                ],

                const SizedBox(height: 16),

                // ─── 7 POINT INHOUSE FORM SECTION ───
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(16),
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
                        children: [
                          Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: primaryColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Icon(Icons.playlist_add_check_circle_rounded, color: primaryColor, size: 20),
                          ),
                          const SizedBox(width: 10),
                          Text(
                            'Form Evaluasi Kunjungan Inhouse',
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: textColor),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),

                      // 1. BA yang Ditemui
                      _buildSectionHeader('1. BA yang Ditemui', textColor, primaryColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _metWithController,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Nama BA / PIC yang Ditemui *',
                          hintText: 'Contoh: Rina, Budi, dll',
                        ),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Wajib diisi' : null,
                      ),
                      const SizedBox(height: 18),

                      // 2. Prinsiple apa
                      _buildSectionHeader('2. Prinsiple apa', textColor, primaryColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _principalController,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Nama Prinsiple / Brand *',
                          hintText: 'Contoh: Dulux, Fonterra, Wings, dll',
                        ),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Wajib diisi' : null,
                      ),
                      const SizedBox(height: 18),

                      // 3. Grooming bagaimana?
                      _buildSectionHeader('3. Grooming bagaimana?', textColor, primaryColor),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String>(
                        value: _groomingCondition,
                        decoration: inputDecoration.copyWith(labelText: 'Kondisi Grooming BA *'),
                        items: [
                          'Sangat Rapi & Sesuai SOP',
                          'Rapi',
                          'Kurang Rapi / Tidak Seragam',
                          'Tidak Sesuai SOP',
                        ]
                            .map((e) => DropdownMenuItem(value: e, child: Text(e, style: TextStyle(color: textColor, fontSize: 13))))
                            .toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _groomingCondition = val;
                            });
                          }
                        },
                      ),
                      const SizedBox(height: 18),

                      // 4. Target vs Actual Pencapaian
                      _buildSectionHeader('4. Target vs Actual Pencapaian', textColor, primaryColor),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String>(
                        value: _targetType,
                        decoration: inputDecoration.copyWith(labelText: 'Tipe Target *'),
                        items: ['Target Qty', 'Target Value', 'Keduanya']
                            .map((e) => DropdownMenuItem(value: e, child: Text(e, style: TextStyle(color: textColor, fontSize: 13))))
                            .toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _targetType = val;
                            });
                          }
                        },
                      ),
                      const SizedBox(height: 12),

                      if (_targetType == 'Target Qty' || _targetType == 'Keduanya') ...[
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: _targetQtyController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(
                                  labelText: 'Target (Qty)',
                                  hintText: '0',
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: TextFormField(
                                controller: _actualQtyController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(
                                  labelText: 'Actual (Qty)',
                                  hintText: '0',
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                      ],

                      if (_targetType == 'Target Value' || _targetType == 'Keduanya') ...[
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: _targetValueController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(
                                  labelText: 'Target (Value Rp)',
                                  hintText: 'Rp 0',
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: TextFormField(
                                controller: _actualValueController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(
                                  labelText: 'Actual (Value Rp)',
                                  hintText: 'Rp 0',
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                      ],

                      // Deadline target (opsional)
                      InkWell(
                        onTap: () async {
                          final date = await showDatePicker(
                            context: context,
                            initialDate: _deadline ?? DateTime.now(),
                            firstDate: DateTime.now(),
                            lastDate: DateTime.now().add(const Duration(days: 365)),
                            builder: (context, child) {
                              return Theme(
                                data: Theme.of(context).copyWith(
                                  colorScheme: isDarkMode
                                      ? ColorScheme.dark(primary: primaryColor, surface: cardColor)
                                      : ColorScheme.light(primary: primaryColor),
                                ),
                                child: child!,
                              );
                            },
                          );
                          if (date != null) {
                            setState(() {
                              _deadline = date;
                            });
                          }
                        },
                        child: InputDecorator(
                          decoration: inputDecoration.copyWith(
                            labelText: 'Deadline Evaluasi Target',
                          ),
                          child: Text(
                            _deadline == null ? 'Pilih Deadline (Opsional)' : DateFormat('dd MMMM yyyy').format(_deadline!),
                            style: TextStyle(color: _deadline == null ? subtitleColor : textColor),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),

                      // 5. Promo yang Berlangsung apa
                      _buildSectionHeader('5. Promo yang Berlangsung apa', textColor, primaryColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _activePromoController,
                        style: TextStyle(color: textColor),
                        maxLines: 2,
                        decoration: inputDecoration.copyWith(
                          labelText: 'Promo yang Sedang Berjalan',
                          hintText: 'Contoh: Diskon 10% Dulux Weathershield, Gift tumbler, dll',
                        ),
                      ),
                      const SizedBox(height: 18),

                      // 6. Barang yang OOS?
                      _buildSectionHeader('6. Barang yang OOS?', textColor, primaryColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _oosProductsController,
                        style: TextStyle(color: textColor),
                        maxLines: 2,
                        decoration: inputDecoration.copyWith(
                          labelText: 'Barang yang Out of Stock (OOS)',
                          hintText: 'Contoh: Pentalite 2.5L Brilliant White, Catylac 25kg, dll',
                        ),
                      ),
                      const SizedBox(height: 18),

                      // 7. Issue lainnya
                      _buildSectionHeader('7. Issue lainnya', textColor, primaryColor),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _otherIssuesController,
                        style: TextStyle(color: textColor),
                        maxLines: 3,
                        decoration: inputDecoration.copyWith(
                          labelText: 'Issue / Catatan Tambahan',
                          hintText: 'Tuliskan isu lapangan atau catatan penting lainnya di sini...',
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Switch Ada Isu Kritis
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Tandai sebagai Issue Penting / Masalah?',
                            style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 13),
                          ),
                          Switch(
                            value: _isIssue,
                            onChanged: (val) {
                              setState(() {
                                _isIssue = val;
                              });
                            },
                            activeColor: primaryColor,
                          ),
                        ],
                      ),

                      if (_isIssue) ...[
                        const SizedBox(height: 10),
                        TextFormField(
                          controller: _actionController,
                          style: TextStyle(color: textColor),
                          decoration: inputDecoration.copyWith(
                            labelText: 'Tindakan yang Diambil (Action Taken)',
                            hintText: 'Tindakan sementara atau eskalasi...',
                          ),
                          maxLines: 2,
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // Foto Bukti Kunjungan
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Foto Bukti Kunjungan & BA *',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: textColor),
                      ),
                      const SizedBox(height: 12),
                      GestureDetector(
                        onTap: _takePhoto,
                        child: Container(
                          height: 190,
                          width: double.infinity,
                          decoration: BoxDecoration(
                            color: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: _photoFile == null ? primaryColor.withOpacity(0.4) : Colors.transparent,
                              width: 1.5,
                              style: _photoFile == null ? BorderStyle.solid : BorderStyle.none,
                            ),
                          ),
                          child: _photoFile != null
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(14),
                                  child: Image.file(
                                    File(_photoFile!.path),
                                    fit: BoxFit.cover,
                                  ),
                                )
                              : Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(12),
                                      decoration: BoxDecoration(
                                        color: primaryColor.withOpacity(0.12),
                                        shape: BoxShape.circle,
                                      ),
                                      child: Icon(Icons.camera_alt_rounded, size: 36, color: primaryColor),
                                    ),
                                    const SizedBox(height: 10),
                                    Text(
                                      'Ambil Foto Bukti Kunjungan',
                                      style: TextStyle(fontWeight: FontWeight.bold, color: primaryColor, fontSize: 13),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      'Arahkan kamera ke BA toko atau display produk',
                                      style: TextStyle(color: subtitleColor, fontSize: 11),
                                    ),
                                  ],
                                ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Submit Button
                SizedBox(
                  width: double.infinity,
                  height: 52,
                  child: ElevatedButton(
                    onPressed: _isSubmitting ? null : _submitReport,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 2,
                    ),
                    child: _isSubmitting
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                          )
                        : const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.check_circle_outline, size: 20),
                              SizedBox(width: 8),
                              Text('Submit Laporan & Visit-Out', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                            ],
                          ),
                  ),
                ),
                const SizedBox(height: 32),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, Color textColor, Color primaryColor) {
    return Text(
      title,
      style: TextStyle(
        fontWeight: FontWeight.bold,
        fontSize: 13.5,
        color: textColor,
      ),
    );
  }
}
