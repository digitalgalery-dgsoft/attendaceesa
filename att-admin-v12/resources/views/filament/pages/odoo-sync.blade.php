<x-filament-panels::page>
    <div x-data="odooSyncEngine()" class="space-y-6">

        {{-- Status Konfigurasi & Quick Header --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                @if ($this->isConfigured())
                    <div class="flex items-center justify-between p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-500/20 rounded-lg">
                                <x-filament::icon icon="heroicon-m-check-badge" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <h4 class="font-bold text-sm">Odoo ERP Terhubung & Siap</h4>
                                <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80 mt-0.5">
                                    DB: <code class="font-mono px-1 py-0.5 bg-emerald-500/10 rounded">{{ $this->getCompany()?->odoo_db }}</code> · Host: <span class="font-mono">{{ $this->getCompany()?->odoo_url }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="startSync('test_connection')" :disabled="isRunning" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition disabled:opacity-50">
                            Test Ping
                        </button>
                    </div>
                @else
                    <div class="flex items-center justify-between p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-amber-500/20 rounded-lg">
                                <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h4 class="font-bold text-sm">Konfigurasi Odoo Belum Lengkap</h4>
                                <p class="text-xs text-amber-600/80 dark:text-amber-400/80 mt-0.5">
                                    URL, Database, Username, atau API Key belum diisi untuk entitas ini.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('filament.admin.resources.companies.index') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition">
                            Master Perusahaan
                        </a>
                    </div>
                @endif
            </div>

            {{-- Pembersih Duplikat NIK & Link Laporan --}}
            <div class="flex items-center gap-2">
                <button type="button" @click="startSync('cleanup_duplicates')" :disabled="isRunning" class="flex-1 flex items-center justify-center gap-2 p-4 rounded-xl bg-violet-500/10 hover:bg-violet-500/20 border border-violet-500/20 text-violet-700 dark:text-violet-300 transition disabled:opacity-50 text-left">
                    <x-filament::icon icon="heroicon-m-sparkles" class="h-5 w-5 text-violet-600 dark:text-violet-400 shrink-0" />
                    <div>
                        <div class="text-xs font-bold">Bersihkan Duplikat NIK</div>
                        <div class="text-[10px] text-gray-500 dark:text-gray-400">Gabungkan riwayat ganda</div>
                    </div>
                </button>
                <a href="{{ route('filament.admin.pages.odoo-sync-report') }}" class="flex items-center justify-center p-4 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/20 text-cyan-700 dark:text-cyan-300 transition text-center" title="Buka Laporan Sinkronisasi">
                    <x-filament::icon icon="heroicon-m-document-chart-bar" class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                </a>
            </div>
        </div>

        {{-- Company Selector & Mode Sync --}}
        <x-filament::section>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="w-full md:w-1/2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                        Pilih Perusahaan / Entitas Target
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedCompanyId" x-ref="companySelect" class="w-full font-medium" :disabled="isRunning">
                            @foreach ($this->getCompanyOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="w-full md:w-auto flex items-center gap-3 pt-2 md:pt-5">
                    <button type="button" 
                            @click="startSync('all_companies')" 
                            :disabled="isRunning" 
                            class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition transform active:scale-95 disabled:opacity-50">
                        <x-filament::icon icon="heroicon-m-bolt" class="h-5 w-5" />
                        <span>Sync Semua Perusahaan Sekaligus</span>
                    </button>
                </div>
            </div>
        </x-filament::section>

        {{-- Action Cards (3 Tombol Utama) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Sync Principal --}}
            <x-filament::section class="relative overflow-hidden border-t-4 border-t-primary-500">
                <div class="flex items-center gap-2.5 text-primary-600 dark:text-primary-400 mb-2">
                    <div class="p-2 rounded-lg bg-primary-500/10">
                        <x-filament::icon icon="heroicon-m-building-office-2" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Sync Principals</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Odoo Contact (is_principal = True)</p>
                    </div>
                </div>
                
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-5 min-h-[36px]">
                    Menarik dan memperbarui master data <strong>Prinsiple / Brand</strong> dari kontak Odoo ke database lokal.
                </p>
                
                <button type="button" 
                        @click="startSync('principals')" 
                        :disabled="isRunning || !isCompanyConfigured" 
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-xs shadow transition disabled:opacity-40">
                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" ::class="isRunning && currentAction === 'principals' ? 'animate-spin' : ''" />
                    <span>Mulai Sync Principals</span>
                </button>
            </x-filament::section>

            {{-- Sync Employee --}}
            <x-filament::section class="relative overflow-hidden border-t-4 border-t-emerald-500">
                <div class="flex items-center gap-2.5 text-emerald-600 dark:text-emerald-400 mb-2">
                    <div class="p-2 rounded-lg bg-emerald-500/10">
                        <x-filament::icon icon="heroicon-m-users" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Sync Employees</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Odoo hr.employee</p>
                    </div>
                </div>
                
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-5 min-h-[36px]">
                    Sinkronisasi data karyawan aktif & resign, jabatan, area, serta auto-create department.
                </p>
                
                <button type="button" 
                        @click="startSync('employees')" 
                        :disabled="isRunning || !isCompanyConfigured" 
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow transition disabled:opacity-40">
                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" ::class="isRunning && currentAction === 'employees' ? 'animate-spin' : ''" />
                    <span>Mulai Sync Employees</span>
                </button>
            </x-filament::section>

            {{-- Sync All --}}
            <x-filament::section class="relative overflow-hidden border-t-4 border-t-amber-500">
                <div class="flex items-center gap-2.5 text-amber-600 dark:text-amber-400 mb-2">
                    <div class="p-2 rounded-lg bg-amber-500/10">
                        <x-filament::icon icon="heroicon-m-bolt" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Sync All</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Principal + Employee</p>
                    </div>
                </div>
                
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-5 min-h-[36px]">
                    Jalankan sinkronisasi lengkap: <strong>Principal</strong> terlebih dahulu, lalu <strong>Employee</strong> secara berurutan.
                </p>
                
                <button type="button" 
                        @click="startSync('all')" 
                        :disabled="isRunning || !isCompanyConfigured" 
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow transition disabled:opacity-40">
                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" ::class="isRunning && currentAction === 'all' ? 'animate-spin' : ''" />
                    <span>Mulai Sync All</span>
                </button>
            </x-filament::section>
        </div>

        {{-- JENDELA TERMINAL REALTIME STREAMING (SEPERTI DI TERMINAL LINUX) --}}
        <div id="odoo-terminal-container" class="rounded-2xl bg-[#090d16] border border-gray-800 shadow-2xl overflow-hidden transition-all duration-300">
            
            {{-- Terminal Top Bar (macOS / Linux Style) --}}
            <div class="flex items-center justify-between px-4 py-3 bg-[#111827] border-b border-gray-800 select-none">
                <div class="flex items-center gap-3">
                    {{-- Window Dots --}}
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-rose-500 inline-block shadow-sm"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500 inline-block shadow-sm"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block shadow-sm"></span>
                    </div>
                    {{-- Terminal Title --}}
                    <div class="flex items-center gap-2 text-xs font-mono text-gray-300 font-semibold pl-2 border-l border-gray-700">
                        <x-filament::icon icon="heroicon-m-command-line" class="h-4 w-4 text-cyan-400" />
                        <span>esa@appsend: ~/odoo-sync-terminal</span>
                    </div>
                </div>

                {{-- Status Badge & Controls --}}
                <div class="flex items-center gap-2.5">
                    {{-- Running Status Pill --}}
                    <template x-if="isRunning">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span x-text="statusText || 'RUNNING...'"></span>
                        </span>
                    </template>
                    <template x-if="!isRunning && isFinished">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                            <span>COMPLETED</span>
                        </span>
                    </template>
                    <template x-if="!isRunning && !isFinished">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono text-gray-400 bg-gray-800/80 border border-gray-700">
                            <span class="w-2 h-2 rounded-full bg-gray-500"></span>
                            <span>STANDBY</span>
                        </span>
                    </template>

                    {{-- Toolbar buttons --}}
                    <div class="flex items-center gap-1 pl-2 border-l border-gray-700">
                        <button type="button" @click="toggleAutoScroll()" :title="autoScroll ? 'Auto Scroll Aktif' : 'Auto Scroll Mati'" class="p-1.5 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition" :class="autoScroll ? 'text-cyan-400' : ''">
                            <x-filament::icon icon="heroicon-m-arrows-up-down" class="h-4 w-4" />
                        </button>
                        <button type="button" @click="copyLogs()" title="Salin Seluruh Log" class="p-1.5 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition">
                            <x-filament::icon icon="heroicon-m-clipboard-document" class="h-4 w-4" />
                        </button>
                        <button type="button" @click="clearLogs()" title="Bersihkan Layar Terminal" class="p-1.5 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition">
                            <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                        </button>
                        <template x-if="isRunning">
                            <button type="button" @click="stopSync()" title="Hentikan Proses" class="px-2 py-1 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white font-mono text-[10px] font-bold transition">
                                STOP
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Live Metric Counter Header --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 px-4 py-2.5 bg-[#0b1322] border-b border-gray-800/80 text-xs font-mono">
                <div class="flex items-center gap-2 text-gray-300">
                    <span class="text-gray-500">DIPROSES:</span>
                    <span class="font-bold text-cyan-400" x-text="metrics.processed">0</span>
                </div>
                <div class="flex items-center gap-2 text-gray-300">
                    <span class="text-gray-500">BARU:</span>
                    <span class="font-bold text-emerald-400" x-text="metrics.created">0</span>
                </div>
                <div class="flex items-center gap-2 text-gray-300">
                    <span class="text-gray-500">UPDATE:</span>
                    <span class="font-bold text-amber-400" x-text="metrics.updated">0</span>
                </div>
                <div class="flex items-center gap-2 text-gray-300">
                    <span class="text-gray-500">RESIGN:</span>
                    <span class="font-bold text-rose-400" x-text="metrics.resigned">0</span>
                </div>
                <div class="flex items-center gap-2 text-gray-300">
                    <span class="text-gray-500">ERROR:</span>
                    <span class="font-bold text-red-500" x-text="metrics.errors">0</span>
                </div>
            </div>

            {{-- Progress Line Indicator --}}
            <div class="w-full bg-gray-900 h-1 relative overflow-hidden">
                <div class="h-full bg-gradient-to-r from-cyan-500 via-emerald-400 to-amber-400 transition-all duration-300" :style="`width: ${progressPercent}%`"></div>
                <template x-if="isRunning">
                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                </template>
            </div>

            {{-- Terminal Output Log Box --}}
            <div x-ref="terminalBox" class="p-4 font-mono text-[12px] leading-relaxed text-gray-300 min-h-[380px] max-h-[520px] overflow-y-auto space-y-1 select-text scroll-smooth" style="scrollbar-width: thin; scrollbar-color: #374151 #090d16;">
                
                {{-- Welcome / Standby message --}}
                <div class="text-gray-600 dark:text-gray-500 select-none pb-2 border-b border-gray-800/60 mb-2">
                    <div>ESA Odoo Sync Engine v2.0 [Real-time Stream Edition]</div>
                    <div>Klik tombol di atas untuk memulai sinkronisasi. Log aktivitas tiap entitas & batch akan tampil di bawah ini secara langsung tanpa jeda atau timeout.</div>
                </div>

                {{-- Dynamic Stream Logs --}}
                <template x-for="(log, idx) in logs" :key="idx">
                    <div class="flex items-start gap-2 py-0.5 group hover:bg-gray-800/30 px-1 rounded transition-colors">
                        <span class="text-gray-600 select-none shrink-0" x-text="`[${log.time}]`"></span>
                        
                        {{-- Log Type Badge / Format --}}
                        <template x-if="log.type === 'company_start'">
                            <span class="text-amber-400 font-bold tracking-wide" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'company_end'">
                            <span class="text-emerald-400 font-bold" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'batch'">
                            <span class="text-violet-400 font-semibold" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'created'">
                            <span class="text-emerald-400" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'updated'">
                            <span class="text-cyan-300" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'resigned'">
                            <span class="text-rose-400 font-medium" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'progress'">
                            <span class="text-blue-400 font-semibold" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'summary'">
                            <span class="text-emerald-300 font-bold bg-emerald-500/10 px-1.5 py-0.5 rounded" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'error'">
                            <span class="text-rose-500 font-bold bg-rose-500/10 px-1.5 py-0.5 rounded" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'warning'">
                            <span class="text-amber-400 font-medium" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'info' || log.type === 'item'">
                            <span class="text-gray-300" x-text="log.message"></span>
                        </template>
                    </div>
                </template>

                {{-- Cursor Blinking --}}
                <div class="flex items-center gap-1 pt-1 text-cyan-400 font-bold select-none" x-show="isRunning">
                    <span class="animate-pulse">❯</span>
                    <span class="w-2 h-4 bg-cyan-400 animate-pulse"></span>
                </div>
            </div>

        </div>

    </div>

    {{-- Script AlpineJS Engine untuk Real-time SSE Terminal --}}
    <script>
        function odooSyncEngine() {
            return {
                isRunning: false,
                isFinished: false,
                currentAction: '',
                statusText: 'STANDBY',
                progressPercent: 0,
                autoScroll: true,
                eventSource: null,
                logs: [],
                metrics: {
                    processed: 0,
                    created: 0,
                    updated: 0,
                    resigned: 0,
                    errors: 0,
                },

                get isCompanyConfigured() {
                    return {{ $this->isConfigured() ? 'true' : 'false' }};
                },

                init() {
                    console.log('Odoo Sync Engine Initialized');
                },

                startSync(action) {
                    if (this.isRunning) return;

                    const companySelect = this.$refs.companySelect;
                    const companyId = action === 'all_companies' ? 'all' : (companySelect ? companySelect.value : @js($this->selectedCompanyId));

                    if (action !== 'all_companies' && action !== 'cleanup_duplicates' && !companyId) {
                        alert('Silakan pilih Company terlebih dahulu.');
                        return;
                    }

                    this.isRunning = true;
                    this.isFinished = false;
                    this.currentAction = action;
                    this.statusText = 'CONNECTING...';
                    this.progressPercent = 10;
                    this.metrics = { processed: 0, created: 0, updated: 0, resigned: 0, errors: 0 };

                    // Scroll terminal into view
                    document.getElementById('odoo-terminal-container')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                    const timeStr = new Date().toLocaleTimeString('id-ID', { hour12: false });
                    this.logs.push({
                        time: timeStr,
                        type: 'info',
                        message: `▶️ Memulai proses [${action.toUpperCase()}]... Membuka koneksi stream ke server.`
                    });

                    // Construct SSE endpoint URL
                    const url = `{{ route('admin.odoo-sync.stream') }}?company_id=${encodeURIComponent(companyId)}&action=${encodeURIComponent(action)}&_t=${Date.now()}`;

                    if (this.eventSource) {
                        this.eventSource.close();
                    }

                    this.eventSource = new EventSource(url);

                    this.eventSource.onmessage = (event) => {
                        try {
                            const data = JSON.parse(event.data);
                            this.handleStreamMessage(data);
                        } catch (e) {
                            console.error('Error parsing SSE event:', e, event.data);
                        }
                    };

                    this.eventSource.onerror = (err) => {
                        console.warn('SSE EventSource disconnected/finished:', err);
                        if (this.isRunning) {
                            const nowTime = new Date().toLocaleTimeString('id-ID', { hour12: false });
                            this.logs.push({
                                time: nowTime,
                                type: 'info',
                                message: '🏁 Koneksi stream server selesai ditutup.'
                            });
                        }
                        this.finishSync();
                    };
                },

                handleStreamMessage(data) {
                    const time = data.time || new Date().toLocaleTimeString('id-ID', { hour12: false });
                    const type = data.type || 'info';
                    const message = data.message || '';
                    const meta = data.meta || {};

                    this.logs.push({ time, type, message });

                    // Update Metrics
                    if (type === 'created') this.metrics.created++;
                    if (type === 'updated') this.metrics.updated++;
                    if (type === 'resigned') this.metrics.resigned++;
                    if (type === 'error') this.metrics.errors++;

                    if (meta.created !== undefined) this.metrics.created = meta.created;
                    if (meta.updated !== undefined) this.metrics.updated = meta.updated;
                    if (meta.resigned !== undefined) this.metrics.resigned = meta.resigned;
                    if (meta.processed !== undefined) this.metrics.processed = meta.processed;
                    if (meta.errors !== undefined) this.metrics.errors = meta.errors;

                    // Update Status text
                    if (type === 'company_start') {
                        this.statusText = meta.name ? `SYNC: ${meta.name}` : 'SYNCING...';
                    }

                    if (type === 'progress') {
                        this.statusText = `PROGRESS (${this.metrics.processed})`;
                        this.progressPercent = Math.min(95, this.progressPercent + 5);
                    }

                    if (type === 'done') {
                        this.progressPercent = 100;
                        this.statusText = meta.status === 'success' ? 'COMPLETED' : 'FAILED';
                        this.finishSync();
                    }

                    // Auto scroll to bottom
                    if (this.autoScroll) {
                        this.$nextTick(() => {
                            const box = this.$refs.terminalBox;
                            if (box) {
                                box.scrollTop = box.scrollHeight;
                            }
                        });
                    }
                },

                stopSync() {
                    if (this.eventSource) {
                        this.eventSource.close();
                        this.eventSource = null;
                    }
                    const timeStr = new Date().toLocaleTimeString('id-ID', { hour12: false });
                    this.logs.push({
                        time: timeStr,
                        type: 'warning',
                        message: '⏹️ Proses sinkronisasi dihentikan oleh user.'
                    });
                    this.finishSync();
                },

                finishSync() {
                    this.isRunning = false;
                    this.isFinished = true;
                    if (this.eventSource) {
                        this.eventSource.close();
                        this.eventSource = null;
                    }
                },

                clearLogs() {
                    this.logs = [];
                    this.progressPercent = 0;
                    this.metrics = { processed: 0, created: 0, updated: 0, resigned: 0, errors: 0 };
                    this.isFinished = false;
                },

                toggleAutoScroll() {
                    this.autoScroll = !this.autoScroll;
                },

                copyLogs() {
                    const text = this.logs.map(l => `[${l.time}] ${l.message}`).join('\n');
                    navigator.clipboard.writeText(text).then(() => {
                        alert('Seluruh log terminal berhasil disalin ke clipboard!');
                    }).catch(() => {
                        alert('Gagal menyalin log.');
                    });
                }
            };
        }
    </script>
</x-filament-panels::page>
