import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class ChatMessage {
  final int id;
  final String senderType;
  final String message;
  final bool isRead;
  final DateTime createdAt;

  ChatMessage({
    required this.id,
    required this.senderType,
    required this.message,
    required this.isRead,
    required this.createdAt,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'],
      senderType: json['sender_type'],
      message: json['message'],
      isRead: json['is_read'] == 1 || json['is_read'] == true,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class ChatProvider with ChangeNotifier {
  List<ChatMessage> _messages = [];
  bool _isLoading = false;
  String? _error;
  int _unreadCount = 0;
  Timer? _pollingTimer;
  Timer? _unreadPollingTimer;
  bool _isChatScreenOpen = false;

  List<ChatMessage> get messages => _messages;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get unreadCount => _unreadCount;

  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  /// Mulai polling pesan (saat layar chat terbuka)
  void startMessagePolling() {
    _isChatScreenOpen = true;
    stopMessagePolling(); // Stop existing if any
    _pollingTimer = Timer.periodic(const Duration(seconds: 2), (_) {
      fetchMessages(silent: true);
    });
  }

  /// Hentikan polling pesan (saat layar chat ditutup)
  void stopMessagePolling() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
  }

  /// Mulai polling unread count (berjalan selama app aktif)
  void startUnreadPolling() {
    stopUnreadPolling();
    _unreadPollingTimer = Timer.periodic(const Duration(seconds: 10), (_) {
      fetchUnreadCount();
    });
    // Also fetch immediately
    fetchUnreadCount();
  }

  /// Hentikan polling unread count
  void stopUnreadPolling() {
    _unreadPollingTimer?.cancel();
    _unreadPollingTimer = null;
  }

  @override
  void dispose() {
    stopMessagePolling();
    stopUnreadPolling();
    super.dispose();
  }

  Future<void> fetchMessages({bool silent = false}) async {
    if (!silent) {
      _isLoading = true;
      _error = null;
      notifyListeners();
    }

    try {
      final token = await _getToken();
      if (token == null) return;

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/chat/messages'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final newMessages = (data['data'] as List)
            .map((item) => ChatMessage.fromJson(item))
            .toList();

        // Only update and notify if content actually changed
        if (_messagesChanged(newMessages)) {
          _messages = newMessages;
          // Update unread count based on fetched messages
          _unreadCount = _messages
              .where((m) => m.senderType == 'admin' && !m.isRead)
              .length;
          notifyListeners();
        }
      } else {
        if (!silent) _error = 'Error ${response.statusCode}';
      }
    } catch (e) {
      debugPrint('[ChatProvider] fetchMessages error: $e');
      if (!silent) _error = e.toString();
    }

    if (!silent) {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Check if the messages list has changed (to avoid unnecessary rebuilds)
  bool _messagesChanged(List<ChatMessage> newMessages) {
    if (newMessages.length != _messages.length) return true;
    if (newMessages.isEmpty) return false;
    return newMessages.last.id != _messages.last.id ||
        newMessages.last.isRead != _messages.last.isRead;
  }

  /// Fetch only the unread count (lightweight call via dedicated endpoint)
  Future<void> fetchUnreadCount() async {
    try {
      final token = await _getToken();
      if (token == null) return;

      final response = await http.get(
        Uri.parse('${Constants.baseUrl}/chat/unread'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final newUnread = data['data']['unread_count'] as int? ?? 0;

        if (newUnread != _unreadCount) {
          _unreadCount = newUnread;
          notifyListeners();
        }
      }
    } catch (e) {
      debugPrint('[ChatProvider] fetchUnreadCount error: $e');
    }
  }

  Future<bool> sendMessage(String text) async {
    // Optimistic UI: add message immediately
    final tempMessage = ChatMessage(
      id: -1, // temporary ID
      senderType: 'employee',
      message: text,
      isRead: false,
      createdAt: DateTime.now(),
    );
    _messages.add(tempMessage);
    notifyListeners();

    try {
      final token = await _getToken();
      final url = Uri.parse('${Constants.baseUrl}/chat/send');

      debugPrint('[ChatProvider] Sending to: $url');

      final response = await http.post(
        url,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({'message': text}),
      ).timeout(const Duration(seconds: 15));

      debugPrint('[ChatProvider] Response: ${response.statusCode} - ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        // Replace temp message with real one from server
        _messages.removeWhere((m) => m.id == -1);
        final newMessage = ChatMessage.fromJson(data['data']);
        _messages.add(newMessage);
        notifyListeners();
        return true;
      }

      // Remove optimistic message on failure
      _messages.removeWhere((m) => m.id == -1);
      _error = 'Error ${response.statusCode}: ${response.body}';
      notifyListeners();
      return false;
    } catch (e) {
      debugPrint('[ChatProvider] sendMessage exception: $e');
      // Remove optimistic message on exception
      _messages.removeWhere((m) => m.id == -1);
      _error = e.toString();
      notifyListeners();
      return false;
    }
  }

  Future<void> markAsRead() async {
    try {
      final token = await _getToken();
      await http.post(
        Uri.parse('${Constants.baseUrl}/chat/read'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      ).timeout(const Duration(seconds: 8));
      
      _unreadCount = 0;
      notifyListeners();
    } catch (e) {
      debugPrint('[ChatProvider] markAsRead error: $e');
    }
  }
}
