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

class MeetingParticipantModel {
  final int employeeId;
  final String name;
  final String? employeeNo;
  final String? position;
  final String? department;
  final String? avatar;
  final String status; // 'completed', 'in_meeting', 'not_attended'
  final String? meetInAt;
  final String? meetOutAt;
  final String? meetInTimeFull;
  final String? meetOutTimeFull;
  final int? durationSeconds;
  final String? formattedDuration;
  final String? reportNotes;
  final String? meetInPhoto;
  final String? meetOutPhoto;
  final double? meetInLat;
  final double? meetInLng;
  final double? meetOutLat;
  final double? meetOutLng;

  MeetingParticipantModel({
    required this.employeeId,
    required this.name,
    this.employeeNo,
    this.position,
    this.department,
    this.avatar,
    required this.status,
    this.meetInAt,
    this.meetOutAt,
    this.meetInTimeFull,
    this.meetOutTimeFull,
    this.durationSeconds,
    this.formattedDuration,
    this.reportNotes,
    this.meetInPhoto,
    this.meetOutPhoto,
    this.meetInLat,
    this.meetInLng,
    this.meetOutLat,
    this.meetOutLng,
  });

  bool get isAttended => status == 'completed';
  bool get isInMeeting => status == 'in_meeting';
  bool get isNotAttended => status == 'not_attended';

  factory MeetingParticipantModel.fromJson(Map<String, dynamic> json) {
    return MeetingParticipantModel(
      employeeId: json['employee_id'] is int ? json['employee_id'] : int.tryParse('${json['employee_id']}') ?? 0,
      name: json['name'] ?? 'Karyawan',
      employeeNo: json['employee_no'],
      position: json['position'],
      department: json['department'],
      avatar: json['avatar'],
      status: json['status'] ?? 'not_attended',
      meetInAt: json['meet_in_at'],
      meetOutAt: json['meet_out_at'],
      meetInTimeFull: json['meet_in_time_full'],
      meetOutTimeFull: json['meet_out_time_full'],
      durationSeconds: json['duration_seconds'] is int ? json['duration_seconds'] : int.tryParse('${json['duration_seconds']}'),
      formattedDuration: json['formatted_duration'],
      reportNotes: json['report_notes'],
      meetInPhoto: json['meet_in_photo'],
      meetOutPhoto: json['meet_out_photo'],
      meetInLat: json['meet_in_lat'] != null ? double.tryParse('${json['meet_in_lat']}') : null,
      meetInLng: json['meet_in_lng'] != null ? double.tryParse('${json['meet_in_lng']}') : null,
      meetOutLat: json['meet_out_lat'] != null ? double.tryParse('${json['meet_out_lat']}') : null,
      meetOutLng: json['meet_out_lng'] != null ? double.tryParse('${json['meet_out_lng']}') : null,
    );
  }
}

class MeetingDetailModel {
  final int id;
  final String title;
  final String meetingDate;
  final String meetingDateFormatted;
  final String startTime;
  final String? endTime;
  final String timeRange;
  final String meetingType; // 'online' or 'offline'
  final String? meetingLink;
  final String? locationName;
  final double? latitude;
  final double? longitude;
  final int? radiusMeter;
  final String? notes;
  final String status;
  final int totalParticipants;
  final int completedCount;
  final int inMeetingCount;
  final int notAttendedCount;
  final List<MeetingParticipantModel> participants;

  MeetingDetailModel({
    required this.id,
    required this.title,
    required this.meetingDate,
    required this.meetingDateFormatted,
    required this.startTime,
    this.endTime,
    required this.timeRange,
    required this.meetingType,
    this.meetingLink,
    this.locationName,
    this.latitude,
    this.longitude,
    this.radiusMeter,
    this.notes,
    required this.status,
    required this.totalParticipants,
    required this.completedCount,
    required this.inMeetingCount,
    required this.notAttendedCount,
    required this.participants,
  });

  bool get isOnline => meetingType.toLowerCase() == 'online';
  bool get isOffline => !isOnline;

  factory MeetingDetailModel.fromJson(Map<String, dynamic> json) {
    final stats = json['stats'] is Map<String, dynamic> ? json['stats'] : <String, dynamic>{};
    final pList = json['participants'] as List? ?? [];

    return MeetingDetailModel(
      id: json['id'] is int ? json['id'] : int.tryParse('${json['id']}') ?? 0,
      title: json['title'] ?? '-',
      meetingDate: json['meeting_date'] ?? '',
      meetingDateFormatted: json['meeting_date_formatted'] ?? (json['meeting_date'] ?? ''),
      startTime: json['start_time'] ?? '',
      endTime: json['end_time'],
      timeRange: json['time_range'] ?? '',
      meetingType: json['meeting_type'] ?? 'offline',
      meetingLink: json['meeting_link'],
      locationName: json['location_name'],
      latitude: json['latitude'] != null ? double.tryParse('${json['latitude']}') : null,
      longitude: json['longitude'] != null ? double.tryParse('${json['longitude']}') : null,
      radiusMeter: json['radius_meter'] is int ? json['radius_meter'] : int.tryParse('${json['radius_meter']}'),
      notes: json['notes'],
      status: json['status'] ?? 'scheduled',
      totalParticipants: stats['total_participants'] ?? pList.length,
      completedCount: stats['completed_count'] ?? 0,
      inMeetingCount: stats['in_meeting_count'] ?? 0,
      notAttendedCount: stats['not_attended_count'] ?? 0,
      participants: pList.map((e) => MeetingParticipantModel.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }
}
