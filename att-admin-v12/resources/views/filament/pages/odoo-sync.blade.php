<x-filament-panels::page>
    {{-- Header status konfigurasi --}}
    <div class="mb-6">
        @if ($this->isConfigured())
            <div class="flex items-center gap-2 px-4 py-3 rounded-lg bg-success-50 dark:bg-success-950 border border-success-300 dark:border-success-700 text-success-700 dark:text-success-300">
                <x-heroicon-o-check-circle class="w-5 h-5 flex-shrink-0" />
                <span class="text-sm font-medium">Odoo terhubung dan aktif. Gunakan tombol di bawah untuk sinkronisasi data.</span>
            </div>
        @else
            <div class="flex items-center gap-2 px-4 py-3 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-300 dark:border-warning-700 text-warning-700 dark:text-warning-300">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 flex-shrink-0" />
                <span class="text-sm font-medium">
                    Konfigurasi Odoo belum lengkap atau sync belum diaktifkan.
                    <a href="{{ route('filament.admin.pages.manage-settings') }}" class="underline font-bold">Buka General Settings</a> untuk mengatur.
                </span>
            </div>
        @endif
    </div>

    @if ($this->isConfigured())
        {{-- Company Selector --}}
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Pilih Company Tujuan Sync</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Data dari Odoo akan disinkronisasi ke company yang dipilih di bawah ini.</p>
            <div class="max-w-xs">
                <select
                    wire:model.live="selectedCompanyId"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">-- Pilih Company --</option>
                    @foreach ($this->getCompanyOptions() as $id => $name)
                        <option value="{{ $id }}" @selected($this->selectedCompanyId == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Sync Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Sync Principal --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-primary-100 dark:bg-primary-900">
                        <x-heroicon-o-building-office-2 class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Sync Principals</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Dari Odoo Contact (is_principal = True)</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 flex-1">
                    Mengambil data kontak yang ditandai sebagai <strong>Prinsiple</strong> dari Odoo dan menyinkronisasinya ke master data Principal.
                </p>
                <button
                    wire:click="syncPrincipals"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition disabled:opacity-60"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" wire:target="syncPrincipals" />
                    <span wire:loading.remove wire:target="syncPrincipals">Sync Principals</span>
                    <span wire:loading wire:target="syncPrincipals">Syncing...</span>
                </button>
            </div>

            {{-- Sync Employee --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-success-100 dark:bg-success-900">
                        <x-heroicon-o-users class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Sync Employees</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Dari Odoo hr.employee (active = True)</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 flex-1">
                    Mengambil data karyawan aktif dari Odoo. Department & Position yang belum ada akan <strong>otomatis dibuat</strong>.
                </p>
                <button
                    wire:click="syncEmployees"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-success-600 hover:bg-success-700 text-white text-sm font-medium transition disabled:opacity-60"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" wire:target="syncEmployees" />
                    <span wire:loading.remove wire:target="syncEmployees">Sync Employees</span>
                    <span wire:loading wire:target="syncEmployees">Syncing...</span>
                </button>
            </div>

            {{-- Sync All --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-warning-100 dark:bg-warning-900">
                        <x-heroicon-o-bolt class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Sync All</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Principal + Employee sekaligus</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 flex-1">
                    Jalankan sinkronisasi lengkap: <strong>Principal</strong> terlebih dahulu, lalu <strong>Employee</strong> — dalam satu proses.
                </p>
                <button
                    wire:click="syncAll"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-warning-600 hover:bg-warning-700 text-white text-sm font-medium transition disabled:opacity-60"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4" wire:loading.class="animate-spin" wire:target="syncAll" />
                    <span wire:loading.remove wire:target="syncAll">Sync All</span>
                    <span wire:loading wire:target="syncAll">Syncing...</span>
                </button>
            </div>

        </div>

        {{-- Info Notes --}}
        <div class="mt-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">📌 Catatan Sinkronisasi</h4>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                <li>Data yang sudah ada akan <strong>diperbarui</strong>, bukan digandakan (berdasarkan Odoo ID)</li>
                <li>Department & Position yang belum ada di master akan <strong>otomatis dibuat</strong></li>
                <li>Karyawan yang tidak aktif di Odoo akan <strong>dinonaktifkan</strong> di sistem ini</li>
                <li>Gunakan tombol <strong>Test Connection</strong> di atas untuk memverifikasi koneksi Odoo</li>
            </ul>
        </div>
    @endif
</x-filament-panels::page>
