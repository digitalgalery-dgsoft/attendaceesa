import 'package:flutter/material.dart';
import 'package:att_mobile/services/network_status_service.dart';

class ConnectionStatusBadge extends StatefulWidget {
  final bool showText;
  final double fontSize;
  final EdgeInsetsGeometry padding;

  const ConnectionStatusBadge({
    super.key,
    this.showText = true,
    this.fontSize = 11.0,
    this.padding = const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
  });

  @override
  State<ConnectionStatusBadge> createState() => _ConnectionStatusBadgeState();
}

class _ConnectionStatusBadgeState extends State<ConnectionStatusBadge>
    with SingleTickerProviderStateMixin {
  late AnimationController _pulseController;
  late Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    NetworkStatusService.startMonitoring();

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat(reverse: true);

    _pulseAnim = Tween<double>(begin: 0.4, end: 1.0).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return ValueListenableBuilder<bool>(
      valueListenable: NetworkStatusService.isOnline,
      builder: (context, isOnline, _) {
        final Color bgColor = isOnline
            ? (isDark
                ? const Color(0xFF14532D).withValues(alpha: 0.5)
                : const Color(0xFFDCFCE7))
            : (isDark
                ? const Color(0xFF7F1D1D).withValues(alpha: 0.5)
                : const Color(0xFFFEE2E2));

        final Color borderColor = isOnline
            ? (isDark
                ? const Color(0xFF22C55E).withValues(alpha: 0.6)
                : const Color(0xFF86EFAC))
            : (isDark
                ? const Color(0xFFEF4444).withValues(alpha: 0.6)
                : const Color(0xFFFCA5A5));

        final Color textColor = isOnline
            ? (isDark ? const Color(0xFF4ADE80) : const Color(0xFF15803D))
            : (isDark ? const Color(0xFFF87171) : const Color(0xFFB91C1C));

        final Color dotColor = isOnline
            ? const Color(0xFF16A34A)
            : const Color(0xFFDC2626);

        return GestureDetector(
          onTap: () async {
            final nowOnline = await NetworkStatusService.checkConnection();
            if (context.mounted) {
              ScaffoldMessenger.of(context).removeCurrentSnackBar();
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    nowOnline
                        ? '🟢 Koneksi Internet Aktif (Online)'
                        : '🔴 Tidak Ada Koneksi Internet (Offline)',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                  backgroundColor: nowOnline ? const Color(0xFF15803D) : const Color(0xFFB91C1C),
                  duration: const Duration(seconds: 2),
                  behavior: SnackBarBehavior.floating,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              );
            }
          },
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            padding: widget.padding,
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: borderColor, width: 1.2),
              boxShadow: [
                BoxShadow(
                  color: (isOnline ? const Color(0xFF16A34A) : const Color(0xFFDC2626))
                      .withValues(alpha: 0.15),
                  blurRadius: 4,
                  offset: const Offset(0, 1),
                ),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                AnimatedBuilder(
                  animation: _pulseAnim,
                  builder: (context, child) {
                    return Opacity(
                      opacity: isOnline ? _pulseAnim.value : 1.0,
                      child: Container(
                        width: 7.5,
                        height: 7.5,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: dotColor,
                          boxShadow: isOnline
                              ? [
                                  BoxShadow(
                                    color: dotColor.withValues(alpha: _pulseAnim.value * 0.7),
                                    blurRadius: 5,
                                    spreadRadius: 1,
                                  ),
                                ]
                              : null,
                        ),
                      ),
                    );
                  },
                ),
                if (widget.showText) ...[
                  const SizedBox(width: 5.5),
                  Text(
                    isOnline ? 'Online' : 'Offline',
                    style: TextStyle(
                      color: textColor,
                      fontSize: widget.fontSize,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.2,
                    ),
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }
}
