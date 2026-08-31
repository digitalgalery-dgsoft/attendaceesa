<?php

namespace App\Filament\Resources\LocationRequests\Schemas;

use App\Models\Employee;
use App\Models\Principal;
use App\Models\Branch;
use App\Services\GoogleMapsService;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LocationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📌 Petunjuk Pengambilan Link Google Maps')
                    ->description('Gunakan panduan berikut untuk mendapatkan titik koordinat lokasi toko/kantor dengan akurat.')
                    ->schema([
                        Placeholder::make('google_maps_instructions')
                            ->label('')
                            ->content(new HtmlString('
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 1rem 1.25rem; font-size: 0.88rem; color: #166534; line-height: 1.6;">
                                    <div style="font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="font-size: 1.2rem;">📍</span> Cara Mendapatkan Link dari Google Maps:
                                    </div>
                                    <ol style="margin-left: 1.25rem; margin-bottom: 0;">
                                        <li>Buka aplikasi <strong>Google Maps</strong> di Smartphone (HP) atau Browser Komputer Anda.</li>
                                        <li>Ketik nama toko / lokasi pada pencarian, atau <strong>tekan & tahan</strong> pada titik lokasi toko di peta hingga muncul pin merah.</li>
                                        <li>Tekan tombol <strong>"Bagikan" (Share)</strong> pada informasi lokasi.</li>
                                        <li>Pilih <strong>"Salin Link" (Copy Link)</strong>.</li>
                                        <li>Tempelkan (Paste) link tersebut ke kolom <strong>"Link Google Maps"</strong> di bawah ini, lalu klik tombol <strong>"🔍 Ekstrak Koordinat"</strong>.</li>
                                    </ol>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make('Informasi Pengajuan')
                    ->columns(2)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan Pengaju')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, ?string $state) {
                                if ($state) {
                                    $emp = Employee::find($state);
                                    if ($emp) {
                                        if ($emp->principal_id) $set('principal_id', $emp->principal_id);
                                        if ($emp->branch_id) $set('branch_id', $emp->branch_id);
                                        if ($emp->company_id) $set('company_id', $emp->company_id);
                                    }
                                }
                            }),

                        Select::make('principal_id')
                            ->relationship('principal', 'name', fn ($query) => $query->where('is_active', true))
                            ->label('Prinsiple')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->label('Cabang / Area')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('type')
                            ->label('Tipe Lokasi')
                            ->options([
                                'store' => 'Toko / Outlet',
                                'client' => 'Client',
                                'office' => 'Kantor',
                                'project' => 'Project',
                                'warehouse' => 'Gudang',
                                'other' => 'Lainnya',
                            ])
                            ->default('store')
                            ->required(),

                        TextInput::make('name')
                            ->label('Nama Toko / Lokasi Baru')
                            ->placeholder('Contoh: Toko Cat Sumber Rejeki')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->placeholder('Masukkan alamat lengkap toko/lokasi...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Titik Koordinat & Geofence GPS')
                    ->columns(2)
                    ->schema([
                        TextInput::make('maps_url')
                            ->label('Link Google Maps (Share Link / URL)')
                            ->placeholder('https://maps.app.goo.gl/... atau https://www.google.com/maps?...')
                            ->helperText('Tempel link yang disalin dari Google Maps, lalu klik tombol ekstrak di samping kanan.')
                            ->columnSpanFull()
                            ->suffixAction(
                                \Filament\Actions\Action::make('extractCoords')
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->label('Ekstrak Koordinat')
                                    ->color('success')
                                    ->action(function ($state, $set, \Livewire\Component $livewire) {
                                        if (empty($state)) {
                                            Notification::make()
                                                ->title('⚠️ Masukkan Link Google Maps')
                                                ->body('Silakan tempelkan link Google Maps terlebih dahulu.')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        $result = GoogleMapsService::parseCoordinates($state);
                                        if ($result['success'] && !empty($result['latitude']) && !empty($result['longitude'])) {
                                            $lat = (float) $result['latitude'];
                                            $lng = (float) $result['longitude'];
                                            $set('latitude', $lat);
                                            $set('longitude', $lng);
                                            $set('location', ['lat' => $lat, 'lng' => $lng]);

                                            $livewire->dispatch('refreshMap');

                                            Notification::make()
                                                ->title('✅ Koordinat Berhasil Ditemukan')
                                                ->body("Latitude: {$lat}, Longitude: {$lng}")
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('❌ Gagal Mengekstrak Koordinat')
                                                ->body($result['message'] ?? 'Pastikan link Google Maps benar atau masukkan koordinat secara manual.')
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),

                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->required(),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->required(),

                        TextInput::make('radius_meter')
                            ->label('Radius Toleransi (Meter)')
                            ->numeric()
                            ->default(100)
                            ->required(),

                        Map::make('location')
                            ->label('Peta Titik Lokasi')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($set, ?array $state): void {
                                if (isset($state['lat']) && isset($state['lng'])) {
                                    $set('latitude', $state['lat']);
                                    $set('longitude', $state['lng']);
                                }
                            })
                            ->afterStateHydrated(function ($state, $record, $set): void {
                                if ($record && $record->latitude && $record->longitude) {
                                    $set('location', ['lat' => (float)$record->latitude, 'lng' => (float)$record->longitude]);
                                }
                            })
                            ->live(onBlur: true)
                            ->showMarker()
                            ->markerColor("#22c55e")
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable()
                            ->clickable(true)
                            ->defaultLocation(-7.2504, 112.7688)
                            ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                            ->zoom(16)
                            ->showMyLocationButton(),
                    ]),

                Section::make('Foto & Catatan')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Foto Toko / Lokasi')
                            ->image()
                            ->directory('location_requests')
                            ->imagePreviewHeight('200')
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Catatan dari Karyawan')
                            ->placeholder('Alasan atau keterangan penambahan toko...')
                            ->rows(4)
                            ->columnSpan(1),
                    ]),

                Section::make('Status & Persetujuan Administrator')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status Pengajuan')
                            ->options([
                                'pending' => 'Menunggu Approval (Pending)',
                                'approved' => 'Disetujui (Approved)',
                                'rejected' => 'Ditolak (Rejected)',
                            ])
                            ->default('pending')
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Catatan / Alasan Admin')
                            ->placeholder('Catatan atau alasan penolakan/persetujuan...')
                            ->rows(2),
                    ]),
            ]);
    }
}
