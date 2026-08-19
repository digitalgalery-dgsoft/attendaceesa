import 'dart:io';
import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:flutter/services.dart';

class LivenessCameraScreen extends StatefulWidget {
  const LivenessCameraScreen({Key? key}) : super(key: key);

  @override
  State<LivenessCameraScreen> createState() => _LivenessCameraScreenState();
}

class _LivenessCameraScreenState extends State<LivenessCameraScreen> {
  CameraController? _cameraController;
  final FaceDetector _faceDetector = FaceDetector(
    options: FaceDetectorOptions(
      enableClassification: true, // Untuk deteksi kedipan (mata terbuka)
      enableTracking: true,
      performanceMode: FaceDetectorMode.fast,
    ),
  );

  bool _isBusy = false;
  bool _canProcess = true;
  String _instruction = "Arahkan wajah ke kamera";
  bool _hasBlinked = false;
  bool _isEyesClosed = false;

  int _cameraIndex = -1;
  List<CameraDescription> _cameras = [];

  @override
  void initState() {
    super.initState();
    _initializeCamera();
  }

  Future<void> _initializeCamera() async {
    _cameras = await availableCameras();
    // Cari kamera depan
    for (var i = 0; i < _cameras.length; i++) {
      if (_cameras[i].lensDirection == CameraLensDirection.front) {
        _cameraIndex = i;
        break;
      }
    }

    if (_cameraIndex == -1) {
      if (_cameras.isNotEmpty) _cameraIndex = 0;
    }

    if (_cameras.isEmpty) {
      setState(() {
        _instruction = "Kamera tidak ditemukan";
      });
      return;
    }

    _cameraController = CameraController(
      _cameras[_cameraIndex],
      ResolutionPreset.medium,
      enableAudio: false,
      imageFormatGroup: Platform.isAndroid 
          ? ImageFormatGroup.nv21 
          : ImageFormatGroup.bgra8888,
    );

    try {
      await _cameraController!.initialize();
      if (!mounted) return;

      _cameraController!.startImageStream(_processCameraImage);
      setState(() {});
    } catch (e) {
      debugPrint("Error initializing camera: $e");
      setState(() {
        _instruction = "Gagal mengakses kamera";
      });
    }
  }

  Future<void> _processCameraImage(CameraImage image) async {
    if (!_canProcess || _isBusy) return;
    _isBusy = true;

    final inputImage = _inputImageFromCameraImage(image);
    if (inputImage == null) {
      _isBusy = false;
      return;
    }

    try {
      final faces = await _faceDetector.processImage(inputImage);
      if (faces.isEmpty) {
        if (mounted) {
          setState(() {
            _instruction = "Wajah tidak terdeteksi";
          });
        }
      } else {
        // Ambil wajah pertama (atau terbesar)
        final face = faces.first;
        final leftEyeOpen = face.leftEyeOpenProbability;
        final rightEyeOpen = face.rightEyeOpenProbability;

        if (leftEyeOpen != null && rightEyeOpen != null) {
          if (leftEyeOpen > 0.7 && rightEyeOpen > 0.7) {
            if (_isEyesClosed) {
              // Jika sebelumnya tertutup lalu terbuka lagi -> kedipan terjadi
              _hasBlinked = true;
              if (mounted) {
                setState(() {
                  _instruction = "Bagus! Memproses...";
                });
              }
              _captureAndReturn();
            } else {
              if (!_hasBlinked && mounted) {
                setState(() {
                  _instruction = "Silakan KEDIPKAN mata Anda";
                });
              }
            }
          } else if (leftEyeOpen < 0.2 && rightEyeOpen < 0.2) {
            _isEyesClosed = true;
          }
        }
      }
    } catch (e) {
      debugPrint("Face detection error: $e");
    }

    _isBusy = false;
  }

  Future<void> _captureAndReturn() async {
    _canProcess = false; // Hentikan deteksi frame
    await _cameraController?.stopImageStream();

    try {
      final XFile? file = await _cameraController?.takePicture();
      if (mounted) {
        Navigator.pop(context, file?.path);
      }
    } catch (e) {
      debugPrint("Gagal menjepret foto: $e");
      if (mounted) {
        Navigator.pop(context, null);
      }
    }
  }

  // Konversi CameraImage ke InputImage untuk ML Kit
  final _orientations = {
    DeviceOrientation.portraitUp: 0,
    DeviceOrientation.landscapeLeft: 90,
    DeviceOrientation.portraitDown: 180,
    DeviceOrientation.landscapeRight: 270,
  };

  InputImage? _inputImageFromCameraImage(CameraImage image) {
    if (_cameraController == null) return null;
    final camera = _cameras[_cameraIndex];
    final sensorOrientation = camera.sensorOrientation;
    
    InputImageRotation? rotation;
    if (Platform.isIOS) {
      rotation = InputImageRotationValue.fromRawValue(sensorOrientation);
    } else if (Platform.isAndroid) {
      var rotationCompensation = _orientations[_cameraController!.value.deviceOrientation];
      if (rotationCompensation == null) return null;
      if (camera.lensDirection == CameraLensDirection.front) {
        rotationCompensation = (sensorOrientation + rotationCompensation) % 360;
      } else {
        rotationCompensation = (sensorOrientation - rotationCompensation + 360) % 360;
      }
      rotation = InputImageRotationValue.fromRawValue(rotationCompensation);
    }
    if (rotation == null) return null;

    final format = InputImageFormatValue.fromRawValue(image.format.raw);
    if (format == null ||
        (Platform.isAndroid && format != InputImageFormat.nv21) ||
        (Platform.isIOS && format != InputImageFormat.bgra8888)) return null;

    if (image.planes.isEmpty) return null;

    return InputImage.fromBytes(
      bytes: Platform.isAndroid ? _concatenatePlanes(image.planes) : image.planes[0].bytes,
      metadata: InputImageMetadata(
        size: Size(image.width.toDouble(), image.height.toDouble()),
        rotation: rotation, // rotasi frame
        format: format,
        bytesPerRow: image.planes[0].bytesPerRow,
      ),
    );
  }

  Uint8List _concatenatePlanes(List<Plane> planes) {
    final WriteBuffer allBytes = WriteBuffer();
    for (final Plane plane in planes) {
      allBytes.putUint8List(plane.bytes);
    }
    return allBytes.done().buffer.asUint8List();
  }

  @override
  void dispose() {
    _canProcess = false;
    _faceDetector.close();
    _cameraController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text("Ambil Selfie"),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: Stack(
        fit: StackFit.expand,
        children: [
          if (_cameraController != null && _cameraController!.value.isInitialized)
            CameraPreview(_cameraController!),
          
          // Overlay berbentuk bulat untuk memandu wajah (opsional)
          ColorFiltered(
            colorFilter: ColorFilter.mode(Colors.black.withOpacity(0.5), BlendMode.srcOut),
            child: Stack(
              fit: StackFit.expand,
              children: [
                Container(
                  decoration: const BoxDecoration(
                    color: Colors.black,
                    backgroundBlendMode: BlendMode.dstOut,
                  ),
                ),
                Align(
                  alignment: const Alignment(0.0, -0.2),
                  child: Container(
                    height: 350,
                    width: 300,
                    decoration: BoxDecoration(
                      color: Colors.red,
                      borderRadius: BorderRadius.circular(200), // Bentuk oval/bulat
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Teks Instruksi
          Positioned(
            bottom: 60,
            left: 20,
            right: 20,
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.8),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                _instruction,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.black,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
