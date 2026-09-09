import 'package:flutter/material.dart';

/// Custom Loading Indicator featuring the ESA Running Mascot (`loadinglogo.png`)
/// with smooth pulsing & floating micro-animations.
class CustomLoadingIndicator extends StatefulWidget {
  final double size;
  final String? message;
  final Color? textColor;
  final bool showBackgroundCard;
  final Color? cardColor;

  const CustomLoadingIndicator({
    super.key,
    this.size = 80.0,
    this.message,
    this.textColor,
    this.showBackgroundCard = false,
    this.cardColor,
  });

  /// Static helper to display a non-dismissible loading dialog with custom mascot
  static Future<T?> show<T>(
    BuildContext context, {
    String message = 'Mohon tunggu...',
  }) {
    return showDialog<T>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => PopScope(
        canPop: false,
        child: Dialog(
          backgroundColor: Colors.transparent,
          elevation: 0,
          insetPadding: const EdgeInsets.symmetric(horizontal: 40),
          child: CustomLoadingIndicator(
            size: 96.0,
            message: message,
            showBackgroundCard: true,
          ),
        ),
      ),
    );
  }

  /// Static helper to dismiss the loading dialog
  static void hide(BuildContext context) {
    if (Navigator.of(context, rootNavigator: true).canPop()) {
      Navigator.of(context, rootNavigator: true).pop();
    }
  }

  @override
  State<CustomLoadingIndicator> createState() => _CustomLoadingIndicatorState();
}

class _CustomLoadingIndicatorState extends State<CustomLoadingIndicator>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnim;
  late Animation<double> _glowAnim;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..repeat(reverse: true);

    _scaleAnim = Tween<double>(begin: 0.94, end: 1.06).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOutCubic),
    );

    _glowAnim = Tween<double>(begin: 0.15, end: 0.45).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOutSine),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final defaultTextColor = isDark ? Colors.white70 : const Color(0xFF1E293B);

    Widget content = Column(
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // Mascot Logo with Pulse & Glow Effect
        AnimatedBuilder(
          animation: _controller,
          builder: (context, child) {
            return Transform.scale(
              scale: _scaleAnim.value,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  // Subtle Radial Glow behind the circular track
                  Container(
                    width: widget.size * 0.9,
                    height: widget.size * 0.9,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF0F52BA).withValues(alpha: _glowAnim.value),
                          blurRadius: widget.size * 0.35,
                          spreadRadius: widget.size * 0.05,
                        ),
                        BoxShadow(
                          color: const Color(0xFF00C7BE).withValues(alpha: _glowAnim.value * 0.7),
                          blurRadius: widget.size * 0.2,
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                  ),

                  // The Mascot Running Image with Progress Trail
                  Image.asset(
                    'assets/images/loadinglogo.png',
                    width: widget.size,
                    height: widget.size,
                    fit: BoxFit.contain,
                    errorBuilder: (context, error, stackTrace) {
                      return Image.asset(
                        'assets/images/maskot_esa.png',
                        width: widget.size,
                        height: widget.size,
                        fit: BoxFit.contain,
                        errorBuilder: (c, e, s) => SizedBox(
                          width: widget.size * 0.6,
                          height: widget.size * 0.6,
                          child: const CircularProgressIndicator(strokeWidth: 3),
                        ),
                      );
                    },
                  ),
                ],
              ),
            );
          },
        ),

        if (widget.message != null && widget.message!.isNotEmpty) ...[
          const SizedBox(height: 14),
          Text(
            widget.message!,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: widget.textColor ?? defaultTextColor,
              letterSpacing: 0.2,
            ),
          ),
        ],
      ],
    );

    if (widget.showBackgroundCard) {
      final cardBg = widget.cardColor ??
          (isDark ? const Color(0xFF1E1E2E).withValues(alpha: 0.94) : Colors.white.withValues(alpha: 0.95));

      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 22),
        decoration: BoxDecoration(
          color: cardBg,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.4 : 0.12),
              blurRadius: 20,
              spreadRadius: 2,
              offset: const Offset(0, 6),
            ),
          ],
          border: Border.all(
            color: isDark ? Colors.white12 : const Color(0xFFE2E8F0),
            width: 1,
          ),
        ),
        child: content,
      );
    }

    return content;
  }
}
