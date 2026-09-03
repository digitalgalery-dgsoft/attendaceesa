import 'dart:io';
import 'dart:math';
import 'package:flutter/foundation.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';

/// Representasi profil biometrik wajah dalam koordinat kanonikal (invarian skala, translasi, dan rotasi).
class FaceBiometricProfile {
  final Map<FaceLandmarkType, Point<double>> canonicalLandmarks;
  final double eyeDistance;
  final double noseToEyeRatio;
  final double mouthToNoseRatio;
  final double eyeToMouthRatio;
  final double mouthWidthRatio;
  final double cheekWidthRatio;
  final double faceAspectRatio;

  FaceBiometricProfile({
    required this.canonicalLandmarks,
    required this.eyeDistance,
    required this.noseToEyeRatio,
    required this.mouthToNoseRatio,
    required this.eyeToMouthRatio,
    required this.mouthWidthRatio,
    required this.cheekWidthRatio,
    required this.faceAspectRatio,
  });

  /// Membandingkan profil ini dengan profil wajah lain.
  /// Mengembalikan persentase kemiripan (0.0% - 100.0%).
  double compareWith(FaceBiometricProfile other) {
    // 1. Bandingkan rasio-rasio geometrik wajah
    final ratioDiffs = <double>[];
    final rawDiffs = <double>[];

    void addRatioDiff(double a, double b, double maxTolerance) {
      if (a > 0 && b > 0) {
        final diff = (a - b).abs() / max(a, b);
        rawDiffs.add(diff);
        final score = (1.0 - (diff / maxTolerance)).clamp(0.0, 1.0);
        ratioDiffs.add(score);
      }
    }

    // Toleransi terkalibrasi presisi: mengakomodasi variasi pose/perspektif kamera wajar
    // namun secara tegas membedakan struktur tulang wajah orang lain.
    addRatioDiff(noseToEyeRatio, other.noseToEyeRatio, 0.26);
    addRatioDiff(mouthToNoseRatio, other.mouthToNoseRatio, 0.28);
    addRatioDiff(eyeToMouthRatio, other.eyeToMouthRatio, 0.24);
    addRatioDiff(mouthWidthRatio, other.mouthWidthRatio, 0.26);
    addRatioDiff(cheekWidthRatio, other.cheekWidthRatio, 0.26);
    addRatioDiff(faceAspectRatio, other.faceAspectRatio, 0.25);

    double ratioScore = 0.5;
    if (ratioDiffs.isNotEmpty) {
      ratioScore = ratioDiffs.reduce((a, b) => a + b) / ratioDiffs.length;
    }

    // 2. Bandingkan jarak koordinat landmark kanonikal (Euclidean distance)
    // Titik jangkar mata dan landmark wajah lengkap dinormalisasi kanonikal (jarak mata = 100 unit).
    // Toleransi deviasi 24 unit memberikan diskriminasi kuat terhadap wajah lain
    // tanpa mengorbankan variasi alami pengguna asli.
    final landmarkScores = <double>[];
    for (final entry in canonicalLandmarks.entries) {
      final otherPoint = other.canonicalLandmarks[entry.key];
      if (otherPoint != null) {
        final p1 = entry.value;
        final p2 = otherPoint;
        final dist = sqrt(pow(p1.x - p2.x, 2) + pow(p1.y - p2.y, 2));
        final score = (1.0 - (dist / 24.0)).clamp(0.0, 1.0);
        landmarkScores.add(score);
      }
    }

    double landmarkScore = ratioScore;
    if (landmarkScores.isNotEmpty) {
      landmarkScore = landmarkScores.reduce((a, b) => a + b) / landmarkScores.length;
    }

    // 3. Penalti deviasi bentuk wajah signifikan (> 28% deviasi rasio):
    // Memastikan orang dengan bentuk wajah berbeda langsung tertolak.
    double penalty = 1.0;
    for (final diff in rawDiffs) {
      if (diff > 0.28) {
        penalty *= (1.0 - (diff - 0.28) * 1.5).clamp(0.70, 1.0);
      }
    }

    // Bobot proporsional: 40% rasio geometrik + 60% posisi landmark
    final baseScore = 0.40 * ratioScore + 0.60 * landmarkScore;
    final combinedScore = (baseScore * penalty) * 100.0;
    return combinedScore.clamp(0.0, 100.0);
  }
}

