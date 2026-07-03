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
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;
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
                        ColorPicker::make('theme_color')
                            ->label('Primary Theme Color')
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Application Logo')
                            ->image()
                            ->directory('logos'),
                    ])->columns(1),
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $setting = Setting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            Setting::create($data);
        }

        Notification::make()
            ->success()
            ->title('Settings saved successfully.')
            ->send();
            
        // Reload page to reflect new theme
        $this->redirect(ManageSettings::getUrl());
    }
}
