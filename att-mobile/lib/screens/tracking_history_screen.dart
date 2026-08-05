import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';

class TrackingHistoryScreen extends StatefulWidget {
  const TrackingHistoryScreen({super.key});

  @override
  State<TrackingHistoryScreen> createState() => _TrackingHistoryScreenState();
}

class _TrackingHistoryScreenState extends State<TrackingHistoryScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _points = [];
  String _errorMessage = '';
  DateTime _selectedDate = DateTime.now();
  final MapController _mapController = MapController();

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/tracking/history?date=$dateStr'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final List<dynamic> raw = data['data'] ?? [];
        setState(() {
          _points = raw.map((e) => Map<String, dynamic>.from(e)).toList();
          _isLoading = false;
        });

        // Auto-center map to first point
        if (_points.isNotEmpty) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            _mapController.move(
              LatLng(_points.first['latitude'], _points.first['longitude']),
              14.0,
            );
          });
        }
      } else {
        setState(() {
          _errorMessage = 'Gagal memuat data tracking';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _pickDate() async {
    final primaryColor = Provider.of<AuthProvider>(context, listen: false).appColor ?? const Color(0xFF0F52BA);
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: primaryColor,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDate) {
      setState(() => _selectedDate = picked);
      _fetchHistory();
    }
  }

  List<LatLng> get _latLngs =>
      _points.map((p) => LatLng(p['latitude'], p['longitude'])).toList();

  @override
  Widget build(BuildContext context) {
    final dateLabel = DateFormat('dd MMMM yyyy').format(_selectedDate);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA);
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final authProvider = Provider.of<AuthProvider>(context);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        title: Text('Tracking History', style: TextStyle(fontWeight: FontWeight.bold, color: textColor)),
        backgroundColor: bgColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        actions: [
          IconButton(
            icon: Icon(Icons.calendar_today, color: textColor),
            onPressed: _pickDate,
            tooltip: 'Pilih Tanggal',
          ),
        ],
      ),
      body: Column(
        children: [
          // Date header + point count
          Container(
            width: double.infinity,
            color: primaryColor,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.route, color: Colors.white, size: 20),
                    const SizedBox(width: 8),
                    Text(dateLabel, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${_points.length} titik',
                    style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
          ),

          // Map
          Expanded(
            flex: 3,
            child: _isLoading
                ? Center(child: CircularProgressIndicator(color: primaryColor))
                : FlutterMap(
                    mapController: _mapController,
                    options: MapOptions(
                      initialCenter: _latLngs.isNotEmpty
                          ? _latLngs.first
                          : const LatLng(-7.2575, 112.7521), // Surabaya fallback
                      initialZoom: _latLngs.isNotEmpty ? 14.0 : 10.0,
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                        userAgentPackageName: 'com.example.att_mobile',
                      ),
                      if (_latLngs.length >= 2)
                        PolylineLayer(
                          polylines: [
                            Polyline(
                              points: _latLngs,
                              color: primaryColor,
                              strokeWidth: 3.5,
                            ),
                          ],
                        ),
                      if (_latLngs.isNotEmpty)
                        MarkerLayer(
                          markers: [
                            // Start marker (green)
                            Marker(
                              point: _latLngs.first,
                              width: 40,
                              height: 40,
                              child: const Icon(Icons.play_circle_fill, color: Colors.green, size: 36),
                            ),
                            // End marker (red) - only if more than one point
                            if (_latLngs.length > 1)
                              Marker(
                                point: _latLngs.last,
                                width: 40,
                                height: 40,
                                child: const Icon(Icons.stop_circle, color: Colors.red, size: 36),
                              ),
                            // Intermediate points
                            ..._latLngs.asMap().entries
                                .where((e) => e.key > 0 && e.key < _latLngs.length - 1)
                                .map((e) => Marker(
                                      point: e.value,
                                      width: 18,
                                      height: 18,
                                      child: Container(
                                        decoration: BoxDecoration(
                                          color: primaryColor,
                                          shape: BoxShape.circle,
                                          border: Border.all(color: Colors.white, width: 2),
                                        ),
                                      ),
                                    )),
                          ],
                        ),
                    ],
                  ),
          ),

          // Bottom list
          Expanded(
            flex: 2,
            child: _isLoading
                ? const SizedBox()
                : _errorMessage.isNotEmpty
                    ? Center(child: Text(_errorMessage, style: const TextStyle(color: Colors.red)))
                    : _points.isEmpty
                        ? const Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.location_off, size: 48, color: Colors.grey),
                                SizedBox(height: 8),
                                Text('Tidak ada data tracking untuk tanggal ini',
                                    style: TextStyle(color: Colors.grey)),
                              ],
                            ),
                          )
                        : Container(
                            color: bgColor,
                            child: ListView.builder(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              itemCount: _points.length,
                              itemBuilder: (context, index) {
                                final p = _points[index];
                                final isFirst = index == 0;
                                final isLast = index == _points.length - 1;
                                return Card(
                                  margin: const EdgeInsets.only(bottom: 8),
                                  color: isDarkMode ? const Color(0xFF1E1E2C) : Colors.white,
                                  elevation: 0,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                    side: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                                  ),
                                  child: ListTile(
                                    dense: true,
                                    leading: Icon(
                                      isFirst
                                          ? Icons.play_circle_fill
                                          : isLast
                                              ? Icons.stop_circle
                                              : Icons.circle,
                                      color: isFirst
                                          ? Colors.green
                                          : isLast
                                              ? Colors.red
                                              : primaryColor,
                                      size: isFirst || isLast ? 24 : 14,
                                    ),
                                    title: Text(
                                      '${p['latitude'].toStringAsFixed(6)}, ${p['longitude'].toStringAsFixed(6)}',
                                      style: TextStyle(fontSize: 13, fontFamily: 'monospace', color: textColor, fontWeight: FontWeight.w600),
                                    ),
                                    trailing: Text(
                                      p['created_at'] ?? '',
                                      style: const TextStyle(fontSize: 12, color: Colors.grey),
                                    ),
                                    onTap: () {
                                      _mapController.move(
                                        LatLng(p['latitude'], p['longitude']),
                                        16.0,
                                      );
                                    },
                                  ),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.small(
        onPressed: _fetchHistory,
        backgroundColor: primaryColor,
        elevation: 2,
        tooltip: 'Refresh',
        child: const Icon(Icons.refresh, color: Colors.white),
      ),
    );
  }
}
