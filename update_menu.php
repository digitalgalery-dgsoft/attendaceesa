<?php

$basePath = __DIR__ . '/att-admin-v12/app/Filament/';

$mapping = [
    // 1. Master Data
    'Resources/Companies/CompanyResource.php' => ['1. Master Data', 1],
    'Resources/Departments/DepartmentResource.php' => ['1. Master Data', 2],
    'Resources/Positions/PositionResource.php' => ['1. Master Data', 3],
    'Resources/Principals/PrincipalResource.php' => ['1. Master Data', 4],
    'Resources/Branches/BranchResource.php' => ['1. Master Data', 5], // Assuming Areas is BranchResource
    'Resources/WorkLocations/WorkLocationResource.php' => ['1. Master Data', 6],
    'Resources/Shifts/ShiftResource.php' => ['1. Master Data', 7],
    'Resources/Holidays/HolidayResource.php' => ['1. Master Data', 8],

    // 2. Employee Management
    'Resources/Employees/EmployeeResource.php' => ['2. Employee Management', 1],
    'Resources/WorkTargetResource.php' => ['2. Employee Management', 2],
    'Resources/PayslipResource.php' => ['2. Employee Management', 3],
    
    // 3. Attendance & Time Management
    'Resources/Attendances/AttendanceResource.php' => ['3. Attendance & Time Management', 1],
    'Resources/EmployeeSchedules/EmployeeScheduleResource.php' => ['3. Attendance & Time Management', 2],
    'Resources/LeaveRequestResource.php' => ['3. Attendance & Time Management', 3],
    'Resources/ExtraHourResource.php' => ['3. Attendance & Time Management', 4],

    // 4. Field Operations & Sales
    'Resources/Itineraries/ItineraryResource.php' => ['4. Field Operations & Sales', 1],
    'Resources/VisitReportResource.php' => ['4. Field Operations & Sales', 2],
    'Resources/SalesReports/SalesReportResource.php' => ['4. Field Operations & Sales', 3],

    // 5. Communication
    'Pages/LiveChat.php' => ['5. Communication', 1],
    'Resources/BlastInfoResource.php' => ['5. Communication', 2],

    // 6. Reports & Analytics
    'Pages/ManPowerReport.php' => ['6. Reports & Analytics', 1],
    'Pages/TurnOverReport.php' => ['6. Reports & Analytics', 2],
    'Pages/MandaysReport.php' => ['6. Reports & Analytics', 3],

    // 7. System & Settings
    'Resources/Users/UserResource.php' => ['7. System & Settings', 1],
    'Resources/Roles/RoleResource.php' => ['7. System & Settings', 2],
    'Pages/AiSettings.php' => ['7. System & Settings', 3],
    'Pages/ManageSettings.php' => ['7. System & Settings', 4],
];

foreach ($mapping as $file => $data) {
    $filePath = $basePath . $file;
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }

    $content = file_get_contents($filePath);
    $group = $data[0];
    $sort = $data[1];

    // Replace or insert $navigationGroup
    if (preg_match('/protected static string\|\\\\?UnitEnum\|null \$navigationGroup = .*;/', $content)) {
        $content = preg_replace('/protected static string\|\\\\?UnitEnum\|null \$navigationGroup = .*;/', "protected static ?string \$navigationGroup = '$group';", $content);
    } elseif (preg_match('/protected static \?string \$navigationGroup = .*;/', $content)) {
        $content = preg_replace('/protected static \?string \$navigationGroup = .*;/', "protected static ?string \$navigationGroup = '$group';", $content);
    } else {
        // insert after $navigationIcon or $model
        if (preg_match('/protected static string\|\\\\?BackedEnum\|null \$navigationIcon = .*;/', $content)) {
            $content = preg_replace('/(protected static string\|\\\\?BackedEnum\|null \$navigationIcon = .*;)/', "$1\n    protected static ?string \$navigationGroup = '$group';", $content);
        } else {
            $content = preg_replace('/(protected static \?string \$model = .*;)/', "$1\n    protected static ?string \$navigationGroup = '$group';", $content);
        }
    }

    // Replace or insert $navigationSort
    if (preg_match('/protected static \?int \$navigationSort = .*;/', $content)) {
        $content = preg_replace('/protected static \?int \$navigationSort = .*;/', "protected static ?int \$navigationSort = $sort;", $content);
    } else {
        // insert after $navigationGroup
        $content = preg_replace('/(protected static \?string \$navigationGroup = .*;)/', "$1\n    protected static ?int \$navigationSort = $sort;", $content);
    }

    file_put_contents($filePath, $content);
    echo "Updated: $file\n";
}

echo "Done.\n";
