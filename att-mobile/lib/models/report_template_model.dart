class ReportTemplateModel {
  final int id;
  final String code;
  final String title;
  final String? description;
  final String icon;
  final String color;
  final bool requireGps;
  final bool requirePhoto;
  final bool requireSignature;
  final int fieldsCount;
  final List<TemplateProductModel> products;
  final List<ReportFormFieldModel> fields;

  ReportTemplateModel({
    required this.id,
    required this.code,
    required this.title,
    this.description,
    this.icon = 'document-text',
    this.color = '#0F52BA',
    this.requireGps = true,
    this.requirePhoto = false,
    this.requireSignature = false,
    this.fieldsCount = 0,
    this.products = const [],
    required this.fields,
  });

  factory ReportTemplateModel.fromJson(Map<String, dynamic> json) {
    var rawFields = json['fields'] as List? ?? [];
    List<ReportFormFieldModel> fieldsList = rawFields
        .map((f) => ReportFormFieldModel.fromJson(f as Map<String, dynamic>))
        .toList();

    var rawProducts = json['products'] as List? ?? [];
    List<TemplateProductModel> productsList = rawProducts
        .map((p) => TemplateProductModel.fromJson(p as Map<String, dynamic>))
        .toList();

    return ReportTemplateModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      code: json['code'] ?? '',
      title: json['title'] ?? '',
      description: json['description'],
      icon: json['icon'] ?? 'document-text',
      color: json['color'] ?? '#0F52BA',
      requireGps: json['require_gps'] == true || json['require_gps'] == 1,
      requirePhoto: json['require_photo'] == true || json['require_photo'] == 1,
      requireSignature: json['require_signature'] == true || json['require_signature'] == 1,
      fieldsCount: json['fields_count'] is int ? json['fields_count'] : (fieldsList.length),
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
      'require_gps': requireGps,
      'require_photo': requirePhoto,
      'require_signature': requireSignature,
      'fields_count': fieldsCount,
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
