import 'package:flutter/material.dart';

/// Reusable Shimmer Effect Controller & Container
class ShimmerLoading extends StatefulWidget {
  final Widget child;
  final bool isLoading;

  const ShimmerLoading({
    super.key,
    required this.child,
    this.isLoading = true,
  });

  @override
  State<ShimmerLoading> createState() => _ShimmerLoadingState();
}

class _ShimmerLoadingState extends State<ShimmerLoading> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat();

    _animation = Tween<double>(begin: -1.5, end: 1.5).animate(
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
    if (!widget.isLoading) return widget.child;

    final isDark = Theme.of(context).brightness == Brightness.dark;
    final baseColor = isDark ? const Color(0xFF1E293B) : const Color(0xFFE2E8F0);
    final highlightColor = isDark ? const Color(0xFF334155) : const Color(0xFFF8FAFC);

    return AnimatedBuilder(
      animation: _animation,
      builder: (context, child) {
        return ShaderMask(
          blendMode: BlendMode.srcATop,
          shaderCallback: (bounds) {
            return LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                baseColor,
                highlightColor,
                baseColor,
              ],
              stops: const [0.1, 0.5, 0.9],
              transform: _SlidingGradientTransform(slidePercent: _animation.value),
            ).createShader(bounds);
          },
          child: widget.child,
        );
      },
    );
  }
}

class _SlidingGradientTransform extends GradientTransform {
  const _SlidingGradientTransform({required this.slidePercent});
  final double slidePercent;

  @override
  Matrix4? transform(Rect bounds, {TextDirection? textDirection}) {
    return Matrix4.translationValues(bounds.width * slidePercent, 0.0, 0.0);
  }
}

/// Simple Rounded Skeleton Box
class SkeletonBox extends StatelessWidget {
  final double? width;
  final double height;
  final double borderRadius;
  final EdgeInsetsGeometry? margin;

  const SkeletonBox({
    super.key,
    this.width,
    required this.height,
    this.borderRadius = 8.0,
    this.margin,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final color = isDark ? const Color(0xFF1E293B) : const Color(0xFFE2E8F0);

    return Container(
      width: width,
      height: height,
      margin: margin,
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(borderRadius),
      ),
    );
  }
}

/// Dashboard Full Screen Skeleton
class DashboardSkeleton extends StatelessWidget {
  const DashboardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return ShimmerLoading(
      isLoading: true,
      child: SingleChildScrollView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // User Header Profile Banner Skeleton
            Container(
              padding: const EdgeInsets.all(16.0),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16.0),
              ),
              child: Row(
                children: [
                  const SkeletonBox(width: 54, height: 54, borderRadius: 27),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        SkeletonBox(width: 160, height: 16, borderRadius: 4),
                        SizedBox(height: 8),
                        SkeletonBox(width: 110, height: 12, borderRadius: 4),
                        SizedBox(height: 6),
                        SkeletonBox(width: 190, height: 12, borderRadius: 4),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Live Attendance Card Skeleton
            Container(
              padding: const EdgeInsets.all(20.0),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20.0),
              ),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: const [
                      SkeletonBox(width: 120, height: 14, borderRadius: 4),
                      SkeletonBox(width: 80, height: 22, borderRadius: 12),
                    ],
                  ),
                  const SizedBox(height: 18),
                  const SkeletonBox(width: 180, height: 36, borderRadius: 8),
                  const SizedBox(height: 8),
                  const SkeletonBox(width: 140, height: 14, borderRadius: 4),
                  const SizedBox(height: 20),
                  Row(
                    children: const [
                      Expanded(child: SkeletonBox(height: 64, borderRadius: 12)),
                      SizedBox(width: 12),
                      Expanded(child: SkeletonBox(height: 64, borderRadius: 12)),
                    ],
                  ),
                  const SizedBox(height: 18),
                  const SkeletonBox(width: double.infinity, height: 50, borderRadius: 14),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Quick Menu Action Grid Skeleton (4 columns)
            Container(
              padding: const EdgeInsets.all(16.0),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16.0),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SkeletonBox(width: 130, height: 14, borderRadius: 4),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: List.generate(
                      4,
                      (index) => Column(
                        children: const [
                          SkeletonBox(width: 48, height: 48, borderRadius: 14),
                          SizedBox(height: 8),
                          SkeletonBox(width: 42, height: 10, borderRadius: 3),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Work Location & Shift Card Skeleton
            Container(
              padding: const EdgeInsets.all(16.0),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16.0),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  SkeletonBox(width: 140, height: 14, borderRadius: 4),
                  SizedBox(height: 12),
                  SkeletonBox(width: double.infinity, height: 14, borderRadius: 4),
                  SizedBox(height: 8),
                  SkeletonBox(width: 220, height: 14, borderRadius: 4),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// History & Attendance List Skeleton
class HistorySkeleton extends StatelessWidget {
  const HistorySkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return ShimmerLoading(
      isLoading: true,
      child: SingleChildScrollView(
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // Calendar Skeleton
            Container(
              height: 240,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16.0),
              ),
            ),
            const SizedBox(height: 16),

            // History Log Cards
            ...List.generate(
              4,
              (index) => Container(
                margin: const EdgeInsets.only(bottom: 12.0),
                padding: const EdgeInsets.all(16.0),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14.0),
                ),
                child: Row(
                  children: [
                    const SkeletonBox(width: 44, height: 44, borderRadius: 12),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          SkeletonBox(width: 130, height: 14, borderRadius: 4),
                          SizedBox(height: 6),
                          SkeletonBox(width: 180, height: 12, borderRadius: 4),
                        ],
                      ),
                    ),
                    const SkeletonBox(width: 60, height: 24, borderRadius: 12),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
