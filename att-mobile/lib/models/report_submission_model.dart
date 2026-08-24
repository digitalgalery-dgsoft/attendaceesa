class ReportSubmissionModel {
  final int id;
  final String submissionCode;
  final String templateTitle;
  final String? storeName;
  final String status;
  final DateTime submittedAt;
  final String? principalName;

  ReportSubmissionModel({
    required this.id,
    required this.submissionCode,
    required this.templateTitle,
    this.storeName,
    required this.status,
    required this.submittedAt,
    this.principalName,
  });

  factory ReportSubmissionModel.fromJson(Map<String, dynamic> json) {
    return ReportSubmissionModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      submissionCode: json['submission_code'] ?? '',
      templateTitle: json['template']?['title'] ?? json['template_title'] ?? 'Laporan',
      storeName: json['store_name'],
      status: json['status'] ?? 'submitted',
      submittedAt: json['submitted_at'] != null 
          ? DateTime.tryParse(json['submitted_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      principalName: json['principal']?['name'],
    );
  }
}
