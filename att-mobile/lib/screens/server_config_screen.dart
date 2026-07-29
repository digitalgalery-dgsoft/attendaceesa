import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/screens/login_screen.dart';
import 'package:toastification/toastification.dart';
import 'dart:convert';

class ServerConfigScreen extends StatefulWidget {
  const ServerConfigScreen({super.key});

  @override
  State<ServerConfigScreen> createState() => _ServerConfigScreenState();
}

class _ServerConfigScreenState extends State<ServerConfigScreen> {
  final _urlController = TextEditingController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _urlController.text = Constants.baseUrl.isNotEmpty 
        ? Constants.baseUrl.replaceAll('/api', '')
        : 'https://dgsoft.web.id';
  }

  Future<void> _testAndSaveUrl() async {
    String url = _urlController.text.trim();
    if (url.isEmpty) {
      toastification.show(
        context: context,
        title: const Text('URL cannot be empty'),
        type: ToastificationType.error,
        autoCloseDuration: const Duration(seconds: 3),
      );
      return;
    }

    // Ensure it doesn't end with slash
    if (url.endsWith('/')) {
      url = url.substring(0, url.length - 1);
    }
    
    // Auto add /api if not present
    String apiUrl = url;
    if (!apiUrl.endsWith('/api')) {
      apiUrl = '$url/api';
    }

    setState(() {
      _isLoading = true;
    });

    try {
      // Test the URL by fetching settings
      final response = await http.get(Uri.parse('$apiUrl/settings'))
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'success') {
          await Constants.setBaseUrl(apiUrl);
          
          if (!mounted) return;
          toastification.show(
            context: context,
            title: const Text('Connected successfully!'),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 2),
          );

          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const LoginScreen()),
          );
        } else {
          throw Exception('Invalid response format');
        }
      } else {
        throw Exception('Server returned status ${response.statusCode}');
      }
    } catch (e) {
      if (!mounted) return;
      toastification.show(
        context: context,
        title: const Text('Failed to connect to server'),
        description: Text(e.toString()),
        type: ToastificationType.error,
        autoCloseDuration: const Duration(seconds: 5),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(
                  Icons.dns_rounded,
                  size: 80,
                  color: Color(0xFF7367F0),
                ),
                const SizedBox(height: 24),
                const Text(
                  'Server Configuration',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Enter your company\'s server URL to connect',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 32),
                TextField(
                  controller: _urlController,
                  decoration: const InputDecoration(
                    labelText: 'Server URL',
                    hintText: 'https://example.com',
                    prefixIcon: Icon(Icons.link),
                  ),
                  keyboardType: TextInputType.url,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _testAndSaveUrl,
                    child: _isLoading
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text(
                            'Connect',
                            style: TextStyle(fontSize: 16),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
