import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:path_provider/path_provider.dart';

class ImageUtils {
  static Future<File?> compressAndGetWebP(File file) async {
    final Directory tempDir = await getTemporaryDirectory();
    final String targetPath = '${tempDir.path}/compressed_${DateTime.now().millisecondsSinceEpoch}.webp';

    final XFile? result = await FlutterImageCompress.compressAndGetFile(
      file.absolute.path,
      targetPath,
      quality: 75,
      minWidth: 1080,
      minHeight: 1080,
      format: CompressFormat.webp,
    );

    if (result != null) {
      return File(result.path);
    }
    return null;
  }

  static Future<String> addWatermarkAndCompress({
    required String imagePath,
    required String locationName,
    required String datetime,
    required String coordinates,
  }) async {
    // 1. Read original image
    final Uint8List bytes = await File(imagePath).readAsBytes();
    final ui.Codec codec = await ui.instantiateImageCodec(bytes);
    final ui.FrameInfo frameInfo = await codec.getNextFrame();
    final ui.Image image = frameInfo.image;

    // 2. Setup Canvas
    final ui.PictureRecorder recorder = ui.PictureRecorder();
    final ui.Canvas canvas = ui.Canvas(recorder);

    // Draw original image
    canvas.drawImage(image, Offset.zero, Paint());

    // 3. Define Watermark Text
    final double imageWidth = image.width.toDouble();
    final double imageHeight = image.height.toDouble();
    
    // Scale font size based on image width (e.g., 3.5% of width)
    final double fontSize = imageWidth * 0.035;
    final double padding = fontSize;

    final String watermarkText = '$locationName\n$datetime\n$coordinates';

    final ui.ParagraphBuilder paragraphBuilder = ui.ParagraphBuilder(
      ui.ParagraphStyle(
        textAlign: TextAlign.left,
        fontSize: fontSize,
        textDirection: TextDirection.ltr,
        height: 1.5,
      ),
    )
      ..pushStyle(ui.TextStyle(
        color: Colors.white,
        background: Paint()..color = Colors.transparent,
      ))
      ..addText(watermarkText);

    final ui.Paragraph paragraph = paragraphBuilder.build()
      ..layout(ui.ParagraphConstraints(width: imageWidth - (padding * 2)));

    // 4. Draw semi-transparent background at the bottom
    final double textHeight = paragraph.height;
    final double bgHeight = textHeight + (padding * 2);
    final Rect bgRect = Rect.fromLTWH(
      0,
      imageHeight - bgHeight,
      imageWidth,
      bgHeight,
    );
    canvas.drawRect(bgRect, Paint()..color = Colors.black.withOpacity(0.6));

    // 5. Draw text
    canvas.drawParagraph(paragraph, Offset(padding, imageHeight - bgHeight + padding));

    // 6. Convert Canvas back to Image Bytes
    final ui.Picture picture = recorder.endRecording();
    final ui.Image watermarkedImage = await picture.toImage(image.width, image.height);
    final ByteData? byteData = await watermarkedImage.toByteData(format: ui.ImageByteFormat.png);
    final Uint8List watermarkedBytes = byteData!.buffer.asUint8List();

    // 7. Compress to WebP
    final Directory tempDir = await getTemporaryDirectory();
    final String targetPath = '${tempDir.path}/attendance_${DateTime.now().millisecondsSinceEpoch}.webp';

    final Uint8List? compressedBytes = await FlutterImageCompress.compressWithList(
      watermarkedBytes,
      minWidth: 1080,
      minHeight: 1080,
      quality: 75,
      format: CompressFormat.webp,
    );

    if (compressedBytes != null) {
      final File file = File(targetPath);
      await file.writeAsBytes(compressedBytes);
      return targetPath;
    }

    // Fallback if compression fails
    return imagePath;
  }
}
