import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/itinerary_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/screens/attendance_location_screen.dart';
import 'package:att_mobile/screens/request_location_screen.dart';

class AddItineraryScreen extends StatefulWidget {
  final DateTime initialDate;

  const AddItineraryScreen({
    super.key,
    required this.initialDate,
  });

  @override
  State<AddItineraryScreen> createState() => _AddItineraryScreenState();
}

class _AddItineraryScreenState extends State<AddItineraryScreen> {
  String? _selectedArea;
  int? _selectedWorkLocationId;
  int? _selectedPrincipalId;
  String? _selectedVisitType;
  String? _selectedMeetingType;
  final TextEditingController _agendaController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  void _fetchData() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      Provider.of<ItineraryProvider>(context, listen: false).fetchWorkLocations(authProvider);
      Provider.of<ItineraryProvider>(context, listen: false).fetchPrincipals(authProvider);
    });
  }

  Future<void> _submit(String type) async {
    if (_selectedWorkLocationId == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Harap pilih lokasi kerja terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }
    
    if (_selectedPrincipalId == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Harap pilih Brand / Prinsiple terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    final attProvider = Provider.of<AttendanceProvider>(context, listen: false);

    if (type == 'now' && !attProvider.isCheckedIn) {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Gagal'),
        description: const Text('Anda harus Check-in terlebih dahulu sebelum melakukan Visit-in.'),
        autoCloseDuration: const Duration(seconds: 4),
      );
      return;
    }

    DateTime targetDate = DateTime.now();

    if (_selectedVisitType == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Harap pilih Type Visit terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (_selectedMeetingType == null) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Harap pilih Type Meeting terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (_agendaController.text.trim().isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Harap isi Agenda terlebih dahulu'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    if (type == 'scheduled') {
      final picked = await showDatePicker(
        context: context,
        initialDate: targetDate,
        firstDate: DateTime.now(),
        lastDate: DateTime.now().add(const Duration(days: 30)),
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
      if (picked == null) return;
      targetDate = picked;
    }

    setState(() {
      _isSubmitting = true;
    });

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final itineraryProvider = Provider.of<ItineraryProvider>(context, listen: false);
    
    final dateStr = DateFormat('yyyy-MM-dd').format(targetDate);
    
    final success = await itineraryProvider.createItinerary(
      authProvider,
      dateStr,
      [{
        'work_location_id': _selectedWorkLocationId,
        'principal_id': _selectedPrincipalId,
        'notes': type == 'now' ? 'Visit Now' : 'Scheduled Visit',
        'visit_type': _selectedVisitType,
        'meeting_type': _selectedMeetingType,
        'agenda': _agendaController.text.trim(),
      }],
    );

    setState(() {
      _isSubmitting = false;
    });

    if (!mounted) return;

    if (success) {
      toastification.show(
        context: context,
        type: ToastificationType.success,
        title: const Text('Berhasil'),
        description: const Text('Jadwal kunjungan berhasil disimpan'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      if (type == 'now') {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => AttendanceLocationScreen(
              type: 'visit_in',
              initialWorkLocationId: _selectedWorkLocationId,
            ),
          ),
        );
      } else {
        Navigator.pop(context);
      }
    } else {
      toastification.show(
        context: context,
        type: ToastificationType.error,
        title: const Text('Gagal'),
        description: Text(itineraryProvider.error),
        autoCloseDuration: const Duration(seconds: 3),
      );
    }
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

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text('Form Visit', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Consumer<ItineraryProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && (provider.workLocations.isEmpty || provider.principals.isEmpty)) {
            return Center(child: CircularProgressIndicator(color: primaryColor));
          }

          final areas = provider.workLocations
              .map((loc) {
                final area = loc['area']?.toString().trim();
                return (area == null || area.isEmpty) ? 'Area Lainnya' : area;
              })
              .toSet()
              .toList();

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: cardColor,
                    border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Pilih Lokasi Visit',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor),
                      ),
                      const SizedBox(height: 24),
                      
                      // Area Dropdown
                      DropdownButtonFormField<String>(
                        dropdownColor: cardColor,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Area',
                        ),
                        value: _selectedArea,
                        items: areas.map((area) {
                          return DropdownMenuItem<String>(
                            value: area,
                            child: Text(area),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() {
                            _selectedArea = value;
                            _selectedWorkLocationId = null;
                          });
                        },
                      ),
                      
                      const SizedBox(height: 16),

                      // Location Dropdown
                      DropdownButtonFormField<int>(
                        dropdownColor: cardColor,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Lokasi Kerja',
                        ),
                        value: _selectedWorkLocationId,
                        items: provider.workLocations.where((loc) {
                          final locArea = loc['area']?.toString().trim();
                          final normalizedArea = (locArea == null || locArea.isEmpty) ? 'Area Lainnya' : locArea;
                          return normalizedArea == _selectedArea;
                        }).map((loc) {
                          return DropdownMenuItem<int>(
                            value: loc['id'],
                            child: Text(loc['name'] ?? ''),
                          );
                        }).toList(),
                        onChanged: _selectedArea == null ? null : (value) {
                          setState(() {
                            _selectedWorkLocationId = value;
                          });
                        },
                      ),

                      const SizedBox(height: 6),
                      Align(
                        alignment: Alignment.centerRight,
                        child: InkWell(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (_) => const RequestLocationScreen()),
                            );
                          },
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.add_location_alt_outlined, size: 14, color: primaryColor),
                                const SizedBox(width: 4),
                                Text(
                                  'Toko belum terdaftar? Request Lokasi Baru',
                                  style: TextStyle(color: primaryColor, fontSize: 11.5, fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      
                      const SizedBox(height: 12),

                      // Brand/Prinsiple Dropdown
                      DropdownButtonFormField<int>(
                        dropdownColor: cardColor,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Brand / Prinsiple',
                        ),
                        value: _selectedPrincipalId,
                        items: provider.principals.map((prin) {
                          return DropdownMenuItem<int>(
                            value: prin['id'],
                            child: Text(prin['name'] ?? ''),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() {
                            _selectedPrincipalId = value;
                          });
                        },
                      ),

                      const SizedBox(height: 16),

                      // Type Visit Dropdown
                      DropdownButtonFormField<String>(
                        dropdownColor: cardColor,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Type Visit',
                        ),
                        value: _selectedVisitType,
                        items: ['Store', 'Prinsiple', 'Lainnya'].map((type) {
                          return DropdownMenuItem<String>(
                            value: type,
                            child: Text(type),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() {
                            _selectedVisitType = value;
                          });
                        },
                      ),

                      const SizedBox(height: 16),

                      // Type Meeting Dropdown
                      DropdownButtonFormField<String>(
                        dropdownColor: cardColor,
                        style: TextStyle(color: textColor),
                        decoration: inputDecoration.copyWith(
                          labelText: 'Type Meeting',
                        ),
                        value: _selectedMeetingType,
                        items: ['Online', 'Offline'].map((type) {
                          return DropdownMenuItem<String>(
                            value: type,
                            child: Text(type),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() {
                            _selectedMeetingType = value;
                          });
                        },
                      ),

                      const SizedBox(height: 16),

                      // Agenda TextField
                      TextFormField(
                        controller: _agendaController,
                        style: TextStyle(color: textColor),
                        maxLines: 3,
                        decoration: inputDecoration.copyWith(
                          labelText: 'Agenda',
                        ),
                      ),
                    ],
                  ),
                ),
                
                const SizedBox(height: 32),
                
                // Buttons
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _isSubmitting ? null : () => _submit('scheduled'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: primaryColor,
                          side: BorderSide(color: primaryColor),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: const Text('Scheduled', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : () => _submit('now'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                        ),
                        child: _isSubmitting
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : const Text('Visit Now', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
