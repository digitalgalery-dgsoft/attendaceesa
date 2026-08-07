import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/sales_provider.dart';
import 'package:toastification/toastification.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

class SalesReportScreen extends StatefulWidget {
  const SalesReportScreen({super.key});

  @override
  State<SalesReportScreen> createState() => _SalesReportScreenState();
}

class _SalesReportScreenState extends State<SalesReportScreen> {
  DateTime _selectedDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<SalesProvider>(context, listen: false).fetchSalesReports();
    });
  }

  void _showAddReportModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => const AddSalesReportForm(),
    );
  }


  void _changeMonth(int offset) {
    setState(() {
      _selectedDate = DateTime(_selectedDate.year, _selectedDate.month + offset, 1);
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final sales = Provider.of<SalesProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    
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
          'Sales Reporting',
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
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                GestureDetector(
                  onTap: () => _changeMonth(-1),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(20)),
                    child: Text('‹', style: TextStyle(fontSize: 18, color: textColor, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 15),
                Text(
                  DateFormat('MMMM yyyy').format(_selectedDate),
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: textColor),
                ),
                const SizedBox(width: 15),
                GestureDetector(
                  onTap: () => _changeMonth(1),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(color: elevatedColor, borderRadius: BorderRadius.circular(20)),
                    child: Text('›', style: TextStyle(fontSize: 18, color: textColor, fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
          ),
          
          Expanded(
            child: Builder(
              builder: (context) {
                final filteredReports = sales.salesReports.where((report) {
                  if (report['report_date'] == null) return false;
                  try {
                    final reportDate = DateTime.parse(report['report_date']);
                    return reportDate.month == _selectedDate.month && reportDate.year == _selectedDate.year;
                  } catch (e) {
                    return false;
                  }
                }).toList();

                return sales.isLoading && sales.salesReports.isEmpty
                    ? Center(child: CircularProgressIndicator(color: primaryColor))
                    : filteredReports.isEmpty
                    ? const Center(
                        child: Text('Belum ada laporan penjualan.'),
                      )
                    : RefreshIndicator(
                        onRefresh: () => sales.fetchSalesReports(),
                        color: primaryColor,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: filteredReports.length,
                          itemBuilder: (context, index) {
                            final report = filteredReports[index];
                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: cardColor,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: Colors.grey.shade300),
                              ),
                                child: InkWell(
                                  borderRadius: BorderRadius.circular(12),
                                  onTap: () {
                                    _showUpdateStatusModal(report);
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
                                              report['store_name'] ?? 'Unknown Store',
                                              style: TextStyle(
                                                color: textColor,
                                                fontWeight: FontWeight.bold,
                                                fontSize: 16,
                                              ),
                                            ),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                              decoration: BoxDecoration(
                                                color: _getStatusColor(report['status']).withOpacity(0.1),
                                                borderRadius: BorderRadius.circular(8),
                                              ),
                                              child: Text(
                                                report['status']?.toUpperCase() ?? 'SUBMITTED',
                                                style: TextStyle(
                                                  color: _getStatusColor(report['status']),
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 12),
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            _buildBadge('OOS: ${report['oos_status'] ?? '-'}', report['oos_status'] == 'Aman' ? Colors.green : Colors.red),
                                            _buildBadge('Plano: ${report['plano_status'] ?? '-'}', report['plano_status'] == 'Sesuai' ? Colors.green : Colors.red),
                                            _buildBadge('Promo: ${report['promo_status'] ?? '-'}', report['promo_status'] == 'Berjalan' ? Colors.green : Colors.red),
                                          ],
                                        ),
                                        const SizedBox(height: 8),
                                        Row(
                                          children: [
                                            const Icon(Icons.calendar_today, size: 16, color: Colors.grey),
                                            const SizedBox(width: 4),
                                            Text(
                                              _formatDateString(report['report_date']),
                                              style: TextStyle(color: subtitleColor, fontSize: 12, fontWeight: FontWeight.w500),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                        );
                }
              ),
            ),
          ],
        ),
        floatingActionButton: FloatingActionButton(
          onPressed: _showAddReportModal,
          child: const Icon(Icons.add, color: Colors.white),
        ),
      );
    }

    Widget _buildBadge(String text, Color color) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          border: Border.all(color: color),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Text(
          text,
          style: TextStyle(fontSize: 10, color: color, fontWeight: FontWeight.bold),
        ),
      );
    }
  
    String _formatDateString(String? dateStr) {
      if (dateStr == null || dateStr.isEmpty) return '';
      try {
        final dt = DateTime.parse(dateStr);
        return DateFormat('dd MMM yyyy').format(dt);
      } catch (e) {
        return dateStr; // fallback if parse fails
      }
    }

    void _showUpdateStatusModal(Map<String, dynamic> report) {
      showModalBottomSheet(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        builder: (context) => UpdateSalesReportForm(report: report),
      );
    }

    Color _getStatusColor(String? status) {
      switch (status?.toLowerCase()) {
        case 'approved':
          return Colors.green;
        case 'rejected':
          return Colors.red;
        case 'submitted':
          return Colors.blue;
        default:
          return Colors.orange;
      }
    }
}

