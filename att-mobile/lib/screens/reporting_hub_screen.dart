import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/models/report_template_model.dart';
import 'package:att_mobile/models/report_submission_model.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/screens/dynamic_form_screen.dart';

class ReportingHubScreen extends StatefulWidget {
  final String? storeName;
  final int? workLocationId;
  final int? itineraryItemId;

  const ReportingHubScreen({
    super.key,
    this.storeName,
    this.workLocationId,
    this.itineraryItemId,
  });

  @override
  State<ReportingHubScreen> createState() => _ReportingHubScreenState();
}

class _ReportingHubScreenState extends State<ReportingHubScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
      if (auth.token != null) {
        repProvider.fetchTemplates(auth.token!);
        repProvider.fetchHistory(auth.token!);
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  IconData _getIconData(String iconName) {
    switch (iconName.toLowerCase()) {
      case 'shopping-cart':
      case 'cart':
      case 'offtake':
        return Icons.shopping_bag_rounded;
      case 'boxes':
      case 'archive-box':
      case 'stock':
        return Icons.inventory_2_rounded;
      case 'chart-pie':
      case 'chart-bar':
      case 'market':
        return Icons.pie_chart_rounded;
      case 'color-swatch':
      case 'paint':
      case 'tinting':
        return Icons.format_paint_rounded;
      case 'building-storefront':
      case 'store':
      case 'visit':
        return Icons.storefront_rounded;
      default:
        return Icons.description_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final repProvider = Provider.of<DynamicReportingProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pelaporan Lapangan', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: primaryColor,
        foregroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
          tabs: [
            Tab(
              text: 'Formulir Aktif (${repProvider.templates.length})',
              icon: const Icon(Icons.edit_document, size: 20),
            ),
            Tab(
              text: 'Riwayat Laporan (${repProvider.history.length})',
              icon: const Icon(Icons.history_rounded, size: 20),
            ),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // Tab 1: Daftar Form Aktif
          _buildTemplatesTab(repProvider, auth, primaryColor, isDarkMode),

          // Tab 2: Riwayat Laporan
          _buildHistoryTab(repProvider, primaryColor, isDarkMode),
        ],
      ),
    );
  }

  Widget _buildTemplatesTab(
    DynamicReportingProvider provider,
    AuthProvider auth,
    Color primaryColor,
    bool isDarkMode,
  ) {
    if (provider.isLoading && provider.templates.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    final principalName = auth.employeeData?['principal']?['name'] ?? 'Prinsiple';

    return RefreshIndicator(
      onRefresh: () async {
        if (auth.token != null) {
          await provider.fetchTemplates(auth.token!, forceRefresh: true);
        }
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Banner Offline jika ada antrian
          if (provider.pendingOfflineCount > 0)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.cloud_queue_rounded, color: Color(0xFFD97706), size: 24),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      '${provider.pendingOfflineCount} laporan tersimpan di HP (Offline).',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF92400E)),
                    ),
                  ),
                  ElevatedButton(
                    onPressed: () async {
                      if (auth.token != null) {
                        final count = await provider.syncPending(auth.token!);
                        if (mounted && count > 0) {
                          toastification.show(
                            context: context,
                            type: ToastificationType.success,
                            title: Text('$count Laporan Offline Berhasil Disinkronkan'),
                            autoCloseDuration: const Duration(seconds: 3),
                          );
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD97706),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      minimumSize: Size.zero,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                    ),
                    child: const Text('Sync', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),

          // Header Info Prinsiple
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [primaryColor.withOpacity(0.15), primaryColor.withOpacity(0.05)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: primaryColor.withOpacity(0.2)),
            ),
            child: Row(
              children: [
                Icon(Icons.verified_rounded, color: primaryColor, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Formulir Pelaporan $principalName',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: primaryColor),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          if (provider.templates.isEmpty)
            Center(
              child: Padding(
                padding: const EdgeInsets.only(top: 40),
                child: Column(
                  children: [
                    Icon(Icons.assignment_outlined, size: 48, color: Colors.grey.shade400),
                    const SizedBox(height: 12),
                    Text(
                      'Belum ada formulir pelaporan aktif untuk prinsiple ini.',
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            )
          else
            ...provider.templates.map((template) => _buildTemplateCard(template, isDarkMode)),
        ],
      ),
    );
  }

  Widget _buildTemplateCard(ReportTemplateModel template, bool isDarkMode) {
    final cardColor = Color(int.tryParse(template.color.replaceAll('#', '0xFF')) ?? 0xFF0F52BA);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDarkMode ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: () {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => DynamicFormScreen(
                  template: template,
                  storeName: widget.storeName,
                  workLocationId: widget.workLocationId,
                  itineraryItemId: widget.itineraryItemId,
                ),
              ),
            );
          },
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: cardColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(_getIconData(template.icon), color: cardColor, size: 24),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        template.title,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, height: 1.2),
                      ),
                      if (template.description != null && template.description!.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          template.description!,
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade500, height: 1.3),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.grey.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              template.code,
                              style: TextStyle(fontSize: 9.5, color: Colors.grey.shade600, fontFamily: 'monospace'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            '${template.fieldsCount} Pertanyaan',
                            style: TextStyle(fontSize: 10.5, color: cardColor, fontWeight: FontWeight.w600),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Icon(Icons.chevron_right_rounded, color: Colors.grey.shade400),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHistoryTab(DynamicReportingProvider provider, Color primaryColor, bool isDarkMode) {
    if (provider.history.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.history_rounded, size: 48, color: Colors.grey.shade400),
              const SizedBox(height: 12),
              Text(
                'Belum ada riwayat pengiriman laporan.',
                style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
              ),
            ],
          ),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: provider.history.length,
      itemBuilder: (context, index) {
        final item = provider.history[index];
        final dateStr = DateFormat('dd MMM yyyy, HH:mm').format(item.submittedAt);

        Color statusColor;
        String statusLabel;
        if (item.status == 'verified') {
          statusColor = Colors.green;
          statusLabel = 'Terverifikasi';
        } else if (item.status == 'rejected') {
          statusColor = Colors.red;
          statusLabel = 'Ditolak';
        } else {
          statusColor = Colors.orange;
          statusLabel = 'Menunggu';
        }

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1E293B) : Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: isDarkMode ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    item.submissionCode,
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: primaryColor, fontFamily: 'monospace'),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      statusLabel,
                      style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: statusColor),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                item.templateTitle,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
              if (item.storeName != null) ...[
                const SizedBox(height: 2),
                Text(
                  '📍 ${item.storeName}',
                  style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600),
                ),
              ],
              const Divider(height: 14),
              Text(
                'Waktu Lapor: $dateStr',
                style: TextStyle(fontSize: 10.5, color: Colors.grey.shade500),
              ),
            ],
          ),
        );
      },
    );
  }
}
