import 'dart:io';
import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:flutter/services.dart';

class LivenessCameraScreen extends StatefulWidget {
  final bool isRequired;

  const LivenessCameraScreen({
    Key? key,
    this.isRequired = true,
  }) : super(key: key);

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
  String _instruction = "Arahkan wajah ke dalam frame";
  bool _isFaceDetected = false;
  bool _hasBlinked = false;
  bool _isEyesClosed = false;
  bool _isCapturing = false;

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

    await _startCameraStream();
  }

  Future<void> _startCameraStream() async {
    if (_cameraIndex == -1 || _cameras.isEmpty) return;

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

      _canProcess = true;
      _cameraController!.startImageStream(_processCameraImage);
      setState(() {});
    } catch (e) {
      debugPrint("Error initializing camera: $e");
      if (mounted) {
        setState(() {
          _instruction = "Gagal mengakses kamera";
        });
      }
    }
  }

  Future<void> _switchCamera() async {
    if (_cameras.length < 2) return;
    _canProcess = false;
    await _cameraController?.stopImageStream();
    await _cameraController?.dispose();

    _cameraIndex = (_cameraIndex + 1) % _cameras.length;
    setState(() {
      _cameraController = null;
    });
    await _startCameraStream();
  }

  Future<void> _processCameraImage(CameraImage image) async {
    if (!_canProcess || _isBusy || _isCapturing) return;
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
            _isFaceDetected = false;
            _instruction = "Wajah tidak terdeteksi di dalam frame";
          });
        }
      } else {
        // Ambil wajah pertama (atau terbesar)
        final face = faces.first;
        final leftEyeOpen = face.leftEyeOpenProbability;
        final rightEyeOpen = face.rightEyeOpenProbability;

        if (mounted) {
          setState(() {
            _isFaceDetected = true;
          });
        }

        if (leftEyeOpen != null && rightEyeOpen != null) {
          if (leftEyeOpen > 0.65 || rightEyeOpen > 0.65) {
            if (_isEyesClosed) {
              // Jika sebelumnya tertutup lalu terbuka lagi -> kedipan terjadi
              _hasBlinked = true;
              if (mounted) {
                setState(() {
                  _instruction = "✨ Wajah Terverifikasi! Mengambil foto...";
                });
              }
              _captureAndReturn();
            } else {
              if (!_hasBlinked && mounted) {
                setState(() {
                  _instruction = "Posisikan wajah & KEDIPKAN mata 😉";
                });
              }
            }
          } else if (leftEyeOpen < 0.25 || rightEyeOpen < 0.25) {
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
    if (_isCapturing) return;
    _isCapturing = true;
    _canProcess = false; // Hentikan deteksi frame

    try {
      if (_cameraController != null && _cameraController!.value.isStreamingImages) {
        await _cameraController?.stopImageStream();
      }
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
        rotation: rotation,
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
    final isRequired = widget.isRequired;

    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        fit: StackFit.expand,
        children: [
          // ─── CAMERA PREVIEW ──────────────────────────────────────────────
          if (_cameraController != null && _cameraController!.value.isInitialized)
            Center(
              child: CameraPreview(_cameraController!),
            )
          else
            const Center(
              child: CircularProgressIndicator(color: Colors.white),
            ),

          // ─── CUTOUT OVERLAY DENGAN FRAME OVAL GLOWING ─────────────────────
          ColorFiltered(
            colorFilter: ColorFilter.mode(Colors.black.withValues(alpha: 0.55), BlendMode.srcOut),
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
                    width: 290,
                    decoration: BoxDecoration(
                      color: Colors.red,
                      borderRadius: BorderRadius.circular(160),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // ─── BORDER OVAL HIGHLIGHT ───────────────────────────────────────
          Align(
            alignment: const Alignment(0.0, -0.2),
            child: Container(
              height: 354,
              width: 294,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(160),
                border: Border.all(
                  color: _isFaceDetected 
                      ? (_hasBlinked ? Colors.greenAccent : const Color(0xFF38BDF8))
                      : Colors.white.withValues(alpha: 0.3),
                  width: 3,
                ),
                boxShadow: _isFaceDetected
                    ? [
                        BoxShadow(
                          color: (_hasBlinked ? Colors.greenAccent : const Color(0xFF38BDF8)).withValues(alpha: 0.4),
                          blurRadius: 16,
                          spreadRadius: 2,
                        )
                      ]
                    : null,
              ),
            ),
          ),

          // ─── TOP APP BAR & MODE BADGE ────────────────────────────────────
          Positioned(
            top: MediaQuery.of(context).padding.top + 8,
            left: 16,
            right: 16,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                CircleAvatar(
                  backgroundColor: Colors.black.withValues(alpha: 0.5),
                  child: IconButton(
                    icon: const Icon(Icons.close, color: Colors.white, size: 22),
                    onPressed: () => Navigator.pop(context, null),
                  ),
                ),

                // Liveness AI Mode Pill
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F52BA).withValues(alpha: 0.85),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: const Color(0xFF38BDF8).withValues(alpha: 0.6),
                      width: 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      )
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.face_retouching_natural,
                        color: Colors.white,
                        size: 15,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        isRequired ? 'Face AI: Wajib (Jabatan)' : 'Face AI: Liveness Active',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11.5,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.3,
                        ),
                      ),
                    ],
                  ),
                ),

                // Switch camera button
                if (_cameras.length > 1)
                  CircleAvatar(
                    backgroundColor: Colors.black.withValues(alpha: 0.5),
                    child: IconButton(
                      icon: const Icon(Icons.flip_camera_ios, color: Colors.white, size: 20),
                      onPressed: _switchCamera,
                    ),
                  )
                else
                  const SizedBox(width: 40),
              ],
            ),
          ),

          // ─── BOTTOM INSTRUCTIONS & CONTROLS ──────────────────────────────
          Positioned(
            bottom: 35,
            left: 20,
            right: 20,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Teks Instruksi Glassmorphism
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 20),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.75),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.15),
                      width: 1,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.4),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      )
                    ],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (_isFaceDetected)
                        const Padding(
                          padding: EdgeInsets.only(right: 8.0),
                          child: Icon(Icons.face, color: Color(0xFF38BDF8), size: 20),
                        ),
                      Flexible(
                        child: Text(
                          _instruction,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: _isFaceDetected ? Colors.white : Colors.white70,
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 12),

                Text(
                  'Posisikan wajah Anda tepat di dalam lingkaran dan kedipkan mata.',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 11),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
