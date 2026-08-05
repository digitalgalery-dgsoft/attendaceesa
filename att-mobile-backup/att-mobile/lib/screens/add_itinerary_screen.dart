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
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tambah Jadwal'),
        foregroundColor: Colors.black,
      ),
      backgroundColor: Colors.grey[50],
      body: Consumer<ItineraryProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.workLocations.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Date Selection
                const Text(
                  'Tanggal Kunjungan',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 8),
                InkWell(
                  onTap: () => _selectDate(context),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border.all(color: Colors.grey[300]!),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          DateFormat('dd MMMM yyyy').format(_selectedDate),
                          style: const TextStyle(fontSize: 16),
                        ),
                        const Icon(Icons.calendar_today, color: Colors.grey),
                      ],
                    ),
                  ),
                ),
                
                const SizedBox(height: 24),
                
                // Locations List
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Daftar Lokasi',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    TextButton.icon(
                      onPressed: _addLocation,
                      icon: const Icon(Icons.add),
                      label: const Text('Tambah'),
                      style: TextButton.styleFrom(
                        foregroundColor: Theme.of(context).primaryColor,
                      ),
                    ),
                  ],
                ),
                
                if (_locations.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(32),
                    alignment: Alignment.center,
                    child: const Text(
                      'Belum ada lokasi ditambahkan',
                      style: TextStyle(color: Colors.grey),
                    ),
                  )
                else
                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _locations.length,
                    itemBuilder: (context, index) {
                      return Card(
                        margin: const EdgeInsets.only(bottom: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                          side: BorderSide(color: Colors.grey[200]!),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'Kunjungan ke-${index + 1}',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: Colors.grey,
                                    ),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.close, color: Colors.red, size: 20),
                                    onPressed: () => _removeLocation(index),
                                    constraints: const BoxConstraints(),
                                    padding: EdgeInsets.zero,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              
                              // Location Dropdown
                              DropdownButtonFormField<int>(
                                decoration: const InputDecoration(
                                  labelText: 'Lokasi Kerja',
                                  isDense: true,
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
                              
                              const SizedBox(height: 12),
                              
                              // Notes Field
                              TextFormField(
                                decoration: const InputDecoration(
                                  labelText: 'Catatan (Opsional)',
                                  isDense: true,
                                ),
                                maxLines: 2,
                                initialValue: _locations[index]['notes'],
                                onChanged: (value) {
                                  _locations[index]['notes'] = value;
                                },
                              ),
                            ],
                          ),
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
                    child: _isSubmitting
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text('Simpan Jadwal'),
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
