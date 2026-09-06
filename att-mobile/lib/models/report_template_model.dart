class ReportTemplateModel {
  final int id;
  final String code;
  final String title;
  final String? description;
  final String icon;
  final String color;
  final String scheduleType;
  final int targetCount;
  final int cutoffTarget;
  final int cutoffSubmitted;
  final int cutoffProgressPercent;
  final String targetRatioDisplay;
  final bool requireGps;
  final bool requirePhoto;
  final bool requireSignature;
  final int fieldsCount;
  final List<String> reportDays;
  final List<String> assignedPositions;
  final List<String> assignedEmployees;
  final List<TemplateProductModel> products;
  final List<ReportFormFieldModel> fields;

  ReportTemplateModel({
    required this.id,
    required this.code,
    required this.title,
    this.description,
    this.icon = 'document-text',
    this.color = '#0F52BA',
    this.scheduleType = 'daily',
    this.targetCount = 1,
    this.cutoffTarget = 1,
    this.cutoffSubmitted = 0,
    this.cutoffProgressPercent = 0,
    this.targetRatioDisplay = '0/1 (0%)',
    this.requireGps = true,
    this.requirePhoto = false,
    this.requireSignature = false,
    this.fieldsCount = 0,
    this.reportDays = const [],
    this.assignedPositions = const [],
    this.assignedEmployees = const [],
    this.products = const [],
    required this.fields,
  });

  bool isScheduledForDay(int weekday) {
    if (reportDays.isEmpty) return true;
    final dayNames = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
    final englishDayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    final currentDay = dayNames[weekday - 1];
    final currentEnglishDay = englishDayNames[weekday - 1];
    return reportDays.any((d) => d.toLowerCase() == currentDay || d.toLowerCase() == currentEnglishDay);
  }

  bool get isTodayScheduled => isScheduledForDay(DateTime.now().weekday);

  String get scheduleBadgeLabel {
    final type = scheduleType.toLowerCase();
    if (type == 'weekly') {
      return '🗓️ Weekly (${targetCount}x/mg)';
    } else if (type == 'monthly') {
      return '📆 Monthly (${targetCount}x/bln)';
    }
    return '📅 Daily (Harian)';
  }

  String get scheduleDaysDisplay {
    if (reportDays.isEmpty) return 'Setiap Hari';
    final map = {
      'senin': 'Sen', 'selasa': 'Sel', 'rabu': 'Rab', 'kamis': 'Kam',
      'jumat': 'Jum', 'sabtu': 'Sab', 'minggu': 'Min',
      'monday': 'Sen', 'tuesday': 'Sel', 'wednesday': 'Rab', 'thursday': 'Kam',
      'friday': 'Jum', 'saturday': 'Sab', 'sunday': 'Min',
    };
    return reportDays.map((d) => map[d.toLowerCase()] ?? d).join(', ');
  }

  factory ReportTemplateModel.fromJson(Map<String, dynamic> json) {
    var rawFields = json['fields'] as List? ?? [];
    List<ReportFormFieldModel> fieldsList = rawFields
        .map((f) => ReportFormFieldModel.fromJson(f as Map<String, dynamic>))
        .toList();

    var rawProducts = json['products'] as List? ?? [];
    List<TemplateProductModel> productsList = rawProducts
        .map((p) => TemplateProductModel.fromJson(p as Map<String, dynamic>))
        .toList();

    var rawDays = json['report_days'] as List? ?? [];
    List<String> parsedDays = rawDays.map((e) => e.toString().toLowerCase()).toList();

    var rawPositions = json['assigned_positions'] as List? ?? [];
    List<String> parsedPositions = rawPositions.map((e) => e.toString()).toList();

    var rawEmployees = json['assigned_employees'] as List? ?? [];
    List<String> parsedEmployees = rawEmployees.map((e) => e.toString()).toList();

    final cTarget = json['cutoff_target'] is num ? (json['cutoff_target'] as num).toInt() : (int.tryParse(json['cutoff_target']?.toString() ?? '1') ?? 1);
    final cSubmitted = json['cutoff_submitted'] is num ? (json['cutoff_submitted'] as num).toInt() : (int.tryParse(json['cutoff_submitted']?.toString() ?? '0') ?? 0);
    final cPercent = json['cutoff_progress_percent'] is num 
        ? (json['cutoff_progress_percent'] as num).toInt() 
        : (int.tryParse(json['cutoff_progress_percent']?.toString() ?? '0') ?? (cTarget > 0 ? ((cSubmitted / cTarget) * 100).round() : 0));
    final ratioDisplay = json['target_ratio_display']?.toString() ?? '$cSubmitted/$cTarget ($cPercent%)';

    return ReportTemplateModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      code: json['code'] ?? '',
      title: json['title'] ?? '',
      description: json['description'],
      icon: json['icon'] ?? 'document-text',
      color: json['color'] ?? '#0F52BA',
      scheduleType: json['schedule_type']?.toString() ?? 'daily',
      targetCount: json['target_count'] is num ? (json['target_count'] as num).toInt() : (int.tryParse(json['target_count']?.toString() ?? '1') ?? 1),
      cutoffTarget: cTarget,
      cutoffSubmitted: cSubmitted,
      cutoffProgressPercent: cPercent,
      targetRatioDisplay: ratioDisplay,
      requireGps: json['require_gps'] == true || json['require_gps'] == 1,
      requirePhoto: json['require_photo'] == true || json['require_photo'] == 1,
      requireSignature: json['require_signature'] == true || json['require_signature'] == 1,
      fieldsCount: json['fields_count'] is int ? json['fields_count'] : (fieldsList.length),
      reportDays: parsedDays,
      assignedPositions: parsedPositions,
      assignedEmployees: parsedEmployees,
      products: productsList,
      fields: fieldsList,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'code': code,
      'title': title,
      'description': description,
      'icon': icon,
      'color': color,
      'schedule_type': scheduleType,
      'target_count': targetCount,
      'cutoff_target': cutoffTarget,
      'cutoff_submitted': cutoffSubmitted,
      'cutoff_progress_percent': cutoffProgressPercent,
      'target_ratio_display': targetRatioDisplay,
      'require_gps': requireGps,
      'require_photo': requirePhoto,
      'require_signature': requireSignature,
      'fields_count': fieldsCount,
      'report_days': reportDays,
      'assigned_positions': assignedPositions,
      'assigned_employees': assignedEmployees,
      'products': products.map((p) => p.toJson()).toList(),
      'fields': fields.map((f) => f.toJson()).toList(),
    };
  }
}

