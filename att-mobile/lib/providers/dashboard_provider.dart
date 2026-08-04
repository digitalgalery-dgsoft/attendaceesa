import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class DashboardProvider with ChangeNotifier {
  bool _isLoading = false;
  bool get isLoading => _isLoading;

  // Personal Stats
  String _position = 'Standar';
  int _targetHk = 0;
  int _runningRate = 0;
  int _kehadiran = 0;
  int _sakit = 0;
  int _cuti = 0;
  int _prevKehadiran = 0;

  String get position => _position;
  int get targetHk => _targetHk;
  int get runningRate => _runningRate;
  int get kehadiran => _kehadiran;
  int get sakit => _sakit;
  int get cuti => _cuti;
  int get prevKehadiran => _prevKehadiran;

  // Team Stats
  int _totalTeam = 0;
  int _hadirHariIni = 0;
  int _sakitHariIni = 0;
  int _cutiHariIni = 0;
  int _vacant = 0;
  int _teamTargetMandays = 0;
  int _teamRunningRate = 0;

  int get totalTeam => _totalTeam;
  int get hadirHariIni => _hadirHariIni;
  int get sakitHariIni => _sakitHariIni;
  int get cutiHariIni => _cutiHariIni;
  int get vacant => _vacant;
  int get teamTargetMandays => _teamTargetMandays;
  int get teamRunningRate => _teamRunningRate;

  Future<void> fetchDashboardStats() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) {
        _isLoading = false;
        notifyListeners();
        return;
      }

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/dashboard/stats'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _position = data['position'] ?? 'Standar';
        _targetHk = data['target_hk'] ?? 0;
        _runningRate = data['running_rate'] ?? 0;
        _kehadiran = data['kehadiran'] ?? 0;
        _sakit = data['sakit'] ?? 0;
        _cuti = data['cuti'] ?? 0;
        _prevKehadiran = data['prev_kehadiran'] ?? 0;
      }
    } catch (e) {
      debugPrint('Error fetching dashboard stats: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> fetchTeamStats() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      if (token == null) return;

      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/dashboard/team-stats'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        _totalTeam = data['total_team'] ?? 0;
        _hadirHariIni = data['hadir_hari_ini'] ?? 0;
        _sakitHariIni = data['sakit_hari_ini'] ?? 0;
        _cutiHariIni = data['cuti_hari_ini'] ?? 0;
        _vacant = data['vacant'] ?? 0;
        _teamTargetMandays = data['team_target_mandays'] ?? 0;
        _teamRunningRate = data['team_running_rate'] ?? 0;
        notifyListeners();
      }
    } catch (e) {
      debugPrint('Error fetching team stats: $e');
    }
  }
}
