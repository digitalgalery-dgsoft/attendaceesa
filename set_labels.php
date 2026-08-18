<?php

$basePath = __DIR__ . '/att-admin-v12/app/Filament/';

$mapping = [
    // 1. Master Data
    'Resources/Companies/CompanyResource.php' => 'Companies',
    'Resources/Departments/DepartmentResource.php' => 'Departments',
    'Resources/Positions/PositionResource.php' => 'Positions',
    'Resources/Principals/PrincipalResource.php' => 'Principals',
    'Resources/Branches/BranchResource.php' => 'Areas', 
    'Resources/WorkLocations/WorkLocationResource.php' => 'Work Locations',
    'Resources/Shifts/ShiftResource.php' => 'Shifts',
    'Resources/Holidays/HolidayResource.php' => 'Holidays',

    // 2. Employee Management
    'Resources/Employees/EmployeeResource.php' => 'Employees',
    'Resources/WorkTargetResource.php' => 'Work Targets',
    'Resources/PayslipResource.php' => 'Payslips',
    
    // 3. Attendance & Time Management
    'Resources/Attendances/AttendanceResource.php' => 'Attendances',
    'Resources/EmployeeSchedules/EmployeeScheduleResource.php' => 'Roster Individual',
    'Resources/LeaveRequestResource.php' => 'Leave Requests',
    'Resources/ExtraHourResource.php' => 'Extra Hours',

    // 4. Field Operations & Sales
    'Resources/Itineraries/ItineraryResource.php' => 'Visit Schedule',
    'Resources/VisitReportResource.php' => 'Visit Reports',
    'Resources/SalesReports/SalesReportResource.php' => 'Sales Reports',

    // 5. Communication
    'Pages/LiveChat.php' => 'Live Chat',
    'Resources/BlastInfoResource.php' => 'Blast Infos',

    // 6. Reports & Analytics
    'Pages/ManPowerReport.php' => 'Manpower Report',
    'Pages/TurnOverReport.php' => 'Turnover Report',
    'Pages/MandaysReport.php' => 'Mandays Report',

    // 7. System & Settings
    'Resources/Users/UserResource.php' => 'Users',
    'Resources/Roles/RoleResource.php' => 'Roles',
    'Pages/AiSettings.php' => 'AI Configuration',
    'Pages/ManageSettings.php' => 'General Settings',
];

foreach ($mapping as $file => $label) {
    $filePath = $basePath . $file;
    if (!file_exists($filePath)) {
        continue;
    }

    $content = file_get_contents($filePath);

    // Ensure $navigationLabel is set
    if (preg_match('/protected static \?string \$navigationLabel\s*=\s*\'.*\';/', $content)) {
        $content = preg_replace('/protected static \?string \$navigationLabel\s*=\s*\'.*\';/', "protected static ?string \$navigationLabel = '$label';", $content);
    } else {
        // Insert it after $navigationSort
        $content = preg_replace('/(protected static \?int \$navigationSort = .*;)/', "$1\n    protected static ?string \$navigationLabel = '$label';", $content);
    }

    file_put_contents($filePath, $content);
    echo "Set label for: $file to $label\n";
}

echo "Done.\n";
