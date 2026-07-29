import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:att_mobile/providers/permit_provider.dart';
import '../providers/auth_provider.dart';
import 'package:att_mobile/screens/permit_form_screen.dart';
import 'package:att_mobile/utils/constants.dart';
import 'package:url_launcher/url_launcher.dart';
import 'permit_form_screen.dart';

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

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Permit',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Consumer<PermitProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.permits.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          final currentMonth = DateFormat('MMMM, yyyy').format(_selectedDate);
          
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
                // Top Calendar Bar
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: primaryColor,
                    borderRadius: const BorderRadius.only(
                      bottomLeft: Radius.circular(24),
                      bottomRight: Radius.circular(24),
                    ),
                  ),
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
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            currentMonth,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                          const Icon(Icons.calendar_month, color: Colors.blue),
                        ],
                      ),
                    ),
                  ),
                ),
                
                const SizedBox(height: 16),
                
                // List or Empty state
                Expanded(
                  child: filteredPermits.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            const SizedBox(height: 50),
                            Center(
                              child: Text(
                                DateFormat('MMM yyyy').format(DateTime.now()),
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                            ),
                            const SizedBox(height: 40),
                            // Placeholder icon/image
                            const Icon(Icons.people_alt, size: 200, color: Colors.blueGrey),
                            const SizedBox(height: 20),
                            const Center(
                              child: Text(
                                'Oopss Data Not Found!',
                                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
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
                            
                            // Format dates
                            String startDate = permit['start_date'] ?? '';
                            String endDate = permit['end_date'] ?? '';
                            try {
                              if (startDate.isNotEmpty) {
                                startDate = DateFormat('dd MMM yyyy').format(DateTime.parse(startDate).toLocal());
                              }
                              if (endDate.isNotEmpty) {
                                endDate = DateFormat('dd MMM yyyy').format(DateTime.parse(endDate).toLocal());
                              }
                            } catch (_) {}
                            
                            return Card(
                              margin: const EdgeInsets.only(bottom: 12),
                              color: Colors.white,
                              elevation: 0.5,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              child: ListTile(
                                onTap: () => _showDetailModal(context, permit, startDate, endDate, status),
                                leading: const CircleAvatar(
                                  backgroundColor: Color(0xFFE8E8E8),
                                  child: Icon(Icons.event_note, color: Colors.blueGrey),
                                ),
                                title: Text(type.toString().toUpperCase(), style: const TextStyle(fontWeight: FontWeight.bold)),
                                subtitle: Text("$startDate s/d $endDate"),
                                trailing: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: status == 'approved' ? Colors.green.withOpacity(0.1) : 
                                           status == 'rejected' ? Colors.red.withOpacity(0.1) : Colors.orange.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    status.toString().toUpperCase(),
                                    style: TextStyle(
                                      color: status == 'approved' ? Colors.green : 
                                             status == 'rejected' ? Colors.red : Colors.orange,
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold
                                    ),
                                  ),
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
        backgroundColor: Colors.blue,
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
                    'Permit Detail',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: status == 'approved' ? Colors.green.withOpacity(0.1) : 
                             status == 'rejected' ? Colors.red.withOpacity(0.1) : Colors.orange.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      status.toUpperCase(),
                      style: TextStyle(
                        color: status == 'approved' ? Colors.green : 
                               status == 'rejected' ? Colors.red : Colors.orange,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              const Divider(height: 30),
              _buildDetailRow('Type', (permit['type'] ?? '').toString().toUpperCase()),
              if (permit['sub_type'] != null)
                _buildDetailRow('Sub Type', (permit['sub_type'] ?? '').toString().replaceAll('_', ' ').toUpperCase()),
              _buildDetailRow('Start Date', startDate),
              _buildDetailRow('End Date', endDate),
              _buildDetailRow('Head Approval', (permit['head_approval_status'] ?? 'pending').toString().toUpperCase()),
              if (permit['head_approval_notes'] != null && permit['head_approval_notes'].toString().trim().isNotEmpty)
                _buildDetailRow('Head Notes', permit['head_approval_notes'].toString()),
              _buildDetailRow('HRD Approval', (permit['hrd_approval_status'] ?? 'pending').toString().toUpperCase()),
              if (permit['hrd_approval_notes'] != null && permit['hrd_approval_notes'].toString().trim().isNotEmpty)
                _buildDetailRow('HRD Notes', permit['hrd_approval_notes'].toString()),
              const SizedBox(height: 10),
              const Text('Notes', style: TextStyle(color: Colors.grey, fontSize: 13)),
              const SizedBox(height: 4),
              Text(permit['notes'] ?? '-', style: const TextStyle(fontSize: 14)),
              if (permit['attachment_path'] != null) ...[
                const SizedBox(height: 16),
                const Text('Attachment', style: TextStyle(color: Colors.grey, fontSize: 13)),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    Constants.getImageUrl(permit['attachment_path']),
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => const Text('Failed to load image', style: TextStyle(color: Colors.red)),
                  ),
                ),
              ],
              const SizedBox(height: 20),
              if (permit['pdf_url'] != null) ...[
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.picture_as_pdf, color: Colors.white),
                    label: const Text('Download Surat Cuti', style: TextStyle(color: Colors.white)),
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
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: const Text('Close', style: TextStyle(color: Colors.white)),
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
            width: 120,
            child: Text(
              label,
              style: const TextStyle(color: Colors.grey, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}
