import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/dashboard_provider.dart';
import '../providers/auth_provider.dart';

class TeamUncheckedScreen extends StatefulWidget {
  const TeamUncheckedScreen({super.key});

  @override
  State<TeamUncheckedScreen> createState() => _TeamUncheckedScreenState();
}

class _TeamUncheckedScreenState extends State<TeamUncheckedScreen> {
  String _searchQuery = '';
  String _selectedFilter = 'all'; // 'all', 'today_unchecked', 'high_absence', 'never'
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<DashboardProvider>(context, listen: false).fetchTeamUncheckedList();
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

    List<dynamic> rawList = dashboardProvider.teamUncheckedList.isNotEmpty 
        ? dashboardProvider.teamUncheckedList 
        : dashboardProvider.vacantDetails;

    // Apply Filter
    List<dynamic> filteredList = rawList.where((item) {
      final name = (item['full_name'] ?? item['name'] ?? '').toString().toLowerCase();
      final position = (item['position'] ?? '').toString().toLowerCase();
      final principal = (item['principal'] ?? '').toString().toLowerCase();
      final area = (item['area'] ?? item['branch'] ?? '').toString().toLowerCase();
      final employeeNo = (item['employee_no'] ?? '').toString().toLowerCase();

      final query = _searchQuery.toLowerCase();
      final matchSearch = query.isEmpty ||
          name.contains(query) ||
          position.contains(query) ||
          principal.contains(query) ||
          area.contains(query) ||
          employeeNo.contains(query);

      if (!matchSearch) return false;

      final days = item['days'] ?? -1;
      final isTodayUnchecked = item['is_today_unchecked'] ?? (days >= 0 || days == -1);
      final missedCount = item['missed_count_7days'] ?? (days == -1 ? 7 : (days > 7 ? 7 : days));

      if (_selectedFilter == 'today_unchecked') {
        return isTodayUnchecked == true;
      } else if (_selectedFilter == 'high_absence') {
        return missedCount >= 3 || days >= 3 || days == -1;
      } else if (_selectedFilter == 'never') {
        return days == -1;
      }

      return true;
    }).toList();

