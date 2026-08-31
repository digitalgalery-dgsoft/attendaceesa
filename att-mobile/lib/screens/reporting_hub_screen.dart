import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:toastification/toastification.dart';
import 'package:att_mobile/models/report_template_model.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/providers/locale_provider.dart';
import 'package:att_mobile/screens/dynamic_form_screen.dart';
import 'package:att_mobile/screens/report_detail_screen.dart';

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
  int _selectedTabIndex = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      if (_tabController.indexIsChanging || _tabController.index != _selectedTabIndex) {
        setState(() {
          _selectedTabIndex = _tabController.index;
        });
      }
    });

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  void _loadData({bool force = true}) {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    if (auth.token != null) {
      repProvider.fetchTemplates(auth.token!, forceRefresh: force);
      repProvider.fetchHistory(auth.token!);
      repProvider.fetchStores(auth.token!, forceRefresh: force);
    }
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
      case 'competitor':
        return Icons.pie_chart_rounded;
      case 'briefcase':
        return Icons.business_center_rounded;
      case 'calendar-days':
      case 'calendar':
        return Icons.calendar_month_rounded;
      case 'view-columns':
      case 'display':
        return Icons.view_column_rounded;
      case 'tag':
      case 'promo':
        return Icons.local_offer_rounded;
      case 'currency-dollar':
      case 'price':
        return Icons.monetization_on_rounded;
      case 'sparkles':
        return Icons.auto_awesome_rounded;
      case 'photo':
      case 'posm':
        return Icons.photo_library_rounded;
      case 'squares-plus':
        return Icons.dashboard_customize_rounded;
      case 'color-swatch':
      case 'paint':
      case 'tinting':
        return Icons.format_paint_rounded;
      case 'building-storefront':
      case 'store':
      case 'visit':
      case 'survey':
        return Icons.storefront_rounded;
      default:
        return Icons.assignment_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final repProvider = Provider.of<DynamicReportingProvider>(context);
    final locale = Provider.of<LocaleProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    final principalName = auth.employeeData?['principal']?['name'] ?? 'Prinsiple';

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          locale.tr('reporting_hub_title'),
          style: TextStyle(color: textColor, fontSize: 18, fontWeight: FontWeight.bold),
        ),
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded, color: textColor),
            tooltip: 'Segarkan Formulir',
            onPressed: () => _loadData(force: true),
          ),
        ],
      ),
      body: Column(
        children: [
          // Segmented Tab Selector (Sesuai Desain Standar Aplikasi)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: elevatedColor,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: GestureDetector(
                      onTap: () {
                        _tabController.animateTo(0);
                        setState(() => _selectedTabIndex = 0);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        decoration: BoxDecoration(
                          color: _selectedTabIndex == 0 ? cardColor : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          boxShadow: _selectedTabIndex == 0
                              ? [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.06),
                                    blurRadius: 4,
                                    offset: const Offset(0, 2),
                                  )
                                ]
                              : null,
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.assignment_rounded,
                              size: 17,
                              color: _selectedTabIndex == 0 ? primaryColor : subtitleColor,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              '${locale.tr('tab_templates')} (${repProvider.templates.length})',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: _selectedTabIndex == 0 ? FontWeight.bold : FontWeight.w500,
                                color: _selectedTabIndex == 0 ? primaryColor : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    child: GestureDetector(
                      onTap: () {
                        _tabController.animateTo(1);
                        setState(() => _selectedTabIndex = 1);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        decoration: BoxDecoration(
                          color: _selectedTabIndex == 1 ? cardColor : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          boxShadow: _selectedTabIndex == 1
                              ? [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.06),
                                    blurRadius: 4,
                                    offset: const Offset(0, 2),
                                  )
                                ]
                              : null,
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.history_rounded,
                              size: 17,
                              color: _selectedTabIndex == 1 ? primaryColor : subtitleColor,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              '${locale.tr('tab_history')} (${repProvider.history.length})',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: _selectedTabIndex == 1 ? FontWeight.bold : FontWeight.w500,
                                color: _selectedTabIndex == 1 ? primaryColor : subtitleColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Content Tab View
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                // Tab 1: Formulir Aktif
                _buildTemplatesList(
                  repProvider,
                  auth,
                  locale,
                  primaryColor,
                  cardColor,
                  textColor,
                  subtitleColor,
                  elevatedColor,
                  isDarkMode,
                  principalName,
                ),

                // Tab 2: Riwayat Laporan
                _buildHistoryList(
                  repProvider,
                  primaryColor,
                  cardColor,
                  textColor,
                  subtitleColor,
                  elevatedColor,
                  isDarkMode,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTemplatesList(
    DynamicReportingProvider provider,
    AuthProvider auth,
    LocaleProvider locale,
    Color primaryColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
    String principalName,
  ) {
    if (provider.isLoading && provider.templates.isEmpty) {
      return Center(
        child: CircularProgressIndicator(color: primaryColor),
      );
    }

    return RefreshIndicator(
      onRefresh: () async {
        if (auth.token != null) {
          await provider.fetchTemplates(auth.token!, forceRefresh: true);
        }
      },
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        children: [
          // Banner Antrian Offline jika ada
          if (provider.pendingOfflineCount > 0)
            Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.cloud_queue_rounded, color: Color(0xFFD97706), size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      locale.tr('offline_queue_banner', params: {'count': '${provider.pendingOfflineCount}'}),
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
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      minimumSize: Size.zero,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      elevation: 0,
                    ),
                    child: Text(locale.tr('btn_sync_now'), style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),

          // Header Info Prinsiple
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.03),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(9),
                  decoration: BoxDecoration(
                    color: primaryColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(Icons.verified_rounded, color: primaryColor, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        principalName,
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${provider.templates.length} formulir pelaporan operasional tersedia',
                        style: TextStyle(fontSize: 11.5, color: subtitleColor),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 14),

          if (provider.templates.isEmpty)
            Center(
              child: Padding(
                padding: const EdgeInsets.only(top: 40),
                child: Column(
                  children: [
                    Icon(Icons.assignment_outlined, size: 48, color: subtitleColor),
                    const SizedBox(height: 12),
                    Text(
                      'Belum ada formulir pelaporan aktif untuk prinsiple ini.',
                      style: TextStyle(color: subtitleColor, fontSize: 13),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: () => _loadData(force: true),
                      icon: const Icon(Icons.refresh, size: 16),
                      label: const Text('Coba Muat Ulang', style: TextStyle(fontSize: 12)),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: primaryColor,
                        side: BorderSide(color: primaryColor),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ],
                ),
              ),
            )
          else
            ...provider.templates.map((template) => _buildTemplateCard(
                  template,
                  primaryColor,
                  cardColor,
                  textColor,
                  subtitleColor,
                  isDarkMode,
                )),
        ],
      ),
    );
  }

  Widget _buildTemplateCard(
    ReportTemplateModel template,
    Color defaultColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    bool isDarkMode,
  ) {
    final themeColor = Color(int.tryParse(template.color.replaceAll('#', '0xFF')) ?? defaultColor.value);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
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
            ).then((_) {
              final auth = Provider.of<AuthProvider>(context, listen: false);
              if (auth.token != null) {
                Provider.of<DynamicReportingProvider>(context, listen: false).fetchHistory(auth.token!);
              }
            });
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
                    color: themeColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(_getIconData(template.icon), color: themeColor, size: 24),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        template.title,
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor, height: 1.25),
                      ),
                      if (template.description != null && template.description!.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          template.description!,
                          style: TextStyle(fontSize: 11.5, color: subtitleColor, height: 1.3),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      const SizedBox(height: 8),
                      Wrap(
                        crossAxisAlignment: WrapCrossAlignment.center,
                        spacing: 8,
                        runSpacing: 4,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                            decoration: BoxDecoration(
                              color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(5),
                              border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
                            ),
                            child: Text(
                              template.code,
                              style: TextStyle(fontSize: 10, color: subtitleColor, fontFamily: 'monospace', fontWeight: FontWeight.w600),
                            ),
                          ),
                          Text(
                            '${template.fieldsCount} Parameter',
                            style: TextStyle(fontSize: 11, color: themeColor, fontWeight: FontWeight.w600),
                          ),
                          if (template.reportDays.isNotEmpty)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: template.isTodayScheduled
                                    ? const Color(0xFF149A6E).withOpacity(0.12)
                                    : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200),
                                borderRadius: BorderRadius.circular(5),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.calendar_today_rounded,
                                    size: 10,
                                    color: template.isTodayScheduled ? const Color(0xFF149A6E) : subtitleColor,
                                  ),
                                  const SizedBox(width: 3),
                                  Text(
                                    template.scheduleDaysDisplay + (template.isTodayScheduled ? ' (Hari ini)' : ''),
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                      color: template.isTodayScheduled ? const Color(0xFF149A6E) : subtitleColor,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
                Icon(Icons.arrow_forward_ios_rounded, color: subtitleColor, size: 14),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHistoryList(
    DynamicReportingProvider provider,
    Color primaryColor,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    bool isDarkMode,
  ) {
    if (provider.history.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.history_rounded, size: 48, color: subtitleColor),
              const SizedBox(height: 12),
              Text(
                'Belum ada riwayat pengiriman laporan.',
                style: TextStyle(color: subtitleColor, fontSize: 13),
              ),
            ],
          ),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      itemCount: provider.history.length,
      itemBuilder: (context, index) {
        final item = provider.history[index];
        final dateStr = DateFormat('dd MMM yyyy, HH:mm').format(item.submittedAt);

        Color statusBgColor;
        Color statusTextColor;
        String statusLabel;

        if (item.status == 'verified') {
          statusBgColor = Colors.green.withOpacity(0.12);
          statusTextColor = Colors.green.shade700;
          statusLabel = 'Terverifikasi';
        } else if (item.status == 'rejected') {
          statusBgColor = Colors.red.withOpacity(0.12);
          statusTextColor = Colors.red.shade700;
          statusLabel = 'Ditolak';
        } else {
          statusBgColor = Colors.orange.withOpacity(0.12);
          statusTextColor = Colors.orange.shade800;
          statusLabel = 'Menunggu';
        }

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.03),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Material(
            color: Colors.transparent,
            child: InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => ReportDetailScreen(submission: item),
                  ),
                ).then((_) {
                  final auth = Provider.of<AuthProvider>(context, listen: false);
                  if (auth.token != null) {
                    Provider.of<DynamicReportingProvider>(context, listen: false).fetchHistory(auth.token!);
                  }
                });
              },
              child: Padding(
                padding: const EdgeInsets.all(16),
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
                            color: statusBgColor,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            statusLabel,
                            style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: statusTextColor),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      item.templateTitle,
                      style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                    ),
                    if (item.storeName != null) ...[
                      const SizedBox(height: 3),
                      Text(
                        '📍 ${item.storeName}',
                        style: TextStyle(fontSize: 12, color: subtitleColor),
                      ),
                    ],
                    const Divider(height: 18),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Waktu: $dateStr',
                          style: TextStyle(fontSize: 11, color: subtitleColor),
                        ),
                        Row(
                          children: [
                            Text(
                              item.canEdit ? 'Detail & Edit' : 'Lihat Detail',
                              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: primaryColor),
                            ),
                            const SizedBox(width: 4),
                            Icon(Icons.arrow_forward_ios_rounded, size: 11, color: primaryColor),
                          ],
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
