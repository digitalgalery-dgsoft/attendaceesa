import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'auth_provider.dart';

class NotificationProvider with ChangeNotifier {
  List<dynamic> _notifications = [];
  bool _isLoading = false;

  List<dynamic> get notifications => _notifications;
  bool get isLoading => _isLoading;
  int get unreadCount => _notifications.where((n) => n['read_at'] == null).length;

  Future<void> fetchNotifications(AuthProvider authProvider) async {
    if (authProvider.token == null) return;

    _isLoading = true;
    notifyListeners();

    try {
      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/notifications'),
        headers: {
          'Authorization': 'Bearer ${authProvider.token}',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          _notifications = data['data'];
        }
      }
    } catch (e) {
      print('Error fetching notifications: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> markAsRead(AuthProvider authProvider, {String? notificationId}) async {
    if (authProvider.token == null) return;

    try {
      final body = notificationId != null ? {'notification_id': notificationId} : {};
      final response = await http.post(
        Uri.parse('${Constants.baseUrl}/notifications/read'),
        headers: {
          'Authorization': 'Bearer ${authProvider.token}',
          'Accept': 'application/json',
        },
        body: body,
      );

      if (response.statusCode == 200) {
        if (notificationId != null) {
          final index = _notifications.indexWhere((n) => n['id'] == notificationId);
          if (index != -1) {
            _notifications[index]['read_at'] = DateTime.now().toIso8601String();
            notifyListeners();
          }
        } else {
          for (var n in _notifications) {
            if (n['read_at'] == null) {
              n['read_at'] = DateTime.now().toIso8601String();
            }
          }
          notifyListeners();
        }
      }
    } catch (e) {
      print('Error marking notification as read: $e');
    }
  }
}

