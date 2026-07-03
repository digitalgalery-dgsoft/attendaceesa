<?php
$files = ['resources/menu/verticalMenu.json', 'resources/menu/horizontalMenu.json'];

$menu = [
  "menu" => [
    [
      "name" => "Dashboards",
      "icon" => "menu-icon tf-icons ti ti-smart-home",
      "slug" => "dashboard",
      "url" => "/"
    ],
    [
      "menuHeader" => "Master Data"
    ],
    [
      "url" => "/branches",
      "name" => "Branches",
      "icon" => "menu-icon tf-icons ti ti-map-pin",
      "slug" => "branches-index"
    ],
    [
      "url" => "/employees",
      "name" => "Employees",
      "icon" => "menu-icon tf-icons ti ti-users",
      "slug" => "employees-index"
    ]
  ]
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        file_put_contents($path, json_encode($menu, JSON_PRETTY_PRINT));
        echo "Updated $file\n";
    }
}
