import 'package:flutter/material.dart';

class UpdateManager {
  static bool _isChecking = false;
  static bool _isDialogShowing = false;

  /// Fitur self-update / dialog in-app update dinonaktifkan sepenuhnya.
  /// Seluruh pembaruan aplikasi ditangani melalui Google Play Store.
  static Future<void> checkForUpdate(BuildContext context, {String? authToken}) async {
    _isChecking = false;
    _isDialogShowing = false;
    return;
  }
}
