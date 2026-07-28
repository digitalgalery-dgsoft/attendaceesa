<?php
require 'vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use App\Models\Area;
use App\Models\Principal;
use App\Models\Company;
use Illuminate\Support\Carbon;

$file = 'C:/Users/jamil/Downloads/Karyawan_Inhouse_Aktif.csv';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$company = Company::first();
if (!$company) {
    die("No company found. Please create a company first.\n");
}

$rows = array_map('str_getcsv', file($file));
$header = null;
$employeesToUpdateSupervisor = [];

// Start at row index 3 (0-indexed) where the header is, but the user's data shows headers on line 4, which is index 3.
foreach ($rows as $index => $row) {
    if (empty($row[0])) continue;
    
    // Find the header row
    if ($row[0] === 'No.') {
        $header = $row;
        continue;
    }

    if (!$header) continue;

    // Pad row if it has fewer columns than header
    if (count($row) < count($header)) {
        $row = array_pad($row, count($header), null);
    }
    
    // Sometimes row has more columns than header
    if (count($row) > count($header)) {
        $row = array_slice($row, 0, count($header));
    }

    $data = array_combine($header, $row);

    // Clean up strings (like the leading single quote in NIP and KTP)
    $ktp = ltrim($data['Nomor KTP'], "'");
    $nip = ltrim($data['NIP'], "'");
    $name = $data['Nama Karyawan'];
    $jabatan = $data['Jabatan DB'];
    $divisi = $data['Divisi'];
    $areaName = $data['Area'];
    $principalName = $data['Prinsiple'];
    $pimpinanName = $data['Pimpinan'];
    $status = $data['Status'];
    $joinDateStr = $data['Join Date'];
    $email = $data['Email'];
    $resignDateStr = $data['Tgl Resign'] ?? null;

    if (empty($nip)) continue;

    echo "Processing $name ($nip)...\n";

    // Position
    $positionId = null;
    if ($jabatan) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $jabatan), 0, 30) . '_' . substr(md5($jabatan), 0, 4));
        $position = Position::firstOrCreate(['name' => $jabatan, 'company_id' => $company->id], ['code' => $code]);
        $positionId = $position->id;
    }

    // Department
    $departmentId = null;
    if ($divisi) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $divisi), 0, 30) . '_' . substr(md5($divisi), 0, 4));
        $department = Department::firstOrCreate(['name' => $divisi, 'company_id' => $company->id], ['code' => $code]);
        $departmentId = $department->id;
    }

    // Area
    $areaId = null;
    if ($areaName) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $areaName), 0, 30) . '_' . substr(md5($areaName), 0, 4));
        $area = Area::firstOrCreate(['name' => $areaName], ['code' => $code]);
        $areaId = $area->id;
    }

    // Principal
    $principalId = null;
    if ($principalName) {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $principalName), 0, 30) . '_' . substr(md5($principalName), 0, 4));
        $principal = Principal::firstOrCreate(['name' => $principalName], ['code' => $code]);
        $principalId = $principal->id;
    }

    // Dates
    $joinDate = null;
    if ($joinDateStr) {
        try {
            $joinDate = Carbon::parse($joinDateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            $joinDate = null;
        }
    }
    
    $resignDate = null;
    if ($resignDateStr) {
        try {
            $resignDate = Carbon::parse($resignDateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            $resignDate = null;
        }
    }
    
    // Status
    $empStatus = 'contract';
    if (strtolower($status) == 'aktiv' || strtolower($status) == 'aktif') {
        $empStatus = 'permanent';
    }

    $isActive = (strtolower($status) == 'aktiv' || strtolower($status) == 'aktif');

    $employee = Employee::updateOrCreate(
        ['employee_no' => $nip, 'company_id' => $company->id],
        [
            'full_name' => $name,
            'position_id' => $positionId,
            'department_id' => $departmentId,
            'area_id' => $areaId,
            'principal_id' => $principalId,
            'employment_status' => $empStatus,
            'join_date' => $joinDate,
            'email' => $email,
            'resign_date' => $resignDate,
            'is_active' => $isActive,
        ]
    );

    if ($pimpinanName) {
        $employeesToUpdateSupervisor[$employee->id] = $pimpinanName;
    }
}

// Second pass for supervisors
echo "Updating supervisors...\n";
foreach ($employeesToUpdateSupervisor as $empId => $supervisorName) {
    $supervisor = Employee::where('full_name', 'like', "%$supervisorName%")->first();
    if ($supervisor) {
        Employee::where('id', $empId)->update(['supervisor_id' => $supervisor->id]);
        echo "Updated supervisor for Employee ID $empId to {$supervisor->id}\n";
    }
}

echo "Done importing employees.\n";
