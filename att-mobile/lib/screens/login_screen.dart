import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';
import 'package:att_mobile/services/biometric_service.dart';
import 'package:att_mobile/screens/main_screen.dart';
import 'package:att_mobile/screens/server_config_screen.dart';
import 'package:att_mobile/screens/helpdesk_chat_screen.dart';
import 'package:toastification/toastification.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  bool _hasBiometricSaved = false;

  @override
  void initState() {
    super.initState();
    _checkBiometricLogin();
  }

  Future<void> _checkBiometricLogin() async {
    final isAvailable = await BiometricService.isBiometricAvailable();
    final isEnabled = await BiometricService.isBiometricEnabled();
    final savedEmail = await BiometricService.getSavedEmail();
    if (isAvailable && isEnabled) {
      if (mounted) {
        setState(() {
          _hasBiometricSaved = true;
          if (savedEmail != null && savedEmail.isNotEmpty && _emailController.text.isEmpty) {
            _emailController.text = savedEmail;
          }
        });
      }
    }
  }

  Future<void> _loginWithBiometrics() async {
    final locale = Provider.of<LocaleProvider>(context, listen: false);
    final auth = Provider.of<AuthProvider>(context, listen: false);

    final authenticated = await BiometricService.authenticate(
      localizedReason: locale.tr('biometric_prompt_login'),
    );

    if (authenticated && mounted) {
      // 1. Try with saved token first
      final savedToken = await BiometricService.getSavedToken();
      if (savedToken != null && savedToken.isNotEmpty) {
        final success = await auth.loginWithSavedToken(savedToken);
        if (!mounted) return;
        if (success) {
          toastification.show(
            context: context,
            title: Text(locale.tr('success')),
            description: const Text('Login Successful'),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 2),
          );
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => const MainScreen()),
          );
          return;
        }
      }

      // 2. Try with saved credentials (email + password)
      final savedCreds = await BiometricService.getSavedCredentials();
      if (savedCreds != null && savedCreds['password'] != null) {
        final result = await auth.login(savedCreds['email']!, savedCreds['password']!);
        if (!mounted) return;
        if (result['success']) {
          await BiometricService.saveCredentials(savedCreds['email']!, savedCreds['password']!, token: auth.token);
          toastification.show(
            context: context,
            title: Text(locale.tr('success')),
            description: const Text('Login Successful'),
            type: ToastificationType.success,
            autoCloseDuration: const Duration(seconds: 2),
          );
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => const MainScreen()),
          );
          return;
        }
      }

      // 3. If password or session wasn't saved yet, notify user to enter password once
      if (!mounted) return;
      toastification.show(
        context: context,
        title: Text(locale.tr('info')),
        description: Text(locale.isEnglish
            ? 'Please enter your password once to complete biometric linking.'
            : 'Silakan masukkan kata sandi sekali untuk menautkan login biometrik.'),
        type: ToastificationType.info,
        autoCloseDuration: const Duration(seconds: 3),
      );
    }
  }

  void _login() async {
    if (_formKey.currentState!.validate()) {
      final locale = Provider.of<LocaleProvider>(context, listen: false);
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final email = _emailController.text.trim();
      final password = _passwordController.text;

      final result = await auth.login(email, password);

      if (result['success']) {
        // Save credentials if biometric enabled
        final isBioEnabled = await BiometricService.isBiometricEnabled();
        if (isBioEnabled) {
          await BiometricService.saveCredentials(email, password, token: auth.token);
        }

        if (!mounted) return;
        toastification.show(
          context: context,
          title: Text(locale.tr('success')),
          description: const Text('Login Successful'),
          type: ToastificationType.success,
          autoCloseDuration: const Duration(seconds: 2),
        );
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const MainScreen()),
        );
      } else {
        if (!mounted) return;
        toastification.show(
          context: context,
          title: Text(locale.tr('error')),
          description: Text(result['message'] ?? 'Login failed'),
          type: ToastificationType.error,
          autoCloseDuration: const Duration(seconds: 3),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final locale = Provider.of<LocaleProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600;

    final inputDecoration = InputDecoration(
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF2A2A3D) : Colors.grey.shade50,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.grey.shade300),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: primaryColor, width: 1.5),
      ),
      labelStyle: TextStyle(color: subtitleColor),
    );

    return Scaffold(
      backgroundColor: isDarkMode ? const Color(0xFF121212) : const Color(0xFFF8F9FA),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Container(
            padding: const EdgeInsets.all(24.0),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 16,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Image.asset(
                      'assets/images/logo.jpeg',
                      height: 100,
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stackTrace) =>
                          Icon(Icons.fingerprint, size: 80, color: primaryColor),
                    ),
                  ),
                  const SizedBox(height: 24),
                  Text(
                    locale.tr('login_title'),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: textColor,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    locale.tr('login_subtitle'),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 14,
                      color: subtitleColor,
                    ),
                  ),
                  const SizedBox(height: 32),
                  TextFormField(
                    controller: _emailController,
                    validator: (value) => value!.isEmpty ? 'Email / NIK is required' : null,
                    style: TextStyle(color: textColor),
                    decoration: inputDecoration.copyWith(
                      labelText: 'Email / NIK',
                      hintText: locale.tr('login_email_hint'),
                      prefixIcon: Icon(Icons.person_outline, color: subtitleColor),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _passwordController,
                    validator: (value) => value!.isEmpty ? 'Password is required' : null,
                    obscureText: true,
                    style: TextStyle(color: textColor),
                    decoration: inputDecoration.copyWith(
                      labelText: 'Password',
                      hintText: locale.tr('login_password_hint'),
                      prefixIcon: Icon(Icons.lock_outline, color: subtitleColor),
                    ),
                  ),
                  const SizedBox(height: 24),
                  Consumer<AuthProvider>(
                    builder: (context, auth, child) {
                      return InkWell(
                        onTap: auth.isLoading ? null : _login,
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [primaryColor, primaryColor.withValues(alpha: 0.85)],
                              begin: Alignment.centerLeft,
                              end: Alignment.centerRight,
                            ),
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: primaryColor.withValues(alpha: 0.25),
                                blurRadius: 8,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          alignment: Alignment.center,
                          child: auth.isLoading
                              ? const SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                )
                              : Text(
                                  locale.tr('login_btn'),
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                                ),
                        ),
                      );
                    },
                  ),
                  if (_hasBiometricSaved) ...[
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: _loginWithBiometrics,
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        decoration: BoxDecoration(
                          color: primaryColor.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: primaryColor.withValues(alpha: 0.3)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.fingerprint_rounded, color: primaryColor, size: 22),
                            const SizedBox(width: 8),
                            Text(
                              locale.tr('login_with_biometrics'),
                              style: TextStyle(
                                color: primaryColor,
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 14),
                  // Helpdesk Live Chat Action Button
                  InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => HelpdeskChatScreen(
                            initialNik: _emailController.text.trim(),
                          ),
                        ),
                      );
                    },
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 14),
                      decoration: BoxDecoration(
                        color: primaryColor.withValues(alpha: 0.05),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: primaryColor.withValues(alpha: 0.2),
                        ),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: primaryColor.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Icon(Icons.support_agent_rounded, color: primaryColor, size: 20),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Kendala Login?',
                                  style: TextStyle(
                                    color: textColor,
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                const SizedBox(height: 1),
                                Text(
                                  'Bantuan Lupa Password / Unlock HP',
                                  style: TextStyle(
                                    color: primaryColor,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Icon(Icons.arrow_forward_ios_rounded, color: primaryColor.withValues(alpha: 0.5), size: 13),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const ServerConfigScreen(),
                        ),
                      );
                    },
                    icon: const Icon(Icons.settings_ethernet, size: 18),
                    label: Text(locale.tr('server_setting')),
                    style: TextButton.styleFrom(
                      foregroundColor: subtitleColor,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
