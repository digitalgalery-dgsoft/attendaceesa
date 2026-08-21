import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';

class BiometricService {
  static final LocalAuthentication _auth = LocalAuthentication();

  static const String _keyBiometricEnabled = 'biometric_login_enabled';
  static const String _keyBiometricEmail = 'biometric_saved_email';
  static const String _keyBiometricPassword = 'biometric_saved_password';

  /// Check if device hardware supports biometrics and has enrolled fingerprints/face
  static Future<bool> isBiometricAvailable() async {
    try {
      final bool canAuthenticateWithBiometrics = await _auth.canCheckBiometrics;
      final bool isDeviceSupported = await _auth.isDeviceSupported();
      return canAuthenticateWithBiometrics && isDeviceSupported;
    } on PlatformException catch (e) {
      print('Biometric availability error: $e');
      return false;
    }
  }

  /// Get list of available biometric types (fingerprint, face, etc.)
  static Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _auth.getAvailableBiometrics();
    } on PlatformException {
      return [];
    }
  }

  /// Trigger biometric authentication prompt
  static Future<bool> authenticate({String? localizedReason}) async {
    try {
      final isAvailable = await isBiometricAvailable();
      if (!isAvailable) return false;

      return await _auth.authenticate(
        localizedReason: localizedReason ?? 'Scan your fingerprint or face to authenticate',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: false,
          useErrorDialogs: true,
        ),
      );
    } on PlatformException catch (e) {
      print('Biometric authentication failed: $e');
      return false;
    }
  }

  /// Check if user has enabled biometric login in settings
  static Future<bool> isBiometricEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_keyBiometricEnabled) ?? false;
  }

  /// Save biometric login state
  static Future<void> setBiometricEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_keyBiometricEnabled, enabled);
  }

  /// Save credentials for quick biometric login
  static Future<void> saveCredentials(String email, String password) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyBiometricEmail, email);
    await prefs.setString(_keyBiometricPassword, password);
  }

  /// Retrieve saved credentials
  static Future<Map<String, String>?> getSavedCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    final email = prefs.getString(_keyBiometricEmail);
    final password = prefs.getString(_keyBiometricPassword);
    if (email != null && email.isNotEmpty && password != null && password.isNotEmpty) {
      return {'email': email, 'password': password};
    }
    return null;
  }

  /// Clear saved credentials
  static Future<void> clearCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_keyBiometricEmail);
    await prefs.remove(_keyBiometricPassword);
    await prefs.remove(_keyBiometricEnabled);
  }
}
