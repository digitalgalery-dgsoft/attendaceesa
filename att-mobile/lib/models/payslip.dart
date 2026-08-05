class Payslip {
  final int id;
  final String monthYear;
  final String fileUrl;
  final String createdAt;

  Payslip({
    required this.id,
    required this.monthYear,
    required this.fileUrl,
    required this.createdAt,
  });

  factory Payslip.fromJson(Map<String, dynamic> json) {
    return Payslip(
      id: json['id'] ?? 0,
      monthYear: json['month_year'] ?? '',
      fileUrl: json['file_url'] ?? '',
      createdAt: json['created_at'] ?? '',
    );
  }
}