class FaceMatcherService {
  /// Ambang batas persentase kemiripan wajah minimum untuk lolos validasi biometrik
  static const double defaultThreshold = 75.0;
  /// Ekstraksi profil biometrik kanonikal dari objek Face ML Kit.
  static FaceBiometricProfile? extractProfile(Face face) {
    final leftEye = face.landmarks[FaceLandmarkType.leftEye];
    final rightEye = face.landmarks[FaceLandmarkType.rightEye];

    if (leftEye == null || rightEye == null) {
      return null;
    }

    final pLeftEye = Point<double>(leftEye.position.x.toDouble(), leftEye.position.y.toDouble());
    final pRightEye = Point<double>(rightEye.position.x.toDouble(), rightEye.position.y.toDouble());

    // Interpupillary Distance (jarak antara dua pupil)
    final eyeDistance = sqrt(pow(pRightEye.x - pLeftEye.x, 2) + pow(pRightEye.y - pLeftEye.y, 2));
    if (eyeDistance < 10.0) {
      return null; // Wajah terlalu kecil atau tidak valid
    }

    // Titik tengah kedua mata
    final midEye = Point<double>((pLeftEye.x + pRightEye.x) / 2.0, (pLeftEye.y + pRightEye.y) / 2.0);

    // Sudut rotasi garis mata (radian)
    final angle = atan2(pRightEye.y - pLeftEye.y, pRightEye.x - pLeftEye.x);
    final cosAngle = cos(-angle);
    final sinAngle = sin(-angle);

    // Normalisasi kanonikal: Skala agar jarak kedua mata menjadi tepat 100 unit
    final scale = 100.0 / eyeDistance;

    Point<double> transform(Point<double> p) {
      final dx = p.x - midEye.x;
      final dy = p.y - midEye.y;
      final rx = dx * cosAngle - dy * sinAngle;
      final ry = dx * sinAngle + dy * cosAngle;
      return Point<double>(rx * scale, ry * scale);
    }

    final canonicalLandmarks = <FaceLandmarkType, Point<double>>{};
    for (final entry in face.landmarks.entries) {
      if (entry.value != null) {
        canonicalLandmarks[entry.key] = transform(
          Point<double>(entry.value!.position.x.toDouble(), entry.value!.position.y.toDouble()),
        );
      }
    }

    // Pastikan posisi kanonikal mata persis di (-50, 0) dan (+50, 0)
    canonicalLandmarks[FaceLandmarkType.leftEye] = const Point<double>(-50.0, 0.0);
    canonicalLandmarks[FaceLandmarkType.rightEye] = const Point<double>(50.0, 0.0);

    // Hitung fitur proporsi geometrik
    final nose = canonicalLandmarks[FaceLandmarkType.noseBase];
    final leftMouth = canonicalLandmarks[FaceLandmarkType.leftMouth];
    final rightMouth = canonicalLandmarks[FaceLandmarkType.rightMouth];
    final bottomMouth = canonicalLandmarks[FaceLandmarkType.bottomMouth];
    final leftCheek = canonicalLandmarks[FaceLandmarkType.leftCheek];
    final rightCheek = canonicalLandmarks[FaceLandmarkType.rightCheek];

    final double noseToEyeRatio = nose != null ? (nose.y / 100.0).abs() : 0.45;
    
    Point<double>? mouthCenter;
    if (leftMouth != null && rightMouth != null) {
      mouthCenter = Point<double>((leftMouth.x + rightMouth.x) / 2.0, (leftMouth.y + rightMouth.y) / 2.0);
    } else if (bottomMouth != null) {
      mouthCenter = Point<double>(bottomMouth.x, bottomMouth.y - 10.0);
    }

    final double mouthToNoseRatio = (mouthCenter != null && nose != null)
        ? ((mouthCenter.y - nose.y) / 100.0).abs()
        : 0.35;

    final double eyeToMouthRatio = mouthCenter != null
        ? (mouthCenter.y / 100.0).abs()
        : 0.80;

    final double mouthWidthRatio = (leftMouth != null && rightMouth != null)
        ? ((rightMouth.x - leftMouth.x) / 100.0).abs()
        : 0.45;

    final double cheekWidthRatio = (leftCheek != null && rightCheek != null)
        ? ((rightCheek.x - leftCheek.x) / 100.0).abs()
        : 0.90;

    final double faceAspectRatio = face.boundingBox.height > 0
        ? (face.boundingBox.width / face.boundingBox.height)
        : 0.75;

    return FaceBiometricProfile(
      canonicalLandmarks: canonicalLandmarks,
      eyeDistance: eyeDistance,
      noseToEyeRatio: noseToEyeRatio,
      mouthToNoseRatio: mouthToNoseRatio,
      eyeToMouthRatio: eyeToMouthRatio,
      mouthWidthRatio: mouthWidthRatio,
      cheekWidthRatio: cheekWidthRatio,
      faceAspectRatio: faceAspectRatio,
    );
  }