class AddSalesReportForm extends StatefulWidget {
  const AddSalesReportForm({super.key});

  @override
  State<AddSalesReportForm> createState() => _AddSalesReportFormState();
}

class _AddSalesReportFormState extends State<AddSalesReportForm> {
  final _formKey = GlobalKey<FormState>();
  final _storeNameCtrl = TextEditingController();
  final _oosNotesCtrl = TextEditingController();
  final _planoNotesCtrl = TextEditingController();
  final _promoNotesCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  
  String _oosStatus = 'Aman';
  String _planoStatus = 'Sesuai';
  String _promoStatus = 'Berjalan';
  
  XFile? _photoOos;
  XFile? _photoPlano;
  XFile? _photoPromo;
  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImage(String type) async {
    final XFile? image = await _picker.pickImage(source: ImageSource.camera, imageQuality: 70);
    if (image != null) {
      setState(() {
        if (type == 'oos') _photoOos = image;
        else if (type == 'plano') _photoPlano = image;
        else if (type == 'promo') _photoPromo = image;
      });
    }
  }

  Widget _buildPhotoPicker(String title, XFile? file, String type) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: () => _pickImage(type),
          child: Container(
            width: double.infinity,
            height: 120,
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey.shade400, style: BorderStyle.solid),
              borderRadius: BorderRadius.circular(8),
              color: Colors.grey.shade50,
            ),
            child: file != null
                ? (kIsWeb ? Image.network(file.path, fit: BoxFit.cover) : Image.file(File(file.path), fit: BoxFit.cover))
                : Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: const [
                      Icon(Icons.camera_alt, color: Colors.grey, size: 40),
                      SizedBox(height: 8),
                      Text('Ambil Foto', style: TextStyle(color: Colors.grey)),
                    ],
                  ),
          ),
        ),
      ],
    );
  }


  void _changeMonth(int offset) {
    setState(() {
      _selectedDate = DateTime(_selectedDate.year, _selectedDate.month + offset, 1);
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    final sales = Provider.of<SalesProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF7367F0);

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        left: 20,
        right: 20,
        top: 20,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Add Store Report', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 20),
              
              TextFormField(
                controller: _storeNameCtrl,
                decoration: const InputDecoration(labelText: 'Nama Toko/Outlet', border: OutlineInputBorder()),
                validator: (v) => v!.isEmpty ? 'Wajib diisi' : null,
              ),
              const SizedBox(height: 24),
              
              const Text('1. Out of Stock (OOS)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _oosStatus,
                decoration: const InputDecoration(labelText: 'Status OOS', border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'Aman', child: Text('Aman')),
                  DropdownMenuItem(value: 'Kosong', child: Text('Kosong')),
                ],
                onChanged: (v) {
                  if (v != null) setState(() => _oosStatus = v);
                },
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _oosNotesCtrl,
                decoration: const InputDecoration(labelText: 'Catatan OOS', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              _buildPhotoPicker('Foto OOS', _photoOos, 'oos'),
              
              const SizedBox(height: 24),
              const Text('2. Planogram', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _planoStatus,
                decoration: const InputDecoration(labelText: 'Status Planogram', border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'Sesuai', child: Text('Sesuai')),
                  DropdownMenuItem(value: 'Tidak Sesuai', child: Text('Tidak Sesuai')),
                ],
                onChanged: (v) {
                  if (v != null) setState(() => _planoStatus = v);
                },
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _planoNotesCtrl,
                decoration: const InputDecoration(labelText: 'Catatan Planogram', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              _buildPhotoPicker('Foto Planogram', _photoPlano, 'plano'),
              
              const SizedBox(height: 24),
              const Text('3. Promo', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _promoStatus,
                decoration: const InputDecoration(labelText: 'Status Promo', border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'Berjalan', child: Text('Berjalan')),
                  DropdownMenuItem(value: 'Tidak Berjalan', child: Text('Tidak Berjalan')),
                ],
                onChanged: (v) {
                  if (v != null) setState(() => _promoStatus = v);
                },
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _promoNotesCtrl,
                decoration: const InputDecoration(labelText: 'Catatan Promo', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              _buildPhotoPicker('Foto Promo', _photoPromo, 'promo'),

              const SizedBox(height: 24),
              TextFormField(
                controller: _notesCtrl,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Catatan Tambahan', border: OutlineInputBorder()),
              ),
              
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: sales.isLoading ? null : () async {
                    if (_formKey.currentState!.validate()) {
                      final data = {
                        'store_name': _storeNameCtrl.text,
                        'oos_status': _oosStatus,
                        'oos_notes': _oosNotesCtrl.text,
                        'plano_status': _planoStatus,
                        'plano_notes': _planoNotesCtrl.text,
                        'promo_status': _promoStatus,
                        'promo_notes': _promoNotesCtrl.text,
                        'notes': _notesCtrl.text,
                        'status': 'submitted',
                        'photo_oos': _photoOos?.path,
                        'photo_plano': _photoPlano?.path,
                        'photo_promo': _photoPromo?.path,
                      };
                      
                      final result = await sales.submitSalesReport(data);
                      
                      if (result['success']) {
                        if (context.mounted) {
                          Navigator.pop(context);
                          toastification.show(
                            context: context,
                            title: const Text('Success'),
                            description: Text(result['message']),
                            type: ToastificationType.success,
                            autoCloseDuration: const Duration(seconds: 3),
                          );
                        }
                      } else {
                        if (context.mounted) {
                          toastification.show(
                            context: context,
                            title: const Text('Error'),
                            description: Text(result['message']),
                            type: ToastificationType.error,
                            autoCloseDuration: const Duration(seconds: 3),
                          );
                        }
                      }
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  child: sales.isLoading
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Submit Report'),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}

class UpdateSalesReportForm extends StatefulWidget {
  final Map<String, dynamic> report;
  const UpdateSalesReportForm({super.key, required this.report});

  @override
  State<UpdateSalesReportForm> createState() => _UpdateSalesReportFormState();
}

class _UpdateSalesReportFormState extends State<UpdateSalesReportForm> {
  final _formKey = GlobalKey<FormState>();
  final _notesCtrl = TextEditingController();
  String _status = 'submitted';
  bool _isAnalyzing = false;

  @override
  void initState() {
    super.initState();
    _status = widget.report['status'] ?? 'submitted';
    if (_status.toLowerCase() == 'submitted') _status = 'submitted';
    if (_status.toLowerCase() == 'approved') _status = 'approved';
    if (_status.toLowerCase() == 'rejected') _status = 'rejected';
    _notesCtrl.text = widget.report['notes'] ?? '';
  }

  @override
  void dispose() {
    _notesCtrl.dispose();
    super.dispose();
  }

  void _submitUpdate() async {
    if (!_formKey.currentState!.validate()) return;
    
    final sales = Provider.of<SalesProvider>(context, listen: false);
    final result = await sales.updateSalesReportStatus(
      widget.report['id'],
      _status,
      notes: _notesCtrl.text,
    );

    if (mounted) {
      if (result['success']) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Status berhasil diupdate')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Gagal update status')),
        );
      }
    }
  }

  void _handleAnalyze() async {
    setState(() => _isAnalyzing = true);
    final sales = Provider.of<SalesProvider>(context, listen: false);
    final result = await sales.analyzeSalesReport(widget.report['id']);
    
    if (mounted) {
      setState(() => _isAnalyzing = false);
      if (result['success']) {
        _notesCtrl.text = result['analysis'];
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Analisa AI berhasil ditambahkan')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'Gagal melakukan analisa')),
        );
      }
    }
  }


  void _changeMonth(int offset) {
    setState(() {
      _selectedDate = DateTime(_selectedDate.year, _selectedDate.month + offset, 1);
    });
  }

  @override
  Widget build(BuildContext context) {
    final primaryColor = Provider.of<AuthProvider>(context).appColor ?? const Color(0xFF7367F0);
    final sales = Provider.of<SalesProvider>(context);

    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        left: 20,
        right: 20,
        top: 20,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Update Status Laporan',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: primaryColor,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                'Toko: ${widget.report['store_name']}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _isAnalyzing ? null : _handleAnalyze,
                icon: _isAnalyzing
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.auto_awesome, color: Colors.amber),
                label: Text(
                  _isAnalyzing ? 'Sedang menganalisa...' : '✨ Analisa dengan AI',
                  style: TextStyle(
                    color: _isAnalyzing ? Colors.grey : primaryColor,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                style: OutlinedButton.styleFrom(
                  side: BorderSide(color: _isAnalyzing ? Colors.grey : primaryColor),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _status,
                decoration: const InputDecoration(
                  labelText: 'Status',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(value: 'submitted', child: Text('Submitted')),
                  DropdownMenuItem(value: 'approved', child: Text('Approved')),
                  DropdownMenuItem(value: 'rejected', child: Text('Rejected')),
                ],
                onChanged: (value) {
                  if (value != null) setState(() => _status = value);
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _notesCtrl,
                decoration: const InputDecoration(
                  labelText: 'Tambahan Catatan (Update)',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
                maxLines: 3,
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: sales.isLoading ? null : _submitUpdate,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: sales.isLoading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text(
                        'Update Status',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }
}
