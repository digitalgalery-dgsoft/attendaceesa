import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Helper dialog untuk memenuhi kepatuhan kebijakan Google Play:
/// Prominent In-App Location Disclosure
class LocationDisclosureHelper {
  static const String _keyAcknowledged = 'location_prominent_disclosure_acknowledged';

  /// Memeriksa apakah pengguna sudah menyetujui disclosure lokasi.
  /// Jika belum, menampilkan dialog edukasi sebelum dialog izin OS Android muncul.
  /// Mengembalikan `true` jika disetujui atau sudah pernah disetujui sebelumnya.
  static Future<bool> ensureDisclosure(BuildContext context) async {
    final prefs = await SharedPreferences.getInstance();
    final bool alreadyAcknowledged = prefs.getBool(_keyAcknowledged) ?? false;
    if (alreadyAcknowledged) return true;

    if (!context.mounted) return false;

    final bool? consented = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext ctx) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.location_on_rounded, color: Color(0xFF0F52BA), size: 28),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Akses Lokasi Presensi',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          content: const SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Aplikasi ESA Groups mengumpulkan data lokasi perangkat Anda untuk:',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                ),
                SizedBox(height: 8),
                Text(
                  '• Memvalidasi titik lokasi saat Check-In dan Check-Out presensi kerja.\n'
                  '• Mencatat rute kunjungan dinas (Live Tracking / Itinerary) saat jam kerja aktif.\n'
                  '• Memastikan kepatuhan radius penugasan kerja perusahaan.',
                  style: TextStyle(fontSize: 13, height: 1.4, color: Colors.black87),
                ),
                SizedBox(height: 10),
                Text(
                  'Data lokasi Anda dikumpulkan saat aplikasi dibuka maupun saat layanan pelacakan dinas berjalan dengan notifikasi aktif di status bar. Data tidak akan dibagikan kepada pihak ketiga.',
                  style: TextStyle(fontSize: 12, color: Colors.black54, height: 1.3),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: const Text('Tolak', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F52BA),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: () => Navigator.of(ctx).pop(true),
              child: const Text('Setuju & Lanjutkan'),
            ),
          ],
        );
      },
    );

    if (consented == true) {
      await prefs.setBool(_keyAcknowledged, true);
      return true;
    }
    return false;
  }
}