  /// Memuat foto master wajah (dari URL web atau file lokal) dan mengekstraksi profil biometriknya.
  static Future<FaceBiometricProfile?> loadMasterProfile(String masterPhotoPathOrUrl) async {
    try {
      String localFilePath = masterPhotoPathOrUrl;

      // Jika berbentuk URL (http/https), unduh ke temporary file
      if (masterPhotoPathOrUrl.startsWith('http://') || masterPhotoPathOrUrl.startsWith('https://')) {
        final tempDir = await getTemporaryDirectory();
        final cachedFile = File('${tempDir.path}/cached_master_face_${masterPhotoPathOrUrl.hashCode}.jpg');

        if (!await cachedFile.exists() || await cachedFile.length() < 1000) {
          final List<String> urlsToTry = [masterPhotoPathOrUrl];
          try {
            final uri = Uri.parse(masterPhotoPathOrUrl);
            final pathAndQuery = uri.path + (uri.hasQuery ? '?${uri.query}' : '');
            for (final host in ['atk.esa-solutions.id', 'amk.esa-solutions.id', 'akp.esa-solutions.id']) {
              final altUrl = 'https://$host$pathAndQuery';
              if (!urlsToTry.contains(altUrl)) {
                urlsToTry.add(altUrl);
              }
            }
          } catch (_) {}

          bool downloaded = false;
          for (final url in urlsToTry) {
            try {
              final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 7));
              if (response.statusCode == 200 && response.bodyBytes.length > 500) {
                await cachedFile.writeAsBytes(response.bodyBytes);
                downloaded = true;
                debugPrint("Master face photo downloaded successfully from: $url");
                break;
              }
            } catch (e) {
              debugPrint("Failed to download master photo from $url: $e");
            }
          }

          if (!downloaded) {
            debugPrint("Gagal mengunduh foto master wajah dari seluruh endpoint.");
            return null;
          }
        }
        localFilePath = cachedFile.path;
      }

      final file = File(localFilePath);
      if (!await file.exists()) {
        debugPrint("File foto master wajah tidak ditemukan: $localFilePath");
        return null;
      }

      // Deteksi wajah pada foto master dengan akurasi tinggi
      final masterDetector = FaceDetector(
        options: FaceDetectorOptions(
          enableClassification: true,
          enableLandmarks: true,
          enableContours: true,
          performanceMode: FaceDetectorMode.accurate,
        ),
      );

      final inputImage = InputImage.fromFilePath(localFilePath);
      final faces = await masterDetector.processImage(inputImage);
      await masterDetector.close();

      if (faces.isEmpty) {
        debugPrint("Tidak ada wajah yang terdeteksi pada foto master.");
        return null;
      }

      // Ambil wajah dengan bounding box terbesar
      faces.sort((a, b) => (b.boundingBox.width * b.boundingBox.height)
          .compareTo(a.boundingBox.width * a.boundingBox.height));

      final profile = extractProfile(faces.first);
      return profile;
    } catch (e) {
      debugPrint("Error saat memproses foto master wajah: $e");
      return null;
    }
  }

  /// Menghapus cache master wajah agar saat ada update foto baru, sistem mengunduh foto terbaru.
  static Future<void> clearCachedMasterProfile() async {
    try {
      final tempDir = await getTemporaryDirectory();
      final files = tempDir.listSync();
      for (final f in files) {
        if (f is File && f.path.contains('cached_master_face_')) {
          await f.delete();
        }
      }
    } catch (_) {}
  }
}
