import 'dart:io';
import 'dart:convert';
import 'package:image_picker/image_picker.dart';
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
import 'package:att_mobile/widgets/barcode_scanner_dialog.dart';

// Model untuk input dinamis produk kompetitor pada form CBP
class CompetitorInputItem {
  String merk;
  final TextEditingController subbrandCtrl;
  final TextEditingController tinCtrl;
  final TextEditingController galonCtrl;
  final TextEditingController pailCtrl;

  CompetitorInputItem({
    this.merk = 'JOTUN',
    String subbrand = '',
    String tin = '',
    String galon = '',
    String pail = '',
  })  : subbrandCtrl = TextEditingController(text: subbrand),
        tinCtrl = TextEditingController(text: tin),
        galonCtrl = TextEditingController(text: galon),
        pailCtrl = TextEditingController(text: pail);

  void dispose() {
    subbrandCtrl.dispose();
    tinCtrl.dispose();
    galonCtrl.dispose();
    pailCtrl.dispose();
  }
}

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

  // Dynamic Competitor Items (CBP Report)
  final List<CompetitorInputItem> _competitorItems = [];

  // Mutual Discount Calculation Flags
  bool _isSyncingDiscounts = false;
  String? _activeDiscountType;

  // Lokasi Terikat Sesuai Check-in / Visit Aktif
  Map<String, dynamic>? _selectedLocation;
  int? _selectedWorkLocationId;
  String _selectedStoreName = '';
  double? _calculatedDistance;
  bool _isWithinRadius = false;
  double _allowedRadiusMeter = 100.0;

  // Multi-Category & Session Progress Tracking
  final ScrollController _scrollController = ScrollController();
  final Set<String> _submittedCategories = {};
  final List<String> _submittedCategoryLog = [];
  int _sessionSubmissionCount = 0;

  // Multi-Product & Sequential Submission Tracking
  final Set<String> _submittedProductNames = {};

  // Multi-Machine & Sequential Submission Tracking (Daily Maintenance Dulux)
  final Set<String> _submittedMachineNames = {};

  void _initSubmittedMachines() {
    for (final m in widget.template.submittedMachines) {
      final clean = m.trim().toLowerCase();
      if (clean.isNotEmpty) {
        _submittedMachineNames.add(clean);
      }
    }
  }

  bool _isMachineSubmitted(String? machineType) {
    if (machineType == null || machineType.trim().isEmpty) return false;
    final clean = machineType.trim().toLowerCase();
    return _submittedMachineNames.contains(clean);
  }

  bool _hasMachineBinding() {
    if (!_isDailyMaintenanceTemplate()) return false;
    if (widget.template.hasMachineBinding) return true;
    final map = _getStoreMachinesMap();
    return map.isNotEmpty;
  }

  int _getTotalMachinesCount() {
    final map = _getStoreMachinesMap();
    if (map.isNotEmpty) return map.length;
    return widget.template.totalMachinesCount > 0 ? widget.template.totalMachinesCount : 0;
  }

  int _getRemainingMachinesCount() {
    final map = _getStoreMachinesMap();
    if (map.isNotEmpty) {
      return map.keys.where((type) => !_isMachineSubmitted(type)).length;
    }
    final total = _getTotalMachinesCount();
    final rem = total - _submittedMachineNames.length;
    return rem > 0 ? rem : 0;
  }

  void _autoSelectNextUnsubmittedMachine() {
    if (!_isDailyMaintenanceTemplate()) return;
    final storeMachinesMap = _getStoreMachinesMap();
    if (storeMachinesMap.isEmpty) return;

    String? nextMachine;
    for (final type in storeMachinesMap.keys) {
      if (!_isMachineSubmitted(type)) {
        nextMachine = type;
        break;
      }
    }

    nextMachine ??= storeMachinesMap.keys.first;

    for (final f in widget.template.fields) {
      final fName = f.fieldName.toLowerCase();
      final fLabel = f.fieldLabel.toLowerCase();
      if (fName == 'tipe_mesin_post' || fName.contains('tipe_mesin') || fLabel.contains('tipe mesin')) {
        final machineKey = f.id.toString();
        setState(() {
          _formValues[machineKey] = nextMachine;
          _formValues['tipe_mesin_post'] = nextMachine;
          _formValues[f.fieldName] = nextMachine;
          _autoFillMachineSerial(nextMachine);
        });
      }
    }
  }

  void _initSubmittedProducts() {
    for (final p in widget.template.submittedProducts) {
      final clean = p.trim().toLowerCase();
      if (clean.isNotEmpty) {
        _submittedProductNames.add(clean);
      }
    }
  }

  bool _isProductSubmitted(String? productName) {
    if (productName == null || productName.trim().isEmpty) return false;
    final clean = productName.trim().toLowerCase();
    if (_submittedProductNames.contains(clean)) return true;
    for (final sub in _submittedProductNames) {
      if (sub == clean) return true;
    }
    return false;
  }

  bool _isTemplateProductSubmitted(TemplateProductModel p) {
    if (widget.template.submittedProductIds.contains(p.id)) return true;
    if (_isProductSubmitted(p.name)) return true;
    if (p.skuCode != null && _isProductSubmitted(p.skuCode!)) return true;
    return false;
  }

  bool _isOptionSubmitted(String opt) {
    if (_isProductSubmitted(opt)) return true;
    final products = _getProducts();
    final matched = products.cast<TemplateProductModel?>().firstWhere(
      (p) => p != null && (p.name.toLowerCase() == opt.toLowerCase() || (p.skuCode != null && p.skuCode!.toLowerCase() == opt.toLowerCase())),
      orElse: () => null,
    );
    if (matched != null) {
      return _isTemplateProductSubmitted(matched);
    }
    return false;
  }

  bool _isOfftakeTemplate() {
    final code = widget.template.code.toUpperCase();
    final title = widget.template.title.toLowerCase();
    return code == 'RPT-DULUX-OFFTAKE-01' ||
        code.contains('OFFTAKE') ||
        title.contains('offtake');
  }

  bool _isOosTemplate() {
    final code = widget.template.code.toUpperCase();
    final title = widget.template.title.toLowerCase();
    return code == 'RPT-DULUX-OOS-SSO' ||
        code == 'RPT-DULUX-OOS-LSO' ||
        code.contains('OOS') ||
        title.contains('out of stock') ||
        title.contains('oos');
  }

  bool _hasProductBinding() {
    if (_isDailyMaintenanceTemplate()) return false;
    if (_isOfftakeTemplate()) return false; // Offtake uses cart + confirmation review, NOT sequential per-product locks!
    if (_isOosTemplate()) return false; // OOS uses cart + confirmation review, NOT sequential per-product locks!
    if (widget.template.hasProductBinding) return true;
    if (_getProducts().isNotEmpty) return true;
    final code = widget.template.code.toUpperCase();
    if (code == 'RPT-DULUX-STOCK-END' ||
        code == 'RPT-DULUX-CBP-PRICING') {
      return true;
    }
    return widget.template.fields.any((f) => _isProductField(f));
  }

  int _getTotalProductsCount() {
    final prods = _getProducts();
    if (prods.isNotEmpty) return prods.length;
    for (final f in widget.template.fields) {
      if (_isProductField(f) && f.options.isNotEmpty) {
        return f.options.length;
      }
    }
    return widget.template.totalProductsCount > 0 ? widget.template.totalProductsCount : 0;
  }

  int _getRemainingProductsCount() {
    final total = _getTotalProductsCount();
    if (total == 0) return 0;
    final prods = _getProducts();
    if (prods.isNotEmpty) {
      return prods.where((p) => !_isTemplateProductSubmitted(p)).length;
    }
    for (final f in widget.template.fields) {
      if (_isProductField(f) && f.options.isNotEmpty) {
        return f.options.where((opt) => !_isOptionSubmitted(opt)).length;
      }
    }
    final rem = total - _submittedProductNames.length;
    return rem > 0 ? rem : 0;
  }

  void _autoSelectNextUnsubmittedProduct() {
    final products = _getProducts();
    TemplateProductModel? nextProduct;
    for (final p in products) {
      if (!_isTemplateProductSubmitted(p)) {
        nextProduct = p;
        break;
      }
    }

    for (final f in widget.template.fields) {
      if (_isProductField(f)) {
        final fieldKey = f.id.toString();
        if (nextProduct != null) {
          _onProductSelected(nextProduct, fieldKey);
        } else if (f.options.isNotEmpty) {
          final nextOpt = f.options.firstWhere(
            (opt) => !_isOptionSubmitted(opt),
            orElse: () => '',
          );
          if (nextOpt.isNotEmpty) {
            setState(() {
              _controllers[fieldKey]?.text = nextOpt;
              _formValues[fieldKey] = nextOpt;
              _formValues[f.fieldName] = nextOpt;
            });
          }
        }
      }
    }
  }

  // State nilai form dinamis
  final Map<String, dynamic> _formValues = {};
  final Map<String, File> _photoFiles = {};
  final Map<String, List<File>> _multiPhotoFiles = {};
  final Map<String, String> _watermarkTexts = {};
  final Map<String, String> _existingPhotoUrls = {};
  final Map<String, List<String>> _existingMultiPhotoUrls = {};

  // Controllers untuk text & currency fields
  final Map<String, TextEditingController> _controllers = {};

  // Offtake Reporting State
  String _offtakeType = 'sale'; // 'sale' | 'no_sale'
  int _offtakeStep = 0; // 0: Input & Keranjang, 1: Review & Konfirmasi
  final List<Map<String, dynamic>> _offtakeCart = [];
  TemplateProductModel? _currentOfftakeProduct;

  final TextEditingController _offtakeQtyTinCtrl = TextEditingController();
  final TextEditingController _offtakeQtyGalonCtrl = TextEditingController();
  final TextEditingController _offtakeQtyPailCtrl = TextEditingController();

  final TextEditingController _offtakeCustMasukCtrl = TextEditingController();
  final TextEditingController _offtakeCustBeliCatCtrl = TextEditingController();
  final TextEditingController _offtakeCustBeliDuluxCtrl = TextEditingController();

  File? _offtakeCardPhoto;
  String? _offtakeCardPhotoWatermark;
  final List<File> _offtakeNotaPhotos = [];
  final List<String> _offtakeNotaPhotoWatermarks = [];

  // Out of Stock (OOS) Reporting State
  String _oosType = 'oos'; // 'oos' | 'no_oos'
  int _oosStep = 0; // 0: Input & Keranjang, 1: Review & Konfirmasi
  final List<Map<String, dynamic>> _oosCart = [];
  TemplateProductModel? _currentOosProduct;

  final TextEditingController _oosChannelTokoCtrl = TextEditingController(text: 'SSO (Traditional Trade / Retail)');
  final TextEditingController _oosKemasanSizeCtrl = TextEditingController();
  final TextEditingController _oosBaseWarnaCtrl = TextEditingController();
  final TextEditingController _oosReadyMixColorCtrl = TextEditingController();
  final TextEditingController _oosLamaHariCtrl = TextEditingController(text: '1');
  final TextEditingController _oosSaranQtyCtrl = TextEditingController(text: '2');
  final TextEditingController _oosAlasanCtrl = TextEditingController();
  final TextEditingController _oosAlasanLainnyaCtrl = TextEditingController();

  // Previous OOS History / Reference for consecutive calculation
  List<Map<String, dynamic>> _previousOosItems = [];
  int _oosDiffDays = 1;
  String? _previousOosDate;

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

    _initSubmittedProducts();
    _initSubmittedMachines();
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
      _initStoreMachineForDailyMaintenance();
    });

    if (_isOosTemplate() && _selectedWorkLocationId != null) {
      _fetchOosHistoryForStore(_selectedWorkLocationId!);
    }

    _recalculateRadius();
  }

  Future<void> _fetchOosHistoryForStore(int storeId) async {
    try {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
      if (auth.token == null) return;
      final res = await repProvider.fetchOosHistory(auth.token!, storeId);
      if (res != null && mounted) {
        setState(() {
          _oosDiffDays = int.tryParse(res['diff_days']?.toString() ?? '1') ?? 1;
          _previousOosDate = res['previous_date']?.toString();
          if (res['previous_oos_items'] is List) {
            _previousOosItems = (res['previous_oos_items'] as List)
                .whereType<Map>()
                .map((e) => Map<String, dynamic>.from(e))
                .toList();
          } else {
            _previousOosItems = [];
          }
        });
      }
    } catch (e) {
      debugPrint('Error loading OOS history for store: $e');
    }
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

  bool _isDailyMaintenanceTemplate() {
    final code = widget.template.code.toUpperCase();
    final title = widget.template.title.toLowerCase();
    return code == 'RPT-DULUX-DAILY-MAINTENANCE' ||
        code.contains('DAILY-MAINTENANCE') ||
        title.contains('daily maintenance');
  }

  Map<String, String> _getStoreMachinesMap() {
    final Map<String, String> map = {};

    // 1. Dari array 'machines' pada data store JSON (_selectedLocation)
    if (_selectedLocation != null) {
      final rawMachines = _selectedLocation!['machines'];
      if (rawMachines is List && rawMachines.isNotEmpty) {
        for (final m in rawMachines) {
          if (m is Map) {
            final type = m['machine_type']?.toString().trim() ?? m['type']?.toString().trim() ?? '';
            final serial = m['machine_serial_no']?.toString().trim() ?? m['serial_no']?.toString().trim() ?? m['no']?.toString().trim() ?? '';
            if (type.isNotEmpty) {
              map[type] = serial;
            }
          }
        }
      }

      // 2. Dari field scalar 'machine_type' dan 'machine_serial_no' pada _selectedLocation
      final singleType = _selectedLocation!['machine_type']?.toString().trim() ?? '';
      final singleSerial = _selectedLocation!['machine_serial_no']?.toString().trim() ?? '';
      if (singleType.isNotEmpty && !map.containsKey(singleType)) {
        map[singleType] = singleSerial;
      }
    }

    // 3. Dari array 'storeMachines' pada widget.template
    if (widget.template.storeMachines.isNotEmpty) {
      for (final m in widget.template.storeMachines) {
        final type = m['machine_type']?.toString().trim() ?? m['type']?.toString().trim() ?? '';
        final serial = m['machine_serial_no']?.toString().trim() ?? m['serial_no']?.toString().trim() ?? m['no']?.toString().trim() ?? '';
        if (type.isNotEmpty && !map.containsKey(type)) {
          map[type] = serial;
        }
      }
    }

    // 4. Default / Fallback khusus Toko Demo Arina Rajawali (wajib 2 mesin: Type Mesin 1 & Type Mesin 2)
    final storeNameLower = _selectedStoreName.toLowerCase();
    if (storeNameLower.contains('rajawali') || storeNameLower.contains('arina rajawali')) {
      if (!map.containsKey('Type Mesin 1')) {
        map['Type Mesin 1'] = 'XX-001';
      }
      if (!map.containsKey('Type Mesin 2')) {
        map['Type Mesin 2'] = 'XX-002';
      }
    } else if (storeNameLower.contains('kalilor')) {
      if (!map.containsKey('Mesin D200 (Automatic Tinting)')) {
        map['Mesin D200 (Automatic Tinting)'] = 'POST-2022-SUB-042';
      }
      if (!map.containsKey('Mesin Discovery (Automatic Tinting)')) {
        map['Mesin Discovery (Automatic Tinting)'] = 'POST-2023-SUB-089';
      }
    }

    return map;
  }

  void _autoFillMachineSerial(String? selectedMachine) {
    if (selectedMachine == null) return;
    final storeMachinesMap = _getStoreMachinesMap();
    final isNoMachine = selectedMachine.toLowerCase().contains('tidak memiliki') || selectedMachine.toLowerCase().contains('tidak ada');
    String serialNo = isNoMachine ? '-' : (storeMachinesMap[selectedMachine] ?? '');
    if (!isNoMachine && serialNo.isEmpty && storeMachinesMap.length == 1) {
      serialNo = storeMachinesMap.values.first;
    }

    for (final f in widget.template.fields) {
      final fName = f.fieldName.toLowerCase();
      final fLabel = f.fieldLabel.toLowerCase();
      if (fName == 'no_mesin_post' || fName.contains('no_mesin') || fLabel.contains('no mesin') || fLabel.contains('nomor seri')) {
        final serialKey = f.id.toString();
        if (_controllers.containsKey(serialKey)) {
          _controllers[serialKey]!.text = serialNo;
        } else {
          _controllers[serialKey] = TextEditingController(text: serialNo);
        }
        _formValues[serialKey] = serialNo;
        _formValues['no_mesin_post'] = serialNo;
        _formValues[f.fieldName] = serialNo;
      }
    }
  }

  void _initStoreMachineForDailyMaintenance() {
    if (!_isDailyMaintenanceTemplate()) return;
    if (widget.editSubmission != null) return;

    final storeMachinesMap = _getStoreMachinesMap();
    if (storeMachinesMap.isEmpty) return;

    // Cari mesin pertama yang belum dilaporkan
    String? targetMachine;
    for (final m in storeMachinesMap.keys) {
      if (!_isMachineSubmitted(m)) {
        targetMachine = m;
        break;
      }
    }
    targetMachine ??= storeMachinesMap.keys.first;

    for (final f in widget.template.fields) {
      final fName = f.fieldName.toLowerCase();
      final fLabel = f.fieldLabel.toLowerCase();
      if (fName == 'tipe_mesin_post' || fName.contains('tipe_mesin') || fLabel.contains('tipe mesin')) {
        final machineKey = f.id.toString();
        final currentVal = _formValues[machineKey]?.toString();
        if (currentVal == null || currentVal.isEmpty || _isMachineSubmitted(currentVal) || !storeMachinesMap.containsKey(currentVal)) {
          _formValues[machineKey] = targetMachine;
          _formValues['tipe_mesin_post'] = targetMachine;
          _formValues[f.fieldName] = targetMachine;
          _autoFillMachineSerial(targetMachine);
        } else {
          _autoFillMachineSerial(currentVal);
        }
      }
    }
  }

  bool _isCalculatedField(ReportFormFieldModel field) {
    if (field.isReadonly) return true;
    final name = field.fieldName.toLowerCase();
    return name == 'volume_galon_l' ||
           name == 'volume_pail_l' ||
           name == 'total_volume_unit' ||
           name == 'total_volume_liter' ||
           name == 'total_volume_stok_liter' ||
           name == 'volume_liter' ||
           name == 'estimasi_market_share_persen';
  }

  bool _isCategoryField(ReportFormFieldModel field) {
    final name = field.fieldName.toLowerCase();
    final label = field.fieldLabel.toLowerCase();
    return name.contains('kategori') ||
           label.contains('kategori') ||
           name.contains('category') ||
           label.contains('category') ||
           name == 'produk_dulux_cbp' ||
           name == 'kategori_produk';
  }

  void _recalculateFormulas() {
    // Helper untuk ambil nilai numerik dari formValues atau controller
    double getNumVal(String fieldNameOrKey) {
      for (final f in widget.template.fields) {
        if (f.fieldName.toLowerCase() == fieldNameOrKey.toLowerCase() || f.id.toString() == fieldNameOrKey) {
          final k = f.id.toString();
          final raw = _controllers[k]?.text.replaceAll(RegExp(r'[^0-9.]'), '') ?? _formValues[k]?.toString() ?? '';
          return double.tryParse(raw) ?? 0.0;
        }
      }
      final raw = _controllers[fieldNameOrKey]?.text.replaceAll(RegExp(r'[^0-9.]'), '') ?? _formValues[fieldNameOrKey]?.toString() ?? '';
      return double.tryParse(raw) ?? 0.0;
    }

    // Helper untuk ambil string value (misal kemasan)
    String getStrVal(String fieldNameOrKey) {
      for (final f in widget.template.fields) {
        if (f.fieldName.toLowerCase() == fieldNameOrKey.toLowerCase() || f.id.toString() == fieldNameOrKey) {
          final k = f.id.toString();
          return _formValues[k]?.toString() ?? _controllers[k]?.text ?? '';
        }
      }
      return _formValues[fieldNameOrKey]?.toString() ?? _controllers[fieldNameOrKey]?.text ?? '';
    }

    // Helper untuk parse ukuran liter dari string kemasan (misal "2.5 Liter" -> 2.5, "20 Liter" -> 20.0, "0.8 L" -> 0.8)
    double parseLitersFromKemasan(String kemasanStr) {
      if (kemasanStr.isEmpty || kemasanStr.toLowerCase().contains('tidak ada')) return 0.0;
      final match = RegExp(r'([0-9]+(?:\.[0-9]+)?)').firstMatch(kemasanStr);
      if (match != null) {
        return double.tryParse(match.group(1) ?? '0') ?? 0.0;
      }
      return 0.0;
    }

    // Helper untuk update field target calculated
    void updateCalculatedField(String targetFieldName, double val, {bool isInteger = false, String suffix = ''}) {
      for (final f in widget.template.fields) {
        if (f.fieldName.toLowerCase() == targetFieldName.toLowerCase()) {
          final k = f.id.toString();
          final formatted = val <= 0 && suffix.isEmpty
              ? ''
              : (isInteger ? val.toInt().toString() : (val % 1 == 0 ? val.toInt().toString() : val.toStringAsFixed(2)) + suffix);
          
          if (_controllers.containsKey(k) && _controllers[k]!.text != formatted) {
            _controllers[k]!.text = formatted;
          }
          _formValues[k] = isInteger ? val.toInt() : (val % 1 == 0 ? val.toInt() : double.parse(val.toStringAsFixed(2)));
        }
      }
    }

    // 1. OFFTAKE CALCULATIONS (Volume Galon, Volume Pail, Total Unit, Total Volume Liter)
    final qtyGalon = getNumVal('qty_galon');
    final kemasanGalonStr = getStrVal('kemasan_galon');
    final galonSizeL = parseLitersFromKemasan(kemasanGalonStr);
    final effectiveGalonSize = galonSizeL > 0 ? galonSizeL : (kemasanGalonStr.isNotEmpty && !kemasanGalonStr.toLowerCase().contains('tidak ada') ? 2.5 : (qtyGalon > 0 ? 2.5 : 0.0));
    final volGalon = qtyGalon * effectiveGalonSize;

    final qtyPail = getNumVal('qty_pail');
    final kemasanPailStr = getStrVal('kemasan_pail');
    final pailSizeL = parseLitersFromKemasan(kemasanPailStr);
    final effectivePailSize = pailSizeL > 0 ? pailSizeL : (kemasanPailStr.isNotEmpty && !kemasanPailStr.toLowerCase().contains('tidak ada') ? 20.0 : (qtyPail > 0 ? 20.0 : 0.0));
    final volPail = qtyPail * effectivePailSize;

    final totalUnits = qtyGalon + qtyPail;
    final totalVolL = volGalon + volPail;

    updateCalculatedField('volume_galon_l', volGalon);
    updateCalculatedField('volume_pail_l', volPail);
    updateCalculatedField('total_volume_unit', totalUnits, isInteger: true);
    updateCalculatedField('total_volume_liter', totalVolL);

    // 2. STOCK END CALCULATIONS (Total Volume Liter)
    final stokQtyGalon = getNumVal('stok_qty_galon') > 0 ? getNumVal('stok_qty_galon') : getNumVal('kuantiti_galon');
    final stokKemasanGalon = getStrVal('kemasan_galon');
    final stokGalonSize = parseLitersFromKemasan(stokKemasanGalon) > 0 ? parseLitersFromKemasan(stokKemasanGalon) : 2.5;

    final stokQtyPail = getNumVal('stok_qty_pail') > 0 ? getNumVal('stok_qty_pail') : getNumVal('kuantiti_pail');
    final stokKemasanPail = getStrVal('kemasan_pail');
    final stokPailSize = parseLitersFromKemasan(stokKemasanPail) > 0 ? parseLitersFromKemasan(stokKemasanPail) : 20.0;

    final stokTotalVol = (stokQtyGalon * stokGalonSize) + (stokQtyPail * stokPailSize);
    updateCalculatedField('total_volume_stok_liter', stokTotalVol);
    updateCalculatedField('volume_liter', stokTotalVol);

    // 3. TRAFIK PEMBELI (Market Share %)
    final jmlBeliCat = getNumVal('jml_customer_beli_cat');
    final jmlBeliDulux = getNumVal('jml_customer_beli_dulux');
    if (jmlBeliCat > 0) {
      final marketSharePct = (jmlBeliDulux / jmlBeliCat) * 100.0;
      updateCalculatedField('estimasi_market_share_persen', marketSharePct > 100 ? 100 : marketSharePct, suffix: '%');
    }
  }

  // 4. CBP AUTO-CALCULATION MUTUAL DISCOUNTS (Nominal <-> Persen)
  void _syncCbpDiscounts(String triggerSource) {
    if (_isSyncingDiscounts) return;
    _isSyncingDiscounts = true;

    try {
      ReportFormFieldModel? hargaField;
      ReportFormFieldModel? nominalField;
      ReportFormFieldModel? persenField;

      for (final f in widget.template.fields) {
        final name = f.fieldName.toLowerCase();
        if (name == 'harga_cbp_dulux_rp' || name.contains('harga_cbp')) hargaField = f;
        if (name == 'diskon_promo_nominal_rp' || (name.contains('diskon') && name.contains('nominal'))) nominalField = f;
        if (name == 'diskon_promo_persen' || (name.contains('diskon') && name.contains('persen'))) persenField = f;
      }

      if (hargaField == null || nominalField == null || persenField == null) return;

      final hargaKey = hargaField.id.toString();
      final nominalKey = nominalField.id.toString();
      final persenKey = persenField.id.toString();

      final rawHarga = _controllers[hargaKey]?.text.replaceAll(RegExp(r'[^0-9]'), '') ?? '';
      final double harga = double.tryParse(rawHarga) ?? 0.0;

      if (triggerSource == 'nominal') {
        _activeDiscountType = 'nominal';
        final rawNominal = _controllers[nominalKey]?.text.replaceAll(RegExp(r'[^0-9]'), '') ?? '';
        final double nominal = double.tryParse(rawNominal) ?? 0.0;

        if (nominal > 0 && harga > 0) {
          final double persen = (nominal / harga) * 100.0;
          final String formattedPersen = (persen % 1 == 0) ? persen.toInt().toString() : persen.toStringAsFixed(1);
          if (_controllers[persenKey]?.text != formattedPersen) {
            _controllers[persenKey]?.text = formattedPersen;
          }
          final parsedNum = double.tryParse(formattedPersen) ?? persen;
          _formValues[persenKey] = parsedNum;
          _formValues[persenField.fieldName] = parsedNum;
        } else if (nominal == 0) {
          _controllers[persenKey]?.clear();
          _formValues.remove(persenKey);
          _formValues.remove(persenField.fieldName);
        }
      } else if (triggerSource == 'persen') {
        _activeDiscountType = 'persen';
        final rawPersen = _controllers[persenKey]?.text.replaceAll(RegExp(r'[^0-9.]'), '') ?? '';
        final double persen = double.tryParse(rawPersen) ?? 0.0;

        if (persen > 0 && harga > 0) {
          final double nominal = (persen / 100.0) * harga;
          final int intNominal = nominal.round();
          final formatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
          final String formattedNominal = formatter.format(intNominal);

          if (_controllers[nominalKey]?.text != formattedNominal) {
            _controllers[nominalKey]?.value = TextEditingValue(
              text: formattedNominal,
              selection: TextSelection.collapsed(offset: formattedNominal.length),
            );
          }
          _formValues[nominalKey] = intNominal;
          _formValues[nominalField.fieldName] = intNominal;
        } else if (persen == 0) {
          _controllers[nominalKey]?.clear();
          _formValues.remove(nominalKey);
          _formValues.remove(nominalField.fieldName);
        }
      } else if (triggerSource == 'harga') {
        if (_activeDiscountType == 'nominal') {
          _isSyncingDiscounts = false;
          _syncCbpDiscounts('nominal');
          return;
        } else if (_activeDiscountType == 'persen') {
          _isSyncingDiscounts = false;
          _syncCbpDiscounts('persen');
          return;
        }
      }
    } finally {
      _isSyncingDiscounts = false;
    }
  }

  void _formatCustomCurrency(TextEditingController ctrl, String value) {
    String cleanDigits = value.replaceAll(RegExp(r'[^0-9]'), '');
    if (cleanDigits.isEmpty) {
      ctrl.value = const TextEditingValue(text: '');
      return;
    }

    int intValue = int.tryParse(cleanDigits) ?? 0;
    final formatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
    String formatted = formatter.format(intValue);

    ctrl.value = TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }

  void _initializeForm() {
    for (final field in widget.template.fields) {
      final fieldKey = field.id.toString();

      // Inisialisasi default values
      if (field.defaultValue != null && field.defaultValue!.isNotEmpty) {
        _formValues[fieldKey] = field.defaultValue;
      }

      if (['text', 'textarea', 'number', 'integer', 'currency', 'percentage', 'date', 'datepicker', 'time', 'timepicker', 'datetime', 'product', 'product_select', 'barcode_scanner', 'month_year', 'month', 'year_month'].contains(field.fieldType) || _isProductField(field)) {
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
        } else if (['text', 'textarea', 'number', 'integer', 'percentage', 'date', 'datepicker', 'time', 'timepicker', 'datetime', 'month_year', 'month', 'year_month'].contains(val.fieldType)) {
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

    // Inisialisasi daftar produk kompetitor dinamis untuk formulir CBP
    final bool isCbp = widget.template.code == 'RPT-DULUX-CBP-PRICING' || widget.template.code.contains('CBP');
    if (isCbp) {
      for (final itm in _competitorItems) {
        itm.dispose();
      }
      _competitorItems.clear();
      bool loadedExisting = false;

      if (widget.editSubmission != null) {
        // 1. Coba baca dari data_kompetitor_list
        for (final val in widget.editSubmission!.values) {
          if (val.fieldName == 'data_kompetitor_list' && val.valueJson is List) {
            for (final item in (val.valueJson as List)) {
              if (item is Map) {
                final pTin = item['harga_tin'];
                final pGalon = item['harga_galon'];
                final pPail = item['harga_pail'];
                _competitorItems.add(CompetitorInputItem(
                  merk: item['merk']?.toString() ?? 'JOTUN',
                  subbrand: item['subbrand']?.toString() ?? '',
                  tin: pTin != null && pTin != 0 ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(pTin) : '',
                  galon: pGalon != null && pGalon != 0 ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(pGalon) : '',
                  pail: pPail != null && pPail != 0 ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(pPail) : '',
                ));
              }
            }
            if (_competitorItems.isNotEmpty) {
              loadedExisting = true;
            }
            break;
          }
        }

        // 2. Fallback jika data sebelumnya tersimpan di field flat
        if (!loadedExisting) {
          String fallbackMerk = 'JOTUN';
          String fallbackSubbrand = '';
          String fallbackTin = '';
          String fallbackGalon = '';
          String fallbackPail = '';

          for (final val in widget.editSubmission!.values) {
            final fName = val.fieldName.toLowerCase();
            if (fName == 'merk_kompetitor') fallbackMerk = val.valueText ?? 'JOTUN';
            if (fName == 'subbrand_kompetitor') fallbackSubbrand = val.valueText ?? '';
            if (fName == 'harga_kompetitor_tin_rp') {
              fallbackTin = val.valueNumber != null && val.valueNumber != 0 ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val.valueNumber) : (val.valueText ?? '');
            }
            if (fName == 'harga_kompetitor_galon_rp') {
              fallbackGalon = val.valueNumber != null && val.valueNumber != 0 ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val.valueNumber) : (val.valueText ?? '');
            }
            if (fName == 'harga_kompetitor_pail_rp') {
              fallbackPail = val.valueNumber != null && val.valueNumber != 0 ? NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val.valueNumber) : (val.valueText ?? '');
            }
          }

          if (fallbackSubbrand.isNotEmpty || fallbackTin.isNotEmpty || fallbackGalon.isNotEmpty || fallbackPail.isNotEmpty) {
            _competitorItems.add(CompetitorInputItem(
              merk: fallbackMerk,
              subbrand: fallbackSubbrand,
              tin: fallbackTin,
              galon: fallbackGalon,
              pail: fallbackPail,
            ));
            loadedExisting = true;
          }
        }
      }

      if (!loadedExisting && _competitorItems.isEmpty) {
        _competitorItems.add(CompetitorInputItem(merk: 'JOTUN'));
      }
    }

    // Inisialisasi data laporan Offtake Dulux
    final bool isOfftake = _isOfftakeTemplate();
    if (isOfftake) {
      _offtakeCart.clear();
      _offtakeType = 'sale';
      _offtakeStep = 0;
      _currentOfftakeProduct = null;
      _offtakeQtyTinCtrl.clear();
      _offtakeQtyGalonCtrl.clear();
      _offtakeQtyPailCtrl.clear();
      _offtakeCustMasukCtrl.clear();
      _offtakeCustBeliCatCtrl.clear();
      _offtakeCustBeliDuluxCtrl.clear();
      _offtakeCardPhoto = null;
      _offtakeCardPhotoWatermark = null;
      _offtakeNotaPhotos.clear();
      _offtakeNotaPhotoWatermarks.clear();

      if (widget.editSubmission != null) {
        for (final val in widget.editSubmission!.values) {
          final fn = val.fieldName.toLowerCase();
          if (fn == 'tipe_laporan_offtake') {
            _offtakeType = val.valueText == 'no_sale' ? 'no_sale' : 'sale';
          } else if (fn == 'offtake_items_json') {
            try {
              final raw = val.valueJson ?? val.valueText;
              final list = raw is List ? raw : (raw is String ? jsonDecode(raw) : null);
              if (list is List) {
                for (final itm in list) {
                  if (itm is Map) {
                    _offtakeCart.add(Map<String, dynamic>.from(itm));
                  }
                }
              }
            } catch (e) {
              debugPrint('Error decoding offtake_items_json: $e');
            }
          } else if (fn == 'jml_customer_masuk') {
            _offtakeCustMasukCtrl.text = val.valueNumber?.toInt().toString() ?? (val.valueText ?? '');
          } else if (fn == 'jml_customer_beli_cat') {
            _offtakeCustBeliCatCtrl.text = val.valueNumber?.toInt().toString() ?? (val.valueText ?? '');
          } else if (fn == 'jml_customer_beli_dulux') {
            _offtakeCustBeliDuluxCtrl.text = val.valueNumber?.toInt().toString() ?? (val.valueText ?? '');
          }
        }
      }
    }

    // Inisialisasi data laporan Out of Stock (OOS) Dulux
    final bool isOos = _isOosTemplate();
    if (isOos) {
      _oosCart.clear();
      _oosType = 'oos';
      _oosStep = 0;
      _currentOosProduct = null;
      _oosKemasanSizeCtrl.clear();
      _oosBaseWarnaCtrl.clear();
      _oosReadyMixColorCtrl.clear();
      _oosLamaHariCtrl.text = '1';
      _oosSaranQtyCtrl.text = '2';
      _oosAlasanCtrl.clear();
      _oosAlasanLainnyaCtrl.clear();

      // Load initial OOS reference from template if passed
      if (widget.template.oosReference != null) {
        final ref = widget.template.oosReference!;
        _oosDiffDays = int.tryParse(ref['diff_days']?.toString() ?? '1') ?? 1;
        _previousOosDate = ref['previous_date']?.toString();
        if (ref['previous_oos_items'] is List) {
          _previousOosItems = (ref['previous_oos_items'] as List)
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
        }
      }

      // If store is already known, load store's fresh OOS history
      if (_selectedWorkLocationId != null) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _fetchOosHistoryForStore(_selectedWorkLocationId!);
        });
      }

      if (widget.editSubmission != null) {
        for (final val in widget.editSubmission!.values) {
          final fn = val.fieldName.toLowerCase();
          if (fn == 'tipe_laporan_oos') {
            _oosType = val.valueText == 'no_oos' ? 'no_oos' : 'oos';
          } else if (fn == 'oos_items_json') {
            try {
              final raw = val.valueJson ?? val.valueText;
              final list = raw is List ? raw : (raw is String ? jsonDecode(raw) : null);
              if (list is List) {
                for (final itm in list) {
                  if (itm is Map) {
                    _oosCart.add(Map<String, dynamic>.from(itm));
                  }
                }
              }
            } catch (e) {
              debugPrint('Error decoding oos_items_json: $e');
            }
          }
        }
      }
    }

    // Attach reactive calculation listeners
    for (final ctrl in _controllers.values) {
      ctrl.addListener(_recalculateFormulas);
    }

    // Pre-select first unsubmitted product if in sequential product mode
    if (widget.editSubmission == null && _hasProductBinding()) {
      _autoSelectNextUnsubmittedProduct();
    }

    _recalculateFormulas();
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
    _offtakeQtyTinCtrl.dispose();
    _offtakeQtyGalonCtrl.dispose();
    _offtakeQtyPailCtrl.dispose();
    _offtakeCustMasukCtrl.dispose();
    _offtakeCustBeliCatCtrl.dispose();
    _offtakeCustBeliDuluxCtrl.dispose();

    for (final itm in _competitorItems) {
      itm.dispose();
    }
    for (final ctrl in _controllers.values) {
      ctrl.removeListener(_recalculateFormulas);
      ctrl.dispose();
    }
    _scrollController.dispose();
    super.dispose();
  }

  // Nama-nama bulan Indonesia
  static const List<String> _indoMonths = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

  // Format Bulan & Tahun display (MM/yyyy -> "Agustus 2026")
  String _formatMonthYearDisplay(String val) {
    if (val.trim().isEmpty) return '';
    final clean = val.trim();

    // Cek format MM/yyyy (misal: 08/2026)
    if (clean.contains('/')) {
      final parts = clean.split('/');
      if (parts.length == 2) {
        final m = int.tryParse(parts[0]);
        final y = int.tryParse(parts[1]);
        if (m != null && y != null && m >= 1 && m <= 12) {
          return '${_indoMonths[m - 1]} $y';
        }
      }
    }

    // Cek format yyyy-MM atau yyyy-MM-dd
    if (clean.contains('-')) {
      final parts = clean.split('-');
      if (parts.length >= 2) {
        final y = int.tryParse(parts[0]);
        final m = int.tryParse(parts[1]);
        if (m != null && y != null && m >= 1 && m <= 12) {
          return '${_indoMonths[m - 1]} $y';
        }
      }
    }

    return clean;
  }

  // Pemilih Bulan & Tahun Interaktif (Month & Year Dialog)
  Future<void> _pickMonthYear(String fieldKey, ReportFormFieldModel field) async {
    int selectedYear = DateTime.now().year;
    int? selectedMonth = DateTime.now().month;

    final currentVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
    if (currentVal.isNotEmpty) {
      if (currentVal.contains('/')) {
        final parts = currentVal.split('/');
        if (parts.length == 2) {
          final m = int.tryParse(parts[0]);
          final y = int.tryParse(parts[1]);
          if (y != null) selectedYear = y;
          if (m != null && m >= 1 && m <= 12) selectedMonth = m;
        }
      } else if (currentVal.contains('-')) {
        final parts = currentVal.split('-');
        if (parts.length >= 2) {
          final y = int.tryParse(parts[0]);
          final m = int.tryParse(parts[1]);
          if (y != null) selectedYear = y;
          if (m != null && m >= 1 && m <= 12) selectedMonth = m;
        }
      }
    }

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final themeColor = Color(int.tryParse(widget.template.color.replaceAll('#', '0xFF')) ?? primaryColor.value);

    final result = await showDialog<Map<String, int>>(
      context: context,
      builder: (dialogCtx) {
        int tempYear = selectedYear;
        int? tempMonth = selectedMonth;

        return StatefulBuilder(
          builder: (context, setDialogState) {
            final dialogBg = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
            final textCol = isDarkMode ? Colors.white : const Color(0xFF0E1830);

            return AlertDialog(
              backgroundColor: dialogBg,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              titlePadding: const EdgeInsets.fromLTRB(20, 20, 20, 10),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              title: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(Icons.event_note_rounded, color: themeColor, size: 20),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          field.fieldLabel,
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textCol),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          'Pilih Bulan & Tahun Expired',
                          style: TextStyle(fontSize: 11, color: isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              content: SizedBox(
                width: 320,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Year Selector Bar
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: isDarkMode ? Colors.grey.shade800 : const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          IconButton(
                            icon: const Icon(Icons.chevron_left_rounded, size: 24),
                            color: textCol,
                            onPressed: () {
                              setDialogState(() {
                                tempYear--;
                              });
                            },
                          ),
                          Text(
                            '$tempYear',
                            style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: themeColor),
                          ),
                          IconButton(
                            icon: const Icon(Icons.chevron_right_rounded, size: 24),
                            color: textCol,
                            onPressed: () {
                              setDialogState(() {
                                tempYear++;
                              });
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // 12 Months Grid (3 columns x 4 rows)
                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 3,
                        childAspectRatio: 2.2,
                        crossAxisSpacing: 8,
                        mainAxisSpacing: 8,
                      ),
                      itemCount: 12,
                      itemBuilder: (context, index) {
                        final monthNum = index + 1;
                        final monthName = _indoMonths[index];
                        final isSelected = tempMonth == monthNum;

                        return InkWell(
                          onTap: () {
                            setDialogState(() {
                              tempMonth = monthNum;
                            });
                            Navigator.of(dialogCtx).pop({'year': tempYear, 'month': monthNum});
                          },
                          borderRadius: BorderRadius.circular(10),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 180),
                            decoration: BoxDecoration(
                              color: isSelected ? themeColor : (isDarkMode ? const Color(0xFF28283C) : const Color(0xFFF8FAFC)),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: isSelected ? themeColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                                width: isSelected ? 1.5 : 1.0,
                              ),
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              monthName,
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                                color: isSelected ? Colors.white : textCol,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogCtx).pop(null),
                  child: Text('Batal', style: TextStyle(color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600)),
                ),
              ],
            );
          },
        );
      },
    );

    if (result != null) {
      final y = result['year']!;
      final m = result['month']!.toString().padLeft(2, '0');
      final formattedValue = '$m/$y'; // format standard: "MM/yyyy"
      setState(() {
        _formValues[fieldKey] = formattedValue;
        _controllers[fieldKey]?.text = formattedValue;
      });
    }
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

  // Submit Form (Single or Multi-Category Continuous Input)
  Future<void> _submitForm({bool isNewInput = false}) async {
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
      if (field.isRequired && !_isCalculatedField(field)) {
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
      final text = ctrl.text.trim();
      if (text.isNotEmpty) {
        ReportFormFieldModel? matchedField;
        for (final f in widget.template.fields) {
          if (f.id.toString() == key || f.fieldName.toLowerCase() == key.toLowerCase()) {
            matchedField = f;
            break;
          }
        }
        if (matchedField != null && matchedField.fieldType == 'currency') {
          final cleanDigits = text.replaceAll(RegExp(r'[^0-9]'), '');
          _formValues[key] = int.tryParse(cleanDigits) ?? 0;
        } else if (matchedField != null && (matchedField.fieldType == 'number' || matchedField.fieldType == 'integer')) {
          final cleanNum = text.replaceAll(',', '.').replaceAll(RegExp(r'[^0-9.]'), '');
          _formValues[key] = num.tryParse(cleanNum) ?? (num.tryParse(text) ?? text);
        } else {
          _formValues[key] = text;
        }
      } else if (!_formValues.containsKey(key)) {
        _formValues[key] = '';
      }
    });

    // Capture category or item identifier for continuous input tracking
    String? submittedCategoryValue;
    for (final f in widget.template.fields) {
      if (_isCategoryField(f)) {
        final k = f.id.toString();
        final val = _formValues[k]?.toString() ?? _controllers[k]?.text;
        if (val != null && val.trim().isNotEmpty) {
          submittedCategoryValue = val.trim();
          break;
        }
      }
    }
    if (submittedCategoryValue == null || submittedCategoryValue.isEmpty) {
      for (final f in widget.template.fields) {
        if (_isProductField(f)) {
          final k = f.id.toString();
          final val = _formValues[k]?.toString() ?? _controllers[k]?.text;
          if (val != null && val.trim().isNotEmpty) {
            submittedCategoryValue = val.trim();
            break;
          }
        }
      }
    }
    submittedCategoryValue ??= 'Item ${_submittedCategories.length + 1}';

    // Capture machine identifier for Daily Maintenance
    String? submittedMachineValue;
    if (_isDailyMaintenanceTemplate()) {
      for (final f in widget.template.fields) {
        final fn = f.fieldName.toLowerCase();
        if (fn == 'tipe_mesin_post' || fn.contains('tipe_mesin')) {
          final k = f.id.toString();
          final val = _formValues[k]?.toString() ?? _controllers[k]?.text ?? _formValues['tipe_mesin_post']?.toString();
          if (val != null && val.trim().isNotEmpty) {
            submittedMachineValue = val.trim();
            break;
          }
        }
      }
      if (widget.editSubmission == null && _hasMachineBinding() && submittedMachineValue != null && submittedMachineValue.isNotEmpty && _isMachineSubmitted(submittedMachineValue) && !submittedMachineValue.toLowerCase().contains('tidak memiliki')) {
        toastification.show(
          context: context,
          type: ToastificationType.warning,
          title: const Text('Mesin Sudah Dilaporkan'),
          description: Text('Mesin "$submittedMachineValue" sudah dilaporkan hari ini. Silakan pilih mesin lain yang belum dilaporkan.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
        setState(() => _isSubmitting = false);
        return;
      }
    }

    // Cegah pengiriman ganda untuk produk yang telah dilaporkan hari ini
    if (widget.editSubmission == null && _hasProductBinding() && submittedCategoryValue.isNotEmpty && _isProductSubmitted(submittedCategoryValue)) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Produk Sudah Dilaporkan'),
        description: Text('Produk "$submittedCategoryValue" sudah dilaporkan hari ini. Silakan pilih produk lain.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      setState(() => _isSubmitting = false);
      return;
    }

    // Buat salinan bersih dari formValues tanpa path file lokal perangkat
    // Kirim dengan DUA key: ID angka (f.id) dan field_name agar server 100% selalu cocok
    final Map<String, dynamic> cleanFormValues = {};
    for (var f in widget.template.fields) {
      if (['photo', 'camera_photo', 'multi_photo', 'signature'].contains(f.fieldType)) {
        continue;
      }
      final fieldKey = f.id.toString();
      dynamic val = _formValues[fieldKey] ?? _formValues[f.fieldName] ?? _controllers[fieldKey]?.text;
      if (f.fieldType == 'currency') {
        final cleanDigits = val?.toString().replaceAll(RegExp(r'[^0-9]'), '') ?? '';
        val = int.tryParse(cleanDigits) ?? 0;
      } else if (f.fieldType == 'number' || f.fieldType == 'integer') {
        final cleanNum = val?.toString().replaceAll(',', '.').replaceAll(RegExp(r'[^0-9.]'), '') ?? '';
        val = num.tryParse(cleanNum) ?? (num.tryParse(val?.toString() ?? '') ?? val);
      }
      if (val != null) {
        cleanFormValues[fieldKey] = val;
        cleanFormValues[f.fieldName] = val;
      }
    }
    _formValues.forEach((k, v) {
      if (!cleanFormValues.containsKey(k) && v != null) {
        cleanFormValues[k] = v;
      }
    });

    // Khusus Laporan CBP: Serialize daftar produk kompetitor dinamis ke JSON dan sinkronkan item pertama ke field flat
    final bool isCbp = widget.template.code == 'RPT-DULUX-CBP-PRICING' || widget.template.code.contains('CBP');
    if (isCbp && _competitorItems.isNotEmpty) {
      final List<Map<String, dynamic>> compList = [];
      for (final item in _competitorItems) {
        final merk = item.merk.trim();
        final subbrand = item.subbrandCtrl.text.trim();
        final tinStr = item.tinCtrl.text.replaceAll(RegExp(r'[^0-9]'), '');
        final galonStr = item.galonCtrl.text.replaceAll(RegExp(r'[^0-9]'), '');
        final pailStr = item.pailCtrl.text.replaceAll(RegExp(r'[^0-9]'), '');

        final tinVal = int.tryParse(tinStr) ?? 0;
        final galonVal = int.tryParse(galonStr) ?? 0;
        final pailVal = int.tryParse(pailStr) ?? 0;

        if (subbrand.isNotEmpty || tinVal > 0 || galonVal > 0 || pailVal > 0) {
          compList.add({
            'merk': merk.isNotEmpty ? merk : 'JOTUN',
            'subbrand': subbrand,
            'harga_tin': tinVal,
            'harga_galon': galonVal,
            'harga_pail': pailVal,
          });
        }
      }

      cleanFormValues['data_kompetitor_list'] = compList;

      // Sinkronkan kompetitor ke-1 ke field flat agar 100% backward compatible
      if (compList.isNotEmpty) {
        final first = compList.first;
        cleanFormValues['merk_kompetitor'] = first['merk'];
        cleanFormValues['subbrand_kompetitor'] = first['subbrand'];
        cleanFormValues['harga_kompetitor_tin_rp'] = first['harga_tin'];
        cleanFormValues['harga_kompetitor_galon_rp'] = first['harga_galon'];
        cleanFormValues['harga_kompetitor_pail_rp'] = first['harga_pail'];

        for (final f in widget.template.fields) {
          final fn = f.fieldName.toLowerCase();
          final fKey = f.id.toString();
          if (fn == 'merk_kompetitor') cleanFormValues[fKey] = first['merk'];
          if (fn == 'subbrand_kompetitor') cleanFormValues[fKey] = first['subbrand'];
          if (fn == 'harga_kompetitor_tin_rp') cleanFormValues[fKey] = first['harga_tin'];
          if (fn == 'harga_kompetitor_galon_rp') cleanFormValues[fKey] = first['harga_galon'];
          if (fn == 'harga_kompetitor_pail_rp') cleanFormValues[fKey] = first['harga_pail'];
        }
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
      if (attProvider.isVisiting) {
        attProvider.markVisitReportFilled();
      }

      if (isNewInput) {
        setState(() {
          if (submittedMachineValue != null && submittedMachineValue.isNotEmpty) {
            _submittedMachineNames.add(submittedMachineValue.toLowerCase().trim());
          }
          _submittedCategories.add(submittedCategoryValue!);
          _submittedCategoryLog.add(submittedCategoryValue);
          _submittedProductNames.add(submittedCategoryValue.toLowerCase().trim());
          _sessionSubmissionCount++;

          // Reset non-persistent fields for next category / product entry
          for (final f in widget.template.fields) {
            final fieldKey = f.id.toString();
            if (!['date', 'datepicker'].contains(f.fieldType)) {
              _controllers[fieldKey]?.clear();
              _formValues.remove(fieldKey);
              _formValues.remove(f.fieldName);
            }
          }
          if (isCbp) {
            for (final itm in _competitorItems) {
              itm.dispose();
            }
            _competitorItems.clear();
            _competitorItems.add(CompetitorInputItem(merk: 'JOTUN'));
          }
          _photoFiles.clear();
          _multiPhotoFiles.clear();
          _watermarkTexts.clear();
          _existingPhotoUrls.clear();
          _existingMultiPhotoUrls.clear();
        });

        _recalculateFormulas();

        // Otomatis pilih produk atau mesin berikutnya yang belum dilaporkan
        if (_isDailyMaintenanceTemplate()) {
          _autoSelectNextUnsubmittedMachine();
        } else {
          _autoSelectNextUnsubmittedProduct();
        }

        if (_scrollController.hasClients) {
          _scrollController.animateTo(
            0,
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeOut,
          );
        }

        // Sinkronkan status template di background
        final auth = Provider.of<AuthProvider>(context, listen: false);
        if (auth.token != null) {
          repProvider.fetchTemplates(auth.token!, forceRefresh: true, storeId: _selectedWorkLocationId);
        }

        if (_isDailyMaintenanceTemplate()) {
          final int remM = _getRemainingMachinesCount();
          toastification.show(
            context: context,
            type: result['is_offline'] == true ? ToastificationType.info : ToastificationType.success,
            title: Text(result['is_offline'] == true ? 'Tersimpan Offline' : 'Laporan Mesin Terkirim'),
            description: Text(remM > 0
                ? 'Laporan untuk "$submittedMachineValue" berhasil dikirim. Sisa $remM mesin lagi yang harus dilaporkan hari ini.'
                : 'Laporan untuk "$submittedMachineValue" berhasil dikirim. Seluruh mesin telah selesai dilaporkan!'),
            autoCloseDuration: const Duration(seconds: 4),
          );
        } else {
          final int rem = _getRemainingProductsCount();
          toastification.show(
            context: context,
            type: result['is_offline'] == true ? ToastificationType.info : ToastificationType.success,
            title: Text(result['is_offline'] == true ? 'Tersimpan Offline' : 'Laporan Produk Terkirim'),
            description: Text(rem > 0
                ? 'Laporan untuk "$submittedCategoryValue" berhasil dikirim. Sisa $rem produk lagi yang harus dilaporkan hari ini.'
                : 'Laporan untuk "$submittedCategoryValue" berhasil dikirim. Seluruh produk telah selesai dilaporkan!'),
            autoCloseDuration: const Duration(seconds: 4),
          );
        }
      } else {
        if (submittedMachineValue != null && submittedMachineValue.isNotEmpty) {
          _submittedMachineNames.add(submittedMachineValue.toLowerCase().trim());
        }
        if (submittedCategoryValue != null) {
          _submittedProductNames.add(submittedCategoryValue.toLowerCase().trim());
        }
        toastification.show(
          context: context,
          type: result['is_offline'] == true ? ToastificationType.info : ToastificationType.success,
          title: Text(widget.editSubmission != null ? 'Laporan Diperbarui' : (result['is_offline'] == true ? 'Tersimpan Offline' : 'Laporan Terkirim & Selesai')),
          description: Text(result['message'] ?? 'Seluruh laporan berhasil diselesaikan.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
        Navigator.of(context).pop(true);
      }
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

    if (_isOfftakeTemplate()) {
      return _buildOfftakeScaffold(
        context: context,
        canSubmitReport: canSubmitReport,
        isDarkMode: isDarkMode,
        themeColor: themeColor,
        cardColor: cardColor,
        textColor: textColor,
        subtitleColor: subtitleColor,
        elevatedColor: elevatedColor,
        locale: locale,
      );
    }

    if (_isOosTemplate()) {
      return _buildOosScaffold(
        context: context,
        canSubmitReport: canSubmitReport,
        isDarkMode: isDarkMode,
        themeColor: themeColor,
        cardColor: cardColor,
        textColor: textColor,
        subtitleColor: subtitleColor,
        elevatedColor: elevatedColor,
        locale: locale,
      );
    }

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              widget.editSubmission != null ? 'Edit ${widget.template.title}' : widget.template.title,
              style: TextStyle(color: textColor, fontSize: 15, fontWeight: FontWeight.bold),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            if (_selectedStoreName.isNotEmpty)
              Text(
                _selectedStoreName,
                style: TextStyle(color: subtitleColor, fontSize: 11.5, fontWeight: FontWeight.w500),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: _buildLocationStatusIndicator(canSubmitReport, isDarkMode),
          ),
        ],
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          controller: _scrollController,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          children: [

            // ─── Session Progress Banner (Multi-Category Reporting) ───
            if (_submittedCategories.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFF149A6E).withOpacity(isDarkMode ? 0.15 : 0.08),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFF149A6E).withOpacity(0.4), width: 1.2),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: const Color(0xFF149A6E).withOpacity(0.18),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.check_circle_rounded, color: Color(0xFF149A6E), size: 16),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '${_submittedCategories.length} Kategori / Item Telah Disimpan Sesi Ini',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: isDarkMode ? const Color(0xFF4ADE80) : const Color(0xFF0F7652),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: _submittedCategoryLog.map((cat) => Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: isDarkMode ? const Color(0xFF1E293B) : Colors.white,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFF149A6E).withOpacity(0.35)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.check_rounded, size: 12, color: Color(0xFF149A6E)),
                            const SizedBox(width: 4),
                            Text(
                              cat,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: isDarkMode ? Colors.white : const Color(0xFF0E1830),
                              ),
                            ),
                          ],
                        ),
                      )).toList(),
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

            // Submit Buttons (Single Finish vs Continuous Multi-Category Input)
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
            else if (isEditMode)
              ElevatedButton.icon(
                onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: false),
                icon: _isSubmitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.save_rounded, color: Colors.white, size: 18),
                label: Text(
                  _isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Perubahan Laporan',
                  style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Colors.white),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: themeColor,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  elevation: 2,
                ),
              )
            else if (_isDailyMaintenanceTemplate() || (_hasMachineBinding() && _getTotalMachinesCount() > 0))
              _buildMachineProgressAndButtons(themeColor, cardColor, textColor, subtitleColor, isDarkMode)
            else if (_hasProductBinding() && _getTotalProductsCount() > 0)
              _buildProductProgressAndButtons(themeColor, cardColor, textColor, subtitleColor, isDarkMode)
            else
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: true),
                      icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
                      label: const Text(
                        'Kirim & Input Baru',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                      ),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: themeColor,
                        side: BorderSide(color: themeColor, width: 1.5),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: false),
                      icon: _isSubmitting
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
                      label: Text(
                        _isSubmitting ? 'Mengirim...' : 'Kirim & Selesai',
                        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: themeColor,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 2,
                      ),
                    ),
                  ),
                ],
              ),
            const SizedBox(height: 30),
          ],
        ),
      ),
    );
  }

  Widget _buildProductProgressAndButtons(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    bool isDarkMode,
  ) {
    final int total = _getTotalProductsCount();
    final int remaining = _getRemainingProductsCount();
    final int submitted = total >= remaining ? total - remaining : 0;
    final double progress = total > 0 ? (submitted / total).clamp(0.0, 1.0) : 0.0;
    final bool isLastProduct = remaining <= 1;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Product Progress Card
        Container(
          padding: const EdgeInsets.all(14),
          margin: const EdgeInsets.only(bottom: 14),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: isLastProduct
                  ? Colors.green.withOpacity(0.4)
                  : themeColor.withOpacity(0.3),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Icon(
                        isLastProduct ? Icons.check_circle_outline_rounded : Icons.inventory_2_outlined,
                        size: 18,
                        color: isLastProduct ? Colors.green : themeColor,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        'Progres Laporan Produk',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: textColor,
                        ),
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: isLastProduct ? Colors.green.withOpacity(0.12) : themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '$submitted / $total Produk',
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.bold,
                        color: isLastProduct ? Colors.green : themeColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: LinearProgressIndicator(
                  value: progress,
                  backgroundColor: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    isLastProduct ? Colors.green : themeColor,
                  ),
                  minHeight: 7,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.info_outline_rounded,
                    size: 13,
                    color: subtitleColor,
                  ),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      isLastProduct
                          ? (remaining == 0
                              ? 'Seluruh produk telah dilaporkan hari ini ✓'
                              : 'Ini adalah produk terakhir. Tombol "Kirim & Selesai" aktif!')
                          : 'Sisa $remaining produk lagi. Setiap produk dikirim sebagai 1 laporan.',
                      style: TextStyle(fontSize: 11, color: subtitleColor),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // Sequential Submission Action Buttons
        if (remaining > 1) ...[
          ElevatedButton.icon(
            onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: true),
            icon: _isSubmitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim Data Produk...' : 'Kirim & Lanjut Produk Berikutnya',
              style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: themeColor,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 2,
            ),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: null,
            icon: const Icon(Icons.lock_outline_rounded, size: 16, color: Colors.grey),
            label: Text(
              'Kirim & Selesai (Terkunci: sisa $remaining produk)',
              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Colors.grey),
            ),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 12),
              side: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
          ),
        ] else ...[
          ElevatedButton.icon(
            onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: false),
            icon: _isSubmitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim & Menyelesaikan...' : 'Kirim & Selesai (Produk Terakhir ✓)',
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green.shade600,
              padding: const EdgeInsets.symmetric(vertical: 15),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 3,
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildMachineProgressAndButtons(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    bool isDarkMode,
  ) {
    final int total = _getTotalMachinesCount();
    final int remaining = _getRemainingMachinesCount();
    final int submitted = total >= remaining ? total - remaining : 0;
    final double progress = total > 0 ? (submitted / total).clamp(0.0, 1.0) : 0.0;
    final bool isLastMachine = remaining <= 1;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Machine Progress Card
        Container(
          padding: const EdgeInsets.all(14),
          margin: const EdgeInsets.only(bottom: 14),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: isLastMachine
                  ? Colors.green.withOpacity(0.4)
                  : themeColor.withOpacity(0.3),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Icon(
                        isLastMachine ? Icons.check_circle_outline_rounded : Icons.precision_manufacturing_rounded,
                        size: 18,
                        color: isLastMachine ? Colors.green : themeColor,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        'Progres Laporan Mesin Tinting',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: textColor,
                        ),
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: isLastMachine ? Colors.green.withOpacity(0.12) : themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '$submitted / $total Mesin',
                      style: TextStyle(
                        fontSize: 11.5,
                        fontWeight: FontWeight.bold,
                        color: isLastMachine ? Colors.green : themeColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: LinearProgressIndicator(
                  value: progress,
                  backgroundColor: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    isLastMachine ? Colors.green : themeColor,
                  ),
                  minHeight: 7,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(
                    Icons.info_outline_rounded,
                    size: 13,
                    color: subtitleColor,
                  ),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      isLastMachine
                          ? (remaining == 0
                              ? 'Seluruh mesin telah dilaporkan hari ini ✓'
                              : 'Ini adalah mesin terakhir yang wajib dilaporkan. Tombol "Kirim & Selesai" aktif!')
                          : 'Sisa $remaining mesin lagi di toko ini. Seluruh mesin wajib dilaporkan sebelum lanjut ke langkah berikutnya.',
                      style: TextStyle(fontSize: 11, color: subtitleColor),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // Sequential Submission Action Buttons
        if (remaining > 1) ...[
          ElevatedButton.icon(
            onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: true),
            icon: _isSubmitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim Data Mesin...' : 'Kirim & Lanjut Mesin Berikutnya',
              style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: themeColor,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 2,
            ),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: null,
            icon: const Icon(Icons.lock_outline_rounded, size: 16, color: Colors.grey),
            label: Text(
              'Kirim & Selesai (Terkunci: sisa $remaining mesin)',
              style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Colors.grey),
            ),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 12),
              side: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
          ),
        ] else ...[
          ElevatedButton.icon(
            onPressed: _isSubmitting ? null : () => _submitForm(isNewInput: false),
            icon: _isSubmitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim & Menyelesaikan...' : 'Kirim & Selesai (Mesin Terakhir ✓)',
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green.shade600,
              padding: const EdgeInsets.symmetric(vertical: 15),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 3,
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildLocationStatusIndicator(bool canSubmitReport, bool isDarkMode) {
    final Color statusColor;
    final IconData statusIcon;
    final String statusLabel;
    final String statusDialogTitle;
    final String statusDetail;

    if (!canSubmitReport) {
      statusColor = const Color(0xFFE53935); // Merah = Belum Check-in
      statusIcon = Icons.location_off_rounded;
      statusLabel = 'Belum Check-in';
      statusDialogTitle = 'Belum Check-in';
      statusDetail = 'Anda belum melakukan Check-in atau Visit-in di Toko/Store ini. Formulir laporan terkunci dan belum dapat dikirim.';
    } else if (!_isWithinRadius) {
      statusColor = const Color(0xFFF57C00); // Orange = Diluar Radius Lokasi / Store
      statusIcon = Icons.wrong_location_rounded;
      statusLabel = _calculatedDistance != null 
          ? '${_calculatedDistance!.round()}m (Luar)' 
          : 'Diluar Radius';
      statusDialogTitle = 'Diluar Radius Lokasi / Store';
      statusDetail = _calculatedDistance != null
          ? 'Jarak Anda: ${_calculatedDistance!.round()} meter dari titik Store (Batas radius: ${_allowedRadiusMeter.round()}m).'
          : 'Koordinat GPS Anda terdeteksi di luar batas radius Store.';
    } else {
      statusColor = const Color(0xFF149A6E); // Hijau = Dalam Radius Lokasi / Store
      statusIcon = Icons.location_on_rounded;
      statusLabel = _calculatedDistance != null
          ? '${_calculatedDistance!.round()}m (Aman)'
          : 'Dalam Radius';
      statusDialogTitle = 'Dalam Radius Lokasi / Store';
      statusDetail = _calculatedDistance != null
          ? 'Jarak Anda: ${_calculatedDistance!.round()} meter (Aman dalam radius Store ${_allowedRadiusMeter.round()}m).'
          : 'Posisi GPS Anda berada di dalam area radius Store.';
    }

    return Tooltip(
      message: statusDialogTitle,
      child: GestureDetector(
        onTap: () {
          showDialog(
            context: context,
            builder: (ctx) => AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: Row(
                children: [
                  Icon(statusIcon, color: statusColor, size: 24),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      statusDialogTitle,
                      style: TextStyle(color: statusColor, fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(statusDetail, style: const TextStyle(fontSize: 13, height: 1.4)),
                  const SizedBox(height: 12),
                  if (_selectedStoreName.isNotEmpty) ...[
                    Row(
                      children: [
                        const Icon(Icons.storefront_rounded, size: 16, color: Colors.grey),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            _selectedStoreName,
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                  ],
                  if (_latitude != null && _longitude != null) ...[
                    Row(
                      children: [
                        const Icon(Icons.my_location_rounded, size: 16, color: Colors.grey),
                        const SizedBox(width: 6),
                        Text(
                          'GPS: ${_latitude!.toStringAsFixed(4)}, ${_longitude!.toStringAsFixed(4)}',
                          style: const TextStyle(fontSize: 11, color: Colors.grey),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Tutup', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          );
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.12),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: statusColor.withOpacity(0.4), width: 1.2),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  color: statusColor,
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 6),
              Icon(statusIcon, color: statusColor, size: 15),
              const SizedBox(width: 4),
              Text(
                statusLabel,
                style: TextStyle(
                  color: statusColor,
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCompetitorRepeaterSection(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
    LocaleProvider locale,
  ) {
    ReportFormFieldModel? merkField;
    for (final f in widget.template.fields) {
      if (f.fieldName.toLowerCase() == 'merk_kompetitor') {
        merkField = f;
        break;
      }
    }

    final List<String> merkOptions = (merkField != null && merkField.options.isNotEmpty)
        ? merkField.options
        : const [
            'JOTUN',
            'NIPPON PAINT',
            'AVIAN / NO DROP / LENKOTE',
            'MOWILEX',
            'PROPAN',
            'KANSAI / DANAPAINT',
            'PACIFIC PAINT',
            'MERK LAINNYA',
          ];

    final inputBg = isDarkMode ? const Color(0xFF1E1E2D) : const Color(0xFFF8FAFC);
    final borderColor = isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
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
          // Header Section
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: themeColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(Icons.compare_arrows_rounded, size: 20, color: themeColor),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          'Data Produk Kompetitor Sejenis',
                          style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '${_competitorItems.length} Produk',
                            style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: themeColor),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Bisa menambahkan lebih dari 1 produk kompetitor pembanding sejenis.',
                      style: TextStyle(fontSize: 11, color: subtitleColor),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 16),

          // List Repeater Item Cards
          ..._competitorItems.asMap().entries.map((entry) {
            final int index = entry.key;
            final CompetitorInputItem item = entry.value;

            return Container(
              margin: const EdgeInsets.only(bottom: 14),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: inputBg,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: borderColor),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Sub-header per item
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          CircleAvatar(
                            radius: 11,
                            backgroundColor: themeColor,
                            child: Text(
                              '${index + 1}',
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Produk Kompetitor #${index + 1}',
                            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor),
                          ),
                        ],
                      ),
                      if (_competitorItems.length > 1)
                        InkWell(
                          onTap: () {
                            setState(() {
                              final removed = _competitorItems.removeAt(index);
                              removed.dispose();
                            });
                          },
                          borderRadius: BorderRadius.circular(6),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                            child: Row(
                              children: [
                                Icon(Icons.delete_outline_rounded, size: 16, color: Colors.red.shade400),
                                const SizedBox(width: 3),
                                Text(
                                  'Hapus',
                                  style: TextStyle(fontSize: 11, color: Colors.red.shade400, fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
                  const Divider(height: 20),

                  // 1. Merk Kompetitor
                  Text(
                    'Nama Merk Kompetitor',
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: textColor),
                  ),
                  const SizedBox(height: 6),
                  DropdownButtonFormField<String>(
                    value: merkOptions.contains(item.merk) ? item.merk : merkOptions.first,
                    decoration: _inputDecoration('Pilih Merk Kompetitor', elevatedColor, isDarkMode),
                    dropdownColor: cardColor,
                    isExpanded: true,
                    style: TextStyle(color: textColor, fontSize: 13),
                    items: merkOptions.map((m) {
                      return DropdownMenuItem<String>(
                        value: m,
                        child: Text(m, style: TextStyle(fontSize: 12.5, color: textColor)),
                      );
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) {
                        setState(() {
                          item.merk = val;
                        });
                      }
                    },
                  ),
                  const SizedBox(height: 12),

                  // 2. Subbrand Kompetitor
                  Text(
                    'Nama Subbrand Kompetitor',
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: textColor),
                  ),
                  const SizedBox(height: 6),
                  TextFormField(
                    controller: item.subbrandCtrl,
                    style: TextStyle(color: textColor, fontSize: 13),
                    decoration: _inputDecoration('Contoh: Majestic, Weatherbond, No Drop...', elevatedColor, isDarkMode),
                  ),
                  const SizedBox(height: 12),

                  // 3. Harga Jual (Tin / Galon / Pail)
                  Text(
                    'Harga Jual Kompetitor (Tin / Galon / Pail)',
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: textColor),
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      // Tin
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Tin (Rp)', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                            const SizedBox(height: 4),
                            TextFormField(
                              controller: item.tinCtrl,
                              keyboardType: TextInputType.number,
                              style: TextStyle(color: textColor, fontSize: 12),
                              decoration: _inputDecoration('Rp 0', elevatedColor, isDarkMode),
                              onChanged: (v) => _formatCustomCurrency(item.tinCtrl, v),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      // Galon
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Galon (Rp)', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                            const SizedBox(height: 4),
                            TextFormField(
                              controller: item.galonCtrl,
                              keyboardType: TextInputType.number,
                              style: TextStyle(color: textColor, fontSize: 12),
                              decoration: _inputDecoration('Rp 0', elevatedColor, isDarkMode),
                              onChanged: (v) => _formatCustomCurrency(item.galonCtrl, v),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      // Pail
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Pail (Rp)', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                            const SizedBox(height: 4),
                            TextFormField(
                              controller: item.pailCtrl,
                              keyboardType: TextInputType.number,
                              style: TextStyle(color: textColor, fontSize: 12),
                              decoration: _inputDecoration('Rp 0', elevatedColor, isDarkMode),
                              onChanged: (v) => _formatCustomCurrency(item.pailCtrl, v),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          }),

          // Button: Tambah Produk Kompetitor
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () {
                setState(() {
                  _competitorItems.add(CompetitorInputItem(merk: 'JOTUN'));
                });
              },
              icon: Icon(Icons.add_circle_outline_rounded, size: 18, color: themeColor),
              label: Text(
                'Tambah Produk Kompetitor Sejenis',
                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: themeColor),
              ),
              style: OutlinedButton.styleFrom(
                side: BorderSide(color: themeColor.withOpacity(0.5)),
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
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
    final bool isFieldCalculated = _isCalculatedField(field);
    final bool isFieldReadonly = field.isReadonly || isFieldCalculated;
    final readonlyBgColor = isDarkMode ? Colors.grey.shade900 : const Color(0xFFF1F5F9);

    final fieldNameLower = field.fieldName.toLowerCase();
    final fieldLabelLower = field.fieldLabel.toLowerCase();

    final bool isCbp = widget.template.code == 'RPT-DULUX-CBP-PRICING' || widget.template.code.contains('CBP');

    // Khusus Laporan CBP: Tampilkan dynamic repeater untuk data kompetitor pada field pertama dan sembunyikan sisanya
    if (isCbp) {
      if (fieldNameLower == 'merk_kompetitor') {
        return _buildCompetitorRepeaterSection(
          themeColor,
          cardColor,
          textColor,
          subtitleColor,
          elevatedColor,
          isDarkMode,
          locale,
        );
      }
      if (const [
        'subbrand_kompetitor',
        'harga_kompetitor_tin_rp',
        'harga_kompetitor_galon_rp',
        'harga_kompetitor_pail_rp'
      ].contains(fieldNameLower)) {
        return const SizedBox.shrink();
      }
    }

    final bool isPhoneOrNik = fieldNameLower.contains('hp') ||
        fieldNameLower.contains('phone') ||
        fieldNameLower.contains('wa') ||
        fieldNameLower.contains('whatsapp') ||
        fieldNameLower.contains('telepon') ||
        fieldNameLower.contains('ktp') ||
        fieldNameLower.contains('nik') ||
        fieldNameLower.contains('sim') ||
        fieldLabelLower.contains('nomor hp') ||
        fieldLabelLower.contains('no hp') ||
        fieldLabelLower.contains('whatsapp') ||
        fieldLabelLower.contains('ktp') ||
        fieldLabelLower.contains('nik');

    final bool isDecimal = fieldNameLower.contains('volume') ||
        fieldNameLower.contains('liter') ||
        fieldNameLower.contains('persen') ||
        fieldNameLower.contains('percent') ||
        fieldNameLower.contains('share') ||
        fieldNameLower.contains('luas') ||
        fieldNameLower.contains('conf') ||
        fieldNameLower.contains('density');

    Widget inputWidget;

    if (_isProductField(field)) {
      inputWidget = _buildProductInput(
        field,
        fieldKey,
        themeColor,
        cardColor,
        textColor,
        subtitleColor,
        elevatedColor,
        isDarkMode,
        locale,
        isFieldReadonly: isFieldReadonly,
      );
    } else {
      switch (field.fieldType) {
      case 'textarea':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          readOnly: isFieldReadonly,
          keyboardType: TextInputType.multiline,
          maxLines: 3,
          style: TextStyle(color: isFieldReadonly ? subtitleColor : textColor, fontSize: 13),
          decoration: _inputDecoration(
            field.placeholder ?? 'Tuliskan keterangan lengkap...',
            isFieldReadonly ? readonlyBgColor : elevatedColor,
            isDarkMode,
          ),
          validator: (v) => (!isFieldReadonly && field.isRequired) && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: isFieldReadonly ? null : (v) {
            _formValues[fieldKey] = v;
            _recalculateFormulas();
          },
        );
        break;

      case 'number':
      case 'integer':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          readOnly: isFieldReadonly,
          keyboardType: isDecimal
              ? const TextInputType.numberWithOptions(decimal: true)
              : (isPhoneOrNik ? TextInputType.phone : TextInputType.number),
          style: TextStyle(color: isFieldReadonly ? subtitleColor : textColor, fontSize: 13, fontWeight: isFieldCalculated ? FontWeight.bold : FontWeight.normal),
          decoration: _inputDecoration(
            isFieldCalculated ? 'Dihitung otomatis' : (field.placeholder ?? '0'),
            isFieldReadonly ? readonlyBgColor : elevatedColor,
            isDarkMode,
            helperText: (isCbp && fieldNameLower.contains('persen')) ? 'Otomatis dihitung jika nominal diisi' : null,
          ),
          validator: (v) => (!isFieldReadonly && field.isRequired) && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: isFieldReadonly ? null : (v) {
            _formValues[fieldKey] = num.tryParse(v);
            _recalculateFormulas();
            if (isCbp && fieldNameLower.contains('persen')) {
              _syncCbpDiscounts('persen');
            }
          },
        );
        break;

      case 'currency':
        inputWidget = TextFormField(
          controller: _controllers[fieldKey],
          readOnly: isFieldReadonly,
          keyboardType: TextInputType.number,
          style: TextStyle(color: isFieldReadonly ? subtitleColor : textColor, fontSize: 13, fontWeight: FontWeight.w600),
          decoration: _inputDecoration(
            'Rp 0',
            isFieldReadonly ? readonlyBgColor : elevatedColor,
            isDarkMode,
            helperText: (isCbp && fieldNameLower.contains('nominal')) ? 'Otomatis dihitung jika persen diisi' : null,
          ),
          validator: (v) => (!isFieldReadonly && field.isRequired) && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: isFieldReadonly ? null : (v) {
            _formatCurrency(fieldKey, v);
            _recalculateFormulas();
            if (isCbp) {
              if (fieldNameLower.contains('nominal')) {
                _syncCbpDiscounts('nominal');
              } else if (fieldNameLower.contains('harga') || fieldNameLower == 'harga_cbp_dulux_rp') {
                _syncCbpDiscounts('harga');
              }
            }
          },
        );
        break;

      case 'select':
      case 'dropdown':
      case 'product':
      case 'product_select':
        List<String> effectiveOptions = field.options.isNotEmpty
            ? List<String>.from(field.options)
            : widget.template.products.map((p) => p.name).toList();

        // Dynamic Dependent Filter: Dulux Tinter Category (Dramatone vs Acotone)
        if (fieldKey == 'tipe_tinter_warna' || field.fieldLabel.toLowerCase().contains('tipe tinter')) {
          final selectedCategory = _formValues['kategori_tinter']?.toString() ?? 'Dramatone';
          if (selectedCategory.toLowerCase().contains('acotone')) {
            effectiveOptions = effectiveOptions.where((opt) => opt.toLowerCase().contains('acotone')).toList();
          } else if (selectedCategory.toLowerCase().contains('dramatone')) {
            effectiveOptions = effectiveOptions.where((opt) => !opt.toLowerCase().contains('acotone')).toList();
          } else if (selectedCategory.toLowerCase().contains('tidak ada') || selectedCategory.toLowerCase().contains('non-tinting')) {
            effectiveOptions = ['Tidak Ada Mesin Tinting / Non-Tinting'];
          }
        }

        // Filter out already submitted categories in continuous input mode
        final bool isCategoryField = _isCategoryField(field);
        if (isCategoryField && _submittedCategories.isNotEmpty) {
          effectiveOptions = effectiveOptions.where((opt) => !_submittedCategories.contains(opt)).toList();
          if (effectiveOptions.isEmpty) {
            effectiveOptions = ['Semua Kategori Sudah Terisi Selesai ✓'];
          }
        }

        // Dynamic Machine Type Filter for Daily Maintenance based on Location / Store
        final fieldNameLower = field.fieldName.toLowerCase();
        final fieldLabelLower = field.fieldLabel.toLowerCase();
        final isDailyMaintenance = _isDailyMaintenanceTemplate();
        final isMachineTypeField = isDailyMaintenance &&
            (fieldNameLower == 'tipe_mesin_post' ||
             fieldNameLower.contains('tipe_mesin') ||
             fieldLabelLower.contains('tipe mesin') ||
             fieldLabelLower.contains('mesin tinting post'));

        if (isMachineTypeField) {
          final storeMachinesMap = _getStoreMachinesMap();
          if (storeMachinesMap.isNotEmpty) {
            effectiveOptions = [
              ...storeMachinesMap.keys,
              'Toko Tidak Memiliki Mesin Tinting',
            ];
          } else {
            if (!effectiveOptions.contains('Toko Tidak Memiliki Mesin Tinting')) {
              effectiveOptions.add('Toko Tidak Memiliki Mesin Tinting');
            }
          }
          effectiveOptions = effectiveOptions.toSet().toList();
        }

        // Pastikan nilai value yang dipilih valid ada di daftar options
        var currentDropdownVal = _formValues[fieldKey] ?? _formValues[field.fieldName] ?? _formValues['tipe_mesin_post'];

        // Auto-select first unsubmitted machine for Daily Maintenance
        if (isMachineTypeField && (currentDropdownVal == null || _isMachineSubmitted(currentDropdownVal.toString()) || !effectiveOptions.contains(currentDropdownVal.toString()))) {
          final firstUnsubmitted = effectiveOptions.firstWhere(
            (opt) => !_isMachineSubmitted(opt) && opt != 'Toko Tidak Memiliki Mesin Tinting',
            orElse: () => effectiveOptions.isNotEmpty ? effectiveOptions.first : '',
          );
          if (firstUnsubmitted.isNotEmpty) {
            currentDropdownVal = firstUnsubmitted;
            _formValues[fieldKey] = firstUnsubmitted;
            _formValues[field.fieldName] = firstUnsubmitted;
            _formValues['tipe_mesin_post'] = firstUnsubmitted;
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (mounted) {
                _autoFillMachineSerial(firstUnsubmitted);
              }
            });
          }
        }

        final isProductSelect = _isProductField(field);

        if (isProductSelect && (currentDropdownVal == null || _isOptionSubmitted(currentDropdownVal.toString()))) {
          final firstUnsubmitted = effectiveOptions.firstWhere(
            (opt) => !_isOptionSubmitted(opt),
            orElse: () => '',
          );
          if (firstUnsubmitted.isNotEmpty) {
            currentDropdownVal = firstUnsubmitted;
            _formValues[fieldKey] = firstUnsubmitted;
            _formValues[field.fieldName] = firstUnsubmitted;
            if (_controllers.containsKey(fieldKey)) {
              _controllers[fieldKey]!.text = firstUnsubmitted;
            }
          }
        }

        if (currentDropdownVal != null && !effectiveOptions.contains(currentDropdownVal.toString())) {
          effectiveOptions.insert(0, currentDropdownVal.toString());
        }

        final validDropdownValue = (currentDropdownVal != null && effectiveOptions.contains(currentDropdownVal.toString()))
            ? currentDropdownVal.toString()
            : null;

        final dropdownWidget = DropdownButtonFormField<String>(
          value: validDropdownValue,
          decoration: _inputDecoration(
            field.placeholder ?? 'Pilih salah satu opsi...',
            isFieldReadonly ? readonlyBgColor : elevatedColor,
            isDarkMode,
          ),
          dropdownColor: cardColor,
          isExpanded: true,
          style: TextStyle(color: isFieldReadonly ? subtitleColor : textColor, fontSize: 13),
          items: effectiveOptions
              .map((opt) {
                final matchedProduct = widget.template.products.cast<TemplateProductModel?>().firstWhere(
                      (p) => p?.name.toLowerCase() == opt.toLowerCase(),
                      orElse: () => null,
                    );
                final isOptSubmitted = (isProductSelect && _isOptionSubmitted(opt)) ||
                    (isMachineTypeField && _isMachineSubmitted(opt) && opt != 'Toko Tidak Memiliki Mesin Tinting');
                return DropdownMenuItem<String>(
                  value: opt,
                  enabled: !isOptSubmitted,
                  child: Row(
                    children: [
                      if (matchedProduct != null) ...[
                        Icon(
                          Icons.inventory_2_outlined,
                          size: 16,
                          color: isOptSubmitted ? Colors.grey : const Color(0xFFE53935),
                        ),
                        const SizedBox(width: 6),
                      ],
                      Expanded(
                        child: Text(
                          opt,
                          style: TextStyle(
                            fontSize: 12.5,
                            color: isOptSubmitted
                                ? Colors.grey.shade400
                                : (isFieldReadonly ? subtitleColor : textColor),
                            decoration: isOptSubmitted ? TextDecoration.lineThrough : null,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (isOptSubmitted) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.green.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(5),
                            border: Border.all(color: Colors.green.withOpacity(0.3)),
                          ),
                          child: const Text(
                            'Sudah Dilaporkan ✓',
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.green),
                          ),
                        ),
                      ] else if (matchedProduct != null && (matchedProduct.formattedPrice != null || matchedProduct.brand != null)) ...[
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
          validator: (v) => (!isFieldReadonly && field.isRequired) && (v == null || v.isEmpty) ? locale.tr('required_field') : null,
          onChanged: isFieldReadonly
              ? null
              : (v) {
                  setState(() {
                    _formValues[fieldKey] = v;
                    _formValues[field.fieldName] = v;
                    if (fieldKey == 'kategori_tinter') {
                      _formValues['tipe_tinter_warna'] = null;
                    }
                    if (isMachineTypeField) {
                      _formValues['tipe_mesin_post'] = v;
                      _autoFillMachineSerial(v);
                    }
                  });
                },
        );

        inputWidget = isFieldReadonly
            ? IgnorePointer(ignoring: true, child: dropdownWidget)
            : dropdownWidget;
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
                  color: isSelected ? Colors.white : (isFieldReadonly ? subtitleColor : textColor),
                ),
              ),
              selected: isSelected,
              selectedColor: isFieldReadonly ? subtitleColor : themeColor,
              backgroundColor: isFieldReadonly ? readonlyBgColor : elevatedColor,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              onSelected: isFieldReadonly
                  ? null
                  : (selected) {
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
                color: isSelected
                    ? (isFieldReadonly ? subtitleColor.withOpacity(0.1) : themeColor.withOpacity(0.08))
                    : (isFieldReadonly ? readonlyBgColor : elevatedColor),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: isSelected
                      ? (isFieldReadonly ? subtitleColor : themeColor)
                      : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                ),
              ),
              child: RadioListTile<String>(
                title: Text(
                  opt,
                  style: TextStyle(
                    fontSize: 12.5,
                    color: isFieldReadonly ? subtitleColor : textColor,
                    fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  ),
                ),
                value: opt,
                groupValue: currentRadioVal,
                activeColor: isFieldReadonly ? subtitleColor : themeColor,
                dense: true,
                contentPadding: const EdgeInsets.symmetric(horizontal: 8),
                onChanged: isFieldReadonly
                    ? null
                    : (val) {
                        setState(() {
                          _formValues[fieldKey] = val;
                          if (fieldKey == 'kategori_tinter') {
                            _formValues['tipe_tinter_warna'] = null;
                          }
                        });
                      },
              ),
            );
          }).toList(),
        );
        break;

      case 'checkbox':
        final bool isChecked = _formValues[fieldKey] == true;
        inputWidget = SwitchListTile(
          title: Text(
            field.placeholder ?? 'Aktifkan jika sesuai',
            style: TextStyle(fontSize: 13, color: isFieldReadonly ? subtitleColor : textColor),
          ),
          value: isChecked,
          activeColor: isFieldReadonly ? subtitleColor : themeColor,
          contentPadding: EdgeInsets.zero,
          onChanged: isFieldReadonly ? null : (val) => setState(() => _formValues[fieldKey] = val),
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
                color: isFieldReadonly ? Colors.grey : Colors.amber,
                size: 32,
              ),
              onPressed: isFieldReadonly ? null : () => setState(() => _formValues[fieldKey] = starVal),
            );
          }),
        );
        break;

      case 'photo':
      case 'camera_photo':
      case 'multi_photo':
        final capturedFiles = _multiPhotoFiles[fieldKey] ?? [];
        final existingUrls = _existingMultiPhotoUrls[fieldKey] ?? _existingMultiPhotoUrls[field.fieldName] ?? [];
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
                        if (!isFieldReadonly)
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
                        if (!isFieldReadonly)
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
            if (!isFieldReadonly)
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
            if (!isFieldReadonly)
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

      case 'month_year':
      case 'month':
      case 'year_month':
        inputWidget = FormField<String>(
          initialValue: _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text,
          validator: (v) {
            final val = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            if (!isFieldReadonly && field.isRequired && val.trim().isEmpty) {
              return locale.tr('required_field');
            }
            return null;
          },
          builder: (state) {
            final rawVal = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            final displayMonthYearText = _formatMonthYearDisplay(rawVal);
            final hasError = state.hasError;

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                InkWell(
                  onTap: isFieldReadonly
                      ? null
                      : () async {
                          await _pickMonthYear(fieldKey, field);
                          state.didChange(_formValues[fieldKey]?.toString());
                        },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: isFieldReadonly ? readonlyBgColor : elevatedColor,
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
                        Icon(Icons.event_note_rounded, color: isFieldReadonly ? subtitleColor : themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            displayMonthYearText.isNotEmpty
                                ? displayMonthYearText
                                : (field.placeholder ?? 'Pilih Bulan & Tahun Expired...'),
                            style: TextStyle(
                              color: displayMonthYearText.isNotEmpty
                                  ? (isFieldReadonly ? subtitleColor : textColor)
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: displayMonthYearText.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                        if (isFieldReadonly)
                          Icon(Icons.lock_rounded, size: 16, color: subtitleColor)
                        else if (displayMonthYearText.isNotEmpty)
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

      case 'date':
      case 'datepicker':
        inputWidget = FormField<String>(
          initialValue: _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text,
          validator: (v) {
            final val = _formValues[fieldKey]?.toString() ?? _controllers[fieldKey]?.text ?? '';
            if (!isFieldReadonly && field.isRequired && val.trim().isEmpty) {
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
                  onTap: isFieldReadonly
                      ? null
                      : () async {
                          await _pickDate(fieldKey, field);
                          state.didChange(_formValues[fieldKey]?.toString());
                        },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: isFieldReadonly ? readonlyBgColor : elevatedColor,
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
                        Icon(Icons.calendar_month_rounded, color: isFieldReadonly ? subtitleColor : themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            displayDateText.isNotEmpty
                                ? displayDateText
                                : (field.placeholder ?? 'Pilih tanggal...'),
                            style: TextStyle(
                              color: displayDateText.isNotEmpty
                                  ? (isFieldReadonly ? subtitleColor : textColor)
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: displayDateText.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                        if (isFieldReadonly)
                          Icon(Icons.lock_rounded, size: 16, color: subtitleColor)
                        else if (displayDateText.isNotEmpty)
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
            if (!isFieldReadonly && field.isRequired && val.trim().isEmpty) {
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
                  onTap: isFieldReadonly
                      ? null
                      : () async {
                          await _pickTime(fieldKey, field);
                          state.didChange(_formValues[fieldKey]?.toString());
                        },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: isFieldReadonly ? readonlyBgColor : elevatedColor,
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
                        Icon(Icons.access_time_rounded, color: isFieldReadonly ? subtitleColor : themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            timeVal.isNotEmpty ? timeVal : (field.placeholder ?? 'Pilih jam...'),
                            style: TextStyle(
                              color: timeVal.isNotEmpty
                                  ? (isFieldReadonly ? subtitleColor : textColor)
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: timeVal.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                        if (isFieldReadonly)
                          Icon(Icons.lock_rounded, size: 16, color: subtitleColor)
                        else if (timeVal.isNotEmpty)
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
            if (!isFieldReadonly && field.isRequired && val.trim().isEmpty) {
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
                  onTap: isFieldReadonly
                      ? null
                      : () async {
                          await _pickDate(fieldKey, field);
                          state.didChange(_formValues[fieldKey]?.toString());
                        },
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: isFieldReadonly ? readonlyBgColor : elevatedColor,
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
                        Icon(Icons.calendar_today_rounded, color: isFieldReadonly ? subtitleColor : themeColor, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            dtVal.isNotEmpty ? dtVal : (field.placeholder ?? 'Pilih tanggal & waktu...'),
                            style: TextStyle(
                              color: dtVal.isNotEmpty
                                  ? (isFieldReadonly ? subtitleColor : textColor)
                                  : (isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400),
                              fontSize: 13,
                              fontWeight: dtVal.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                            ),
                          ),
                        ),
                        if (isFieldReadonly)
                          Icon(Icons.lock_rounded, size: 16, color: subtitleColor),
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
          readOnly: isFieldReadonly,
          style: TextStyle(color: isFieldReadonly ? subtitleColor : textColor, fontSize: 13),
          decoration: _inputDecoration(
            field.placeholder ?? 'Masukkan ${field.fieldLabel.toLowerCase()}',
            isFieldReadonly ? readonlyBgColor : elevatedColor,
            isDarkMode,
          ),
          validator: (v) => (!isFieldReadonly && field.isRequired) && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: isFieldReadonly ? null : (v) => _formValues[fieldKey] = v,
        );
        break;
      }
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
                child: Row(
                  children: [
                    Flexible(
                      child: Text(
                        field.fieldLabel,
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                      ),
                    ),
                    if (isFieldCalculated) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F52BA).withOpacity(isDarkMode ? 0.2 : 0.1),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFF0F52BA).withOpacity(0.35), width: 0.8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: const [
                            Icon(Icons.calculate_rounded, size: 11, color: Color(0xFF0F52BA)),
                            SizedBox(width: 3.5),
                            Text(
                              'Dihitung Otomatis',
                              style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: Color(0xFF0F52BA)),
                            ),
                          ],
                        ),
                      ),
                    ] else if (isFieldReadonly) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: isDarkMode ? Colors.grey.shade800 : const Color(0xFFE2E8F0),
                          borderRadius: BorderRadius.circular(5),
                          border: Border.all(color: isDarkMode ? Colors.grey.shade700 : const Color(0xFFCBD5E1), width: 0.8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.lock_rounded, size: 10.5, color: subtitleColor),
                            const SizedBox(width: 3),
                            Text(
                              'Read Only',
                              style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: subtitleColor),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ],
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

  bool _isProductField(ReportFormFieldModel field) {
    if (field.fieldType == 'product_select' || field.fieldType == 'barcode_scanner' || field.fieldType == 'product') {
      return true;
    }

    final name = field.fieldName.toLowerCase();
    final label = field.fieldLabel.toLowerCase();

    // Pastikan field kemasan, kategori, foto, qty, harga, uom, PIC, dll. TIDAK dianggap field produk
    final isExcluded = name.contains('kemasan') || label.contains('kemasan') ||
                       name.contains('kategori') || label.contains('kategori') ||
                       name.contains('category') || label.contains('category') ||
                       name.contains('foto') || label.contains('foto') ||
                       name.contains('photo') || label.contains('photo') ||
                       name.contains('harga') || label.contains('harga') ||
                       name.contains('price') || label.contains('price') ||
                       name.contains('qty') || label.contains('qty') ||
                       name.contains('jumlah') || label.contains('jumlah') ||
                       name.contains('stok') || label.contains('stok') ||
                       name.contains('stock') || label.contains('stock') ||
                       name.contains('uom') || label.contains('satuan') ||
                       name.contains('diskon') || label.contains('alasan') ||
                       name.contains('pic') || label.contains('pic') ||
                       name.contains('staff') || name.contains('nama_lengkap') ||
                       name.contains('konsumen') || name.contains('resep') ||
                       name.contains('feedback') || name.contains('tipe_customer');

    if (isExcluded) return false;

    final matchesProduct = name == 'produk' || name == 'product' || name == 'sku' ||
                           name == 'sub_brand' || name == 'subbrand_produk' ||
                           name.contains('nama_produk') || name.contains('nama_sku') ||
                           name.contains('sku_produk') || name.contains('sku_warna') ||
                           name.contains('sku_barang') || name.contains('pilih_produk') ||
                           name.contains('produk_stock_end') || name.contains('produk_oos') ||
                           label.contains('nama & sku') || label.contains('sku produk') ||
                           label.contains('sku / nama') || label.contains('pilih produk') ||
                           label.contains('sku / nama warna') ||
                           (label.contains('produk') && !label.contains('kategori') && !label.contains('kemasan')) ||
                           (label.contains('product') && !label.contains('category'));

    return matchesProduct;
  }

  List<TemplateProductModel> _getProducts() {
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final freshTemplate = repProvider.templates.cast<ReportTemplateModel?>().firstWhere(
      (t) => t?.id == widget.template.id || t?.code == widget.template.code,
      orElse: () => null,
    );
    if (freshTemplate != null && freshTemplate.products.isNotEmpty) {
      return freshTemplate.products;
    }
    return widget.template.products;
  }

  Widget _buildProductInput(
    ReportFormFieldModel field,
    String fieldKey,
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
    LocaleProvider locale, {
    bool isFieldReadonly = false,
  }) {
    final currentText = _controllers[fieldKey]?.text ?? _formValues[fieldKey]?.toString() ?? '';
    final products = _getProducts();
    final readonlyBgColor = isDarkMode ? Colors.grey.shade900 : const Color(0xFFF1F5F9);
    
    // Cari produk yang sesuai dari katalog template jika ada
    final matchedProduct = products.cast<TemplateProductModel?>().firstWhere(
          (p) => p != null && (p.name.toLowerCase() == currentText.toLowerCase() || (p.skuCode != null && p.skuCode!.toLowerCase() == currentText.toLowerCase())),
          orElse: () => null,
        );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextFormField(
          controller: _controllers[fieldKey],
          readOnly: isFieldReadonly,
          style: TextStyle(color: isFieldReadonly ? subtitleColor : textColor, fontSize: 13, fontWeight: FontWeight.w600),
          decoration: InputDecoration(
            hintText: isFieldReadonly ? 'Produk terpilih otomatis' : (field.placeholder ?? 'Ketik nama / pilih produk / scan barcode...'),
            hintStyle: TextStyle(color: isDarkMode ? Colors.grey.shade500 : Colors.grey.shade400, fontSize: 12.5),
            filled: true,
            fillColor: isFieldReadonly ? readonlyBgColor : elevatedColor,
            prefixIcon: Icon(Icons.inventory_2_outlined, color: isFieldReadonly ? subtitleColor : themeColor, size: 20),
            suffixIcon: isFieldReadonly
                ? Icon(Icons.lock_rounded, size: 18, color: subtitleColor)
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (currentText.isNotEmpty)
                        IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          color: subtitleColor,
                          tooltip: 'Hapus',
                          onPressed: () {
                            setState(() {
                              _formValues.remove(fieldKey);
                              _controllers[fieldKey]?.clear();
                            });
                          },
                        ),
                      IconButton(
                        icon: const Icon(Icons.qr_code_scanner_rounded, size: 20),
                        color: themeColor,
                        tooltip: 'Scan Barcode',
                        onPressed: () => _scanBarcodeForProduct(field, themeColor),
                      ),
                      IconButton(
                        icon: const Icon(Icons.menu_book_rounded, size: 20),
                        color: themeColor,
                        tooltip: 'Katalog Produk',
                        onPressed: () => _openProductPickerBottomSheet(field, themeColor, isDarkMode),
                      ),
                    ],
                  ),
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
              borderSide: BorderSide(color: themeColor, width: 1.5),
            ),
          ),
          validator: (v) => (!isFieldReadonly && field.isRequired) && (v == null || v.trim().isEmpty) ? locale.tr('required_field') : null,
          onChanged: isFieldReadonly
              ? null
              : (v) {
                  setState(() {
                    _formValues[fieldKey] = v;
                  });
                },
        ),
        if (!isFieldReadonly) ...[
          const SizedBox(height: 8),

          // Quick Action Buttons (Scan Barcode & Buka Katalog)
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _scanBarcodeForProduct(field, themeColor),
                  icon: const Icon(Icons.qr_code_scanner_rounded, size: 16, color: Colors.white),
                  label: const Text(
                    'Scan Barcode',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: themeColor,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 9),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
                  ),
                ),
              ),
              if (products.isNotEmpty) ...[
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _openProductPickerBottomSheet(field, themeColor, isDarkMode),
                    icon: Icon(Icons.inventory_2_rounded, size: 16, color: themeColor),
                    label: Text(
                      'Katalog (${products.length})',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: themeColor),
                    ),
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(color: themeColor.withOpacity(0.5)),
                      backgroundColor: themeColor.withOpacity(0.06),
                      padding: const EdgeInsets.symmetric(vertical: 9),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],

        // Peringatan jika produk terpilih sudah dilaporkan hari ini
        if (currentText.isNotEmpty && _isProductSubmitted(currentText)) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: isDarkMode ? Colors.amber.shade900.withOpacity(0.2) : const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.amber.shade600, width: 1.2),
            ),
            child: Row(
              children: [
                Icon(Icons.warning_amber_rounded, size: 18, color: isDarkMode ? Colors.amber.shade300 : Colors.amber.shade900),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Produk "$currentText" sudah dilaporkan hari ini ✓. Harap pilih produk lain yang belum dilaporkan.',
                    style: TextStyle(
                      fontSize: 11.5,
                      color: isDarkMode ? Colors.amber.shade200 : Colors.amber.shade900,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],

        // Preview Card jika produk teridentifikasi di database master produk
        if (matchedProduct != null) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1E293B) : const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: themeColor.withOpacity(0.3), width: 1),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.check_circle_rounded, color: Colors.green.shade600, size: 16),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        matchedProduct.name,
                        style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  children: [
                    if (matchedProduct.skuCode != null && matchedProduct.skuCode!.isNotEmpty)
                      _buildProductInfoChip('SKU: ${matchedProduct.skuCode}', themeColor, isDarkMode),
                    if (matchedProduct.barcode != null && matchedProduct.barcode!.isNotEmpty)
                      _buildProductInfoChip('Barcode: ${matchedProduct.barcode}', Colors.purple, isDarkMode),
                    if (matchedProduct.category != null && matchedProduct.category!.isNotEmpty)
                      _buildProductInfoChip('Kategori: ${matchedProduct.category}', Colors.teal, isDarkMode),
                    if (matchedProduct.brand != null && matchedProduct.brand!.isNotEmpty)
                      _buildProductInfoChip('Brand: ${matchedProduct.brand}', Colors.indigo, isDarkMode),
                    if (matchedProduct.minStock > 0)
                      _buildProductInfoChip('Min Stock: ${matchedProduct.minStock} ${matchedProduct.uom}', Colors.amber.shade800, isDarkMode),
                    if (matchedProduct.formattedPrice != null && matchedProduct.formattedPrice!.isNotEmpty)
                      _buildProductInfoChip(matchedProduct.formattedPrice!, Colors.orange, isDarkMode),
                  ],
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildProductInfoChip(String label, Color color, bool isDarkMode) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(isDarkMode ? 0.18 : 0.1),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.3), width: 0.5),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 9.5,
          fontWeight: FontWeight.w600,
          color: isDarkMode ? color.withOpacity(0.9) : color,
        ),
      ),
    );
  }

  Future<void> _scanBarcodeForProduct(ReportFormFieldModel field, Color themeColor) async {
    final scannedCode = await BarcodeScannerDialog.show(
      context,
      title: 'Scan Barcode ${field.fieldLabel}',
      hintText: 'Arahkan kamera ke barcode kemasan produk untuk memilih otomatis',
    );

    if (scannedCode == null || scannedCode.trim().isEmpty) return;
    final cleanCode = scannedCode.trim();
    final products = _getProducts();

    // Cari produk yang matching di list template products
    final matched = products.cast<TemplateProductModel?>().firstWhere(
      (p) {
        if (p == null) return false;
        if (p.barcode != null && p.barcode!.trim() == cleanCode) return true;
        if (p.skuCode != null && p.skuCode!.trim().toLowerCase() == cleanCode.toLowerCase()) return true;
        if (p.name.trim().toLowerCase() == cleanCode.toLowerCase()) return true;
        return false;
      },
      orElse: () => null,
    );

    final fieldKey = field.id.toString();

    if (matched != null) {
      _onProductSelected(matched, fieldKey);
    } else {
      // Barcode tidak ada di master produk, tetap masukkan nomor barcode ke input field
      setState(() {
        _formValues[fieldKey] = cleanCode;
        _controllers[fieldKey]?.text = cleanCode;
      });

      toastification.show(
        context: context,
        type: ToastificationType.warning,
        style: ToastificationStyle.flatColored,
        title: const Text('Barcode Terbaca', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        description: Text('Kode: $cleanCode (Belum terdaftar di master produk)', style: const TextStyle(fontSize: 12)),
        alignment: Alignment.topCenter,
        autoCloseDuration: const Duration(seconds: 4),
      );
    }
  }

  void _onProductSelected(TemplateProductModel product, String targetFieldKey) {
    setState(() {
      _formValues[targetFieldKey] = product.name;
      _controllers[targetFieldKey]?.text = product.name;
    });

    String? autoCategoryResult;
    String? autoKemasanResult;

    final pNameLower = product.name.toLowerCase();
    final pCatLower = (product.category ?? '').trim().toLowerCase();
    final pBrandLower = (product.brand ?? '').trim().toLowerCase();

    // 1. Auto-Fill Kategori Produk jika ada field kategori
    for (final field in widget.template.fields) {
      final fName = field.fieldName.toLowerCase();
      final fLabel = field.fieldLabel.toLowerCase();

      if (field.id.toString() != targetFieldKey &&
          (fName.contains('kategori') || fLabel.contains('kategori') || fName.contains('category'))) {
        final catKey = field.id.toString();
        String? matchedOption;

        if (field.options.isNotEmpty) {
          // 1. Exact / contains match dengan category atau brand
          for (final opt in field.options) {
            final optLower = opt.toLowerCase().trim();
            if (pCatLower.isNotEmpty && (optLower == pCatLower || optLower.contains(pCatLower) || pCatLower.contains(optLower))) {
              matchedOption = opt;
              break;
            }
            if (pBrandLower.isNotEmpty && (optLower == pBrandLower || optLower.contains(pBrandLower) || pBrandLower.contains(optLower))) {
              matchedOption = opt;
              break;
            }
          }

          // 2. Keyword matching dari Brand dan Product Name (misal: "Aquashield", "Weathershield", "Pentalite", "Catylac")
          if (matchedOption == null) {
            final searchTokens = <String>[];
            if (pBrandLower.isNotEmpty) searchTokens.addAll(pBrandLower.split(RegExp(r'[\s\-/(),]+')));
            searchTokens.addAll(pNameLower.split(RegExp(r'[\s\-/(),]+')));
            
            final meaningfulTokens = searchTokens
                .where((t) => t.length >= 4 && !['anti', 'bocor', 'warna', 'ready', 'mix', 'cat'].contains(t))
                .toSet();

            for (final token in meaningfulTokens) {
              for (final opt in field.options) {
                if (opt.toLowerCase().contains(token)) {
                  matchedOption = opt;
                  break;
                }
              }
              if (matchedOption != null) break;
            }
          }

          // 3. Fallback ke category mapping khusus (Waterproofing -> Aquashield/Pelapis Bocor)
          if (matchedOption == null) {
            if (pCatLower.contains('waterproof') || pNameLower.contains('aquashield') || pNameLower.contains('bocor')) {
              for (final opt in field.options) {
                final optLower = opt.toLowerCase();
                if (optLower.contains('aquashield') || optLower.contains('bocor') || optLower.contains('waterproof')) {
                  matchedOption = opt;
                  break;
                }
              }
            }
          }
        }

        if (matchedOption != null) {
          setState(() {
            _formValues[catKey] = matchedOption;
            _controllers[catKey]?.text = matchedOption!;
          });
          autoCategoryResult = matchedOption;
          break;
        } else {
          // Jika tipe text atau dropdown tanpa match opsi, isi teks kategori produk jika ada
          final fillValue = product.category?.isNotEmpty == true ? product.category : (product.brand?.isNotEmpty == true ? product.brand : null);
          if (fillValue != null) {
            setState(() {
              _formValues[catKey] = fillValue;
              _controllers[catKey]?.text = fillValue;
            });
            autoCategoryResult = fillValue;
            break;
          }
        }
      }
    }

    // 2. Auto-Fill Kemasan Produk jika ada field kemasan / ukuran
    for (final field in widget.template.fields) {
      final fName = field.fieldName.toLowerCase();
      final fLabel = field.fieldLabel.toLowerCase();

      if (field.id.toString() != targetFieldKey &&
          (fName.contains('kemasan') || fLabel.contains('kemasan') || fName.contains('packaging') || fName.contains('ukuran'))) {
        final kemasanKey = field.id.toString();
        String? matchedKemasan;

        if (field.options.isNotEmpty) {
          // Cek 20L / 25Kg / Pail
          if (pNameLower.contains('20l') || pNameLower.contains('20 l') || pNameLower.contains('25kg') || pNameLower.contains('25 kg') || pNameLower.contains('pail')) {
            matchedKemasan = field.options.firstWhere(
              (opt) => opt.toLowerCase().contains('20') || opt.toLowerCase().contains('pail') || opt.toLowerCase().contains('25'),
              orElse: () => '',
            );
          }
          // Cek 2.5L / 4Kg / 5Kg / Galon
          else if (pNameLower.contains('4kg') || pNameLower.contains('4 kg') || pNameLower.contains('2.5') || pNameLower.contains('5kg') || pNameLower.contains('5 kg') || pNameLower.contains('galon')) {
            matchedKemasan = field.options.firstWhere(
              (opt) => opt.toLowerCase().contains('4') || opt.toLowerCase().contains('2.5') || opt.toLowerCase().contains('galon') || opt.toLowerCase().contains('5'),
              orElse: () => '',
            );
          }
          // Cek 1L / 1Kg / Kaleng Kecil
          else if (pNameLower.contains('1l') || pNameLower.contains('1 l') || pNameLower.contains('1kg') || pNameLower.contains('1 kg') || pNameLower.contains('kaleng')) {
            matchedKemasan = field.options.firstWhere(
              (opt) => opt.toLowerCase().contains('1') || opt.toLowerCase().contains('kecil'),
              orElse: () => '',
            );
          }

          if (matchedKemasan != null && matchedKemasan.isNotEmpty) {
            setState(() {
              _formValues[kemasanKey] = matchedKemasan;
              _controllers[kemasanKey]?.text = matchedKemasan!;
            });
            autoKemasanResult = matchedKemasan;
            break;
          }
        }
      }
    }

    // 3. Auto-Fill Stock Minimal Standar jika ada field minimal_stock / min_stock / minimum_stock_qty / stok_minimal
    int? autoMinStockResult;
    for (final field in widget.template.fields) {
      final fName = field.fieldName.toLowerCase();
      final fLabel = field.fieldLabel.toLowerCase();

      final isMinStockField = fName.contains('minimal_stock') ||
          fName.contains('minimum_stock') ||
          fName.contains('min_stock') ||
          fName.contains('stok_minimal') ||
          fName.contains('stock_minimal') ||
          fName == 'min_stock_qty' ||
          fLabel.contains('minimum stock') ||
          fLabel.contains('minimal stock') ||
          fLabel.contains('stok minimal') ||
          fLabel.contains('stock minimal') ||
          fLabel.contains('min stock');

      if (field.id.toString() != targetFieldKey && isMinStockField) {
        final minStockKey = field.id.toString();
        final stockVal = product.minStock;
        setState(() {
          _formValues[minStockKey] = stockVal.toString();
          _controllers[minStockKey]?.text = stockVal.toString();
        });
        autoMinStockResult = stockVal;
        break;
      }
    }

    _recalculateFormulas();

    if (autoCategoryResult != null || autoMinStockResult != null) {
      final details = <String>[];
      if (autoCategoryResult != null) details.add('Kategori: $autoCategoryResult');
      if (autoKemasanResult != null) details.add('Kemasan: $autoKemasanResult');
      if (autoMinStockResult != null && autoMinStockResult > 0) details.add('Min Stock: $autoMinStockResult ${product.uom}');

      toastification.show(
        context: context,
        type: ToastificationType.success,
        style: ToastificationStyle.flatColored,
        title: Text(product.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        description: Text(details.join(' | '), style: const TextStyle(fontSize: 12)),
        alignment: Alignment.topCenter,
        autoCloseDuration: const Duration(seconds: 3),
      );
    } else {
      toastification.show(
        context: context,
        type: ToastificationType.success,
        style: ToastificationStyle.flatColored,
        title: const Text('Produk Dipilih', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
        description: Text(product.name, style: const TextStyle(fontSize: 12)),
        alignment: Alignment.topCenter,
        autoCloseDuration: const Duration(seconds: 2),
      );
    }
  }

  void _openProductPickerBottomSheet(
    ReportFormFieldModel field,
    Color themeColor,
    bool isDarkMode,
  ) {
    final fieldKey = field.id.toString();
    final allProducts = _getProducts();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        String searchQuery = '';
        String selectedCategory = 'Semua';

        // Ambil list kategori unik
        final categories = <String>['Semua'];
        for (final p in allProducts) {
          if (p.category != null && p.category!.trim().isNotEmpty) {
            if (!categories.contains(p.category!.trim())) {
              categories.add(p.category!.trim());
            }
          }
        }

        return StatefulBuilder(
          builder: (context, setSheetState) {
            // Filter products
            final filteredProducts = allProducts.where((p) {
              final matchesCat = selectedCategory == 'Semua' || (p.category != null && p.category!.trim().toLowerCase() == selectedCategory.toLowerCase());
              if (!matchesCat) return false;

              if (searchQuery.isEmpty) return true;
              final q = searchQuery.toLowerCase();
              final matchesName = p.name.toLowerCase().contains(q);
              final matchesSku = p.skuCode?.toLowerCase().contains(q) ?? false;
              final matchesBarcode = p.barcode?.toLowerCase().contains(q) ?? false;
              final matchesBrand = p.brand?.toLowerCase().contains(q) ?? false;
              return matchesName || matchesSku || matchesBarcode || matchesBrand;
            }).toList();

            final sheetBg = isDarkMode ? const Color(0xFF1E1E1E) : Colors.white;
            final itemBg = isDarkMode ? const Color(0xFF2A2A2A) : const Color(0xFFF8FAFC);
            final sheetText = isDarkMode ? Colors.white : const Color(0xFF1E293B);

            return Container(
              height: MediaQuery.of(context).size.height * 0.82,
              decoration: BoxDecoration(
                color: sheetBg,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  // Drag handle
                  Center(
                    child: Container(
                      margin: const EdgeInsets.only(top: 10, bottom: 8),
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade400,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),

                  // Header
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(Icons.inventory_2_rounded, color: themeColor, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Master Data Produk',
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: sheetText),
                              ),
                              Text(
                                '${widget.template.title} (${allProducts.length} SKU)',
                                style: TextStyle(fontSize: 11.5, color: Colors.grey.shade500),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () => Navigator.of(sheetCtx).pop(),
                          icon: const Icon(Icons.close_rounded),
                          color: Colors.grey.shade500,
                        ),
                      ],
                    ),
                  ),

                  // Search Bar with Barcode Scanner Button
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            autofocus: false,
                            style: TextStyle(color: sheetText, fontSize: 13),
                            decoration: InputDecoration(
                              hintText: 'Cari nama produk, SKU, barcode...',
                              hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 12.5),
                              prefixIcon: Icon(Icons.search_rounded, color: themeColor, size: 20),
                              filled: true,
                              fillColor: itemBg,
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                                borderSide: BorderSide(color: themeColor, width: 1.5),
                              ),
                            ),
                            onChanged: (v) {
                              setSheetState(() {
                                searchQuery = v;
                              });
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Quick Scan Barcode from inside Bottom Sheet
                        InkWell(
                          onTap: () async {
                            Navigator.of(sheetCtx).pop();
                            await _scanBarcodeForProduct(field, themeColor);
                          },
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
                            decoration: BoxDecoration(
                              color: themeColor,
                              borderRadius: BorderRadius.circular(12),
                              boxShadow: [
                                BoxShadow(
                                  color: themeColor.withOpacity(0.3),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: const Row(
                              children: [
                                Icon(Icons.qr_code_scanner_rounded, color: Colors.white, size: 20),
                                SizedBox(width: 6),
                                Text('Scan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Category Chips (if more than 1 category)
                  if (categories.length > 2) ...[
                    const SizedBox(height: 6),
                    SizedBox(
                      height: 36,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: categories.length,
                        separatorBuilder: (ctx, i) => const SizedBox(width: 6),
                        itemBuilder: (ctx, idx) {
                          final cat = categories[idx];
                          final isSelected = cat == selectedCategory;
                          return ChoiceChip(
                            label: Text(
                              cat,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                color: isSelected ? Colors.white : sheetText,
                              ),
                            ),
                            selected: isSelected,
                            selectedColor: themeColor,
                            backgroundColor: itemBg,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            side: BorderSide(
                              color: isSelected ? themeColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                            ),
                            onSelected: (selected) {
                              if (selected) {
                                setSheetState(() {
                                  selectedCategory = cat;
                                });
                              }
                            },
                          );
                        },
                      ),
                    ),
                  ],

                  const SizedBox(height: 8),
                  const Divider(height: 1),

                  // List of Products
                  Expanded(
                    child: filteredProducts.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.search_off_rounded, size: 48, color: Colors.grey.shade400),
                                const SizedBox(height: 10),
                                Text(
                                  'Produk tidak ditemukan',
                                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: sheetText),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Coba kata kunci lain atau scan barcode produk',
                                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                                ),
                              ],
                            ),
                          )
                        : ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                            itemCount: filteredProducts.length,
                            separatorBuilder: (ctx, i) => const SizedBox(height: 8),
                            itemBuilder: (ctx, idx) {
                              final p = filteredProducts[idx];
                              final isCurrent = _controllers[fieldKey]?.text == p.name || _formValues[fieldKey] == p.name;
                              final isSubmitted = _isTemplateProductSubmitted(p);

                              return InkWell(
                                onTap: isSubmitted
                                    ? null
                                    : () {
                                        Navigator.of(sheetCtx).pop();
                                        _onProductSelected(p, fieldKey);
                                      },
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: isSubmitted
                                        ? (isDarkMode ? const Color(0xFF161616) : const Color(0xFFF1F5F9))
                                        : (isCurrent ? themeColor.withOpacity(0.08) : itemBg),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: isSubmitted
                                          ? (isDarkMode ? Colors.grey.shade900 : Colors.grey.shade300)
                                          : (isCurrent ? themeColor : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200)),
                                      width: isCurrent && !isSubmitted ? 1.5 : 1.0,
                                    ),
                                  ),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(8),
                                        decoration: BoxDecoration(
                                          color: isSubmitted
                                              ? Colors.green.withOpacity(0.1)
                                              : (isCurrent ? themeColor : themeColor.withOpacity(0.12)),
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: Icon(
                                          isSubmitted ? Icons.check_circle_rounded : Icons.inventory_2_rounded,
                                          color: isSubmitted ? Colors.green : (isCurrent ? Colors.white : themeColor),
                                          size: 18,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              p.name,
                                              style: TextStyle(
                                                fontSize: 13,
                                                fontWeight: FontWeight.bold,
                                                color: isSubmitted
                                                    ? Colors.grey.shade500
                                                    : (isCurrent ? themeColor : sheetText),
                                                decoration: isSubmitted ? TextDecoration.lineThrough : null,
                                              ),
                                            ),
                                            if (isSubmitted) ...[
                                              const SizedBox(height: 4),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                                decoration: BoxDecoration(
                                                  color: Colors.green.withOpacity(0.12),
                                                  borderRadius: BorderRadius.circular(5),
                                                  border: Border.all(color: Colors.green.withOpacity(0.3)),
                                                ),
                                                child: const Row(
                                                  mainAxisSize: MainAxisSize.min,
                                                  children: [
                                                    Icon(Icons.check_circle, size: 11, color: Colors.green),
                                                    SizedBox(width: 3),
                                                    Text(
                                                      'Sudah Dilaporkan Hari Ini ✓',
                                                      style: TextStyle(
                                                        color: Colors.green,
                                                        fontSize: 10,
                                                        fontWeight: FontWeight.bold,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ],
                                            const SizedBox(height: 4),
                                            Wrap(
                                              spacing: 6,
                                              runSpacing: 4,
                                              children: [
                                                if (p.skuCode != null && p.skuCode!.isNotEmpty)
                                                  _buildProductInfoChip('SKU: ${p.skuCode}', isSubmitted ? Colors.grey : themeColor, isDarkMode),
                                                if (p.barcode != null && p.barcode!.isNotEmpty)
                                                  _buildProductInfoChip('Barcode: ${p.barcode}', isSubmitted ? Colors.grey : Colors.purple, isDarkMode),
                                                if (p.category != null && p.category!.isNotEmpty)
                                                  _buildProductInfoChip(p.category!, isSubmitted ? Colors.grey : Colors.teal, isDarkMode),
                                                if (p.brand != null && p.brand!.isNotEmpty)
                                                  _buildProductInfoChip(p.brand!, isSubmitted ? Colors.grey : Colors.indigo, isDarkMode),
                                                if (p.minStock > 0)
                                                  _buildProductInfoChip('Min: ${p.minStock} ${p.uom}', isSubmitted ? Colors.grey : Colors.amber.shade800, isDarkMode),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                      if (p.formattedPrice != null && p.formattedPrice!.isNotEmpty) ...[
                                        const SizedBox(width: 8),
                                        Text(
                                          p.formattedPrice!,
                                          style: TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.bold,
                                            color: isSubmitted ? Colors.grey : Colors.green.shade600,
                                          ),
                                        ),
                                      ],
                                      if (isCurrent && !isSubmitted) ...[
                                        const SizedBox(width: 8),
                                        Icon(Icons.check_circle_rounded, color: themeColor, size: 18),
                                      ],
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  InputDecoration _inputDecoration(String hint, Color fillColor, bool isDarkMode, {String? helperText}) {
    return InputDecoration(
      hintText: hint,
      helperText: helperText,
      helperStyle: const TextStyle(fontSize: 11, color: Color(0xFF0F52BA), fontWeight: FontWeight.w500),
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

  // ═══════════════════════════════════════════════════════════════════════════
  // OFFTAKE SPECIALIZED WORKFLOW (SALE vs NO SALE, CART, REVIEW, FOTO BUKTI)
  // ═══════════════════════════════════════════════════════════════════════════

  String _formatRupiah(num amount) {
    return NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(amount);
  }

  Future<ImageSource?> _showPhotoSourceDialog({required String title}) async {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final themeColor = Color(int.tryParse(widget.template.color.replaceAll('#', '0xFF')) ?? primaryColor.value);

    return await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final bg = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
        final textCol = isDarkMode ? Colors.white : const Color(0xFF0E1830);

        return Container(
          decoration: BoxDecoration(
            color: bg,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 14),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade400,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(Icons.add_a_photo_rounded, color: themeColor, size: 20),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: textCol),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Pilih metode pengambilan foto bukti',
                          style: TextStyle(fontSize: 11.5, color: isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              InkWell(
                onTap: () => Navigator.pop(ctx, ImageSource.camera),
                borderRadius: BorderRadius.circular(14),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: themeColor.withOpacity(0.15),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(Icons.camera_alt_rounded, color: themeColor, size: 22),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Kamera (Watermark Geotag)',
                              style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textCol),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Ambil foto langsung dengan stempel lokasi GPS & waktu presisi',
                              style: TextStyle(fontSize: 11, color: isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893)),
                            ),
                          ],
                        ),
                      ),
                      Icon(Icons.chevron_right_rounded, color: isDarkMode ? Colors.grey.shade600 : Colors.grey.shade400),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 10),
              InkWell(
                onTap: () => Navigator.pop(ctx, ImageSource.gallery),
                borderRadius: BorderRadius.circular(14),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.teal.withOpacity(0.15),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.photo_library_rounded, color: Colors.teal, size: 22),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Pilih dari Galeri HP',
                              style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textCol),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Pilih foto dari penyimpanan galeri foto ponsel Anda',
                              style: TextStyle(fontSize: 11, color: isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893)),
                            ),
                          ],
                        ),
                      ),
                      Icon(Icons.chevron_right_rounded, color: isDarkMode ? Colors.grey.shade600 : Colors.grey.shade400),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        );
      },
    );
  }

  Future<void> _pickOfftakeCardPhoto() async {
    final source = await _showPhotoSourceDialog(title: 'Foto Card Offtake (1 Foto)');
    if (source == null) return;

    final auth = Provider.of<AuthProvider>(context, listen: false);
    final employeeName = auth.employeeData?['full_name'] ?? 'Promotor';
    final employeeNik = auth.employeeData?['nik'] ?? '';
    final currentStore = _selectedStoreName.isNotEmpty ? _selectedStoreName : 'Kunjungan Toko';

    WatermarkCaptureResult? res;
    if (source == ImageSource.camera) {
      res = await WatermarkCameraService.captureWithWatermark(
        employeeName: employeeName,
        employeeNik: employeeNik,
        storeName: currentStore,
        latitude: _latitude,
        longitude: _longitude,
      );
    } else {
      res = await WatermarkCameraService.pickFromGallery(
        employeeName: employeeName,
        employeeNik: employeeNik,
        storeName: currentStore,
      );
    }

    if (res != null && mounted) {
      setState(() {
        _offtakeCardPhoto = res!.file;
        _offtakeCardPhotoWatermark = res.watermarkText;
      });

      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Foto Card Offtake Disimpan'),
        autoCloseDuration: const Duration(seconds: 2),
      );
    }
  }

  Future<void> _pickOfftakeNotaPhoto() async {
    final source = await _showPhotoSourceDialog(title: 'Foto Nota Penjualan (Multi-Foto)');
    if (source == null) return;

    final auth = Provider.of<AuthProvider>(context, listen: false);
    final employeeName = auth.employeeData?['full_name'] ?? 'Promotor';
    final employeeNik = auth.employeeData?['nik'] ?? '';
    final currentStore = _selectedStoreName.isNotEmpty ? _selectedStoreName : 'Kunjungan Toko';

    if (source == ImageSource.camera) {
      final res = await WatermarkCameraService.captureWithWatermark(
        employeeName: employeeName,
        employeeNik: employeeNik,
        storeName: currentStore,
        latitude: _latitude,
        longitude: _longitude,
      );
      if (res != null && mounted) {
        setState(() {
          _offtakeNotaPhotos.add(res.file);
          _offtakeNotaPhotoWatermarks.add(res.watermarkText);
        });
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: Text('Foto Nota Ditambahkan (${_offtakeNotaPhotos.length} Foto)'),
          autoCloseDuration: const Duration(seconds: 2),
        );
      }
    } else {
      final results = await WatermarkCameraService.pickMultiFromGallery(
        employeeName: employeeName,
        employeeNik: employeeNik,
        storeName: currentStore,
      );
      if (results.isNotEmpty && mounted) {
        setState(() {
          for (final r in results) {
            _offtakeNotaPhotos.add(r.file);
            _offtakeNotaPhotoWatermarks.add(r.watermarkText);
          }
        });
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: Text('${results.length} Foto Nota Ditambahkan dari Galeri (${_offtakeNotaPhotos.length} Total)'),
          autoCloseDuration: const Duration(seconds: 2),
        );
      }
    }
  }

  void _openOfftakeProductPickerBottomSheet(Color themeColor, bool isDarkMode) {
    final allProducts = _getProducts();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        String searchQuery = '';
        String selectedFilter = 'Semua';

        final categories = <String>['Semua'];
        for (final p in allProducts) {
          final cat = p.category?.trim() ?? '';
          if (cat.isNotEmpty && !categories.contains(cat)) {
            categories.add(cat);
          }
        }

        return StatefulBuilder(
          builder: (context, setSheetState) {
            final filteredProducts = allProducts.where((p) {
              final matchesFilter = selectedFilter == 'Semua' || (p.category != null && p.category!.trim().toLowerCase() == selectedFilter.toLowerCase());
              if (!matchesFilter) return false;

              if (searchQuery.isEmpty) return true;
              final q = searchQuery.toLowerCase();
              final matchesName = p.name.toLowerCase().contains(q);
              final matchesSku = p.skuCode?.toLowerCase().contains(q) ?? false;
              final matchesBrand = p.brand?.toLowerCase().contains(q) ?? false;
              return matchesName || matchesSku || matchesBrand;
            }).toList();

            final sheetBg = isDarkMode ? const Color(0xFF1E1E1E) : Colors.white;
            final itemBg = isDarkMode ? const Color(0xFF2A2A2A) : const Color(0xFFF8FAFC);
            final sheetText = isDarkMode ? Colors.white : const Color(0xFF1E293B);
            final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);

            return Container(
              height: MediaQuery.of(context).size.height * 0.85,
              decoration: BoxDecoration(
                color: sheetBg,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  Center(
                    child: Container(
                      margin: const EdgeInsets.only(top: 10, bottom: 8),
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade400,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(Icons.format_paint_rounded, color: themeColor, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Pilih Sub Brand Produk Dulux',
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: sheetText),
                              ),
                              Text(
                                'Katalog ${allProducts.length} Produk Dulux & Catylac',
                                style: TextStyle(fontSize: 11.5, color: Colors.grey.shade500),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () => Navigator.of(sheetCtx).pop(),
                          icon: const Icon(Icons.close_rounded),
                          color: Colors.grey.shade500,
                        ),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                    child: TextField(
                      autofocus: false,
                      style: TextStyle(color: sheetText, fontSize: 13),
                      decoration: InputDecoration(
                        hintText: 'Cari nama produk, Sub Brand...',
                        hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 12.5),
                        prefixIcon: Icon(Icons.search_rounded, color: themeColor, size: 20),
                        filled: true,
                        fillColor: itemBg,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: themeColor, width: 1.5),
                        ),
                      ),
                      onChanged: (v) => setSheetState(() => searchQuery = v),
                    ),
                  ),
                  if (categories.length > 2) ...[
                    const SizedBox(height: 6),
                    SizedBox(
                      height: 36,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: categories.length,
                        separatorBuilder: (ctx, i) => const SizedBox(width: 6),
                        itemBuilder: (ctx, idx) {
                          final cat = categories[idx];
                          final isSelected = cat == selectedFilter;
                          return ChoiceChip(
                            label: Text(
                              cat,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                color: isSelected ? Colors.white : sheetText,
                              ),
                            ),
                            selected: isSelected,
                            selectedColor: themeColor,
                            backgroundColor: itemBg,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                            side: BorderSide(
                              color: isSelected ? themeColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                            ),
                            onSelected: (selected) {
                              if (selected) {
                                setSheetState(() => selectedFilter = cat);
                              }
                            },
                          );
                        },
                      ),
                    ),
                  ],
                  const SizedBox(height: 8),
                  const Divider(height: 1),
                  Expanded(
                    child: filteredProducts.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.search_off_rounded, size: 48, color: Colors.grey.shade400),
                                const SizedBox(height: 10),
                                Text(
                                  'Produk tidak ditemukan',
                                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: sheetText),
                                ),
                              ],
                            ),
                          )
                        : ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                            itemCount: filteredProducts.length,
                            separatorBuilder: (ctx, i) => const SizedBox(height: 8),
                            itemBuilder: (ctx, idx) {
                              final p = filteredProducts[idx];
                              final isCurrent = _currentOfftakeProduct?.id == p.id;
                              final inCart = _offtakeCart.any((itm) => itm['product_id'] == p.id || itm['product_name'] == p.name);

                              final pMatrix = p.pricingMatrix;
                              final priceGalon = p.getPriceForPackaging('galon');
                              final pricePail = p.getPriceForPackaging('pail');

                              return InkWell(
                                onTap: () {
                                  Navigator.of(sheetCtx).pop();
                                  setState(() {
                                    _currentOfftakeProduct = p;
                                    _offtakeQtyTinCtrl.clear();
                                    _offtakeQtyGalonCtrl.clear();
                                    _offtakeQtyPailCtrl.clear();
                                  });
                                },
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: isCurrent
                                        ? themeColor.withOpacity(0.08)
                                        : itemBg,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: isCurrent
                                          ? themeColor
                                          : (inCart
                                              ? Colors.green.withOpacity(0.5)
                                              : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200)),
                                      width: isCurrent || inCart ? 1.5 : 1.0,
                                    ),
                                  ),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(8),
                                        decoration: BoxDecoration(
                                          color: inCart
                                              ? Colors.green.withOpacity(0.12)
                                              : themeColor.withOpacity(0.12),
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: Icon(
                                          inCart ? Icons.check_circle_rounded : Icons.format_paint_rounded,
                                          color: inCart ? Colors.green : themeColor,
                                          size: 18,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              p.name,
                                              style: TextStyle(
                                                fontSize: 13,
                                                fontWeight: FontWeight.bold,
                                                color: isCurrent ? themeColor : sheetText,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Wrap(
                                              spacing: 5,
                                              runSpacing: 4,
                                              children: [
                                                if (p.brand != null && p.brand!.isNotEmpty)
                                                  _buildProductInfoChip(p.brand!, Colors.indigo, isDarkMode),
                                                if (pMatrix?['brand_rm_base'] != null)
                                                  _buildProductInfoChip(pMatrix!['brand_rm_base'], Colors.teal, isDarkMode),
                                                if (inCart)
                                                  _buildProductInfoChip('Sudah di Keranjang ✓', Colors.green, isDarkMode),
                                              ],
                                            ),
                                            const SizedBox(height: 6),
                                            Row(
                                              children: [
                                                if (priceGalon > 0)
                                                  Text(
                                                    'Galon: ${_formatRupiah(priceGalon)}',
                                                    style: TextStyle(fontSize: 11, color: subtitleColor),
                                                  ),
                                                if (priceGalon > 0 && pricePail > 0)
                                                  Text(' • ', style: TextStyle(color: subtitleColor)),
                                                if (pricePail > 0)
                                                  Text(
                                                    'Pail: ${_formatRupiah(pricePail)}',
                                                    style: TextStyle(fontSize: 11, color: subtitleColor),
                                                  ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                      Icon(Icons.chevron_right_rounded, color: Colors.grey.shade400, size: 20),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildOfftakeScaffold({
    required BuildContext context,
    required bool canSubmitReport,
    required bool isDarkMode,
    required Color themeColor,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color elevatedColor,
    required LocaleProvider locale,
  }) {
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              widget.editSubmission != null ? 'Edit Laporan Offtake' : 'Laporan Offtake Dulux',
              style: TextStyle(color: textColor, fontSize: 15, fontWeight: FontWeight.bold),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            if (_selectedStoreName.isNotEmpty)
              Text(
                _selectedStoreName,
                style: TextStyle(color: subtitleColor, fontSize: 11.5, fontWeight: FontWeight.w500),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: _buildLocationStatusIndicator(canSubmitReport, isDarkMode),
          ),
        ],
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: ListView(
        controller: _scrollController,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        children: [
          // ─── Mode Switch: SALE vs NO SALE ───────────────────────────────
          Container(
            margin: const EdgeInsets.only(bottom: 14),
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                // Option: SALE
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _offtakeType = 'sale'),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _offtakeType == 'sale'
                            ? const Color(0xFF149A6E)
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: _offtakeType == 'sale'
                            ? [
                                BoxShadow(
                                  color: const Color(0xFF149A6E).withOpacity(0.35),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                )
                              ]
                            : null,
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.point_of_sale_rounded,
                            size: 18,
                            color: _offtakeType == 'sale' ? Colors.white : subtitleColor,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Ada Penjualan (Sale)',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: _offtakeType == 'sale' ? Colors.white : subtitleColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                // Option: NO SALE
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _offtakeType = 'no_sale'),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _offtakeType == 'no_sale'
                            ? const Color(0xFFE53935)
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: _offtakeType == 'no_sale'
                            ? [
                                BoxShadow(
                                  color: const Color(0xFFE53935).withOpacity(0.35),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                )
                              ]
                            : null,
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.remove_shopping_cart_rounded,
                            size: 18,
                            color: _offtakeType == 'no_sale' ? Colors.white : subtitleColor,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'No Sale (Nol Transaksi)',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: _offtakeType == 'no_sale' ? Colors.white : subtitleColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ─── Render Body According to Mode ───────────────────────────────
          if (_offtakeType == 'no_sale') ...[
            _buildOfftakeNoSaleBody(themeColor, cardColor, textColor, subtitleColor, isDarkMode, canSubmitReport),
          ] else ...[
            // Step Switch Tabs for Sale
            Container(
              margin: const EdgeInsets.only(bottom: 14),
              child: Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () => setState(() => _offtakeStep = 0),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: _offtakeStep == 0 ? themeColor.withOpacity(0.12) : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _offtakeStep == 0 ? themeColor : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.edit_note_rounded, size: 16, color: _offtakeStep == 0 ? themeColor : subtitleColor),
                            const SizedBox(width: 6),
                            Text(
                              '1. Input Produk (${_offtakeCart.length})',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: _offtakeStep == 0 ? themeColor : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: InkWell(
                      onTap: _offtakeCart.isEmpty
                          ? () {
                              toastification.show(
                                context: context,
                                type: ToastificationType.warning,
                                title: const Text('Keranjang Masih Kosong'),
                                description: const Text('Masukkan minimal 1 produk terlebih dahulu.'),
                                autoCloseDuration: const Duration(seconds: 2),
                              );
                            }
                          : () => setState(() => _offtakeStep = 1),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: _offtakeStep == 1 ? themeColor.withOpacity(0.12) : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _offtakeStep == 1 ? themeColor : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.fact_check_rounded, size: 16, color: _offtakeStep == 1 ? themeColor : subtitleColor),
                            const SizedBox(width: 6),
                            Text(
                              '2. Review & Submit',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: _offtakeStep == 1 ? themeColor : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            if (_offtakeStep == 0)
              _buildOfftakeSaleStep0Body(themeColor, cardColor, textColor, subtitleColor, elevatedColor, isDarkMode)
            else
              _buildOfftakeSaleStep1Body(themeColor, cardColor, textColor, subtitleColor, elevatedColor, isDarkMode, canSubmitReport),
          ],
          const SizedBox(height: 30),
        ],
      ),
    );
  }

  Widget _buildOfftakeNoSaleBody(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    bool isDarkMode,
    bool canSubmitReport,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFFE53935).withOpacity(0.3)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFE53935).withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.store_mall_directory_outlined,
                  size: 44,
                  color: Color(0xFFE53935),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Konfirmasi Laporan: NO SALE',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: textColor,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'Tidak ada penjualan cat Dulux di toko ini hari ini.\nLaporan akan dicatat dengan Volume 0.00 Liter dan Nilai Penjualan Rp 0.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12.5,
                  color: subtitleColor,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    Column(
                      children: [
                        Text('Total Unit', style: TextStyle(fontSize: 11, color: subtitleColor)),
                        const SizedBox(height: 2),
                        Text('0 Unit', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                      ],
                    ),
                    Container(height: 24, width: 1, color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                    Column(
                      children: [
                        Text('Total Liter', style: TextStyle(fontSize: 11, color: subtitleColor)),
                        const SizedBox(height: 2),
                        Text('0.00 L', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                      ],
                    ),
                    Container(height: 24, width: 1, color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                    Column(
                      children: [
                        Text('Total Nilai', style: TextStyle(fontSize: 11, color: subtitleColor)),
                        const SizedBox(height: 2),
                        Text('Rp 0', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
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
            onPressed: _isSubmitting ? null : _submitOfftakeNoSale,
            icon: _isSubmitting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim Laporan No Sale...' : 'Kirim Laporan No Sale Sekarang',
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFE53935),
              padding: const EdgeInsets.symmetric(vertical: 15),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 3,
            ),
          ),
      ],
    );
  }

  Widget _buildOfftakeSaleStep0Body(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
  ) {
    final p = _currentOfftakeProduct;
    final tinSize = p?.getPackagingSize('tin') ?? 1.0;
    final galonSize = p?.getPackagingSize('galon') ?? 2.5;
    final pailSize = p?.getPackagingSize('pail') ?? 20.0;
    final tinPrice = p?.getPriceForPackaging('tin') ?? 0;
    final galonPrice = p?.getPriceForPackaging('galon') ?? 0;
    final pailPrice = p?.getPriceForPackaging('pail') ?? 0;

    final qtyTin = int.tryParse(_offtakeQtyTinCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
    final qtyGalon = int.tryParse(_offtakeQtyGalonCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
    final qtyPail = int.tryParse(_offtakeQtyPailCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;

    final curUnits = qtyTin + qtyGalon + qtyPail;
    final curLiter = (qtyTin * tinSize) + (qtyGalon * galonSize) + (qtyPail * pailSize);
    final curRp = (qtyTin * tinPrice) + (qtyGalon * galonPrice) + (qtyPail * pailPrice);

    final grandLiter = _offtakeCart.fold<double>(0.0, (acc, itm) => acc + ((itm['total_liter'] as num?)?.toDouble() ?? 0.0));
    final grandRp = _offtakeCart.fold<int>(0, (acc, itm) => acc + ((itm['total_nilai_rp'] as num?)?.toInt() ?? 0));

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // ─── CARD 1: INPUT / PILIH PRODUK ──────────────────────────────
        Container(
          padding: const EdgeInsets.all(16),
          margin: const EdgeInsets.only(bottom: 14),
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
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(Icons.format_paint_rounded, color: themeColor, size: 20),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Pilih Sub Brand Produk',
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                        ),
                        Text(
                          'Pilih produk dari master data untuk input penjualan',
                          style: TextStyle(fontSize: 11, color: subtitleColor),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Button Selector
              InkWell(
                onTap: () => _openOfftakeProductPickerBottomSheet(themeColor, isDarkMode),
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: elevatedColor,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: p != null ? themeColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                      width: p != null ? 1.5 : 1.0,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        p != null ? Icons.check_circle_rounded : Icons.search_rounded,
                        color: p != null ? Colors.green : themeColor,
                        size: 20,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          p != null ? p.name : 'Tekan untuk memilih Sub Brand produk...',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: p != null ? FontWeight.bold : FontWeight.normal,
                            color: p != null ? textColor : subtitleColor,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Icon(Icons.arrow_drop_down_rounded, color: subtitleColor, size: 26),
                    ],
                  ),
                ),
              ),

              // Product Spec Detail
              if (p != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: themeColor.withOpacity(0.25)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Wrap(
                              spacing: 6,
                              runSpacing: 4,
                              children: [
                                if (p.brand != null)
                                  _buildProductInfoChip('Brand: ${p.brand}', Colors.indigo, isDarkMode),
                                if (p.pricingMatrix?['brand_rm_base'] != null)
                                  _buildProductInfoChip('RM/Base: ${p.pricingMatrix!['brand_rm_base']}', Colors.teal, isDarkMode),
                                if (p.pricingMatrix?['sub_brand_2'] != null && p.pricingMatrix!['sub_brand_2'].toString().isNotEmpty)
                                  _buildProductInfoChip(p.pricingMatrix!['sub_brand_2'], Colors.deepPurple, isDarkMode),
                              ],
                            ),
                          ),
                          TextButton(
                            onPressed: () => _openOfftakeProductPickerBottomSheet(themeColor, isDarkMode),
                            style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(40, 26), tapTargetSize: MaterialTapTargetSize.shrinkWrap),
                            child: Text('Ganti', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: themeColor)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      const Divider(height: 1),
                      const SizedBox(height: 8),
                      Text('Standar Kemasan & Harga Produk:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: subtitleColor)),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                children: [
                                  Text('Tin (${tinSize % 1 == 0 ? tinSize.toInt() : tinSize}L)', style: TextStyle(fontSize: 10, color: subtitleColor)),
                                  const SizedBox(height: 2),
                                  Text(tinPrice > 0 ? _formatRupiah(tinPrice) : '-', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: textColor)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                children: [
                                  Text('Galon (${galonSize % 1 == 0 ? galonSize.toInt() : galonSize}L)', style: TextStyle(fontSize: 10, color: subtitleColor)),
                                  const SizedBox(height: 2),
                                  Text(galonPrice > 0 ? _formatRupiah(galonPrice) : '-', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: textColor)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.all(6),
                              decoration: BoxDecoration(
                                color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Column(
                                children: [
                                  Text('Pail (${pailSize % 1 == 0 ? pailSize.toInt() : pailSize}L)', style: TextStyle(fontSize: 10, color: subtitleColor)),
                                  const SizedBox(height: 2),
                                  Text(pailPrice > 0 ? _formatRupiah(pailPrice) : '-', style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: textColor)),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 14),
                Text('Kuantiti Terjual (Pcs / Unit):', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                const SizedBox(height: 8),

                // 3 Inputs Row: Tin, Galon, Pail
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Tin', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor)),
                          const SizedBox(height: 4),
                          TextFormField(
                            controller: _offtakeQtyTinCtrl,
                            keyboardType: TextInputType.number,
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                            decoration: _inputDecoration('0', elevatedColor, isDarkMode),
                            onChanged: (v) => setState(() {}),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Galon', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor)),
                          const SizedBox(height: 4),
                          TextFormField(
                            controller: _offtakeQtyGalonCtrl,
                            keyboardType: TextInputType.number,
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                            decoration: _inputDecoration('0', elevatedColor, isDarkMode),
                            onChanged: (v) => setState(() {}),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Pail', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor)),
                          const SizedBox(height: 4),
                          TextFormField(
                            controller: _offtakeQtyPailCtrl,
                            keyboardType: TextInputType.number,
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                            decoration: _inputDecoration('0', elevatedColor, isDarkMode),
                            onChanged: (v) => setState(() {}),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 12),
                // Subtotal for current item
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: themeColor.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: themeColor.withOpacity(0.2)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Subtotal: $curUnits unit (${curLiter.toStringAsFixed(1)} L)',
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: textColor),
                      ),
                      Text(
                        _formatRupiah(curRp),
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: themeColor),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 12),
                // Button Add to Cart
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      if (curUnits <= 0) {
                        toastification.show(
                          context: context,
                          type: ToastificationType.warning,
                          title: const Text('Kuantiti Masih Kosong'),
                          description: const Text('Masukkan minimal 1 unit penjualan (Tin, Galon, atau Pail).'),
                          autoCloseDuration: const Duration(seconds: 3),
                        );
                        return;
                      }

                      final pMatrix = p.pricingMatrix;
                      final brandRmBase = pMatrix?['brand_rm_base'] ?? p.brand ?? 'DULUX';
                      final subBrand1 = pMatrix?['sub_brand_1'] ?? p.name;
                      final subBrand2 = pMatrix?['sub_brand_2'] ?? '';

                      final existingIdx = _offtakeCart.indexWhere((itm) => itm['product_id'] == p.id || itm['product_name'] == p.name);
                      setState(() {
                        if (existingIdx >= 0) {
                          final prevTin = (_offtakeCart[existingIdx]['qty_tin'] as num?)?.toInt() ?? 0;
                          final prevGalon = (_offtakeCart[existingIdx]['qty_galon'] as num?)?.toInt() ?? 0;
                          final prevPail = (_offtakeCart[existingIdx]['qty_pail'] as num?)?.toInt() ?? 0;

                          final newTin = prevTin + qtyTin;
                          final newGalon = prevGalon + qtyGalon;
                          final newPail = prevPail + qtyPail;

                          _offtakeCart[existingIdx]['qty_tin'] = newTin;
                          _offtakeCart[existingIdx]['qty_galon'] = newGalon;
                          _offtakeCart[existingIdx]['qty_pail'] = newPail;
                          _offtakeCart[existingIdx]['volume_tin_l'] = newTin * tinSize;
                          _offtakeCart[existingIdx]['volume_galon_l'] = newGalon * galonSize;
                          _offtakeCart[existingIdx]['volume_pail_l'] = newPail * pailSize;
                          _offtakeCart[existingIdx]['total_unit'] = newTin + newGalon + newPail;
                          _offtakeCart[existingIdx]['total_liter'] = (newTin * tinSize) + (newGalon * galonSize) + (newPail * pailSize);
                          _offtakeCart[existingIdx]['total_nilai_rp'] = (newTin * tinPrice) + (newGalon * galonPrice) + (newPail * pailPrice);
                        } else {
                          _offtakeCart.add({
                            'product_id': p.id,
                            'product_name': p.name,
                            'sub_brand': p.name,
                            'brand': p.brand ?? 'DULUX',
                            'brand_rm_base': brandRmBase,
                            'sub_brand1': subBrand1,
                            'sub_brand2': subBrand2,
                            'kemasan_tin': p.packagingInfo['tin'] ?? '${tinSize}L',
                            'kemasan_galon': p.packagingInfo['galon'] ?? '${galonSize}L',
                            'kemasan_pail': p.packagingInfo['pail'] ?? '${pailSize}L',
                            'qty_tin': qtyTin,
                            'qty_galon': qtyGalon,
                            'qty_pail': qtyPail,
                            'volume_tin_l': qtyTin * tinSize,
                            'volume_galon_l': qtyGalon * galonSize,
                            'volume_pail_l': qtyPail * pailSize,
                            'total_unit': curUnits,
                            'total_liter': curLiter,
                            'harga_tin': tinPrice,
                            'harga_galon': galonPrice,
                            'harga_pail': pailPrice,
                            'total_nilai_rp': curRp,
                          });
                        }

                        _currentOfftakeProduct = null;
                        _offtakeQtyTinCtrl.clear();
                        _offtakeQtyGalonCtrl.clear();
                        _offtakeQtyPailCtrl.clear();
                      });

                      toastification.show(
                        context: context,
                        type: ToastificationType.success,
                        title: const Text('Produk Disimpan ke Daftar ✓'),
                        description: Text('${p.name} ($curUnits unit) ditambahkan.'),
                        autoCloseDuration: const Duration(seconds: 2),
                      );
                    },
                    icon: const Icon(Icons.add_shopping_cart_rounded, size: 18, color: Colors.white),
                    label: const Text(
                      '+ Simpan Produk ke Daftar',
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: themeColor,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 2,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),

        // ─── CARD 2: DAFTAR PRODUK DALAM KERANJANG ─────────────────────
        Container(
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
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Icon(Icons.shopping_cart_checkout_rounded, size: 20, color: themeColor),
                      const SizedBox(width: 8),
                      Text(
                        'Produk Tersimpan (${_offtakeCart.length})',
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                      ),
                    ],
                  ),
                  if (_offtakeCart.isNotEmpty)
                    TextButton.icon(
                      onPressed: () {
                        setState(() => _offtakeCart.clear());
                      },
                      icon: const Icon(Icons.delete_sweep_rounded, size: 16, color: Colors.red),
                      label: const Text('Kosongkan', style: TextStyle(fontSize: 11, color: Colors.red)),
                      style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(40, 26)),
                    ),
                ],
              ),
              const SizedBox(height: 10),

              if (_offtakeCart.isEmpty) ...[
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 16),
                  alignment: Alignment.center,
                  child: Column(
                    children: [
                      Icon(Icons.shopping_basket_outlined, size: 40, color: Colors.grey.shade400),
                      const SizedBox(height: 8),
                      Text(
                        'Belum Ada Produk yang Disimpan',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Pilih produk dari form di atas, isi kuantiti terjual, lalu tekan tombol "+ Simpan Produk ke Daftar".',
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 11.5, color: subtitleColor),
                      ),
                    ],
                  ),
                ),
              ] else ...[
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _offtakeCart.length,
                  separatorBuilder: (ctx, i) => const Divider(height: 16),
                  itemBuilder: (ctx, idx) {
                    final itm = _offtakeCart[idx];
                    final pName = itm['sub_brand'] ?? itm['product_name'] ?? 'Produk';
                    final qTin = itm['qty_tin'] ?? 0;
                    final qGalon = itm['qty_galon'] ?? 0;
                    final qPail = itm['qty_pail'] ?? 0;
                    final totLit = (itm['total_liter'] as num?)?.toDouble() ?? 0.0;
                    final totRp = (itm['total_nilai_rp'] as num?)?.toInt() ?? 0;

                    return Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 28,
                          height: 28,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.12),
                            shape: BoxShape.circle,
                          ),
                          child: Text(
                            '${idx + 1}',
                            style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: themeColor),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                pName,
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Wrap(
                                spacing: 8,
                                children: [
                                  if (qTin > 0)
                                    Text('Tin: $qTin', style: TextStyle(fontSize: 11, color: subtitleColor)),
                                  if (qGalon > 0)
                                    Text('Galon: $qGalon', style: TextStyle(fontSize: 11, color: subtitleColor)),
                                  if (qPail > 0)
                                    Text('Pail: $qPail', style: TextStyle(fontSize: 11, color: subtitleColor)),
                                  Text('• ${totLit.toStringAsFixed(1)} Liter', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: textColor)),
                                ],
                              ),
                              const SizedBox(height: 2),
                              Text(
                                _formatRupiah(totRp),
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF149A6E)),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () {
                            setState(() => _offtakeCart.removeAt(idx));
                          },
                          icon: const Icon(Icons.close_rounded, size: 18, color: Colors.red),
                          tooltip: 'Hapus',
                        ),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 14),
                const Divider(height: 1),
                const SizedBox(height: 12),

                // Running Total Bar
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Total Liter', style: TextStyle(fontSize: 11, color: subtitleColor)),
                        Text('${grandLiter.toStringAsFixed(2)} L', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: textColor)),
                      ],
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text('Total Penjualan', style: TextStyle(fontSize: 11, color: subtitleColor)),
                        Text(_formatRupiah(grandRp), style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: themeColor)),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 14),

                // Button Proceed to Review
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      setState(() => _offtakeStep = 1);
                      if (_scrollController.hasClients) {
                        _scrollController.animateTo(0, duration: const Duration(milliseconds: 300), curve: Curves.easeOut);
                      }
                    },
                    icon: const Icon(Icons.arrow_forward_rounded, size: 18, color: Colors.white),
                    label: const Text(
                      'Lanjut ke Review & Konfirmasi',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF149A6E),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                      elevation: 3,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildOfftakeSaleStep1Body(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
    bool canSubmitReport,
  ) {
    final grandLiter = _offtakeCart.fold<double>(0.0, (acc, itm) => acc + ((itm['total_liter'] as num?)?.toDouble() ?? 0.0));
    final grandUnit = _offtakeCart.fold<int>(0, (acc, itm) => acc + ((itm['total_unit'] as num?)?.toInt() ?? 0));
    final grandRp = _offtakeCart.fold<int>(0, (acc, itm) => acc + ((itm['total_nilai_rp'] as num?)?.toInt() ?? 0));

    final custBeliCat = int.tryParse(_offtakeCustBeliCatCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
    final custBeliDulux = int.tryParse(_offtakeCustBeliDuluxCtrl.text.replaceAll(RegExp(r'[^0-9]'), '')) ?? 0;
    final double marketShare = custBeliCat > 0 ? ((custBeliDulux / custBeliCat) * 100.0).clamp(0.0, 100.0) : 0.0;
    final String marketShareStr = '${marketShare % 1 == 0 ? marketShare.toInt() : marketShare.toStringAsFixed(1)}%';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Top Back Button
        Row(
          children: [
            OutlinedButton.icon(
              onPressed: () => setState(() => _offtakeStep = 0),
              icon: const Icon(Icons.arrow_back_rounded, size: 16),
              label: const Text('Kembali / Tambah Produk', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold)),
              style: OutlinedButton.styleFrom(
                foregroundColor: themeColor,
                side: BorderSide(color: themeColor.withOpacity(0.5)),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),

        // ─── CARD 1: HERO GRAND TOTAL BANNER ──────────────────────────
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: isDarkMode
                  ? [const Color(0xFF1E3A8A), const Color(0xFF0F766E)]
                  : [const Color(0xFF0F52BA), const Color(0xFF149A6E)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: themeColor.withOpacity(0.3),
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
                  const Text(
                    'GRAND TOTAL OFFTAKE',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white70, letterSpacing: 1.1),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '${_offtakeCart.length} Produk',
                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                _formatRupiah(grandRp),
                style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.water_drop_rounded, size: 14, color: Colors.white),
                        const SizedBox(width: 4),
                        Text(
                          '${grandLiter.toStringAsFixed(2)} Liter',
                          style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.inventory_2_rounded, size: 14, color: Colors.white),
                        const SizedBox(width: 4),
                        Text(
                          '$grandUnit Kemasan',
                          style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        const SizedBox(height: 14),

        // ─── CARD 2: RINCIAN PER PRODUK ────────────────────────────────
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
                children: [
                  Icon(Icons.list_alt_rounded, size: 18, color: themeColor),
                  const SizedBox(width: 8),
                  Text('Rincian Penjualan per Produk', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor)),
                ],
              ),
              const SizedBox(height: 10),
              ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _offtakeCart.length,
                separatorBuilder: (ctx, i) => const Divider(height: 12),
                itemBuilder: (ctx, idx) {
                  final itm = _offtakeCart[idx];
                  final pName = itm['sub_brand'] ?? itm['product_name'] ?? 'Produk';
                  final qTin = itm['qty_tin'] ?? 0;
                  final qGalon = itm['qty_galon'] ?? 0;
                  final qPail = itm['qty_pail'] ?? 0;
                  final totLit = (itm['total_liter'] as num?)?.toDouble() ?? 0.0;
                  final totRp = (itm['total_nilai_rp'] as num?)?.toInt() ?? 0;

                  return Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(pName, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                            Text('Tin: $qTin • Galon: $qGalon • Pail: $qPail (${totLit.toStringAsFixed(1)} L)', style: TextStyle(fontSize: 11, color: subtitleColor)),
                          ],
                        ),
                      ),
                      Text(_formatRupiah(totRp), style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: themeColor)),
                    ],
                  );
                },
              ),
            ],
          ),
        ),

        const SizedBox(height: 14),

        // ─── CARD 3: DATA TRAFIK PENGUNJUNG TOKO ──────────────────────
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
                children: [
                  Container(
                    padding: const EdgeInsets.all(7),
                    decoration: BoxDecoration(
                      color: themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.people_alt_rounded, size: 18, color: themeColor),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Trafik Pengunjung Toko', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor)),
                        Text('Jumlah pengunjung dan pembeli cat hari ini', style: TextStyle(fontSize: 11, color: subtitleColor)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),

              // 1. Jumlah Customer Masuk
              Text('1. Jumlah Customer Masuk ke Toko', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: textColor)),
              const SizedBox(height: 4),
              TextFormField(
                controller: _offtakeCustMasukCtrl,
                keyboardType: TextInputType.number,
                style: TextStyle(fontSize: 13, color: textColor),
                decoration: _inputDecoration('Contoh: 25 orang', elevatedColor, isDarkMode),
                onChanged: (v) => setState(() {}),
              ),
              const SizedBox(height: 10),

              // 2. Jumlah Cust Beli Cat
              Text('2. Jumlah Customer yang Beli Cat', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: textColor)),
              const SizedBox(height: 4),
              TextFormField(
                controller: _offtakeCustBeliCatCtrl,
                keyboardType: TextInputType.number,
                style: TextStyle(fontSize: 13, color: textColor),
                decoration: _inputDecoration('Contoh: 15 orang', elevatedColor, isDarkMode),
                onChanged: (v) => setState(() {}),
              ),
              const SizedBox(height: 10),

              // 3. Jumlah Cust Beli Dulux
              Text('3. Jumlah Customer yang Beli Produk Dulux', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: textColor)),
              const SizedBox(height: 4),
              TextFormField(
                controller: _offtakeCustBeliDuluxCtrl,
                keyboardType: TextInputType.number,
                style: TextStyle(fontSize: 13, color: textColor),
                decoration: _inputDecoration('Contoh: 10 orang', elevatedColor, isDarkMode),
                onChanged: (v) => setState(() {}),
              ),
              const SizedBox(height: 10),

              // Market Share Badge
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Estimasi Market Share Dulux:', style: TextStyle(fontSize: 12, color: subtitleColor)),
                    Text(
                      marketShareStr,
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Color(0xFF149A6E)),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 14),

        // ─── CARD 4: FOTO CARD OFFTAKE & NOTA PENJUALAN ────────────────
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
                children: [
                  Container(
                    padding: const EdgeInsets.all(7),
                    decoration: BoxDecoration(
                      color: themeColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.camera_alt_rounded, size: 18, color: themeColor),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Foto Bukti Transaksi', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor)),
                        Text('Bisa diambil dari Kamera (Watermark) atau Galeri HP', style: TextStyle(fontSize: 11, color: subtitleColor)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // ─── 1. Foto Card Offtake (1 Foto) ──────────────────────
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('1. Foto Card Offtake (Wajib)', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                  if (_offtakeCardPhoto != null)
                    const Text('1 Foto Terambil ✓', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.green)),
                ],
              ),
              const SizedBox(height: 8),

              if (_offtakeCardPhoto != null) ...[
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: Image.file(
                        _offtakeCardPhoto!,
                        width: double.infinity,
                        height: 160,
                        fit: BoxFit.cover,
                      ),
                    ),
                    Positioned(
                      top: 8,
                      right: 8,
                      child: GestureDetector(
                        onTap: () => setState(() => _offtakeCardPhoto = null),
                        child: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: const BoxDecoration(
                            color: Colors.red,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.delete_rounded, color: Colors.white, size: 16),
                        ),
                      ),
                    ),
                    Positioned(
                      bottom: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black.withOpacity(0.7),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text('Foto Card Offtake', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                OutlinedButton.icon(
                  onPressed: _pickOfftakeCardPhoto,
                  icon: const Icon(Icons.cached_rounded, size: 16),
                  label: const Text('Ganti Foto Card Offtake', style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: themeColor,
                    side: BorderSide(color: themeColor.withOpacity(0.5)),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                ),
              ] else ...[
                OutlinedButton.icon(
                  onPressed: _pickOfftakeCardPhoto,
                  icon: const Icon(Icons.add_a_photo_rounded, size: 18),
                  label: const Text('Ambil Foto Card Offtake (Kamera / Galeri)', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: themeColor,
                    side: BorderSide(color: themeColor, width: 1.2),
                    padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ],

              const SizedBox(height: 18),
              const Divider(height: 1),
              const SizedBox(height: 14),

              // ─── 2. Foto Nota Penjualan (Multi-Foto) ────────────────
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('2. Foto Nota Penjualan (Multi-Foto)', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                  if (_offtakeNotaPhotos.isNotEmpty)
                    Text('${_offtakeNotaPhotos.length} Foto Terambil ✓', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.green)),
                ],
              ),
              const SizedBox(height: 8),

              if (_offtakeNotaPhotos.isNotEmpty) ...[
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: _offtakeNotaPhotos.asMap().entries.map((entry) {
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
                                _offtakeNotaPhotos.removeAt(idx);
                                if (idx < _offtakeNotaPhotoWatermarks.length) {
                                  _offtakeNotaPhotoWatermarks.removeAt(idx);
                                }
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
                              'Nota ${idx + 1}',
                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                      ],
                    );
                  }).toList(),
                ),
                const SizedBox(height: 10),
              ],

              OutlinedButton.icon(
                onPressed: _pickOfftakeNotaPhoto,
                icon: const Icon(Icons.add_photo_alternate_rounded, size: 18),
                label: Text(
                  _offtakeNotaPhotos.isEmpty
                      ? 'Tambah Foto Nota Penjualan (Kamera / Galeri)'
                      : 'Tambah Foto Nota Lainnya (${_offtakeNotaPhotos.length} Foto)',
                  style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF149A6E),
                  side: const BorderSide(color: Color(0xFF149A6E), width: 1.2),
                  padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 20),

        // ─── SUBMIT BUTTON ─────────────────────────────────────────────
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
            onPressed: _isSubmitting ? null : _submitOfftakeSale,
            icon: _isSubmitting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim Laporan Offtake...' : 'Kirim Laporan Offtake Selesai',
              style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF149A6E),
              padding: const EdgeInsets.symmetric(vertical: 15),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 3,
            ),
          ),
      ],
    );
  }

  Future<void> _submitOfftakeSale() async {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final token = auth.token;

    if (token == null) return;

    final bool isVisiting = attProvider.isVisiting;
    final bool isCheckedIn = attProvider.isCheckedIn;
    final bool isEditMode = widget.editSubmission != null;
    if (!isVisiting && !isCheckedIn && !isEditMode) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Belum Absensi Kehadiran / Visit'),
        description: const Text('Anda wajib Check-In atau Visit-In terlebih dahulu untuk mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    if (_offtakeCart.isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Keranjang Masih Kosong'),
        description: const Text('Silakan masukkan minimal 1 produk terjual ke daftar sebelum mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final bool hasCard = _offtakeCardPhoto != null || (_existingPhotoUrls.containsKey('foto_card_offtake') || _existingPhotoUrls.values.any((u) => u.isNotEmpty));
    final bool hasNota = _offtakeNotaPhotos.isNotEmpty || (_existingMultiPhotoUrls.containsKey('foto_nota_penjualan') && _existingMultiPhotoUrls['foto_nota_penjualan']!.isNotEmpty);

    if (!hasCard) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Wajib Foto Card Offtake'),
        description: const Text('Silakan ambil atau pilih foto Card Offtake terlebih dahulu.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (!hasNota) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Wajib Foto Nota Penjualan'),
        description: const Text('Silakan ambil atau pilih minimal 1 foto Nota Penjualan.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final double grandLiter = _offtakeCart.fold(0.0, (sum, itm) => sum + ((itm['total_liter'] as num?)?.toDouble() ?? 0.0));
      final int grandUnit = _offtakeCart.fold(0, (sum, itm) => sum + ((itm['total_unit'] as num?)?.toInt() ?? 0));
      final int grandRp = _offtakeCart.fold(0, (sum, itm) => sum + ((itm['total_nilai_rp'] as num?)?.toInt() ?? 0));

      final int custMasuk = int.tryParse(_offtakeCustMasukCtrl.text) ?? 0;
      final int custBeliCat = int.tryParse(_offtakeCustBeliCatCtrl.text) ?? 0;
      final int custBeliDulux = int.tryParse(_offtakeCustBeliDuluxCtrl.text) ?? 0;
      final double marketShare = custBeliCat > 0 ? ((custBeliDulux / custBeliCat) * 100.0).clamp(0.0, 100.0) : 0.0;
      final String marketShareStr = '${marketShare % 1 == 0 ? marketShare.toInt() : marketShare.toStringAsFixed(1)}%';

      final Map<String, dynamic> cleanFormValues = {
        'tipe_laporan_offtake': 'sale',
        'total_volume_unit': grandUnit,
        'total_volume_liter': grandLiter,
        'total_nilai_sales_rp': grandRp,
        'jml_customer_masuk': custMasuk,
        'jml_customer_beli_cat': custBeliCat,
        'jml_customer_beli_dulux': custBeliDulux,
        'estimasi_market_share_persen': marketShareStr,
        'offtake_items_json': jsonEncode(_offtakeCart),
      };

      if (_offtakeCart.isNotEmpty) {
        final first = _offtakeCart.first;
        cleanFormValues['sub_brand'] = first['sub_brand'];
        cleanFormValues['brand'] = first['brand'];
        cleanFormValues['brand_rm_base'] = first['brand_rm_base'];
        cleanFormValues['sub_brand1'] = first['sub_brand1'];
        cleanFormValues['sub_brand2'] = first['sub_brand2'];
        cleanFormValues['kemasan_tin'] = first['kemasan_tin'];
        cleanFormValues['kemasan_galon'] = first['kemasan_galon'];
        cleanFormValues['kemasan_pail'] = first['kemasan_pail'];
        cleanFormValues['qty_tin'] = first['qty_tin'];
        cleanFormValues['qty_galon'] = first['qty_galon'];
        cleanFormValues['qty_pail'] = first['qty_pail'];
        cleanFormValues['volume_tin_l'] = first['volume_tin_l'];
        cleanFormValues['volume_galon_l'] = first['volume_galon_l'];
        cleanFormValues['volume_pail_l'] = first['volume_pail_l'];
      }

      for (final f in widget.template.fields) {
        final fn = f.fieldName.toLowerCase();
        final fKey = f.id.toString();
        if (cleanFormValues.containsKey(fn)) {
          cleanFormValues[fKey] = cleanFormValues[fn];
        }
      }

      final Map<String, dynamic> allPhotosPayload = {};
      final Map<String, String> watermarkPayload = {};

      ReportFormFieldModel? cardField;
      ReportFormFieldModel? notaField;
      ReportFormFieldModel? genericField;

      for (final f in widget.template.fields) {
        final fn = f.fieldName.toLowerCase();
        if (fn == 'foto_card_offtake' || fn.contains('card_offtake')) {
          cardField = f;
        } else if (fn == 'foto_nota_penjualan' || fn.contains('nota')) {
          notaField = f;
        } else if (['photo', 'camera_photo', 'multi_photo'].contains(f.fieldType)) {
          genericField ??= f;
        }
      }

      if (cardField != null && _offtakeCardPhoto != null) {
        allPhotosPayload[cardField.id.toString()] = _offtakeCardPhoto!;
        if (_offtakeCardPhotoWatermark != null) {
          watermarkPayload[cardField.id.toString()] = _offtakeCardPhotoWatermark!;
        }
      }

      if (notaField != null && _offtakeNotaPhotos.isNotEmpty) {
        allPhotosPayload[notaField.id.toString()] = _offtakeNotaPhotos;
        if (_offtakeNotaPhotoWatermarks.isNotEmpty) {
          watermarkPayload[notaField.id.toString()] = _offtakeNotaPhotoWatermarks.first;
        }
      }

      // Backward compatibility jika server hanya punya 1 field foto (nota / generic):
      if (cardField == null && _offtakeCardPhoto != null) {
        final target = notaField ?? genericField;
        if (target != null) {
          final existing = allPhotosPayload[target.id.toString()];
          List<File> combined = [];
          if (existing is List<File>) {
            combined = List<File>.from(existing);
          } else if (existing is File) {
            combined = [existing];
          }
          combined.insert(0, _offtakeCardPhoto!);
          allPhotosPayload[target.id.toString()] = combined;
        } else {
          allPhotosPayload['foto_card_offtake'] = _offtakeCardPhoto!;
        }
      }

      if (allPhotosPayload.isEmpty) {
        if (_offtakeCardPhoto != null) allPhotosPayload['foto_card_offtake'] = _offtakeCardPhoto!;
        if (_offtakeNotaPhotos.isNotEmpty) allPhotosPayload['foto_nota_penjualan'] = _offtakeNotaPhotos;
      }

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
          watermarkTexts: watermarkPayload,
        );
      }

      setState(() => _isSubmitting = false);

      if (result['success'] == true && mounted) {
        if (attProvider.isVisiting) {
          attProvider.markVisitReportFilled();
        }
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Laporan Offtake Terkirim'),
          description: Text('Total $grandUnit unit (${grandLiter.toStringAsFixed(1)} L) berhasil dilaporkan.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
        Navigator.of(context).pop(true);
      } else if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Gagal Mengirim Laporan'),
          description: Text(result['message'] ?? 'Terjadi kesalahan sistem.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Terjadi Kesalahan'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }

  Future<void> _submitOfftakeNoSale() async {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final token = auth.token;

    if (token == null) return;

    final bool isVisiting = attProvider.isVisiting;
    final bool isCheckedIn = attProvider.isCheckedIn;
    final bool isEditMode = widget.editSubmission != null;
    if (!isVisiting && !isCheckedIn && !isEditMode) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Belum Absensi Kehadiran / Visit'),
        description: const Text('Anda wajib Check-In atau Visit-In terlebih dahulu untuk mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final Map<String, dynamic> cleanFormValues = {
        'tipe_laporan_offtake': 'no_sale',
        'sub_brand': 'NO SALE',
        'brand': 'DULUX',
        'brand_rm_base': '-',
        'sub_brand1': '-',
        'sub_brand2': '-',
        'kemasan_tin': '-',
        'kemasan_galon': '-',
        'kemasan_pail': '-',
        'qty_tin': 0,
        'qty_galon': 0,
        'qty_pail': 0,
        'volume_tin_l': 0.0,
        'volume_galon_l': 0.0,
        'volume_pail_l': 0.0,
        'total_volume_unit': 0,
        'total_volume_liter': 0.0,
        'total_nilai_sales_rp': 0,
        'jml_customer_masuk': 0,
        'jml_customer_beli_cat': 0,
        'jml_customer_beli_dulux': 0,
        'estimasi_market_share_persen': '0%',
        'offtake_items_json': '[]',
      };

      for (final f in widget.template.fields) {
        final fn = f.fieldName.toLowerCase();
        final fKey = f.id.toString();
        if (cleanFormValues.containsKey(fn)) {
          cleanFormValues[fKey] = cleanFormValues[fn];
        }
      }

      Map<String, dynamic> result;
      if (widget.editSubmission != null) {
        result = await repProvider.updateReport(
          token: token,
          submissionId: widget.editSubmission!.id,
          storeName: _selectedStoreName,
          workLocationId: _selectedWorkLocationId,
          address: _selectedLocation?['address'] ?? _address,
          values: cleanFormValues,
          photoFiles: {},
          existingPhotos: {},
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
          photoFiles: {},
          watermarkTexts: {},
        );
      }

      setState(() => _isSubmitting = false);

      if (result['success'] == true && mounted) {
        if (attProvider.isVisiting) {
          attProvider.markVisitReportFilled();
        }
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Laporan No Sale Terkirim'),
          description: const Text('Laporan tanpa transaksi penjualan berhasil dikirim.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
        Navigator.of(context).pop(true);
      } else if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Gagal Mengirim Laporan'),
          description: Text(result['message'] ?? 'Terjadi kesalahan sistem.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Terjadi Kesalahan'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── OUT OF STOCK (OOS) DULUX SYSTEM IMPLEMENTATION ──────────────────────────
  // ════════════════════════════════════════════════════════════════════════════

  static const List<String> _kOosReasons = [
    '1. Sudah buka PO namun belum ada pengiriman ke toko',
    '2. Sudah buka PO namun kendala stock di distributor',
    '3. Kendala pembayaran (kiriman barang diblokir)',
    '4. Barang sedang dalam proses pengiriman ke toko',
    '5. Adanya pembelian dalam jumlah besar (borongan) sehingga menyebabkan OOS',
    '6. Other / Lainnya',
  ];

  static const List<String> _kOosKemasanOptions = [
    'Tin (1 L)',
    'Galon (2.5 L)',
    'Pail (20 L)',
    'Pail (20 Kg)',
    'Galon (5 Kg)',
  ];

  static const List<String> _kOosBaseOptions = [
    'Ready Mix',
    'Base A',
    'Base B',
    'Base C',
    'Base D',
    'Base Medium',
    'Primer / Cat Dasar',
  ];

  List<TemplateProductModel> _getOosProducts() {
    final prods = _getProducts();
    if (prods.isNotEmpty) return prods;
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    for (final t in repProvider.templates) {
      if (t.products.isNotEmpty) return t.products;
    }
    return [];
  }

  void _onOosProductSelected(TemplateProductModel prod) {
    setState(() {
      _currentOosProduct = prod;

      // 1. Kemasan Size auto detection
      final pName = prod.name.toUpperCase();
      final pPack = (prod.pricingMatrix?['packaging']?.toString() ?? prod.uom).toUpperCase();
      if (pName.contains('20 L') || pName.contains('20L') || pName.contains('PAIL') || pPack.contains('PAIL') || pPack.contains('20')) {
        _oosKemasanSizeCtrl.text = 'Pail (20 L)';
      } else if (pName.contains('2.5 L') || pName.contains('2.5L') || pName.contains('GALON') || pPack.contains('GALON') || pPack.contains('2.5')) {
        _oosKemasanSizeCtrl.text = 'Galon (2.5 L)';
      } else if (pName.contains('1 L') || pName.contains('1L') || pName.contains('TIN') || pPack.contains('TIN') || pPack.contains('1')) {
        _oosKemasanSizeCtrl.text = 'Tin (1 L)';
      } else if (pName.contains('20 KG') || pName.contains('20KG')) {
        _oosKemasanSizeCtrl.text = 'Pail (20 Kg)';
      } else if (pName.contains('5 KG') || pName.contains('5KG')) {
        _oosKemasanSizeCtrl.text = 'Galon (5 Kg)';
      } else {
        _oosKemasanSizeCtrl.text = 'Galon (2.5 L)';
      }

      // 2. Base Color / RM auto detection
      final rmBase = (prod.pricingMatrix?['brand_rm_base']?.toString() ?? '').toUpperCase();
      if (rmBase.contains('RM') || rmBase.contains('READY MIX')) {
        _oosBaseWarnaCtrl.text = 'Ready Mix';
        _oosReadyMixColorCtrl.text = prod.name;
      } else if (rmBase.contains('BASE A')) {
        _oosBaseWarnaCtrl.text = 'Base A';
        _oosReadyMixColorCtrl.text = 'Bukan Ready Mix (Base Oplos)';
      } else if (rmBase.contains('BASE B')) {
        _oosBaseWarnaCtrl.text = 'Base B';
        _oosReadyMixColorCtrl.text = 'Bukan Ready Mix (Base Oplos)';
      } else if (rmBase.contains('BASE C')) {
        _oosBaseWarnaCtrl.text = 'Base C';
        _oosReadyMixColorCtrl.text = 'Bukan Ready Mix (Base Oplos)';
      } else if (rmBase.contains('BASE D')) {
        _oosBaseWarnaCtrl.text = 'Base D';
        _oosReadyMixColorCtrl.text = 'Bukan Ready Mix (Base Oplos)';
      } else if (rmBase.contains('BASE M')) {
        _oosBaseWarnaCtrl.text = 'Base Medium';
        _oosReadyMixColorCtrl.text = 'Bukan Ready Mix (Base Oplos)';
      } else if (rmBase.contains('PRIMER') || (prod.category ?? '').toLowerCase().contains('primer') || pName.contains('PRIMER')) {
        _oosBaseWarnaCtrl.text = 'Primer / Cat Dasar';
        _oosReadyMixColorCtrl.text = 'Primer / Cat Dasar';
      } else {
        _oosBaseWarnaCtrl.text = 'Base A';
        _oosReadyMixColorCtrl.text = 'Bukan Ready Mix (Base Oplos)';
      }

      // 3. Auto calculate consecutive OOS days from previous history (0 if new / no history)
      final prevMatch = _previousOosItems.firstWhere(
        (item) =>
            (item['product_id'] != null && item['product_id'] == prod.id) ||
            (item['product_name'] != null && item['product_name'].toString().toLowerCase() == prod.name.toLowerCase()) ||
            (item['product_code'] != null && item['product_code'].toString().toLowerCase() == (prod.skuCode ?? '').toLowerCase()),
        orElse: () => {},
      );

      int calculatedDays = 1;
      int prevSaran = 2;
      if (prevMatch.isNotEmpty) {
        final prevLama = int.tryParse(prevMatch['lama_oos_hari']?.toString() ?? '1') ?? 1;
        calculatedDays = prevLama + _oosDiffDays;
        prevSaran = int.tryParse(prevMatch['saran_qty_order']?.toString() ?? '2') ?? 2;
        if (prevMatch['alasan_oos'] != null && _oosAlasanCtrl.text.isEmpty) {
          _oosAlasanCtrl.text = prevMatch['alasan_oos'].toString();
        }
      }
      _oosLamaHariCtrl.text = calculatedDays.toString();
      _oosSaranQtyCtrl.text = prevSaran.toString();

      // 4. Default Reason
      if (_oosAlasanCtrl.text.isEmpty) {
        _oosAlasanCtrl.text = _kOosReasons.first;
      }
    });
  }

  void _addCurrentOosToCart() {
    if (_currentOosProduct == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Pilih Produk Terlebih Dahulu'),
        description: const Text('Silakan pilih produk Dulux / Catylac yang mengalami OOS.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final p = _currentOosProduct!;
    final alreadyInCart = _oosCart.any((item) =>
        item['product_name']?.toString().toLowerCase() == p.name.toLowerCase() ||
        (item['product_id'] != null && item['product_id'] == p.id));

    if (alreadyInCart) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Produk Sudah Ada di Daftar'),
        description: Text('Produk "${p.name}" sudah dimasukkan ke daftar OOS.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final int lamaHari = int.tryParse(_oosLamaHariCtrl.text) ?? 1;
    final int saranQty = int.tryParse(_oosSaranQtyCtrl.text) ?? 2;
    final String kemasan = _oosKemasanSizeCtrl.text.isNotEmpty ? _oosKemasanSizeCtrl.text : 'Galon (2.5 L)';
    final String base = _oosBaseWarnaCtrl.text.isNotEmpty ? _oosBaseWarnaCtrl.text : 'Base A';
    final String alasan = _oosAlasanCtrl.text.isNotEmpty
        ? (_oosAlasanCtrl.text == '6. Other / Lainnya' && _oosAlasanLainnyaCtrl.text.isNotEmpty ? _oosAlasanLainnyaCtrl.text : _oosAlasanCtrl.text)
        : _kOosReasons.first;

    setState(() {
      _oosCart.add({
        'product_id': p.id,
        'product_code': p.skuCode ?? p.name,
        'product_name': p.name,
        'brand': p.brand ?? 'Dulux',
        'kemasan_size': kemasan,
        'kemasan_size_oos': kemasan,
        'ukuran_kemasan_size': kemasan,
        'base_color': base,
        'base_warna_oos': base,
        'base_tipe_warna': base,
        'warna_ready_mix_oos': _oosReadyMixColorCtrl.text.isNotEmpty ? _oosReadyMixColorCtrl.text : 'Bukan Ready Mix (Base Oplos)',
        'lama_oos_hari': lamaHari,
        'saran_qty_order': saranQty,
        'saran_kuantiti_order_ke_toko_qty_kemasan': saranQty,
        'alasan_oos': alasan,
        'channel_toko': _oosChannelTokoCtrl.text,
      });

      _currentOosProduct = null;
      _oosKemasanSizeCtrl.clear();
      _oosBaseWarnaCtrl.clear();
      _oosReadyMixColorCtrl.clear();
      _oosLamaHariCtrl.text = '1';
      _oosSaranQtyCtrl.text = '2';
      _oosAlasanCtrl.clear();
      _oosAlasanLainnyaCtrl.clear();
    });

    toastification.show(
      context: context,
      type: ToastificationType.success,
      title: const Text('Item OOS Ditambahkan'),
      description: Text('${p.name} berhasil dimasukkan ke daftar OOS hari ini.'),
      autoCloseDuration: const Duration(seconds: 2),
    );
  }

  void _addPreviousOosToCart(Map<String, dynamic> prev) {
    final prodName = prev['product_name']?.toString() ?? 'Dulux Product';
    final alreadyInCart = _oosCart.any((item) => item['product_name']?.toString().toLowerCase() == prodName.toLowerCase());

    if (alreadyInCart) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Produk Sudah Ada di Daftar'),
        description: Text('Produk "$prodName" sudah dimasukkan ke daftar OOS.'),
        autoCloseDuration: const Duration(seconds: 2),
      );
      return;
    }

    final prevLama = int.tryParse(prev['lama_oos_hari']?.toString() ?? '1') ?? 1;
    final int consecutiveDays = prevLama + _oosDiffDays;
    final prevSaran = int.tryParse(prev['saran_qty_order']?.toString() ?? '2') ?? 2;
    final String kemasan = prev['kemasan_size'] ?? prev['kemasan_size_oos'] ?? 'Galon (2.5 L)';
    final String base = prev['base_color'] ?? prev['base_warna_oos'] ?? 'Base A';

    setState(() {
      _oosCart.add({
        'product_id': prev['product_id'],
        'product_code': prev['product_code'] ?? prodName,
        'product_name': prodName,
        'brand': prev['brand'] ?? 'Dulux',
        'kemasan_size': kemasan,
        'kemasan_size_oos': kemasan,
        'ukuran_kemasan_size': kemasan,
        'base_color': base,
        'base_warna_oos': base,
        'base_tipe_warna': base,
        'warna_ready_mix_oos': prev['warna_ready_mix_oos'] ?? 'Bukan Ready Mix (Base Oplos)',
        'lama_oos_hari': consecutiveDays,
        'saran_qty_order': prevSaran,
        'saran_kuantiti_order_ke_toko_qty_kemasan': prevSaran,
        'alasan_oos': prev['alasan_oos'] ?? _kOosReasons.first,
        'channel_toko': _oosChannelTokoCtrl.text,
      });
    });

    toastification.show(
      context: context,
      type: ToastificationType.success,
      title: const Text('OOS Lanjutan Ditambahkan'),
      description: Text('$prodName ditambahkan dengan durasi berlanjut ($consecutiveDays hari).'),
      autoCloseDuration: const Duration(seconds: 2),
    );
  }

  void _openOosProductPickerBottomSheet(Color themeColor, bool isDarkMode) {
    final allProducts = _getOosProducts();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        String searchQuery = '';
        String selectedFilter = 'Semua';

        final categories = <String>['Semua'];
        for (final p in allProducts) {
          final cat = p.category?.trim() ?? '';
          if (cat.isNotEmpty && !categories.contains(cat)) {
            categories.add(cat);
          }
        }

        return StatefulBuilder(
          builder: (context, setSheetState) {
            final filteredProducts = allProducts.where((p) {
              final matchesFilter = selectedFilter == 'Semua' || (p.category != null && p.category!.trim().toLowerCase() == selectedFilter.toLowerCase());
              if (!matchesFilter) return false;

              if (searchQuery.isEmpty) return true;
              final q = searchQuery.toLowerCase();
              final matchesName = p.name.toLowerCase().contains(q);
              final matchesSku = p.skuCode?.toLowerCase().contains(q) ?? false;
              final matchesBrand = p.brand?.toLowerCase().contains(q) ?? false;
              return matchesName || matchesSku || matchesBrand;
            }).toList();

            final sheetBg = isDarkMode ? const Color(0xFF1E1E1E) : Colors.white;
            final itemBg = isDarkMode ? const Color(0xFF2A2A2A) : const Color(0xFFF8FAFC);
            final sheetText = isDarkMode ? Colors.white : const Color(0xFF1E293B);
            final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);

            return Container(
              height: MediaQuery.of(context).size.height * 0.85,
              decoration: BoxDecoration(
                color: sheetBg,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  Center(
                    child: Container(
                      margin: const EdgeInsets.only(top: 10, bottom: 8),
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade400,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFD97706).withOpacity(0.12),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.remove_shopping_cart_rounded, color: Color(0xFFD97706), size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Pilih Produk Out of Stock (OOS)',
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: sheetText),
                              ),
                              Text(
                                'Katalog ${allProducts.length} Produk Dulux & Catylac',
                                style: TextStyle(fontSize: 11.5, color: Colors.grey.shade500),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () => Navigator.of(sheetCtx).pop(),
                          icon: const Icon(Icons.close_rounded),
                          color: Colors.grey.shade500,
                        ),
                      ],
                    ),
                  ),

                  // Search Box
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
                    child: TextField(
                      onChanged: (val) => setSheetState(() => searchQuery = val),
                      style: TextStyle(fontSize: 13, color: sheetText),
                      decoration: InputDecoration(
                        hintText: 'Cari nama produk, SKU, atau brand...',
                        hintStyle: TextStyle(fontSize: 13, color: subtitleColor),
                        prefixIcon: Icon(Icons.search_rounded, size: 20, color: themeColor),
                        filled: true,
                        fillColor: itemBg,
                        contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      ),
                    ),
                  ),

                  // Category Filter Pills
                  if (categories.length > 1)
                    SizedBox(
                      height: 38,
                      child: ListView.separated(
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        scrollDirection: Axis.horizontal,
                        itemCount: categories.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 8),
                        itemBuilder: (ctx, idx) {
                          final cat = categories[idx];
                          final isSel = selectedFilter == cat;
                          return ChoiceChip(
                            label: Text(cat, style: TextStyle(fontSize: 12, fontWeight: isSel ? FontWeight.bold : FontWeight.normal, color: isSel ? Colors.white : subtitleColor)),
                            selected: isSel,
                            onSelected: (val) => setSheetState(() => selectedFilter = cat),
                            selectedColor: themeColor,
                            backgroundColor: itemBg,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20), side: BorderSide(color: isSel ? themeColor : Colors.grey.shade300)),
                          );
                        },
                      ),
                    ),

                  const Divider(height: 16),

                  // Product List
                  Expanded(
                    child: filteredProducts.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.search_off_rounded, size: 48, color: Colors.grey.shade400),
                                const SizedBox(height: 8),
                                Text('Produk tidak ditemukan', style: TextStyle(color: subtitleColor, fontSize: 13)),
                              ],
                            ),
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
                            itemCount: filteredProducts.length,
                            itemBuilder: (ctx, idx) {
                              final prod = filteredProducts[idx];
                              final isCurrent = _currentOosProduct?.id == prod.id;
                              final inCart = _oosCart.any((c) => c['product_name'] == prod.name || (c['product_id'] != null && c['product_id'] == prod.id));

                              return Container(
                                margin: const EdgeInsets.only(bottom: 8),
                                decoration: BoxDecoration(
                                  color: isCurrent ? themeColor.withOpacity(0.08) : (inCart ? Colors.amber.withOpacity(0.06) : itemBg),
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(
                                    color: isCurrent ? themeColor : (inCart ? Colors.amber.shade300 : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200)),
                                    width: isCurrent ? 1.5 : 1.0,
                                  ),
                                ),
                                child: ListTile(
                                  onTap: () {
                                    Navigator.of(sheetCtx).pop();
                                    _onOosProductSelected(prod);
                                  },
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                                  leading: Container(
                                    width: 40,
                                    height: 40,
                                    decoration: BoxDecoration(
                                      color: inCart ? Colors.amber.withOpacity(0.15) : (isCurrent ? themeColor.withOpacity(0.15) : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200)),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Icon(
                                      inCart ? Icons.check_circle_rounded : Icons.format_paint_rounded,
                                      color: inCart ? Colors.amber.shade700 : (isCurrent ? themeColor : subtitleColor),
                                      size: 20,
                                    ),
                                  ),
                                  title: Text(
                                    prod.name,
                                    style: TextStyle(
                                      fontSize: 13.5,
                                      fontWeight: FontWeight.bold,
                                      color: isCurrent ? themeColor : sheetText,
                                    ),
                                  ),
                                  subtitle: Row(
                                    children: [
                                      if (prod.brand != null) ...[
                                        Text(prod.brand!, style: TextStyle(fontSize: 11, color: subtitleColor)),
                                        const SizedBox(width: 6),
                                        Text('•', style: TextStyle(fontSize: 11, color: subtitleColor)),
                                        const SizedBox(width: 6),
                                      ],
                                      if (prod.pricingMatrix?['brand_rm_base'] != null)
                                        Text('${prod.pricingMatrix!['brand_rm_base']}', style: const TextStyle(fontSize: 11, color: Color(0xFF0F52BA), fontWeight: FontWeight.w600)),
                                      if (inCart) ...[
                                        const Spacer(),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(color: Colors.amber.shade100, borderRadius: BorderRadius.circular(6)),
                                          child: Text('Ada di Daftar', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.amber.shade900)),
                                        ),
                                      ],
                                    ],
                                  ),
                                  trailing: Icon(Icons.arrow_forward_ios_rounded, size: 14, color: isCurrent ? themeColor : Colors.grey.shade400),
                                ),
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildOosScaffold({
    required BuildContext context,
    required bool canSubmitReport,
    required bool isDarkMode,
    required Color themeColor,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color elevatedColor,
    required LocaleProvider locale,
  }) {
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              widget.editSubmission != null ? 'Edit Laporan OOS' : 'Laporan Out of Stock (OOS)',
              style: TextStyle(color: textColor, fontSize: 15, fontWeight: FontWeight.bold),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            if (_selectedStoreName.isNotEmpty)
              Text(
                _selectedStoreName,
                style: TextStyle(color: subtitleColor, fontSize: 11.5, fontWeight: FontWeight.w500),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: _buildLocationStatusIndicator(canSubmitReport, isDarkMode),
          ),
        ],
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: ListView(
        controller: _scrollController,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        children: [
          // ─── Mode Switch: OOS vs NO OOS (STOK LENGKAP) ───────────────────────────
          Container(
            margin: const EdgeInsets.only(bottom: 14),
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                // Option: OOS (Ada Barang Kosong)
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _oosType = 'oos'),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _oosType == 'oos' ? const Color(0xFFE53935) : Colors.transparent,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: _oosType == 'oos'
                            ? [
                                BoxShadow(
                                  color: const Color(0xFFE53935).withOpacity(0.35),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                )
                              ]
                            : null,
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.remove_shopping_cart_rounded,
                            size: 18,
                            color: _oosType == 'oos' ? Colors.white : subtitleColor,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'OOS (Ada Barang Kosong)',
                            style: TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.bold,
                              color: _oosType == 'oos' ? Colors.white : subtitleColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),

                // Option: NO OOS (Stok Lengkap)
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _oosType = 'no_oos'),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _oosType == 'no_oos' ? const Color(0xFF149A6E) : Colors.transparent,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: _oosType == 'no_oos'
                            ? [
                                BoxShadow(
                                  color: const Color(0xFF149A6E).withOpacity(0.35),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                )
                              ]
                            : null,
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.check_circle_rounded,
                            size: 18,
                            color: _oosType == 'no_oos' ? Colors.white : subtitleColor,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'No OOS (Stok Lengkap)',
                            style: TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.bold,
                              color: _oosType == 'no_oos' ? Colors.white : subtitleColor,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ─── Render Body According to Mode ───────────────────────────────
          if (_oosType == 'no_oos') ...[
            _buildOosNoOosBody(themeColor, cardColor, textColor, subtitleColor, isDarkMode, canSubmitReport),
          ] else ...[
            // Step Switch Tabs for OOS
            Container(
              margin: const EdgeInsets.only(bottom: 14),
              child: Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () => setState(() => _oosStep = 0),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: _oosStep == 0 ? themeColor.withOpacity(0.12) : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _oosStep == 0 ? themeColor : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.edit_note_rounded, size: 16, color: _oosStep == 0 ? themeColor : subtitleColor),
                            const SizedBox(width: 6),
                            Text(
                              '1. Input OOS (${_oosCart.length})',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: _oosStep == 0 ? themeColor : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: InkWell(
                      onTap: _oosCart.isEmpty
                          ? () {
                              toastification.show(
                                context: context,
                                type: ToastificationType.warning,
                                title: const Text('Daftar OOS Masih Kosong'),
                                description: const Text('Masukkan minimal 1 produk yang kosong terlebih dahulu.'),
                                autoCloseDuration: const Duration(seconds: 2),
                              );
                            }
                          : () => setState(() => _oosStep = 1),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: _oosStep == 1 ? themeColor.withOpacity(0.12) : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: _oosStep == 1 ? themeColor : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.fact_check_rounded, size: 16, color: _oosStep == 1 ? themeColor : subtitleColor),
                            const SizedBox(width: 6),
                            Text(
                              '2. Review & Submit',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: _oosStep == 1 ? themeColor : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            if (_oosStep == 0)
              _buildOosStep0Body(themeColor, cardColor, textColor, subtitleColor, elevatedColor, isDarkMode)
            else
              _buildOosStep1Body(themeColor, cardColor, textColor, subtitleColor, elevatedColor, isDarkMode, canSubmitReport),
          ],
          const SizedBox(height: 30),
        ],
      ),
    );
  }

  Widget _buildOosNoOosBody(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    bool isDarkMode,
    bool canSubmitReport,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFF149A6E).withOpacity(0.35)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFF149A6E).withOpacity(0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.check_circle_outline_rounded,
                  size: 48,
                  color: Color(0xFF149A6E),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                'Stok Lengkap (No OOS)',
                style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.bold,
                  color: textColor,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Toko memiliki ketersediaan stok lengkap untuk seluruh SKU Dulux & Catylac. Tidak ada produk yang mengalami Out of Stock.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12.5,
                  color: subtitleColor,
                  height: 1.45,
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFF149A6E).withOpacity(0.08),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFF149A6E).withOpacity(0.2)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.info_outline_rounded, size: 18, color: Color(0xFF149A6E)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Mengirim laporan No OOS akan mereset hitungan hari OOS produk toko ini karena seluruh barang telah tersedia.',
                        style: TextStyle(fontSize: 11.5, color: isDarkMode ? Colors.green.shade200 : const Color(0xFF166534)),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 24),

        if (!canSubmitReport)
          ElevatedButton.icon(
            onPressed: null,
            icon: const Icon(Icons.lock_clock_rounded, size: 18),
            label: const Text(
              'Wajib Check-In / Visit-In Toko',
              style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
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
            onPressed: _isSubmitting ? null : _submitOosNoOos,
            icon: _isSubmitting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
            label: Text(
              _isSubmitting ? 'Mengirim Laporan Stok Lengkap...' : 'Kirim Laporan Stok Lengkap (No OOS)',
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF149A6E),
              padding: const EdgeInsets.symmetric(vertical: 15),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              elevation: 3,
            ),
          ),
      ],
    );
  }

  Widget _buildOosStep0Body(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
  ) {
    final p = _currentOosProduct;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // ─── CARD 1: INPUT / PILIH PRODUK OOS ──────────────────────────────
        Container(
          padding: const EdgeInsets.all(16),
          margin: const EdgeInsets.only(bottom: 14),
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
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD97706).withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.remove_shopping_cart_rounded, color: Color(0xFFD97706), size: 20),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Pilih Produk Kosong (OOS)',
                          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                        ),
                        Text(
                          'Data kemasan, base, dan lama OOS akan terisi otomatis',
                          style: TextStyle(fontSize: 11, color: subtitleColor),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Button Selector
              InkWell(
                onTap: () => _openOosProductPickerBottomSheet(themeColor, isDarkMode),
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: elevatedColor,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: p != null ? themeColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                      width: p != null ? 1.5 : 1.0,
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        p != null ? Icons.check_circle_rounded : Icons.search_rounded,
                        color: p != null ? Colors.green : themeColor,
                        size: 20,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          p != null ? p.name : 'Tekan untuk memilih produk yang OOS...',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: p != null ? FontWeight.bold : FontWeight.normal,
                            color: p != null ? textColor : subtitleColor,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Icon(Icons.arrow_drop_down_rounded, color: subtitleColor, size: 26),
                    ],
                  ),
                ),
              ),

              // Product Spec Detail
              if (p != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: themeColor.withOpacity(0.25)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Wrap(
                        spacing: 6,
                        runSpacing: 4,
                        children: [
                          if (p.brand != null)
                            _buildProductInfoChip('Brand: ${p.brand}', Colors.indigo, isDarkMode),
                          if (p.pricingMatrix?['brand_rm_base'] != null)
                            _buildProductInfoChip('RM/Base: ${p.pricingMatrix!['brand_rm_base']}', Colors.teal, isDarkMode),
                          if (p.category != null)
                            _buildProductInfoChip(p.category!, Colors.deepPurple, isDarkMode),
                        ],
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 14),

              // Field Auto-filled: Kemasan Size & Base Kategori
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Kemasan Size', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
                        const SizedBox(height: 4),
                        DropdownButtonFormField<String>(
                          value: _kOosKemasanOptions.contains(_oosKemasanSizeCtrl.text) ? _oosKemasanSizeCtrl.text : _kOosKemasanOptions[1],
                          items: _kOosKemasanOptions.map((opt) => DropdownMenuItem(value: opt, child: Text(opt, style: const TextStyle(fontSize: 12)))).toList(),
                          onChanged: (val) {
                            if (val != null) setState(() => _oosKemasanSizeCtrl.text = val);
                          },
                          decoration: InputDecoration(
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: elevatedColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Base / Kategori', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
                        const SizedBox(height: 4),
                        DropdownButtonFormField<String>(
                          value: _kOosBaseOptions.contains(_oosBaseWarnaCtrl.text) ? _oosBaseWarnaCtrl.text : _kOosBaseOptions[1],
                          items: _kOosBaseOptions.map((opt) => DropdownMenuItem(value: opt, child: Text(opt, style: const TextStyle(fontSize: 12)))).toList(),
                          onChanged: (val) {
                            if (val != null) setState(() => _oosBaseWarnaCtrl.text = val);
                          },
                          decoration: InputDecoration(
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: elevatedColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // Field: Lama Kondisi OOS & Saran Order (Qty)
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text('Lama OOS (Hari)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
                            const SizedBox(width: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                              decoration: BoxDecoration(color: Colors.blue.withOpacity(0.12), borderRadius: BorderRadius.circular(4)),
                              child: const Text('1 jika baru', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.blue)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        TextField(
                          controller: _oosLamaHariCtrl,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: elevatedColor,
                            suffixText: 'Hari',
                            suffixStyle: TextStyle(fontSize: 11, color: subtitleColor),
                            hintText: '1',
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Saran Order (Qty)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
                        const SizedBox(height: 4),
                        TextField(
                          controller: _oosSaranQtyCtrl,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                            filled: true,
                            fillColor: elevatedColor,
                            suffixText: 'Kemasan',
                            suffixStyle: TextStyle(fontSize: 11, color: subtitleColor),
                            hintText: 'Contoh: 2',
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // Field: Alasan Out of Stock (OOS)
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Alasan Out of Stock (OOS)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: textColor)),
                  const SizedBox(height: 4),
                  DropdownButtonFormField<String>(
                    isExpanded: true,
                    value: _kOosReasons.contains(_oosAlasanCtrl.text) ? _oosAlasanCtrl.text : _kOosReasons.first,
                    items: _kOosReasons.map((r) => DropdownMenuItem(
                      value: r,
                      child: Text(r, style: const TextStyle(fontSize: 11.5), overflow: TextOverflow.ellipsis),
                    )).toList(),
                    onChanged: (val) {
                      if (val != null) setState(() => _oosAlasanCtrl.text = val);
                    },
                    decoration: InputDecoration(
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      filled: true,
                      fillColor: elevatedColor,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Button: Tambah ke Keranjang / Daftar OOS
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _addCurrentOosToCart,
                  icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
                  label: const Text('Tambahkan ke Daftar OOS', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD97706),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 1,
                  ),
                ),
              ),
            ],
          ),
        ),

        // ─── CARD 2: RUJUKAN OOS HARI SEBELUMNYA (JIKA ADA) ─────────────────
        if (_previousOosItems.isNotEmpty) ...[
          Container(
            padding: const EdgeInsets.all(16),
            margin: const EdgeInsets.only(bottom: 14),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.blue.withOpacity(0.3)),
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
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: Colors.blue.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.history_rounded, color: Colors.blue, size: 18),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Rujukan OOS Periode Sebelumnya',
                            style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                          ),
                          Text(
                            'Laporan terakhir: ${_previousOosDate ?? 'Hari Sebelumnya'} (+${_oosDiffDays} hari bertambah)',
                            style: TextStyle(fontSize: 11, color: subtitleColor),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.blue.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        '${_previousOosItems.length} SKU',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.blue),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  'Tekan "+ Lanjutkan OOS" untuk memasukkan produk yang masih kosong dengan hitungan hari otomatis bertambah.',
                  style: TextStyle(fontSize: 11, color: subtitleColor),
                ),
                const SizedBox(height: 10),

                ..._previousOosItems.map((prev) {
                  final pName = prev['product_name']?.toString() ?? 'Dulux Product';
                  final prevLama = int.tryParse(prev['lama_oos_hari']?.toString() ?? '1') ?? 1;
                  final consecutive = prevLama + _oosDiffDays;
                  final inCart = _oosCart.any((c) => c['product_name']?.toString().toLowerCase() == pName.toLowerCase());

                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: inCart ? Colors.green.withOpacity(0.06) : (isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFF8FAFC)),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: inCart ? Colors.green.withOpacity(0.3) : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                      ),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                pName,
                                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 3),
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                    decoration: BoxDecoration(color: Colors.orange.withOpacity(0.12), borderRadius: BorderRadius.circular(4)),
                                    child: Text(
                                      '${prev['kemasan_size'] ?? 'Galon'}',
                                      style: const TextStyle(fontSize: 10, color: Colors.deepOrange, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    'Lama: $consecutive hari ($prevLama + $_oosDiffDays hr)',
                                    style: TextStyle(fontSize: 10.5, color: subtitleColor),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        if (inCart)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                            decoration: BoxDecoration(color: Colors.green.withOpacity(0.12), borderRadius: BorderRadius.circular(8)),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.check_circle_rounded, size: 13, color: Colors.green),
                                SizedBox(width: 4),
                                Text('Masuk', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.green)),
                              ],
                            ),
                          )
                        else
                          ElevatedButton(
                            onPressed: () => _addPreviousOosToCart(prev),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.blue,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              minimumSize: const Size(0, 32),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              elevation: 0,
                            ),
                            child: const Text('+ Lanjutkan OOS', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold)),
                          ),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        ],

        // ─── CARD 3: DAFTAR BARANG OOS SAAT INI (CART) ──────────────────────
        Container(
          padding: const EdgeInsets.all(16),
          margin: const EdgeInsets.only(bottom: 14),
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
                  Icon(Icons.inventory_2_outlined, color: themeColor, size: 18),
                  const SizedBox(width: 8),
                  Text(
                    'Daftar Produk OOS Hari Ini',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: _oosCart.isNotEmpty ? const Color(0xFFE53935).withOpacity(0.12) : Colors.grey.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '${_oosCart.length} SKU OOS',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: _oosCart.isNotEmpty ? const Color(0xFFE53935) : subtitleColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              if (_oosCart.isEmpty)
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: elevatedColor,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: Column(
                      children: [
                        Icon(Icons.checklist_rounded, size: 36, color: subtitleColor),
                        const SizedBox(height: 6),
                        Text('Belum ada produk OOS ditambahkan.', style: TextStyle(fontSize: 12, color: subtitleColor)),
                        const SizedBox(height: 2),
                        Text('Pilih produk di atas atau dari rujukan hari sebelumnya.', style: TextStyle(fontSize: 11, color: subtitleColor)),
                      ],
                    ),
                  ),
                )
              else
                ..._oosCart.asMap().entries.map((entry) {
                  final idx = entry.key;
                  final item = entry.value;

                  return Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: elevatedColor,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE53935).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '#${idx + 1}',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFE53935)),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                item['product_name'] ?? 'Dulux Product',
                                style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                              ),
                              const SizedBox(height: 4),
                              Wrap(
                                spacing: 6,
                                runSpacing: 4,
                                children: [
                                  _buildProductInfoChip('Kemasan: ${item['kemasan_size']}', Colors.indigo, isDarkMode),
                                  _buildProductInfoChip('Base: ${item['base_color']}', Colors.teal, isDarkMode),
                                  _buildProductInfoChip('Lama: ${item['lama_oos_hari']} Hari', Colors.red, isDarkMode),
                                  _buildProductInfoChip('Saran: ${item['saran_qty_order']} Qty', Colors.green, isDarkMode),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Alasan: ${item['alasan_oos']}',
                                style: TextStyle(fontSize: 11, color: subtitleColor, fontStyle: FontStyle.italic),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () {
                            setState(() {
                              _oosCart.removeAt(idx);
                            });
                          },
                          icon: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 20),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                        ),
                      ],
                    ),
                  );
                }),

              const SizedBox(height: 16),

              // Button to step 1
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _oosCart.isEmpty
                      ? null
                      : () => setState(() => _oosStep = 1),
                  icon: const Icon(Icons.arrow_forward_rounded, size: 18),
                  label: Text(
                    'Lanjut ke Review & Konfirmasi (${_oosCart.length} SKU)',
                    style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: themeColor,
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFE2E8F0),
                    disabledForegroundColor: Colors.grey.shade500,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildOosStep1Body(
    Color themeColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
    bool canSubmitReport,
  ) {
    final int totalSku = _oosCart.length;
    final int maxLama = _oosCart.fold<int>(0, (max, itm) {
      final l = (itm['lama_oos_hari'] as num?)?.toInt() ?? 0;
      return l > max ? l : max;
    });
    final int totalSaran = _oosCart.fold<int>(0, (sum, itm) {
      return sum + ((itm['saran_qty_order'] as num?)?.toInt() ?? 0);
    });

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // ─── KPI SUMMARY CARD ───────────────────────────────────────────────
        Container(
          padding: const EdgeInsets.all(16),
          margin: const EdgeInsets.only(bottom: 14),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE53935).withOpacity(0.3)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.fact_check_rounded, color: Color(0xFFE53935), size: 20),
                  const SizedBox(width: 8),
                  Text(
                    'Ringkasan Laporan Out of Stock (OOS)',
                    style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: textColor),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE53935).withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        children: [
                          Text('$totalSku SKU', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFFE53935))),
                          const SizedBox(height: 2),
                          Text('Barang OOS', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                      decoration: BoxDecoration(
                        color: Colors.orange.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        children: [
                          Text('$maxLama Hari', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.deepOrange)),
                          const SizedBox(height: 2),
                          Text('Durasi Terlama', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                      decoration: BoxDecoration(
                        color: Colors.green.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        children: [
                          Text('$totalSaran Qty', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.green)),
                          const SizedBox(height: 2),
                          Text('Saran Order', style: TextStyle(fontSize: 10.5, color: subtitleColor)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // ─── REVIEW DETAIL CARD ─────────────────────────────────────────────
        Container(
          padding: const EdgeInsets.all(16),
          margin: const EdgeInsets.only(bottom: 14),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Rincian Produk yang Dilaporkan OOS', style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor)),
              const SizedBox(height: 10),
              ..._oosCart.asMap().entries.map((entry) {
                final idx = entry.key;
                final item = entry.value;

                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: elevatedColor,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(color: const Color(0xFFE53935).withOpacity(0.12), borderRadius: BorderRadius.circular(8)),
                        child: Text('${idx + 1}', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFFE53935))),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item['product_name'] ?? '', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor)),
                            const SizedBox(height: 2),
                            Text(
                              'Kemasan: ${item['kemasan_size']} • Base: ${item['base_color']} • Durasi: ${item['lama_oos_hari']} Hari • Saran: ${item['saran_qty_order']} Qty',
                              style: TextStyle(fontSize: 11, color: subtitleColor),
                            ),
                            Text('Alasan: ${item['alasan_oos']}', style: TextStyle(fontSize: 10.5, color: subtitleColor, fontStyle: FontStyle.italic)),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }),
            ],
          ),
        ),

        // ─── SUBMISSION ACTIONS ─────────────────────────────────────────────
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: () => setState(() => _oosStep = 0),
                icon: const Icon(Icons.arrow_back_rounded, size: 18),
                label: const Text('Edit / Tambah', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              flex: 2,
              child: canSubmitReport
                  ? ElevatedButton.icon(
                      onPressed: _isSubmitting ? null : _submitOosSale,
                      icon: _isSubmitting
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Icon(Icons.send_rounded, size: 18, color: Colors.white),
                      label: Text(
                        _isSubmitting ? 'Mengirim Laporan...' : 'Kirim Laporan OOS Sekarang',
                        style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFE53935),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 3,
                      ),
                    )
                  : ElevatedButton.icon(
                      onPressed: null,
                      icon: const Icon(Icons.lock_clock_rounded, size: 18),
                      label: const Text('Wajib Check-In Toko', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFE2E8F0),
                        disabledBackgroundColor: isDarkMode ? const Color(0xFF2A2A3C) : const Color(0xFFE2E8F0),
                        disabledForegroundColor: Colors.grey.shade500,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        elevation: 0,
                      ),
                    ),
            ),
          ],
        ),
      ],
    );
  }

  Future<void> _submitOosSale() async {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final token = auth.token;

    if (token == null) return;

    final bool isVisiting = attProvider.isVisiting;
    final bool isCheckedIn = attProvider.isCheckedIn;
    final bool isEditMode = widget.editSubmission != null;
    if (!isVisiting && !isCheckedIn && !isEditMode) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Belum Absensi Kehadiran / Visit'),
        description: const Text('Anda wajib Check-In atau Visit-In terlebih dahulu untuk mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    if (_oosCart.isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Daftar OOS Masih Kosong'),
        description: const Text('Silakan masukkan minimal 1 produk kosong sebelum mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final int maxLamaOos = _oosCart.fold<int>(0, (max, itm) {
        final l = (itm['lama_oos_hari'] as num?)?.toInt() ?? 0;
        return l > max ? l : max;
      });
      final int totalSaranOrder = _oosCart.fold<int>(0, (sum, itm) {
        return sum + ((itm['saran_qty_order'] as num?)?.toInt() ?? 0);
      });

      final firstItem = _oosCart.first;
      final now = DateTime.now();
      final weekNum = (now.difference(DateTime(now.year, 1, 1)).inDays ~/ 7) + 1;

      final Map<String, dynamic> cleanFormValues = {
        'tipe_laporan_oos': 'oos',
        'tanggal_oos': DateFormat('yyyy-MM-dd').format(now),
        'week': weekNum,
        'channel': _oosChannelTokoCtrl.text,
        'channel_toko': _oosChannelTokoCtrl.text,
        'account': 'Non Modern Trade / SSO',
        'produk': firstItem['product_name'] ?? 'Dulux Product',
        'produk_oos': firstItem['product_name'] ?? 'Dulux Product',
        'nama_produk_yang_kosong_oos': firstItem['product_name'] ?? 'Dulux Product',
        'pilih_produk_dulux_yang_mengalami_out_of_stock_oos': firstItem['product_name'] ?? 'Dulux Product',
        'kemasan_size': firstItem['kemasan_size'] ?? 'Galon (2.5 L)',
        'kemasan_size_oos': firstItem['kemasan_size'] ?? 'Galon (2.5 L)',
        'ukuran_kemasan_size': firstItem['kemasan_size'] ?? 'Galon (2.5 L)',
        'base_color': firstItem['base_color'] ?? 'Base A',
        'base_warna_oos': firstItem['base_color'] ?? 'Base A',
        'base_tipe_warna': firstItem['base_color'] ?? 'Base A',
        'warna_ready_mix_oos': firstItem['warna_ready_mix_oos'] ?? 'Bukan Ready Mix (Base Oplos)',
        'lama_oos_hari': maxLamaOos,
        'lama_kondisi_barang_kosong_jumlah_hari': maxLamaOos,
        'saran_qty_order': totalSaranOrder,
        'saran_kuantiti_order_ke_toko_qty_kemasan': totalSaranOrder,
        'alasan_oos': firstItem['alasan_oos'] ?? _kOosReasons.first,
        'penyebab_alasan_out_of_stock_oos': firstItem['alasan_oos'] ?? _kOosReasons.first,
        'oos_items_json': jsonEncode(_oosCart),
      };

      for (final f in widget.template.fields) {
        final fn = f.fieldName.toLowerCase();
        final fKey = f.id.toString();
        if (cleanFormValues.containsKey(fn)) {
          cleanFormValues[fKey] = cleanFormValues[fn];
        }
      }

      Map<String, dynamic> result;
      if (widget.editSubmission != null) {
        result = await repProvider.updateReport(
          token: token,
          submissionId: widget.editSubmission!.id,
          storeName: _selectedStoreName,
          workLocationId: _selectedWorkLocationId,
          address: _selectedLocation?['address'] ?? _address,
          values: cleanFormValues,
          photoFiles: {},
          existingPhotos: {},
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
          photoFiles: {},
          watermarkTexts: {},
        );
      }

      setState(() => _isSubmitting = false);

      if (result['success'] == true && mounted) {
        if (attProvider.isVisiting) {
          attProvider.markVisitReportFilled();
        }
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Laporan OOS Terkirim'),
          description: Text('Total ${_oosCart.length} SKU kosong berhasil dilaporkan.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
        Navigator.of(context).pop(true);
      } else if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Gagal Mengirim Laporan'),
          description: Text(result['message'] ?? 'Terjadi kesalahan sistem.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Terjadi Kesalahan'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }

  Future<void> _submitOosNoOos() async {
    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final token = auth.token;

    if (token == null) return;

    final bool isVisiting = attProvider.isVisiting;
    final bool isCheckedIn = attProvider.isCheckedIn;
    final bool isEditMode = widget.editSubmission != null;
    if (!isVisiting && !isCheckedIn && !isEditMode) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Belum Absensi Kehadiran / Visit'),
        description: const Text('Anda wajib Check-In atau Visit-In terlebih dahulu untuk mengirim laporan.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final now = DateTime.now();
      final weekNum = (now.difference(DateTime(now.year, 1, 1)).inDays ~/ 7) + 1;

      final Map<String, dynamic> cleanFormValues = {
        'tipe_laporan_oos': 'no_oos',
        'tanggal_oos': DateFormat('yyyy-MM-dd').format(now),
        'week': weekNum,
        'channel': _oosChannelTokoCtrl.text,
        'channel_toko': _oosChannelTokoCtrl.text,
        'account': 'Non Modern Trade / SSO',
        'produk': 'No OOS (Stok Lengkap)',
        'produk_oos': 'No OOS (Stok Lengkap)',
        'nama_produk_yang_kosong_oos': 'No OOS (Stok Lengkap)',
        'pilih_produk_dulux_yang_mengalami_out_of_stock_oos': 'No OOS',
        'kemasan_size': '-',
        'kemasan_size_oos': '-',
        'ukuran_kemasan_size': '-',
        'base_color': '-',
        'base_warna_oos': '-',
        'base_tipe_warna': '-',
        'warna_ready_mix_oos': '-',
        'lama_oos_hari': 0,
        'lama_kondisi_barang_kosong_jumlah_hari': 0,
        'saran_qty_order': 0,
        'saran_kuantiti_order_ke_toko_qty_kemasan': 0,
        'alasan_oos': '7. No OOS / Stok Lengkap',
        'penyebab_alasan_out_of_stock_oos': '7. No OOS / Stok Lengkap',
        'oos_items_json': '[]',
      };

      for (final f in widget.template.fields) {
        final fn = f.fieldName.toLowerCase();
        final fKey = f.id.toString();
        if (cleanFormValues.containsKey(fn)) {
          cleanFormValues[fKey] = cleanFormValues[fn];
        }
      }

      Map<String, dynamic> result;
      if (widget.editSubmission != null) {
        result = await repProvider.updateReport(
          token: token,
          submissionId: widget.editSubmission!.id,
          storeName: _selectedStoreName,
          workLocationId: _selectedWorkLocationId,
          address: _selectedLocation?['address'] ?? _address,
          values: cleanFormValues,
          photoFiles: {},
          existingPhotos: {},
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
          photoFiles: {},
          watermarkTexts: {},
        );
      }

      setState(() => _isSubmitting = false);

      if (result['success'] == true && mounted) {
        if (attProvider.isVisiting) {
          attProvider.markVisitReportFilled();
        }
        toastification.show(
          context: context,
          type: ToastificationType.success,
          title: const Text('Laporan Stok Lengkap Terkirim'),
          description: const Text('Laporan No OOS (Stok Lengkap) berhasil dikirim.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
        Navigator.of(context).pop(true);
      } else if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Gagal Mengirim Laporan'),
          description: Text(result['message'] ?? 'Terjadi kesalahan sistem.'),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    } catch (e) {
      setState(() => _isSubmitting = false);
      if (mounted) {
        toastification.show(
          context: context,
          type: ToastificationType.error,
          title: const Text('Terjadi Kesalahan'),
          description: Text(e.toString()),
          autoCloseDuration: const Duration(seconds: 4),
        );
      }
    }
  }
}

