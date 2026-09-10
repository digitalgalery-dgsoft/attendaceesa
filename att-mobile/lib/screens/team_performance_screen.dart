import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/dashboard_provider.dart';
import '../providers/auth_provider.dart';
import '../widgets/custom_loading_indicator.dart';

class TeamPerformanceScreen extends StatefulWidget {
  const TeamPerformanceScreen({super.key});

  @override
  State<TeamPerformanceScreen> createState() => _TeamPerformanceScreenState();
}

class _TeamPerformanceScreenState extends State<TeamPerformanceScreen> {
  String _searchQuery = '';
  String _selectedFilter = 'all'; // 'all', 'top_offtake', 'best_mandays', 'need_attention'
  final TextEditingController _searchController = TextEditingController();
  final Set<int> _expandedMembers = {};

  final NumberFormat _numFormat = NumberFormat('#,##0.#', 'id_ID');

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<DashboardProvider>(context, listen: false).fetchTeamPerformance();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final dashboardProvider = Provider.of<DashboardProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;

    final bgColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFF6F8FC);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF111C2D);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF6E6B7B);
    final borderColor = isDarkMode ? Colors.grey.shade800 : Colors.grey.shade200;

    final data = dashboardProvider.teamPerformanceData;
    final summary = (data?['summary'] as Map<String, dynamic>?) ?? {};
    final cutoff = (data?['cutoff'] as Map<String, dynamic>?) ?? {};
    final cutoffLabel = cutoff['label'] ?? 'Periode Cut-Off Aktif';

    final rawMembers = (data?['members'] as List<dynamic>?) ?? [];

    // Filter & Search Members
    List<dynamic> filteredMembers = rawMembers.where((m) {
      final name = (m['full_name'] ?? m['name'] ?? '').toString().toLowerCase();
      final empNo = (m['employee_no'] ?? '').toString().toLowerCase();
      final pos = (m['position'] ?? '').toString().toLowerCase();
      final area = (m['area'] ?? '').toString().toLowerCase();
      final q = _searchQuery.toLowerCase();

      final matchQuery = q.isEmpty ||
          name.contains(q) ||
          empNo.contains(q) ||
          pos.contains(q) ||
          area.contains(q);

      if (!matchQuery) return false;

      final mRate = (m['mandays_rate'] as num?)?.toInt() ?? 0;
      final oRate = (m['offtake_rate'] as num?)?.toInt() ?? 0;
      final rRate = (m['reports_rate'] as num?)?.toInt() ?? 0;

      if (_selectedFilter == 'need_attention') {
        return mRate < 75 || rRate < 75 || (oRate > 0 && oRate < 50);
      } else if (_selectedFilter == 'best_mandays') {
        return mRate >= 90;
      } else if (_selectedFilter == 'top_offtake') {
        final actualL = (m['actual_offtake_liter'] as num?)?.toDouble() ?? 0.0;
        return actualL > 0;
      }
      return true;
    }).toList();

    // Sorting
    if (_selectedFilter == 'top_offtake') {
      filteredMembers.sort((a, b) {
        final lA = (a['actual_offtake_liter'] as num?)?.toDouble() ?? 0.0;
        final lB = (b['actual_offtake_liter'] as num?)?.toDouble() ?? 0.0;
        return lB.compareTo(lA);
      });
    } else if (_selectedFilter == 'best_mandays') {
      filteredMembers.sort((a, b) {
        final rA = (a['mandays_rate'] as num?)?.toInt() ?? 0;
        final rB = (b['mandays_rate'] as num?)?.toInt() ?? 0;
        return rB.compareTo(rA);
      });
    }

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: cardColor,
        elevation: 0.5,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new_rounded, size: 20, color: textColor),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Target & Performa Tim',
              style: TextStyle(color: textColor, fontSize: 17, fontWeight: FontWeight.bold),
            ),
            Text(
              cutoffLabel,
              style: TextStyle(color: primaryColor, fontSize: 11.5, fontWeight: FontWeight.w600),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh_rounded, color: primaryColor),
            tooltip: 'Perbarui Data',
            onPressed: () => dashboardProvider.fetchTeamPerformance(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => dashboardProvider.fetchTeamPerformance(),
        color: primaryColor,
        child: dashboardProvider.isLoadingTeamPerformance && data == null
            ? const Center(child: CustomLoadingIndicator(message: 'Memuat data performa tim...'))
            : ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                children: [
                  // ── 1. HEADER RINGKASAN TOTAL TIM (3 METRIK BESAR) ──
                  _buildTotalTeamSummary(
                    summary: summary,
                    primaryColor: primaryColor,
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    borderColor: borderColor,
                    isDarkMode: isDarkMode,
                  ),

                  const SizedBox(height: 16),

                  // ── 2. SEARCH & FILTER CHIPS ──
                  _buildSearchBar(
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    borderColor: borderColor,
                  ),

                  const SizedBox(height: 10),

                  _buildFilterChips(
                    primaryColor: primaryColor,
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    borderColor: borderColor,
                    totalCount: rawMembers.length,
                  ),

                  const SizedBox(height: 14),

                  // ── 3. LIST ANGGOTA TIM & DETAIL PERFORMA PER ORANG ──
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'PERFORMA ANGGOTA TIM (${filteredMembers.length})',
                        style: TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w800,
                          color: subtitleColor,
                          letterSpacing: 0.5,
                        ),
                      ),
                      Text(
                        'Mandays • Offtake • Report',
                        style: TextStyle(
                          fontSize: 10.5,
                          color: primaryColor,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),

                  if (filteredMembers.isEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 20),
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: borderColor),
                      ),
                      child: Column(
                        children: [
                          Icon(Icons.search_off_rounded, size: 44, color: subtitleColor.withOpacity(0.5)),
                          const SizedBox(height: 8),
                          Text(
                            'Tidak ada data anggota tim yang sesuai',
                            style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Coba ubah kata kunci atau filter pencarian',
                            style: TextStyle(fontSize: 11.5, color: subtitleColor),
                          ),
                        ],
                      ),
                    )
                  else
                    ...filteredMembers.map((member) => _buildMemberCard(
                          member: member,
                          primaryColor: primaryColor,
                          cardColor: cardColor,
                          textColor: textColor,
                          subtitleColor: subtitleColor,
                          borderColor: borderColor,
                          isDarkMode: isDarkMode,
                        )),

                  const SizedBox(height: 30),
                ],
              ),
      ),
    );
  }

  // ── WIDGET: RINGKASAN TOTAL TIM (3 KPI UTAMA) ──
  Widget _buildTotalTeamSummary({
    required Map<String, dynamic> summary,
    required Color primaryColor,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color borderColor,
    required bool isDarkMode,
  }) {
    final tMandays = (summary['team_target_mandays'] as num?)?.toInt() ?? 0;
    final aMandays = (summary['team_actual_mandays'] as num?)?.toInt() ?? 0;
    final rMandays = (summary['team_mandays_rate'] as num?)?.toInt() ?? 0;

    final tOfftake = (summary['team_target_offtake_liter'] as num?)?.toDouble() ?? 0.0;
    final aOfftake = (summary['team_actual_offtake_liter'] as num?)?.toDouble() ?? 0.0;
    final rOfftake = (summary['team_offtake_rate'] as num?)?.toInt() ?? 0;

    final tReports = (summary['team_target_reports'] as num?)?.toInt() ?? 0;
    final aReports = (summary['team_actual_reports'] as num?)?.toInt() ?? 0;
    final rReports = (summary['team_reports_rate'] as num?)?.toInt() ?? 0;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDarkMode ? 0.2 : 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: primaryColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(Icons.analytics_rounded, size: 18, color: primaryColor),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Akumulasi Target & Pencapaian Tim',
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                    ),
                    Text(
                      'Total ${(summary['total_team'] ?? 0)} Anggota Aktif',
                      style: TextStyle(fontSize: 10.5, color: subtitleColor),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Grid 3 KPI Summary
          Row(
            children: [
              // 1. Total Mandays
              Expanded(
                child: _buildSummaryBox(
                  title: 'Mandays Tim',
                  mainValue: '$aMandays / $tMandays',
                  subValue: '$rMandays% Tercapai',
                  icon: Icons.calendar_month_rounded,
                  iconColor: const Color(0xFF0F52BA),
                  progress: tMandays > 0 ? (aMandays / tMandays).clamp(0.0, 1.0) : 0.0,
                  isDarkMode: isDarkMode,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                ),
              ),
              const SizedBox(width: 8),

              // 2. Total Offtake (Liter)
              Expanded(
                child: _buildSummaryBox(
                  title: 'Offtake Tim',
                  mainValue: '${_numFormat.format(aOfftake)} L',
                  subValue: tOfftake > 0 ? '$rOfftake% (${_numFormat.format(tOfftake)} L)' : 'Aktual Liter',
                  icon: Icons.water_drop_rounded,
                  iconColor: const Color(0xFF10B981),
                  progress: tOfftake > 0 ? (aOfftake / tOfftake).clamp(0.0, 1.0) : 1.0,
                  isDarkMode: isDarkMode,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                ),
              ),
              const SizedBox(width: 8),

              // 3. Seluruh Report
              Expanded(
                child: _buildSummaryBox(
                  title: 'Seluruh Report',
                  mainValue: '$aReports / $tReports',
                  subValue: '$rReports% Disubmit',
                  icon: Icons.assignment_turned_in_rounded,
                  iconColor: const Color(0xFF8B5CF6),
                  progress: tReports > 0 ? (aReports / tReports).clamp(0.0, 1.0) : 0.0,
                  isDarkMode: isDarkMode,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryBox({
    required String title,
    required String mainValue,
    required String subValue,
    required IconData icon,
    required Color iconColor,
    required double progress,
    required bool isDarkMode,
    required Color textColor,
    required Color subtitleColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: iconColor.withOpacity(isDarkMode ? 0.08 : 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: iconColor.withOpacity(0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: iconColor),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: subtitleColor),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            mainValue,
            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800, color: textColor),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            subValue,
            style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: iconColor),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 6),
          ClipRRect(
            borderRadius: BorderRadius.circular(3),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 4,
              backgroundColor: iconColor.withOpacity(0.15),
              valueColor: AlwaysStoppedAnimation<Color>(iconColor),
            ),
          ),
        ],
      ),
    );
  }

  // ── WIDGET: SEARCH BAR ──
  Widget _buildSearchBar({
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color borderColor,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor),
      ),
      child: TextField(
        controller: _searchController,
        style: TextStyle(fontSize: 13, color: textColor),
        onChanged: (val) => setState(() => _searchQuery = val),
        decoration: InputDecoration(
          hintText: 'Cari nama anggota, NIK, jabatan, area...',
          hintStyle: TextStyle(fontSize: 12.5, color: subtitleColor.withOpacity(0.7)),
          prefixIcon: Icon(Icons.search_rounded, size: 18, color: subtitleColor),
          suffixIcon: _searchQuery.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear, size: 16),
                  onPressed: () {
                    _searchController.clear();
                    setState(() => _searchQuery = '');
                  },
                )
              : null,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 12, horizontal: 12),
        ),
      ),
    );
  }

  // ── WIDGET: FILTER CHIPS ──
  Widget _buildFilterChips({
    required Color primaryColor,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color borderColor,
    required int totalCount,
  }) {
    final filters = [
      {'key': 'all', 'label': 'Semua ($totalCount)'},
      {'key': 'top_offtake', 'label': 'Offtake (Liter)'},
      {'key': 'best_mandays', 'label': 'Mandays Terbaik'},
      {'key': 'need_attention', 'label': 'Perlu Perhatian'},
    ];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: filters.map((f) {
          final isSelected = _selectedFilter == f['key'];
          return Padding(
            padding: const EdgeInsets.only(right: 6),
            child: FilterChip(
              selected: isSelected,
              label: Text(
                f['label']!,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                  color: isSelected ? Colors.white : textColor,
                ),
              ),
              backgroundColor: cardColor,
              selectedColor: primaryColor,
              checkmarkColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
                side: BorderSide(
                  color: isSelected ? primaryColor : borderColor,
                ),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
              onSelected: (_) => setState(() => _selectedFilter = f['key']!),
            ),
          );
        }).toList(),
      ),
    );
  }

  // ── WIDGET: CARD ANGGOTA TIM (INDIVIDUAL PERFORMANCE) ──
  Widget _buildMemberCard({
    required Map<String, dynamic> member,
    required Color primaryColor,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color borderColor,
    required bool isDarkMode,
  }) {
    final int memberId = (member['id'] as num?)?.toInt() ?? 0;
    final isExpanded = _expandedMembers.contains(memberId);

    final name = member['full_name'] ?? member['name'] ?? 'Staff';
    final empNo = member['employee_no'] ?? '-';
    final pos = member['position'] ?? 'Staff';
    final area = member['area'] ?? '-';

    // 1. Mandays
    final tMandays = (member['target_mandays'] as num?)?.toInt() ?? 0;
    final aMandays = (member['actual_mandays'] as num?)?.toInt() ?? 0;
    final sakit = (member['sakit'] as num?)?.toInt() ?? 0;
    final cuti = (member['cuti'] as num?)?.toInt() ?? 0;
    final mRate = (member['mandays_rate'] as num?)?.toInt() ?? 0;

    // 2. Offtake (Liters)
    final tOfftake = (member['target_offtake_liter'] as num?)?.toDouble() ?? 0.0;
    final aOfftake = (member['actual_offtake_liter'] as num?)?.toDouble() ?? 0.0;
    final oRate = (member['offtake_rate'] as num?)?.toInt() ?? 0;
    final offtakeCount = (member['offtake_submission_count'] as num?)?.toInt() ?? 0;

    // 3. Seluruh Report
    final tReports = (member['target_reports'] as num?)?.toInt() ?? 0;
    final aReports = (member['actual_reports'] as num?)?.toInt() ?? 0;
    final rRate = (member['reports_rate'] as num?)?.toInt() ?? 0;
    final breakdown = (member['reports_breakdown'] as List<dynamic>?) ?? [];

    // Status Hari Ini
    final todayStatus = (member['today_status'] as String?) ?? 'Belum Check-In';
    final todayType = (member['today_status_type'] as String?) ?? 'uncheck';
    Color todayColor;
    IconData todayIcon;
    switch (todayType) {
      case 'present':
        todayColor = const Color(0xFF10B981);
        todayIcon = Icons.check_circle_rounded;
        break;
      case 'sick':
        todayColor = const Color(0xFFF59E0B);
        todayIcon = Icons.local_hospital_rounded;
        break;
      case 'leave':
        todayColor = const Color(0xFF3B82F6);
        todayIcon = Icons.beach_access_rounded;
        break;
      default:
        todayColor = const Color(0xFFEF4444);
        todayIcon = Icons.cancel_rounded;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDarkMode ? 0.2 : 0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Header Anggota ──
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: primaryColor.withOpacity(0.12),
                  child: Text(
                    name.isNotEmpty ? name.substring(0, 1).toUpperCase() : 'U',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: primaryColor),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Text(
                            empNo,
                            style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor),
                          ),
                          const SizedBox(width: 6),
                          Text('•', style: TextStyle(color: subtitleColor, fontSize: 10)),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              '$pos ($area)',
                              style: TextStyle(fontSize: 11, color: subtitleColor),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                // Status Hari Ini Badge
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3.5),
                  decoration: BoxDecoration(
                    color: todayColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: todayColor.withOpacity(0.3)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(todayIcon, size: 11, color: todayColor),
                      const SizedBox(width: 4),
                      Text(
                        todayStatus,
                        style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.bold, color: todayColor),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const Divider(height: 1, thickness: 0.8),

          // ── 3 METRIK INDIVIDUAL: MANDAYS, OFFTAKE LITER, & SELURUH REPORT ──
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Column(
              children: [
                // 1. MANDAYS PER ORANG
                _buildIndividualMetricRow(
                  label: 'Mandays (Kehadiran)',
                  icon: Icons.event_available_rounded,
                  iconColor: const Color(0xFF0F52BA),
                  actualText: '$aMandays HK',
                  targetText: 'Target: $tMandays HK',
                  subNote: 'Sakit: $sakit hr • Cuti/Izin: $cuti hr',
                  ratePercent: mRate,
                  isDarkMode: isDarkMode,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                ),

                const SizedBox(height: 10),

                // 2. OFFTAKE (LITER) PER ORANG
                _buildIndividualMetricRow(
                  label: 'Laporan Offtake (Volume)',
                  icon: Icons.opacity_rounded,
                  iconColor: const Color(0xFF10B981),
                  actualText: '${_numFormat.format(aOfftake)} Liter',
                  targetText: tOfftake > 0 ? 'Target: ${_numFormat.format(tOfftake)} L' : 'Target: Belum diset',
                  subNote: '$offtakeCount laporan offtake disubmit',
                  ratePercent: oRate,
                  isTargetSet: tOfftake > 0,
                  isDarkMode: isDarkMode,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                ),

                const SizedBox(height: 10),

                // 3. SELURUH REPORT PER ORANG
                _buildIndividualMetricRow(
                  label: 'Pencapaian Seluruh Report',
                  icon: Icons.checklist_rtl_rounded,
                  iconColor: const Color(0xFF8B5CF6),
                  actualText: '$aReports Laporan Selesai',
                  targetText: 'Target: $tReports Lap',
                  subNote: 'Kepatuhan pengisian: $rRate%',
                  ratePercent: rRate,
                  isDarkMode: isDarkMode,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                ),
              ],
            ),
          ),

          // ── ACCORDION: RINCIAN TEMPLATE LAPORAN ──
          if (breakdown.isNotEmpty) ...[
            InkWell(
              onTap: () {
                setState(() {
                  if (isExpanded) {
                    _expandedMembers.remove(memberId);
                  } else {
                    _expandedMembers.add(memberId);
                  }
                });
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white.withOpacity(0.03) : const Color(0xFFF8FAFC),
                  borderRadius: const BorderRadius.vertical(bottom: Radius.circular(14)),
                  border: Border(top: BorderSide(color: borderColor.withOpacity(0.7))),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isExpanded ? 'Tutup Rincian Laporan' : 'Lihat Rincian ${breakdown.length} Template Laporan',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: primaryColor,
                      ),
                    ),
                    Icon(
                      isExpanded ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded,
                      size: 18,
                      color: primaryColor,
                    ),
                  ],
                ),
              ),
            ),
            if (isExpanded)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                color: isDarkMode ? Colors.black.withOpacity(0.15) : const Color(0xFFF1F5F9),
                child: Column(
                  children: breakdown.map((b) {
                    final bTitle = b['title'] ?? 'Laporan';
                    final bTarget = (b['target'] as num?)?.toInt() ?? 0;
                    final bActual = (b['actual'] as num?)?.toInt() ?? 0;
                    final bRate = (b['rate'] as num?)?.toInt() ?? 0;
                    final isDone = bActual >= bTarget && bTarget > 0;

                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          Icon(
                            isDone ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
                            size: 13,
                            color: isDone ? const Color(0xFF10B981) : subtitleColor,
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              bTitle,
                              style: TextStyle(fontSize: 11, color: textColor, fontWeight: FontWeight.w500),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          Text(
                            '$bActual / $bTarget ($bRate%)',
                            style: TextStyle(
                              fontSize: 10.5,
                              fontWeight: FontWeight.bold,
                              color: isDone ? const Color(0xFF10B981) : (bActual > 0 ? primaryColor : subtitleColor),
                            ),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                ),
              ),
          ],
        ],
      ),
    );
  }

  // ── WIDGET HELPER: METRIK BARIS INDIVIDUAL ──
  Widget _buildIndividualMetricRow({
    required String label,
    required IconData icon,
    required Color iconColor,
    required String actualText,
    required String targetText,
    required String subNote,
    required int ratePercent,
    bool isTargetSet = true,
    required bool isDarkMode,
    required Color textColor,
    required Color subtitleColor,
  }) {
    final progress = isTargetSet ? (ratePercent / 100.0).clamp(0.0, 1.0) : 1.0;
    Color badgeColor;
    if (!isTargetSet) {
      badgeColor = subtitleColor;
    } else if (ratePercent >= 100) {
      badgeColor = const Color(0xFF10B981);
    } else if (ratePercent >= 75) {
      badgeColor = const Color(0xFF0F52BA);
    } else if (ratePercent >= 50) {
      badgeColor = const Color(0xFFF59E0B);
    } else {
      badgeColor = const Color(0xFFEF4444);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Icon(icon, size: 14, color: iconColor),
                const SizedBox(width: 5),
                Text(
                  label,
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: subtitleColor),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
              decoration: BoxDecoration(
                color: badgeColor.withOpacity(0.12),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                isTargetSet ? '$ratePercent%' : 'Aktual',
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: badgeColor),
              ),
            ),
          ],
        ),
        const SizedBox(height: 3),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              actualText,
              style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800, color: textColor),
            ),
            Text(
              targetText,
              style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w600, color: subtitleColor),
            ),
          ],
        ),
        const SizedBox(height: 4),
        ClipRRect(
          borderRadius: BorderRadius.circular(3),
          child: LinearProgressIndicator(
            value: progress,
            minHeight: 4,
            backgroundColor: iconColor.withOpacity(0.12),
            valueColor: AlwaysStoppedAnimation<Color>(badgeColor),
          ),
        ),
        const SizedBox(height: 2),
        Text(
          subNote,
          style: TextStyle(fontSize: 9.5, color: subtitleColor.withOpacity(0.8)),
        ),
      ],
    );
  }
}
