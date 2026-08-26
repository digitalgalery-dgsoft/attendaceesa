<x-filament-panels::page>
    <style>
        .odoo-sync-wrap {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .odoo-top-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }
        .odoo-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }
        @media (max-width: 1024px) {
            .odoo-top-grid {
                grid-template-columns: 1fr;
            }
            .odoo-actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card Styles */
        .odoo-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .dark .odoo-card {
            background: #18181b;
            border-color: #27272a;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
        }
        .odoo-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
        }
        .dark .odoo-card:hover {
            border-color: #3f3f46;
        }

        /* Banner Styles */
        .odoo-banner-success {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px 20px;
            color: #166534;
        }
        .dark .odoo-banner-success {
            background: rgba(22, 101, 52, 0.15);
            border-color: rgba(34, 197, 94, 0.25);
            color: #4ade80;
        }

        .odoo-banner-warning {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 16px 20px;
            color: #92400e;
        }
        .dark .odoo-banner-warning {
            background: rgba(146, 64, 14, 0.15);
            border-color: rgba(245, 158, 11, 0.25);
            color: #fbbf24;
        }

        /* Button Styles */
        .odoo-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
            text-decoration: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .odoo-btn:active {
            transform: scale(0.98);
        }
        .odoo-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none !important;
        }

        .odoo-btn-primary { background: #2563eb; color: #ffffff; }
        .odoo-btn-primary:hover:not(:disabled) { background: #1d4ed8; }

        .odoo-btn-success { background: #16a34a; color: #ffffff; }
        .odoo-btn-success:hover:not(:disabled) { background: #15803d; }

        .odoo-btn-warning { background: #d97706; color: #ffffff; }
        .odoo-btn-warning:hover:not(:disabled) { background: #b45309; }

        .odoo-btn-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25);
        }
        .odoo-btn-gradient:hover:not(:disabled) {
            background: linear-gradient(135deg, #d97706 0%, #c2410c 100%);
            box-shadow: 0 6px 14px rgba(234, 88, 12, 0.35);
        }

        .odoo-btn-outline-violet {
            background: rgba(139, 92, 246, 0.08);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #6d28d9;
        }
        .dark .odoo-btn-outline-violet {
            background: rgba(139, 92, 246, 0.12);
            border-color: rgba(139, 92, 246, 0.35);
            color: #c4b5fd;
        }
        .odoo-btn-outline-violet:hover:not(:disabled) {
            background: rgba(139, 92, 246, 0.18);
        }

        .odoo-btn-outline-cyan {
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: #0e7490;
        }
        .dark .odoo-btn-outline-cyan {
            background: rgba(6, 182, 212, 0.12);
            border-color: rgba(6, 182, 212, 0.35);
            color: #67e8f9;
        }
        .odoo-btn-outline-cyan:hover {
            background: rgba(6, 182, 212, 0.18);
        }

        /* Terminal Window */
        .odoo-terminal-box {
            background: #090d16;
            border: 1px solid #1f293d;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .odoo-terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
        }
        .odoo-terminal-dots {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .odoo-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            display: inline-block;
        }
        .odoo-dot-red { background: #ef4444; }
        .odoo-dot-yellow { background: #f59e0b; }
        .odoo-dot-green { background: #10b981; }

        .odoo-metrics-bar {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            padding: 10px 18px;
            background: #0c1427;
            border-bottom: 1px solid #1a253c;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 11px;
        }
        @media (max-width: 640px) {
            .odoo-metrics-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .odoo-metric-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #94a3b8;
        }
        .odoo-metric-val {
            font-weight: 800;
        }

        .odoo-terminal-body {
            padding: 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 12px;
            line-height: 1.6;
            color: #cbd5e1;
            min-height: 380px;
            max-height: 520px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .odoo-terminal-body::-webkit-scrollbar {
            width: 8px;
        }
        .odoo-terminal-body::-webkit-scrollbar-track {
            background: #090d16;
        }
        .odoo-terminal-body::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        /* Spinner animation */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .odoo-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
    </style>

    <div wire:ignore x-data="odooSyncEngine()" class="odoo-sync-wrap">

        {{-- Row 1: Status & Quick Tools --}}
        <div class="odoo-top-grid">
            <div>
                @if ($this->isConfigured())
                    <div class="odoo-banner-success">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="padding: 8px; background: rgba(34, 197, 94, 0.2); border-radius: 10px;">
                                <x-filament::icon icon="heroicon-m-check-badge" style="width: 24px; height: 24px; color: #16a34a;" />
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 14px;">Odoo ERP Terhubung & Siap</div>
                                <div style="font-size: 11px; margin-top: 2px; opacity: 0.85; font-family: ui-monospace, monospace;">
                                    DB: <strong>{{ $this->getCompany()?->odoo_db }}</strong> · Host: <span>{{ $this->getCompany()?->odoo_url }}</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="startSync('test_connection')" x-bind:disabled="isRunning" class="odoo-btn odoo-btn-success" style="padding: 6px 14px; font-size: 12px;">
                            <x-filament::icon icon="heroicon-m-signal" style="width: 14px; height: 14px;" />
                            <span>Test Ping</span>
                        </button>
                    </div>
                @else
                    <div class="odoo-banner-warning">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="padding: 8px; background: rgba(245, 158, 11, 0.2); border-radius: 10px;">
                                <x-filament::icon icon="heroicon-m-exclamation-triangle" style="width: 24px; height: 24px; color: #d97706;" />
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 14px;">Konfigurasi Odoo Belum Lengkap</div>
                                <div style="font-size: 11px; margin-top: 2px; opacity: 0.85;">
                                    URL, Database, Username, atau API Key belum diisi untuk entitas ini.
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('filament.admin.resources.companies.index') }}" class="odoo-btn odoo-btn-warning" style="padding: 6px 14px; font-size: 12px;">
                            Master Perusahaan
                        </a>
                    </div>
                @endif
            </div>

            {{-- Quick action cards --}}
            <div style="display: flex; gap: 10px;">
                <button type="button" @click="startSync('cleanup_duplicates')" x-bind:disabled="isRunning" class="odoo-btn odoo-btn-outline-violet" style="flex: 1; text-align: left; padding: 12px 16px; border-radius: 12px; justify-content: flex-start;">
                    <x-filament::icon icon="heroicon-m-sparkles" style="width: 20px; height: 20px; flex-shrink: 0;" />
                    <div>
                        <div style="font-size: 12px; font-weight: 800;">Bersihkan Duplikat</div>
                        <div style="font-size: 10px; font-weight: 400; opacity: 0.75;">Gabungkan data NIK ganda</div>
                    </div>
                </button>
                <a href="{{ route('filament.admin.pages.odoo-sync-report') }}" class="odoo-btn odoo-btn-outline-cyan" style="padding: 12px 16px; border-radius: 12px;" title="Buka Laporan Sinkronisasi">
                    <x-filament::icon icon="heroicon-m-document-chart-bar" style="width: 22px; height: 22px;" />
                </a>
            </div>
        </div>

        {{-- Row 2: Company Selector Card --}}
        <div class="odoo-card" style="padding: 18px 24px;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px;">
                <div style="flex: 1; min-width: 280px; max-width: 550px;">
                    <label style="display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px;">
                        Pilih Perusahaan / Entitas Target
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedCompanyId" x-ref="companySelect" x-bind:disabled="isRunning" style="font-weight: 600;">
                            @foreach ($this->getCompanyOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; padding-top: 4px;">
                    <button type="button" @click="startSync('all_companies')" x-bind:disabled="isRunning" class="odoo-btn odoo-btn-gradient" style="padding: 12px 24px; font-size: 13px;">
                        <x-filament::icon icon="heroicon-m-bolt" style="width: 18px; height: 18px;" />
                        <span>Sync Semua Perusahaan Sekaligus</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Row 3: 3 Action Cards --}}
        <div class="odoo-actions-grid">
            
            {{-- Card 1: Sync Principals --}}
            <div class="odoo-card" style="border-top: 4px solid #3b82f6;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <div style="padding: 6px; background: rgba(59, 130, 246, 0.12); border-radius: 8px; color: #2563eb;">
                            <x-filament::icon icon="heroicon-m-building-office-2" style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a;" class="dark:!text-white">Sync Principals</div>
                            <div style="font-size: 11px; color: #64748b;">Odoo Contact (is_principal = True)</div>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin: 12px 0 20px 0; line-height: 1.5;">
                        Menarik dan memperbarui master data <strong>Prinsiple / Brand</strong> dari kontak Odoo ke master database lokal.
                    </div>
                </div>

                <button type="button" @click="startSync('principals')" x-bind:disabled="isRunning || !isCompanyConfigured" class="odoo-btn odoo-btn-primary" style="width: 100%;">
                    <span x-show="isRunning && currentAction === 'principals'" class="odoo-spinner"></span>
                    <span x-show="!isRunning || currentAction !== 'principals'"><x-filament::icon icon="heroicon-m-arrow-path" style="width: 15px; height: 15px;" /></span>
                    <span>Mulai Sync Principals</span>
                </button>
            </div>

            {{-- Card 2: Sync Employees --}}
            <div class="odoo-card" style="border-top: 4px solid #10b981;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <div style="padding: 6px; background: rgba(16, 185, 129, 0.12); border-radius: 8px; color: #16a34a;">
                            <x-filament::icon icon="heroicon-m-users" style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a;" class="dark:!text-white">Sync Employees</div>
                            <div style="font-size: 11px; color: #64748b;">Odoo hr.employee</div>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin: 12px 0 20px 0; line-height: 1.5;">
                        Sinkronisasi data karyawan aktif & resign, jabatan, area, serta auto-create department secara instan.
                    </div>
                </div>

                <button type="button" @click="startSync('employees')" x-bind:disabled="isRunning || !isCompanyConfigured" class="odoo-btn odoo-btn-success" style="width: 100%;">
                    <span x-show="isRunning && currentAction === 'employees'" class="odoo-spinner"></span>
                    <span x-show="!isRunning || currentAction !== 'employees'"><x-filament::icon icon="heroicon-m-arrow-path" style="width: 15px; height: 15px;" /></span>
                    <span>Mulai Sync Employees</span>
                </button>
            </div>

            {{-- Card 3: Sync All --}}
            <div class="odoo-card" style="border-top: 4px solid #f59e0b;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <div style="padding: 6px; background: rgba(245, 158, 11, 0.12); border-radius: 8px; color: #d97706;">
                            <x-filament::icon icon="heroicon-m-bolt" style="width: 20px; height: 20px;" />
                        </div>
                        <div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a;" class="dark:!text-white">Sync All</div>
                            <div style="font-size: 11px; color: #64748b;">Principal + Employee Sekaligus</div>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin: 12px 0 20px 0; line-height: 1.5;">
                        Jalankan sinkronisasi lengkap: <strong>Principal</strong> terlebih dahulu, lalu <strong>Employee</strong> secara berurutan.
                    </div>
                </div>

                <button type="button" @click="startSync('all')" x-bind:disabled="isRunning || !isCompanyConfigured" class="odoo-btn odoo-btn-warning" style="width: 100%;">
                    <span x-show="isRunning && currentAction === 'all'" class="odoo-spinner"></span>
                    <span x-show="!isRunning || currentAction !== 'all'"><x-filament::icon icon="heroicon-m-arrow-path" style="width: 15px; height: 15px;" /></span>
                    <span>Mulai Sync All</span>
                </button>
            </div>

        </div>

        {{-- JENDELA TERMINAL REALTIME STREAMING (SEPERTI DI TERMINAL LINUX) --}}
        <div id="odoo-terminal-container" class="odoo-terminal-box">
            
            {{-- Terminal Top Bar --}}
            <div class="odoo-terminal-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="odoo-terminal-dots">
                        <span class="odoo-dot odoo-dot-red"></span>
                        <span class="odoo-dot odoo-dot-yellow"></span>
                        <span class="odoo-dot odoo-dot-green"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; font-family: ui-monospace, monospace; font-size: 12px; font-weight: 700; color: #e2e8f0; padding-left: 10px; border-left: 1px solid #334155;">
                        <x-filament::icon icon="heroicon-m-command-line" style="width: 16px; height: 16px; color: #38bdf8;" />
                        <span>esa@appsend: ~/odoo-sync-terminal</span>
                    </div>
                </div>

                {{-- Status Indicator & Terminal Controls --}}
                <div style="display: flex; align-items: center; gap: 10px;">
                    <template x-if="isRunning">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 9999px; font-family: ui-monospace, monospace; font-size: 10px; font-weight: 800; background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399; display: inline-block;"></span>
                            <span x-text="statusText || 'RUNNING...'"></span>
                        </span>
                    </template>
                    <template x-if="!isRunning && isFinished">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 9999px; font-family: ui-monospace, monospace; font-size: 10px; font-weight: 800; background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4);">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; display: inline-block;"></span>
                            <span>COMPLETED</span>
                        </span>
                    </template>
                    <template x-if="!isRunning && !isFinished">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 9999px; font-family: ui-monospace, monospace; font-size: 10px; font-weight: 700; background: #1e293b; color: #94a3b8; border: 1px solid #334155;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #64748b; display: inline-block;"></span>
                            <span>STANDBY</span>
                        </span>
                    </template>

                    {{-- Toolbar buttons --}}
                    <div style="display: flex; align-items: center; gap: 4px; padding-left: 8px; border-left: 1px solid #334155;">
                        <button type="button" @click="toggleAutoScroll()" x-bind:title="autoScroll ? 'Auto Scroll Aktif' : 'Auto Scroll Mati'" style="padding: 6px 8px; border-radius: 6px; background: transparent; border: none; cursor: pointer; color: #94a3b8;" x-bind:style="autoScroll ? 'color: #38bdf8;' : ''">
                            <x-filament::icon icon="heroicon-m-arrows-up-down" style="width: 16px; height: 16px;" />
                        </button>
                        <button type="button" @click="copyLogs()" title="Salin Seluruh Log" style="padding: 6px 8px; border-radius: 6px; background: transparent; border: none; cursor: pointer; color: #94a3b8;">
                            <x-filament::icon icon="heroicon-m-clipboard-document" style="width: 16px; height: 16px;" />
                        </button>
                        <button type="button" @click="clearLogs()" title="Bersihkan Layar Terminal" style="padding: 6px 8px; border-radius: 6px; background: transparent; border: none; cursor: pointer; color: #94a3b8;">
                            <x-filament::icon icon="heroicon-m-trash" style="width: 16px; height: 16px;" />
                        </button>
                        <template x-if="isRunning">
                            <button type="button" @click="stopSync()" style="padding: 4px 10px; border-radius: 6px; background: #dc2626; color: #ffffff; font-family: ui-monospace, monospace; font-size: 10px; font-weight: 800; border: none; cursor: pointer; margin-left: 4px;">
                                STOP
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Live Metric Counter Header --}}
            <div class="odoo-metrics-bar">
                <div class="odoo-metric-item">
                    <span>DIPROSES:</span>
                    <span class="odoo-metric-val" style="color: #38bdf8;" x-text="metrics.processed">0</span>
                </div>
                <div class="odoo-metric-item">
                    <span>BARU:</span>
                    <span class="odoo-metric-val" style="color: #34d399;" x-text="metrics.created">0</span>
                </div>
                <div class="odoo-metric-item">
                    <span>UPDATE:</span>
                    <span class="odoo-metric-val" style="color: #fbbf24;" x-text="metrics.updated">0</span>
                </div>
                <div class="odoo-metric-item">
                    <span>RESIGN:</span>
                    <span class="odoo-metric-val" style="color: #f87171;" x-text="metrics.resigned">0</span>
                </div>
                <div class="odoo-metric-item">
                    <span>ERROR:</span>
                    <span class="odoo-metric-val" style="color: #ef4444;" x-text="metrics.errors">0</span>
                </div>
            </div>

            {{-- Prominent Progress Bar Section --}}
            <div style="background: #090d16; padding: 12px 18px; border-bottom: 1px solid #1e293b;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-family: ui-monospace, monospace; font-size: 11px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #94a3b8; font-weight: 700;">PROGRES SYNC:</span>
                        <span style="color: #38bdf8; font-weight: 800;" x-text="metrics.processed + ' / ' + (totalEmployees > 0 ? totalEmployees : '...') + ' Karyawan'">0 / ... Karyawan</span>
                        <template x-if="currentBatchText">
                            <span style="color: #a78bfa; font-weight: 600;" x-text="'(' + currentBatchText + ')'"></span>
                        </template>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: #34d399; font-weight: 800; font-size: 12px;" x-text="progressPercent + '%'">0%</span>
                        <span style="color: #64748b;">SELESAI</span>
                    </div>
                </div>
                <div style="width: 100%; height: 8px; background: #1e293b; border-radius: 9999px; overflow: hidden; position: relative; border: 1px solid #334155;">
                    <div style="height: 100%; background: linear-gradient(90deg, #38bdf8 0%, #34d399 50%, #10b981 100%); border-radius: 9999px; transition: width 0.4s ease-out; box-shadow: 0 0 10px rgba(56, 189, 248, 0.5);" x-bind:style="'width: ' + progressPercent + '%'"></div>
                </div>
            </div>

            {{-- Terminal Output Log Box --}}
            <div x-ref="terminalBox" class="odoo-terminal-body">
                
                {{-- Welcome / Standby message --}}
                <div style="color: #64748b; padding-bottom: 8px; border-bottom: 1px solid #1e293b; margin-bottom: 8px; user-select: none;">
                    <div>ESA Odoo Sync Engine v2.0 [Real-time Stream Edition]</div>
                    <div>Klik tombol di atas untuk memulai sinkronisasi. Log aktivitas tiap entitas & batch akan tampil di bawah ini secara langsung tanpa jeda atau timeout.</div>
                </div>

                {{-- Dynamic Stream Logs --}}
                <template x-for="(log, idx) in logs" :key="idx">
                    <div style="display: flex; align-items: flex-start; gap: 8px; padding: 2px 4px; border-radius: 4px;">
                        <span style="color: #64748b; flex-shrink: 0; user-select: none;" x-text="'[' + log.time + ']'"></span>
                        
                        {{-- Log Type Badge / Format --}}
                        <template x-if="log.type === 'company_start'">
                            <span style="color: #fbbf24; font-weight: 800; letter-spacing: 0.02em;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'company_end'">
                            <span style="color: #34d399; font-weight: 800;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'batch'">
                            <span style="color: #a78bfa; font-weight: 700;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'created'">
                            <span style="color: #34d399;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'updated'">
                            <span style="color: #38bdf8;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'resigned'">
                            <span style="color: #f87171; font-weight: 600;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'progress'">
                            <span style="color: #60a5fa; font-weight: 700;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'summary'">
                            <span style="color: #6ee7b7; font-weight: 800; background: rgba(16, 185, 129, 0.15); padding: 2px 6px; border-radius: 4px; display: inline-block;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'error'">
                            <span style="color: #ef4444; font-weight: 800; background: rgba(239, 68, 68, 0.15); padding: 2px 6px; border-radius: 4px; display: inline-block;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'warning'">
                            <span style="color: #fbbf24; font-weight: 600;" x-text="log.message"></span>
                        </template>
                        <template x-if="log.type === 'info' || log.type === 'item'">
                            <span style="color: #cbd5e1;" x-text="log.message"></span>
                        </template>
                    </div>
                </template>

                {{-- Cursor Blinking --}}
                <div style="display: flex; align-items: center; gap: 4px; padding-top: 4px; color: #38bdf8; font-weight: 800; user-select: none;" x-show="isRunning">
                    <span>❯</span>
                    <span style="display: inline-block; width: 8px; height: 14px; background: #38bdf8;"></span>
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
                totalEmployees: 0,
                currentBatchText: '',
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
                    this.progressPercent = 0;
                    this.totalEmployees = 0;
                    this.currentBatchText = '';
                    this.metrics = { processed: 0, created: 0, updated: 0, resigned: 0, errors: 0 };

                    // Scroll terminal into view
                    document.getElementById('odoo-terminal-container')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                    const timeStr = new Date().toLocaleTimeString('id-ID', { hour12: false });
                    this.logs.push({
                        time: timeStr,
                        type: 'info',
                        message: '▶️ Memulai proses [' + action.toUpperCase() + ']... Menghubungkan ke engine Odoo.'
                    });

                    // Construct SSE endpoint URL
                    const url = '{{ route('admin.odoo-sync.stream') }}?company_id=' + encodeURIComponent(companyId) + '&action=' + encodeURIComponent(action) + '&_t=' + Date.now();

                    if (this.eventSource) {
                        this.eventSource.close();
                    }

                    this.eventSource = new EventSource(url);

                    this.eventSource.onopen = () => {
                        this.statusText = 'STREAM ACTIVE';
                    };

                    this.eventSource.onmessage = (event) => {
                        try {
                            const data = JSON.parse(event.data);
                            this.handleStreamMessage(data);
                        } catch (e) {
                            console.error('Error parsing SSE event:', e, event.data);
                        }
                    };

                    this.eventSource.onerror = (err) => {
                        if (this.eventSource && this.eventSource.readyState === EventSource.CONNECTING) {
                            this.statusText = 'MEMPROSES BATCH...';
                            return;
                        }
                        if (this.isRunning && !this.isFinished) {
                            const nowTime = new Date().toLocaleTimeString('id-ID', { hour12: false });
                            this.logs.push({
                                time: nowTime,
                                type: 'info',
                                message: '🏁 Koneksi stream selesai.'
                            });
                            this.finishSync();
                        }
                    };
                },

                handleStreamMessage(data) {
                    if (data.type === 'ping') return;

                    const time = data.time || new Date().toLocaleTimeString('id-ID', { hour12: false });
                    const type = data.type || 'info';
                    const message = data.message || '';
                    const meta = data.meta || {};

                    this.logs.push({ time: time, type: type, message: message });

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
                    if (meta.total !== undefined && meta.total > 0) this.totalEmployees = meta.total;
                    if (meta.progress !== undefined) this.progressPercent = meta.progress;

                    // Update Status text and batch badge
                    if (type === 'company_start') {
                        this.statusText = meta.name ? 'SYNC: ' + meta.name : 'SYNCING...';
                    }

                    if (type === 'batch') {
                        this.currentBatchText = meta.batch ? 'Batch #' + meta.batch : (message.includes('Batch #') ? message.substring(message.indexOf('Batch #')).split('(')[0].trim() : 'Processing');
                    }

                    if (type === 'progress') {
                        this.statusText = 'SYNCING (' + (this.totalEmployees > 0 ? this.metrics.processed + '/' + this.totalEmployees : this.metrics.processed) + ')';
                        if (meta.progress !== undefined) {
                            this.progressPercent = meta.progress;
                        }
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
                    const text = this.logs.map(l => '[' + l.time + '] ' + l.message).join('\n');
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
