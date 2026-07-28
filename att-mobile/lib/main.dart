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
import 'package:att_mobile/screens/login_screen.dart';
import 'package:att_mobile/screens/main_screen.dart';
import 'package:att_mobile/screens/server_config_screen.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:toastification/toastification.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Constants.loadBaseUrl();
  
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

class _AuthWrapperState extends State<AuthWrapper> {
  late Future<bool> _autoLoginFuture;

  @override
  void initState() {
    super.initState();
    if (Constants.baseUrl.isNotEmpty) {
      _autoLoginFuture = Provider.of<AuthProvider>(context, listen: false).tryAutoLogin();
    } else {
      _autoLoginFuture = Future.value(false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (Constants.baseUrl.isEmpty) {
      return const ServerConfigScreen();
    }

    return FutureBuilder(
      future: _autoLoginFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Scaffold(
            body: Center(
              child: CircularProgressIndicator(),
            ),
          );
        }
        
        return Consumer<AuthProvider>(
          builder: (context, auth, _) {
            if (auth.isAuthenticated) {
              return const MainScreen();
            }
            return const LoginScreen();
          },
        );
      },
    );
  }
}
