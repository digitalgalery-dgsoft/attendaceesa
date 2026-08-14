import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/overtime_provider.dart';
import '../providers/auth_provider.dart';

class OvertimeScreen extends StatefulWidget {
  const OvertimeScreen({Key? key}) : super(key: key);

  @override
  _OvertimeScreenState createState() => _OvertimeScreenState();
}

class _OvertimeScreenState extends State<OvertimeScreen> {
  final TextEditingController _notesController = TextEditingController();
  TimeOfDay _selectedTime = TimeOfDay.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<OvertimeProvider>(context, listen: false).checkStatus().then((_) {
        final prov = Provider.of<OvertimeProvider>(context, listen: false);
        if (prov.isRunning && prov.activeOvertime != null) {
          _notesController.text = prov.activeOvertime!['notes'] ?? '';
        }
      });
    });
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _selectTime(BuildContext context) async {
    final TimeOfDay? picked = await showTimePicker(
      context: context,
      initialTime: _selectedTime,
    );
    if (picked != null && picked != _selectedTime) {
      setState(() {
        _selectedTime = picked;
      });
    }
  }

  void _submitStart() async {
    if (_notesController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Deskripsi pekerjaan harus diisi', style: TextStyle(color: Colors.white)), backgroundColor: Colors.red),
      );
      return;
    }
    
    final prov = Provider.of<OvertimeProvider>(context, listen: false);
    final formattedTime = '${_selectedTime.hour.toString().padLeft(2, '0')}:${_selectedTime.minute.toString().padLeft(2, '0')}';
    
    final res = await prov.startOvertime(formattedTime, _notesController.text.trim());
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(res['message'], style: const TextStyle(color: Colors.white)), 
        backgroundColor: res['success'] ? Colors.green : Colors.red
      ),
    );
  }

  void _submitFinish() async {
    final prov = Provider.of<OvertimeProvider>(context, listen: false);
    final formattedTime = '${_selectedTime.hour.toString().padLeft(2, '0')}:${_selectedTime.minute.toString().padLeft(2, '0')}';
    
    final res = await prov.finishOvertime(formattedTime);
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(res['message'], style: const TextStyle(color: Colors.white)), 
        backgroundColor: res['success'] ? Colors.green : Colors.red
      ),
    );
    
    if (res['success']) {
      Navigator.pop(context); // Kembali ke dashboard
    }
  }

  @override
  Widget build(BuildContext context) {
    final prov = Provider.of<OvertimeProvider>(context);
    final authProvider = Provider.of<AuthProvider>(context);
    final primaryColor = authProvider.appColor ?? const Color(0xFF0F52BA);
    
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final inputFillColor = isDarkMode ? Colors.grey.shade800 : Colors.grey.shade50;

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          'Extra Hours (Lembur)',
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
      body: prov.isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (!prov.isRunning && !prov.canStart && prov.statusMessage.isNotEmpty)
                    Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: isDarkMode ? const Color(0xFF2A2A3D) : const Color(0xFFFFF3E0),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFFFB74D).withOpacity(0.4)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.info_outline, color: Color(0xFFFF9800), size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              prov.statusMessage,
                              style: TextStyle(
                                color: isDarkMode ? const Color(0xFFFFB74D) : const Color(0xFFE65100),
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: cardColor,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        if (!isDarkMode)
                          BoxShadow(
                            color: Colors.black.withOpacity(0.05),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: primaryColor.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Icon(Icons.timer, color: primaryColor, size: 24),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    prov.isRunning ? 'Lembur Sedang Berjalan' : 'Form Pengajuan Lembur',
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: textColor),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    prov.isRunning ? 'Silakan selesaikan lembur Anda' : 'Isi form untuk mulai lembur',
                                    style: TextStyle(color: subtitleColor, fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        if (prov.isRunning) ...[
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.blue.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.blue.withOpacity(0.3)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.info_outline, color: Colors.blue, size: 20),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    'Jam Mulai: ${prov.activeOvertime?['start_time'] ?? '-'}',
                                    style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 16),
                        ],

                        Text(
                          'Deskripsi Pekerjaan',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _notesController,
                          maxLines: 3,
                          enabled: !prov.isRunning && prov.canStart, 
                          style: TextStyle(color: textColor),
                          decoration: InputDecoration(
                            hintText: 'Tuliskan deskripsi pekerjaan lembur...',
                            hintStyle: TextStyle(color: subtitleColor),
                            filled: true,
                            fillColor: inputFillColor,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                          ),
                        ),
                        const SizedBox(height: 20),

                        Text(
                          prov.isRunning ? 'Jam Selesai' : 'Jam Mulai',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor),
                        ),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: (!prov.isRunning && !prov.canStart) ? null : () => _selectTime(context),
                          borderRadius: BorderRadius.circular(10),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            decoration: BoxDecoration(
                              color: inputFillColor,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Colors.grey.withOpacity(0.2)),
                            ),
                            child: Row(
                              children: [
                                Icon(Icons.access_time, color: (!prov.isRunning && !prov.canStart) ? Colors.grey : primaryColor),
                                const SizedBox(width: 12),
                                Text(
                                  _selectedTime.format(context),
                                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500, color: (!prov.isRunning && !prov.canStart) ? Colors.grey : textColor),
                                ),
                                const Spacer(),
                                Icon(Icons.edit, size: 16, color: (!prov.isRunning && !prov.canStart) ? Colors.grey : subtitleColor),
                              ],
                            ),
                          ),
                        ),
                        
                        const SizedBox(height: 32),
                        
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: (!prov.isRunning && !prov.canStart) ? null : (prov.isRunning ? _submitFinish : _submitStart),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: prov.isRunning ? Colors.red.shade400 : primaryColor,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              disabledBackgroundColor: Colors.grey.shade400,
                            ),
                            child: Text(
                              prov.isRunning ? 'Selesai Lembur' : 'Mulai Lembur',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
