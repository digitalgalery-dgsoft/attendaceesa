import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/tracking_history_screen.dart';
import 'package:percent_indicator/percent_indicator.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _endDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _fetchData();
    });
  }

  void _fetchData() {
    final dateFormat = DateFormat('yyyy-MM-dd');
    Provider.of<AttendanceProvider>(context, listen: false).fetchHistory(
      startDate: dateFormat.format(_startDate),
      endDate: dateFormat.format(_endDate),
    );
  }

  Future<void> _selectDateRange(BuildContext context) async {
    final DateTimeRange? picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
    );
    if (picked != null) {
      setState(() {
        _startDate = picked.start;
        _endDate = picked.end;
      });
      _fetchData();
    }
  }

  /// Parse jam dari string UTC → waktu lokal device
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

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'History',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.route, color: Colors.white),
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
                child: Column(
                  children: [
                    _buildHeader(attProvider, primaryColor),
                    _buildStatsRow(attProvider),
                    _buildHistoryList(attProvider),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildHeader(AttendanceProvider provider, Color primaryColor) {
    final stats = provider.stats;
    int plan = stats['plan'] ?? 0;
    int actual = stats['actual'] ?? 0;
    double ach = (stats['ach'] ?? 0).toDouble();

    return Container(
      color: Colors.white,
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Column(
        children: [
          // Curved background to match Permit
          Container(
            width: double.infinity,
            decoration: BoxDecoration(
              color: primaryColor,
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(24),
                bottomRight: Radius.circular(24),
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Row(
                children: [
                  InkWell(
                    onTap: () => _selectDateRange(context),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.calendar_today, size: 16, color: Colors.grey),
                          const SizedBox(width: 8),
                          Text(
                            '${DateFormat('dd MMM yy').format(_startDate)} s/d ${DateFormat('dd MMM yy').format(_endDate)}',
                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                          ),
                          const Icon(Icons.arrow_drop_down, color: Colors.grey),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _buildCircularStat('Plan', plan.toString(), 1.0, Colors.grey[300]!),
              _buildCircularStat('Actual', actual.toString(), plan > 0 ? actual / plan : 0, Colors.blue),
              _buildCircularStat('Ach', '$ach%', ach / 100, Colors.orange),
            ],
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  Widget _buildCircularStat(String title, String value, double percent, Color color) {
    return Column(
      children: [
        CircularPercentIndicator(
          radius: 35.0,
          lineWidth: 6.0,
          percent: percent > 1.0 ? 1.0 : (percent < 0 ? 0 : percent),
          center: Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
          ),
          progressColor: color,
          backgroundColor: Colors.grey[200]!,
          circularStrokeCap: CircularStrokeCap.round,
        ),
        const SizedBox(height: 8),
        Text(title, style: const TextStyle(color: Colors.grey, fontSize: 12)),
      ],
    );
  }

  Widget _buildStatsRow(AttendanceProvider provider) {
    final stats = provider.stats;
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(vertical: 16.0),
      margin: const EdgeInsets.only(top: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _buildSmallStat('Total Masuk', (stats['total_masuk'] ?? 0).toString(), Icons.login, Colors.blue),
          _buildSmallStat('Unique Store', (stats['unique_store'] ?? 0).toString(), Icons.store, Colors.purple),
          _buildSmallStat('No Out', (stats['no_out'] ?? 0).toString(), Icons.warning_amber, Colors.red),
          _buildSmallStat('<5 Menit', (stats['less_than_5_min'] ?? 0).toString(), Icons.timer, Colors.orange),
        ],
      ),
    );
  }

  Widget _buildSmallStat(String title, String value, IconData icon, Color color) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: color, size: 24),
        ),
        const SizedBox(height: 8),
        Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        Text(title, style: const TextStyle(color: Colors.grey, fontSize: 11)),
      ],
    );
  }

  Widget _buildHistoryList(AttendanceProvider provider) {
    final logsByDate = provider.logsByDate;
    if (logsByDate.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(32.0),
        child: Center(child: Text("Tidak ada data kunjungan")),
      );
    }

    var dates = logsByDate.keys.toList();
    dates.sort((a, b) => b.compareTo(a));

    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        children: dates.map((dateStr) {
          final logs = logsByDate[dateStr] as List;
          return _buildDateCard(dateStr, logs);
        }).toList(),
      ),
    );
  }

  Widget _buildDateCard(String dateStr, List logs) {
    String firstIn = '-';
    String lastOut = '-';

    var checkins = logs.where((l) => l['log_type'] == 'checkin').toList();
    if (checkins.isNotEmpty) {
      firstIn = _parseLocalTime(checkins.first['logged_at']);
    }

    var checkouts = logs.where((l) => l['log_type'] == 'checkout').toList();
    if (checkouts.isNotEmpty) {
      lastOut = _parseLocalTime(checkouts.last['logged_at']);
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ExpansionTile(
        initiallyExpanded: true,
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                DateFormat('dd').format(DateTime.parse(dateStr)),
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.blue),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    DateFormat('EEEE, MMM yyyy').format(DateTime.parse(dateStr)),
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.login, size: 14, color: Colors.green),
                      const SizedBox(width: 4),
                      Text(firstIn, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                      const SizedBox(width: 16),
                      const Icon(Icons.logout, size: 14, color: Colors.red),
                      const SizedBox(width: 4),
                      Text(lastOut, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
            child: Column(
              children: logs.map((log) => _buildLogItem(log)).toList(),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildLogItem(dynamic log) {
    final String type = log['log_type'] ?? '';
    final String time = _parseLocalTime(log['logged_at']);
    final String? rawPhotoUrl = log['photo_url'];
    final String? photoPath = log['photo_path'];
    final String? photoUrl = (photoPath != null && photoPath.isNotEmpty) 
        ? Constants.getImageUrl(photoPath) 
        : rawPhotoUrl;
    final String? note = log['note'];
    final String locationName = log['location']?['name'] as String? ?? '';

    IconData icon;
    Color color;
    String label;

    switch (type) {
      case 'checkin':
        icon = Icons.login;
        color = Colors.green;
        label = 'Check-in';
        break;
      case 'checkout':
        icon = Icons.logout;
        color = Colors.red;
        label = 'Check-out';
        break;
      case 'visit_in':
        icon = Icons.storefront;
        color = Colors.blue;
        label = 'Visit-in';
        break;
      case 'visit_out':
        icon = Icons.storefront_outlined;
        color = Colors.orange;
        label = 'Visit-out';
        break;
      default:
        icon = Icons.info_outline;
        color = Colors.grey;
        label = type;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.04),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withOpacity(0.15)),
      ),
      child: Column(
        children: [
          // ── Header row: icon + label + time ──────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 6),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.12),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, size: 16, color: color),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        label,
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                          color: color,
                        ),
                      ),
                      if (locationName.isNotEmpty)
                        Row(
                          children: [
                            Icon(Icons.location_on, size: 11, color: Colors.grey[600]),
                            const SizedBox(width: 2),
                            Expanded(
                              child: Text(
                                locationName,
                                style: TextStyle(
                                  color: Colors.grey[600],
                                  fontSize: 11,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
                // Time badge
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    time,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                      color: color,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ── Catatan / note ────────────────────────────────────────
          if (note != null && note.trim().isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.notes, size: 13, color: Colors.grey[500]),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      note.trim(),
                      style: TextStyle(fontSize: 11, color: Colors.grey[700]),
                    ),
                  ),
                ],
              ),
            ),

          // ── Foto ──────────────────────────────────────────────────
          if (photoUrl != null && photoUrl.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 10),
              child: Align(
                alignment: Alignment.centerLeft,
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
                    borderRadius: BorderRadius.circular(8),
                    child: Image.network(
                      photoUrl,
                      height: 50,
                      width: 50,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        height: 50, width: 50,
                        color: Colors.grey[200],
                        child: const Icon(Icons.broken_image, color: Colors.grey, size: 20),
                      ),
                      loadingBuilder: (_, child, progress) {
                        if (progress == null) return child;
                        return Container(
                          height: 50, width: 50,
                          color: Colors.grey[100],
                          child: const Center(
                            child: SizedBox(
                              height: 16, width: 16, 
                              child: CircularProgressIndicator(strokeWidth: 2)
                            )
                          ),
                        );
                      },
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
