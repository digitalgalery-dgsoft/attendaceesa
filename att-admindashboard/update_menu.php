<?php
$files = ['resources/menu/verticalMenu.json', 'resources/menu/horizontalMenu.json'];

$newMenu = [
    "menuHeader" => "Master Data"
];

$branchMenu = [
    "url" => "/branches",
    "name" => "Branches",
    "icon" => "menu-icon tf-icons ti ti-map-pin",
    "slug" => "branches-index"
];

$employeeMenu = [
    "url" => "/employees",
    "name" => "Employees",
    "icon" => "menu-icon tf-icons ti ti-users",
    "slug" => "employees-index"
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        
        // Insert right after the first item (Dashboards)
        array_splice($data['menu'], 1, 0, [$newMenu, $branchMenu, $employeeMenu]);
        
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        echo "Updated $file\n";
    }
}
