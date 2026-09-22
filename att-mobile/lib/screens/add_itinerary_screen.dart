import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/itinerary_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/screens/attendance_location_screen.dart';
import 'package:att_mobile/screens/request_location_screen.dart';
import '../widgets/custom_loading_indicator.dart';

class SearchablePickerItem<T> {
  final String label;
  final String? subtitle;
  final T value;

  SearchablePickerItem({
    required this.label,
    this.subtitle,
    required this.value,
  });
}

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

  Future<void> _openSearchablePicker<T>({
    required String title,
    required List<SearchablePickerItem<T>> items,
    required T? currentValue,
    required ValueChanged<T> onSelected,
  }) async {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final searchBg = isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade100;

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        String query = '';
        return StatefulBuilder(
          builder: (context, setSheetState) {
            final filtered = items.where((item) {
              if (query.isEmpty) return true;
              final q = query.toLowerCase();
              final matchesLabel = item.label.toLowerCase().contains(q);
              final matchesSub = item.subtitle?.toLowerCase().contains(q) ?? false;
              return matchesLabel || matchesSub;
            }).toList();

            return Container(
              height: MediaQuery.of(context).size.height * 0.75,
              decoration: BoxDecoration(
                color: cardColor,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 36,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade400,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          title,
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, size: 20),
                          onPressed: () => Navigator.pop(context),
                          color: subtitleColor,
                        ),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                    child: TextField(
                      autofocus: true,
                      style: TextStyle(color: textColor, fontSize: 14),
                      decoration: InputDecoration(
                        hintText: 'Ketik untuk mencari...',
                        hintStyle: TextStyle(color: subtitleColor, fontSize: 13),
                        prefixIcon: Icon(Icons.search, color: subtitleColor, size: 20),
                        filled: true,
                        fillColor: searchBg,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                      ),
                      onChanged: (val) {
                        setSheetState(() {
                          query = val;
                        });
                      },
                    ),
                  ),
                  const SizedBox(height: 8),
                  Expanded(
                    child: filtered.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.search_off, size: 40, color: subtitleColor.withOpacity(0.5)),
                                const SizedBox(height: 8),
                                Text(
                                  'Data tidak ditemukan',
                                  style: TextStyle(color: subtitleColor, fontSize: 13),
                                ),
                              ],
                            ),
                          )
                        : ListView.separated(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            itemCount: filtered.length,
                            separatorBuilder: (_, __) => Divider(height: 1, color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                            itemBuilder: (context, idx) {
                              final itm = filtered[idx];
                              final isSelected = itm.value == currentValue;
                              return ListTile(
                                contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                title: Text(
                                  itm.label,
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                                    color: isSelected ? primaryColor : textColor,
                                  ),
                                ),
                                subtitle: itm.subtitle != null && itm.subtitle!.isNotEmpty
                                    ? Text(
                                        itm.subtitle!,
                                        style: TextStyle(fontSize: 11.5, color: subtitleColor),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      )
                                    : null,
                                trailing: isSelected
                                    ? Icon(Icons.check_circle, color: primaryColor, size: 20)
                                    : null,
                                onTap: () {
                                  Navigator.pop(context);
                                  onSelected(itm.value);
                                },
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

  Widget _buildSearchableSelectField({
    required String label,
    required String? valueText,
    required String placeholder,
    required VoidCallback? onTap,
    required Color subtitleColor,
    required Color textColor,
    required bool isDarkMode,
    bool isEnabled = true,
  }) {
    return InkWell(
      onTap: isEnabled ? onTap : null,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
        decoration: BoxDecoration(
          color: isEnabled
              ? (isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50)
              : (isDarkMode ? Colors.grey.shade900 : Colors.grey.shade200),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300,
          ),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: subtitleColor,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    valueText != null && valueText.isNotEmpty ? valueText : placeholder,
                    style: TextStyle(
                      fontSize: 13.5,
                      fontWeight: valueText != null && valueText.isNotEmpty ? FontWeight.w600 : FontWeight.normal,
                      color: !isEnabled
                          ? subtitleColor.withOpacity(0.5)
                          : (valueText != null && valueText.isNotEmpty ? textColor : subtitleColor),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            Icon(
              Icons.arrow_drop_down,
              color: isEnabled ? subtitleColor : subtitleColor.withOpacity(0.3),
            ),
          ],
        ),
      ),
    );
  }

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
            return const Center(child: CustomLoadingIndicator(message: 'Menyiapkan data toko...'));
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
                      
                      // 1. Area Selector (Searchable)
                      _buildSearchableSelectField(
                        label: 'Area',
                        valueText: _selectedArea,
                        placeholder: 'Pilih Area',
                        subtitleColor: subtitleColor,
                        textColor: textColor,
                        isDarkMode: isDarkMode,
                        onTap: () {
                          final items = areas.map((a) => SearchablePickerItem<String>(
                            label: a,
                            value: a,
                          )).toList();
                          _openSearchablePicker<String>(
                            title: 'Pilih Area',
                            items: items,
                            currentValue: _selectedArea,
                            onSelected: (val) {
                              setState(() {
                                _selectedArea = val;
                                _selectedWorkLocationId = null;
                              });
                            },
                          );
                        },
                      ),
                      
                      const SizedBox(height: 16),

                      // 2. Lokasi Kerja Selector (Searchable)
                      Builder(
                        builder: (context) {
                          final filteredLocations = provider.workLocations.where((loc) {
                            final locArea = loc['area']?.toString().trim();
                            final normalizedArea = (locArea == null || locArea.isEmpty) ? 'Area Lainnya' : locArea;
                            return normalizedArea == _selectedArea;
                          }).toList();

                          final selectedLoc = provider.workLocations.firstWhere(
                            (l) => l['id'] == _selectedWorkLocationId,
                            orElse: () => null,
                          );

                          return _buildSearchableSelectField(
                            label: 'Lokasi Kerja',
                            valueText: selectedLoc != null ? (selectedLoc['name'] ?? '') : null,
                            placeholder: _selectedArea == null ? 'Pilih Area terlebih dahulu' : 'Pilih Lokasi Kerja',
                            isEnabled: _selectedArea != null,
                            subtitleColor: subtitleColor,
                            textColor: textColor,
                            isDarkMode: isDarkMode,
                            onTap: () {
                              final items = filteredLocations.map((loc) => SearchablePickerItem<int>(
                                label: loc['name'] ?? 'Toko',
                                subtitle: loc['address']?.toString(),
                                value: loc['id'] as int,
                              )).toList();
                              _openSearchablePicker<int>(
                                title: 'Pilih Lokasi Kerja (${_selectedArea ?? ''})',
                                items: items,
                                currentValue: _selectedWorkLocationId,
                                onSelected: (val) {
                                  setState(() {
                                    _selectedWorkLocationId = val;
                                  });
                                },
                              );
                            },
                          );
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

                      // 3. Brand/Prinsiple Selector (Searchable)
                      Builder(
                        builder: (context) {
                          final selectedPrin = provider.principals.firstWhere(
                            (p) => p['id'] == _selectedPrincipalId,
                            orElse: () => null,
                          );

                          return _buildSearchableSelectField(
                            label: 'Brand / Prinsiple',
                            valueText: selectedPrin != null ? (selectedPrin['name'] ?? '') : null,
                            placeholder: 'Pilih Brand / Prinsiple',
                            subtitleColor: subtitleColor,
                            textColor: textColor,
                            isDarkMode: isDarkMode,
                            onTap: () {
                              final items = provider.principals.map((prin) => SearchablePickerItem<int>(
                                label: prin['name'] ?? 'Brand',
                                subtitle: prin['code']?.toString(),
                                value: prin['id'] as int,
                              )).toList();
                              _openSearchablePicker<int>(
                                title: 'Pilih Brand / Prinsiple',
                                items: items,
                                currentValue: _selectedPrincipalId,
                                onSelected: (val) {
                                  setState(() {
                                    _selectedPrincipalId = val;
                                  });
                                },
                              );
                            },
                          );
                        },
                      ),

                      const SizedBox(height: 16),

                      // 4. Type Visit Selector (Searchable)
                      _buildSearchableSelectField(
                        label: 'Type Visit',
                        valueText: _selectedVisitType,
                        placeholder: 'Pilih Type Visit',
                        subtitleColor: subtitleColor,
                        textColor: textColor,
                        isDarkMode: isDarkMode,
                        onTap: () {
                          final items = ['Store', 'Prinsiple', 'Lainnya'].map((type) => SearchablePickerItem<String>(
                            label: type,
                            value: type,
                          )).toList();
                          _openSearchablePicker<String>(
                            title: 'Pilih Type Visit',
                            items: items,
                            currentValue: _selectedVisitType,
                            onSelected: (val) {
                              setState(() {
                                _selectedVisitType = val;
                              });
                            },
                          );
                        },
                      ),

                      const SizedBox(height: 16),

                      // 5. Type Meeting Selector (Searchable)
                      _buildSearchableSelectField(
                        label: 'Type Meeting',
                        valueText: _selectedMeetingType,
                        placeholder: 'Pilih Type Meeting',
                        subtitleColor: subtitleColor,
                        textColor: textColor,
                        isDarkMode: isDarkMode,
                        onTap: () {
                          final items = ['Online', 'Offline'].map((type) => SearchablePickerItem<String>(
                            label: type,
                            value: type,
                          )).toList();
                          _openSearchablePicker<String>(
                            title: 'Pilih Type Meeting',
                            items: items,
                            currentValue: _selectedMeetingType,
                            onSelected: (val) {
                              setState(() {
                                _selectedMeetingType = val;
                              });
                            },
                          );
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
