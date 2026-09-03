<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Database\Schema\Blueprint;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $title = 'General Settings';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'General Settings';
    protected string $view = 'filament.pages.manage-settings';

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->can('manage_settings') || auth()->user()->can('view_settings'));
    }

    public ?array $data = [];

    public static function ensureColumnsExist(): void
    {
        if (DbSchema::hasTable('settings')) {
            if (!DbSchema::hasColumn('settings', 'dark_mode_enabled')) {
                DbSchema::table('settings', function (Blueprint $table) {
                    $table->boolean('dark_mode_enabled')->default(true)->nullable();
                });
            }
            if (!DbSchema::hasColumn('settings', 'dark_mode_theme')) {
                DbSchema::table('settings', function (Blueprint $table) {
                    $table->string('dark_mode_theme')->default('dark_navy')->nullable();
                });
            }
        }
    }

    public function mount(): void
    {
        self::ensureColumnsExist();
        $setting = Setting::first();
        if ($setting) {
            $this->form->fill($setting->toArray());
            if (!empty($setting->dark_mode_theme)) {
                $dbTheme = $setting->dark_mode_theme;
                $this->js("
                    localStorage.setItem('esa_dark_theme', '{$dbTheme}');
                    document.documentElement.setAttribute('data-dark-theme', '{$dbTheme}');
                    if (document.body) document.body.setAttribute('data-dark-theme', '{$dbTheme}');
                ");
            }
        } else {
            $this->form->fill();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('clear_today_checkins')
                ->label('Clear Today Check-ins')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Clear Today Check-ins')
                ->modalDescription('Apakah Anda yakin ingin menghapus SELURUH data absensi (check-in/out) hari ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus Data')
                ->action(function () {
                    $today = \Carbon\Carbon::today()->toDateString();
                    
                    \App\Models\Attendance::where('attendance_date', $today)->delete();
                    \App\Models\AttendanceLog::whereDate('logged_at', $today)->delete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Data Terhapus')
                        ->body('Semua data absensi dan log hari ini berhasil dibersihkan.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Application Settings')
                    ->components([
                        TextInput::make('app_name')
                            ->label('Application Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('mobile_app_url')
                            ->label('Mobile App URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('mobile_app_version')
                            ->label('Mobile App Version')
                            ->placeholder('e.g. 1.0.0')
                            ->maxLength(20),
                        \Filament\Forms\Components\Toggle::make('is_force_update')
                            ->label('Force Update?')
                            ->default(false),
                        ColorPicker::make('theme_color')
                            ->label('Primary Theme Color')
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Application Logo')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->visibility('public'),
                        TextInput::make('tracking_distance_meters')
                            ->label('Tracking Distance Filter (Meter)')
                            ->numeric()
                            ->default(10)
                            ->required(),
                        TextInput::make('tracking_interval_minutes')
                            ->label('Tracking Interval (Fallback - Menit)')
                            ->numeric()
                            ->default(5)
                            ->required(),
                    ])->columns(2),
                Section::make('Tampilan & Tema Mode Gelap (Dark Mode Customization)')
                    ->description('Sesuaikan pengaturan mode gelap dan pilihan variasi warna latar belakang admin panel.')
                    ->components([
                        \Filament\Forms\Components\Toggle::make('dark_mode_enabled')
                            ->label('Aktifkan Dukungan Mode Gelap (Dark Mode)')
                            ->helperText('Jika diaktifkan, tombol penggantian Light/Dark mode akan aktif pada header.')
                            ->default(true),
                        \Filament\Forms\Components\Select::make('dark_mode_theme')
                            ->label('Pilihan Variasi Warna Dark Mode')
                            ->options([
                                'dark_navy'    => '🌌 Dark Navy (Midnight Blue - Default Elegan)',
                                'pitch_black'  => '⬛ Pitch Black (Pure AMOLED Black / Hitam Pekat)',
                                'dark_grey'    => '🔘 Dark Grey (Charcoal / Abu-Abu Gelap Modern)',
                                'dark_emerald' => '🌲 Dark Emerald (Deep Forest / Hijau Gelap Mewah)',
                                'dark_purple'  => '🔮 Dark Purple (Royal Amethyst / Ungu Gelap)',
                            ])
                            ->default('dark_navy')
                            ->required()
                            ->helperText('Pilih nuansa warna gelap yang ingin diterapkan secara default pada panel admin saat mode gelap aktif.'),
                    ])->columns(2),
                Section::make('Pengaturan Foto Wajib')
                    ->components([
                        \Filament\Forms\Components\Toggle::make('require_checkin_photo')
                            ->label('Check-In Photo (Mandatory)')
                            ->default(true),
                        \Filament\Forms\Components\Toggle::make('require_checkout_photo')
                            ->label('Check-Out Photo (Mandatory)')
                            ->default(true),
                        \Filament\Forms\Components\Toggle::make('require_visit_photo')
                            ->label('Visit Photo (Mandatory)')
                            ->default(true),
                    ])->columns(3),
                Section::make('Konfigurasi Principle & Jarak')
                    ->components([
                        \Filament\Forms\Components\Toggle::make('use_roster_principle')
                            ->label('Gunakan Roster Principle')
                            ->default(false),
                        \Filament\Forms\Components\Toggle::make('lock_roster')
                            ->label('Lock Roster (Wajib Punya Plan Check-In)')
                            ->default(true),
                        TextInput::make('global_distance_lock')
                            ->label('Distance Lock Global (Radius Meter)')
                            ->numeric()
                            ->default(50)
                            ->required(),
                    ])->columns(3),
                Section::make('Pusat Bantuan & Kebijakan Privasi (Helpdesk & Policy)')
                    ->description('Pengaturan kontak layanan bantuan HR / IT Helpdesk dan link kebijakan privasi untuk aplikasi mobile.')
                    ->components([
                        TextInput::make('help_phone')
                            ->label('Nomor Telepon Helpdesk')
                            ->tel()
                            ->placeholder('e.g. 021-12345678 / 081234567890'),
                        TextInput::make('help_whatsapp')
                            ->label('Nomor WhatsApp Helpdesk (Format: 628xxx)')
                            ->placeholder('e.g. 6281234567890'),
                        TextInput::make('help_email')
                            ->label('Email Layanan Bantuan / Support')
                            ->email()
                            ->placeholder('support@company.com'),
                        TextInput::make('help_hours')
                            ->label('Jam Operasional Layanan')
                            ->placeholder('e.g. Senin - Jumat, 08:00 - 17:00 WIB'),
                        TextInput::make('privacy_policy_url')
                            ->label('Link Kebijakan Privasi Kustom (Opsional)')
                            ->url()
                            ->placeholder('https://company.com/privacy-policy'),
                    ])->columns(2),
                Section::make('SMTP / Email Settings')
                    ->components([
                        TextInput::make('smtp_host')->label('SMTP Host')->placeholder('smtp.mailtrap.io'),
                        TextInput::make('smtp_port')->label('SMTP Port')->placeholder('2525')->numeric(),
                        TextInput::make('smtp_username')->label('SMTP Username'),
                        TextInput::make('smtp_password')->label('SMTP Password')->password(),
                        \Filament\Forms\Components\Select::make('smtp_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ]),
                        TextInput::make('mail_from_address')->label('From Address')->email()->placeholder('noreply@example.com'),
                        TextInput::make('mail_from_name')->label('From Name')->placeholder('My App'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        self::ensureColumnsExist();
        $data = $this->form->getState();

        $setting = Setting::first();
        $oldVersion = $setting ? $setting->mobile_app_version : null;

        if ($setting) {
            $setting->update($data);
        } else {
            $setting = Setting::create($data);
        }

        \Illuminate\Support\Facades\Cache::forget('public_app_system_setting_array_v2');
        \Illuminate\Support\Facades\Cache::forget('global_landing_stats_active_v3');

        if (!empty($setting->mobile_app_version) && ($oldVersion !== $setting->mobile_app_version || !empty($setting->is_force_update))) {
            $tokens = \App\Models\Employee::whereNotNull('fcm_token')->where('is_active', true)->pluck('fcm_token')->toArray();
            $tokens = array_unique(array_filter($tokens));
            if (!empty($tokens)) {
                $firebase = new \App\Services\FirebaseService();
                $firebase->sendNotification(
                    $tokens,
                    'Update Aplikasi Tersedia',
                    "Versi {$setting->mobile_app_version} telah dirilis. Silakan update aplikasi Anda untuk kelancaran absensi.",
                    [
                        'type' => 'app_update',
                        'version' => (string) $setting->mobile_app_version,
                        'url' => (string) ($setting->mobile_app_url ?? 'https://appsend.my.id/app-release.apk'),
                        'is_force' => $setting->is_force_update ? '1' : '0',
                    ]
                );
            }
        }

        $newTheme = $data['dark_mode_theme'] ?? 'dark_navy';
        $this->js("
            localStorage.setItem('esa_dark_theme', '{$newTheme}');
            document.documentElement.setAttribute('data-dark-theme', '{$newTheme}');
            if (document.body) document.body.setAttribute('data-dark-theme', '{$newTheme}');
            window.dispatchEvent(new CustomEvent('esa-theme-changed', { detail: { theme: '{$newTheme}' } }));
        ");

        // Auto-sync setting ke server ESA lainnya (Peer Servers)
        $syncedMessage = '';
        try {
            $syncService = app(\App\Services\SettingSyncService::class);
            $syncResults = $syncService->syncToPeers($data);

            $syncedServers = [];
            foreach ($syncResults as $res) {
                if (!empty($res['success'])) {
                    $syncedServers[] = $res['name'];
                }
            }

            if (!empty($syncedServers)) {
                $syncedMessage = ' & otomatis tersinkron ke ' . implode(', ', $syncedServers);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Setting peer sync failed: ' . $e->getMessage());
        }

        Notification::make()
            ->success()
            ->title('Settings saved successfully' . $syncedMessage . '.')
            ->send();
            
        // Reload page to reflect new theme
        $this->redirect(ManageSettings::getUrl());
    }
}
