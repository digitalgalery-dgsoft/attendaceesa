<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;

class AiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $title = 'AI Configuration';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 101;
    protected string $view = 'filament.pages.ai-settings';

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
                Section::make('Gemini API (Primary)')
                    ->description('Konfigurasi utama AI menggunakan API Gemini.')
                    ->components([
                        Textarea::make('gemini_api_keys')
                            ->label('Gemini API Key(s)')
                            ->helperText('Masukkan API Key. Tiap baris merepresentasikan 1 Key. Sistem otomatis memutar (rotate) ke key berikutnya jika terkena limit.')
                            ->rows(5),
                        Select::make('gemini_model')
                            ->label('Gemini Model')
                            ->options([
                                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast)',
                                'gemini-1.5-pro' => 'Gemini 1.5 Pro (Accurate)',
                                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                            ])
                            ->default('gemini-1.5-flash'),
                    ])->columns(1),

                Section::make('Backup API (Sumopod / Fallback)')
                    ->description('Digunakan jika semua API Gemini terkena limit.')
                    ->components([
                        TextInput::make('sumopod_api_key')
                            ->label('Sumopod API Key')
                            ->password(),
                        TextInput::make('sumopod_model')
                            ->label('Sumopod Model')
                            ->placeholder('e.g. meta-llama/Llama-3-8b-chat-hf'),
                    ])->columns(1),
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
            ->title('AI Settings saved successfully.')
            ->send();
            
        $this->redirect(AiSettings::getUrl());
    }
}
