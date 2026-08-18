<?php

$basePath = __DIR__ . '/att-admin-v12/app/Filament/';

$mapping = [
    // 1. Master Data
    'Resources/Companies/CompanyResource.php',
    'Resources/Departments/DepartmentResource.php',
    'Resources/Positions/PositionResource.php',
    'Resources/Principals/PrincipalResource.php',
    'Resources/Branches/BranchResource.php', 
    'Resources/WorkLocations/WorkLocationResource.php',
    'Resources/Shifts/ShiftResource.php',
    'Resources/Holidays/HolidayResource.php',

    // 2. Employee Management
    'Resources/Employees/EmployeeResource.php',
    'Resources/WorkTargetResource.php',
    'Resources/PayslipResource.php',
    
    // 3. Attendance & Time Management
    'Resources/Attendances/AttendanceResource.php',
    'Resources/EmployeeSchedules/EmployeeScheduleResource.php',
    'Resources/LeaveRequestResource.php',
    'Resources/ExtraHourResource.php',

    // 4. Field Operations & Sales
    'Resources/Itineraries/ItineraryResource.php',
    'Resources/VisitReportResource.php',
    'Resources/SalesReports/SalesReportResource.php',

    // 5. Communication
    'Pages/LiveChat.php',
    'Resources/BlastInfoResource.php',

    // 6. Reports & Analytics
    'Pages/ManPowerReport.php',
    'Pages/TurnOverReport.php',
    'Pages/MandaysReport.php',

    // 7. System & Settings
    'Resources/Users/UserResource.php',
    'Resources/Roles/RoleResource.php',
    'Pages/AiSettings.php',
    'Pages/ManageSettings.php',
];

foreach ($mapping as $file) {
    $filePath = $basePath . $file;
    if (!file_exists($filePath)) {
        continue;
    }

    $content = file_get_contents($filePath);

    // Fix $navigationGroup type
    $content = preg_replace('/protected static \?string \$navigationGroup/', 'protected static string|\UnitEnum|null $navigationGroup', $content);

    // Also, some files might need $navigationIcon fixed if it was broken, but I only touched $navigationGroup and $navigationSort.
    // Let's verify $navigationSort type just in case. The base class Filament\Resources\Resource defines:
    // protected static ?int $navigationSort = null;
    // So ?int is fine for $navigationSort.

    file_put_contents($filePath, $content);
    echo "Fixed: $file\n";
}

echo "Done.\n";
