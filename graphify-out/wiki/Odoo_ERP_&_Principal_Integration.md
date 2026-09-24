# Odoo ERP & Principal Integration

> 43 nodes

## Key Concepts

- **OdooSyncService** (32 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **OdooSync** (13 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **OdooSync.php** (12 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **.syncEmployeesBatch()** (8 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.xmlRpcCall()** (8 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.authenticate()** (6 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.mergeDuplicates()** (6 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **OdooSyncCommand.php** (6 connections) — `att-admin-v12/app/Console/Commands/OdooSyncCommand.php`
- **.syncEmployees()** (5 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **.syncPrincipals()** (5 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **.syncPrincipals()** (5 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.xmlRpcToPHP()** (5 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.syncAll()** (4 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **.countOdooEmployees()** (4 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.syncEmployees()** (4 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **OdooSyncCommand** (3 connections) — `att-admin-v12/app/Console/Commands/OdooSyncCommand.php`
- **.handle()** (3 connections) — `att-admin-v12/app/Console/Commands/OdooSyncCommand.php`
- **.getCompany()** (3 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **.fromCompany()** (3 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.parseXmlRpcResponse()** (3 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.phpToXmlRpc()** (3 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.syncAllConfiguredCompanies()** (3 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **.testConnection()** (3 connections) — `att-admin-v12/app/Services/OdooSyncService.php`
- **{closure#1}()** (2 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- **{closure#2}()** (2 connections) — `att-admin-v12/app/Filament/Pages/OdooSync.php`
- *... and 18 more nodes in this community*

## Relationships

- [Employee & Identity Management](Employee_&_Identity_Management.md) (12 shared connections)
- [Admin Panel Filament Resources](Admin_Panel_Filament_Resources.md) (3 shared connections)
- [Attendance & Schedule Subsystem](Attendance_&_Schedule_Subsystem.md) (3 shared connections)
- [Odoo ERP & Principal Integration](Odoo_ERP_&_Principal_Integration.md) (2 shared connections)
- [Subsystem: ReportTemplate](Subsystem-_ReportTemplate.md) (2 shared connections)
- [Reporting & Form Engine](Reporting_&_Form_Engine.md) (1 shared connections)
- [Visit & Itinerary Management](Visit_&_Itinerary_Management.md) (1 shared connections)

## Source Files

- `att-admin-v12/app/Console/Commands/OdooSyncCommand.php`
- `att-admin-v12/app/Filament/Pages/OdooSync.php`
- `att-admin-v12/app/Services/OdooSyncService.php`

## Audit Trail

- EXTRACTED: 99 (100%)
- INFERRED: 0 (0%)
- AMBIGUOUS: 0 (0%)

---

*Part of the graphify knowledge wiki. See [index](index.md) to navigate.*