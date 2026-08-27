import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:table_calendar/table_calendar.dart';
import 'package:intl/intl.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/itinerary_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/screens/add_itinerary_screen.dart';

class ItineraryScreen extends StatefulWidget {
  const ItineraryScreen({super.key});

  @override
  State<ItineraryScreen> createState() => _ItineraryScreenState();
}

class _ItineraryScreenState extends State<ItineraryScreen> {
  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;
  bool _isInit = true;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_isInit) {
      _selectedDay = _focusedDay;
      _fetchData();
      _isInit = false;
    }
  }

  Future<void> _fetchData() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    await Provider.of<ItineraryProvider>(context, listen: false)
        .fetchItineraries(authProvider);
  }

  Map<String, dynamic>? _getItineraryForDay(DateTime day, List<dynamic> itineraries) {
    final dateStr = DateFormat('yyyy-MM-dd').format(day);
    try {
      final item = itineraries.firstWhere(
        (it) => it['date'] == dateStr,
      );
      return item is Map<String, dynamic> ? item : Map<String, dynamic>.from(item);
    } catch (e) {
      return null;
    }
  }

  List<dynamic> _getEventsForDay(DateTime day, List<dynamic> itineraries) {
    final itinerary = _getItineraryForDay(day, itineraries);
    return itinerary != null ? (itinerary['items'] ?? []) : [];
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text(
          'Visit',
          style: TextStyle(fontWeight: FontWeight.bold, color: textColor),
        ),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh, color: textColor),
            onPressed: _fetchData,
          ),
        ],
      ),
      body: Consumer<ItineraryProvider>(
        builder: (context, itineraryProvider, child) {
          if (itineraryProvider.isLoading && itineraryProvider.itineraries.isEmpty) {
            return Center(child: CircularProgressIndicator(color: primaryColor));
          }

          final selectedEvents = _getEventsForDay(
            _selectedDay ?? _focusedDay,
            itineraryProvider.itineraries,
          );

          return Column(
            children: [
              Container(
                color: cardColor,
                child: TableCalendar(
                  firstDay: DateTime.now().subtract(const Duration(days: 365)),
                  lastDay: DateTime.now().add(const Duration(days: 365)),
                  focusedDay: _focusedDay,
                  calendarFormat: _calendarFormat,
                  headerStyle: HeaderStyle(
                    titleTextStyle: TextStyle(color: textColor, fontSize: 16),
                    formatButtonTextStyle: TextStyle(color: textColor),
                    leftChevronIcon: Icon(Icons.chevron_left, color: textColor),
                    rightChevronIcon: Icon(Icons.chevron_right, color: textColor),
                  ),
                  daysOfWeekStyle: DaysOfWeekStyle(
                    weekdayStyle: TextStyle(color: textColor),
                    weekendStyle: TextStyle(color: Colors.red.shade400),
                  ),
                  calendarStyle: CalendarStyle(
                    defaultTextStyle: TextStyle(color: textColor),
                    weekendTextStyle: TextStyle(color: Colors.red.shade400),
                    outsideTextStyle: TextStyle(color: subtitleColor),
                    markerDecoration: BoxDecoration(
                      color: primaryColor,
                      shape: BoxShape.circle,
                    ),
                    selectedDecoration: BoxDecoration(
                      color: primaryColor,
                      shape: BoxShape.circle,
                    ),
                    todayDecoration: BoxDecoration(
                      color: primaryColor.withOpacity(0.5),
                      shape: BoxShape.circle,
                    ),
                  ),
                  selectedDayPredicate: (day) {
                    return isSameDay(_selectedDay, day);
                  },
                  onDaySelected: (selectedDay, focusedDay) {
                    if (!isSameDay(_selectedDay, selectedDay)) {
                      setState(() {
                        _selectedDay = selectedDay;
                        _focusedDay = focusedDay;
                      });
                    }
                  },
                  onFormatChanged: (format) {
                    if (_calendarFormat != format) {
                      setState(() {
                        _calendarFormat = format;
                      });
                    }
                  },
                  onPageChanged: (focusedDay) {
                    _focusedDay = focusedDay;
                  },
                  eventLoader: (day) => _getEventsForDay(day, itineraryProvider.itineraries),
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: Container(
                  width: double.infinity,
                  color: bgColor,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Jadwal Kunjungan: ${DateFormat('dd MMM yyyy').format(_selectedDay ?? _focusedDay)}',
                                style: TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.bold,
                                  color: textColor,
                                ),
                              ),
                            ),
                            if (selectedEvents.isNotEmpty) ...[
                              Builder(builder: (context) {
                                final currentItinerary = _getItineraryForDay(_selectedDay ?? _focusedDay, itineraryProvider.itineraries);
                                final isStrict = currentItinerary?['is_strict_routing'] == true;
                                return Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: isStrict ? Colors.amber.withValues(alpha: 0.15) : Colors.blue.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(6),
                                    border: Border.all(color: isStrict ? Colors.amber.shade700 : Colors.blue.shade400, width: 0.8),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(
                                        isStrict ? Icons.lock : Icons.swap_horiz,
                                        size: 12,
                                        color: isStrict ? Colors.amber.shade900 : Colors.blue.shade700,
                                      ),
                                      const SizedBox(width: 4),
                                      Text(
                                        isStrict ? 'Rute Wajib Berurutan' : 'Bebas Visit',
                                        style: TextStyle(
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                          color: isStrict ? Colors.amber.shade900 : Colors.blue.shade800,
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }),
                            ],
                          ],
                        ),
                      ),
                      if (selectedEvents.isEmpty)
                        Expanded(
                          child: Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.event_busy, size: 48, color: subtitleColor),
                                const SizedBox(height: 16),
                                Text(
                                  'Tidak ada kunjungan',
                                  style: TextStyle(color: subtitleColor, fontSize: 14),
                                ),
                              ],
                            ),
                          ),
                        )
                      else
                        Expanded(
                          child: ListView.builder(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            itemCount: selectedEvents.length,
                            itemBuilder: (context, index) {
                              final event = selectedEvents[index];
                              final locationName = event['work_location'] != null
                                  ? event['work_location']['name']
                                  : 'Unknown Location';
                              final isVisited = event['is_visited'] == true;
                              final isLocked = event['is_locked'] == true;
                              final isNextTarget = event['is_next_target'] == true;
                                  
                              return Container(
                                margin: const EdgeInsets.only(bottom: 10),
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: cardColor,
                                  border: Border.all(
                                    color: isNextTarget 
                                        ? primaryColor.withValues(alpha: 0.6) 
                                        : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                                    width: isNextTarget ? 1.5 : 1,
                                  ),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(8),
                                      decoration: BoxDecoration(
                                        color: isVisited 
                                            ? Colors.green.withValues(alpha: 0.15)
                                            : (isLocked ? Colors.orange.withValues(alpha: 0.15) : primaryColor.withValues(alpha: 0.1)),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: isVisited
                                          ? const Icon(Icons.check_circle, size: 18, color: Colors.green)
                                          : (isLocked
                                              ? Icon(Icons.lock, size: 18, color: Colors.orange.shade700)
                                              : Text(
                                                  '${event['sequence'] ?? (index + 1)}',
                                                  style: TextStyle(
                                                    color: primaryColor,
                                                    fontWeight: FontWeight.bold,
                                                    fontSize: 13,
                                                  ),
                                                )),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Expanded(
                                                child: Text(
                                                  locationName,
                                                  style: TextStyle(
                                                    fontWeight: FontWeight.bold,
                                                    color: isLocked ? textColor.withValues(alpha: 0.7) : textColor,
                                                    fontSize: 13.5,
                                                  ),
                                                ),
                                              ),
                                              if (isVisited)
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: Colors.green.withValues(alpha: 0.12),
                                                    borderRadius: BorderRadius.circular(4),
                                                  ),
                                                  child: const Text('SELESAI', style: TextStyle(color: Colors.green, fontSize: 9, fontWeight: FontWeight.bold)),
                                                )
                                              else if (isLocked)
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: Colors.orange.withValues(alpha: 0.12),
                                                    borderRadius: BorderRadius.circular(4),
                                                  ),
                                                  child: Text('TERKUNCI', style: TextStyle(color: Colors.orange.shade800, fontSize: 9, fontWeight: FontWeight.bold)),
                                                )
                                              else if (isNextTarget)
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: primaryColor.withValues(alpha: 0.12),
                                                    borderRadius: BorderRadius.circular(4),
                                                  ),
                                                  child: Text('TARGET BERIKUTNYA', style: TextStyle(color: primaryColor, fontSize: 9, fontWeight: FontWeight.bold)),
                                                ),
                                            ],
                                          ),
                                          if (event['notes'] != null && event['notes'].toString().isNotEmpty) ...[
                                            const SizedBox(height: 3),
                                            Text(
                                              event['notes'],
                                              style: TextStyle(color: subtitleColor, fontSize: 11.5),
                                            ),
                                          ],
                                          if (isLocked) ...[
                                            const SizedBox(height: 2),
                                            Text(
                                              'Wajib selesaikan kunjungan urutan sebelumnya terlebih dahulu',
                                              style: TextStyle(color: Colors.orange.shade700, fontSize: 10, fontStyle: FontStyle.italic),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                    if ((_selectedDay ?? _focusedDay).isAfter(DateTime.now().subtract(const Duration(days: 1))) && event['is_visited'] != true)
                                      IconButton(
                                        icon: const Icon(Icons.cancel_outlined, color: Colors.red),
                                        onPressed: () async {
                                          final confirm = await showDialog<bool>(
                                            context: context,
                                            builder: (ctx) => AlertDialog(
                                              backgroundColor: cardColor,
                                              title: Text('Batalkan Jadwal?', style: TextStyle(color: textColor)),
                                              content: Text('Apakah Anda yakin ingin membatalkan jadwal kunjungan ini?', style: TextStyle(color: subtitleColor)),
                                              actions: [
                                                TextButton(
                                                  onPressed: () => Navigator.pop(ctx, false),
                                                  child: const Text('Tidak', style: TextStyle(color: Colors.grey)),
                                                ),
                                                TextButton(
                                                  onPressed: () => Navigator.pop(ctx, true),
                                                  child: const Text('Ya, Batalkan', style: TextStyle(color: Colors.red)),
                                                ),
                                              ],
                                            ),
                                          );
                                          
                                          if (confirm == true) {
                                            final auth = Provider.of<AuthProvider>(context, listen: false);
                                            final success = await Provider.of<ItineraryProvider>(context, listen: false)
                                                .cancelItineraryItem(auth, event['id']);
                                                
                                            if (success && mounted) {
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                const SnackBar(content: Text('Jadwal berhasil dibatalkan'), backgroundColor: Colors.green),
                                              );
                                              // Refresh dashboard to update the 'Kunjungan Lapangan' section
                                              Provider.of<AttendanceProvider>(context, listen: false).loadDashboardData();
                                            }
                                          }
                                        },
                                      ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => AddItineraryScreen(
                initialDate: _selectedDay ?? _focusedDay,
              ),
            ),
          );
        },
        backgroundColor: primaryColor,
        elevation: 2,
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }
}
