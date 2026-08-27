import 'package:att_mobile/models/report_template_model.dart';

class ReportSubmissionModel {
  final int id;
  final String submissionCode;
  final int? reportTemplateId;
  final String templateTitle;
  final String? templateCode;
  final String? templateCategory;
  final String? storeName;
  final String? address;
  final int? workLocationId;
  final String status;
  final String statusLabel;
  final bool canEdit;
  final bool isWithinRadius;
  final double? latitude;
  final double? longitude;
  final DateTime submittedAt;
  final String? submittedAtFormatted;
  final String? principalName;
  final ReportTemplateModel? template;
  final List<ReportSubmissionValueModel> values;

  ReportSubmissionModel({
    required this.id,
    required this.submissionCode,
    this.reportTemplateId,
    required this.templateTitle,
    this.templateCode,
    this.templateCategory,
    this.storeName,
    this.address,
    this.workLocationId,
    required this.status,
    this.statusLabel = 'Menunggu Verifikasi',
    this.canEdit = true,
    this.isWithinRadius = true,
    this.latitude,
    this.longitude,
    required this.submittedAt,
    this.submittedAtFormatted,
    this.principalName,
    this.template,
    this.values = const [],
  });

  factory ReportSubmissionModel.fromJson(Map<String, dynamic> json) {
    var rawValues = json['values'] as List? ?? [];
    List<ReportSubmissionValueModel> valuesList = rawValues
        .map((v) => ReportSubmissionValueModel.fromJson(v as Map<String, dynamic>))
        .toList();

    ReportTemplateModel? parsedTemplate;
    if (json['template'] != null && json['template'] is Map<String, dynamic>) {
      try {
        parsedTemplate = ReportTemplateModel.fromJson(json['template']);
      } catch (_) {}
    }

    final rawStatus = json['status']?.toString().toLowerCase() ?? 'pending';
    final isApproved = rawStatus == 'approved' || rawStatus == 'verified';
    final canEditVal = json['can_edit'] != null ? (json['can_edit'] == true || json['can_edit'] == 1) : !isApproved;

    return ReportSubmissionModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      submissionCode: json['submission_code'] ?? '',
      reportTemplateId: json['report_template_id'] is int ? json['report_template_id'] : int.tryParse(json['report_template_id']?.toString() ?? ''),
      templateTitle: json['template_title'] ?? json['template']?['title'] ?? 'Laporan',
      templateCode: json['template_code'] ?? json['template']?['code'],
      templateCategory: json['template_category'] ?? json['template']?['category'],
      storeName: json['store_name'],
      address: json['address'],
      workLocationId: json['work_location_id'] is int ? json['work_location_id'] : int.tryParse(json['work_location_id']?.toString() ?? ''),
      status: rawStatus,
      statusLabel: json['status_label'] ?? (isApproved ? 'Terverifikasi (Approve)' : (rawStatus == 'rejected' ? 'Ditolak' : 'Menunggu Verifikasi')),
      canEdit: canEditVal,
      isWithinRadius: json['is_within_radius'] == true || json['is_within_radius'] == 1,
      latitude: json['latitude'] is num ? (json['latitude'] as num).toDouble() : double.tryParse(json['latitude']?.toString() ?? ''),
      longitude: json['longitude'] is num ? (json['longitude'] as num).toDouble() : double.tryParse(json['longitude']?.toString() ?? ''),
      submittedAt: json['submitted_at'] != null 
          ? DateTime.tryParse(json['submitted_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      submittedAtFormatted: json['submitted_at_formatted'],
      principalName: json['principal_name'] ?? json['principal']?['name'],
      template: parsedTemplate,
      values: valuesList,
    );
  }
}

class ReportSubmissionValueModel {
  final int id;
  final int? reportFormFieldId;
  final String fieldName;
  final String fieldLabel;
  final String fieldType;
  final String? valueText;
  final double? valueNumber;
  final dynamic valueJson;
  final String? mediaUrl;
  final String? mediaFullUrl;

  ReportSubmissionValueModel({
    required this.id,
    this.reportFormFieldId,
    required this.fieldName,
    required this.fieldLabel,
    required this.fieldType,
    this.valueText,
    this.valueNumber,
    this.valueJson,
    this.mediaUrl,
    this.mediaFullUrl,
  });

  factory ReportSubmissionValueModel.fromJson(Map<String, dynamic> json) {
    return ReportSubmissionValueModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      reportFormFieldId: json['report_form_field_id'] is int ? json['report_form_field_id'] : int.tryParse(json['report_form_field_id']?.toString() ?? ''),
      fieldName: json['field_name'] ?? '',
      fieldLabel: json['field_label'] ?? json['field_name'] ?? '',
      fieldType: json['field_type'] ?? 'text',
      valueText: json['value_text']?.toString(),
      valueNumber: json['value_number'] is num ? (json['value_number'] as num).toDouble() : double.tryParse(json['value_number']?.toString() ?? ''),
      valueJson: json['value_json'],
      mediaUrl: json['media_url'],
      mediaFullUrl: json['media_full_url'] ?? json['media_url'],
    );
  }
}