class TemplateProductModel {
  final int id;
  final String name;
  final String? skuCode;
  final String? barcode;
  final String? category;
  final String? brand;
  final double price;
  final String? formattedPrice;
  final String uom;
  final int minStock;

  TemplateProductModel({
    required this.id,
    required this.name,
    this.skuCode,
    this.barcode,
    this.category,
    this.brand,
    this.price = 0.0,
    this.formattedPrice,
    this.uom = 'Pcs',
    this.minStock = 0,
  });

  factory TemplateProductModel.fromJson(Map<String, dynamic> json) {
    return TemplateProductModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name'] ?? '',
      skuCode: json['sku_code'],
      barcode: json['barcode'],
      category: json['category'],
      brand: json['brand'],
      price: json['price'] is num ? (json['price'] as num).toDouble() : double.tryParse(json['price']?.toString() ?? '0') ?? 0.0,
      formattedPrice: json['formatted_price'],
      uom: json['uom'] ?? 'Pcs',
      minStock: json['min_stock'] is num
          ? (json['min_stock'] as num).toInt()
          : (json['minimal_stock'] is num
              ? (json['minimal_stock'] as num).toInt()
              : int.tryParse(json['min_stock']?.toString() ?? json['minimal_stock']?.toString() ?? '0') ?? 0),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'sku_code': skuCode,
      'barcode': barcode,
      'category': category,
      'brand': brand,
      'price': price,
      'formatted_price': formattedPrice,
      'uom': uom,
      'min_stock': minStock,
    };
  }
}

class ReportFormFieldModel {
  final int id;
  final String fieldName;
  final String fieldLabel;
  final String fieldType;
  final bool isRequired;
  final bool isReadonly;
  final List<String> options;
  final String? placeholder;
  final String? defaultValue;
  final Map<String, dynamic> validationRules;
  final int sortOrder;

  ReportFormFieldModel({
    required this.id,
    required this.fieldName,
    required this.fieldLabel,
    required this.fieldType,
    this.isRequired = false,
    this.isReadonly = false,
    this.options = const [],
    this.placeholder,
    this.defaultValue,
    this.validationRules = const {},
    this.sortOrder = 0,
  });

  factory ReportFormFieldModel.fromJson(Map<String, dynamic> json) {
    List<String> parsedOptions = [];
    if (json['options'] != null) {
      if (json['options'] is List) {
        parsedOptions = (json['options'] as List).map((e) => e.toString()).toList();
      } else if (json['options'] is Map) {
        parsedOptions = (json['options'] as Map).values.map((e) => e.toString()).toList();
      }
    }

    return ReportFormFieldModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      fieldName: json['field_name'] ?? '',
      fieldLabel: json['field_label'] ?? '',
      fieldType: json['field_type'] ?? 'text',
      isRequired: json['is_required'] == true || json['is_required'] == 1,
      isReadonly: json['is_readonly'] == true || json['is_readonly'] == 1 || json['read_only'] == true,
      options: parsedOptions,
      placeholder: json['placeholder'],
      defaultValue: json['default_value']?.toString(),
      validationRules: json['validation_rules'] is Map<String, dynamic> ? json['validation_rules'] : {},
      sortOrder: json['sort_order'] is int ? json['sort_order'] : int.tryParse(json['sort_order']?.toString() ?? '0') ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'field_name': fieldName,
      'field_label': fieldLabel,
      'field_type': fieldType,
      'is_required': isRequired,
      'is_readonly': isReadonly,
      'options': options,
      'placeholder': placeholder,
      'default_value': defaultValue,
      'validation_rules': validationRules,
      'sort_order': sortOrder,
    };
  }
}
