# ReportTemplate

> God node · 197 connections · `att-admin-v12/app/Models/ReportTemplate.php`

**Community:** [Subsystem: ReportTemplate](Subsystem-_ReportTemplate.md)

## Connections by Relation

### calls
- .run() `EXTRACTED`
- up() `EXTRACTED`
- .submit() `EXTRACTED`
- .teamPerformance() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .templates() `EXTRACTED`
- .checkPendingReportsStatic() `EXTRACTED`
- seedAllFonterraTemplates() `EXTRACTED`
- up() `EXTRACTED`
- seedCbpPricing() `EXTRACTED`
- seedDailyMaintenance() `EXTRACTED`
- seedDataPelanggan() `EXTRACTED`
- seedOfftake() `EXTRACTED`
- seedOosLso() `EXTRACTED`
- seedOosSso() `EXTRACTED`
- seedRegistrasiMitra() `EXTRACTED`
- *…and 68 more `calls` connection(s) not listed (lowest-degree first to go)*

### contains
- ReportTemplate.php `EXTRACTED`

### imports
- PrincipalPortalController.php `EXTRACTED`
- ReportingApiController.php `EXTRACTED`
- ReportSubmissionsTable.php `EXTRACTED`
- ReportTemplatesTable.php `EXTRACTED`
- DashboardApiController.php `EXTRACTED`
- 2026_08_31_100000_seed_all_dulux_official_templates.php `EXTRACTED`
- att-admin-v12/app/Models/Employee.php `EXTRACTED`
- WingsMbrExportService.php `EXTRACTED`
- ImportDuluxCbpCommand.php `EXTRACTED`
- ReportTemplateResource.php `EXTRACTED`
- ImportDuluxDailyMaintenanceCommand.php `EXTRACTED`
- ImportDuluxOfftakeCommand.php `EXTRACTED`
- ImportDuluxOosCommand.php `EXTRACTED`
- ImportDuluxStockCommand.php `EXTRACTED`
- 2026_08_24_172000_seed_all_fonterra_reporting_templates.php `EXTRACTED`
- 2026_09_07_120000_sync_all_dulux_report_template_fields.php `EXTRACTED`
- TemplateSyncService.php `EXTRACTED`
- 2026_09_01_210000_fix_dulux_stock_end_fields_and_face_off.php `EXTRACTED`
- 2026_09_09_150000_update_ici_paint_products_from_excel.php `EXTRACTED`
- 2026_09_14_110000_seed_wings_mbr_freetaste_report_template.php `EXTRACTED`
- *…and 36 more `imports` connection(s) not listed (lowest-degree first to go)*

### inherits
- Illuminate\Database\Eloquent\Model `EXTRACTED`

### method
- .syncDuluxMergedStockEnd() `EXTRACTED`
- .principals() `EXTRACTED`
- .isMonthlyDueForDate() `EXTRACTED`
- .isWithinMonthlyRange() `EXTRACTED`
- .principal() `EXTRACTED`
- .assignments() `EXTRACTED`
- .isScheduledForDate() `EXTRACTED`
- .getDefaultDashboardConfig() `EXTRACTED`
- .getResolvedDashboardConfigAttribute() `EXTRACTED`
- .products() `EXTRACTED`
- .positions() `EXTRACTED`
- .employees() `EXTRACTED`
- .fields() `EXTRACTED`
- .submissions() `EXTRACTED`
- .calculateCutoffTarget() `EXTRACTED`
- .syncDuluxOfftakeTemplate() `EXTRACTED`
- .booted() `EXTRACTED`
- .scopeRegular() `EXTRACTED`
- .scopeEventMbr() `EXTRACTED`
- .getReportGroupLabelAttribute() `EXTRACTED`

### mixes_in
- Illuminate\Database\Eloquent\Factories\HasFactory `EXTRACTED`

### references
- .buildFreeTasteAreaRegionSheet() `EXTRACTED`
- .buildFreeTasteRawSubmissionsSheet() `EXTRACTED`
- .buildFreeTasteSummarySheet() `EXTRACTED`
- .buildSalesAreaRegionSheet() `EXTRACTED`
- .buildSalesRawSubmissionsSheet() `EXTRACTED`
- .buildSalesSummarySheet() `EXTRACTED`
- .buildToolsSummarySheet() `EXTRACTED`
- .buildFreeTasteProductSheet() `EXTRACTED`
- .buildFreeTasteTopMitraSheet() `EXTRACTED`
- .buildSalesProductSheet() `EXTRACTED`
- .buildSalesTopMitraSheet() `EXTRACTED`
- .buildTools13ItemsSheet() `EXTRACTED`
- .buildToolsMitraActivitySheet() `EXTRACTED`
- .buildToolsRawSubmissionsSheet() `EXTRACTED`
- .exportFreeTaste() `EXTRACTED`
- .exportSales() `EXTRACTED`
- .exportTools() `EXTRACTED`
- .buildToolsDefectsSheet() `EXTRACTED`
- .calculateWingsMbrDashboardData() `EXTRACTED`
- .calculateWingsMbrFreeTasteDashboardData() `EXTRACTED`
- *…and 10 more `references` connection(s) not listed (lowest-degree first to go)*

---

*Part of the graphify knowledge wiki. See [index](index.md) to navigate.*