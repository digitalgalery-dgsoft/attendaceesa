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
          () {
            final tmplCode = (_currentSubmission.templateCode ?? _currentSubmission.template?.code ?? '').toUpperCase();
            final tmplTitle = (_currentSubmission.templateTitle).toUpperCase();
            final hasOosField = _currentSubmission.values.any((v) => v.fieldName.toLowerCase().contains('oos'));
            final isOosReport = tmplCode.contains('OOS') || tmplTitle.contains('OUT OF STOCK') || tmplTitle.contains('OOS') || hasOosField;
            final isOfftakeReport = !isOosReport && (tmplCode.contains('OFFTAKE') || tmplTitle.contains('OFFTAKE') || tmplTitle.contains('PENJUALAN'));

            // Cek apakah ada data offtake multi-produk dinamis (hanya jika BUKAN laporan OOS)
            List<dynamic>? offtakeItemsList;
            if (!isOosReport) {
              for (final v in _currentSubmission.values) {
                final fn = v.fieldName.toLowerCase();
                if (fn == 'offtake_items_json' || fn.contains('offtake_items')) {
                  if (v.valueJson is List && (v.valueJson as List).isNotEmpty) {
                    offtakeItemsList = v.valueJson as List;
                    break;
                  } else if (v.valueText != null && v.valueText!.trim().isNotEmpty) {
                    try {
                      final decoded = jsonDecode(v.valueText!);
                      if (decoded is List && decoded.isNotEmpty) {
                        offtakeItemsList = decoded;
                        break;
                      }
                    } catch (_) {}
                  }
                } else if (v.valueText != null && v.valueText!.trim().startsWith('[{') && (fn.contains('offtake') || fn.contains('rincian_transaksi'))) {
                  try {
                    final decoded = jsonDecode(v.valueText!);
                    if (decoded is List && decoded.isNotEmpty) {
                      offtakeItemsList = decoded;
                      break;
                    }
                  } catch (_) {}
                }
              }
            }

            final hasDynamicOfftake = !isOosReport && offtakeItemsList != null && offtakeItemsList.isNotEmpty;

            // Cek apakah ada data OOS multi-produk dinamis
            List<dynamic>? oosItemsList;
            String? tipeLaporanOos;
            for (final v in _currentSubmission.values) {
              final fn = v.fieldName.toLowerCase();
              if (fn == 'tipe_laporan_oos') {
                tipeLaporanOos = v.valueText?.toLowerCase();
              }
              if (fn == 'oos_items_json' || fn.contains('oos_items')) {
                if (v.valueJson is List && (v.valueJson as List).isNotEmpty) {
                  oosItemsList = v.valueJson as List;
                  break;
                } else if (v.valueText != null && v.valueText!.trim().isNotEmpty) {
                  try {
                    final decoded = jsonDecode(v.valueText!);
                    if (decoded is List && decoded.isNotEmpty) {
                      oosItemsList = decoded;
                      break;
                    }
                  } catch (_) {}
                }
              }
            }
            final hasDynamicOos = oosItemsList != null && oosItemsList.isNotEmpty;
            final isNoOos = tipeLaporanOos == 'no_oos' || _currentSubmission.values.any((v) {
              final val = (v.valueText ?? '').toLowerCase();
              return val.contains('no oos') || val.contains('stok lengkap');
            });

            // Cek apakah ada data Stock End multi-produk dinamis
            List<dynamic>? stockItemsList;
            for (final v in _currentSubmission.values) {
              final fn = v.fieldName.toLowerCase();
              if (fn == 'stock_items_json' || fn.contains('stock_items')) {
                if (v.valueJson is List && (v.valueJson as List).isNotEmpty) {
                  stockItemsList = v.valueJson as List;
                  break;
                } else if (v.valueText != null && v.valueText!.trim().isNotEmpty) {
                  try {
                    final decoded = jsonDecode(v.valueText!);
                    if (decoded is List && decoded.isNotEmpty) {
                      stockItemsList = decoded;
                      break;
                    }
                  } catch (_) {}
                }
              }
            }
            final hasDynamicStock = stockItemsList != null && stockItemsList.isNotEmpty;
            int stockTotalSku = 0;
            double stockTotalVolume = 0;
            int stockTotalGalon = 0;
            int stockTotalPail = 0;
            int stockTotalTinter = 0;
            if (hasDynamicStock) {
              stockTotalSku = stockItemsList.length;
              for (final it in stockItemsList) {
                if (it is! Map) continue;
                stockTotalVolume += (it['volume_liter'] as num?)?.toDouble() ?? double.tryParse(it['volume_liter']?.toString() ?? '0') ?? 0.0;
                stockTotalGalon += (it['kuantiti_galon'] as num?)?.toInt() ?? int.tryParse(it['kuantiti_galon']?.toString() ?? '0') ?? 0;
                stockTotalPail += (it['kuantiti_pail'] as num?)?.toInt() ?? int.tryParse(it['kuantiti_pail']?.toString() ?? '0') ?? 0;
                stockTotalTinter += (it['qty_kaleng_tinta'] as num?)?.toInt() ?? int.tryParse(it['qty_kaleng_tinta']?.toString() ?? '0') ?? 0;
              }
            }

            final suppressStockFields = {
              'stock_items_json',
              'produk',
              'brand',
              'volume_liter',
              'kemasan_galon',
              'kemasan_pail',
              'kuantiti_galon',
              'kuantiti_pail',
              'tipe_tinter_warna',
              'qty_kaleng_tinta',
              'conf',
              'total_volume_stok_liter',
              'total_sku_stok',
            };

            final suppressOosFields = {
              'oos_items_json',
              'tipe_laporan_oos',
              'produk_oos',
              'nama_produk_yang_kosong_oos',
              'pilih_produk_dulux_yang_mengalami_out_of_stock_oos',
              'kemasan_size_oos',
              'ukuran_kemasan_size',
              'base_warna_oos',
              'base_tipe_warna',
              'warna_ready_mix_oos',
              'lama_oos_hari',
              'lama_kondisi_barang_kosong_jumlah_hari',
              'saran_qty_order',
              'saran_kuantiti_order_ke_toko_qty_kemasan',
              'alasan_oos',
              'penyebab_alasan_out_of_stock_oos',
            };

            // Cek apakah ada list kompetitor dinamis
            bool hasDynamicComp = false;
            for (final v in _currentSubmission.values) {
              if (v.fieldName == 'data_kompetitor_list' || (v.valueText != null && v.valueText!.trim().startsWith('[{') && v.valueText!.contains('harga_'))) {
                hasDynamicComp = true;
                break;
              }
            }

            final suppressCompFields = {
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

            final suppressOfftakeFields = {
              'offtake_items_json',
              'rincian_transaksi_produk_(json)',
              'rincian_transaksi_produk',
              'subbrand',
              'produk_terjual',
              'subbrand_produk',
              'brand',
              'brand_rm_base',
              'sub_brand1',
              'sub_brand2',
              'pilih_produk_sub_brand',
              'pilih_produk_/_sub_brand',
              'sub_brand_spesifik_/_varian_(sub_brand_1)',
              'detail_rm_/_base_(sub_brand_2)',
              'kemasan_tin',
              'kemasan_galon',
              'kemasan_pail',
              'qty_tin',
              'qty_galon',
              'qty_pail',
              'kuantiti_tin_terjual_(unit)',
              'kuantiti_galon_terjual_(unit)',
              'kuantiti_pail_terjual_(unit)',
              'volume_tin_l',
              'volume_galon_l',
              'volume_pail_l',
              'volume_tin_(liter)',
              'volume_galon_(liter)',
              'volume_pail_(liter)',
              'total_volume_unit',
              'total_volume_liter',
              'total_nilai_sales_rp',
              'grand_total_nilai_penjualan_(rupiah)',
              'grand_total_volume_penjualan_(liter)',
              'grand_total_kuantiti_unit_(tin_+_galon_+_pail)',
              'estimasi_market_share_persen',
              'estimasi_market_share_(%)',
              'tipe_transaksi_hari_ini',
              'tipe_laporan_offtake',
            };

            // Hitung metrik akumulatif Offtake jika ada
            double totalNilaiSalesRp = 0;
            double totalVolumeLiter = 0;
            int totalVolumeUnit = 0;
            int jmlCustMasuk = 0;
            int jmlCustBeliCat = 0;
            int jmlCustBeliDulux = 0;
            String? marketShare;

            if (hasDynamicOfftake) {
              for (final item in offtakeItemsList) {
                if (item is! Map) continue;
                final qT = (item['qty_tin'] as num?)?.toDouble() ?? 0;
                final qG = (item['qty_galon'] as num?)?.toDouble() ?? 0;
                final qP = (item['qty_pail'] as num?)?.toDouble() ?? 0;
                final u = (item['total_unit'] as num?)?.toInt() ?? (qT + qG + qP).toInt();
                final vT = (item['volume_tin_l'] as num?)?.toDouble() ?? 0;
                final vG = (item['volume_galon_l'] as num?)?.toDouble() ?? 0;
                final vP = (item['volume_pail_l'] as num?)?.toDouble() ?? 0;
                final l = (item['total_liter'] as num?)?.toDouble() ?? (vT + vG + vP);
                final rp = (item['total_nilai_rp'] as num?)?.toDouble() ?? 0;
                
                totalVolumeUnit += u;
                totalVolumeLiter += l;
                totalNilaiSalesRp += rp;
              }
            }

            for (final v in _currentSubmission.values) {
              final fn = v.fieldName.toLowerCase();
              final fl = v.fieldLabel.toLowerCase().replaceAll(' ', '_');
              if (fn == 'total_nilai_sales_rp' || fl.contains('grand_total_nilai')) {
                if (totalNilaiSalesRp == 0 && v.valueNumber != null) {
                  totalNilaiSalesRp = v.valueNumber!;
                }
              } else if (fn == 'total_volume_liter' || fl.contains('grand_total_volume')) {
                if (totalVolumeLiter == 0 && v.valueNumber != null) {
                  totalVolumeLiter = v.valueNumber!;
                }
              } else if (fn == 'total_volume_unit' || fl.contains('grand_total_kuantiti')) {
                if (totalVolumeUnit == 0 && v.valueNumber != null) {
                  totalVolumeUnit = v.valueNumber!.toInt();
                }
              } else if (fn == 'jml_customer_masuk' || fl.contains('jumlah_customer_masuk')) {
                jmlCustMasuk = v.valueNumber?.toInt() ?? int.tryParse(v.valueText ?? '') ?? 0;
              } else if (fn == 'jml_customer_beli_cat' || fl.contains('jumlah_cust_yang_beli_cat')) {
                jmlCustBeliCat = v.valueNumber?.toInt() ?? int.tryParse(v.valueText ?? '') ?? 0;
              } else if (fn == 'jml_customer_beli_dulux' || fl.contains('jumlah_cust_yang_beli_produk_dulux')) {
                jmlCustBeliDulux = v.valueNumber?.toInt() ?? int.tryParse(v.valueText ?? '') ?? 0;
              } else if (fn == 'estimasi_market_share_persen' || fl.contains('market_share')) {
                marketShare = v.valueText;
              }
            }

            if (marketShare == null || marketShare == '0%' || marketShare == '0') {
              if (jmlCustBeliCat > 0) {
                final ms = ((jmlCustBeliDulux / jmlCustBeliCat) * 100).round();
                marketShare = '$ms%';
              } else if (jmlCustBeliDulux > 0) {
                marketShare = '100%';
              } else {
                marketShare = '0%';
              }
            }

            final displayValues = _currentSubmission.values.where((val) {
              final fn = val.fieldName.toLowerCase();
              final fl = val.fieldLabel.toLowerCase().replaceAll(' ', '_');
              final rawVal = val.valueText?.trim() ?? '';

              // Supresi field JSON teknis / mentah agar tidak tampil di UI mobile
              if (fn.contains('json') || fl.contains('json')) {
                return false;
              }
              if ((rawVal.startsWith('[') && rawVal.endsWith(']')) || (rawVal.startsWith('{') && rawVal.endsWith('}'))) {
                return false;
              }

              if (hasDynamicComp) {
                if (suppressCompFields.contains(fn) || suppressCompFields.contains(fl)) {
                  return false;
                }
              }
              if (hasDynamicOfftake) {
                if (suppressOfftakeFields.contains(fn) || suppressOfftakeFields.contains(fl) || fn.contains('grand_total') || fl.contains('grand_total')) {
                  return false;
                }
              }
              if (hasDynamicOos || isNoOos) {
                if (suppressOosFields.contains(fn) || suppressOosFields.contains(fl)) {
                  return false;
                }
              }
              if (hasDynamicStock) {
                if (suppressStockFields.contains(fn) || suppressStockFields.contains(fl)) {
                  return false;
                }
              }
              return true;
            }).toList();

            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // ── 0. Banner No OOS (Stok Lengkap) ──
                if (isNoOos) ...[
                  Container(
                    margin: const EdgeInsets.only(bottom: 14),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isDarkMode ? const Color(0xFF14532D).withOpacity(0.25) : const Color(0xFFF0FDF4),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFF22C55E).withOpacity(0.35)),
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: const Color(0xFF22C55E).withOpacity(0.15),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.check_circle_rounded, color: Color(0xFF16A34A), size: 28),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Stok Lengkap (No OOS)',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: isDarkMode ? Colors.green.shade200 : const Color(0xFF15803D)),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Toko memiliki ketersediaan barang lengkap untuk seluruh SKU Dulux & Catylac.',
                                style: TextStyle(fontSize: 12, color: subtitleColor),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],

                // ── 1. Panel Ringkasan KPI Global Offtake ──
                if (hasDynamicOfftake) ...[
                  _buildOfftakeSummaryGrid(
                    totalNilaiSalesRp: totalNilaiSalesRp,
                    totalVolumeLiter: totalVolumeLiter,
                    totalVolumeUnit: totalVolumeUnit,
                    offtakeCount: offtakeItemsList.length,
                    jmlCustMasuk: jmlCustMasuk,
                    jmlCustBeliCat: jmlCustBeliCat,
                    jmlCustBeliDulux: jmlCustBeliDulux,
                    marketShare: marketShare,
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                  ),
                  const SizedBox(height: 8),

                  // ── 2. Kartu Rincian Produk Terjual ──
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'RINCIAN PRODUK TERJUAL',
                        style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor, letterSpacing: 0.8),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: primaryColor.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '${offtakeItemsList.length} Produk Terjual',
                          style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: primaryColor),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ...offtakeItemsList.asMap().entries.map((entry) {
                    final idx = entry.key;
                    final item = entry.value;
                    if (item is! Map) return const SizedBox.shrink();
                    return _buildOfftakeProductCard(
                      index: idx + 1,
                      item: Map<String, dynamic>.from(item),
                      cardColor: cardColor,
                      textColor: textColor,
                      subtitleColor: subtitleColor,
                      elevatedColor: elevatedColor,
                      primaryColor: primaryColor,
                      isDarkMode: isDarkMode,
                    );
                  }),
                  const SizedBox(height: 12),
                ],

                // ── 1.5. Panel Ringkasan KPI Global Out of Stock (OOS) ──
                if (hasDynamicOos) ...[
                  _buildOosSummaryGrid(
                    oosItems: oosItemsList,
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                  ),
                  const SizedBox(height: 8),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'RINCIAN PRODUK OUT OF STOCK (OOS)',
                        style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor, letterSpacing: 0.8),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.red.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '${oosItemsList.length} SKU OOS',
                          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Colors.red),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ...oosItemsList.asMap().entries.map((entry) {
                    final idx = entry.key;
                    final item = entry.value;
                    if (item is! Map) return const SizedBox.shrink();
                    return _buildOosProductCard(
                      index: idx + 1,
                      item: Map<String, dynamic>.from(item),
                      cardColor: cardColor,
                      textColor: textColor,
                      subtitleColor: subtitleColor,
                      elevatedColor: elevatedColor,
                      primaryColor: primaryColor,
                      isDarkMode: isDarkMode,
                    );
                  }),
                  const SizedBox(height: 12),
                ],

                // ── 1.6. Panel Ringkasan KPI Global Stock End ──
                if (hasDynamicStock) ...[
                  _buildStockSummaryGrid(
                    stockItems: stockItemsList!,
                    totalSku: stockTotalSku,
                    totalVolume: stockTotalVolume,
                    totalGalon: stockTotalGalon,
                    totalPail: stockTotalPail,
                    totalTinter: stockTotalTinter,
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                  ),
                  const SizedBox(height: 8),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'RINCIAN PRODUK & STOK AKHIR',
                        style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor, letterSpacing: 0.8),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F52BA).withOpacity(0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          '$stockTotalSku Produk Dilaporkan',
                          style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.bold, color: Color(0xFF0F52BA)),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ...stockItemsList!.asMap().entries.map((entry) {
                    final idx = entry.key;
                    final item = entry.value;
                    if (item is! Map) return const SizedBox.shrink();
                    return _buildStockProductCard(
                      index: idx + 1,
                      item: Map<String, dynamic>.from(item),
                      cardColor: cardColor,
                      textColor: textColor,
                      subtitleColor: subtitleColor,
                      elevatedColor: elevatedColor,
                      primaryColor: primaryColor,
                      isDarkMode: isDarkMode,
                    );
                  }),
                  const SizedBox(height: 12),
                ],

                // ── 3. Parameter Tambahan / Standar Form ──
                if (displayValues.isNotEmpty) ...[
                  Text(
                    (hasDynamicOfftake || hasDynamicOos || isNoOos || hasDynamicStock) ? 'PARAMETER TAMBAHAN' : 'RINCIAN DATA LAPORAN',
                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: subtitleColor, letterSpacing: 0.8),
                  ),
                  const SizedBox(height: 8),
                  ...displayValues.map((val) => _buildValueCard(val, cardColor, textColor, subtitleColor, elevatedColor, primaryColor, isDarkMode)),
                ] else if (!hasDynamicOfftake && !hasDynamicOos && !isNoOos && !hasDynamicStock) ...[
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: cardColor,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Center(
                      child: Text('Tidak ada rincian data tersimpan.', style: TextStyle(color: subtitleColor, fontSize: 12.5)),
                    ),
                  ),
                ],
              ],
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

  // ─── HELPER WIDGETS DETAIL OFFTAKE PERSIS DASHBOARD WEB ───
  Widget _buildOfftakeSummaryGrid({
    required double totalNilaiSalesRp,
    required double totalVolumeLiter,
    required int totalVolumeUnit,
    required int offtakeCount,
    required int jmlCustMasuk,
    required int jmlCustBeliCat,
    required int jmlCustBeliDulux,
    required String marketShare,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
  }) {
    final currencyFmt = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.monetization_on_rounded,
                  iconColor: const Color(0xFF10B981),
                  iconBgColor: const Color(0xFF10B981).withOpacity(0.12),
                  label: 'Grand Total Penjualan',
                  value: currencyFmt.format(totalNilaiSalesRp),
                  valueColor: const Color(0xFF15803D),
                  subText: 'Akumulasi $offtakeCount produk terjual',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.opacity_rounded,
                  iconColor: const Color(0xFF0F52BA),
                  iconBgColor: const Color(0xFF0F52BA).withOpacity(0.12),
                  label: 'Grand Total Volume',
                  value: '${totalVolumeLiter.toStringAsFixed(2)} L',
                  valueColor: const Color(0xFF0F52BA),
                  subText: 'Total volume cat terjual',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.inventory_2_rounded,
                  iconColor: const Color(0xFF6366F1),
                  iconBgColor: const Color(0xFF6366F1).withOpacity(0.12),
                  label: 'Total Kuantiti Terjual',
                  value: '$totalVolumeUnit Unit',
                  valueColor: const Color(0xFF4F46E5),
                  subText: 'Akumulasi Tin + Galon + Pail',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.people_alt_rounded,
                  iconColor: const Color(0xFFF59E0B),
                  iconBgColor: const Color(0xFFF59E0B).withOpacity(0.12),
                  label: 'Traffic & Market Share',
                  value: marketShare,
                  valueColor: const Color(0xFFB45309),
                  subText: 'Masuk: $jmlCustMasuk | Beli Cat: $jmlCustBeliCat | Dulux: $jmlCustBeliDulux',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildOfftakeStatCard({
    required IconData icon,
    required Color iconColor,
    required Color iconBgColor,
    required String label,
    required String value,
    required Color valueColor,
    required String subText,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
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
                  color: iconBgColor,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, size: 16, color: iconColor),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: subtitleColor),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: valueColor),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 3),
          Text(
            subText,
            style: TextStyle(fontSize: 9, color: subtitleColor),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildOfftakeProductCard({
    required int index,
    required Map<String, dynamic> item,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color elevatedColor,
    required Color primaryColor,
    required bool isDarkMode,
  }) {
    final currencyFmt = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    final pBrand = (item['brand']?.toString() ?? 'Dulux').trim();
    final pName = (item['sub_brand']?.toString() ?? item['product_name']?.toString() ?? 'Produk Cat').trim();
    final pRmBase = (item['brand_rm_base']?.toString() ?? '-').trim();

    num parseNum(dynamic val) {
      if (val == null) return 0;
      if (val is num) return val;
      if (val is String) {
        final clean = val.replaceAll(RegExp(r'[^0-9.]'), '');
        return num.tryParse(clean) ?? 0;
      }
      return 0;
    }

    final hTin = parseNum(item['harga_tin']);
    final hGalon = parseNum(item['harga_galon']);
    final hPail = parseNum(item['harga_pail']);

    final qTin = parseNum(item['qty_tin']).toInt();
    final qGalon = parseNum(item['qty_galon']).toInt();
    final qPail = parseNum(item['qty_pail']).toInt();
    final totUnit = item['total_unit'] != null ? parseNum(item['total_unit']).toInt() : (qTin + qGalon + qPail);

    final vTin = parseNum(item['volume_tin_l']).toDouble();
    final vGalon = parseNum(item['volume_galon_l']).toDouble();
    final vPail = parseNum(item['volume_pail_l']).toDouble();
    final totLiter = item['total_liter'] != null ? parseNum(item['total_liter']).toDouble() : (vTin + vGalon + vPail);

    final totRp = parseNum(item['total_nilai_rp']).toDouble();

    final isCatylac = pBrand.toLowerCase().contains('catylac');
    final brandColor = isCatylac ? const Color(0xFFEA580C) : const Color(0xFF0F52BA);
    final brandBgColor = isCatylac ? const Color(0xFFFFF7ED) : const Color(0xFFEFF6FF);
    final brandBorderColor = isCatylac ? const Color(0xFFFDBA74) : const Color(0xFFBFDBFE);

    final kGalon = item['kemasan_galon']?.toString() ?? '2.5L';
    final kPail = item['kemasan_pail']?.toString() ?? '20L';
    final kTin = item['kemasan_tin']?.toString() ?? '1L';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Header Card Produk ──
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: primaryColor,
                  borderRadius: BorderRadius.circular(5),
                ),
                child: Text(
                  '#$index',
                  style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(
                  color: brandBgColor,
                  borderRadius: BorderRadius.circular(5),
                  border: Border.all(color: brandBorderColor, width: 0.8),
                ),
                child: Text(
                  pBrand.toUpperCase(),
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: brandColor),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      pName,
                      style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: textColor),
                    ),
                    if (pRmBase.isNotEmpty && pRmBase != '-' && pRmBase.toLowerCase() != pBrand.toLowerCase()) ...[
                      const SizedBox(height: 2),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: elevatedColor,
                          borderRadius: BorderRadius.circular(4),
                          border: Border.all(color: isDarkMode ? Colors.grey.shade700 : Colors.grey.shade300, width: 0.6),
                        ),
                        child: Text(
                          pRmBase,
                          style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w600, color: subtitleColor),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFDCFCE7),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFBBF7D0)),
                ),
                child: Text(
                  currencyFmt.format(totRp),
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF15803D)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          const Divider(height: 1),
          const SizedBox(height: 10),

          // ── 3 Blok Spesifikasi Grid ──
          // 1. Harga Standart Acuan
          _buildSpecSection(
            title: 'Harga Standart Acuan',
            icon: Icons.local_offer_rounded,
            iconColor: const Color(0xFF0F52BA),
            isDarkMode: isDarkMode,
            cardColor: elevatedColor,
            children: [
              _buildSpecRow('Galon ($kGalon):', hGalon > 0 ? currencyFmt.format(hGalon) : 'Rp 0', isBold: hGalon > 0, textColor: textColor, subtitleColor: subtitleColor),
              _buildSpecRow('Pail ($kPail):', hPail > 0 ? currencyFmt.format(hPail) : 'Rp 0', isBold: hPail > 0, textColor: textColor, subtitleColor: subtitleColor),
              _buildSpecRow('Tin ($kTin):', hTin > 0 ? currencyFmt.format(hTin) : 'Rp 0', isBold: hTin > 0, textColor: textColor, subtitleColor: subtitleColor),
            ],
          ),
          const SizedBox(height: 8),

          // 2. Kuantiti Terjual & Total Unit
          _buildSpecSection(
            title: 'Kuantiti Terjual',
            icon: Icons.shopping_cart_rounded,
            iconColor: const Color(0xFF10B981),
            isDarkMode: isDarkMode,
            cardColor: elevatedColor,
            children: [
              _buildSpecRow('Galon:', '$qGalon Unit', isBold: qGalon > 0, valueColor: qGalon > 0 ? const Color(0xFF15803D) : null, textColor: textColor, subtitleColor: subtitleColor),
              _buildSpecRow('Pail:', '$qPail Unit', isBold: qPail > 0, valueColor: qPail > 0 ? const Color(0xFF15803D) : null, textColor: textColor, subtitleColor: subtitleColor),
              _buildSpecRow('Tin:', '$qTin Unit', isBold: qTin > 0, valueColor: qTin > 0 ? const Color(0xFF15803D) : null, textColor: textColor, subtitleColor: subtitleColor),
              const Divider(height: 8, thickness: 0.7),
              _buildSpecRow('Total Qty:', '$totUnit Unit', isBold: true, valueColor: primaryColor, textColor: textColor, subtitleColor: subtitleColor),
            ],
          ),
          const SizedBox(height: 8),

          // 3. Total Volume Liter
          _buildSpecSection(
            title: 'Total Volume (Liter)',
            icon: Icons.opacity_rounded,
            iconColor: const Color(0xFF0284C7),
            isDarkMode: isDarkMode,
            cardColor: elevatedColor,
            children: [
              _buildSpecRow('Vol Galon:', '${vGalon.toStringAsFixed(2)} L', textColor: textColor, subtitleColor: subtitleColor),
              _buildSpecRow('Vol Pail:', '${vPail.toStringAsFixed(2)} L', textColor: textColor, subtitleColor: subtitleColor),
              _buildSpecRow('Vol Tin:', '${vTin.toStringAsFixed(2)} L', textColor: textColor, subtitleColor: subtitleColor),
              const Divider(height: 8, thickness: 0.7),
              _buildSpecRow('Total Volume:', '${totLiter.toStringAsFixed(2)} Liter', isBold: true, valueColor: const Color(0xFF0284C7), textColor: textColor, subtitleColor: subtitleColor),
            ],
          ),
        ],
      ),
    );
  }

  // ─── HELPER WIDGETS DETAIL OUT OF STOCK (OOS) ───
  Widget _buildOosSummaryGrid({
    required List<dynamic> oosItems,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
  }) {
    final int count = oosItems.length;
    final int maxDays = oosItems.fold<int>(0, (max, itm) {
      if (itm is! Map) return max;
      final d = (itm['lama_oos_hari'] as num?)?.toInt() ?? int.tryParse(itm['lama_oos_hari']?.toString() ?? '0') ?? 0;
      return d > max ? d : max;
    });
    final int totalSaran = oosItems.fold<int>(0, (sum, itm) {
      if (itm is! Map) return sum;
      final s = (itm['saran_qty_order'] as num?)?.toInt() ?? int.tryParse(itm['saran_qty_order']?.toString() ?? '0') ?? 0;
      return sum + s;
    });

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Expanded(
            child: _buildOfftakeStatCard(
              icon: Icons.remove_shopping_cart_rounded,
              iconColor: const Color(0xFFE53935),
              iconBgColor: const Color(0xFFE53935).withOpacity(0.12),
              label: 'Total SKU OOS',
              value: '$count SKU',
              valueColor: const Color(0xFFE53935),
              subText: 'Barang tidak tersedia',
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              isDarkMode: isDarkMode,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _buildOfftakeStatCard(
              icon: Icons.timer_outlined,
              iconColor: const Color(0xFFD97706),
              iconBgColor: const Color(0xFFD97706).withOpacity(0.12),
              label: 'Durasi Terlama',
              value: '$maxDays Hari',
              valueColor: const Color(0xFFD97706),
              subText: 'Berturut-turut OOS',
              cardColor: cardColor,
              textColor: textColor,
              subtitleColor: subtitleColor,
              isDarkMode: isDarkMode,
            ),
          ),
          if (totalSaran > 0) ...[
            const SizedBox(width: 8),
            Expanded(
              child: _buildOfftakeStatCard(
                icon: Icons.shopping_basket_outlined,
                iconColor: const Color(0xFF10B981),
                iconBgColor: const Color(0xFF10B981).withOpacity(0.12),
                label: 'Total Saran Order',
                value: '$totalSaran Qty',
                valueColor: const Color(0xFF15803D),
                subText: 'Kemasan rekomendasi',
                cardColor: cardColor,
                textColor: textColor,
                subtitleColor: subtitleColor,
                isDarkMode: isDarkMode,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildOosProductCard({
    required int index,
    required Map<String, dynamic> item,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color elevatedColor,
    required Color primaryColor,
    required bool isDarkMode,
  }) {
    final pName = (item['product_name']?.toString() ?? item['product_code']?.toString() ?? 'Produk Dulux').trim();
    final pKemasan = (item['kemasan_size']?.toString() ?? '-').trim();
    final pBase = (item['base_color']?.toString() ?? '-').trim();
    final pRm = (item['warna_ready_mix_oos']?.toString() ?? '-').trim();
    final pLama = item['lama_oos_hari']?.toString() ?? '0';
    final pSaran = int.tryParse(item['saran_qty_order']?.toString() ?? '0') ?? 0;
    final pAlasan = (item['alasan_oos']?.toString() ?? '-').trim();

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE53935).withOpacity(0.25)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFE53935).withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '#$index',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFFE53935)),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  pName,
                  style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.orange.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '$pLama Hari OOS',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.deepOrange),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 6,
            runSpacing: 4,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F52BA).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text('Kemasan: $pKemasan', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF0F52BA))),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.teal.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text('Base: $pBase', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.teal)),
              ),
              if (pRm != '-' && !pRm.toLowerCase().contains('bukan ready mix'))
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.purple.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text('Warna: $pRm', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.purple)),
                ),
              if (pSaran > 0)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text('Saran Order: $pSaran Qty', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.green)),
                ),
            ],
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: elevatedColor,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.help_outline_rounded, size: 14, color: Colors.grey),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'Penyebab: $pAlasan',
                    style: TextStyle(fontSize: 11, color: subtitleColor, fontStyle: FontStyle.italic),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ─── HELPER WIDGETS DETAIL STOCK END ───
  Widget _buildStockSummaryGrid({
    required List<dynamic> stockItems,
    required int totalSku,
    required double totalVolume,
    required int totalGalon,
    required int totalPail,
    required int totalTinter,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required bool isDarkMode,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.inventory_2_rounded,
                  iconColor: const Color(0xFF0F52BA),
                  iconBgColor: const Color(0xFF0F52BA).withOpacity(0.12),
                  label: 'Total SKU',
                  value: '$totalSku SKU',
                  valueColor: const Color(0xFF0F52BA),
                  subText: 'Produk dicek',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.opacity_rounded,
                  iconColor: const Color(0xFF0284C7),
                  iconBgColor: const Color(0xFF0284C7).withOpacity(0.12),
                  label: 'Total Volume',
                  value: '${totalVolume.toStringAsFixed(1)} L',
                  valueColor: const Color(0xFF0284C7),
                  subText: 'Akumulasi liter',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _buildOfftakeStatCard(
                  icon: Icons.takeout_dining_rounded,
                  iconColor: const Color(0xFF10B981),
                  iconBgColor: const Color(0xFF10B981).withOpacity(0.12),
                  label: 'Galon & Pail',
                  value: '$totalGalon G / $totalPail P',
                  valueColor: const Color(0xFF15803D),
                  subText: 'Total fisik kemasan',
                  cardColor: cardColor,
                  textColor: textColor,
                  subtitleColor: subtitleColor,
                  isDarkMode: isDarkMode,
                ),
              ),
              if (totalTinter > 0) ...[
                const SizedBox(width: 8),
                Expanded(
                  child: _buildOfftakeStatCard(
                    icon: Icons.format_paint_rounded,
                    iconColor: const Color(0xFF8B5CF6),
                    iconBgColor: const Color(0xFF8B5CF6).withOpacity(0.12),
                    label: 'Tinter / Tinta',
                    value: '$totalTinter Kaleng',
                    valueColor: const Color(0xFF7C3AED),
                    subText: 'Mesin tinting',
                    cardColor: cardColor,
                    textColor: textColor,
                    subtitleColor: subtitleColor,
                    isDarkMode: isDarkMode,
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStockProductCard({
    required int index,
    required Map<String, dynamic> item,
    required Color cardColor,
    required Color textColor,
    required Color subtitleColor,
    required Color elevatedColor,
    required Color primaryColor,
    required bool isDarkMode,
  }) {
    final pName = (item['product_name']?.toString() ?? item['product_code']?.toString() ?? 'Produk Dulux').trim();
    final pBrand = (item['brand']?.toString() ?? '-').trim();
    final pCat = (item['category']?.toString() ?? '-').trim();
    final qGalon = (item['kuantiti_galon'] as num?)?.toInt() ?? int.tryParse(item['kuantiti_galon']?.toString() ?? '0') ?? 0;
    final qPail = (item['kuantiti_pail'] as num?)?.toInt() ?? int.tryParse(item['kuantiti_pail']?.toString() ?? '0') ?? 0;
    final volLiter = (item['volume_liter'] as num?)?.toDouble() ?? double.tryParse(item['volume_liter']?.toString() ?? '0') ?? 0.0;
    final isTinter = item['is_tinter'] == true || (item['tipe_tinter_warna'] != null && item['tipe_tinter_warna'].toString().isNotEmpty);
    final tipeTinter = (item['tipe_tinter_warna']?.toString() ?? '-').trim();
    final qTinter = (item['qty_kaleng_tinta'] as num?)?.toInt() ?? int.tryParse(item['qty_kaleng_tinta']?.toString() ?? '0') ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF0F52BA).withOpacity(0.2)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F52BA).withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '#$index',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF0F52BA)),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  pName,
                  style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.bold, color: textColor),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF0284C7).withOpacity(0.12),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '${volLiter.toStringAsFixed(1)} Liter',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF0284C7)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 6,
            runSpacing: 4,
            children: [
              if (pBrand != '-')
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.grey.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text('Brand: $pBrand', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor)),
                ),
              if (pCat != '-')
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: Colors.grey.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text('Kategori: $pCat', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: subtitleColor)),
                ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text('Galon: $qGalon Unit', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF15803D))),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text('Pail: $qPail Unit', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF15803D))),
              ),
              if (isTinter || qTinter > 0) ...[
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF8B5CF6).withOpacity(0.12),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text('Tinter: $tipeTinter ($qTinter Kaleng)', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF7C3AED))),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSpecSection({
    required String title,
    required IconData icon,
    required Color iconColor,
    required bool isDarkMode,
    required Color cardColor,
    required List<Widget> children,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: isDarkMode ? Colors.grey.shade800 : Colors.grey.shade300, width: 0.8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 13, color: iconColor),
              const SizedBox(width: 5),
              Text(
                title.toUpperCase(),
                style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: iconColor, letterSpacing: 0.3),
              ),
            ],
          ),
          const SizedBox(height: 6),
          ...children,
        ],
      ),
    );
  }

  Widget _buildSpecRow(
    String label,
    String value, {
    bool isBold = false,
    Color? valueColor,
    required Color textColor,
    required Color subtitleColor,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 11, color: subtitleColor)),
          Text(
            value,
            style: TextStyle(
              fontSize: 11,
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              color: valueColor ?? (isBold ? textColor : subtitleColor),
            ),
          ),
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

    final rawText = val.valueText?.trim() ?? '';
    if (val.fieldName.toLowerCase().contains('json') || 
        val.fieldLabel.toLowerCase().contains('json') ||
        (rawText.startsWith('[') && rawText.endsWith(']')) ||
        (rawText.startsWith('{') && rawText.endsWith('}'))) {
      return const SizedBox.shrink();
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
