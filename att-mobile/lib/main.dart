import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/attendance_provider.dart';
import 'package:att_mobile/providers/permit_provider.dart';
import 'package:att_mobile/providers/notification_provider.dart';
import 'package:att_mobile/providers/sales_provider.dart';
import 'package:att_mobile/providers/itinerary_provider.dart';
import 'package:att_mobile/providers/theme_provider.dart';
import 'package:att_mobile/providers/blast_info_provider.dart';
import 'package:att_mobile/providers/dashboard_provider.dart';
import 'package:att_mobile/providers/payslip_provider.dart';
import 'package:att_mobile/providers/overtime_provider.dart';
import 'package:att_mobile/providers/chat_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';
import 'package:att_mobile/screens/login_screen.dart';
import 'package:att_mobile/screens/main_screen.dart';
import 'package:att_mobile/screens/server_config_screen.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:att_mobile/utils/update_manager.dart' as att_mobile_update_manager;
import 'package:att_mobile/services/location_service.dart';
import 'package:toastification/toastification.dart';
import 'package:safe_device/safe_device.dart';
import 'package:att_mobile/screens/security_warning_screen.dart';
import 'package:att_mobile/screens/splash_screen.dart';
import 'package:att_mobile/screens/onboarding_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:att_mobile/services/push_notification_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  try {
    await Firebase.initializeApp();
    await PushNotificationService().initialize();
  } catch (e) {
    debugPrint('Firebase init error: $e');
  }
  await Constants.loadBaseUrl();
  await LocationService.initializeService();
  
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => AttendanceProvider()),
        ChangeNotifierProvider(create: (_) => PermitProvider()),
        ChangeNotifierProvider(create: (_) => NotificationProvider()),
        ChangeNotifierProvider(create: (_) => SalesProvider()),
        ChangeNotifierProvider(create: (_) => ItineraryProvider()),
        ChangeNotifierProvider(create: (_) => ThemeProvider()),
        ChangeNotifierProvider(create: (_) => BlastInfoProvider()),
        ChangeNotifierProvider(create: (_) => DashboardProvider()),
        ChangeNotifierProvider(create: (_) => PayslipProvider()),
        ChangeNotifierProvider(create: (_) => OvertimeProvider()),
        ChangeNotifierProvider(create: (_) => ChatProvider()),
        ChangeNotifierProvider(create: (_) => LocaleProvider()),
      ],
      child: const AttendanceApp(),
    ),
  );
}

class AttendanceApp extends StatelessWidget {
  const AttendanceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ToastificationWrapper(
      child: Consumer2<ThemeProvider, AuthProvider>(
        builder: (context, themeProvider, authProvider, child) {
          final primaryColor = authProvider.appColor ?? const Color(0xFF7367F0);

          return MaterialApp(
            title: 'Attendance App',
            debugShowCheckedModeBanner: false,
            themeMode: themeProvider.themeMode,
            theme: ThemeData(
              primaryColor: primaryColor,
              scaffoldBackgroundColor: const Color(0xFFF9F9FF),
              appBarTheme: AppBarTheme(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                elevation: 0,
                iconTheme: const IconThemeData(color: Colors.white),
                titleTextStyle: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              colorScheme: ColorScheme.fromSeed(
                seedColor: primaryColor,
                primary: primaryColor,
                secondary: const Color(0xFFEA5455),
              ),
              useMaterial3: true,
              fontFamily: 'Inter',
              elevatedButtonTheme: ElevatedButtonThemeData(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
              inputDecorationTheme: InputDecorationTheme(
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
            darkTheme: ThemeData(
              brightness: Brightness.dark,
              primaryColor: primaryColor,
              scaffoldBackgroundColor: const Color(0xFF121212),
              appBarTheme: AppBarTheme(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                elevation: 0,
                iconTheme: const IconThemeData(color: Colors.white),
                titleTextStyle: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              colorScheme: ColorScheme.fromSeed(
                brightness: Brightness.dark,
                seedColor: primaryColor,
                primary: primaryColor,
                secondary: const Color(0xFFEA5455),
              ),
              useMaterial3: true,
              fontFamily: 'Inter',
              elevatedButtonTheme: ElevatedButtonThemeData(
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
              inputDecorationTheme: InputDecorationTheme(
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
            home: const AuthWrapper(),
          );
        },
      ),
    );
  }
}

class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> with WidgetsBindingObserver {
  late Future<bool> _initFuture;
  bool _isSecure = true;
  String _securityMessage = '';
  bool _hasSeenOnboarding = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initFuture = _initialize();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      if (Constants.baseUrl.isNotEmpty) {
        importUpdateManagerAndCheck(context);
      }
    }
  }

  Future<bool> _initialize() async {
    // 0. Delay for Splash Screen branding animation
    await Future.delayed(const Duration(milliseconds: 2500));

    // Check Onboarding status
    try {
      final prefs = await SharedPreferences.getInstance();
      _hasSeenOnboarding = prefs.getBool('has_seen_onboarding') ?? false;
    } catch (_) {}

    // 1. Security Checks
    try {
      bool isDevMode = await SafeDevice.isDevelopmentModeEnable;
      if (isDevMode) {
        _isSecure = false;
        _securityMessage = 'Mode Pengembang (Developer Options) atau USB Debugging terdeteksi aktif. Harap matikan Mode Pengembang di Pengaturan HP Anda untuk menggunakan aplikasi ini.';
        return false;
      }
      
      bool isMockLocation = await SafeDevice.isMockLocation;
      if (isMockLocation) {
        _isSecure = false;
        _securityMessage = 'Aplikasi Lokasi Palsu (Fake GPS) / Mock Location terdeteksi. Harap matikan pengaturan Lokasi Palsu untuk menggunakan aplikasi ini.';
        return false;
      }
    } catch (_) {
      // Continue if check is not supported
    }

    // 2. Auto Login
    if (Constants.baseUrl.isNotEmpty) {
      bool isLoggedIn = await Provider.of<AuthProvider>(context, listen: false).tryAutoLogin();
      
      // Check for updates
      WidgetsBinding.instance.addPostFrameCallback((_) {
        importUpdateManagerAndCheck(context);
      });
      return isLoggedIn;
    }
    
    return false;
  }

  void importUpdateManagerAndCheck(BuildContext context) {
     att_mobile_update_manager.UpdateManager.checkForUpdate(context);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<bool>(
      future: _initFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const SplashScreen();
        }

        if (!_isSecure) {
          return SecurityWarningScreen(
            title: 'Keamanan Terancam',
            message: _securityMessage,
            onRetry: () {
              setState(() {
                _isSecure = true;
                _initFuture = _initialize();
              });
            },
          );
        }
        
        if (Constants.baseUrl.isEmpty) {
          return const ServerConfigScreen();
        }
        
        return Consumer<AuthProvider>(
          builder: (context, auth, _) {
            if (auth.isAuthenticated) {
              return const MainScreen();
            }
            if (!_hasSeenOnboarding) {
              return const OnboardingScreen();
            }
            return const LoginScreen();
          },
        );
      },
    );
  }
}
