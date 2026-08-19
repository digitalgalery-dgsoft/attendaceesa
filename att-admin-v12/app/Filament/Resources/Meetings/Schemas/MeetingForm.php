<?php

namespace App\Filament\Resources\Meetings\Schemas;

use App\Models\Area;
use App\Models\Employee;
use App\Models\Principal;
use App\Models\WorkLocation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Meeting')
                    ->description('Detail jadwal dan jenis pertemuan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama / Judul Meeting')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        DatePicker::make('meeting_date')
                            ->label('Tanggal Meeting')
                            ->required()
                            ->default(now()),

                        Radio::make('meeting_type')
                            ->label('Jenis Meeting')
                            ->options([
                                'offline' => 'Offline (Tatap Muka)',
                                'online' => 'Online (Virtual)',
                            ])
                            ->default('offline')
                            ->inline()
                            ->live(),

                        TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->required()
                            ->default('09:00'),

                        TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->nullable(),

                        TextInput::make('meeting_link')
                            ->label('Link Meeting (Zoom / Google Meet / MS Teams)')
                            ->placeholder('https://meet.google.com/abc-defg-hij atau https://zoom.us/j/...')
                            ->url()
                            ->visible(fn ($get) => $get('meeting_type') === 'online')
                            ->required(fn ($get) => $get('meeting_type') === 'online')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Agenda / Catatan Pembahasan')
                            ->placeholder('Tuliskan agenda atau topik pembahasan meeting...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Lokasi Meeting (Offline / Radius Lock)')
                    ->description('Tentukan lokasi meeting untuk membatasi radius presensi Meet-In')
                    ->columns(2)
                    ->visible(fn ($get) => $get('meeting_type') === 'offline')
                    ->schema([
                        Select::make('work_location_id')
                            ->label('Pilih dari Master Lokasi (Opsional)')
                            ->options(WorkLocation::pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $loc = WorkLocation::find($state);
                                    if ($loc) {
                                        $set('location_name', $loc->name);
                                        $set('latitude', $loc->latitude);
                                        $set('longitude', $loc->longitude);
                                        $set('radius_meter', $loc->radius_meter ?? 100);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('location_name')
                            ->label('Nama Lokasi / Ruangan')
                            ->placeholder('Misal: Ruang Rapat Lt. 2, Kantor Cabang Surabaya')
                            ->required(fn ($get) => $get('meeting_type') === 'offline')
                            ->columnSpanFull(),

                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->required(fn ($get) => $get('meeting_type') === 'offline'),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->required(fn ($get) => $get('meeting_type') === 'offline'),

                        TextInput::make('radius_meter')
                            ->label('Radius Lock (Meter)')
                            ->numeric()
                            ->default(100)
                            ->required(fn ($get) => $get('meeting_type') === 'offline')
                            ->helperText('Jarak maksimal peserta diizinkan melakukan Meet-In dari titik lokasi.'),
                    ]),

                Section::make('Peserta Meeting')
                    ->description('Filter dan pilih karyawan yang diundang ke meeting')
                    ->columns(2)
                    ->schema([
                        Select::make('filter_principal_id')
                            ->label('Filter by Principal')
                            ->options(Principal::pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->placeholder('Semua Principal'),

                        Select::make('filter_area_id')
                            ->label('Filter by Area')
                            ->options(Area::pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->placeholder('Semua Area'),

                        Select::make('employees')
                            ->label('Daftar Peserta Meeting (Multiple)')
                            ->relationship('employees', 'full_name', function ($query, $get) {
                                $principalId = $get('filter_principal_id');
                                $areaId = $get('filter_area_id');
                                if ($principalId) {
                                    $query->where('principal_id', $principalId);
                                }
                                if ($areaId) {
                                    $query->where('area_id', $areaId);
                                }
                                return $query->where('is_active', true);
                            })
                            ->getSearchResultsUsing(function (string $search, $get) {
                                $principalId = $get('filter_principal_id');
                                $areaId = $get('filter_area_id');
                                return Employee::where('is_active', true)
                                    ->when($principalId, fn ($q) => $q->where('principal_id', $principalId))
                                    ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                                    ->where(function ($q) use ($search) {
                                        $q->where('full_name', 'like', "%{$search}%")
                                          ->orWhere('employee_no', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($emp) {
                                        $pos = $emp->position ? $emp->position->name : '-';
                                        return [$emp->id => "{$emp->full_name} (NIK: {$emp->employee_no}) - {$pos}"];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(function (array $values) {
                                return Employee::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn ($emp) => [$emp->id => "{$emp->full_name} (NIK: {$emp->employee_no})"])
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Ketik nama atau No. KTP / NIK karyawan untuk mencari.'),
                    ]),
            ]);
    }
}
