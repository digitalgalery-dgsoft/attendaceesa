<x-filament-panels::page>
    {{-- Header status konfigurasi --}}
    <div class="mb-2">
        @if ($this->isConfigured())
            <x-filament::section class="bg-success-50 dark:bg-success-900/30 border-success-200 dark:border-success-800">
                <div class="flex items-center gap-3 text-success-700 dark:text-success-400">
                    <x-filament::icon icon="heroicon-m-check-circle" class="h-6 w-6" />
                    <span class="font-medium">Odoo terhubung dan siap. Gunakan tombol di bawah untuk sinkronisasi data.</span>
                </div>
            </x-filament::section>
        @else
            <x-filament::section class="bg-warning-50 dark:bg-warning-900/30 border-warning-200 dark:border-warning-800">
                <div class="flex items-center gap-3 text-warning-700 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-6 w-6" />
                    <span class="font-medium flex-1">Konfigurasi Odoo untuk Company ini belum lengkap atau belum dipilih.</span>
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.companies.index') }}" color="warning" size="sm">
                        Buka Master Companies
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>

    {{-- Company Selector --}}
    <x-filament::section title="Pilih Company Tujuan Sync" description="Data dari Odoo akan disinkronisasi ke company yang dipilih di bawah ini.">
        <div class="max-w-md">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="selectedCompanyId" class="w-full">
                    <option value="">-- Pilih Company --</option>
                    @foreach ($this->getCompanyOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    @if ($this->isConfigured())
        {{-- Sync Cards --}}
        <x-filament::grid default="1" md="3" gap="6">
            
            {{-- Sync Principal --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                        <x-filament::icon icon="heroicon-m-building-office-2" class="h-6 w-6" />
                        <span>Sync Principals</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Dari Odoo Contact (is_principal = True)
                </x-slot>
                
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 mt-2">
                    Mengambil data kontak yang ditandai sebagai <strong>Prinsiple</strong> dari Odoo dan menyinkronisasinya ke master data Principal.
                </p>
                
                <x-filament::button wire:click="syncPrincipals" wire:target="syncPrincipals" color="primary" class="w-full" size="lg">
                    <span wire:loading.remove wire:target="syncPrincipals">Mulai Sync Principals</span>
                    <span wire:loading wire:target="syncPrincipals">Proses Sinkronisasi...</span>
                </x-filament::button>
            </x-filament::section>

            {{-- Sync Employee --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
                        <x-filament::icon icon="heroicon-m-users" class="h-6 w-6" />
                        <span>Sync Employees</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Dari Odoo hr.employee (active = True)
                </x-slot>
                
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 mt-2">
                    Mengambil data karyawan aktif dari Odoo. Department & Position yang belum ada akan <strong>otomatis dibuat</strong>.
                </p>
                
                <x-filament::button wire:click="syncEmployees" wire:target="syncEmployees" color="success" class="w-full" size="lg">
                    <span wire:loading.remove wire:target="syncEmployees">Mulai Sync Employees</span>
                    <span wire:loading wire:target="syncEmployees">Proses Sinkronisasi...</span>
                </x-filament::button>
            </x-filament::section>

            {{-- Sync All --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-warning-600 dark:text-warning-400">
                        <x-filament::icon icon="heroicon-m-bolt" class="h-6 w-6" />
                        <span>Sync All</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Principal + Employee sekaligus
                </x-slot>
                
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 mt-2">
                    Jalankan sinkronisasi lengkap: <strong>Principal</strong> terlebih dahulu, lalu <strong>Employee</strong> — dalam satu proses.
                </p>
                
                <x-filament::button wire:click="syncAll" wire:target="syncAll" color="warning" class="w-full" size="lg">
                    <span wire:loading.remove wire:target="syncAll">Mulai Sync All</span>
                    <span wire:loading wire:target="syncAll">Proses Sinkronisasi...</span>
                </x-filament::button>
            </x-filament::section>

        </x-filament::grid>

        {{-- Info Notes --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-information-circle" class="h-5 w-5 text-gray-500" />
                    Catatan Sinkronisasi
                </div>
            </x-slot>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 list-disc list-inside ml-2">
                <li>Proses memakan waktu tergantung jumlah data (rata-rata sinkronisasi memproses ratusan data per detik). Harap tunggu hingga notifikasi muncul.</li>
                <li>Data yang sudah ada akan <strong>diperbarui</strong>, bukan digandakan (berdasarkan Odoo ID).</li>
                <li>Department & Position yang belum ada di master akan <strong>otomatis dibuat</strong>.</li>
                <li>Karyawan yang tidak aktif di Odoo akan <strong>dinonaktifkan</strong> di sistem ini.</li>
                <li>Gunakan tombol <strong>Test Connection</strong> di atas untuk memverifikasi koneksi Odoo.</li>
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
