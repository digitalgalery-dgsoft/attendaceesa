import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/itinerary_provider.dart';

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
  late DateTime _selectedDate;
  final List<Map<String, dynamic>> _locations = [];
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _selectedDate = widget.initialDate;
    _fetchLocations();
  }

  void _fetchLocations() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      Provider.of<ItineraryProvider>(context, listen: false)
          .fetchWorkLocations(authProvider);
    });
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Theme.of(context).primaryColor,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  void _addLocation() {
    setState(() {
      _locations.add({
        'work_location_id': null,
        'notes': '',
      });
    });
  }

  void _removeLocation(int index) {
    setState(() {
      _locations.removeAt(index);
    });
  }

  Future<void> _submit() async {
    if (_locations.isEmpty) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Silakan tambahkan minimal 1 lokasi kunjungan'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    bool hasEmptyLocation = _locations.any((loc) => loc['work_location_id'] == null);
    if (hasEmptyLocation) {
      toastification.show(
        context: context,
        type: ToastificationType.warning,
        title: const Text('Perhatian'),
        description: const Text('Harap pilih lokasi untuk semua jadwal'),
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final itineraryProvider = Provider.of<ItineraryProvider>(context, listen: false);
    
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    
    final success = await itineraryProvider.createItinerary(
      authProvider,
      dateStr,
      _locations,
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
      Navigator.pop(context);
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
        title: Text('Tambah Jadwal', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Consumer<ItineraryProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.workLocations.isEmpty) {
            return Center(child: CircularProgressIndicator(color: primaryColor));
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Date Selection
                Text(
                  'Tanggal Kunjungan',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                ),
                const SizedBox(height: 8),
                InkWell(
                  onTap: () => _selectDate(context),
                  borderRadius: BorderRadius.circular(12),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      color: cardColor,
                      border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          DateFormat('dd MMMM yyyy').format(_selectedDate),
                          style: TextStyle(fontSize: 14, color: textColor),
                        ),
                        Icon(Icons.calendar_today, color: subtitleColor, size: 20),
                      ],
                    ),
                  ),
                ),
                
                const SizedBox(height: 24),
                
                // Locations List
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Daftar Lokasi',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                    ),
                    TextButton.icon(
                      onPressed: _addLocation,
                      icon: Icon(Icons.add, color: primaryColor, size: 18),
                      label: Text('Tambah', style: TextStyle(color: primaryColor)),
                    ),
                  ],
                ),
                
                if (_locations.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(32),
                    alignment: Alignment.center,
                    child: Text(
                      'Belum ada lokasi ditambahkan',
                      style: TextStyle(color: subtitleColor, fontSize: 13),
                    ),
                  )
                else
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _locations.length,
                    itemBuilder: (context, index) {
                      return Container(
                        margin: const EdgeInsets.only(bottom: 16),
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: cardColor,
                          border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Kunjungan ke-${index + 1}',
                                  style: TextStyle(fontWeight: FontWeight.bold, color: subtitleColor, fontSize: 12),
                                ),
                                GestureDetector(
                                  onTap: () => _removeLocation(index),
                                  child: const Icon(Icons.close, color: Colors.red, size: 20),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            
                            // Location Dropdown
                            DropdownButtonFormField<int>(
                              dropdownColor: cardColor,
                              style: TextStyle(color: textColor),
                              decoration: inputDecoration.copyWith(
                                labelText: 'Lokasi Kerja',
                              ),
                              value: _locations[index]['work_location_id'],
                              items: provider.workLocations.map((loc) {
                                return DropdownMenuItem<int>(
                                  value: loc['id'],
                                  child: Text(loc['name'] ?? ''),
                                );
                              }).toList(),
                              onChanged: (value) {
                                setState(() {
                                  _locations[index]['work_location_id'] = value;
                                });
                              },
                            ),
                            
                            const SizedBox(height: 16),
                            
                            // Notes Field
                            TextFormField(
                              style: TextStyle(color: textColor),
                              decoration: inputDecoration.copyWith(
                                labelText: 'Catatan (Opsional)',
                              ),
                              maxLines: 2,
                              initialValue: _locations[index]['notes'],
                              onChanged: (value) {
                                _locations[index]['notes'] = value;
                              },
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                  
                const SizedBox(height: 32),
                
                // Submit Button
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: _isSubmitting ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: primaryColor,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    child: _isSubmitting
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          )
                        : const Text('Simpan Jadwal', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ),
                ),
                
                const SizedBox(height: 32),
              ],
            ),
          );
        },
      ),
    );
  }
}
