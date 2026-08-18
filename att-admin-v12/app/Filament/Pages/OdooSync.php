<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Setting;
use App\Services\OdooSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class OdooSync extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $title = 'Odoo Sync';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Odoo Sync';
    protected string $view = 'filament.pages.odoo-sync';

    public ?int $selectedCompanyId = null;

    public function mount(): void
    {
        $this->selectedCompanyId = Company::where('is_active', true)->value('id');
    }

    public function getCompany(): ?Company
    {
        if (!$this->selectedCompanyId) return null;
        return Company::find($this->selectedCompanyId);
    }

    public function isConfigured(): bool
    {
        $c = $this->getCompany();
        return $c && $c->odoo_url && $c->odoo_db && $c->odoo_username && $c->odoo_api_key;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    try {
                        $company = Company::find($this->selectedCompanyId);
                        if (!$company) {
                            throw new \Exception("Company tidak ditemukan.");
                        }
                        
                        $service = OdooSyncService::fromCompany($company);
                        if (!$service) {
                            throw new \Exception("Konfigurasi Odoo untuk perusahaan ini belum lengkap.");
                        }

                        $result = $service->testConnection();
                        Notification::make()
                            ->title('Koneksi Berhasil')
                            ->body("Terhubung ke Odoo v{$result['server_version']} sebagai UID {$result['uid']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Koneksi Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function syncPrincipals(): void
    {
        if (!$this->selectedCompanyId) {
            Notification::make()->title('Pilih Company terlebih dahulu')->warning()->send();
            return;
        }
        try {
            $company = Company::find($this->selectedCompanyId);
            $service = OdooSyncService::fromCompany($company);
            if (!$service) {
                throw new \Exception("Konfigurasi Odoo untuk perusahaan ini belum lengkap.");
            }
            
            $result = $service->syncPrincipals($this->selectedCompanyId);
            $body   = "✅ Baru: {$result['created']} | 🔄 Diperbarui: {$result['updated']}";
            if (!empty($result['errors'])) {
                $body .= "\n⚠️ Error: " . implode('; ', array_slice($result['errors'], 0, 3));
            }
            Notification::make()
                ->title('Sync Principal Selesai')
                ->body($body)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()->title('Sync Principal Gagal')->body($e->getMessage())->danger()->send();
        }
    }

    public function syncEmployees(): void
    {
        if (!$this->selectedCompanyId) {
            Notification::make()->title('Pilih Company terlebih dahulu')->warning()->send();
            return;
        }
        try {
            $company = Company::find($this->selectedCompanyId);
            $service = OdooSyncService::fromCompany($company);
            if (!$service) {
                throw new \Exception("Konfigurasi Odoo untuk perusahaan ini belum lengkap.");
            }
            
            $result = $service->syncEmployees($this->selectedCompanyId);
            $body   = "✅ Baru: {$result['created']} | 🔄 Diperbarui: {$result['updated']}";
            if (!empty($result['errors'])) {
                $body .= "\n⚠️ Error: " . implode('; ', array_slice($result['errors'], 0, 3));
            }
            Notification::make()
                ->title('Sync Employee Selesai')
                ->body($body)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()->title('Sync Employee Gagal')->body($e->getMessage())->danger()->send();
        }
    }

    public function syncAll(): void
    {
        if (!$this->selectedCompanyId) {
            Notification::make()->title('Pilih Company terlebih dahulu')->warning()->send();
            return;
        }
        try {
            $company = Company::find($this->selectedCompanyId);
            $service = OdooSyncService::fromCompany($company);
            if (!$service) {
                throw new \Exception("Konfigurasi Odoo untuk perusahaan ini belum lengkap.");
            }
            $p = $service->syncPrincipals($this->selectedCompanyId);
            $e = $service->syncEmployees($this->selectedCompanyId);

            $allErrors = array_merge($p['errors'] ?? [], $e['errors'] ?? []);
            $body = "📦 Principal — Baru: {$p['created']}, Update: {$p['updated']}\n"
                  . "👤 Employee — Baru: {$e['created']}, Update: {$e['updated']}";
            if ($allErrors) {
                $body .= "\n⚠️ " . count($allErrors) . " error(s)";
            }

            Notification::make()
                ->title('Sync All Selesai')
                ->body($body)
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()->title('Sync Gagal')->body($e->getMessage())->danger()->send();
        }
    }

    public function getCompanyOptions(): array
    {
        return Company::where('is_active', true)->pluck('name', 'id')->toArray();
    }
}
