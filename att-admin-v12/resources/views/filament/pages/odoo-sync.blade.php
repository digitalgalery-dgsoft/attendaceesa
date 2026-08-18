<x-filament-panels::page>
    {{-- Header status konfigurasi --}}
    <div class="mb-6">
        @if ($this->isConfigured())
            <div class="flex items-center gap-2 px-4 py-3 rounded-lg bg-success-50 dark:bg-success-950 border border-success-300 dark:border-success-700 text-success-700 dark:text-success-300">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="text-sm font-medium">Odoo terhubung dan aktif. Gunakan tombol di bawah untuk sinkronisasi data.</span>
            </div>
        @else
            <div class="flex items-center gap-2 px-4 py-3 rounded-lg bg-warning-50 dark:bg-warning-950 border border-warning-300 dark:border-warning-700 text-warning-700 dark:text-warning-300">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span class="text-sm font-medium">
                    Konfigurasi Odoo untuk Company ini belum lengkap.
                    <a href="{{ route('filament.admin.resources.companies.index') }}" class="underline font-bold">Buka Master Companies</a> untuk mengatur.
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
                    <div class="p-2 rounded-lg bg-primary-100 dark:bg-primary-900 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-primary-600 dark:text-primary-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        wire:loading.class="animate-spin" wire:target="syncPrincipals">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span wire:loading.remove wire:target="syncPrincipals">Sync Principals</span>
                    <span wire:loading wire:target="syncPrincipals">Syncing...</span>
                </button>
            </div>

            {{-- Sync Employee --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-success-100 dark:bg-success-900 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-success-600 dark:text-success-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        wire:loading.class="animate-spin" wire:target="syncEmployees">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span wire:loading.remove wire:target="syncEmployees">Sync Employees</span>
                    <span wire:loading wire:target="syncEmployees">Syncing...</span>
                </button>
            </div>

            {{-- Sync All --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-warning-100 dark:bg-warning-900 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-warning-600 dark:text-warning-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                        </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                        wire:loading.class="animate-spin" wire:target="syncAll">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
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
