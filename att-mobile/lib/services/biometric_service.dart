import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';

class BiometricService {
  static final LocalAuthentication _auth = LocalAuthentication();

  static const String _keyBiometricEnabled = 'biometric_login_enabled';
  static const String _keyBiometricEmail = 'biometric_saved_email';
  static const String _keyBiometricPassword = 'biometric_saved_password';
  static const String _keyBiometricToken = 'biometric_saved_token';

  /// Check if device hardware supports biometrics and has enrolled fingerprints/face
  static Future<bool> isBiometricAvailable() async {
    try {
      final bool canAuthenticateWithBiometrics = await _auth.canCheckBiometrics;
      final bool isDeviceSupported = await _auth.isDeviceSupported();
      return canAuthenticateWithBiometrics || isDeviceSupported;
    } on PlatformException {
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
    } on PlatformException {
      return false;
    }
  }

  /// Check if user has enabled biometric login in settings
  static Future<bool> isBiometricEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(_keyBiometricEnabled) ?? false;
  }

  /// Save biometric login state
  static Future<void> setBiometricEnabled(bool enabled, {String? email, String? token, String? password}) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_keyBiometricEnabled, enabled);
    if (enabled) {
      if (email != null && email.isNotEmpty) await prefs.setString(_keyBiometricEmail, email);
      if (token != null && token.isNotEmpty) await prefs.setString(_keyBiometricToken, token);
      if (password != null && password.isNotEmpty) await prefs.setString(_keyBiometricPassword, password);
    } else {
      await prefs.remove(_keyBiometricToken);
      await prefs.remove(_keyBiometricPassword);
    }
  }

  /// Save credentials for quick biometric login
  static Future<void> saveCredentials(String email, String password, {String? token}) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyBiometricEmail, email);
    await prefs.setString(_keyBiometricPassword, password);
    if (token != null && token.isNotEmpty) {
      await prefs.setString(_keyBiometricToken, token);
    }
  }

  /// Retrieve saved email
  static Future<String?> getSavedEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyBiometricEmail);
  }

  /// Retrieve saved token
  static Future<String?> getSavedToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyBiometricToken);
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
    await prefs.remove(_keyBiometricToken);
    await prefs.remove(_keyBiometricEnabled);
  }
}
