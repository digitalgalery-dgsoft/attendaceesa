<?php

namespace App\Filament\Resources\AttendanceBaps\Schemas;

use App\Models\Employee;
use App\Models\Principal;
use App\Models\WorkLocation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class AttendanceBapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Karyawan & Tanggal Presensi')
                    ->description('Data karyawan dan tanggal jadwal kerja yang diajukan BAP.')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('employee_id')
                                ->label('Nama Karyawan')
                                ->relationship('employee', 'full_name')
                                ->searchable(['full_name', 'employee_no'])
                                ->getOptionLabelFromRecordUsing(fn (Employee $record) => "{$record->full_name} ({$record->employee_no})")
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $emp = Employee::find($state);
                                        if ($emp) {
                                            $set('principal_id', $emp->principal_id);
                                            $set('company_id', $emp->company_id);
                                            $set('branch_id', $emp->branch_id);
                                        }
                                    }
                                }),

                            DatePicker::make('date')
                                ->label('Tanggal Absensi')
                                ->required()
                                ->native(false)
                                ->displayFormat('d F Y'),

                            Select::make('principal_id')
                                ->label('Prinsiple')
                                ->relationship('principal', 'name')
                                ->searchable()
                                ->disabled(),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('work_location_id')
                                ->label('Lokasi Kerja / Toko Terjadwal')
                                ->relationship('workLocation', 'name')
                                ->searchable()
                                ->placeholder('Pilih lokasi kerja...'),

                            Select::make('issue_category')
                                ->label('Kategori Kendala Teknis')
                                ->options([
                                    'app_error'    => '📱 Kendala Aplikasi (Error / Force Close / Layar Putih)',
                                    'gps_network'  => '📡 Kendala Sinyal / Jaringan / GPS Map Error',
                                    'device_issue' => '🔋 Kendala Handphone (Baterai Habis / Kamera Rusak)',
                                    'server_down'  => '⚠️ Server Error / Sedang Maintenance',
                                    'other'        => '📝 Kendala Operasional Lainnya',
                                ])
                                ->required()
                                ->default('app_error'),
                        ]),
                    ]),

                Section::make('Waktu Presensi & Bukti Screenshot')
                    ->description('Jam masuk, jam pulang, dan lampiran tangkapan layar kamera timestamp.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('checkin_time')
                                ->label('Jam Masuk (Check-In)')
                                ->placeholder('08:00')
                                ->default('08:00')
                                ->required(),

                            TextInput::make('checkout_time')
                                ->label('Jam Pulang (Check-Out)')
                                ->placeholder('17:00')
                                ->default('17:00'),
                        ]),

                        Textarea::make('reason')
                            ->label('Penjelasan Kendala Teknis')
                            ->placeholder('Jelaskan detail kendala teknis yang dialami di lapangan...')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('evidence_path')
                            ->label('Foto Bukti Screenshot (Timestamp Camera / GPS Map Camera)')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('bap_evidence')
                            ->imageEditor()
                            ->openable()
                            ->downloadable()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Status Verifikasi & Approval')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('status')
                                ->label('Status BAP')
                                ->options([
                                    'pending'  => 'Menunggu Approval (Pending)',
                                    'approved' => 'Disetujui (Approved)',
                                    'rejected' => 'Ditolak (Rejected)',
                                ])
                                ->default('pending')
                                ->required(),

                            Select::make('approved_by')
                                ->label('Diverifikasi Oleh')
                                ->relationship('approver', 'name')
                                ->disabled(),

                            TextInput::make('approved_at')
                                ->label('Waktu Approval')
                                ->disabled(),
                        ]),

                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan (Jika Ditolak)')
                            ->placeholder('Tuliskan alasan penolakan jika status ditolak...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
