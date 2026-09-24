# Employee

> God node · 178 connections · `att-admin-v12/app/Models/Employee.php`

**Community:** [Employee & Identity Management](Employee_&_Identity_Management.md)

## Connections by Relation

### calls
- .editReportTemplate() `EXTRACTED`
- .schedulesList() `EXTRACTED`
- .createReportTemplate() `EXTRACTED`
- .itinerariesList() `EXTRACTED`
- .handle() `EXTRACTED`
- .teamPerformance() `EXTRACTED`
- .dashboard() `EXTRACTED`
- .run() `EXTRACTED`
- .store() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .handle() `EXTRACTED`
- .attendances() `EXTRACTED`
- .employeesList() `EXTRACTED`
- .collection() `EXTRACTED`
- .collection() `EXTRACTED`
- .collection() `EXTRACTED`
- .run() `EXTRACTED`
- *…and 62 more `calls` connection(s) not listed (lowest-degree first to go)*

### contains
- att-admin-v12/app/Models/Employee.php `EXTRACTED`

### imports
- PrincipalPortalController.php `EXTRACTED`
- ReportingApiController.php `EXTRACTED`
- EmployeeScheduleRoster.php `EXTRACTED`
- AttendanceRoster.php `EXTRACTED`
- att-admin-v12/routes/web.php `EXTRACTED`
- MeetingForm.php `EXTRACTED`
- ListEmployeeSchedules.php `EXTRACTED`
- att-admin-v12/app/Http/Controllers/Api/AttendanceController.php `EXTRACTED`
- CreateWorkingGroup.php `EXTRACTED`
- ListItineraries.php `EXTRACTED`
- DashboardApiController.php `EXTRACTED`
- ItineraryForm.php `EXTRACTED`
- ListEmployees.php `EXTRACTED`
- LocationRequestForm.php `EXTRACTED`
- OdooSyncService.php `EXTRACTED`
- VisitScheduleTemplateExport.php `EXTRACTED`
- EmployeeScheduleImport.php `EXTRACTED`
- ActiveEmployeesHourlyChartWidget.php `EXTRACTED`
- BapApiController.php `EXTRACTED`
- AttendanceBapForm.php `EXTRACTED`
- *…and 44 more `imports` connection(s) not listed (lowest-degree first to go)*

### inherits
- Illuminate\Foundation\Auth\User `EXTRACTED`

### method
- .getHasReportingTemplatesAttribute() `EXTRACTED`
- .workLocation() `EXTRACTED`
- .getEffectiveRadiusForLocation() `EXTRACTED`
- .user() `EXTRACTED`
- .company() `EXTRACTED`
- .principal() `EXTRACTED`
- .branch() `EXTRACTED`
- .department() `EXTRACTED`
- .position() `EXTRACTED`
- .area() `EXTRACTED`
- .getNameAttribute() `EXTRACTED`
- .getNikAttribute() `EXTRACTED`
- .deduplicateActiveRecords() `EXTRACTED`
- .booted() `EXTRACTED`
- .getIsInhouseAttribute() `EXTRACTED`
- .getPhotoUrlAttribute() `EXTRACTED`

### mixes_in
- Illuminate\Database\Eloquent\Factories\HasFactory `EXTRACTED`
- Illuminate\Database\Eloquent\SoftDeletes `EXTRACTED`
- Illuminate\Notifications\Notifiable `EXTRACTED`
- Laravel\Sanctum\HasApiTokens `EXTRACTED`

### references
- .getAuthenticatedEmployee() `EXTRACTED`
- .mergeDuplicates() `EXTRACTED`
- .checkPendingReportsStatic() `EXTRACTED`
- .update() `EXTRACTED`
- .edit() `EXTRACTED`
- {closure#1}() `EXTRACTED`
- .getEffectiveRadiusForEmployee() `EXTRACTED`
- .destroy() `EXTRACTED`
- .resolveRecord() `EXTRACTED`

### references_constant
- .supervisor() `EXTRACTED`

---

*Part of the graphify knowledge wiki. See [index](index.md) to navigate.*