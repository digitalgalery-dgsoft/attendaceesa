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

  List<dynamic> _getEventsForDay(DateTime day, List<dynamic> itineraries) {
    final dateStr = DateFormat('yyyy-MM-dd').format(day);
    try {
      final itinerary = itineraries.firstWhere(
        (it) => it['date'] == dateStr,
      );
      return itinerary['items'] ?? [];
    } catch (e) {
      return [];
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
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                        child: Text(
                          'Jadwal Kunjungan: ${DateFormat('dd MMM yyyy').format(_selectedDay ?? _focusedDay)}',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: textColor,
                          ),
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
                                  
                              return Container(
                                margin: const EdgeInsets.only(bottom: 12),
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: cardColor,
                                  border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(10),
                                      decoration: BoxDecoration(
                                        color: primaryColor.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        '${index + 1}',
                                        style: TextStyle(
                                          color: primaryColor,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            locationName,
                                            style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 14),
                                          ),
                                          if (event['notes'] != null && event['notes'].toString().isNotEmpty) ...[
                                            const SizedBox(height: 4),
                                            Text(
                                              event['notes'],
                                              style: TextStyle(color: subtitleColor, fontSize: 12),
                                            ),
                                          ]
                                        ],
                                      ),
                                    ),
                                    if ((_selectedDay ?? _focusedDay).isAfter(DateTime.now().subtract(const Duration(days: 1))))
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
