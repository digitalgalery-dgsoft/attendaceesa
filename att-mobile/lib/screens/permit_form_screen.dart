import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:toastification/toastification.dart';
import '../providers/permit_provider.dart';
import '../providers/auth_provider.dart';
import '../utils/image_utils.dart';

class PermitFormScreen extends StatefulWidget {
  const PermitFormScreen({super.key});

  @override
  State<PermitFormScreen> createState() => _PermitFormScreenState();
}

class _PermitFormScreenState extends State<PermitFormScreen> {
  final _formKey = GlobalKey<FormState>();
  
  String? _selectedType;
  String? _selectedSubType;         // 'Cuti Tahunan' or 'Cuti Peraturan'
  String? _selectedCutiPeraturanKey; // key from server e.g. 'cuti_menikah'
  
  DateTime? _startDate;
  DateTime? _endDate;
  
  final _notesController = TextEditingController();
  
  XFile? _imageFile;
  final ImagePicker _picker = ImagePicker();

  final List<String> _permitTypes = [
    'Sakit',
    'Izin',
    'Cuti',
    'Off',
    'Store Closed',
    'Izin Khusus'
  ];

  final List<String> _cutiTypes = [
    'Cuti Tahunan',
    'Cuti Peraturan'
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<PermitProvider>(context, listen: false).fetchCutiPeraturanTypes();
    });
  }

  int? get _maxDaysForCutiPeraturan {
    if (_selectedCutiPeraturanKey == null) return null;
    final provider = Provider.of<PermitProvider>(context, listen: false);
    try {
      final match = provider.cutiPeraturanTypes.firstWhere(
        (t) => t['key'] == _selectedCutiPeraturanKey,
      );
      return match['max_days'] as int?;
    } catch (_) {
      return null;
    }
  }

  Future<void> _selectDate(BuildContext context, bool isStart) async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    
    // For Cuti Tahunan, minimum is H+14
    bool isCutiTahunan = _selectedType == 'Cuti' && _selectedSubType == 'Cuti Tahunan';
    final minDate = isCutiTahunan ? today.add(const Duration(days: 14)) : today;
    
    final initialDate = isStart ? (_startDate ?? minDate) : (_endDate ?? _startDate ?? minDate);
    final firstDate = isStart ? minDate : (_startDate ?? minDate);

    // For Cuti Peraturan with max days, also cap end date
    DateTime lastDate = DateTime(2030);
    if (!isStart && _startDate != null && _selectedSubType == 'Cuti Peraturan') {
      final maxDays = _maxDaysForCutiPeraturan;
      if (maxDays != null) {
        lastDate = _startDate!.add(Duration(days: maxDays - 1));
      }
    }
    
    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate.isBefore(firstDate) ? firstDate : initialDate,
      firstDate: firstDate,
      lastDate: lastDate,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA),
            ),
          ),
          child: child!,
        );
      },
    );
    
    if (picked != null) {
      setState(() {
        if (isStart) {
          _startDate = picked;
          if (_endDate != null && _endDate!.isBefore(_startDate!)) {
            _endDate = _startDate;
          }
          // Auto-enforce max days for cuti peraturan
          if (_selectedSubType == 'Cuti Peraturan' && _endDate != null) {
            final maxDays = _maxDaysForCutiPeraturan;
            if (maxDays != null) {
              final maxEnd = _startDate!.add(Duration(days: maxDays - 1));
              if (_endDate!.isAfter(maxEnd)) _endDate = maxEnd;
            }
          }
        } else {
          _endDate = picked;
        }
      });
    }
  }

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 100,
    );
    if (image != null) {
      if (kIsWeb) {
        setState(() {
          _imageFile = image;
        });
        return;
      }
      
      // Compress and convert to WebP
      final compressedFile = await ImageUtils.compressAndGetWebP(File(image.path));
      
      setState(() {
        _imageFile = compressedFile != null ? XFile(compressedFile.path) : image;
      });
    }
  }

  void _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_startDate == null || _endDate == null) {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Error'),
        description: const Text('Pilih Tanggal Mulai dan Tanggal Akhir!'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    // Validate cuti peraturan type selected
    if (_selectedType == 'Cuti' &&
        _selectedSubType == 'Cuti Peraturan' &&
        _selectedCutiPeraturanKey == null) {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Error'),
        description: const Text('Pilih Jenis Cuti Peraturan!'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }
    
    final provider = Provider.of<PermitProvider>(context, listen: false);
    
    String typeValue = _selectedType!.toLowerCase().replaceAll(' ', '_');
    String? subTypeValue = _selectedSubType?.toLowerCase().replaceAll(' ', '_');
    
    final result = await provider.submitPermit(
      type: typeValue,
      subType: typeValue == 'cuti' ? subTypeValue : null,
      cutiPeraturanType: (typeValue == 'cuti' && subTypeValue == 'cuti_peraturan')
          ? _selectedCutiPeraturanKey
          : null,
      startDate: DateFormat('yyyy-MM-dd').format(_startDate!),
      endDate: DateFormat('yyyy-MM-dd').format(_endDate!),
      notes: _notesController.text,
      imagePath: _imageFile?.path,
      isWeb: kIsWeb,
    );
    
    if (!mounted) return;
    
    if (result['success'] == true) {
      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Berhasil'),
        description: Text(result['message']),
        autoCloseDuration: const Duration(seconds: 3),
      );
      Navigator.pop(context);
    } else {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Gagal'),
        description: Text(result['message']),
        autoCloseDuration: const Duration(seconds: 3),
      );
    }
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
      hintStyle: TextStyle(color: subtitleColor, fontSize: 13),
    );

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(
          'Permit',
          style: TextStyle(
            color: textColor,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Consumer<PermitProvider>(
        builder: (context, provider, child) {
          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Tipe Izin
                Text('Tipe Izin', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  dropdownColor: cardColor,
                  style: TextStyle(color: textColor),
                  decoration: inputDecoration.copyWith(hintText: 'Tipe Izin'),
                  value: _selectedType,
                  items: _permitTypes.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                  onChanged: (val) {
                    setState(() {
                      _selectedType = val;
                      if (val != 'Cuti') {
                        _selectedSubType = null;
                        _selectedCutiPeraturanKey = null;
                      }
                    });
                  },
                  validator: (val) => val == null ? 'Pilih tipe izin' : null,
                ),
                
                const SizedBox(height: 16),
                
                // Jenis Cuti (if Cuti)
                if (_selectedType == 'Cuti') ...[
                  Text('Jenis Cuti', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    dropdownColor: cardColor,
                    style: TextStyle(color: textColor),
                    decoration: inputDecoration.copyWith(hintText: 'Pilih Jenis Cuti'),
                    value: _selectedSubType,
                    items: _cutiTypes.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                    onChanged: (val) => setState(() {
                      _selectedSubType = val;
                      _selectedCutiPeraturanKey = null;
                    }),
                    validator: (val) => val == null ? 'Pilih jenis cuti' : null,
                  ),
                  const SizedBox(height: 16),
                ],

                // Cuti Peraturan sub-type picker
                if (_selectedType == 'Cuti' && _selectedSubType == 'Cuti Peraturan') ...[
                  Text('Jenis Cuti Peraturan', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
                  const SizedBox(height: 4),
                  Text(
                    'Pilih sesuai kejadian yang dialami',
                    style: TextStyle(color: subtitleColor, fontSize: 11),
                  ),
                  const SizedBox(height: 8),
                  if (provider.cutiPeraturanTypes.isEmpty)
                    Center(child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: primaryColor)))
                  else
                    ...provider.cutiPeraturanTypes.map((t) {
                      final key      = t['key'] as String;
                      final label    = t['label'] as String;
                      final maxDays  = t['max_days'] as int;
                      final selected = _selectedCutiPeraturanKey == key;

                      return GestureDetector(
                        onTap: () => setState(() {
                          _selectedCutiPeraturanKey = key;
                          // Auto-cap end date
                          if (_startDate != null && _endDate != null) {
                            final maxEnd = _startDate!.add(Duration(days: maxDays - 1));
                            if (_endDate!.isAfter(maxEnd)) _endDate = maxEnd;
                          }
                        }),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          decoration: BoxDecoration(
                            color: selected ? primaryColor.withOpacity(0.1) : (isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: selected ? primaryColor : (isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                              width: selected ? 1.5 : 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                selected ? Icons.radio_button_checked : Icons.radio_button_unchecked,
                                color: selected ? primaryColor : subtitleColor,
                                size: 18,
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  label,
                                  style: TextStyle(
                                    color: selected ? primaryColor : textColor,
                                    fontSize: 13,
                                    fontWeight: selected ? FontWeight.bold : FontWeight.normal,
                                  ),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: selected ? primaryColor : Colors.grey.shade200,
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  'Maks. $maxDays Hari',
                                  style: TextStyle(
                                    color: selected ? Colors.white : Colors.grey.shade700,
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }).toList(),
                  const SizedBox(height: 8),
                ],
                
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Tanggal Mulai', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
                          const SizedBox(height: 8),
                          InkWell(
                            onTap: () => _selectDate(context, true),
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                              decoration: BoxDecoration(
                                color: cardColor,
                                border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    _startDate != null ? DateFormat('dd MMM yyyy').format(_startDate!) : 'Tanggal Mulai',
                                    style: TextStyle(color: _startDate != null ? textColor : subtitleColor, fontSize: 13),
                                  ),
                                  Icon(Icons.calendar_today, color: subtitleColor, size: 18),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Tanggal Akhir', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
                          const SizedBox(height: 8),
                          InkWell(
                            onTap: () => _selectDate(context, false),
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                              decoration: BoxDecoration(
                                color: cardColor,
                                border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    _endDate != null ? DateFormat('dd MMM yyyy').format(_endDate!) : 'Tanggal Akhir',
                                    style: TextStyle(color: _endDate != null ? textColor : subtitleColor, fontSize: 13),
                                  ),
                                  Icon(Icons.calendar_today, color: subtitleColor, size: 18),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                // Hint for max days when cuti peraturan selected
                if (_selectedSubType == 'Cuti Peraturan' && _selectedCutiPeraturanKey != null && _maxDaysForCutiPeraturan != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Text(
                      'Maksimal ${_maxDaysForCutiPeraturan} hari kerja untuk jenis cuti ini.',
                      style: TextStyle(color: primaryColor, fontSize: 11, fontWeight: FontWeight.w600),
                    ),
                  ),
                
                const SizedBox(height: 24),
                
                Text('Foto', style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14)),
                const SizedBox(height: 8),
                InkWell(
                  onTap: _pickImage,
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    height: 150,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
                      border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300, style: BorderStyle.solid),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: _imageFile == null
                        ? Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.camera_alt, size: 40, color: subtitleColor),
                              const SizedBox(height: 8),
                              Text('Ambil Foto', style: TextStyle(color: subtitleColor, fontSize: 13)),
                            ],
                          )
                        : ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: kIsWeb
                                ? Image.network(_imageFile!.path, fit: BoxFit.cover)
                                : Image.file(File(_imageFile!.path), fit: BoxFit.cover),
                          ),
                  ),
                ),
                
                const SizedBox(height: 24),
                
                TextFormField(
                  controller: _notesController,
                  style: TextStyle(color: textColor),
                  decoration: inputDecoration.copyWith(
                    prefixIcon: Icon(Icons.edit, color: subtitleColor, size: 20),
                    labelText: 'Alasan',
                  ),
                  maxLines: 3,
                ),
                
                const SizedBox(height: 32),
                
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      )
                    ),
                    onPressed: provider.isLoading ? null : _submit,
                    child: provider.isLoading 
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Simpan Pengajuan', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(height: 24),
              ],
            ),
          );
        }
      ),
    );
  }
}
