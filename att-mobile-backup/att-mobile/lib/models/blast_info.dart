class BlastInfo {
  final int id;
  final String title;
  final String content;
  final String targetType;
  final int? departmentId;
  final String startDate;
  final String endDate;

  BlastInfo({
    required this.id,
    required this.title,
    required this.content,
    required this.targetType,
    this.departmentId,
    required this.startDate,
    required this.endDate,
  });

  factory BlastInfo.fromJson(Map<String, dynamic> json) {
    return BlastInfo(
      id: json['id'] != null ? int.tryParse(json['id'].toString()) ?? 0 : 0,
      title: json['title'] ?? '',
      content: json['content'] ?? '',
      targetType: json['target_type'] ?? 'all',
      departmentId: json['department_id'] != null ? int.tryParse(json['department_id'].toString()) : null,
      startDate: json['start_date'] ?? '',
      endDate: json['end_date'] ?? '',
    );
  }
}
