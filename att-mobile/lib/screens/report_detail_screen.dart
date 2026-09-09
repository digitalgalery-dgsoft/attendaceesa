import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:att_mobile/models/report_submission_model.dart';
import 'package:att_mobile/models/report_template_model.dart';
import 'package:att_mobile/providers/auth_provider.dart';
import 'package:att_mobile/providers/dynamic_reporting_provider.dart';
import 'package:att_mobile/screens/dynamic_form_screen.dart';

class ReportDetailScreen extends StatefulWidget {
  final ReportSubmissionModel submission;

  const ReportDetailScreen({
    super.key,
    required this.submission,
  });

  @override
  State<ReportDetailScreen> createState() => _ReportDetailScreenState();
}

class _ReportDetailScreenState extends State<ReportDetailScreen> {
  late ReportSubmissionModel _currentSubmission;

  @override
  void initState() {
    super.initState();
    _currentSubmission = widget.submission;
  }

  void _openEditScreen() async {
    final repProvider = Provider.of<DynamicReportingProvider>(context, listen: false);
    
    // Temukan template yang cocok
    ReportTemplateModel? targetTemplate = _currentSubmission.template;
    if (targetTemplate == null && repProvider.templates.isNotEmpty) {
      targetTemplate = repProvider.templates.cast<ReportTemplateModel?>().firstWhere(
            (t) => t?.id == _currentSubmission.reportTemplateId || t?.code == _currentSubmission.templateCode,
            orElse: () => null,
          );
    }

    if (targetTemplate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Template form tidak ditemukan untuk diedit.')),
      );
      return;
    }

    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => DynamicFormScreen(
          template: targetTemplate!,
          editSubmission: _currentSubmission,
        ),
      ),
    );

    if (result == true && mounted) {
      final auth = Provider.of<AuthProvider>(context, listen: false);
      if (auth.token != null) {
        await repProvider.fetchHistory(auth.token!);
        // Cari updated submission
        final updated = repProvider.history.cast<ReportSubmissionModel?>().firstWhere(
              (s) => s?.id == _currentSubmission.id,
              orElse: () => null,
            );
        if (updated != null) {
          setState(() {
            _currentSubmission = updated;
          });
        }
      }
    }
  }

  void _showImageDialog(BuildContext context, String imageUrl, String title) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                color: Colors.black,
              ),
              clipBehavior: Clip.antiAlias,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  InteractiveViewer(
                    maxScale: 4.0,
                    child: Image.network(
                      imageUrl,
                      fit: BoxFit.contain,
                      loadingBuilder: (context, child, loadingProgress) {
                        if (loadingProgress == null) return child;
                        return Container(
                          height: 250,
                          alignment: Alignment.center,
                          child: const CircularProgressIndicator(color: Colors.white),
                        );
                      },
                      errorBuilder: (context, error, stackTrace) => Container(
                        height: 200,
                        alignment: Alignment.center,
                        child: const Text('Gagal memuat foto', style: TextStyle(color: Colors.white70)),
                      ),
                    ),
                  ),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
                    color: Colors.black87,
                    child: Text(
                      title,
                      style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            ),
            Positioned(
              top: 8,
              right: 8,
              child: IconButton(
                icon: const Icon(Icons.close_rounded, color: Colors.white, size: 28),
                onPressed: () => Navigator.of(ctx).pop(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthProvider>(context);
    final primaryColor = auth.appColor ?? const Color(0xFF0F52BA);
    final isDarkMode = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDarkMode ? const Color(0xFF121212) : const Color(0xFFE6EAF2);
    final cardColor = isDarkMode ? const Color(0xFF1E1E2C) : Colors.white;
    final textColor = isDarkMode ? Colors.white : const Color(0xFF0E1830);
    final subtitleColor = isDarkMode ? Colors.grey.shade400 : const Color(0xFF707893);
    final elevatedColor = isDarkMode ? Colors.grey.shade800 : const Color(0xFFEDF1F8);

    final isApproved = _currentSubmission.status == 'approved' || _currentSubmission.status == 'verified';
    final isRejected = _currentSubmission.status == 'rejected';

    Color statusBgColor;
    Color statusTextColor;
    IconData statusIcon;

    if (isApproved) {
      statusBgColor = Colors.green.withOpacity(0.15);
      statusTextColor = Colors.green.shade700;
      statusIcon = Icons.check_circle_rounded;
    } else if (isRejected) {
      statusBgColor = Colors.red.withOpacity(0.15);
      statusTextColor = Colors.red.shade700;
      statusIcon = Icons.cancel_rounded;
    } else {
      statusBgColor = Colors.orange.withOpacity(0.15);
      statusTextColor = Colors.orange.shade800;
      statusIcon = Icons.hourglass_top_rounded;
    }

    final dateStr = DateFormat('dd MMMM yyyy, HH:mm').format(_currentSubmission.submittedAt);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(
          _currentSubmission.submissionCode,
          style: TextStyle(color: textColor, fontSize: 16, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
        ),
        backgroundColor: backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: textColor),
        actions: [
          if (_currentSubmission.canEdit)
            IconButton(
              icon: Icon(Icons.edit_rounded, color: primaryColor),
              tooltip: 'Edit Laporan',
              onPressed: _openEditScreen,
            ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        children: [
          // ─── Header Status & Toko ───
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3.5),
                      decoration: BoxDecoration(
                        color: primaryColor.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        _currentSubmission.templateCode ?? 'LAPORAN',
                        style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: primaryColor, fontFamily: 'monospace'),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: statusBgColor,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(statusIcon, size: 14, color: statusTextColor),
                          const SizedBox(width: 5),
                          Text(
                            _currentSubmission.statusLabel,
                            style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: statusTextColor),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  _currentSubmission.templateTitle,
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: textColor, height: 1.25),
                ),
                const SizedBox(height: 8),
                if (_currentSubmission.storeName != null) ...[
                  Row(
                    children: [
                      const Icon(Icons.storefront_rounded, size: 16, color: Color(0xFF0F52BA)),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          _currentSubmission.storeName!,
                          style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: textColor),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                ],
                if (_currentSubmission.address != null && _currentSubmission.address!.isNotEmpty) ...[
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.location_on_rounded, size: 16, color: subtitleColor),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          _currentSubmission.address!,
                          style: TextStyle(fontSize: 11.5, color: subtitleColor),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                ],
                const Divider(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.access_time_rounded, size: 14, color: subtitleColor),
                        const SizedBox(width: 5),
                        Text(dateStr, style: TextStyle(fontSize: 11, color: subtitleColor)),
                      ],
                    ),
                    if (_currentSubmission.isWithinRadius)
                      Row(
                        children: const [
                          Icon(Icons.check_circle_rounded, size: 14, color: Color(0xFF149A6E)),
                          SizedBox(width: 4),
                          Text('Dalam Radius', style: TextStyle(fontSize: 11, color: Color(0xFF149A6E), fontWeight: FontWeight.bold)),
                        ],
                      ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // ─── Status Edit Notification ───
          if (_currentSubmission.canEdit)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.amber.withOpacity(0.12),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.amber.shade600.withOpacity(0.4)),
              ),
              child: Row(
                children: [
                  Icon(Icons.info_outline_rounded, color: Colors.amber.shade800, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Laporan ini berstatus belum Approve. Anda masih dapat mengedit atau memperbaiki data report.',
                      style: TextStyle(fontSize: 11.5, color: Colors.amber.shade900, fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            )
          else
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.green.withOpacity(0.12),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.green.shade600.withOpacity(0.4)),
              ),
              child: Row(
                children: [
                  Icon(Icons.lock_rounded, color: Colors.green.shade800, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Laporan ini telah disetujui (Approve) oleh manajemen dan sudah terkunci.',
                      style: TextStyle(fontSize: 11.5, color: Colors.green.shade900, fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            ),

          // ─── Daftar Nilai / Field Jawaban ───
          Text(
            'RINCIAN DATA LAPORAN',
            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor, letterSpacing: 0.8),
          ),
          const SizedBox(height: 8),

          () {
            // Cek apakah ada list kompetitor dinamis
            bool hasDynamicComp = false;
            for (final v in _currentSubmission.values) {
              if (v.fieldName == 'data_kompetitor_list' || (v.valueText != null && v.valueText!.trim().startsWith('[{') && v.valueText!.contains('harga_'))) {
                hasDynamicComp = true;
                break;
              }
            }

            final suppressFields = {
              'merk_kompetitor',
              'subbrand_kompetitor',
              'harga_kompetitor_tin_rp',
              'harga_kompetitor_galon_rp',
              'harga_kompetitor_pail_rp',
              'merk_kompetitor_sejenis_di_toko',
              'nama_subbrand_kompetitor_yang_dicek',
              'harga_jual_kompetitor_kemasan_galon_2.5l/4-5kg_(rp)',
              'harga_jual_kompetitor_kemasan_pail_20l/25kg_(rp)',
              'harga_jual_kompetitor_kemasan_tin_/_kaleng_1l/1kg_(rp)',
            };

            final displayValues = _currentSubmission.values.where((val) {
              if (hasDynamicComp) {
                final fn = val.fieldName.toLowerCase();
                final fl = val.fieldLabel.toLowerCase().replaceAll(' ', '_');
                if (suppressFields.contains(fn) || suppressFields.contains(fl)) {
                  return false;
                }
              }
              return true;
            }).toList();

            if (displayValues.isEmpty) {
              return Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Center(
                  child: Text('Tidak ada rincian data tersimpan.', style: TextStyle(color: subtitleColor, fontSize: 12.5)),
                ),
              );
            }

            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: displayValues.map((val) => _buildValueCard(val, cardColor, textColor, subtitleColor, elevatedColor, primaryColor, isDarkMode)).toList(),
            );
          }(),

          const SizedBox(height: 20),

          // ─── Tombol Edit Laporan (Jika belum Approve) ───
          if (_currentSubmission.canEdit) ...[
            ElevatedButton.icon(
              onPressed: _openEditScreen,
              icon: const Icon(Icons.edit_note_rounded, color: Colors.white, size: 20),
              label: const Text(
                'Edit Data Laporan Ini',
                style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 2,
              ),
            ),
            const SizedBox(height: 30),
          ],
        ],
      ),
    );
  }

  Widget _buildCompetitorListCard(
    List<dynamic> items,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    Color primaryColor,
    bool isDarkMode,
  ) {
    final currencyFmt = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: primaryColor.withOpacity(0.35), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(Icons.store_mall_directory_rounded, size: 18, color: primaryColor),
                  const SizedBox(width: 8),
                  Text(
                    'Data Produk Kompetitor',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: primaryColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '${items.length} Kompetitor',
                  style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: primaryColor),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...items.asMap().entries.map((entry) {
            final idx = entry.key;
            final item = entry.value;
            if (item is! Map) return const SizedBox.shrink();

            final merk = item['merk']?.toString() ?? 'Kompetitor';
            final subbrand = item['subbrand']?.toString() ?? '-';

            num parsePrice(dynamic val) {
              if (val == null) return 0;
              if (val is num) return val;
              if (val is String) {
                final clean = val.replaceAll(RegExp(r'[^0-9]'), '');
                return num.tryParse(clean) ?? 0;
              }
              return 0;
            }

            final pTin = parsePrice(item['harga_tin']);
            final pGalon = parsePrice(item['harga_galon']);
            final pPail = parsePrice(item['harga_pail']);

            return Container(
              margin: EdgeInsets.only(bottom: idx == items.length - 1 ? 0 : 10),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isDarkMode ? Colors.black26 : const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: primaryColor,
                          borderRadius: BorderRadius.circular(5),
                        ),
                        child: Text(
                          '#${idx + 1}',
                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
                        decoration: BoxDecoration(
                          color: const Color(0xFFE0F2FE),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFFBAE6FD)),
                        ),
                        child: Text(
                          merk.toUpperCase(),
                          style: const TextStyle(color: Color(0xFF0369A1), fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          subbrand,
                          style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.bold, color: textColor),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 6,
                    children: [
                      _buildPriceBadge('Galon (2.5L/4-5Kg)', pGalon, currencyFmt, isDarkMode, highlight: true),
                      if (pTin > 0)
                        _buildPriceBadge('Tin (1L/1Kg)', pTin, currencyFmt, isDarkMode),
                      if (pPail > 0)
                        _buildPriceBadge('Pail (20L/25Kg)', pPail, currencyFmt, isDarkMode),
                      if (pTin == 0 && pPail == 0 && pGalon == 0)
                        Text('Tidak ada harga tercatat', style: TextStyle(fontSize: 11, color: subtitleColor)),
                    ],
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildPriceBadge(String label, num price, NumberFormat fmt, bool isDarkMode, {bool highlight = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: isDarkMode ? Colors.grey.shade900 : Colors.white,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$label: ',
            style: TextStyle(fontSize: 10.5, color: isDarkMode ? Colors.grey.shade400 : Colors.grey.shade600),
          ),
          Text(
            price > 0 ? fmt.format(price) : 'Rp 0',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: price > 0 ? (highlight ? const Color(0xFF149A6E) : (isDarkMode ? Colors.white : Colors.black87)) : Colors.grey,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildValueCard(
    ReportSubmissionValueModel val,
    Color cardColor,
    Color textColor,
    Color subtitleColor,
    Color elevatedColor,
    Color primaryColor,
    bool isDarkMode,
  ) {
    // Cek apakah field ini adalah data_kompetitor_list
    final isCompList = val.fieldName == 'data_kompetitor_list' || (val.fieldName.contains('kompetitor') && !val.fieldName.contains('offtake'));
    List<dynamic>? compItems;
    if (isCompList || (val.fieldName != 'offtake_items_json' && val.valueText != null && val.valueText!.trim().startsWith('[{') && val.valueText!.contains('harga_kompetitor'))) {
      if (val.valueJson is List) {
        compItems = val.valueJson as List;
      } else if (val.valueText != null && val.valueText!.isNotEmpty) {
        try {
          final decoded = jsonDecode(val.valueText!);
          if (decoded is List) compItems = decoded;
        } catch (_) {}
      }
    }

    if (compItems != null && compItems.isNotEmpty) {
      return _buildCompetitorListCard(compItems, cardColor, textColor, subtitleColor, elevatedColor, primaryColor, isDarkMode);
    }
    final isMedia = ['photo', 'camera_photo', 'multi_photo', 'signature'].contains(val.fieldType) || val.mediaFullUrl != null || val.mediaFullUrls.isNotEmpty;
    final hasMedia = val.mediaFullUrls.isNotEmpty || (val.mediaFullUrl != null && val.mediaFullUrl!.isNotEmpty);

    String displayValue = val.valueText ?? '-';
    if (val.fieldType == 'currency' && val.valueNumber != null) {
      displayValue = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0).format(val.valueNumber);
    } else if (val.fieldType == 'number' && val.valueNumber != null) {
      displayValue = val.valueNumber! % 1 == 0 ? val.valueNumber!.toInt().toString() : val.valueNumber.toString();
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            val.fieldLabel,
            style: TextStyle(fontSize: 11.5, color: subtitleColor, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          if (isMedia && hasMedia) ...[
            if (val.mediaFullUrls.length > 1) ...[
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: val.mediaFullUrls.asMap().entries.map((entry) {
                  final idx = entry.key;
                  final url = entry.value;
                  return GestureDetector(
                    onTap: () => _showImageDialog(context, url, '${val.fieldLabel} (${idx + 1}/${val.mediaFullUrls.length})'),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: Stack(
                        alignment: Alignment.bottomRight,
                        children: [
                          Image.network(
                            url,
                            width: 100,
                            height: 100,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) => Container(
                              width: 100,
                              height: 100,
                              color: elevatedColor,
                              alignment: Alignment.center,
                              child: const Icon(Icons.broken_image_rounded, size: 24, color: Colors.grey),
                            ),
                          ),
                          Container(
                            margin: const EdgeInsets.all(4),
                            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: Colors.black87,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              'Foto ${idx + 1}',
                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
            ] else ...[
              GestureDetector(
                onTap: () => _showImageDialog(context, val.mediaFullUrl!, val.fieldLabel),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Stack(
                    alignment: Alignment.bottomRight,
                    children: [
                      Image.network(
                        val.mediaFullUrl!,
                        height: 180,
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Container(
                          height: 100,
                          color: elevatedColor,
                          alignment: Alignment.center,
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.broken_image_rounded, color: subtitleColor, size: 28),
                              const SizedBox(height: 4),
                              Text('Gagal memuat gambar', style: TextStyle(color: subtitleColor, fontSize: 11)),
                            ],
                          ),
                        ),
                      ),
                      Container(
                        margin: const EdgeInsets.all(8),
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black87,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: const [
                            Icon(Icons.zoom_in_rounded, color: Colors.white, size: 14),
                            SizedBox(width: 4),
                            Text('Lihat Foto', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ] else ...[
            Text(
              displayValue,
              style: TextStyle(
                fontSize: 13.5,
                fontWeight: FontWeight.bold,
                color: val.fieldType == 'currency' ? const Color(0xFF149A6E) : textColor,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
