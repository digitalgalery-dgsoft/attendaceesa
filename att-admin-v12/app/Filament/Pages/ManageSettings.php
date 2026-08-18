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

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $title = 'General Settings';
    protected static string|\UnitEnum|null $navigationGroup = '7. System & Settings';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = Setting::first();
        if ($setting) {
            $this->form->fill($setting->toArray());
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
                            ->directory('logos'),
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
        $data = $this->form->getState();

        $setting = Setting::first();
        $oldVersion = $setting ? $setting->mobile_app_version : null;

        if ($setting) {
            $setting->update($data);
        } else {
            $setting = Setting::create($data);
        }

        if ($oldVersion !== $setting->mobile_app_version && !empty($setting->mobile_app_version)) {
            $tokens = \App\Models\Employee::whereNotNull('fcm_token')->where('is_active', true)->pluck('fcm_token')->toArray();
            $tokens = array_unique($tokens);
            if (!empty($tokens)) {
                $firebase = new \App\Services\FirebaseService();
                $firebase->sendNotification($tokens, 'Update Aplikasi Tersedia', "Versi {$setting->mobile_app_version} telah dirilis. Silakan update aplikasi Anda.");
            }
        }

        Notification::make()
            ->success()
            ->title('Settings saved successfully.')
            ->send();
            
        // Reload page to reflect new theme
        $this->redirect(ManageSettings::getUrl());
    }
}
