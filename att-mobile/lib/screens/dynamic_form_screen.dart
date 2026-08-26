import 'dart:io';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/models/report_template_model.dart';
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

  // Area & Lokasi Terdaftar & Kalkulasi Radius
  String? _selectedArea;
  Map<String, dynamic>? _selectedLocation;
  int? _selectedWorkLocationId;
  String _selectedStoreName = '';
  double? _calculatedDistance;
  bool _isWithinRadius = false;
  double _allowedRadiusMeter = 100.0;

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

      final locations = repProvider.stores.isNotEmpty ? repProvider.stores : attProvider.workLocations;
      
      // Auto-assign Area Default sesuai Karyawan atau Store list
      if (_selectedArea == null && locations.isNotEmpty) {
        final availableAreas = locations
            .map((loc) => loc['area']?.toString().trim() ?? '')
            .where((a) => a.isNotEmpty)
            .toSet()
            .toList();

        final employeeBranch = auth.employeeData?['branch']?['name']?.toString() ??
            auth.employeeData?['area']?.toString() ??
            repProvider.defaultArea;

        if (employeeBranch != null && availableAreas.any((a) => a.toLowerCase() == employeeBranch.toLowerCase())) {
          _selectedArea = availableAreas.firstWhere((a) => a.toLowerCase() == employeeBranch.toLowerCase());
        } else if (repProvider.defaultArea != null && availableAreas.any((a) => a.toLowerCase() == repProvider.defaultArea!.toLowerCase())) {
          _selectedArea = availableAreas.firstWhere((a) => a.toLowerCase() == repProvider.defaultArea!.toLowerCase());
        } else if (availableAreas.isNotEmpty) {
          _selectedArea = availableAreas.first;
        }
      }

      _matchInitialLocation(locations);
    });
  }

  void _matchInitialLocation(List<dynamic> locations) {
    if (locations.isEmpty) return;

    Map<String, dynamic>? matched;
    if (_selectedWorkLocationId != null) {
      matched = locations.cast<Map<String, dynamic>?>().firstWhere(
            (l) => l?['id'] == _selectedWorkLocationId || l?['id'].toString() == _selectedWorkLocationId.toString(),
            orElse: () => null,
          );
    } else if (_selectedStoreName.isNotEmpty) {
      matched = locations.cast<Map<String, dynamic>?>().firstWhere(
            (l) => l?['name']?.toString().toLowerCase() == _selectedStoreName.toLowerCase(),
            orElse: () => null,
          );
    } else {
      // Cari store yang ada di area terpilih
      final areaLocs = (_selectedArea != null && _selectedArea != 'Semua Area')
          ? locations.where((l) => l['area']?.toString().trim().toLowerCase() == _selectedArea!.toLowerCase()).toList()
          : locations;

      if (areaLocs.isNotEmpty) {
        matched = areaLocs.first as Map<String, dynamic>?;
      } else {
        matched = locations.first as Map<String, dynamic>?;
      }
    }

    if (matched != null) {
      final matchedArea = matched['area']?.toString().trim();
      if (matchedArea != null && matchedArea.isNotEmpty && (_selectedArea == null || _selectedArea == 'Semua Area')) {
        _selectedArea = matchedArea;
      }
      _selectLocation(matched);
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
      setState(() {
        _photoFiles[field.id.toString()] = result.file;
        _watermarkTexts[field.id.toString()] = result.watermarkText;
        _formValues[field.id.toString()] = result.file.path;
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

  // Modal Dialog Search & Select Area / Cabang
  void _showAreaPickerModal(List<String> areas, Color themeColor, bool isDarkMode) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      backgroundColor: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
      builder: (context) {
        String searchQuery = '';
        return StatefulBuilder(
          builder: (context, setModalState) {
            final filteredAreas = areas.where((a) {
              final q = searchQuery.trim().toLowerCase();
              if (q.isEmpty) return true;
              return a.toLowerCase().contains(q);
            }).toList();

            return Container(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
              height: MediaQuery.of(context).size.height * 0.65,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade400,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Pilih Area / Cabang',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close, size: 20),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    decoration: InputDecoration(
                      hintText: 'Cari nama area / kota...',
                      prefixIcon: const Icon(Icons.search, size: 20),
                      filled: true,
                      fillColor: isDarkMode ? const Color(0xFF121212) : const Color(0xFFF1F5F9),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                    ),
                    onChanged: (val) {
                      setModalState(() {
                        searchQuery = val;
                      });
                    },
                  ),
                  const SizedBox(height: 14),
                  Expanded(
                    child: filteredAreas.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.map_outlined, size: 48, color: Colors.grey.shade400),
                                const SizedBox(height: 10),
                                Text(
                                  'Area tidak ditemukan',
                                  style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
                                ),
                              ],
                            ),
                          )
                        : ListView.separated(
                            itemCount: filteredAreas.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, idx) {
                              final areaName = filteredAreas[idx];
                              final isSelected = areaName == _selectedArea;

                              return InkWell(
                                onTap: () {
                                  setState(() {
                                    _selectedArea = areaName;
                                    // Reset store jika toko terpilih sebelumnya berbeda area
                                    if (_selectedLocation != null) {
                                      final storeArea = _selectedLocation!['area']?.toString().trim();
                                      if (storeArea != null && storeArea.isNotEmpty && areaName != 'Semua Area' && storeArea.toLowerCase() != areaName.toLowerCase()) {
                                        _selectedLocation = null;
                                        _selectedWorkLocationId = null;
                                        _selectedStoreName = '';
                                        _calculatedDistance = null;
                                      }
                                    }
                                  });
                                  Navigator.pop(context);
                                },
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 13),
                                  decoration: BoxDecoration(
                                    color: isSelected ? themeColor.withOpacity(0.08) : Colors.transparent,
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(8),
                                        decoration: BoxDecoration(
                                          color: isSelected ? themeColor : themeColor.withOpacity(0.12),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Icon(
                                          Icons.location_city_rounded,
                                          color: isSelected ? Colors.white : themeColor,
                                          size: 18,
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Text(
                                          areaName,
                                          style: TextStyle(
                                            fontSize: 14,
                                            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                            color: isSelected ? themeColor : null,
                                          ),
                                        ),
                                      ),
                                      if (isSelected)
                                        Icon(Icons.check_circle_rounded, color: themeColor, size: 20),
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

  // Modal Dialog Search & Select Lokasi Terdaftar (Hanya dalam Radius 1.000m / 1 km)
  void _showStorePickerModal(List<dynamic> locations, Color themeColor, bool isDarkMode) {
    final locale = Provider.of<LocaleProvider>(context, listen: false);
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      backgroundColor: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
      builder: (context) {
        String searchQuery = '';
        return StatefulBuilder(
          builder: (context, setModalState) {
            final filtered = locations.where((loc) {
              final name = loc['name']?.toString().toLowerCase() ?? '';
              final addr = loc['address']?.toString().toLowerCase() ?? '';
              final area = loc['area']?.toString().toLowerCase() ?? '';
              final q = searchQuery.trim().toLowerCase();

              // Filter ketat: Hanya toko dalam radius 1.000 meter (1 km) dari posisi GPS user
              if (_latitude != null && _longitude != null) {
                final locLat = double.tryParse(loc['latitude']?.toString() ?? '');
                final locLng = double.tryParse(loc['longitude']?.toString() ?? '');
                if (locLat != null && locLng != null) {
                  final dist = Geolocator.distanceBetween(_latitude!, _longitude!, locLat, locLng);
                  if (dist > 1000.0) {
                    return false; // Abaikan toko di luar 1000 meter
                  }
                } else {
                  return false;
                }
              }

              if (q.isEmpty) return true;
              return name.contains(q) || addr.contains(q) || area.contains(q);
            }).toList();

            // Urutkan toko terdekat di bagian paling atas
            filtered.sort((a, b) {
              final locLatA = double.tryParse(a['latitude']?.toString() ?? '');
              final locLngA = double.tryParse(a['longitude']?.toString() ?? '');
              final locLatB = double.tryParse(b['latitude']?.toString() ?? '');
              final locLngB = double.tryParse(b['longitude']?.toString() ?? '');
              if (_latitude != null && _longitude != null && locLatA != null && locLngA != null && locLatB != null && locLngB != null) {
                final distA = Geolocator.distanceBetween(_latitude!, _longitude!, locLatA, locLngA);
                final distB = Geolocator.distanceBetween(_latitude!, _longitude!, locLatB, locLngB);
                return distA.compareTo(distB);
              }
              return (a['name']?.toString() ?? '').compareTo(b['name']?.toString() ?? '');
            });

            return Container(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
              height: MediaQuery.of(context).size.height * 0.75,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade400,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          _selectedArea != null ? 'Pilih Toko - Area $_selectedArea (Radius ≤ 1km)' : 'Pilih Toko Terdekat (Radius ≤ 1km)',
                          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close, size: 20),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    decoration: InputDecoration(
                      hintText: locale.tr('search_store_hint'),
                      prefixIcon: const Icon(Icons.search, size: 20),
                      filled: true,
                      fillColor: isDarkMode ? const Color(0xFF121212) : const Color(0xFFF1F5F9),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                    ),
                    onChanged: (val) {
                      setModalState(() {
                        searchQuery = val;
                      });
                    },
                  ),
                  const SizedBox(height: 14),
                  Expanded(
                    child: filtered.isEmpty
                        ? Center(
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 24),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.location_off_rounded, size: 52, color: Colors.grey.shade400),
                                  const SizedBox(height: 12),
                                  Text(
                                    'Tidak Ada Toko dalam Radius 1.000m',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: isDarkMode ? Colors.white : const Color(0xFF0E1830)),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    'Hanya menampilkan toko terdaftar dalam jarak maksimal 1 km (1.000 meter) dari titik koordinat GPS Anda saat ini.',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(fontSize: 12.5, color: isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893)),
                                  ),
                                ],
                              ),
                            ),
                          )
                        : ListView.separated(
                            itemCount: filtered.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, idx) {
                              final loc = filtered[idx] as Map<String, dynamic>;
                              final name = loc['name']?.toString() ?? 'Toko';
                              final address = loc['address']?.toString() ?? '';
                              final locLat = double.tryParse(loc['latitude']?.toString() ?? '');
                              final locLng = double.tryParse(loc['longitude']?.toString() ?? '');
                              final isSelected = loc['id'] == _selectedWorkLocationId || loc['id'].toString() == _selectedWorkLocationId?.toString();

                              double? dist;
                              if (_latitude != null && _longitude != null && locLat != null && locLng != null) {
                                dist = Geolocator.distanceBetween(_latitude!, _longitude!, locLat, locLng);
                              }

                              return InkWell(
                                onTap: () {
                                  _selectLocation(loc);
                                  Navigator.pop(context);
                                },
                                borderRadius: BorderRadius.circular(12),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                                  decoration: BoxDecoration(
                                    color: isSelected ? themeColor.withOpacity(0.08) : Colors.transparent,
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(10),
                                        decoration: BoxDecoration(
                                          color: isSelected ? themeColor : themeColor.withOpacity(0.12),
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: Icon(
                                          Icons.storefront_rounded,
                                          color: isSelected ? Colors.white : themeColor,
                                          size: 22,
                                        ),
                                      ),
                                      const SizedBox(width: 14),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Row(
                                              children: [
                                                Expanded(
                                                  child: Text(
                                                    name,
                                                    style: TextStyle(
                                                      fontSize: 14,
                                                      fontWeight: FontWeight.bold,
                                                      color: isSelected ? themeColor : null,
                                                    ),
                                                  ),
                                                ),
                                                if (loc['is_today_itinerary'] == true) ...[
                                                  const SizedBox(width: 6),
                                                  Container(
                                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                    decoration: BoxDecoration(
                                                      color: themeColor.withOpacity(0.12),
                                                      borderRadius: BorderRadius.circular(4),
                                                      border: Border.all(color: themeColor.withOpacity(0.3), width: 0.8),
                                                    ),
                                                    child: Text(
                                                      'Jadwal Hari Ini',
                                                      style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: themeColor),
                                                    ),
                                                  ),
                                                ],
                                              ],
                                            ),
                                            if (address.isNotEmpty || (loc['area'] != null && loc['area'].toString().isNotEmpty)) ...[
                                              const SizedBox(height: 3),
                                              Text(
                                                [
                                                  if (loc['area'] != null && loc['area'].toString().isNotEmpty) loc['area'].toString(),
                                                  if (address.isNotEmpty) address,
                                                ].join(' • '),
                                                style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ],
                                          ],
                                        ),
                                      ),
                                      if (dist != null) ...[
                                        const SizedBox(width: 8),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: (dist <= (_allowedRadiusMeter))
                                                ? const Color(0xFFE2F6EE)
                                                : const Color(0xFFFFF3E0),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: Text(
                                            dist >= 1000 ? '${(dist / 1000).toStringAsFixed(1)} km' : '${dist.round()} m',
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.bold,
                                              color: (dist <= (_allowedRadiusMeter))
                                                  ? const Color(0xFF149A6E)
                                                  : const Color(0xFFD98A2B),
                                            ),
                                          ),
                                        ),
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

  // Submit Form
  Future<void> _submitForm() async {
    final locale = Provider.of<LocaleProvider>(context, listen: false);

    if (_selectedWorkLocationId == null || _selectedStoreName.isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: Text(locale.tr('choose_registered_store')),
        description: const Text('Silakan pilih salah satu toko / lokasi yang terdaftar.'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
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
      storeName: _selectedStoreName,
      workLocationId: _selectedWorkLocationId,
      itineraryItemId: widget.itineraryItemId,
      latitude: _latitude,
      longitude: _longitude,
      address: _selectedLocation?['address'] ?? _address,
      isWithinRadius: _isWithinRadius,
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
    final attProvider = Provider.of<AttendanceProvider>(context);
    final repProvider = Provider.of<DynamicReportingProvider>(context);
    final locale = Provider.of<LocaleProvider>(context);
    final availableLocations = repProvider.stores.isNotEmpty ? repProvider.stores : attProvider.workLocations;

    // List of unique areas
    final List<String> allAreas = availableLocations
        .map((loc) => loc['area']?.toString().trim() ?? '')
        .where((a) => a.isNotEmpty)
        .toSet()
        .toList();
    allAreas.sort();
    if (!allAreas.contains('Semua Area') && allAreas.length > 1) {
      allAreas.insert(0, 'Semua Area');
    }

    // Filter stores based on selected area
    final List<dynamic> storesInSelectedArea = (_selectedArea != null && _selectedArea != 'Semua Area')
        ? availableLocations.where((loc) => loc['area']?.toString().trim().toLowerCase() == _selectedArea!.toLowerCase()).toList()
        : availableLocations;

    final int nearbyCountInSelectedArea = storesInSelectedArea.where((loc) {
      if (_latitude != null && _longitude != null) {
        final locLat = double.tryParse(loc['latitude']?.toString() ?? '');
        final locLng = double.tryParse(loc['longitude']?.toString() ?? '');
        if (locLat != null && locLng != null) {
          final dist = Geolocator.distanceBetween(_latitude!, _longitude!, locLat, locLng);
          return dist <= 1000.0;
        }
        return false;
      }
      return true;
    }).length;

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
            // ─── Header: Pemilih Area & Toko Terdaftar & Perhitungan Radius Otomatis ───
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
                  Row(
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
                              locale.tr('select_store_location'),
                              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              widget.template.code,
                              style: TextStyle(fontSize: 10.5, color: themeColor, fontFamily: 'monospace', fontWeight: FontWeight.w600),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // ─── 1. Selector Pilihan Area / Cabang ───
                  Row(
                    children: [
                      Text(
                        'Area / Cabang',
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: subtitleColor),
                      ),
                      if (_selectedArea != null) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                          decoration: BoxDecoration(
                            color: themeColor.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            '$nearbyCountInSelectedArea toko (≤ 1km)',
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: themeColor),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 6),
                  InkWell(
                    onTap: () => _showAreaPickerModal(allAreas, themeColor, isDarkMode),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: elevatedColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300,
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.map_outlined, color: themeColor, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _selectedArea ?? 'Pilih Area',
                              style: TextStyle(
                                fontSize: 13.5,
                                fontWeight: FontWeight.bold,
                                color: _selectedArea != null ? textColor : subtitleColor,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: themeColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: [
                                Text(
                                  'Ganti Area',
                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: themeColor),
                                ),
                                Icon(Icons.arrow_drop_down, color: themeColor, size: 16),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // ─── 2. Selector Toko / Lokasi Kerja ───
                  Text(
                    'Toko / Lokasi Terdaftar',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: subtitleColor),
                  ),
                  const SizedBox(height: 6),
                  InkWell(
                    onTap: () => _showStorePickerModal(storesInSelectedArea, themeColor, isDarkMode),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                      decoration: BoxDecoration(
                        color: elevatedColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300,
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.location_on_rounded, color: themeColor, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _selectedStoreName.isNotEmpty
                                      ? _selectedStoreName
                                      : (_selectedArea != null ? 'Pilih Toko di Area $_selectedArea' : locale.tr('choose_registered_store')),
                                  style: TextStyle(
                                    fontSize: 13.5,
                                    fontWeight: _selectedStoreName.isNotEmpty ? FontWeight.bold : FontWeight.w500,
                                    color: _selectedStoreName.isNotEmpty ? textColor : subtitleColor,
                                  ),
                                ),
                                if (_selectedLocation?['address'] != null && _selectedLocation!['address'].toString().isNotEmpty) ...[
                                  const SizedBox(height: 2),
                                  Text(
                                    _selectedLocation!['address'].toString(),
                                    style: TextStyle(fontSize: 11, color: subtitleColor),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: themeColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: [
                                Text(
                                  _selectedStoreName.isNotEmpty ? 'Ganti Toko' : 'Pilih Toko',
                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: themeColor),
                                ),
                                Icon(Icons.arrow_drop_down, color: themeColor, size: 16),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  // ─── Dynamic Radius & Geofence Indicator ───
                  if (_selectedLocation != null) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
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
                            size: 20,
                            color: _isWithinRadius ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _isWithinRadius ? locale.tr('within_radius') : locale.tr('outside_radius'),
                                  style: TextStyle(
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.bold,
                                    color: _isWithinRadius ? const Color(0xFF149A6E) : const Color(0xFFD98A2B),
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  _calculatedDistance != null
                                      ? locale.tr('radius_info', params: {
                                          'distance': _calculatedDistance! >= 1000
                                              ? '${(_calculatedDistance! / 1000).toStringAsFixed(1)} km'
                                              : '${_calculatedDistance!.round()} m',
                                          'allowed': '${_allowedRadiusMeter.round()} m',
                                        })
                                      : 'Menghitung jarak...',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: _isWithinRadius
                                        ? const Color(0xFF0F7652)
                                        : (isDarkMode ? Colors.orange.shade200 : const Color(0xFFB46A14)),
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],

                  const Divider(height: 22),

                  // GPS Live Status
                  Row(
                    children: [
                      Icon(
                        Icons.my_location_rounded,
                        size: 15,
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
                          width: 14,
                          height: 14,
                          child: CircularProgressIndicator(strokeWidth: 2),
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
                  locale,
                )),

            const SizedBox(height: 16),

            // Submit Button
            ElevatedButton.icon(
              onPressed: _isSubmitting ? null : _submitForm,
              icon: _isSubmitting
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.send_rounded, color: Colors.white, size: 18),
              label: Text(
                _isSubmitting ? locale.tr('btn_submitting_report') : locale.tr('btn_submit_report'),
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
          validator: (v) => field.isRequired && v == null ? locale.tr('required_field') : null,
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
                child: Image.file(photoFile, height: 220, width: double.infinity, fit: BoxFit.cover),
              ),
              const SizedBox(height: 10),
            ],
            OutlinedButton.icon(
              onPressed: () => _takeWatermarkedPhoto(field),
              icon: const Icon(Icons.camera_alt_rounded, size: 18),
              label: Text(photoFile == null ? locale.tr('take_watermark_photo') : locale.tr('retake_photo')),
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
              label: Text(sigFile == null ? locale.tr('sign_pad_button') : locale.tr('resign_button')),
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
