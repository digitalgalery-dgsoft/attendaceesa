import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/blast_info.dart';
import '../utils/constants.dart';

class BlastInfoProvider with ChangeNotifier {
  List<BlastInfo> _blastInfos = [];
  bool _isLoading = false;

  List<BlastInfo> get blastInfos => _blastInfos;
  bool get isLoading => _isLoading;

  Future<void> fetchBlastInfos() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/blast-infos'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          final List<dynamic> list = data['data'];
          _blastInfos = list.map((json) {
            try {
              return BlastInfo.fromJson(json);
            } catch (e) {
              debugPrint('Failed to parse blast info: $e');
              return null;
            }
          }).where((info) => info != null).cast<BlastInfo>().toList();
          
          debugPrint('Successfully loaded ${_blastInfos.length} blast infos');
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('Error fetching blast infos: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<List<BlastInfo>> getUnreadInfos() async {
    final prefs = await SharedPreferences.getInstance();
    final readIds = prefs.getStringList('read_blast_infos') ?? [];

    return _blastInfos.where((info) => !readIds.contains(info.id.toString())).toList();
  }

  Future<void> markAsRead(int id) async {
    final prefs = await SharedPreferences.getInstance();
    final readIds = prefs.getStringList('read_blast_infos') ?? [];
    
    if (!readIds.contains(id.toString())) {
      readIds.add(id.toString());
      await prefs.setStringList('read_blast_infos', readIds);
      notifyListeners();
    }
  }
}
