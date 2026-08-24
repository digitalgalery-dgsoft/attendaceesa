class AppTranslations {
  static const Map<String, Map<String, String>> _localizedValues = {
    'en': {
      // General & Common
      'app_name': 'ESA Groups',
      'loading': 'Loading...',
      'save': 'Save',
      'cancel': 'Cancel',
      'close': 'Close',
      'success': 'Success',
      'error': 'Error',
      'warning': 'Warning',
      'info': 'Information',
      'confirm': 'Confirm',
      'delete': 'Delete',
      'search': 'Search...',
      'no_data': 'No data available',
      'retry': 'Try Again',

      // Greetings
      'good_morning': 'Good Morning',
      'good_afternoon': 'Good Afternoon',
      'good_evening': 'Good Evening',
      'good_night': 'Good Night',
      'greeting_user': 'Hello, {name}!',

      // Bottom Navigation
      'nav_home': 'Home',
      'nav_history': 'History',
      'nav_permit': 'Permit',
      'nav_sales': 'Sales',
      'nav_profile': 'Profile',

      // Dashboard Time Card
      'time_card_check_in': 'Check In',
      'time_card_check_out': 'Check Out',
      'time_card_working_hours': 'Working Hours',
      'time_card_schedule': 'Today Shift',
      'time_card_off': 'Day Off',
      'time_card_not_checked_in': 'Not Checked In',
      'time_card_checked_in': 'Checked In',
      'time_card_checked_out': 'Checked Out',
      'live_clock': 'Live Clock',

      // Fast Actions
      'action_attendance': 'Selfie Attendance',
      'action_visit': 'Field Visit',
      'action_start_visit': 'Start Visit',
      'action_finish_visit': 'Complete Visit',
      'action_visit_in_progress': 'Visit in Progress',
      'quick_menu': 'Quick Services',
      'other_menus': 'More Menus',

      // Quick Menus
      'menu_attendance': 'Attendance',
      'menu_visit': 'Visit',
      'menu_reporting': 'Reporting',
      'menu_permit': 'Permit',
      'menu_overtime': 'Overtime',
      'menu_announcement': 'Announcements',
      'menu_payslip': 'Payslip',
      'menu_sales': 'Sales',
      'menu_help': 'Help Center',
      'menu_more': 'More',

      // Reporting Hub & Dynamic Forms
      'reporting_hub_title': 'Field Reporting',
      'tab_templates': 'Forms',
      'tab_history': 'History',
      'select_store_location': 'Store / Outlet Location',
      'choose_registered_store': 'Select Registered Store',
      'search_store_hint': 'Search store name or address...',
      'store_not_found': 'No registered store found',
      'within_radius': 'Within Store Radius',
      'outside_radius': 'Outside Store Radius',
      'radius_info': '{distance} from {allowed} radius limit',
      'gps_connected': 'GPS Connected',
      'gps_searching': 'Acquiring GPS signal...',
      'btn_submit_report': 'Submit Report Now',
      'btn_submitting_report': 'Submitting Report...',
      'offline_queued_notice': 'Saved offline and will sync automatically when online.',
      'offline_queue_banner': '{count} report(s) saved offline',
      'btn_sync_now': 'Sync Now',
      'take_watermark_photo': 'Take Photo (Geotag Watermark)',
      'retake_photo': 'Retake Photo',
      'sign_pad_button': 'Sign Here',
      'resign_button': 'Resign',
      'required_field': 'Required',

      // Overview & Stats
      'attendance_overview': 'Attendance Overview',
      'stat_present': 'Present',
      'stat_late': 'Late',
      'stat_permit': 'Permit',
      'stat_absent': 'Absent',
      'team_monitoring': 'Team Activity',

      // Profile & Settings Screen
      'profile_title': 'My Profile',
      'profile_role': 'Role',
      'profile_dept': 'Department',
      'profile_branch': 'Branch Office',
      'sec_account_security': 'Account Security',
      'sec_app_preferences': 'App Preferences',
      'sec_help_policy': 'Help & Policies',
      'change_password': 'Change Password',
      'biometric_login': 'Biometric Login',
      'biometric_subtitle': 'Use Fingerprint or Face ID to sign in',
      'biometric_enabled': 'Enabled',
      'biometric_disabled': 'Disabled',
      'language': 'Language',
      'timezone': 'Time Zone',
      'dark_mode': 'Dark Mode',
      'dark_mode_sub': 'Toggle dark theme mode',
      'user_guide': 'App User Guide (Onboarding)',
      'help_center': 'Help Center & Contacts',
      'privacy_policy': 'Privacy Policy',
      'logout': 'Log Out',
      'logout_confirm': 'Are you sure you want to log out?',

      // Change Password Sheet
      'current_password': 'Old / Current Password',
      'new_password': 'New Password',
      'confirm_password': 'Confirm New Password',
      'save_password': 'Save Password',
      'password_mismatch': 'New passwords do not match',
      'password_updated': 'Password changed successfully',

      // Biometric Prompts
      'biometric_prompt_enable': 'Authenticate to enable Biometric Login',
      'biometric_prompt_login': 'Scan your fingerprint or face to sign in',
      'biometric_not_available': 'Biometric hardware is not available on this device',
      'biometric_not_enrolled': 'No fingerprints or face data registered on this device',
      'biometric_auth_failed': 'Biometric verification failed',
      'biometric_enabled_success': 'Biometric login successfully enabled',
      'biometric_disabled_success': 'Biometric login disabled',

      // Login Screen
      'login_title': 'Welcome Back!',
      'login_subtitle': 'Please enter your credentials to sign in',
      'login_email_hint': 'Enter your corporate email',
      'login_password_hint': 'Enter your password',
      'login_btn': 'Sign In',
      'login_with_biometrics': 'Sign in with Biometrics',
      'server_setting': 'Server URL Settings',

      // Attendance Screen
      'camera_scan_title': 'Selfie Camera Attendance',
      'camera_smile_hint': 'Please look at the camera and keep a steady pose',
      'camera_detecting': 'Detecting face...',
      'camera_face_found': 'Face detected! You can take attendance',
      'camera_face_not_found': 'Face not detected. Position your face in the frame',
      'submit_attendance': 'Submit Attendance',
      'attendance_success': 'Attendance recorded successfully!',

      // Permit Screen
      'permit_title': 'Permit & Leave Requests',
      'new_permit': 'New Request',
      'leave_quota': 'Leave Quota',
      'quota_remaining': 'Days Remaining',
      'permit_history': 'Request History',
      'permit_type': 'Type of Request',
      'permit_reason': 'Reason / Notes',
      'permit_start_date': 'Start Date',
      'permit_end_date': 'End Date',
      'permit_submit': 'Submit Request',
      'status_pending': 'Pending',
      'status_approved': 'Approved',
      'status_rejected': 'Rejected',

      // History Screen
      'history_title': 'Attendance History',
      'filter_month': 'Select Month',
      'total_work_days': 'Working Days',
      'total_hours': 'Total Hours',
      'clock_in_time': 'In',
      'clock_out_time': 'Out',

      // Offline & Sync
      'offline_banner': 'You are currently offline. Attendance will be saved locally and synced automatically when online.',
      'offline_saved': 'Saved offline. Will sync automatically once connected.',
      'sync_complete': 'All offline attendance data synchronized successfully.',
    },
    'id': {
      // General & Common
      'app_name': 'ESA Groups',
      'loading': 'Memuat...',
      'save': 'Simpan',
      'cancel': 'Batal',
      'close': 'Tutup',
      'success': 'Berhasil',
      'error': 'Terjadi Kesalahan',
      'warning': 'Peringatan',
      'info': 'Informasi',
      'confirm': 'Konfirmasi',
      'delete': 'Hapus',
      'search': 'Cari...',
      'no_data': 'Tidak ada data',
      'retry': 'Coba Lagi',

      // Greetings
      'good_morning': 'Selamat Pagi',
      'good_afternoon': 'Selamat Siang',
      'good_evening': 'Selamat Sore',
      'good_night': 'Selamat Malam',
      'greeting_user': 'Halo, {name}!',

      // Bottom Navigation
      'nav_home': 'Beranda',
      'nav_history': 'Riwayat',
      'nav_permit': 'Izin / Cuti',
      'nav_sales': 'Penjualan',
      'nav_profile': 'Profil',

      // Dashboard Time Card
      'time_card_check_in': 'Check In',
      'time_card_check_out': 'Check Out',
      'time_card_working_hours': 'Jam Kerja',
      'time_card_schedule': 'Jadwal Hari Ini',
      'time_card_off': 'Hari Libur',
      'time_card_not_checked_in': 'Belum Absen',
      'time_card_checked_in': 'Sudah Masuk',
      'time_card_checked_out': 'Sudah Pulang',
      'live_clock': 'Jam Sekarang',

      // Fast Actions
      'action_attendance': 'Presensi Selfie',
      'action_visit': 'Kunjungan Dinas',
      'action_start_visit': 'Mulai Kunjungan',
      'action_finish_visit': 'Selesai Kunjungan',
      'action_visit_in_progress': 'Kunjungan Berlangsung',
      'quick_menu': 'Layanan Cepat',
      'other_menus': 'Menu Lainnya',

      // Quick Menus
      'menu_attendance': 'Absensi',
      'menu_visit': 'Visit',
      'menu_reporting': 'Pelaporan',
      'menu_permit': 'Permit',
      'menu_overtime': 'Lembur',
      'menu_announcement': 'Informasi',
      'menu_payslip': 'Slip Gaji',
      'menu_sales': 'Sales',
      'menu_help': 'Pusat Bantuan',
      'menu_more': 'Lainnya',

      // Reporting Hub & Dynamic Forms
      'reporting_hub_title': 'Pusat Pelaporan',
      'tab_templates': 'Formulir',
      'tab_history': 'Riwayat',
      'select_store_location': 'Lokasi Toko / Outlet',
      'choose_registered_store': 'Pilih Toko Terdaftar',
      'search_store_hint': 'Cari nama toko atau alamat...',
      'store_not_found': 'Toko tidak ditemukan di master lokasi',
      'within_radius': 'Dalam Radius Toko',
      'outside_radius': 'Di Luar Radius Toko',
      'radius_info': '{distance} dari batas radius {allowed}',
      'gps_connected': 'GPS Terhubung',
      'gps_searching': 'Mencari sinyal GPS...',
      'btn_submit_report': 'Kirim Laporan Sekarang',
      'btn_submitting_report': 'Mengirim Laporan...',
      'offline_queued_notice': 'Tersimpan offline dan akan terkirim otomatis saat online.',
      'offline_queue_banner': '{count} laporan tersimpan di HP (Offline)',
      'btn_sync_now': 'Sync',
      'take_watermark_photo': 'Ambil Foto Bukti (Watermark Geotag)',
      'retake_photo': 'Ambil Ulang Foto',
      'sign_pad_button': 'Buat Tanda Tangan',
      'resign_button': 'Tanda Tangan Ulang',
      'required_field': 'Wajib diisi',

      // Overview & Stats
      'attendance_overview': 'Ringkasan Kehadiran',
      'stat_present': 'Hadir',
      'stat_late': 'Terlambat',
      'stat_permit': 'Izin/Cuti',
      'stat_absent': 'Alpha',
      'team_monitoring': 'Aktivitas Tim',

      // Profile & Settings Screen
      'profile_title': 'Profil Saya',
      'profile_role': 'Jabatan',
      'profile_dept': 'Divisi / Departemen',
      'profile_branch': 'Kantor Cabang',
      'sec_account_security': 'Keamanan Akun',
      'sec_app_preferences': 'Preferensi Aplikasi',
      'sec_help_policy': 'Bantuan & Kebijakan',
      'change_password': 'Ubah Kata Sandi',
      'biometric_login': 'Login Biometrik',
      'biometric_subtitle': 'Gunakan Sidik Jari atau Wajah untuk login',
      'biometric_enabled': 'Aktif',
      'biometric_disabled': 'Nonaktif',
      'language': 'Bahasa',
      'timezone': 'Zona Waktu',
      'dark_mode': 'Mode Gelap',
      'dark_mode_sub': 'Aktifkan tampilan tema gelap',
      'user_guide': 'Panduan Penggunaan (Onboarding)',
      'help_center': 'Pusat Bantuan & Kontak',
      'privacy_policy': 'Kebijakan Privasi',
      'logout': 'Keluar Akun',
      'logout_confirm': 'Apakah Anda yakin ingin keluar dari aplikasi?',

      // Change Password Sheet
      'current_password': 'Kata Sandi Saat Ini',
      'new_password': 'Kata Sandi Baru',
      'confirm_password': 'Konfirmasi Kata Sandi Baru',
      'save_password': 'Simpan Kata Sandi',
      'password_mismatch': 'Konfirmasi kata sandi tidak cocok',
      'password_updated': 'Kata sandi berhasil diperbarui',

      // Biometric Prompts
      'biometric_prompt_enable': 'Pindai sidik jari/wajah untuk mengaktifkan login biometrik',
      'biometric_prompt_login': 'Pindai sidik jari atau wajah untuk masuk',
      'biometric_not_available': 'Sensor biometrik tidak tersedia di perangkat ini',
      'biometric_not_enrolled': 'Belum ada sidik jari/wajah yang didaftarkan di perangkat ini',
      'biometric_auth_failed': 'Verifikasi biometrik gagal',
      'biometric_enabled_success': 'Login biometrik berhasil diaktifkan',
      'biometric_disabled_success': 'Login biometrik dinonaktifkan',

      // Login Screen
      'login_title': 'Selamat Datang Kembali!',
      'login_subtitle': 'Silakan masukkan akun Anda untuk melanjutkan',
      'login_email_hint': 'Masukkan email kantor Anda',
      'login_password_hint': 'Masukkan kata sandi Anda',
      'login_btn': 'Masuk Sekarang',
      'login_with_biometrics': 'Masuk dengan Biometrik',
      'server_setting': 'Pengaturan URL Server',

      // Attendance Screen
      'camera_scan_title': 'Presensi Kamera Selfie',
      'camera_smile_hint': 'Arahkan wajah ke kamera dengan tegak dan jelas',
      'camera_detecting': 'Mendeteksi wajah...',
      'camera_face_found': 'Wajah terdeteksi! Siap melakukan presensi',
      'camera_face_not_found': 'Wajah belum terdeteksi. Posisikan wajah di dalam frame',
      'submit_attendance': 'Kirim Presensi',
      'attendance_success': 'Presensi berhasil dicatat!',

      // Permit Screen
      'permit_title': 'Pengajuan Izin & Cuti',
      'new_permit': 'Buat Pengajuan',
      'leave_quota': 'Kuota Cuti',
      'quota_remaining': 'Sisa Hari',
      'permit_history': 'Riwayat Pengajuan',
      'permit_type': 'Jenis Pengajuan',
      'permit_reason': 'Alasan / Keterangan',
      'permit_start_date': 'Tanggal Mulai',
      'permit_end_date': 'Tanggal Selesai',
      'permit_submit': 'Kirim Permohonan',
      'status_pending': 'Menunggu',
      'status_approved': 'Disetujui',
      'status_rejected': 'Ditolak',

      // History Screen
      'history_title': 'Riwayat Kehadiran',
      'filter_month': 'Pilih Bulan',
      'total_work_days': 'Hari Kerja',
      'total_hours': 'Total Jam',
      'clock_in_time': 'Masuk',
      'clock_out_time': 'Pulang',

      // Offline & Sync
      'offline_banner': 'Perangkat dalam mode offline. Presensi disimpan sementara di memori lokal dan akan otomatis disinkronkan saat terhubung internet.',
      'offline_saved': 'Tersimpan offline. Akan otomatis disinkronkan ke server.',
      'sync_complete': 'Semua data presensi offline berhasil disinkronkan.',
    },
  };

  static String get(String key, {String lang = 'en', Map<String, String>? params}) {
    String text = _localizedValues[lang]?[key] ?? _localizedValues['en']?[key] ?? key;
    if (params != null) {
      params.forEach((paramKey, paramValue) {
        text = text.replaceAll('{$paramKey}', paramValue);
      });
    }
    return text;
  }
}
