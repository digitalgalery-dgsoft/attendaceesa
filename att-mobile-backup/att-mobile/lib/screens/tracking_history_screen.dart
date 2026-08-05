import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:intl/intl.dart';

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
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now(),
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

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tracking History', style: TextStyle(fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.black),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today),
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
            color: const Color(0xFF0F52BA),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.route, color: Colors.white, size: 18),
                    const SizedBox(width: 8),
                    Text(dateLabel, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${_points.length} titik',
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
                ),
              ],
            ),
          ),

          // Map
          Expanded(
            flex: 3,
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
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
                              color: const Color(0xFF0F52BA),
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
                                          color: const Color(0xFF0F52BA),
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
                        : ListView.separated(
                            padding: const EdgeInsets.all(8),
                            itemCount: _points.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, index) {
                              final p = _points[index];
                              final isFirst = index == 0;
                              final isLast = index == _points.length - 1;
                              return ListTile(
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
                                          : const Color(0xFF0F52BA),
                                  size: isFirst || isLast ? 22 : 14,
                                ),
                                title: Text(
                                  '${p['latitude'].toStringAsFixed(6)}, ${p['longitude'].toStringAsFixed(6)}',
                                  style: const TextStyle(fontSize: 13, fontFamily: 'monospace'),
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
                              );
                            },
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.small(
        onPressed: _fetchHistory,
        child: const Icon(Icons.refresh),
        tooltip: 'Refresh',
      ),
    );
  }
}
