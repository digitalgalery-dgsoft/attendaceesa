import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:att_mobile/providers/permit_provider.dart';
import '../providers/auth_provider.dart';
import 'package:att_mobile/screens/permit_form_screen.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:url_launcher/url_launcher.dart';

class PermitScreen extends StatefulWidget {
  const PermitScreen({super.key});

  @override
  State<PermitScreen> createState() => _PermitScreenState();
}

class _PermitScreenState extends State<PermitScreen> {
  DateTime _selectedDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<PermitProvider>(context, listen: false).fetchPermits();
    });
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          'Pengajuan Izin',
          style: TextStyle(
            color: textColor,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
      ),
      body: Consumer<PermitProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.permits.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          final currentMonth = DateFormat('MMMM yyyy').format(_selectedDate);
          
          final filteredPermits = provider.permits.where((permit) {
            if (permit['start_date'] == null) return false;
            try {
              final permitDate = DateTime.parse(permit['start_date']);
              return permitDate.month == _selectedDate.month && permitDate.year == _selectedDate.year;
            } catch (e) {
              return false;
            }
          }).toList();

          return RefreshIndicator(
            onRefresh: () => provider.fetchPermits(),
            child: Column(
              children: [
                // Minimalist Month Picker
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: InkWell(
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: _selectedDate,
                        firstDate: DateTime(2020),
                        lastDate: DateTime(2030),
                        helpText: 'Pilih Bulan & Tahun (Pilih tanggal apa saja)',
                      );
                      if (picked != null) {
                        setState(() {
                          _selectedDate = picked;
                        });
                      }
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      decoration: BoxDecoration(
                        color: elevatedColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            currentMonth,
                            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                          ),
                          Icon(Icons.calendar_month, color: subtitleColor, size: 20),
                        ],
                      ),
                    ),
                  ),
                ),
                
                const SizedBox(height: 8),
                
                // List or Empty state
                Expanded(
                  child: filteredPermits.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            const SizedBox(height: 80),
                            Icon(Icons.event_busy, size: 80, color: Colors.grey.shade400),
                            const SizedBox(height: 16),
                            Center(
                              child: Text(
                                'Tidak ada data izin bulan ini.',
                                style: TextStyle(fontWeight: FontWeight.w500, fontSize: 13, color: subtitleColor),
                              ),
                            ),
                          ],
                        )
                      : ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: filteredPermits.length,
                          itemBuilder: (context, index) {
                            final permit = filteredPermits[index];
                            final type = permit['type'] ?? '';
                            final status = permit['status'] ?? 'pending';
                            final reason = permit['notes'] ?? '';
                            
                            // Format dates
                            String startDate = permit['start_date'] ?? '';
                            String endDate = permit['end_date'] ?? '';
                            String rangeStr = '';
                            int days = 1;
                            try {
                              if (startDate.isNotEmpty && endDate.isNotEmpty) {
                                final start = DateTime.parse(startDate).toLocal();
                                final end = DateTime.parse(endDate).toLocal();
                                startDate = DateFormat('dd MMM yyyy').format(start);
                                endDate = DateFormat('dd MMM yyyy').format(end);
                                days = end.difference(start).inDays + 1;
                                
                                if (start.month == end.month && start.year == end.year) {
                                  if (start.day == end.day) {
                                    rangeStr = '${start.day} ${DateFormat('MMM yyyy').format(start)} · 1 hari';
                                  } else {
                                    rangeStr = '${start.day}–${end.day} ${DateFormat('MMM yyyy').format(start)} · $days hari';
                                  }
                                } else {
                                  rangeStr = '${DateFormat('dd MMM').format(start)} – ${DateFormat('dd MMM yyyy').format(end)} · $days hari';
                                }
                              }
                            } catch (_) {}
                            
                            Color iconBgColor;
                            Color iconColor;
                            Color badgeBgColor;
                            Color badgeTextColor;
                            String badgeText;
                            
                            if (status == 'approved') {
                              iconBgColor = const Color(0xFFE2F6EE);
                              iconColor = const Color(0xFF149A6E);
                              badgeBgColor = const Color(0xFFE2F6EE);
                              badgeTextColor = const Color(0xFF149A6E);
                              badgeText = 'Disetujui';
                            } else if (status == 'rejected') {
                              iconBgColor = const Color(0xFFFCEAE9);
                              iconColor = const Color(0xFFE0473E);
                              badgeBgColor = const Color(0xFFFCEAE9);
                              badgeTextColor = const Color(0xFFE0473E);
                              badgeText = 'Ditolak';
                            } else {
                              iconBgColor = const Color(0xFFFDF0DE);
                              iconColor = const Color(0xFFD98A2B);
                              badgeBgColor = const Color(0xFFFDF0DE);
                              badgeTextColor = const Color(0xFFD98A2B);
                              badgeText = 'Diproses';
                            }
                            
                            return GestureDetector(
                              onTap: () => _showDetailModal(context, permit, startDate, endDate, status),
                              child: Container(
                                margin: const EdgeInsets.only(bottom: 12),
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: cardColor,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(color: Colors.grey.shade300),
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      width: 44, height: 44,
                                      decoration: BoxDecoration(color: iconBgColor, borderRadius: BorderRadius.circular(12)),
                                      child: Icon(Icons.description, color: iconColor, size: 20),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(type.toString().toUpperCase(), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: textColor)),
                                                    const SizedBox(height: 2),
                                                    Text(rangeStr, style: TextStyle(fontSize: 10, color: subtitleColor, fontWeight: FontWeight.w600)),
                                                  ],
                                                ),
                                              ),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                decoration: BoxDecoration(color: badgeBgColor, borderRadius: BorderRadius.circular(8)),
                                                child: Text(badgeText, style: TextStyle(color: badgeTextColor, fontSize: 9, fontWeight: FontWeight.bold)),
                                              )
                                            ],
                                          ),
                                          const SizedBox(height: 8),
                                          Text(
                                            reason.toString().isNotEmpty ? reason : 'Tidak ada keterangan tambahan.',
                                            style: TextStyle(fontSize: 11, color: subtitleColor),
                                            maxLines: 2,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: primaryColor,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: const Icon(Icons.add, color: Colors.white),
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const PermitFormScreen()),
          );
        },
      ),
    );
  }

  void _showDetailModal(BuildContext context, Map<String, dynamic> permit, String startDate, String endDate, String status) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(20.0),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Detail Pengajuan Izin',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: status == 'approved' ? const Color(0xFFE2F6EE) : 
                             status == 'rejected' ? const Color(0xFFFCEAE9) : const Color(0xFFFDF0DE),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      status == 'approved' ? 'Disetujui' : (status == 'rejected' ? 'Ditolak' : 'Diproses'),
                      style: TextStyle(
                        color: status == 'approved' ? const Color(0xFF149A6E) : 
                               status == 'rejected' ? const Color(0xFFE0473E) : const Color(0xFFD98A2B),
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                      ),
                    ),
                  ),
                ],
              ),
              const Divider(height: 30),
              _buildDetailRow('Tipe', (permit['type'] ?? '').toString().toUpperCase()),
              if (permit['sub_type'] != null)
                _buildDetailRow('Sub Tipe', (permit['sub_type'] ?? '').toString().replaceAll('_', ' ').toUpperCase()),
              _buildDetailRow('Tanggal Mulai', startDate),
              _buildDetailRow('Tanggal Selesai', endDate),
              _buildDetailRow('Persetujuan Atasan', (permit['head_approval_status'] ?? 'pending').toString().toUpperCase()),
              if (permit['head_approval_notes'] != null && permit['head_approval_notes'].toString().trim().isNotEmpty)
                _buildDetailRow('Catatan Atasan', permit['head_approval_notes'].toString()),
              _buildDetailRow('Persetujuan HRD', (permit['hrd_approval_status'] ?? 'pending').toString().toUpperCase()),
              if (permit['hrd_approval_notes'] != null && permit['hrd_approval_notes'].toString().trim().isNotEmpty)
                _buildDetailRow('Catatan HRD', permit['hrd_approval_notes'].toString()),
              const SizedBox(height: 10),
              const Text('Keterangan', style: TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              Text(permit['notes'] ?? '-', style: const TextStyle(fontSize: 13)),
              if (permit['attachment_path'] != null) ...[
                const SizedBox(height: 16),
                const Text('Lampiran', style: TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    Constants.getImageUrl(permit['attachment_path']),
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => const Text('Gagal memuat gambar', style: TextStyle(color: Colors.red)),
                  ),
                ),
              ],
              const SizedBox(height: 20),
              if (permit['pdf_url'] != null) ...[
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.picture_as_pdf, color: Colors.white, size: 18),
                    label: const Text('Download Surat Cuti', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                    onPressed: () async {
                      final url = Uri.parse(permit['pdf_url']);
                      if (!await launchUrl(url, mode: LaunchMode.externalApplication)) {
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Could not open PDF URL')),
                          );
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.redAccent,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
              ],
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blueGrey,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: const Text('Tutup', style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
          ),
        );
      },
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: const TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}
