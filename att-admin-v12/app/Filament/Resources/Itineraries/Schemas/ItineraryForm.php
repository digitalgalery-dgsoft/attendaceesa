<?php

namespace App\Filament\Resources\Itineraries\Schemas;

use App\Models\Employee;
use App\Models\Principal;
use App\Models\WorkLocation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItineraryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Karyawan & Tanggal Visit')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->options(function () {
                                $query = Employee::where('is_active', 1)->with(['position', 'branch', 'principal']);
                                if (auth()->check()) {
                                    $query = \App\Traits\ScopesUserData::applyUserAccessScope($query);
                                }
                                return $query->orderBy('full_name')->get()->mapWithKeys(function ($emp) {
                                    $pos = $emp->position?->name ?? 'Staff';
                                    $area = $emp->branch?->name ?? '-';
                                    return [$emp->id => "{$emp->full_name} ({$pos} - {$area})"];
                                });
                            })
                            ->searchable()
                            ->required(),
                        Radio::make('creation_type')
                            ->label('Tipe Penjadwalan')
                            ->options([
                                'single' => 'Satu Hari Spesifik',
                                'month' => 'Satu Bulan Penuh',
                            ])
                            ->default('single')
                            ->inline()
                            ->live()
                            ->hiddenOn('edit'),
                        DatePicker::make('date')
                            ->label('Tanggal Visit')
                            ->required(fn ($get) => $get('creation_type') === 'single')
                            ->visible(fn ($get) => $get('creation_type') === 'single' || request()->routeIs('*.edit'))
                            ->default(now()),
                        Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ])
                            ->required(fn ($get) => $get('creation_type') === 'month')
                            ->visible(fn ($get) => $get('creation_type') === 'month' && !request()->routeIs('*.edit'))
                            ->default(now()->month),
                        Select::make('year')
                            ->label('Tahun')
                            ->options(
                                array_combine(
                                    range(now()->year - 1, now()->year + 2),
                                    range(now()->year - 1, now()->year + 2)
                                )
                            )
                            ->required(fn ($get) => $get('creation_type') === 'month')
                            ->visible(fn ($get) => $get('creation_type') === 'month' && !request()->routeIs('*.edit'))
                            ->default(now()->year),
                        Select::make('status')
                            ->label('Status Visit')
                            ->options([
                                'approved' => 'Approved',
                                'draft' => 'Draft',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('approved')
                            ->required(),
                        Textarea::make('notes')
                            ->label('Catatan Umum')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Daftar Titik / Lokasi Kunjungan Visit')
                    ->description('Tentukan toko, toko rekanan, atau lokasi yang akan dikunjungi oleh karyawan.')
                    ->schema([
                        Repeater::make('items')
                            ->label('Lokasi Kunjungan')
                            ->relationship()
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('work_location_id')
                                        ->label('Lokasi / Toko')
                                        ->options(function () {
                                            $query = WorkLocation::orderBy('name');
                                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasBranchRestriction()) {
                                                $query->whereIn('branch_id', auth()->user()->getAccessibleBranchIds());
                                            }
                                            return $query->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->required(),
                                    Select::make('principal_id')
                                        ->label('Prinsiple (Opsional)')
                                        ->options(function () {
                                            $query = Principal::orderBy('name');
                                            if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->hasPrincipalRestriction()) {
                                                $query->whereIn('id', auth()->user()->getAccessiblePrincipalIds());
                                            }
                                            return $query->pluck('name', 'id');
                                        })
                                        ->searchable(),
                                    TextInput::make('sequence')
                                        ->label('Urutan Kunjungan')
                                        ->numeric()
                                        ->default(1)
                                        ->required(),
                                ]),
                                Grid::make(2)->schema([
                                    Select::make('visit_type')
                                        ->label('Tipe Kunjungan')
                                        ->options([
                                            'store' => 'Store Visit / Toko',
                                            'principal' => 'Principal Office',
                                            'meeting' => 'Meeting / Pertemuan',
                                            'survey' => 'Survey Lapangan',
                                            'other' => 'Lainnya',
                                        ])
                                        ->default('store'),
                                    Toggle::make('is_checkin_location')
                                        ->label('Jadikan Lokasi Check-In Absensi')
                                        ->helperText('Karyawan bisa check-in di lokasi ini jika belum memiliki jadwal roster di tanggal ini.')
                                        ->default(false),
                                ]),
                                Textarea::make('notes')
                                    ->label('Agenda / Catatan Kunjungan')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->collapsible()
                            ->reorderableWithButtons()
                            ->columnSpanFull()
                    ]),
            ]);
    }
}
