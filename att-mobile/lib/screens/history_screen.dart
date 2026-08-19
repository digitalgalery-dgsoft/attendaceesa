import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/tracking_history_screen.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  late DateTime _currentMonth;
  late DateTime _selectedDate;
  late ScrollController _dateScrollController;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _currentMonth = DateTime(now.year, now.month, 1);
    _selectedDate = DateTime(now.year, now.month, now.day);
    _dateScrollController = ScrollController();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _fetchData().then((_) {
        // Wait another frame to ensure ListView is built after isLoading becomes false
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _scrollToSelectedDate();
        });
      });
    });
  }

  Future<void> _fetchData() async {
    final dateFormat = DateFormat('yyyy-MM-dd');
    final startDate = _currentMonth;
    final endDate = DateTime(_currentMonth.year, _currentMonth.month + 1, 0); // Last day of month
    
    await Provider.of<AttendanceProvider>(context, listen: false).fetchHistory(
      startDate: dateFormat.format(startDate),
      endDate: dateFormat.format(endDate),
    );
  }

  void _scrollToSelectedDate() {
    if (_dateScrollController.hasClients) {
      // Each item is 50 width + 8 margin = 58
      final offset = (_selectedDate.day - 1) * 58.0;
      // Subtract half screen width to center it somewhat
      final screenWidth = MediaQuery.of(context).size.width;
      final targetOffset = offset - (screenWidth / 2) + 29.0;
      
      _dateScrollController.animateTo(
        targetOffset < 0 ? 0 : targetOffset,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  @override
  void dispose() {
    _dateScrollController.dispose();
    super.dispose();
  }

  void _changeMonth(int offset) {
    setState(() {
      _currentMonth = DateTime(_currentMonth.year, _currentMonth.month + offset, 1);
      final lastDayOfNewMonth = DateTime(_currentMonth.year, _currentMonth.month + 1, 0).day;
      int newDay = _selectedDate.day;
      if (newDay > lastDayOfNewMonth) {
        newDay = lastDayOfNewMonth;
      }
      _selectedDate = DateTime(_currentMonth.year, _currentMonth.month, newDay);
    });
    _fetchData().then((_) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _scrollToSelectedDate();
      });
    });
  }

  String _parseLocalTime(String? raw, {String fmt = 'HH:mm'}) {
    if (raw == null || raw.isEmpty) return '-';
    try {
      final dt = DateTime.parse('${raw}');
      return DateFormat(fmt).format(dt);
    } catch (_) {
      return '-';
    }
  }

  @override
  Widget build(BuildContext context) {
    final attProvider = Provider.of<AttendanceProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text('Riwayat Kehadiran', style: TextStyle(color: textColor, fontSize: 18, fontWeight: FontWeight.bold)),
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        actions: [
          IconButton(
            icon: Icon(Icons.route, color: textColor),
            tooltip: 'Tracking History',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const TrackingHistoryScreen()),
              );
            },
          ),
        ],
      ),
      body: attProvider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () async => _fetchData(),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 17, vertical: 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Month Navigator
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          GestureDetector(
                            onTap: () => _changeMonth(-1),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(20)),
                              child: Text('‹', style: TextStyle(fontSize: 18, color: textColor, fontWeight: FontWeight.bold)),
                            ),
                          ),
                          const SizedBox(width: 15),
                          Text(
                            DateFormat('MMMM yyyy').format(_currentMonth),
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                          ),
                          const SizedBox(width: 15),
                          GestureDetector(
                            onTap: () => _changeMonth(1),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(20)),
                              child: Text('›', style: TextStyle(fontSize: 18, color: textColor, fontWeight: FontWeight.bold)),
                            ),
                          ),
                        ],
                      ),
                      
                      const SizedBox(height: 20),
                      
                      // Date Strip
                      SizedBox(
                        height: 70,
                        child: ListView.builder(
                          controller: _dateScrollController,
                          scrollDirection: Axis.horizontal,
                          itemCount: DateTime(_currentMonth.year, _currentMonth.month + 1, 0).day,
                          itemBuilder: (context, index) {
                            final date = DateTime(_currentMonth.year, _currentMonth.month, index + 1);
                            final isSelected = _selectedDate.day == date.day && _selectedDate.month == date.month;
                            final dateStr = DateFormat('yyyy-MM-dd').format(date);
                            final hasData = attProvider.logsByDate.containsKey(dateStr);
                            
                            return GestureDetector(
                              onTap: () {
                                setState(() {
                                  _selectedDate = date;
                                });
                              },
                              child: Container(
                                width: 50,
                                margin: const EdgeInsets.only(right: 8),
                                decoration: BoxDecoration(
                                  color: isSelected ? primaryColor : elevatedColor,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(color: isSelected ? primaryColor : (hasData ? primaryColor.withOpacity(0.3) : Colors.transparent)),
                                ),
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      DateFormat('E').format(date).substring(0, 3),
                                      style: TextStyle(fontSize: 10, color: isSelected ? Colors.white70 : subtitleColor, fontWeight: FontWeight.w600),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      '${date.day}',
                                      style: TextStyle(fontSize: 16, color: isSelected ? Colors.white : textColor, fontWeight: FontWeight.bold),
                                    ),
                                    if (hasData)
                                      Container(
                                        margin: const EdgeInsets.only(top: 4),
                                        width: 4, height: 4,
                                        decoration: BoxDecoration(color: isSelected ? Colors.white : primaryColor, shape: BoxShape.circle),
                                      )
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                      ),
                      
                      const SizedBox(height: 20),
                      
                      // Detail Card
                      _buildDetailCard(attProvider, cardColor, elevatedColor, textColor, subtitleColor),

                      const SizedBox(height: 20),

                      // Month Stats
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Ringkasan Bulan Ini', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor)),
                        ],
                      ),
                      const SizedBox(height: 10),
                      _buildMonthStats(attProvider, cardColor, textColor, subtitleColor, primaryColor, elevatedColor),
                      
                      const SizedBox(height: 30),
                    ],
                  ),
                ),
              ),
            ),
    );
  }

  Widget _buildDetailCard(AttendanceProvider provider, Color cardColor, Color elevatedColor, Color textColor, Color subtitleColor) {
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    final logs = provider.logsByDate[dateStr] as List? ?? [];
    
    if (logs.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: cardColor,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.grey.shade300),
        ),
        child: Center(
          child: Column(
            children: [
              Icon(Icons.event_busy, color: subtitleColor, size: 40),
              const SizedBox(height: 12),
              Text('Tidak ada riwayat pada tanggal ini.', style: TextStyle(color: subtitleColor, fontSize: 12, fontWeight: FontWeight.w500)),
            ],
          ),
        ),
      );
    }
    
    String firstIn = '--:--';
    String lastOut = '--:--';
    
    var checkins = logs.where((l) => l['log_type'] == 'checkin').toList();
    if (checkins.isNotEmpty) {
      firstIn = _parseLocalTime(checkins.first['logged_at']);
    }

    var checkouts = logs.where((l) => l['log_type'] == 'checkout').toList();
    if (checkouts.isNotEmpty) {
      lastOut = _parseLocalTime(checkouts.last['logged_at']);
    }
    
    int visits = logs.where((l) => l['log_type'] == 'visit_in').length;
    int meetings = logs.where((l) => l['log_type'] == 'meet_in').length;

    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(DateFormat('EEEE, d MMMM').format(_selectedDate), style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor)),
                    const SizedBox(height: 2),
                    Text('${logs.length} aktivitas tercatat', style: TextStyle(fontSize: 10, color: subtitleColor)),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: const Color(0xFFE2F6EE), borderRadius: BorderRadius.circular(12)),
                  child: const Text('Hadir', style: TextStyle(fontSize: 10, color: Color(0xFF149A6E), fontWeight: FontWeight.bold)),
                )
              ],
            ),
          ),
          Divider(color: Colors.grey.shade200, height: 1),
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(12)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Check-in', style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(firstIn, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
                            const SizedBox(width: 2),
                            Padding(padding: const EdgeInsets.only(bottom: 2), child: Text('WIB', style: TextStyle(fontSize: 9, color: subtitleColor))),
                          ],
                        )
                      ],
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(12)),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Check-out', style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(lastOut, style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor)),
                            const SizedBox(width: 2),
                            Padding(padding: const EdgeInsets.only(bottom: 2), child: Text('WIB', style: TextStyle(fontSize: 9, color: subtitleColor))),
                          ],
                        )
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (visits > 0 || logs.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Row(
                children: [
                  Icon(Icons.check_circle, size: 12, color: const Color(0xFF149A6E)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${checkins.isNotEmpty ? 'Hadir' : 'Tidak Hadir'}${visits > 0 ? ' · $visits kunjungan' : ''}${meetings > 0 ? ' · $meetings meeting' : ''}',
                      style: TextStyle(fontSize: 11, color: subtitleColor, fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            ),
            
          // Expansion view for detailed logs
          Theme(
            data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
            child: ExpansionTile(
              title: Text('Lihat Detail Aktivitas', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: textColor)),
              children: [
                Padding(
                  padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
                  child: Column(
                    children: logs.map((log) => _buildLogItem(log, elevatedColor)).toList(),
                  ),
                )
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildMonthStats(AttendanceProvider provider, Color cardColor, Color textColor, Color subtitleColor, Color primaryColor, Color elevatedColor) {
    final stats = provider.stats;
    int totalMasuk = stats['total_masuk'] ?? 0;
    int uniqueStore = stats['unique_store'] ?? 0;
    int noOut = stats['no_out'] ?? 0;
    int less5m = stats['less_than_5_min'] ?? 0;

    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 2.2,
      children: [
        _buildStatItem('Total Masuk', totalMasuk.toString(), cardColor, textColor, subtitleColor, const Color(0xFF149A6E)),
        _buildStatItem('Unique Store', uniqueStore.toString(), cardColor, textColor, subtitleColor, primaryColor),
        _buildStatItem('No Out', noOut.toString(), cardColor, textColor, subtitleColor, const Color(0xFFE0473E)),
        _buildStatItem('< 5 Menit', less5m.toString(), cardColor, textColor, subtitleColor, const Color(0xFFD98A2B)),
      ],
    );
  }
  
  Widget _buildStatItem(String title, String value, Color cardColor, Color textColor, Color subtitleColor, Color iconColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor)),
                const SizedBox(height: 2),
                Text(title, style: TextStyle(fontSize: 9, color: subtitleColor, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          Container(
            width: 4, height: double.infinity,
            decoration: BoxDecoration(color: iconColor, borderRadius: BorderRadius.circular(2)),
          )
        ],
      ),
    );
  }

  Widget _buildLogItem(dynamic log, Color elevatedColor) {
    final String type = log['log_type'] ?? '';
    final String time = _parseLocalTime(log['logged_at']);
    final String? rawPhotoUrl = log['photo_url'];
    final String? photoPath = log['photo_path'];
    final String? photoUrl = (photoPath != null && photoPath.isNotEmpty) 
        ? Constants.getImageUrl(photoPath) 
        : rawPhotoUrl;
    final String? note = log['note'];
    String locationName = log['location']?['name'] as String? ?? '';
    final companyName = log['location']?['company']?['name'] as String?;
    if (companyName != null && companyName.isNotEmpty) {
      locationName = '$companyName - $locationName';
    }
    final String locationAddress = log['location']?['address'] as String? ?? '';
    final String lat = log['latitude']?.toString() ?? '';
    final String lng = log['longitude']?.toString() ?? '';
    final String distance = log['distance_from_location_meter']?.toString() ?? '';

    final Map<String, dynamic>? metadata = log['metadata'] is Map
        ? Map<String, dynamic>.from(log['metadata'])
        : null;

    // For meet_in/meet_out: get meeting name from location or metadata
    String? meetingTitle;
    String? meetingLocationInfo;
    if (type == 'meet_in' || type == 'meet_out') {
      meetingTitle = log['location']?['name'] as String?
          ?? metadata?['meeting_title'] as String?;
      meetingLocationInfo = log['location']?['address'] as String?
          ?? metadata?['location_name'] as String?
          ?? (metadata?['meeting_type'] == 'online' ? 'Online Meeting' : null);
    }

    Color color;
    String label;

    switch (type) {
      case 'checkin':
        color = const Color(0xFF149A6E);
        label = 'Check-in';
        break;
      case 'checkout':
        color = const Color(0xFFE0473E);
        label = 'Check-out';
        break;
      case 'visit_in':
        color = const Color(0xFF0FA8C4);
        label = 'Visit-in';
        break;
      case 'visit_out':
        color = const Color(0xFFD98A2B);
        label = 'Visit-out';
        break;
      case 'meet_in':
        color = const Color(0xFF10B981);
        label = 'Meet-In';
        break;
      case 'meet_out':
        color = const Color(0xFF8B5CF6);
        label = 'Meet-Out';
        break;
      default:
        color = Colors.grey;
        label = type;
    }

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: elevatedColor))
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            margin: const EdgeInsets.only(top: 4),
            width: 7, height: 7,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(label, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: Theme.of(context).brightness == Brightness.dark ? Colors.white : const Color(0xFF0E1830))),
                    Text(time, style: TextStyle(fontSize: 10, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade400 : const Color(0xFF707893), fontWeight: FontWeight.bold)),
                  ],
                ),
                // Show meeting title for meet_in / meet_out
                if (meetingTitle != null && meetingTitle.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 3),
                    child: Row(
                      children: [
                        Icon(Icons.video_camera_front, size: 10, color: color),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            meetingTitle,
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: color),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                // Show meeting location/type info
                if ((type == 'meet_in' || type == 'meet_out') && meetingLocationInfo != null && meetingLocationInfo.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Row(
                      children: [
                        Icon(Icons.place_outlined, size: 10, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade400 : Colors.grey),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            meetingLocationInfo,
                            style: TextStyle(fontSize: 9, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade400 : Colors.grey[600]),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                if (note != null && note.trim().isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.notes, size: 10, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade500 : Colors.grey),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(note.trim(), style: TextStyle(fontSize: 10, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade400 : Colors.grey[700])),
                        ),
                      ],
                    ),
                  ),
                // Only show photo and location block for non-meeting types
                if (type != 'meet_in' && type != 'meet_out')
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (photoUrl != null && photoUrl.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(right: 10),
                            child: GestureDetector(
                              onTap: () {
                                showDialog(
                                  context: context,
                                  builder: (_) => Dialog(
                                    backgroundColor: Colors.transparent,
                                    insetPadding: const EdgeInsets.all(16),
                                    child: Stack(
                                      alignment: Alignment.topRight,
                                      children: [
                                        ClipRRect(
                                          borderRadius: BorderRadius.circular(12),
                                          child: Image.network(photoUrl, fit: BoxFit.contain),
                                        ),
                                        IconButton(
                                          icon: const Icon(Icons.close, color: Colors.white, size: 30),
                                          onPressed: () => Navigator.pop(context),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(6),
                                child: Image.network(
                                  photoUrl,
                                  height: 50,
                                  width: 50,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, __, ___) => Container(
                                    height: 50, width: 50,
                                    color: elevatedColor,
                                    child: const Icon(Icons.broken_image, color: Colors.grey, size: 20),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (locationName.isNotEmpty)
                                Text(locationName, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade300 : const Color(0xFF505565))),
                              if (locationAddress.isNotEmpty)
                                Text(locationAddress, style: TextStyle(fontSize: 9, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade400 : const Color(0xFF707893)), maxLines: 2, overflow: TextOverflow.ellipsis),
                              if (lat.isNotEmpty && lng.isNotEmpty)
                                Text(
                                  'Lat: $lat, Lng: $lng${distance.isNotEmpty && distance != 'null' ? ' | Radius: ${double.tryParse(distance)?.toStringAsFixed(1) ?? distance}m' : ''}', 
                                  style: const TextStyle(fontSize: 9, color: Color(0xFF0F52BA))
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                // For meet_in/meet_out: show selfie photo below
                if ((type == 'meet_in' || type == 'meet_out') && photoUrl != null && photoUrl.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: GestureDetector(
                      onTap: () {
                        showDialog(
                          context: context,
                          builder: (_) => Dialog(
                            backgroundColor: Colors.transparent,
                            insetPadding: const EdgeInsets.all(16),
                            child: Stack(
                              alignment: Alignment.topRight,
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(12),
                                  child: Image.network(photoUrl, fit: BoxFit.contain),
                                ),
                                IconButton(
                                  icon: const Icon(Icons.close, color: Colors.white, size: 30),
                                  onPressed: () => Navigator.pop(context),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                      child: Row(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(6),
                            child: Image.network(
                              photoUrl,
                              height: 50,
                              width: 50,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => Container(
                                height: 50, width: 50,
                                color: elevatedColor,
                                child: const Icon(Icons.broken_image, color: Colors.grey, size: 20),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            'Foto Selfie Meeting',
                            style: TextStyle(fontSize: 9, color: Theme.of(context).brightness == Brightness.dark ? Colors.grey.shade400 : Colors.grey[600]),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          )
        ],
      ),
    );
  }
}
