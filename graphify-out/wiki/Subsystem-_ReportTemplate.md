# Subsystem: ReportTemplate

> 214 nodes

## Key Concepts

- **ReportTemplate** (197 connections) — `att-admin-v12/app/Models/ReportTemplate.php`
- **Principal** (185 connections) — `att-admin-v12/app/Models/Principal.php`
- **ReportFormField** (114 connections) — `att-admin-v12/app/Models/ReportFormField.php`
- **Illuminate\Database\Eloquent\Relations\HasMany** (20 connections)
- **2026_08_31_100000_seed_all_dulux_official_templates.php** (18 connections) — `att-admin-v12/database/migrations/2026_08_31_100000_seed_all_dulux_official_templates.php`
- **ReportTemplatePresetsSeeder** (16 connections) — `att-admin-v12/database/seeders/ReportTemplatePresetsSeeder.php`
- **.run()** (15 connections) — `att-admin-v12/database/seeders/ReportTemplatePresetsSeeder.php`
- **up()** (12 connections) — `att-admin-v12/database/migrations/2026_08_31_100000_seed_all_dulux_official_templates.php`
- **ImportDuluxOfftakeCommand.php** (11 connections) — `att-admin-v12/app/Console/Commands/ImportDuluxOfftakeCommand.php`
- **ImportDuluxStockCommand.php** (11 connections) — `att-admin-v12/app/Console/Commands/ImportDuluxStockCommand.php`
- **2026_08_24_172000_seed_all_fonterra_reporting_templates.php** (11 connections) — `att-admin-v12/database/migrations/2026_08_24_172000_seed_all_fonterra_reporting_templates.php`
- **2026_09_07_120000_sync_all_dulux_report_template_fields.php** (11 connections) — `att-admin-v12/database/migrations/2026_09_07_120000_sync_all_dulux_report_template_fields.php`
- **TemplateSyncService.php** (10 connections) — `att-admin-v12/app/Services/TemplateSyncService.php`
- **2026_09_01_210000_fix_dulux_stock_end_fields_and_face_off.php** (10 connections) — `att-admin-v12/database/migrations/2026_09_01_210000_fix_dulux_stock_end_fields_and_face_off.php`
- **2026_09_14_110000_seed_wings_mbr_freetaste_report_template.php** (10 connections) — `att-admin-v12/database/migrations/2026_09_14_110000_seed_wings_mbr_freetaste_report_template.php`
- **ReportTemplatePresetsSeeder.php** (10 connections) — `att-admin-v12/database/seeders/ReportTemplatePresetsSeeder.php`
- **2026_08_27_131000_seed_wings_sales_and_gift_report_template.php** (9 connections) — `att-admin-v12/database/migrations/2026_08_27_131000_seed_wings_sales_and_gift_report_template.php`
- **2026_09_11_151000_seed_wings_mbr_sales_report_template.php** (9 connections) — `att-admin-v12/database/migrations/2026_09_11_151000_seed_wings_mbr_sales_report_template.php`
- **.handle()** (8 connections) — `att-admin-v12/app/Console/Commands/ImportDuluxOfftakeCommand.php`
- **.handle()** (8 connections) — `att-admin-v12/app/Console/Commands/ImportDuluxStockCommand.php`
- **2026_08_26_130000_seed_all_mamasuka_reporting_templates.php** (8 connections) — `att-admin-v12/database/migrations/2026_08_26_130000_seed_all_mamasuka_reporting_templates.php`
- **2026_08_26_140000_seed_all_wings_reporting_templates.php** (8 connections) — `att-admin-v12/database/migrations/2026_08_26_140000_seed_all_wings_reporting_templates.php`
- **2026_09_18_100000_seed_wings_tools_report_template.php** (8 connections) — `att-admin-v12/database/migrations/2026_09_18_100000_seed_wings_tools_report_template.php`
- **TemplateSyncService** (7 connections) — `att-admin-v12/app/Services/TemplateSyncService.php`
- **2026_08_27_150000_ensure_wings_photo_is_multi_photo.php** (7 connections) — `att-admin-v12/database/migrations/2026_08_27_150000_ensure_wings_photo_is_multi_photo.php`
- *... and 189 more nodes in this community*

## Relationships

- [Employee & Identity Management](Employee_&_Identity_Management.md) (93 shared connections)
- [Attendance & Schedule Subsystem](Attendance_&_Schedule_Subsystem.md) (85 shared connections)
- [Odoo ERP & Principal Integration](Odoo_ERP_&_Principal_Integration.md) (70 shared connections)
- [Reporting & Form Engine](Reporting_&_Form_Engine.md) (40 shared connections)
- [Admin Panel Filament Resources](Admin_Panel_Filament_Resources.md) (10 shared connections)
- [Flutter Mobile UI Components](Flutter_Mobile_UI_Components.md) (3 shared connections)
- [Visit & Itinerary Management](Visit_&_Itinerary_Management.md) (1 shared connections)
- [API & Mobile State Providers](API_&_Mobile_State_Providers.md) (1 shared connections)

## Source Files

- `att-admin-v12/app/Console/Commands/ImportDuluxOfftakeCommand.php`
- `att-admin-v12/app/Console/Commands/ImportDuluxStockCommand.php`
- `att-admin-v12/app/Filament/Pages/ManPowerReport.php`
- `att-admin-v12/app/Filament/Pages/MandaysReport.php`
- `att-admin-v12/app/Filament/Pages/TurnOverReport.php`
- `att-admin-v12/app/Filament/Resources/EmployeeSchedules/Pages/EmployeeScheduleRoster.php`
- `att-admin-v12/app/Filament/Resources/EmployeeSchedules/Pages/ListEmployeeSchedules.php`
- `att-admin-v12/app/Filament/Resources/Employees/Pages/ListEmployees.php`
- `att-admin-v12/app/Filament/Resources/ReportTemplates/Pages/ListReportTemplates.php`
- `att-admin-v12/app/Http/Controllers/Api/TemplateSyncController.php`
- `att-admin-v12/app/Models/Employee.php`
- `att-admin-v12/app/Models/Principal.php`
- `att-admin-v12/app/Models/ReportFormField.php`
- `att-admin-v12/app/Models/ReportTemplate.php`
- `att-admin-v12/app/Services/TemplateSyncService.php`
- `att-admin-v12/database/migrations/2026_08_24_172000_seed_all_fonterra_reporting_templates.php`
- `att-admin-v12/database/migrations/2026_08_26_130000_seed_all_mamasuka_reporting_templates.php`
- `att-admin-v12/database/migrations/2026_08_26_140000_seed_all_wings_reporting_templates.php`
- `att-admin-v12/database/migrations/2026_08_27_131000_seed_wings_sales_and_gift_report_template.php`
- `att-admin-v12/database/migrations/2026_08_27_150000_ensure_wings_photo_is_multi_photo.php`

## Audit Trail

- EXTRACTED: 784 (99%)
- INFERRED: 5 (1%)
- AMBIGUOUS: 0 (0%)

---

*Part of the graphify knowledge wiki. See [index](index.md) to navigate.*