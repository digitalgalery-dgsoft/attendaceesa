import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/models/report_template_model.dart';
import 'package:att_mobile/models/report_submission_model.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';
import 'package:att_mobile/services/watermark_camera_service.dart';
import 'package:att_mobile/widgets/signature_pad_dialog.dart';

class DynamicFormScreen extends StatefulWidget {
  final ReportTemplateModel template;
  final String? storeName;
  final int? workLocationId;
  final int? itineraryItemId;
  final ReportSubmissionModel? editSubmission;

  const DynamicFormScreen({
    super.key,
    required this.template,
    this.storeName,
    this.workLocationId,
    this.itineraryItemId,
    this.editSubmission,
  });

  @override
  State<DynamicFormScreen> createState() => _DynamicFormScreenState();
}

class _DynamicFormScreenState extends State<DynamicFormScreen> {
  final _formKey = GlobalKey<FormState>();

  // Lokasi Terikat Sesuai Check-in / Visit Aktif
  Map<String, dynamic>? _selectedLocation;
  int? _selectedWorkLocationId;
  String _selectedStoreName = '';
  double? _calculatedDistance;
  bool _isWithinRadius = false;
  double _allowedRadiusMeter = 100.0;

  // State nilai form dinamis
  final Map<String, dynamic> _formValues = {};
  final Map<String, File> _photoFiles = {};
  final Map<String, List<File>> _multiPhotoFiles = {};
  final Map<String, String> _watermarkTexts = {};
  final Map<String, String> _existingPhotoUrls = {};
  final Map<String, List<String>> _existingMultiPhotoUrls = {};

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
    _selectedWorkLocationId = widget.workLocationId;
    _selectedStoreName = widget.storeName ?? '';

    _initializeForm();
    _fetchCurrentLocation();

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
      final attProvider = Provider.of<AttendanceProvider>(context, listen: false);

      if (auth.token != null) {
        await repProvider.fetchStores(auth.token!, forceRefresh: true);
      }

      // Pastikan status absensi terbaru ter-load
      await attProvider.checkAttendanceStatus();

