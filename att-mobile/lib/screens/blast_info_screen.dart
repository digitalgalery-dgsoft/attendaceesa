import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/blast_info_provider.dart';
import 'package:intl/intl.dart';

class BlastInfoScreen extends StatefulWidget {
  const BlastInfoScreen({super.key});

  @override
  State<BlastInfoScreen> createState() => _BlastInfoScreenState();
}

class _BlastInfoScreenState extends State<BlastInfoScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<BlastInfoProvider>(context, listen: false).fetchBlastInfos();
    });
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr).toLocal();
      return DateFormat('dd MMM yyyy').format(date);
    } catch (e) {
      return dateStr.split('T').first;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF7367F0);
    final provider = Provider.of<BlastInfoProvider>(context);

    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF9F9FF);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF6E6B7B);
    final contentColor = isDarkMode ? Colors.grey.shade300 : const Color(0xFF4B4B4B);

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: bgColor,
        elevation: 0,
        title: Text(
          'Informasi',
          style: TextStyle(
            color: textColor,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: IconThemeData(color: textColor),
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : provider.blastInfos.isEmpty
              ? _buildEmptyState(primaryColor)
              : RefreshIndicator(
                  onRefresh: () => provider.fetchBlastInfos(),
                  color: primaryColor,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: provider.blastInfos.length,
                    itemBuilder: (context, index) {
                      final info = provider.blastInfos[index];
                      return Card(
                        color: cardColor,
                        margin: const EdgeInsets.only(bottom: 12),
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                          side: BorderSide(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(Icons.campaign, color: primaryColor, size: 20),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      info.title,
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.bold,
                                        color: textColor,
                                      ),
                                    ),
                                  ),
                                  Text(
                                    _formatDate(info.startDate),
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: subtitleColor,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Text(
                                info.content,
                                style: TextStyle(
                                  fontSize: 14,
                                  color: contentColor,
                                  height: 1.5,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }

  Widget _buildEmptyState(Color primaryColor) {
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF6E6B7B);

    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: primaryColor.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.info_outline, size: 64, color: primaryColor),
          ),
          const SizedBox(height: 24),
          Text(
            'Tidak ada informasi',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: textColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Belum ada informasi terbaru untuk Anda.',
            style: TextStyle(color: subtitleColor),
          ),
        ],
      ),
    );
  }
}
