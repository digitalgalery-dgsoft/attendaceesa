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

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final sales = Provider.of<SalesProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF7367F0);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Sales Reporting',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Column(
        children: [
          // Top curved background
          Container(
            width: double.infinity,
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
                      DateFormat('MMMM, yyyy').format(_selectedDate),
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const Icon(Icons.calendar_month, color: Colors.blue),
                  ],
                ),
              ),
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
                            return Card(
                              margin: const EdgeInsets.only(bottom: 12),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
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
                                              report['client_name'] ?? 'Unknown Client',
                                              style: const TextStyle(
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
                                                report['status']?.toUpperCase() ?? 'PENDING',
                                                style: TextStyle(
                                                  color: _getStatusColor(report['status']),
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                        if (report['client_company'] != null)
                                          Padding(
                                            padding: const EdgeInsets.only(top: 4),
                                            child: Text(report['client_company'], style: const TextStyle(color: Colors.grey)),
                                          ),
                                        const SizedBox(height: 12),
                                        Row(
                                          children: [
                                            const Icon(Icons.attach_money, size: 16, color: Colors.green),
                                            const SizedBox(width: 4),
                                            Text(
                                              NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ').format(double.tryParse(report['revenue'].toString()) ?? 0),
                                              style: const TextStyle(fontWeight: FontWeight.w600),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 8),
                                        Row(
                                          children: [
                                            const Icon(Icons.calendar_today, size: 16, color: Colors.grey),
                                            const SizedBox(width: 4),
                                            Text(
                                              _formatDateString(report['report_date']),
                                              style: const TextStyle(color: Colors.grey, fontSize: 12),
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
        case 'deal':
        case 'closed_won':
          return Colors.green;
        case 'lost':
        case 'closed_lost':
          return Colors.red;
        case 'follow up':
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
  final _clientNameCtrl = TextEditingController();
  final _clientCompanyCtrl = TextEditingController();
  final _revenueCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  
  String _status = 'pending';
  bool _createPipeline = false;
  XFile? _imageFile;
  final ImagePicker _picker = ImagePicker();

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.camera, imageQuality: 70);
    if (image != null) {
      setState(() {
        _imageFile = image;
      });
    }
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
              const Text('Add Sales Report', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 20),
              
              TextFormField(
                controller: _clientNameCtrl,
                decoration: const InputDecoration(labelText: 'Client Name', border: OutlineInputBorder()),
                validator: (v) => v!.isEmpty ? 'Wajib diisi' : null,
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _clientCompanyCtrl,
                decoration: const InputDecoration(labelText: 'Client Company (Optional)', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 16),
              
              TextFormField(
                controller: _revenueCtrl,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Revenue (Rp)', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 16),

              DropdownButtonFormField<String>(
                value: _status,
                decoration: const InputDecoration(labelText: 'Status', border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'pending', child: Text('Pending')),
                  DropdownMenuItem(value: 'closed', child: Text('Closed')),
                  DropdownMenuItem(value: 'lost', child: Text('Lost')),
                ],
                onChanged: (v) {
                  if (v != null) setState(() => _status = v);
                },
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: _notesCtrl,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Notes', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 16),
              
              Row(
                children: [
                  Checkbox(
                    value: _createPipeline,
                    activeColor: primaryColor,
                    onChanged: (v) {
                      setState(() => _createPipeline = v ?? false);
                    },
                  ),
                  const Text('Add to Sales Pipeline (Leads)'),
                ],
              ),
              const SizedBox(height: 16),

              // Image Picker
              GestureDetector(
                onTap: _pickImage,
                child: Container(
                  width: double.infinity,
                  height: 120,
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade400, style: BorderStyle.solid),
                    borderRadius: BorderRadius.circular(8),
                    color: Colors.grey.shade50,
                  ),
                  child: _imageFile != null
                      ? (kIsWeb ? Image.network(_imageFile!.path, fit: BoxFit.cover) : Image.file(File(_imageFile!.path), fit: BoxFit.cover))
                      : Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: const [
                            Icon(Icons.camera_alt, color: Colors.grey, size: 40),
                            SizedBox(height: 8),
                            Text('Tap to take photo (Receipt/Card)', style: TextStyle(color: Colors.grey)),
                          ],
                        ),
                ),
              ),
              
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: sales.isLoading ? null : () async {
                    if (_formKey.currentState!.validate()) {
                      final data = {
                        'client_name': _clientNameCtrl.text,
                        'client_company': _clientCompanyCtrl.text,
                        'revenue': _revenueCtrl.text,
                        'notes': _notesCtrl.text,
                        'status': _status,
                        'create_pipeline': _createPipeline,
                      };
                      
                      final result = await sales.submitSalesReport(data, imagePath: _imageFile?.path);
                      
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
  String _status = 'Pending';
  bool _isAnalyzing = false;

  @override
  void initState() {
    super.initState();
    _status = widget.report['status'] ?? 'Pending';
    // Capitalize first letter if needed to match options
    if (_status.toLowerCase() == 'pending') _status = 'Pending';
    if (_status.toLowerCase() == 'follow up') _status = 'Follow Up';
    if (_status.toLowerCase() == 'deal') _status = 'Deal';
    if (_status.toLowerCase() == 'lost') _status = 'Lost';
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
                'Klien: ${widget.report['client_name']}',
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
                  DropdownMenuItem(value: 'Pending', child: Text('Pending')),
                  DropdownMenuItem(value: 'Follow Up', child: Text('Follow Up')),
                  DropdownMenuItem(value: 'Deal', child: Text('Deal (Closed Won)')),
                  DropdownMenuItem(value: 'Lost', child: Text('Lost (Closed Lost)')),
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