      // Ikat lokasi otomatis dari Check-in atau Visit aktif
      _initLocationFromAttendance();
    });
  }

  void _initLocationFromAttendance() {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final locations = repProvider.stores.isNotEmpty ? repProvider.stores : attProvider.workLocations;

    // 1. Jika mode Edit Submission
    if (widget.editSubmission != null) {
      _selectedStoreName = widget.editSubmission!.storeName ?? 'Lokasi Terdaftar';
      _selectedWorkLocationId = widget.editSubmission!.workLocationId;
      _address = widget.editSubmission!.address ?? _address;

      final match = locations.cast<Map<String, dynamic>?>().firstWhere(
            (l) => l?['id'] == _selectedWorkLocationId || (l?['name'] != null && l?['name'].toString().toLowerCase() == _selectedStoreName.toLowerCase()),
            orElse: () => null,
          );
      if (match != null) {
        _selectLocation(match);
      }
      return;
    }

    // 2. Jika widget parameters diberikan secara eksplisit (dari Visit/Itinerary screen)
    if (widget.storeName != null && widget.storeName!.isNotEmpty) {
      _selectedStoreName = widget.storeName!;
      _selectedWorkLocationId = widget.workLocationId;

      final match = locations.cast<Map<String, dynamic>?>().firstWhere(
            (l) => l?['id'] == _selectedWorkLocationId || (l?['name'] != null && l?['name'].toString().toLowerCase() == _selectedStoreName.toLowerCase()),
            orElse: () => null,
          );
      if (match != null) {
        _selectLocation(match);
      }
      return;
    }

    // 3. Jika sedang Visit In aktif
    if (attProvider.isVisiting) {
      Map<String, dynamic>? visitInLog;
      for (final log in attProvider.todayLogs) {
        if (log['log_type'] == 'visit_in') {
          visitInLog = log as Map<String, dynamic>?;
          break;
        }
      }

      int? visitLocId;
      if (visitInLog != null && visitInLog['metadata'] != null) {
        final meta = visitInLog['metadata'];
        if (meta is Map) {
          visitLocId = int.tryParse(meta['visit_location_id']?.toString() ?? '');
        }
      }

      Map<String, dynamic>? activeItineraryItem;
      if (attProvider.todayItinerary != null && attProvider.todayItinerary!['items'] is List) {
        for (final it in (attProvider.todayItinerary!['items'] as List)) {
          if (it['status'] == 'in_progress' || (visitLocId != null && (it['work_location_id'] == visitLocId || it['work_location']?['id'] == visitLocId))) {
            activeItineraryItem = it as Map<String, dynamic>?;
            break;
          }
        }
      }

      if (activeItineraryItem != null) {
        _selectedStoreName = activeItineraryItem['work_location']?['name'] ?? activeItineraryItem['name'] ?? 'Toko Kunjungan';
        _selectedWorkLocationId = activeItineraryItem['work_location_id'] ?? activeItineraryItem['work_location']?['id'];
        _address = activeItineraryItem['work_location']?['address'] ?? _address;
      } else if (visitLocId != null) {
        final match = locations.cast<Map<String, dynamic>?>().firstWhere(
              (l) => l?['id'] == visitLocId || l?['id'].toString() == visitLocId.toString(),
              orElse: () => null,
            );
        if (match != null) {
          _selectLocation(match);
          return;
        }
      }
    }

    // 4. Jika sedang Check-In aktif (Reguler Attendance)
    if (_selectedStoreName.isEmpty && attProvider.isCheckedIn) {
      if (attProvider.todaySchedule != null) {
        final sched = attProvider.todaySchedule!;
        _selectedStoreName = sched['work_location']?['name'] ?? sched['work_location_name'] ?? auth.employeeData?['branch']?['name'] ?? 'Lokasi Check-In';
        _selectedWorkLocationId = int.tryParse(sched['work_location_id']?.toString() ?? sched['work_location']?['id']?.toString() ?? '');
        _address = sched['work_location']?['address'] ?? _address;
      } else if (attProvider.monthlyHistory.isNotEmpty) {
        final lastAtt = attProvider.monthlyHistory.first as Map<String, dynamic>;
        _selectedStoreName = lastAtt['work_location']?['name'] ?? lastAtt['branch']?['name'] ?? 'Lokasi Check-In';
        _selectedWorkLocationId = int.tryParse(lastAtt['work_location_id']?.toString() ?? lastAtt['work_location']?['id']?.toString() ?? '');
        _address = lastAtt['work_location']?['address'] ?? _address;
      }
    }

    // Match ke list locations untuk kalkulasi radius jika ditemukan
    if (_selectedStoreName.isNotEmpty) {
      final match = locations.cast<Map<String, dynamic>?>().firstWhere(
            (l) => (_selectedWorkLocationId != null && (l?['id'] == _selectedWorkLocationId || l?['id'].toString() == _selectedWorkLocationId.toString())) ||
                   (l?['name'] != null && l?['name'].toString().toLowerCase() == _selectedStoreName.toLowerCase()),
            orElse: () => null,
          );
      if (match != null) {
        _selectLocation(match);
      } else {
        setState(() {});
      }
    } else {
      setState(() {});
    }
  }

  void _selectLocation(Map<String, dynamic> loc) {
    setState(() {
      _selectedLocation = loc;
      _selectedWorkLocationId = int.tryParse(loc['id']?.toString() ?? '');
      _selectedStoreName = loc['name']?.toString() ?? '';
      _allowedRadiusMeter = double.tryParse(loc['radius_meter']?.toString() ?? loc['radius']?.toString() ?? '100') ?? 100.0;
    });

    _recalculateRadius();
  }

  void _recalculateRadius() {
    if (_selectedLocation == null) return;

    final locLat = double.tryParse(_selectedLocation!['latitude']?.toString() ?? '');
    final locLng = double.tryParse(_selectedLocation!['longitude']?.toString() ?? '');

    if (_latitude != null && _longitude != null && locLat != null && locLng != null) {
      final dist = Geolocator.distanceBetween(_latitude!, _longitude!, locLat, locLng);
      setState(() {
        _calculatedDistance = dist;
        _isWithinRadius = dist <= _allowedRadiusMeter;
      });
    } else {
      setState(() {
        _calculatedDistance = null;
        _isWithinRadius = false;
      });
    }
  }

  void _initializeForm() {
    for (final field in widget.template.fields) {
      final fieldKey = field.id.toString();

      // Inisialisasi default values
      if (field.defaultValue != null && field.defaultValue!.isNotEmpty) {
        _formValues[fieldKey] = field.defaultValue;
      }

      if (['text', 'textarea', 'number', 'integer', 'currency', 'percentage', 'date', 'datepicker', 'time', 'timepicker', 'datetime'].contains(field.fieldType)) {
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

    // Pre-populate jika membuka laporan dalam mode edit
    if (widget.editSubmission != null) {
      _selectedStoreName = widget.editSubmission!.storeName ?? _selectedStoreName;
      _selectedWorkLocationId = widget.editSubmission!.workLocationId ?? _selectedWorkLocationId;

      for (final val in widget.editSubmission!.values) {
        final fieldKey = val.reportFormFieldId != null ? val.reportFormFieldId.toString() : val.fieldName;

        if (['photo', 'camera_photo', 'multi_photo'].contains(val.fieldType)) {
          List<String> urls = [];
          if (val.mediaFullUrls.isNotEmpty) {
            urls.addAll(val.mediaFullUrls);
          } else if (val.valueJson is List) {
            urls.addAll((val.valueJson as List).map((e) => e.toString()));
          } else if (val.mediaFullUrl != null && val.mediaFullUrl!.isNotEmpty) {
            urls.add(val.mediaFullUrl!);
          }
          if (urls.isNotEmpty) {
            _existingMultiPhotoUrls[fieldKey] = urls;
            _existingMultiPhotoUrls[val.fieldName] = urls;
            _existingPhotoUrls[fieldKey] = urls.first;
            _existingPhotoUrls[val.fieldName] = urls.first;
          }
          _formValues[fieldKey] = urls;
          _formValues[val.fieldName] = urls;
          continue;
        }

        if (val.mediaFullUrl != null && val.mediaFullUrl!.isNotEmpty) {
          _existingPhotoUrls[fieldKey] = val.mediaFullUrl!;
          _existingPhotoUrls[val.fieldName] = val.mediaFullUrl!;
        }

        if (val.fieldType == 'signature') {
          _formValues[fieldKey] = val.valueText;
          _formValues[val.fieldName] = val.valueText;
          continue;
        }

        if (val.fieldType == 'currency') {
          final formatted = val.valueNumber != null
              ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val.valueNumber)
              : (val.valueText ?? '');
          _controllers[fieldKey] = TextEditingController(text: formatted);
          _controllers[val.fieldName] = TextEditingController(text: formatted);
          _formValues[fieldKey] = val.valueNumber ?? val.valueText;
          _formValues[val.fieldName] = val.valueNumber ?? val.valueText;
        } else if (['text', 'textarea', 'number', 'integer', 'percentage', 'date', 'datepicker', 'time', 'timepicker', 'datetime'].contains(val.fieldType)) {
          final strVal = val.valueText ?? (val.valueNumber != null ? (val.valueNumber! % 1 == 0 ? val.valueNumber!.toInt().toString() : val.valueNumber.toString()) : '');
          _controllers[fieldKey] = TextEditingController(text: strVal);
          _controllers[val.fieldName] = TextEditingController(text: strVal);
          _formValues[fieldKey] = strVal;
          _formValues[val.fieldName] = strVal;
        } else if (val.fieldType == 'checkbox') {
          final boolVal = val.valueText == 'true' || val.valueText == '1';
          _formValues[fieldKey] = boolVal;
          _formValues[val.fieldName] = boolVal;
        } else if (val.fieldType == 'rating' || val.fieldType == 'rating_star') {
          final rateVal = val.valueNumber?.toInt() ?? int.tryParse(val.valueText ?? '5') ?? 5;
          _formValues[fieldKey] = rateVal;
          _formValues[val.fieldName] = rateVal;
        } else if (val.fieldType == 'multi_select' || val.fieldType == 'checkbox_group') {
          if (val.valueJson is List) {
            _formValues[fieldKey] = (val.valueJson as List).map((e) => e.toString()).toList();
          } else if (val.valueText != null && val.valueText!.isNotEmpty) {
            _formValues[fieldKey] = val.valueText!.split(',').map((e) => e.trim()).toList();
          }
        } else {
          _formValues[fieldKey] = val.valueText;
          _formValues[val.fieldName] = val.valueText;
        }
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
        _recalculateRadius();
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

  // Format tanggal display (dd MMM yyyy)
  String _formatDateDisplay(String dateVal) {
    if (dateVal.isEmpty) return '';
    try {
      final parsed = DateTime.parse(dateVal);
      return DateFormat('dd MMMM yyyy').format(parsed);
    } catch (_) {
      return dateVal;
    }
  }

  // Pemilih Tanggal Interaktif
  Future<void> _pickDate(String fieldKey, ReportFormFieldModel field) async {
    DateTime initialDate = DateTime.now();
    final currentVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
    if (currentVal.isNotEmpty) {
      try {
        initialDate = DateTime.parse(currentVal);
      } catch (_) {}
    }

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final themeColor = Color(int.tryParse(widget.template.color.replaceAll('#', '0xFF')) ?? primaryColor.value);

    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
      builder: (context, child) {
        return Theme(
          data: isDarkMode
              ? ThemeData.dark().copyWith(
                  colorScheme: ColorScheme.dark(
                    primary: themeColor,
                    onPrimary: Colors.white,
                    surface: const Color(0xFF1E1E2C),
                    onSurface: Colors.white,
                  ),
                )
              : ThemeData.light().copyWith(
                  colorScheme: ColorScheme.light(
                    primary: themeColor,
                    onPrimary: Colors.white,
                    surface: Colors.white,
                    onSurface: const Color(0xFF0E1830),
                  ),
                ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      final formatted = DateFormat('yyyy-MM-dd').format(picked);
      setState(() {
        _formValues[fieldKey] = formatted;
        _controllers[fieldKey]?.text = formatted;
      });
    }
  }

  // Pemilih Waktu / Jam
  Future<void> _pickTime(String fieldKey, ReportFormFieldModel field) async {
    TimeOfDay initialTime = TimeOfDay.now();
    final currentVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
    if (currentVal.isNotEmpty && currentVal.contains(':')) {
      final parts = currentVal.split(':');
      final h = int.tryParse(parts[0]);
      final m = int.tryParse(parts[1]);
      if (h != null && m != null) {
        initialTime = TimeOfDay(hour: h, minute: m);
      }
    }

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final themeColor = Color(int.tryParse(widget.template.color.replaceAll('#', '0xFF')) ?? primaryColor.value);

    final picked = await showTimePicker(
      context: context,
      initialTime: initialTime,
      builder: (context, child) {
        return Theme(
          data: isDarkMode
              ? ThemeData.dark().copyWith(
                  colorScheme: ColorScheme.dark(
                    primary: themeColor,
                    onPrimary: Colors.white,
                    surface: const Color(0xFF1E1E2C),
                    onSurface: Colors.white,
                  ),
                )
              : ThemeData.light().copyWith(
                  colorScheme: ColorScheme.light(
                    primary: themeColor,
                    onPrimary: Colors.white,
                    surface: Colors.white,
                    onSurface: const Color(0xFF0E1830),
                  ),
                ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      final formatted = '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
      setState(() {
        _formValues[fieldKey] = formatted;
        _controllers[fieldKey]?.text = formatted;
      });
    }
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

  // Ambil foto dengan Geotag Watermark Permanen
  Future<void> _takeWatermarkedPhoto(ReportFormFieldModel field) async {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final employeeName = auth.employeeData?['full_name'] ?? 'Promotor';
    final employeeNik = auth.employeeData?['nik'] ?? '';
    final currentStore = _selectedStoreName.isNotEmpty ? _selectedStoreName : 'Kunjungan Toko Terdaftar';

    final result = await WatermarkCameraService.captureWithWatermark(
      employeeName: employeeName,
      employeeNik: employeeNik,
      storeName: currentStore,
      latitude: _latitude,
      longitude: _longitude,
    );

    if (result != null && mounted) {
      final fieldKey = field.id.toString();
      setState(() {
        if (['photo', 'camera_photo', 'multi_photo'].contains(field.fieldType)) {
          _multiPhotoFiles.putIfAbsent(fieldKey, () => []).add(result.file);
          _photoFiles[fieldKey] = result.file;
          _watermarkTexts[fieldKey] = result.watermarkText;
          _formValues[fieldKey] = _multiPhotoFiles[fieldKey]!.map((f) => f.path).toList();
        } else {
          _photoFiles[fieldKey] = result.file;
          _watermarkTexts[fieldKey] = result.watermarkText;
          _formValues[fieldKey] = result.file.path;
        }
      });

      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Foto Berhasil Diambil & Diberi Watermark'),
        description: Text('Stempel Geotag $currentStore berhasil dibubuhkan permanen.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
    }
  }

  // Buka Dialog Tanda Tangan
  Future<void> _openSignaturePad(ReportFormFieldModel field) async {
    final currentStore = _selectedStoreName.isNotEmpty ? _selectedStoreName : 'Tanda Tangan PIC / Toko';

    final File? signatureFile = await showDialog<File>(
      context: context,
      builder: (context) => SignaturePadDialog(
        title: field.fieldLabel,
        signerRole: currentStore,
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
    final locale = Provider.of<LocaleProvider>(context, listen: false);
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);

    final bool isVisiting = attProvider.isVisiting;
    final bool isCheckedIn = attProvider.isCheckedIn;
    final bool isEditMode = widget.editSubmission != null;
    final bool canSubmitReport = isVisiting || isCheckedIn || isEditMode;

    if (!canSubmitReport) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Belum Absensi Kehadiran / Visit'),
        description: const Text('Anda wajib melakukan Check-In kehadiran atau Visit-In kunjungan toko terlebih dahulu untuk mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    if (_selectedStoreName.isEmpty) {
      _selectedStoreName = 'Lokasi Kunjungan Terdaftar';
    }

    if (!_formKey.currentState!.validate()) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: Text(locale.tr('error')),
        description: const Text('Mohon lengkapi kolom yang bertanda bintang (*)'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    // Validasi foto & tanda tangan required
    for (final field in widget.template.fields) {
      if (field.isRequired) {
        final fieldKey = field.id.toString();
        if (['photo', 'camera_photo', 'multi_photo'].contains(field.fieldType)) {
          final hasFiles = _multiPhotoFiles[fieldKey]?.isNotEmpty ?? false;
          final hasSingleFile = _photoFiles[fieldKey] != null;
          final hasExisting = (_existingMultiPhotoUrls[fieldKey]?.isNotEmpty ?? false) ||
              (_existingMultiPhotoUrls[field.fieldName]?.isNotEmpty ?? false) ||
              _existingPhotoUrls.containsKey(fieldKey) ||
              _existingPhotoUrls.containsKey(field.fieldName);
          if (!hasFiles && !hasSingleFile && !hasExisting) {
            toastification.show(
              context: context,
              type: ToastificationType.warning,
              title: Text('Wajib Mengisi ${field.fieldLabel}'),
              description: const Text('Silakan ambil minimal 1 foto bukti terlebih dahulu.'),
              autoCloseDuration: const Duration(seconds: 3),
            );
            return;
          }
        } else if (field.fieldType == 'signature') {
          final hasFile = _photoFiles.containsKey(fieldKey) && _photoFiles[fieldKey] != null;
          final hasExisting = _existingPhotoUrls.containsKey(fieldKey) || _existingPhotoUrls.containsKey(field.fieldName);
          if (!hasFile && !hasExisting) {
            toastification.show(
              context: context,
              type: ToastificationType.warning,
              title: Text('Wajib Mengisi ${field.fieldLabel}'),
              description: const Text('Silakan buat tanda tangan terlebih dahulu.'),
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

    // Buat salinan bersih dari formValues tanpa path file lokal perangkat
    final Map<String, dynamic> cleanFormValues = Map<String, dynamic>.from(_formValues);
    for (var f in widget.template.fields) {
      if (['photo', 'camera_photo', 'multi_photo', 'signature'].contains(f.fieldType)) {
        cleanFormValues.remove(f.id.toString());
        cleanFormValues.remove(f.fieldName);
      }
    }

    // Gabungkan payload foto (single dan multi-foto)
    final Map<String, dynamic> allPhotosPayload = {};
    _photoFiles.forEach((k, v) => allPhotosPayload[k] = v);
    _multiPhotoFiles.forEach((k, v) => allPhotosPayload[k] = v);

    Map<String, dynamic> result;

    if (widget.editSubmission != null) {
      result = await repProvider.updateReport(
        token: token,
        submissionId: widget.editSubmission!.id,
        storeName: _selectedStoreName,
        workLocationId: _selectedWorkLocationId,
        address: _selectedLocation?['address'] ?? _address,
        values: cleanFormValues,
        photoFiles: allPhotosPayload,
        existingPhotos: _existingMultiPhotoUrls,
      );
    } else {
      result = await repProvider.submitReport(
        token: token,
        templateId: widget.template.id,
        templateTitle: widget.template.title,
        storeName: _selectedStoreName,
        workLocationId: _selectedWorkLocationId,
        itineraryItemId: widget.itineraryItemId,
        latitude: _latitude,
        longitude: _longitude,
        address: _selectedLocation?['address'] ?? _address,
        isWithinRadius: _isWithinRadius,
        values: cleanFormValues,
        photoFiles: allPhotosPayload,
        watermarkTexts: _watermarkTexts,
      );
    }

    setState(() => _isSubmitting = false);

    if (result['success'] == true && mounted) {
      toastification.show(
        context: context,
        type: result['is_offline'] == true ? ToastificationType.info : ToastificationType.success,
        title: Text(widget.editSubmission != null ? 'Laporan Diperbarui' : (result['is_offline'] == true ? 'Tersimpan Offline' : 'Laporan Terkirim')),
        description: Text(result['message'] ?? 'Berhasil menyimpan laporan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      Navigator.of(context).pop(true);
    } else if (mounted) {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Gagal Menyimpan'),
        description: Text(result['message'] ?? 'Terjadi kesalahan saat menyimpan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final attProvider = Provider.of<AttendanceProvider>(context);
    final locale = Provider.of<LocaleProvider>(context);

    final bool isVisiting = attProvider.isVisiting;
    final bool isCheckedIn = attProvider.isCheckedIn;
    final bool isEditMode = widget.editSubmission != null;
    final bool canSubmitReport = isVisiting || isCheckedIn || isEditMode;

    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final themeColor = Color(int.tryParse(widget.template.color.replaceAll('#', '0xFF')) ?? primaryColor.value);

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    // Badge status lokasi
    String locationBadgeTitle = 'LOKASI CHECK-IN';
    Color locationBadgeColor = themeColor;
    IconData locationBadgeIcon = Icons.verified_user_rounded;

    if (isVisiting) {
      locationBadgeTitle = 'LOKASI VISIT AKTIF';
      locationBadgeColor = const Color(0xFF149A6E);
      locationBadgeIcon = Icons.directions_walk_rounded;
    } else if (isEditMode) {
      locationBadgeTitle = 'LOKASI LAPORAN';
      locationBadgeColor = const Color(0xFF0F52BA);
      locationBadgeIcon = Icons.edit_document;
    }

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          widget.editSubmission != null ? 'Edit ${widget.template.title}' : widget.template.title,
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
            // ─── Header: Lokasi Terikat Otomatis Sesuai Check-In / Visit ───
            if (canSubmitReport) ...[
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(
                    color: _selectedLocation != null
                        ? (_isWithinRadius ? const Color(0xFF149A6E) : const Color(0xFFD98A2B)).withOpacity(0.5)
                        : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                    width: _selectedLocation != null ? 1.5 : 1.0,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Badge Header & Status Terkunci
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: locationBadgeColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: locationBadgeColor.withOpacity(0.3), width: 0.8),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(locationBadgeIcon, color: locationBadgeColor, size: 14),
                              const SizedBox(width: 5),
                              Text(
                                locationBadgeTitle,
                                style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: locationBadgeColor),
                              ),
                            ],
                          ),
                        ),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3.5),
                          decoration: BoxDecoration(
                            color: (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.lock_rounded, size: 11, color: subtitleColor),
                              const SizedBox(width: 4),
                              Text(
                                'Terkunci Otomatis',
                                style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: subtitleColor),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Nama Toko / Lokasi Terikat
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(Icons.storefront_rounded, color: themeColor, size: 24),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _selectedStoreName.isNotEmpty ? _selectedStoreName : 'Lokasi Terdaftar',
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.bold,
                                  color: textColor,
                                ),
                              ),
                              if (_selectedLocation?['address'] != null && _selectedLocation!['address'].toString().isNotEmpty) ...[
                                const SizedBox(height: 3),
                                Text(
                                  _selectedLocation!['address'].toString(),
                                  style: TextStyle(fontSize: 11.5, color: subtitleColor),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ] else if (_address != null && _address!.isNotEmpty) ...[
                                const SizedBox(height: 3),
                                Text(
                                  _address!,
                                  style: TextStyle(fontSize: 11.5, color: subtitleColor),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),

                    // Dynamic Radius & Geofence Indicator
                    if (_selectedLocation != null) ...[
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                        decoration: BoxDecoration(
                          color: _isWithinRadius
                              ? const Color(0xFFE2F6EE)
                              : (isDarkMode ? const Color(0xFF332010) : const Color(0xFFFFF3E0)),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _isWithinRadius ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                            width: 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              _isWithinRadius ? Icons.check_circle_rounded : Icons.warning_amber_rounded,
                              size: 18,
                              color: _isWithinRadius ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _calculatedDistance != null
                                    ? locale.tr('radius_info', params: {
                                        'distance': _calculatedDistance! >= 1000
                                            ? '${(_calculatedDistance! / 1000).toStringAsFixed(1)} km'
                                            : '${_calculatedDistance!.round()} m',
                                        'allowed': '${_allowedRadiusMeter.round()} m',
                                      })
                                    : (_isWithinRadius ? locale.tr('within_radius') : locale.tr('outside_radius')),
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w600,
                                  color: _isWithinRadius
                                      ? const Color(0xFF0F7652)
                                      : (isDarkMode ? Colors.orange.shade200 : const Color(0xFFB46A14)),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],

                    const Divider(height: 20),

                    // GPS Live Status
                    Row(
                      children: [
                        Icon(
                          Icons.my_location_rounded,
                          size: 14,
                          color: _latitude != null ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            _latitude != null
                                ? '${locale.tr('gps_connected')} (${_latitude!.toStringAsFixed(4)}, ${_longitude!.toStringAsFixed(4)})'
                                : locale.tr('gps_searching'),
                            style: TextStyle(
                              fontSize: 11,
                              color: _latitude != null ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        if (_isFetchingLocation)
                          const SizedBox(
                            width: 12,
                            height: 12,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ] else ...[
              // ─── Peringatan: Belum Check-in atau Visit-in ───
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF2A1515) : const Color(0xFFFFF1F1),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: Colors.red.shade400, width: 1.2),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.red.withOpacity(0.06),
                      blurRadius: 10,
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
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.red.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.lock_clock_rounded, color: Colors.red, size: 22),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Belum Check-In / Visit-In',
                                style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Colors.red),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Laporan terkunci & tidak dapat dikirim',
                                style: TextStyle(fontSize: 11, color: isDarkMode ? Colors.red.shade300 : Colors.red.shade700),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Untuk mengisi dan mengirim laporan reporting, Anda wajib melakukan Check-In kehadiran atau Visit-In kunjungan toko terlebih dahulu. Lokasi reporting akan mengikat otomatis ke lokasi absensi aktif Anda.',
                      style: TextStyle(
                        fontSize: 12,
                        height: 1.4,
                        color: isDarkMode ? Colors.grey.shade300 : const Color(0xFF552222),
                      ),
                    ),
                    const Divider(height: 20, color: Colors.redAccent),
                    Row(
                      children: [
                        Icon(
                          Icons.my_location_rounded,
                          size: 14,
                          color: _latitude != null ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            _latitude != null
                                ? 'GPS Siap (${_latitude!.toStringAsFixed(4)}, ${_longitude!.toStringAsFixed(4)})'
                                : 'Mendeteksi koordinat GPS...',
                            style: TextStyle(
                              fontSize: 11,
                              color: _latitude != null ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],

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
                  locale,
                )),

            const SizedBox(height: 16),

            // Submit Button (Dinonaktifkan jika belum Check-In / Visit-In)
            if (!canSubmitReport)
              ElevatedButton.icon(
                onPressed: null,
                icon: const Icon(Icons.lock_rounded, size: 18, color: Colors.grey),
                label: const Text(
                  'Wajib Check-In / Visit-In Terlebih Dahulu',
                  style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.grey),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFE2E8F0),
                  disabledBackgroundColor: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFE2E8F0),
                  disabledForegroundColor: Colors.grey.shade500,
                  padding: const EdgeInsets.symmetric(vertical: 15),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  elevation: 0,
                ),
              )
            else
              ElevatedButton.icon(
                onPressed: _isSubmitting ? null : _submitForm,
                icon: _isSubmitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : Icon(widget.editSubmission != null ? Icons.save_rounded : Icons.send_rounded, color: Colors.white, size: 18),
                label: Text(
                  _isSubmitting
                      ? (widget.editSubmission != null ? 'Menyimpan Perubahan...' : locale.tr('btn_submitting_report'))
                      : (widget.editSubmission != null ? 'Simpan Perubahan Laporan' : locale.tr('btn_submit_report')),
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
    LocaleProvider locale,
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
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
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
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: (v) => _formValues[fieldKey] = num.tryParse(v),
        );
        break;

      case 'currency':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          keyboardType: TextInputType.number,
          style: TextStyle(color: textColor, fontSize: 13, fontWeight: FontWeight.w600),
          decoration: _inputDecoration('Rp 0', elevatedColor, isDarkMode),
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: (v) => _formatCurrency(fieldKey, v),
        );
        break;

      case 'select':
      case 'dropdown':
      case 'product':
      case 'product_select':
        final effectiveOptions = field.options.isNotEmpty
            ? field.options
            : widget.template.products.map((p) => p.name).toList();

        inputWidget = DropdownButtonFormField<String>(
          value: _formValues[fieldKey],
          decoration: _inputDecoration(field.placeholder ?? 'Pilih salah satu opsi...', elevatedColor, isDarkMode),
          dropdownColor: cardColor,
          isExpanded: true,
          style: TextStyle(color: textColor, fontSize: 13),
          items: effectiveOptions
              .map((opt) {
                final matchedProduct = widget.template.products.cast<TemplateProductModel?>().firstWhere(
                      (p) => p?.name.toLowerCase() == opt.toLowerCase(),
                      orElse: () => null,
                    );
                return DropdownMenuItem<String>(
                  value: opt,
                  child: Row(
                    children: [
                      if (matchedProduct != null) ...[
                        const Icon(Icons.inventory_2_outlined, size: 16, color: Color(0xFFE53935)),
                        const SizedBox(width: 6),
                      ],
                      Expanded(
                        child: Text(
                          opt,
                          style: TextStyle(fontSize: 12.5, color: textColor),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (matchedProduct != null && (matchedProduct.formattedPrice != null || matchedProduct.brand != null)) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            matchedProduct.brand ?? matchedProduct.formattedPrice ?? '',
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: themeColor),
                          ),
                        ),
                      ],
                    ],
                  ),
                );
              })
              .toList(),
          validator: (v) => field.isRequired && (v == null || v.isEmpty) ? locale.tr('required_field') : null,
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
        final capturedFiles = _multiPhotoFiles[fieldKey] ?? [];
        final existingUrls = _existingMultiPhotoUrls[fieldKey] ?? _existingMultiPhotoUrls[field.fieldName] ?? [];
        // Also check if there is a single existingPhotoUrl
        final singleExisting = _existingPhotoUrls[fieldKey] ?? _existingPhotoUrls[field.fieldName];
        if (existingUrls.isEmpty && singleExisting != null && singleExisting.isNotEmpty) {
          existingUrls.add(singleExisting);
          _existingMultiPhotoUrls[fieldKey] = existingUrls;
        }
        final totalCount = capturedFiles.length + existingUrls.length;

        inputWidget = Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (totalCount > 0) ...[
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  // Existing network photos
                  ...existingUrls.asMap().entries.map((entry) {
                    final idx = entry.key;
                    final url = entry.value;
                    return Stack(
                      clipBehavior: Clip.none,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.network(
                            url,
                            width: 100,
                            height: 100,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) => Container(
                              width: 100,
                              height: 100,
                              color: elevatedColor,
                              alignment: Alignment.center,
                              child: const Icon(Icons.broken_image_rounded, size: 24, color: Colors.grey),
                            ),
                          ),
                        ),
                        Positioned(
                          top: -6,
                          right: -6,
                          child: GestureDetector(
                            onTap: () {
                              setState(() {
                                existingUrls.removeAt(idx);
                                _existingMultiPhotoUrls[fieldKey] = existingUrls;
                              });
                            },
                            child: Container(
                              padding: const EdgeInsets.all(3),
                              decoration: const BoxDecoration(
                                color: Colors.red,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close_rounded, color: Colors.white, size: 14),
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: 4,
                          left: 4,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: Colors.black87,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              'Foto ${idx + 1}',
                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                      ],
                    );
                  }),
                  // Newly taken local files
                  ...capturedFiles.asMap().entries.map((entry) {
                    final idx = entry.key;
                    final file = entry.value;
                    return Stack(
                      clipBehavior: Clip.none,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.file(
                            file,
                            width: 100,
                            height: 100,
                            fit: BoxFit.cover,
                          ),
                        ),
                        Positioned(
                          top: -6,
                          right: -6,
                          child: GestureDetector(
                            onTap: () {
                              setState(() {
                                capturedFiles.removeAt(idx);
                                _multiPhotoFiles[fieldKey] = capturedFiles;
                                _formValues[fieldKey] = capturedFiles.map((f) => f.path).toList();
                              });
                            },
                            child: Container(
                              padding: const EdgeInsets.all(3),
                              decoration: const BoxDecoration(
                                color: Colors.red,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close_rounded, color: Colors.white, size: 14),
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: 4,
                          left: 4,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: const Color(0xFF149A6E),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              'Baru ${existingUrls.length + idx + 1}',
                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                      ],
                    );
                  }),
                ],
              ),
              const SizedBox(height: 12),
            ],
            OutlinedButton.icon(
              onPressed: () => _takeWatermarkedPhoto(field),
              icon: const Icon(Icons.add_a_photo_rounded, size: 18),
              label: Text(
                totalCount == 0
                    ? 'Ambil Foto Bukti (Multi-Foto)'
                    : 'Tambah Foto Bukti Lainnya ($totalCount Terambil)',
              ),
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
        final existingSigUrl = _existingPhotoUrls[fieldKey] ?? _existingPhotoUrls[field.fieldName];

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
            ] else if (existingSigUrl != null && existingSigUrl.isNotEmpty) ...[
              Container(
                height: 100,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Image.network(existingSigUrl, fit: BoxFit.contain),
              ),
              const SizedBox(height: 10),
            ],
            OutlinedButton.icon(
              onPressed: () => _openSignaturePad(field),
              icon: const Icon(Icons.draw_rounded, size: 18),
              label: Text(
                (sigFile == null && (existingSigUrl == null || existingSigUrl.isEmpty))
                    ? locale.tr('sign_pad_button')
                    : 'Ubah Tanda Tangan',
              ),
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

      case 'date':
      case 'datepicker':
        inputWidget = FormField<String>(
          initialValue: _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text,
          validator: (v) {
            final val = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            if (field.isRequired && val.trim().isEmpty) {
              return locale.tr('required_field');
            }
            return null;
          },
          builder: (state) {
            final dateVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            final displayDateText = _formatDateDisplay(dateVal);
            final hasError = state.hasError;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                InkWell(
                  onTap: () async {
                    await _pickDate(fieldKey, field);
                    state.didChange(_formValues[fieldKey]?.toString());
                  },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: elevatedColor,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: hasError
                            ? Colors.red
                            : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                        width: hasError ? 1.5 : 1.0,
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.calendar_month_rounded, color: themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            displayDateText.isNotEmpty
                                ? displayDateText
                                : (field.placeholder ?? 'Pilih tanggal...'),
                            style: TextStyle(
                              color: displayDateText.isNotEmpty
                                  ? textColor
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: displayDateText.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                        if (displayDateText.isNotEmpty)
                          IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            color: subtitleColor,
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                            onPressed: () {
                              setState(() {
                                _formValues.remove(fieldKey);
                                _controllers[fieldKey]?.clear();
                              });
                              state.didChange('');
                            },
                          )
                        else
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: themeColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              'Pilih',
                              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: themeColor),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
                if (hasError) ...[
                  const SizedBox(height: 6),
                  Padding(
                    padding: const EdgeInsets.only(left: 4),
                    child: Text(
                      state.errorText ?? '',
                      style: const TextStyle(color: Colors.red, fontSize: 11.5),
                    ),
                  ),
                ],
              ],
            );
          },
        );
        break;

      case 'time':
      case 'timepicker':
        inputWidget = FormField<String>(
          initialValue: _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text,
          validator: (v) {
            final val = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            if (field.isRequired && val.trim().isEmpty) {
              return locale.tr('required_field');
            }
            return null;
          },
          builder: (state) {
            final timeVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            final hasError = state.hasError;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                InkWell(
                  onTap: () async {
                    await _pickTime(fieldKey, field);
                    state.didChange(_formValues[fieldKey]?.toString());
                  },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: elevatedColor,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: hasError
                            ? Colors.red
                            : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                        width: hasError ? 1.5 : 1.0,
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.access_time_rounded, color: themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            timeVal.isNotEmpty ? timeVal : (field.placeholder ?? 'Pilih jam...'),
                            style: TextStyle(
                              color: timeVal.isNotEmpty
                                  ? textColor
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: timeVal.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                        if (timeVal.isNotEmpty)
                          IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            color: subtitleColor,
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                            onPressed: () {
                              setState(() {
                                _formValues.remove(fieldKey);
                                _controllers[fieldKey]?.clear();
                              });
                              state.didChange('');
                            },
                          )
                        else
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: themeColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              'Pilih',
                              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: themeColor),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
                if (hasError) ...[
                  const SizedBox(height: 6),
                  Padding(
                    padding: const EdgeInsets.only(left: 4),
                    child: Text(
                      state.errorText ?? '',
                      style: const TextStyle(color: Colors.red, fontSize: 11.5),
                    ),
                  ),
                ],
              ],
            );
          },
        );
        break;

      case 'datetime':
        inputWidget = FormField<String>(
          initialValue: _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text,
          validator: (v) {
            final val = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            if (field.isRequired && val.trim().isEmpty) {
              return locale.tr('required_field');
            }
            return null;
          },
          builder: (state) {
            final dtVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            final hasError = state.hasError;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                InkWell(
                  onTap: () async {
                    await _pickDate(fieldKey, field);
                    state.didChange(_formValues[fieldKey]?.toString());
                  },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: elevatedColor,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: hasError
                            ? Colors.red
                            : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                        width: hasError ? 1.5 : 1.0,
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.calendar_today_rounded, color: themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            dtVal.isNotEmpty ? dtVal : (field.placeholder ?? 'Pilih tanggal & waktu...'),
                            style: TextStyle(
                              color: dtVal.isNotEmpty
                                  ? textColor
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: dtVal.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                if (hasError) ...[
                  const SizedBox(height: 6),
                  Padding(
                    padding: const EdgeInsets.only(left: 4),
                    child: Text(
                      state.errorText ?? '',
                      style: const TextStyle(color: Colors.red, fontSize: 11.5),
                    ),
                  ),
                ],
              ],
            );
          },
        );
        break;

      case 'text':
      default:
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          style: TextStyle(color: textColor, fontSize: 13),
          decoration: _inputDecoration(field.placeholder ?? 'Masukkan ${field.fieldLabel.toLowerCase()}', elevatedColor, isDarkMode),
          validator: (v) => field.isRequired && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
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
