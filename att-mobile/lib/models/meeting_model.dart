class MeetingModel {
  final int id;
  final String title;
  final String meetingDate;
  final String startTime;
  final String? endTime;
  final String meetingType; // 'online' or 'offline'
  final String? meetingLink;
  final String? locationName;
  final double? latitude;
  final double? longitude;
  final int radiusMeter;
  final String? notes;
  final String status;
  final bool isInMeeting;
  final bool isCompleted;
  final Map<String, dynamic>? myAttendance;

  MeetingModel({
    required this.id,
    required this.title,
    required this.meetingDate,
    required this.startTime,
    this.endTime,
    required this.meetingType,
    this.meetingLink,
    this.locationName,
    this.latitude,
    this.longitude,
    this.radiusMeter = 100,
    this.notes,
    required this.status,
    this.isInMeeting = false,
    this.isCompleted = false,
    this.myAttendance,
  });

  bool get isOnline => meetingType.toLowerCase() == 'online';
  bool get isOffline => !isOnline;

  factory MeetingModel.fromJson(Map<String, dynamic> json) {
    return MeetingModel(
      id: json['id'] is int ? json['id'] : int.tryParse('${json['id']}') ?? 0,
      title: json['title'] ?? '-',
      meetingDate: json['meeting_date'] ?? '',
      startTime: json['start_time'] ?? '',
      endTime: json['end_time'],
      meetingType: json['meeting_type'] ?? 'offline',
      meetingLink: json['meeting_link'],
      locationName: json['location_name'],
      latitude: json['latitude'] != null ? double.tryParse('${json['latitude']}') : null,
      longitude: json['longitude'] != null ? double.tryParse('${json['longitude']}') : null,
      radiusMeter: json['radius_meter'] is int
          ? json['radius_meter']
          : int.tryParse('${json['radius_meter']}') ?? 100,
      notes: json['notes'],
      status: json['status'] ?? 'scheduled',
      isInMeeting: json['is_in_meeting'] == true,
      isCompleted: json['is_completed'] == true,
      myAttendance: json['my_attendance'] is Map<String, dynamic> ? json['my_attendance'] : null,
    );
  }
}
