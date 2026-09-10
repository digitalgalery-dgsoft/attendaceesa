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
  final int stepNumber;
  final bool isStepLocked;
  final String? lockedReason;
  final bool isCompletedToday;
  final bool hasProductBinding;
  final List<String> submittedProducts;
  final List<int> submittedProductIds;
  final int totalProductsCount;
  final int remainingProductsCount;
  final bool hasMachineBinding;
  final List<String> submittedMachines;
  final int totalMachinesCount;
  final int remainingMachinesCount;
  final List<Map<String, dynamic>> storeMachines;
  final List<String> reportDays;
  final List<String> assignedPositions;
  final List<String> assignedEmployees;
  final List<TemplateProductModel> products;
  final List<ReportFormFieldModel> fields;
  final Map<String, dynamic>? oosReference;

  static const Map<String, int> duluxOrderMap = {
    'RPT-DULUX-DAILY-MAINTENANCE': 1,
    'RPT-DULUX-OFFTAKE-01': 2,
    'RPT-DULUX-OOS-SSO': 3,
    'RPT-DULUX-DATABASE-PELANGGAN': 4,
    'RPT-DULUX-STOCK-END': 5,
    'RPT-DULUX-CBP-PRICING': 6,
  };

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
    this.stepNumber = 1,
    this.isStepLocked = false,
    this.lockedReason,
    this.isCompletedToday = false,
    this.hasProductBinding = false,
    this.submittedProducts = const [],
    this.submittedProductIds = const [],
    this.totalProductsCount = 0,
    this.remainingProductsCount = 0,
    this.hasMachineBinding = false,
    this.submittedMachines = const [],
    this.totalMachinesCount = 0,
    this.remainingMachinesCount = 0,
    this.storeMachines = const [],
    this.reportDays = const [],
    this.assignedPositions = const [],
    this.assignedEmployees = const [],
    this.products = const [],
    required this.fields,
    this.oosReference,
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

    var rawSubProducts = json['submitted_products'] as List? ?? [];
    List<String> parsedSubProducts = rawSubProducts.map((e) => e.toString()).toList();

    var rawSubProdIds = json['submitted_product_ids'] as List? ?? [];
    List<int> parsedSubProdIds = rawSubProdIds.map((e) => int.tryParse(e.toString()) ?? 0).where((e) => e > 0).toList();

    final cTarget = json['cutoff_target'] is num ? (json['cutoff_target'] as num).toInt() : (int.tryParse(json['cutoff_target']?.toString() ?? '1') ?? 1);
    final cSubmitted = json['cutoff_submitted'] is num ? (json['cutoff_submitted'] as num).toInt() : (int.tryParse(json['cutoff_submitted']?.toString() ?? '0') ?? 0);
    final cPercent = json['cutoff_progress_percent'] is num 
        ? (json['cutoff_progress_percent'] as num).toInt() 
        : (int.tryParse(json['cutoff_progress_percent']?.toString() ?? '0') ?? (cTarget > 0 ? ((cSubmitted / cTarget) * 100).round() : 0));
    final ratioDisplay = json['target_ratio_display']?.toString() ?? '$cSubmitted/$cTarget ($cPercent%)';

    int sNum = json['step_number'] is num ? (json['step_number'] as num).toInt() : (int.tryParse(json['step_number']?.toString() ?? '1') ?? 1);
    final templateCode = json['code']?.toString() ?? '';
    if (duluxOrderMap.containsKey(templateCode)) {
      sNum = duluxOrderMap[templateCode]!;
    }
    final sLocked = json['is_step_locked'] == true || json['is_step_locked'] == 1 || json['is_step_locked'] == 'true';
    final sDone = json['is_completed_today'] == true || json['is_completed_today'] == 1 || json['is_completed_today'] == 'true';
    final hasBinding = json['has_product_binding'] == true || json['has_product_binding'] == 1 || json['has_product_binding'] == 'true' || productsList.isNotEmpty;

    final totProd = json['total_products_count'] is num ? (json['total_products_count'] as num).toInt() : productsList.length;
    final remProd = json['remaining_products_count'] is num ? (json['remaining_products_count'] as num).toInt() : (totProd - parsedSubProducts.length).clamp(0, 9999);

    final hasMachineBinding = json['has_machine_binding'] == true || json['has_machine_binding'] == 1 || json['has_machine_binding'] == 'true';
    var rawSubMachines = json['submitted_machines'] as List? ?? [];
    List<String> parsedSubMachines = rawSubMachines.map((e) => e.toString()).toList();
    final totMachines = json['total_machines_count'] is num ? (json['total_machines_count'] as num).toInt() : (int.tryParse(json['total_machines_count']?.toString() ?? '0') ?? 0);
    final remMachines = json['remaining_machines_count'] is num ? (json['remaining_machines_count'] as num).toInt() : (int.tryParse(json['remaining_machines_count']?.toString() ?? '0') ?? (totMachines - parsedSubMachines.length).clamp(0, 9999));
    var rawStoreMachines = json['store_machines'] as List? ?? [];
    List<Map<String, dynamic>> parsedStoreMachines = rawStoreMachines.whereType<Map<String, dynamic>>().toList();
    final oosRef = json['oos_reference'] is Map ? Map<String, dynamic>.from(json['oos_reference'] as Map) : null;

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
      stepNumber: sNum,
      isStepLocked: sLocked,
      lockedReason: json['locked_reason']?.toString(),
      isCompletedToday: sDone,
      hasProductBinding: hasBinding,
      submittedProducts: parsedSubProducts,
      submittedProductIds: parsedSubProdIds,
      totalProductsCount: totProd,
      remainingProductsCount: remProd,
      hasMachineBinding: hasMachineBinding,
      submittedMachines: parsedSubMachines,
      totalMachinesCount: totMachines,
      remainingMachinesCount: remMachines,
      storeMachines: parsedStoreMachines,
      reportDays: parsedDays,
      assignedPositions: parsedPositions,
      assignedEmployees: parsedEmployees,
      products: productsList,
      fields: fieldsList,
      oosReference: oosRef,
    );
  }

  ReportTemplateModel copyWith({
    int? id,
    String? code,
    String? title,
    String? description,
    String? icon,
    String? color,
    String? scheduleType,
    int? targetCount,
    int? cutoffTarget,
    int? cutoffSubmitted,
    int? cutoffProgressPercent,
    String? targetRatioDisplay,
    bool? requireGps,
    bool? requirePhoto,
    bool? requireSignature,
    int? fieldsCount,
    int? stepNumber,
    bool? isStepLocked,
    String? lockedReason,
    bool? isCompletedToday,
    bool? hasProductBinding,
    List<String>? submittedProducts,
    List<int>? submittedProductIds,
    int? totalProductsCount,
    int? remainingProductsCount,
    bool? hasMachineBinding,
    List<String>? submittedMachines,
    int? totalMachinesCount,
    int? remainingMachinesCount,
    List<Map<String, dynamic>>? storeMachines,
    List<String>? reportDays,
    List<String>? assignedPositions,
    List<String>? assignedEmployees,
    List<TemplateProductModel>? products,
    List<ReportFormFieldModel>? fields,
    Map<String, dynamic>? oosReference,
  }) {
    return ReportTemplateModel(
      id: id ?? this.id,
      code: code ?? this.code,
      title: title ?? this.title,
      description: description ?? this.description,
      icon: icon ?? this.icon,
      color: color ?? this.color,
      scheduleType: scheduleType ?? this.scheduleType,
      targetCount: targetCount ?? this.targetCount,
      cutoffTarget: cutoffTarget ?? this.cutoffTarget,
      cutoffSubmitted: cutoffSubmitted ?? this.cutoffSubmitted,
      cutoffProgressPercent: cutoffProgressPercent ?? this.cutoffProgressPercent,
      targetRatioDisplay: targetRatioDisplay ?? this.targetRatioDisplay,
      requireGps: requireGps ?? this.requireGps,
      requirePhoto: requirePhoto ?? this.requirePhoto,
      requireSignature: requireSignature ?? this.requireSignature,
      fieldsCount: fieldsCount ?? this.fieldsCount,
      stepNumber: stepNumber ?? this.stepNumber,
      isStepLocked: isStepLocked ?? this.isStepLocked,
      lockedReason: lockedReason ?? this.lockedReason,
      isCompletedToday: isCompletedToday ?? this.isCompletedToday,
      hasProductBinding: hasProductBinding ?? this.hasProductBinding,
      submittedProducts: submittedProducts ?? this.submittedProducts,
      submittedProductIds: submittedProductIds ?? this.submittedProductIds,
      totalProductsCount: totalProductsCount ?? this.totalProductsCount,
      remainingProductsCount: remainingProductsCount ?? this.remainingProductsCount,
      hasMachineBinding: hasMachineBinding ?? this.hasMachineBinding,
      submittedMachines: submittedMachines ?? this.submittedMachines,
      totalMachinesCount: totalMachinesCount ?? this.totalMachinesCount,
      remainingMachinesCount: remainingMachinesCount ?? this.remainingMachinesCount,
      storeMachines: storeMachines ?? this.storeMachines,
      reportDays: reportDays ?? this.reportDays,
      assignedPositions: assignedPositions ?? this.assignedPositions,
      assignedEmployees: assignedEmployees ?? this.assignedEmployees,
      products: products ?? this.products,
      fields: fields ?? this.fields,
      oosReference: oosReference ?? this.oosReference,
    );
  }

  /// Memastikan urutan alur pelaporan Dulux selalu konsisten di mobile:
  /// 1. Daily Maintenance POST (RPT-DULUX-DAILY-MAINTENANCE)
  /// 2. Offtake (RPT-DULUX-OFFTAKE-01)
  /// 3. OOS (RPT-DULUX-OOS-SSO)
  /// 4. Database Pelanggan (RPT-DULUX-DATABASE-PELANGGAN)
  /// 5. Stok End (RPT-DULUX-STOCK-END)
  /// 6. CBP (RPT-DULUX-CBP-PRICING)
  static List<ReportTemplateModel> applyDuluxSequence(List<ReportTemplateModel> list) {
    final hasDulux = list.any((t) => duluxOrderMap.containsKey(t.code));
    if (!hasDulux) {
      return List<ReportTemplateModel>.from(list)..sort((a, b) => a.stepNumber.compareTo(b.stepNumber));
    }

    final sorted = List<ReportTemplateModel>.from(list);
    sorted.sort((a, b) {
      final orderA = duluxOrderMap[a.code] ?? (a.stepNumber + 100);
      final orderB = duluxOrderMap[b.code] ?? (b.stepNumber + 100);
      return orderA.compareTo(orderB);
    });

    bool prevStepCompleted = true;
    String? prevStepTitle;

    return sorted.map((t) {
      if (duluxOrderMap.containsKey(t.code)) {
        final step = duluxOrderMap[t.code]!;
        final isLocked = !prevStepCompleted;
        final lockedReason = isLocked ? 'Harap selesaikan $prevStepTitle terlebih dahulu.' : null;
        prevStepCompleted = t.isCompletedToday;
        prevStepTitle = t.title;

        return t.copyWith(
          stepNumber: step,
          isStepLocked: isLocked,
          lockedReason: lockedReason,
        );
      }
      return t;
    }).toList();
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
      'step_number': stepNumber,
      'is_step_locked': isStepLocked,
      'locked_reason': lockedReason,
      'is_completed_today': isCompletedToday,
      'has_product_binding': hasProductBinding,
      'submitted_products': submittedProducts,
      'submitted_product_ids': submittedProductIds,
      'total_products_count': totalProductsCount,
      'remaining_products_count': remainingProductsCount,
      'has_machine_binding': hasMachineBinding,
      'submitted_machines': submittedMachines,
      'total_machines_count': totalMachinesCount,
      'remaining_machines_count': remainingMachinesCount,
      'store_machines': storeMachines,
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
  final Map<String, dynamic>? pricingMatrix;

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
    this.pricingMatrix,
  });

  Map<String, dynamic> get packagingInfo {
    final raw = pricingMatrix?['packaging'];
    if (raw is Map<String, dynamic>) return raw;
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return {};
  }

  Map<String, dynamic> get pricesInfo {
    final raw = pricingMatrix?['prices'];
    if (raw is Map<String, dynamic>) return raw;
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return {};
  }

  double? getPackagingSize(String type) {
    final key = type.toLowerCase().trim();
    final val = packagingInfo[key];
    if (val is num) return val.toDouble();
    if (val != null) return double.tryParse(val.toString());
    return null;
  }

  double getPriceForPackaging(String type, {String? baseVariant}) {
    final key = type.toLowerCase().trim();
    final pMap = pricesInfo;
    if (pMap.isEmpty) {
      if (key == 'galon') return price;
      return 0.0;
    }

    // 1. Jika varian Base ditentukan spesifik (misal 'base_a', 'base_b', dst)
    if (baseVariant != null && pMap.containsKey(baseVariant)) {
      final sub = pMap[baseVariant];
      if (sub is Map && sub[key] is num) {
        return (sub[key] as num).toDouble();
      }
    }

    // 2. Jika produk adalah tipe Base
    final catLower = (category ?? '').toLowerCase();
    final nameLower = name.toLowerCase();
    final isBase = catLower.contains('base') || nameLower.contains('base');

    if (isBase) {
      for (final b in ['base_a', 'base_b', 'base_c', 'base_d']) {
        final sub = pMap[b];
        if (sub is Map && sub[key] is num) {
          return (sub[key] as num).toDouble();
        }
      }
    }

    // 3. Ambil dari RM (Ready Mix)
    if (pMap.containsKey('rm') && pMap['rm'] is Map) {
      final rmMap = pMap['rm'] as Map;
      if (rmMap[key] is num) {
        return (rmMap[key] as num).toDouble();
      }
    }

    // Fallback harga default jika galon
    if (key == 'galon' && price > 0) return price;
    return 0.0;
  }

  factory TemplateProductModel.fromJson(Map<String, dynamic> json) {
    Map<String, dynamic>? parsedMatrix;
    if (json['pricing_matrix'] is Map) {
      parsedMatrix = Map<String, dynamic>.from(json['pricing_matrix'] as Map);
    }

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
      pricingMatrix: parsedMatrix,
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
      'pricing_matrix': pricingMatrix,
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
