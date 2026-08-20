<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class PermissionMatrix extends Field
{
    protected string $view = 'filament.forms.components.permission-matrix';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (PermissionMatrix $component, $state, $record) {
            if ($record && method_exists($record, 'permissions')) {
                $component->state($record->permissions->pluck('name')->toArray());
            } elseif (is_array($state)) {
                $component->state($state);
            } else {
                $component->state([]);
            }
        });

        $this->columnSpanFull();
        $this->dehydrated(true);
    }

    public static function getCategories(): array
    {
        return [
            'Master Data' => [
                'employees' => [
                    'label' => 'Employees (Karyawan)',
                    'actions' => [
                        'view' => ['name' => 'view_employees', 'label' => 'View'],
                        'create' => ['name' => 'create_employees', 'label' => 'Create'],
                        'update' => ['name' => 'update_employees', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_employees', 'label' => 'Delete'],
                    ]
                ],
                'branches' => [
                    'label' => 'Areas / Cabang',
                    'actions' => [
                        'view' => ['name' => 'view_areas', 'label' => 'View'],
                        'create' => ['name' => 'create_branches', 'label' => 'Create'],
                        'update' => ['name' => 'update_branches', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_branches', 'label' => 'Delete'],
                    ]
                ],
                'principals' => [
                    'label' => 'Principals (Prinsiple)',
                    'actions' => [
                        'view' => ['name' => 'view_principals', 'label' => 'View'],
                        'create' => ['name' => 'create_principals', 'label' => 'Create'],
                        'update' => ['name' => 'update_principals', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_principals', 'label' => 'Delete'],
                    ]
                ],
                'companies' => [
                    'label' => 'Companies (Perusahaan)',
                    'actions' => [
                        'view' => ['name' => 'view_companies', 'label' => 'View'],
                        'create' => ['name' => 'create_companies', 'label' => 'Create'],
                        'update' => ['name' => 'update_companies', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_companies', 'label' => 'Delete'],
                    ]
                ],
                'departments' => [
                    'label' => 'Departments (Departemen)',
                    'actions' => [
                        'view' => ['name' => 'view_departments', 'label' => 'View'],
                        'create' => ['name' => 'create_departments', 'label' => 'Create'],
                        'update' => ['name' => 'update_departments', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_departments', 'label' => 'Delete'],
                    ]
                ],
                'positions' => [
                    'label' => 'Positions (Jabatan)',
                    'actions' => [
                        'view' => ['name' => 'view_positions', 'label' => 'View'],
                        'create' => ['name' => 'create_positions', 'label' => 'Create'],
                        'update' => ['name' => 'update_positions', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_positions', 'label' => 'Delete'],
                    ]
                ],
                'work_locations' => [
                    'label' => 'Work Locations (Lokasi Kerja)',
                    'actions' => [
                        'view' => ['name' => 'view_work_locations', 'label' => 'View'],
                        'create' => ['name' => 'create_work_locations', 'label' => 'Create'],
                        'update' => ['name' => 'update_work_locations', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_work_locations', 'label' => 'Delete'],
                    ]
                ],
                'shifts' => [
                    'label' => 'Shifts (Shift Kerja)',
                    'actions' => [
                        'view' => ['name' => 'view_shifts', 'label' => 'View'],
                        'create' => ['name' => 'create_shifts', 'label' => 'Create'],
                        'update' => ['name' => 'update_shifts', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_shifts', 'label' => 'Delete'],
                    ]
                ],
                'holidays' => [
                    'label' => 'Holidays (Hari Libur)',
                    'actions' => [
                        'view' => ['name' => 'view_holidays', 'label' => 'View'],
                        'create' => ['name' => 'create_holidays', 'label' => 'Create'],
                        'update' => ['name' => 'update_holidays', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_holidays', 'label' => 'Delete'],
                    ]
                ],
            ],
            'Attendance & Time Management' => [
                'attendances' => [
                    'label' => 'Attendances (Presensi / Absensi)',
                    'actions' => [
                        'view' => ['name' => 'view_attendance', 'label' => 'View'],
                        'create' => ['name' => 'create_attendances', 'label' => 'Create'],
                        'update' => ['name' => 'update_attendances', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_attendances', 'label' => 'Delete'],
                    ]
                ],
                'employee_schedules' => [
                    'label' => 'Roster & Jadwal Kerja',
                    'actions' => [
                        'view' => ['name' => 'manage_roster', 'label' => 'View'],
                        'create' => ['name' => 'create_employee_schedules', 'label' => 'Create'],
                        'update' => ['name' => 'update_employee_schedules', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_employee_schedules', 'label' => 'Delete'],
                    ]
                ],
                'leave_requests' => [
                    'label' => 'Leave Requests (Izin / Cuti)',
                    'actions' => [
                        'view' => ['name' => 'view_leave_requests', 'label' => 'View'],
                        'create' => ['name' => 'create_leave_requests', 'label' => 'Create'],
                        'update' => ['name' => 'update_leave_requests', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_leave_requests', 'label' => 'Delete'],
                    ]
                ],
                'extra_hours' => [
                    'label' => 'Extra Hours (Lembur)',
                    'actions' => [
                        'view' => ['name' => 'view_extra_hours', 'label' => 'View'],
                        'create' => ['name' => 'create_extra_hours', 'label' => 'Create'],
                        'update' => ['name' => 'update_extra_hours', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_extra_hours', 'label' => 'Delete'],
                    ]
                ],
                'working_groups' => [
                    'label' => 'Working Groups (Pola Kerja)',
                    'actions' => [
                        'view' => ['name' => 'view_working_groups', 'label' => 'View'],
                        'create' => ['name' => 'create_working_groups', 'label' => 'Create'],
                        'update' => ['name' => 'update_working_groups', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_working_groups', 'label' => 'Delete'],
                    ]
                ],
                'unchecked_monitoring' => [
                    'label' => 'Monitoring Tim Belum Check-in',
                    'actions' => [
                        'view' => ['name' => 'view_unchecked_monitoring', 'label' => 'View'],
                    ]
                ],
            ],
            'Field Operations & Sales' => [
                'itineraries' => [
                    'label' => 'Visit Schedule (Itinerari)',
                    'actions' => [
                        'view' => ['name' => 'view_itineraries', 'label' => 'View'],
                        'create' => ['name' => 'create_itineraries', 'label' => 'Create'],
                        'update' => ['name' => 'update_itineraries', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_itineraries', 'label' => 'Delete'],
                    ]
                ],
                'visit_reports' => [
                    'label' => 'Visit Reports (Laporan Kunjungan)',
                    'actions' => [
                        'view' => ['name' => 'view_visit_reports', 'label' => 'View'],
                        'create' => ['name' => 'create_visit_reports', 'label' => 'Create'],
                        'update' => ['name' => 'update_visit_reports', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_visit_reports', 'label' => 'Delete'],
                    ]
                ],
                'sales_reports' => [
                    'label' => 'Sales Reports (Laporan Penjualan)',
                    'actions' => [
                        'view' => ['name' => 'view_sales_reports', 'label' => 'View'],
                        'create' => ['name' => 'create_sales_reports', 'label' => 'Create'],
                        'update' => ['name' => 'update_sales_reports', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_sales_reports', 'label' => 'Delete'],
                    ]
                ],
                'work_targets' => [
                    'label' => 'Work Targets (Target Kerja)',
                    'actions' => [
                        'view' => ['name' => 'view_work_targets', 'label' => 'View'],
                        'create' => ['name' => 'create_work_targets', 'label' => 'Create'],
                        'update' => ['name' => 'update_work_targets', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_work_targets', 'label' => 'Delete'],
                    ]
                ],
                'payslips' => [
                    'label' => 'Payslips (Slip Gaji)',
                    'actions' => [
                        'view' => ['name' => 'view_payslips', 'label' => 'View'],
                        'create' => ['name' => 'create_payslips', 'label' => 'Create'],
                        'update' => ['name' => 'update_payslips', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_payslips', 'label' => 'Delete'],
                    ]
                ],
            ],
            'Reports & Analytics' => [
                'manpower_report' => [
                    'label' => 'Manpower Report',
                    'actions' => [
                        'view' => ['name' => 'view_manpower_report', 'label' => 'View'],
                    ]
                ],
                'mandays_report' => [
                    'label' => 'Mandays Report',
                    'actions' => [
                        'view' => ['name' => 'view_mandays_report', 'label' => 'View'],
                    ]
                ],
                'turnover_report' => [
                    'label' => 'Turnover Report',
                    'actions' => [
                        'view' => ['name' => 'view_turnover_report', 'label' => 'View'],
                    ]
                ],
                'odoo_sync_report' => [
                    'label' => 'Odoo Sync Report',
                    'actions' => [
                        'view' => ['name' => 'view_odoo_sync', 'label' => 'View'],
                    ]
                ],
            ],
            'System & Settings' => [
                'users' => [
                    'label' => 'Users (Manajemen User)',
                    'actions' => [
                        'view' => ['name' => 'manage_users', 'label' => 'View'],
                        'create' => ['name' => 'create_users', 'label' => 'Create'],
                        'update' => ['name' => 'update_users', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_users', 'label' => 'Delete'],
                    ]
                ],
                'roles' => [
                    'label' => 'Roles & Permissions (Hak Akses)',
                    'actions' => [
                        'view' => ['name' => 'manage_roles', 'label' => 'View'],
                        'create' => ['name' => 'create_roles', 'label' => 'Create'],
                        'update' => ['name' => 'update_roles', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_roles', 'label' => 'Delete'],
                    ]
                ],
                'settings' => [
                    'label' => 'System Settings (Pengaturan Sistem)',
                    'actions' => [
                        'view' => ['name' => 'manage_settings', 'label' => 'View'],
                        'update' => ['name' => 'update_settings', 'label' => 'Update'],
                    ]
                ],
                'blast_info' => [
                    'label' => 'Blast Info (Broadcast Pesan)',
                    'actions' => [
                        'view' => ['name' => 'view_blast_info', 'label' => 'View'],
                        'create' => ['name' => 'create_blast_info', 'label' => 'Create'],
                        'update' => ['name' => 'update_blast_info', 'label' => 'Update'],
                        'delete' => ['name' => 'delete_blast_info', 'label' => 'Delete'],
                    ]
                ],
                'live_chat' => [
                    'label' => 'Live Chat Support',
                    'actions' => [
                        'view' => ['name' => 'view_live_chat', 'label' => 'View'],
                    ]
                ],
            ],
        ];
    }
}
