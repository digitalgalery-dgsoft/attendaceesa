import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:intl/intl.dart';

class VisitReportScreen extends StatefulWidget {
  const VisitReportScreen({super.key});

  @override
  State<VisitReportScreen> createState() => _VisitReportScreenState();
}

class _VisitReportScreenState extends State<VisitReportScreen> {
  final _formKey = GlobalKey<FormState>();
  
  final _metWithController = TextEditingController();
  final _positionController = TextEditingController();
  final _notesController = TextEditingController();
  final _actionController = TextEditingController();
  final _targetQtyController = TextEditingController();
  final _actualQtyController = TextEditingController();
  final _targetValueController = TextEditingController();
  final _actualValueController = TextEditingController();
  String _targetType = 'Target Qty';
  DateTime? _deadline;
  
  bool _isIssue = false;
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
    _positionController.dispose();
    _notesController.dispose();
    _actionController.dispose();
    _targetQtyController.dispose();
    _actualQtyController.dispose();
    _targetValueController.dispose();
    _actualValueController.dispose();
    super.dispose();
  }

  Future<void> _takePhoto() async {
    final ImagePicker picker = ImagePicker();
    try {
      final XFile? photo = await picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 50,
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
    if (!_formKey.currentState!.validate()) return;
    
    if (_deadline == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Silakan pilih Deadline terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      setState(() {}); // trigger error text update
      return;
    }
    
    if (_photoFile == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Silakan ambil foto kunjungan terlebih dahulu'),
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
        issue: _isIssue ? _notesController.text : null,
        actionTaken: _isIssue ? _actionController.text : null,
        notes: !_isIssue ? _notesController.text : null,
        photoPath: _photoFile!.path,
        metWith: _metWithController.text,
        position: _positionController.text,
        targetType: _targetType,
        targetQty: _targetType == 'Target Qty' || _targetType == 'Keduanya' ? _targetQtyController.text : null,
        actualQty: _targetType == 'Target Qty' || _targetType == 'Keduanya' ? _actualQtyController.text : null,
        targetValue: _targetType == 'Target Value' || _targetType == 'Keduanya' ? _targetValueController.text : null,
        actualValue: _targetType == 'Target Value' || _targetType == 'Keduanya' ? _actualValueController.text : null,
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
        Navigator.pop(context);
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
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Error'),
        description: Text(e.toString()),
        autoCloseDuration: const Duration(seconds: 3),
      );
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
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);

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
          title: Text('Laporan Visit', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
          backgroundColor: bgColor,
          elevation: 0,
          automaticallyImplyLeading: false, // Hide back button
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
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: primaryColor.withOpacity(0.3)),
                  ),
                  child: Column(
                    children: [
                      Text(
                        'Durasi Visit',
                        style: TextStyle(color: primaryColor, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _formatDuration(_duration),
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: primaryColor,
                          letterSpacing: 2,
                        ),
                      ),
                    ],
                  ),
                ),
                
                const SizedBox(height: 24),
                
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
                        'Detail Pertemuan',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor),
                      ),
                      const SizedBox(height: 16),
                      
                      TextFormField(
                        controller: _metWithController,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(labelText: 'Bertemu Dengan *'),
                        validator: (value) => value == null || value.isEmpty ? 'Wajib diisi' : null,
                      ),
                      const SizedBox(height: 16),
                      
                      TextFormField(
                        controller: _positionController,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(labelText: 'Jabatan *'),
                        validator: (value) => value == null || value.isEmpty ? 'Wajib diisi' : null,
                      ),
                      
                      const SizedBox(height: 16),
                      
                      DropdownButtonFormField<String>(
                        value: _targetType,
                        decoration: inputDecoration.copyWith(labelText: 'Target Report *'),
                        items: ['Target Qty', 'Target Value', 'Keduanya']
                            .map((e) => DropdownMenuItem(value: e, child: Text(e, style: TextStyle(color: textColor))))
                            .toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() {
                              _targetType = val;
                            });
                          }
                        },
                      ),
                      const SizedBox(height: 16),
                      
                      if (_targetType == 'Target Qty' || _targetType == 'Keduanya') ...[
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: _targetQtyController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(labelText: 'Target (Qty) *'),
                                validator: (value) => value == null || value.isEmpty ? 'Wajib diisi' : null,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: TextFormField(
                                controller: _actualQtyController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(labelText: 'Actual (Qty) *'),
                                validator: (value) => value == null || value.isEmpty ? 'Wajib diisi' : null,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                      ],
                      
                      if (_targetType == 'Target Value' || _targetType == 'Keduanya') ...[
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                controller: _targetValueController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(labelText: 'Target (Value) *'),
                                validator: (value) => value == null || value.isEmpty ? 'Wajib diisi' : null,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: TextFormField(
                                controller: _actualValueController,
                                style: TextStyle(color: textColor),
                                keyboardType: TextInputType.number,
                                decoration: inputDecoration.copyWith(labelText: 'Actual (Value) *'),
                                validator: (value) => value == null || value.isEmpty ? 'Wajib diisi' : null,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                      ],
                      
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
                            labelText: 'Deadline *',
                            errorText: _deadline == null ? 'Wajib diisi' : null,
                          ),
                          child: Text(
                            _deadline == null ? 'Pilih Deadline' : DateFormat('dd MMM yyyy').format(_deadline!),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                      ),
                      
                      const SizedBox(height: 24),
                      Divider(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                      const SizedBox(height: 16),
                      
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Apakah ada Issue?',
                            style: TextStyle(fontWeight: FontWeight.bold, color: textColor),
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
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _notesController,
                          style: TextStyle(color: textColor),
                          decoration: inputDecoration.copyWith(labelText: 'Deskripsi Isu *'),
                          maxLines: 3,
                          validator: (value) => _isIssue && (value == null || value.isEmpty) ? 'Wajib diisi' : null,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _actionController,
                          style: TextStyle(color: textColor),
                          decoration: inputDecoration.copyWith(labelText: 'Action Taken'),
                          maxLines: 2,
                        ),
                      ] else ...[
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _notesController,
                          style: TextStyle(color: textColor),
                          decoration: inputDecoration.copyWith(labelText: 'Catatan Umum (Opsional)'),
                          maxLines: 3,
                        ),
                      ],
                    ],
                  ),
                ),
                
                const SizedBox(height: 24),
                
                // Photo
                Text(
                  'Foto Kunjungan *',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor),
                ),
                const SizedBox(height: 12),
                GestureDetector(
                  onTap: _takePhoto,
                  child: Container(
                    height: 200,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: cardColor,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: _photoFile == null ? Colors.red.shade300 : Colors.transparent,
                        width: 1,
                      ),
                    ),
                    child: _photoFile != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(16),
                            child: Image.file(
                              File(_photoFile!.path),
                              fit: BoxFit.cover,
                            ),
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.camera_alt, size: 48, color: subtitleColor),
                              const SizedBox(height: 8),
                              Text('Ambil Foto Kamera', style: TextStyle(color: subtitleColor)),
                            ],
                          ),
                  ),
                ),
                
                const SizedBox(height: 32),
                
                // Submit Button
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton(
                    onPressed: _isSubmitting ? null : _submitReport,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    child: _isSubmitting
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : const Text('Submit & Visit-Out', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
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
}
