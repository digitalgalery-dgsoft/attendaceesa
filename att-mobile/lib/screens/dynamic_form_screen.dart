import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/models/report_template_model.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/services/watermark_camera_service.dart';
import 'package:att_mobile/widgets/signature_pad_dialog.dart';

class DynamicFormScreen extends StatefulWidget {
  final ReportTemplateModel template;
  final String? storeName;
  final int? workLocationId;
  final int? itineraryItemId;

  const DynamicFormScreen({
    super.key,
    required this.template,
    this.storeName,
    this.workLocationId,
    this.itineraryItemId,
  });

  @override
  State<DynamicFormScreen> createState() => _DynamicFormScreenState();
}

class _DynamicFormScreenState extends State<DynamicFormScreen> {
  final _formKey = GlobalKey<FormState>();

  // State nilai form dinamis
  final Map<String, dynamic> _formValues = {};
  final Map<String, File> _photoFiles = {};
  final Map<String, String> _watermarkTexts = {};

  // Controllers untuk text & currency fields
  final Map<String, TextEditingController> _controllers = {};

  // GPS & Status
  double? _latitude;
  double? _longitude;
  String? _address;
  bool _isFetchingLocation = false;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _initializeForm();
    _fetchCurrentLocation();
  }

  void _initializeForm() {
    for (final field in widget.template.fields) {
      final fieldKey = field.id.toString();

      // Inisialisasi default values
      if (field.defaultValue != null && field.defaultValue!.isNotEmpty) {
        _formValues[fieldKey] = field.defaultValue;
      }

      if (['text', 'textarea', 'number', 'integer', 'currency', 'percentage'].contains(field.fieldType)) {
        final ctrl = TextEditingController(text: field.defaultValue ?? '');
        _controllers[fieldKey] = ctrl;
      } else if (field.fieldType == 'checkbox') {
        _formValues[fieldKey] = field.defaultValue == 'true' || field.defaultValue == '1';
      } else if (field.fieldType == 'rating' || field.fieldType == 'rating_star') {
        _formValues[fieldKey] = int.tryParse(field.defaultValue ?? '5') ?? 5;
      } else if (field.fieldType == 'multi_select' || field.fieldType == 'checkbox_group') {
        _formValues[fieldKey] = <String>[];
      }
    }
  }

  Future<void> _fetchCurrentLocation() async {
    setState(() => _isFetchingLocation = true);
    try {
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 8),
      );
      if (mounted) {
        setState(() {
          _latitude = position.latitude;
          _longitude = position.longitude;
          _isFetchingLocation = false;
        });
      }
    } catch (e) {
      debugPrint('Location error: $e');
      if (mounted) setState(() => _isFetchingLocation = false);
    }
  }

  @override
  void dispose() {
    for (final ctrl in _controllers.values) {
      ctrl.dispose();
    }
    super.dispose();
  }

  // Format currency otomatis (Rp 1.500.000)
  void _formatCurrency(String fieldKey, String value) {
    String cleanDigits = value.replaceAll(RegExp(r'[^0-9]'), '');
    if (cleanDigits.isEmpty) {
      _controllers[fieldKey]?.value = const TextEditingValue(text: '');
      _formValues[fieldKey] = 0;
      return;
    }

    int intValue = int.tryParse(cleanDigits) ?? 0;
    _formValues[fieldKey] = intValue;

    final formatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
    String formatted = formatter.format(intValue);

    _controllers[fieldKey]?.value = TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }

  // Ambil foto dengan Geotag Watermark
  Future<void> _takeWatermarkedPhoto(ReportFormFieldModel field) async {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final employeeName = auth.employeeData?['full_name'] ?? 'Promotor';
    final employeeNik = auth.employeeData?['nik'] ?? '';

    final result = await WatermarkCameraService.captureWithWatermark(
      employeeName: employeeName,
      employeeNik: employeeNik,
      storeName: widget.storeName,
      latitude: _latitude,
      longitude: _longitude,
    );

    if (result != null && mounted) {
      setState(() {
        _photoFiles[field.id.toString()] = result.file;
        _watermarkTexts[field.id.toString()] = result.watermarkText;
        _formValues[field.id.toString()] = result.file.path;
      });

      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Foto Berhasil Diambil'),
        description: const Text('Geotag & Watermark lokasi berhasil dibubuhkan.'),
        autoCloseDuration: const Duration(seconds: 2),
      );
    }
  }

  // Buka Dialog Tanda Tangan
  Future<void> _openSignaturePad(ReportFormFieldModel field) async {
    final File? signatureFile = await showDialog<File>(
      context: context,
      builder: (context) => SignaturePadDialog(
        title: field.fieldLabel,
        signerRole: widget.storeName ?? 'Validasi Lapangan',
      ),
    );

    if (signatureFile != null && mounted) {
      setState(() {
        _photoFiles[field.id.toString()] = signatureFile;
        _formValues[field.id.toString()] = signatureFile.path;
      });

      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Tanda Tangan Disimpan'),
        autoCloseDuration: const Duration(seconds: 2),
      );
    }
  }

  // Submit Form
  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Isian Belum Lengkap'),
        description: const Text('Mohon lengkapi kolom yang bertanda bintang (*)'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    // Validasi foto & tanda tangan required
    for (final field in widget.template.fields) {
      if (field.isRequired) {
        final fieldKey = field.id.toString();
        if (['photo', 'camera_photo', 'multi_photo', 'signature'].contains(field.fieldType)) {
          if (!_photoFiles.containsKey(fieldKey) || _photoFiles[fieldKey] == null) {
            toastification.show(
              context: context,
              type: ToastificationType.warning,
              title: Text('Wajib Mengisi ${field.fieldLabel}'),
              description: const Text('Silakan ambil foto bukti atau buat tanda tangan terlebih dahulu.'),
              autoCloseDuration: const Duration(seconds: 3),
            );
            return;
          }
        }
      }
    }

    setState(() => _isSubmitting = true);

    final auth = Provider.of<AuthProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final token = auth.token;

    if (token == null) {
      setState(() => _isSubmitting = false);
      return;
    }

    // Sinkronkan text controllers ke formValues
    _controllers.forEach((key, ctrl) {
      if (!_formValues.containsKey(key) || _formValues[key] == null) {
        _formValues[key] = ctrl.text;
      }
    });

    final result = await repProvider.submitReport(
      token: token,
      templateId: widget.template.id,
      templateTitle: widget.template.title,
      storeName: widget.storeName,
      workLocationId: widget.workLocationId,
      itineraryItemId: widget.itineraryItemId,
      latitude: _latitude,
      longitude: _longitude,
      address: _address,
      values: _formValues,
      photoFiles: _photoFiles,
      watermarkTexts: _watermarkTexts,
    );

    setState(() => _isSubmitting = false);

    if (result['success'] == true && mounted) {
      toastification.show(
        context: context,
        type: result['is_offline'] == true ? ToastificationType.info : ToastificationType.success,
        title: Text(result['is_offline'] == true ? 'Tersimpan Offline' : 'Laporan Terkirim'),
        description: Text(result['message']),
        autoCloseDuration: const Duration(seconds: 4),
      );
      Navigator.of(context).pop(true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final themeColor = Color(int.tryParse(widget.template.color.replaceAll('#', '0xFF')) ?? primaryColor.value);

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          widget.template.title,
          style: TextStyle(color: textColor, fontSize: 16, fontWeight: FontWeight.bold),
        ),
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          children: [
            // Header Info Laporan & Toko
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.03),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(9),
                        decoration: BoxDecoration(
                          color: themeColor.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(Icons.storefront_rounded, color: themeColor, size: 22),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.storeName ?? 'Laporan Operasional Toko',
                              style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: textColor),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              widget.template.code,
                              style: TextStyle(fontSize: 11, color: subtitleColor, fontFamily: 'monospace', fontWeight: FontWeight.w600),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (widget.template.description != null && widget.template.description!.isNotEmpty) ...[
                    const SizedBox(height: 10),
                    Text(
                      widget.template.description!,
                      style: TextStyle(fontSize: 12, color: subtitleColor, height: 1.35),
                    ),
                  ],
                  const Divider(height: 20),
                  Row(
                    children: [
                      Icon(Icons.location_on, size: 14, color: _latitude != null ? Colors.green : Colors.orange),
                      const SizedBox(width: 5),
                      Text(
                        _latitude != null
                            ? 'GPS Terhubung (${_latitude!.toStringAsFixed(4)}, ${_longitude!.toStringAsFixed(4)})'
                            : 'Mencari sinyal GPS...',
                        style: TextStyle(fontSize: 11, color: _latitude != null ? Colors.green : Colors.orange, fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Dynamic Form Fields List
            ...widget.template.fields.map((field) => _buildFieldWidget(
                  field,
                  themeColor,
                  cardColor,
                  textColor,
                  subtitleColor,
                  elevatedColor,
                  isDarkMode,
                )),

            const SizedBox(height: 16),

            // Submit Button
            ElevatedButton.icon(
              onPressed: _isSubmitting ? null : _submitForm,
              icon: _isSubmitting
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.send_rounded, color: Colors.white, size: 18),
              label: Text(
                _isSubmitting ? 'Mengirim Laporan...' : 'Kirim Laporan Sekarang',
                style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: themeColor,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 2,
              ),
            ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildFieldWidget(
    ReportFormFieldModel field,
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
  ) {
    final fieldKey = field.id.toString();

    Widget inputWidget;

    switch (field.fieldType) {
      case 'textarea':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          maxLines: 3,
          style: TextStyle(color: textColor, fontSize: 13),
          decoration: _inputDecoration(field.placeholder ?? 'Tuliskan keterangan lengkap...', elevatedColor, isDarkMode),
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? 'Wajib diisi' : null,
          onChanged: (v) => _formValues[fieldKey] = v,
        );
        break;

      case 'number':
      case 'integer':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          keyboardType: TextInputType.number,
          style: TextStyle(color: textColor, fontSize: 13),
          decoration: _inputDecoration(field.placeholder ?? '0', elevatedColor, isDarkMode),
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? 'Wajib diisi' : null,
          onChanged: (v) => _formValues[fieldKey] = num.tryParse(v),
        );
        break;

      case 'currency':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          keyboardType: TextInputType.number,
          style: TextStyle(color: textColor, fontSize: 13, fontWeight: FontWeight.w600),
          decoration: _inputDecoration('Rp 0', elevatedColor, isDarkMode),
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? 'Wajib diisi' : null,
          onChanged: (v) => _formatCurrency(fieldKey, v),
        );
        break;

      case 'select':
      case 'dropdown':
        inputWidget = DropdownButtonFormField<String>(
          value: _formValues[fieldKey],
          decoration: _inputDecoration('Pilih salah satu opsi', elevatedColor, isDarkMode),
          dropdownColor: cardColor,
          style: TextStyle(color: textColor, fontSize: 13),
          items: field.options
              .map((opt) => DropdownMenuItem(
                    value: opt,
                    child: Text(opt, style: TextStyle(fontSize: 12.5, color: textColor)),
                  ))
              .toList(),
          validator: (v) => field.isRequired && v == null ? 'Wajib dipilih' : null,
          onChanged: (v) => setState(() => _formValues[fieldKey] = v),
        );
        break;

      case 'multi_select':
      case 'checkbox_group':
      case 'sku_list':
        final List<String> currentSelected = List<String>.from(_formValues[fieldKey] ?? []);
        inputWidget = Wrap(
          spacing: 8,
          runSpacing: 8,
          children: field.options.map((opt) {
            final isSelected = currentSelected.contains(opt);
            return FilterChip(
              label: Text(
                opt,
                style: TextStyle(
                  fontSize: 11.5,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  color: isSelected ? Colors.white : textColor,
                ),
              ),
              selected: isSelected,
              selectedColor: themeColor,
              backgroundColor: elevatedColor,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              onSelected: (selected) {
                setState(() {
                  if (selected) {
                    currentSelected.add(opt);
                  } else {
                    currentSelected.remove(opt);
                  }
                  _formValues[fieldKey] = currentSelected;
                });
              },
            );
          }).toList(),
        );
        break;

      case 'radio':
        final currentRadioVal = _formValues[fieldKey];
        inputWidget = Column(
          children: field.options.map((opt) {
            final isSelected = currentRadioVal == opt;
            return Container(
              margin: const EdgeInsets.only(bottom: 6),
              decoration: BoxDecoration(
                color: isSelected ? themeColor.withOpacity(0.08) : elevatedColor,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: isSelected ? themeColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                ),
              ),
              child: RadioListTile<String>(
                title: Text(opt, style: TextStyle(fontSize: 12.5, color: textColor, fontWeight: isSelected ? FontWeight.bold : FontWeight.normal)),
                value: opt,
                groupValue: currentRadioVal,
                activeColor: themeColor,
                dense: true,
                contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                onChanged: (val) => setState(() => _formValues[fieldKey] = val),
              ),
            );
          }).toList(),
        );
        break;

      case 'checkbox':
        final bool isChecked = _formValues[fieldKey] == true;
        inputWidget = SwitchListTile(
          title: Text(field.placeholder ?? 'Aktifkan jika sesuai', style: TextStyle(fontSize: 13, color: textColor)),
          value: isChecked,
          activeColor: themeColor,
          contentPadding: EdgeInsets.zero,
          onChanged: (val) => setState(() => _formValues[fieldKey] = val),
        );
        break;

      case 'rating':
      case 'rating_star':
        final int currentRating = _formValues[fieldKey] ?? 5;
        inputWidget = Row(
          children: List.generate(5, (index) {
            final starVal = index + 1;
            return IconButton(
              icon: Icon(
                starVal <= currentRating ? Icons.star_rounded : Icons.star_outline_rounded,
                color: Colors.amber,
                size: 32,
              ),
              onPressed: () => setState(() => _formValues[fieldKey] = starVal),
            );
          }),
        );
        break;

      case 'photo':
      case 'camera_photo':
      case 'multi_photo':
        final photoFile = _photoFiles[fieldKey];
        inputWidget = Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (photoFile != null) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Stack(
                  children: [
                    Image.file(photoFile, height: 180, width: double.infinity, fit: BoxFit.cover),
                    Positioned(
                      bottom: 0,
                      left: 0,
                      right: 0,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        color: Colors.black.withOpacity(0.7),
                        child: Text(
                          _watermarkTexts[fieldKey] ?? 'Watermark GPS Validated',
                          style: const TextStyle(color: Colors.white, fontSize: 10, height: 1.3),
                          maxLines: 2,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
            ],
            OutlinedButton.icon(
              onPressed: () => _takeWatermarkedPhoto(field),
              icon: const Icon(Icons.camera_alt_rounded, size: 18),
              label: Text(photoFile == null ? 'Ambil Foto Bukti (Watermark Geotag)' : 'Ambil Ulang Foto'),
              style: OutlinedButton.styleFrom(
                foregroundColor: themeColor,
                side: BorderSide(color: themeColor),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 11),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        );
        break;

      case 'signature':
        final sigFile = _photoFiles[fieldKey];
        inputWidget = Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (sigFile != null) ...[
              Container(
                height: 100,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Image.file(sigFile, fit: BoxFit.contain),
              ),
              const SizedBox(height: 10),
            ],
            OutlinedButton.icon(
              onPressed: () => _openSignaturePad(field),
              icon: const Icon(Icons.draw_rounded, size: 18),
              label: Text(sigFile == null ? 'Buat Tanda Tangan PIC / Toko' : 'Tanda Tangan Ulang'),
              style: OutlinedButton.styleFrom(
                foregroundColor: themeColor,
                side: BorderSide(color: themeColor),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 11),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        );
        break;

      case 'text':
      default:
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          style: TextStyle(color: textColor, fontSize: 13),
          decoration: _inputDecoration(field.placeholder ?? 'Masukkan ${field.fieldLabel.toLowerCase()}', elevatedColor, isDarkMode),
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? 'Wajib diisi' : null,
          onChanged: (v) => _formValues[fieldKey] = v,
        );
        break;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  field.fieldLabel,
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                ),
              ),
              if (field.isRequired)
                const Text(' *', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 14)),
            ],
          ),
          const SizedBox(height: 10),
          inputWidget,
        ],
      ),
    );
  }

  InputDecoration _inputDecoration(String hint, Color fillColor, bool isDarkMode) {
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400, fontSize: 12.5),
      filled: true,
      fillColor: fillColor,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFF0F52BA), width: 1.5),
      ),
    );
  }
}