    return Scaffold(
      backgroundColor: bgColor,
      appBar: AppBar(
        backgroundColor: cardColor,
        elevation: 0.5,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios_new, size: 20, color: textColor),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Tim Belum Check-In',
              style: TextStyle(
                color: textColor,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              'Monitoring Kehadiran 7 Hari Terakhir',
              style: TextStyle(
                color: subtitleColor,
                fontSize: 11,
                fontWeight: FontWeight.w400,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.refresh, color: primaryColor),
            tooltip: 'Refresh Data',
            onPressed: () {
              dashboardProvider.fetchTeamUncheckedList();
              dashboardProvider.fetchTeamStats();
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await dashboardProvider.fetchTeamUncheckedList();
          await dashboardProvider.fetchTeamStats();
        },
        child: CustomScrollView(
          slivers: [
            // Top Search & Filter Section
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Search Field
                    Container(
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: borderColor),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.03),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: TextField(
                        controller: _searchController,
                        style: TextStyle(color: textColor, fontSize: 13.5),
                        decoration: InputDecoration(
                          hintText: 'Cari nama, jabatan, prinsiple, atau area...',
                          hintStyle: TextStyle(color: subtitleColor, fontSize: 13),
                          prefixIcon: Icon(Icons.search, color: subtitleColor, size: 20),
                          suffixIcon: _searchQuery.isNotEmpty
                              ? IconButton(
                                  icon: Icon(Icons.clear, size: 18, color: subtitleColor),
                                  onPressed: () {
                                    setState(() {
                                      _searchController.clear();
                                      _searchQuery = '';
                                    });
                                  },
                                )
                              : null,
                          border: InputBorder.none,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        ),
                        onChanged: (val) {
                          setState(() {
                            _searchQuery = val;
                          });
                        },
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Filter Chips
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _buildFilterChip('Semua (${rawList.length})', 'all', primaryColor, isDarkMode),
                          const SizedBox(width: 8),
                          _buildFilterChip('Belum Check-In Hari Ini', 'today_unchecked', Colors.redAccent, isDarkMode),
                          const SizedBox(width: 8),
                          _buildFilterChip('≥ 3 Hari Tidak Hadir', 'high_absence', Colors.orange, isDarkMode),
                          const SizedBox(width: 8),
                          _buildFilterChip('Belum Pernah Hadir', 'never', Colors.purple, isDarkMode),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
            ),

            // Content List / Empty State
            if (dashboardProvider.isLoadingUnchecked)
              const SliverFillRemaining(
                child: Center(
                  child: CircularProgressIndicator(),
                ),
              )
            else if (filteredList.isEmpty)
              SliverFillRemaining(
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(32.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.green.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.check_circle_outline, size: 56, color: Colors.green),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Semua Anggota Tim Telah Check-In',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: textColor,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 6),
                        Text(
                          _searchQuery.isNotEmpty
                              ? 'Tidak ada data tim yang cocok dengan kata kunci pencarian.'
                              : 'Seluruh anggota tim Anda telah hadir atau sedang izin/cuti resmi.',
                          style: TextStyle(fontSize: 12.5, color: subtitleColor),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final item = filteredList[index];
                      return _buildEmployeeCard(item, cardColor, textColor, subtitleColor, borderColor, primaryColor, isDarkMode);
                    },
                    childCount: filteredList.length,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String label, String value, Color activeColor, bool isDarkMode) {
    final isSelected = _selectedFilter == value;
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedFilter = value;
        });
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 7),
        decoration: BoxDecoration(
          color: isSelected
              ? activeColor.withValues(alpha: isDarkMode ? 0.25 : 0.12)
              : (isDarkMode ? const Color(0xFF1E1E2C) : Colors.white),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? activeColor : (isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? activeColor : (isDarkMode ? Colors.grey.shade300 : const Color(0xFF4A5568)),
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
          ),
        ),
      ),
    );
  }

  Widget _buildEmployeeCard(
    dynamic item,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color borderColor,
    Color primaryColor,
    bool isDarkMode,
  ) {
    final fullName = item['full_name'] ?? item['name'] ?? 'Nama Karyawan';
    final position = item['position'] ?? 'Staff';
    final principal = item['principal'] ?? '-';
    final area = item['area'] ?? item['branch'] ?? '-';
    final employeeNo = item['employee_no'] ?? '-';
    final days = item['days'] ?? -1;
    final lastDate = item['last_attendance_date'] ?? 'Belum pernah hadir';
    final isTodayUnchecked = item['is_today_unchecked'] ?? true;
    final List<dynamic> missedDates = item['missed_dates'] as List<dynamic>? ?? [];

    String statusDaysText;
    Color statusDaysColor;
    if (days == -1) {
      statusDaysText = 'Belum pernah hadir';
      statusDaysColor = Colors.redAccent;
    } else if (days == 0) {
      statusDaysText = 'Hari ini belum check-in';
      statusDaysColor = Colors.redAccent;
    } else if (days == 1) {
      statusDaysText = '1 hari tidak check-in';
      statusDaysColor = Colors.orange;
    } else {
      statusDaysText = '$days hari tidak check-in';
      statusDaysColor = days >= 3 ? Colors.redAccent : Colors.orange;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Avatar, Name, and Status Badge
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: statusDaysColor.withValues(alpha: 0.15),
                  child: Text(
                    fullName.isNotEmpty ? fullName[0].toUpperCase() : '?',
                    style: TextStyle(
                      color: statusDaysColor,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        fullName,
                        style: TextStyle(
                          fontSize: 14.5,
                          fontWeight: FontWeight.bold,
                          color: textColor,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          if (employeeNo != '-' && employeeNo.isNotEmpty) ...[
                            Text(
                              'NIK: $employeeNo',
                              style: TextStyle(fontSize: 11, color: subtitleColor, fontWeight: FontWeight.w500),
                            ),
                            Text(' · ', style: TextStyle(color: subtitleColor, fontSize: 11)),
                          ],
                          Flexible(
                            child: Text(
                              position,
                              style: TextStyle(
                                fontSize: 11.5,
                                color: primaryColor,
                                fontWeight: FontWeight.w600,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                // Today Unchecked Tag
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: (isTodayUnchecked ? Colors.red : Colors.green).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    isTodayUnchecked ? 'Belum Hadir' : 'Hadir',
                    style: TextStyle(
                      fontSize: 10.5,
                      fontWeight: FontWeight.bold,
                      color: isTodayUnchecked ? Colors.redAccent : Colors.green,
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 12),
            Divider(height: 1, color: borderColor),
            const SizedBox(height: 10),

            // Metadata Grid: Principal & Area
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem(
                    Icons.business,
                    'Prinsiple',
                    principal,
                    textColor,
                    subtitleColor,
                  ),
                ),
                Expanded(
                  child: _buildInfoItem(
                    Icons.location_on_outlined,
                    'Area',
                    area,
                    textColor,
                    subtitleColor,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 10),

            // Last Attendance Date Info
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              decoration: BoxDecoration(
                color: isDarkMode ? const Color(0xFF262638) : const Color(0xFFF7F8FA),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(Icons.history, size: 16, color: statusDaysColor),
                  const SizedBox(width: 8),
                  Expanded(
                    child: RichText(
                      text: TextSpan(
                        children: [
                          TextSpan(
                            text: 'Terakhir Check-In: ',
                            style: TextStyle(fontSize: 11.5, color: subtitleColor),
                          ),
                          TextSpan(
                            text: '$lastDate ',
                            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: textColor),
                          ),
                          TextSpan(
                            text: '($statusDaysText)',
                            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: statusDaysColor),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // 7 Days Missed Dates breakdown
            if (missedDates.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'Tanggal Tidak Check-In (7 Hari Terakhir):',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor),
              ),
              const SizedBox(height: 5),
              Wrap(
                spacing: 6,
                runSpacing: 4,
                children: missedDates.map((d) {
                  final formattedDate = d['formatted_date'] ?? d['date'] ?? '-';
                  return Container(
                    padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.red.withValues(alpha: isDarkMode ? 0.2 : 0.08),
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(color: Colors.redAccent.withValues(alpha: 0.3)),
                    ),
                    child: Text(
                      formattedDate,
                      style: const TextStyle(
                        fontSize: 10,
                        color: Colors.redAccent,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  );
                }).toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(IconData icon, String label, String value, Color textColor, Color subtitleColor) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 14, color: subtitleColor),
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.w400)),
              const SizedBox(height: 1),
              Text(
                value,
                style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: textColor),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    );
  }
}
