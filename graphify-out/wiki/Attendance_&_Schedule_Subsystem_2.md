# Attendance & Schedule Subsystem

> 35 nodes

## Key Concepts

- **Closure** (16 connections)
- **Symfony\Component\HttpFoundation\Response** (12 connections)
- **SmartGatewayRelayService** (7 connections) — `att-admin-v12/app/Services/SmartGatewayRelayService.php`
- **IdentifyTenantSubdomain.php** (7 connections) — `att-admin-v12/app/Http/Middleware/IdentifyTenantSubdomain.php`
- **.handle()** (5 connections) — `att-admin-v12/app/Http/Middleware/IdentifyTenantSubdomain.php`
- **.handle()** (5 connections) — `att-admin-v12/app/Http/Middleware/SmartGatewayRelayMiddleware.php`
- **SmartGatewayRelayMiddleware.php** (5 connections) — `att-admin-v12/app/Http/Middleware/SmartGatewayRelayMiddleware.php`
- **RedirectIfAuthenticated.php** (5 connections) — `att-admindashboard/app/Http/Middleware/RedirectIfAuthenticated.php`
- **.handle()** (4 connections) — `att-admin-v12/app/Http/Middleware/CheckIfInstalled.php`
- **.handle()** (4 connections) — `att-admin-v12/app/Http/Middleware/CheckInstalled.php`
- **.handle()** (4 connections) — `att-admin-v12/app/Http/Middleware/RedirectIfInstalled.php`
- **.handle()** (4 connections) — `att-admin-v12/app/Http/Middleware/SecurityHeadersMiddleware.php`
- **CheckIfInstalled.php** (4 connections) — `att-admin-v12/app/Http/Middleware/CheckIfInstalled.php`
- **CheckInstalled.php** (4 connections) — `att-admin-v12/app/Http/Middleware/CheckInstalled.php`
- **RedirectIfInstalled.php** (4 connections) — `att-admin-v12/app/Http/Middleware/RedirectIfInstalled.php`
- **SecurityHeadersMiddleware.php** (4 connections) — `att-admin-v12/app/Http/Middleware/SecurityHeadersMiddleware.php`
- **.attemptRelayLogin()** (3 connections) — `att-admin-v12/app/Services/SmartGatewayRelayService.php`
- **.relayRequest()** (3 connections) — `att-admin-v12/app/Services/SmartGatewayRelayService.php`
- **.handle()** (3 connections) — `att-admindashboard/app/Http/Middleware/LocaleMiddleware.php`
- **.handle()** (3 connections) — `att-admindashboard/app/Http/Middleware/RedirectIfAuthenticated.php`
- **LocaleMiddleware.php** (3 connections) — `att-admindashboard/app/Http/Middleware/LocaleMiddleware.php`
- **CheckIfInstalled** (2 connections) — `att-admin-v12/app/Http/Middleware/CheckIfInstalled.php`
- **CheckInstalled** (2 connections) — `att-admin-v12/app/Http/Middleware/CheckInstalled.php`
- **IdentifyTenantSubdomain** (2 connections) — `att-admin-v12/app/Http/Middleware/IdentifyTenantSubdomain.php`
- **RedirectIfInstalled** (2 connections) — `att-admin-v12/app/Http/Middleware/RedirectIfInstalled.php`
- *... and 10 more nodes in this community*

## Relationships

- [Attendance & Schedule Subsystem](Attendance_&_Schedule_Subsystem.md) (19 shared connections)
- [Subsystem: ReportTemplate](Subsystem-_ReportTemplate.md) (2 shared connections)
- [Reporting & Form Engine](Reporting_&_Form_Engine.md) (1 shared connections)
- [Employee & Identity Management](Employee_&_Identity_Management.md) (1 shared connections)

## Source Files

- `att-admin-v12/app/Http/Middleware/CheckIfInstalled.php`
- `att-admin-v12/app/Http/Middleware/CheckInstalled.php`
- `att-admin-v12/app/Http/Middleware/IdentifyTenantSubdomain.php`
- `att-admin-v12/app/Http/Middleware/RedirectIfInstalled.php`
- `att-admin-v12/app/Http/Middleware/SecurityHeadersMiddleware.php`
- `att-admin-v12/app/Http/Middleware/SmartGatewayRelayMiddleware.php`
- `att-admin-v12/app/Services/SmartGatewayRelayService.php`
- `att-admindashboard/app/Http/Middleware/LocaleMiddleware.php`
- `att-admindashboard/app/Http/Middleware/RedirectIfAuthenticated.php`

## Audit Trail

- EXTRACTED: 77 (100%)
- INFERRED: 0 (0%)
- AMBIGUOUS: 0 (0%)

---

*Part of the graphify knowledge wiki. See [index](index.md) to navigate.*