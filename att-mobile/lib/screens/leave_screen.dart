import 'package:flutter/material.dart';

class LeaveScreen extends StatelessWidget {
  const LeaveScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F9FF),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Leave & Time Off',
          style: TextStyle(
            color: Color(0xFF111C2D),
            fontSize: 24,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      body: const Center(
        child: Text(
          'Leave feature coming soon',
          style: TextStyle(color: Color(0xFF6E6B7B), fontSize: 16),
        ),
      ),
    );
  }
}
