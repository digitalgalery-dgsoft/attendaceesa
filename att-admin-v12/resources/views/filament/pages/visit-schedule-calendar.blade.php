<x-filament-panels::page>
    <style>
        .vcal-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
            font-family: inherit;
        }

        /* --- Filter Card & Controls --- */
        .vcal-header-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .dark .vcal-header-card {
            background: #1e293b;
            border-color: #334155;
        }

        .vcal-top-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .vcal-nav-group {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .dark .vcal-nav-group {
            background: #0f172a;
            border-color: #334155;
        }

        .vcal-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            background: transparent;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .vcal-nav-btn svg {
            width: 15px !important;
            height: 15px !important;
            min-width: 15px !important;
            flex-shrink: 0;
            display: inline-block;
        }
        .vcal-nav-btn:hover {
            color: #0f172a;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .dark .vcal-nav-btn {
            color: #94a3b8;
        }
        .dark .vcal-nav-btn:hover {
            color: #ffffff;
            background: #1e293b;
        }

        .vcal-current-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dark .vcal-current-title {
            color: #f8fafc;
        }

        .vcal-counter-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #eff6ff;
            border: 1.5px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 700;
            border-radius: 9999px;
            white-space: nowrap;
            line-height: 1;
        }
        .vcal-counter-pill svg {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            flex-shrink: 0;
            display: inline-block;
        }
        .dark .vcal-counter-pill {
            background: #1e3a8a33;
            border-color: #1e40af;
            color: #93c5fd;
        }

        /* --- Filter Grid --- */
        .vcal-filter-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }
        @media (min-width: 640px) {
            .vcal-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .vcal-filter-grid { grid-template-columns: 1.4fr 1fr 1fr 1.2fr 1.4fr; }
        }
        .dark .vcal-filter-grid {
            border-top-color: #334155;
        }

        .vcal-field-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .vcal-field-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .vcal-field-label {
            color: #94a3b8;
        }

        .vcal-select, .vcal-input {
            width: 100%;
            height: 38px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
            outline: none;
            transition: all 0.15s ease;
        }
        .vcal-select:focus, .vcal-input:focus {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }
        .dark .vcal-select, .dark .vcal-input {
            background: #0f172a;
            border-color: #334155;
            color: #f1f5f9;
        }
        .dark .vcal-select:focus, .dark .vcal-input:focus {
            border-color: #60a5fa;
            background: #1e293b;
        }

        /* --- Calendar Grid --- */
        .vcal-calendar-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .vcal-calendar-box {
            background: #1e293b;
            border-color: #334155;
        }

        .vcal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 1px;
            background: #cbd5e1;
        }
        .dark .vcal-grid {
            background: #334155;
        }

        .vcal-day-header {
            background: #f8fafc;
            padding: 12px 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .vcal-day-header {
            background: #0f172a;
            color: #94a3b8;
        }
        .vcal-day-header.weekend {
            color: #dc2626;
            background: #fef2f2;
        }
        .dark .vcal-day-header.weekend {
            color: #f87171;
            background: #451a1a;
        }

        .vcal-cell {
            background: #ffffff;
            min-height: 130px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            position: relative;
        }
        .dark .vcal-cell {
            background: #1e293b;
        }
        .vcal-cell.other-month {
            background: #f8fafc;
            opacity: 0.4;
        }
        .dark .vcal-cell.other-month {
            background: #0f172a;
            opacity: 0.3;
        }
        .vcal-cell.today {
            background: #f0f7ff;
            box-shadow: inset 0 0 0 2px #3b82f6;
        }
        .dark .vcal-cell.today {
            background: #1e3a8a26;
            box-shadow: inset 0 0 0 2px #60a5fa;
        }

        .vcal-cell-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vcal-date-badge {
            font-size: 12px;
            font-weight: 800;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #1e293b;
        }
        .dark .vcal-date-badge {
            color: #f1f5f9;
        }
        .vcal-cell.today .vcal-date-badge {
            background: #2563eb;
            color: #ffffff;
        }

        .vcal-quick-add {
            opacity: 0;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: #e2e8f0;
            color: #1e293b;
            font-size: 14px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .vcal-cell:hover .vcal-quick-add {
            opacity: 1;
        }
        .vcal-quick-add:hover {
            background: #2563eb;
            color: #ffffff;
            transform: scale(1.08);
        }
        .dark .vcal-quick-add {
            background: #334155;
            color: #f8fafc;
        }
        .dark .vcal-quick-add:hover {
            background: #3b82f6;
        }

        .vcal-cards-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            overflow-y: auto;
            max-height: 150px;
        }

        .vcal-card-item {
            background: #ffffff;
            border: 1px solid #bbf7d0;
            border-left: 3.5px solid #16a34a;
            border-radius: 7px;
            padding: 6px 8px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .vcal-card-item:hover {
            background: #f0fdf4;
            border-color: #86efac;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.06);
        }
        .dark .vcal-card-item {
            background: #064e3b22;
            border-color: #065f46;
            border-left-color: #10b981;
        }
        .dark .vcal-card-item:hover {
            background: #064e3b44;
        }

        .vcal-card-item.draft {
            border-color: #fef08a;
            border-left-color: #eab308;
            background: #fffbeb;
        }
        .dark .vcal-card-item.draft {
            background: #713f1222;
            border-color: #854d0e;
            border-left-color: #eab308;
        }

        .vcal-card-item.cancelled {
            border-color: #fecaca;
            border-left-color: #ef4444;
            background: #fef2f2;
            opacity: 0.7;
        }
        .dark .vcal-card-item.cancelled {
            background: #7f1d1d22;
            border-color: #991b1b;
            border-left-color: #ef4444;
        }

        .vcal-card-name {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.25;
        }
        .dark .vcal-card-name {
            color: #f8fafc;
        }

        .vcal-card-sub {
            font-size: 10px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dark .vcal-card-sub {
            color: #94a3b8;
        }

        .vcal-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
            font-size: 10px;
            color: #475569;
            padding-top: 2px;
            border-top: 1px dashed #e2e8f0;
        }
        .dark .vcal-card-footer {
            color: #cbd5e1;
            border-top-color: #334155;
        }

        .vcal-checkin-badge {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            background: #2563eb;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 4px;
            letter-spacing: 0.02em;
        }

        /* --- Extra Wide & Polished Detail Modal --- */
        .vcal-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .vcal-modal-panel {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            width: 100%;
            max-width: 980px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            animation: vcalModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes vcalModalIn {
            from { opacity: 0; transform: scale(0.96) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .dark .vcal-modal-panel {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        .vcal-modal-header {
            padding: 18px 26px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        .dark .vcal-modal-header {
            background: #0f172a;
            border-bottom-color: #334155;
        }

        .vcal-modal-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .dark .vcal-modal-title {
            color: #f8fafc;
        }

        .vcal-modal-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }
        .dark .vcal-modal-subtitle {
            color: #94a3b8;
        }

        .vcal-modal-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            border-radius: 8px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .vcal-modal-close svg {
            width: 20px !important;
            height: 20px !important;
            min-width: 20px !important;
            flex-shrink: 0;
            display: inline-block;
        }
        .vcal-modal-close:hover {
            color: #0f172a;
            background: #e2e8f0;
        }
        .dark .vcal-modal-close:hover {
            color: #ffffff;
            background: #334155;
        }

        .vcal-modal-content {
            padding: 26px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Profile Summary Card */
        .vcal-emp-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .dark .vcal-emp-card {
            background: #0f172a;
            border-color: #334155;
        }

        .vcal-emp-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .vcal-avatar-circle {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
            flex-shrink: 0;
        }

        .vcal-tag-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: #e2e8f0;
            color: #334155;
            white-space: nowrap;
        }
        .dark .vcal-tag-pill {
            background: #334155;
            color: #cbd5e1;
        }

        .vcal-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .vcal-status-badge.approved {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .dark .vcal-status-badge.approved {
            background: #14532d;
            color: #86efac;
            border-color: #166534;
        }
        .vcal-status-badge.draft {
            background: #fef9c3;
            color: #a16207;
            border: 1px solid #fef08a;
        }
        .dark .vcal-status-badge.draft {
            background: #713f12;
            color: #fef08a;
            border-color: #854d0e;
        }
        .vcal-status-badge.cancelled {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .dark .vcal-status-badge.cancelled {
            background: #7f1d1d;
            color: #fca5a5;
            border-color: #991b1b;
        }

        /* Locations Table */
        .vcal-table-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }
        .dark .vcal-table-box {
            border-color: #334155;
            background: #1e293b;
        }

        .vcal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }
        .vcal-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 12px 16px;
            border-bottom: 1px solid #cbd5e1;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }
        .dark .vcal-table th {
            background: #0f172a;
            color: #94a3b8;
            border-bottom-color: #334155;
        }
        .vcal-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .dark .vcal-table td {
            border-bottom-color: #334155;
            color: #f1f5f9;
        }
        .vcal-table tr:last-child td {
            border-bottom: none;
        }
        .vcal-table tr:hover td {
            background: #f8fafc;
        }
        .dark .vcal-table tr:hover td {
            background: #0f172a44;
        }

        .vcal-seq-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 800;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .dark .vcal-seq-badge {
            background: #334155;
            color: #ffffff;
        }

        .vcal-modal-footer {
            padding: 16px 26px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }
        .dark .vcal-modal-footer {
            background: #0f172a;
            border-top-color: #334155;
        }

        .vcal-btn-danger, .vcal-btn-primary, .vcal-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            text-decoration: none;
            line-height: 1;
            flex-shrink: 0;
        }
        .vcal-btn-danger svg, .vcal-btn-primary svg, .vcal-btn-secondary svg {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            flex-shrink: 0;
            display: inline-block;
        }

        .vcal-btn-danger {
            color: #dc2626;
            background: #ffffff;
            border: 1.5px solid #fca5a5;
        }
        .vcal-btn-danger:hover {
            background: #fee2e2;
            border-color: #ef4444;
            color: #b91c1c;
        }
        .dark .vcal-btn-danger {
            background: #7f1d1d22;
            border-color: #991b1b;
            color: #fca5a5;
        }
        .dark .vcal-btn-danger:hover {
            background: #7f1d1d44;
        }

        .vcal-btn-primary {
            color: #ffffff;
            background: #2563eb;
            border: 1.5px solid #2563eb;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
        }
        .vcal-btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }

        .vcal-btn-secondary {
            color: #475569;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
        }
        .vcal-btn-secondary:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .dark .vcal-btn-secondary {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .dark .vcal-btn-secondary:hover {
            background: #334155;
        }
    </style>

    <div class="vcal-wrapper">
        <!-- Top Controls & Filter Card -->
        <div class="vcal-header-card">
            <div class="vcal-top-bar">
                <div class="vcal-nav-group">
                    <button type="button" wire:click="prevMonth" class="vcal-nav-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Bulan Lalu
                    </button>
                    <button type="button" wire:click="today" class="vcal-nav-btn" style="border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1;">Hari Ini</button>
                    <button type="button" wire:click="nextMonth" class="vcal-nav-btn">
                        Bulan Depan
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <div class="vcal-current-title">
                    <span>{{ Carbon\Carbon::create($this->year, $this->month, 1)->translatedFormat('F Y') }}</span>
                </div>

                <div class="vcal-counter-pill">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Total: {{ $this->totalSchedulesInMonth }} Jadwal Visit</span>
                </div>
            </div>

            <!-- Filters Grid -->
            <div class="vcal-filter-grid">
                <div class="vcal-field-group">
                    <label class="vcal-field-label">Pilih Periode</label>
                    <div style="display: flex; gap: 6px;">
                        <select wire:model.live="month" class="vcal-select" style="flex: 1.5;">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                        <select wire:model.live="year" class="vcal-select" style="flex: 1;">
                            @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Region / Area</label>
                    <select wire:model.live="branch_id" class="vcal-select">
                        <option value="">Semua Area</option>
                        @foreach ($this->branchOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Prinsiple</label>
                    <select wire:model.live="principal_id" class="vcal-select">
                        <option value="">Semua Prinsiple</option>
                        @foreach ($this->principalOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Karyawan</label>
                    <select wire:model.live="employee_id" class="vcal-select">
                        <option value="">Semua Karyawan</option>
                        @foreach ($this->employeeOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Pencarian Cepat</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama karyawan / toko..." class="vcal-input">
                </div>
            </div>
        </div>

        <!-- Monthly Calendar Grid -->
        <div class="vcal-calendar-box">
            <div class="vcal-grid">
                <!-- Day Headers -->
                <div class="vcal-day-header">Senin</div>
                <div class="vcal-day-header">Selasa</div>
                <div class="vcal-day-header">Rabu</div>
                <div class="vcal-day-header">Kamis</div>
                <div class="vcal-day-header">Jumat</div>
                <div class="vcal-day-header weekend">Sabtu</div>
                <div class="vcal-day-header weekend">Minggu</div>

                <!-- Calendar Day Cells -->
                @foreach ($this->calendarDays as $day)
                    <div class="vcal-cell {{ $day['is_current_month'] ? '' : 'other-month' }} {{ $day['is_today'] ? 'today' : '' }}">
                        <div class="vcal-cell-top">
                            <span class="vcal-date-badge">{{ $day['day_number'] }}</span>
                            @if ($day['is_current_month'])
                                <button type="button" wire:click="openAddModal('{{ $day['date_string'] }}')" class="vcal-quick-add" title="Tambah Jadwal Visit pada tanggal {{ $day['date_string'] }}">+</button>
                            @endif
                        </div>

                        <!-- Schedules List inside Day -->
                        <div class="vcal-cards-container">
                            @foreach ($day['schedules'] as $sch)
                                <div wire:click="openDetailModal({{ $sch['id'] }})" class="vcal-card-item {{ $sch['status'] }}" title="Klik untuk membuka detail jadwal visit">
                                    <div class="vcal-card-name">
                                        {{ $sch['employee_name'] }}
                                    </div>
                                    <div class="vcal-card-sub">
                                        {{ $sch['position'] }} • {{ $sch['area'] }}
                                    </div>
                                    <div class="vcal-card-footer">
                                        <span>📍 {{ $sch['location_count'] }} Titik Toko</span>
                                        @if ($sch['has_checkin'])
                                            <span class="vcal-checkin-badge" title="Titik ini difungsikan sebagai lokasi check-in absensi">✓ Check-in</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Extra Wide Polished Detail Modal -->
    @if ($showDetailModal && $selectedItinerary)
        <div class="vcal-modal-backdrop" wire:click.self="closeDetailModal">
            <div class="vcal-modal-panel">
                <div class="vcal-modal-header">
                    <div>
                        <h3 class="vcal-modal-title">Detail Jadwal Visit (Visit Schedule)</h3>
                        <div class="vcal-modal-subtitle">
                            📅 {{ Carbon\Carbon::parse($selectedItinerary['date'])->translatedFormat('l, d F Y') }}
                        </div>
                    </div>
                    <button type="button" wire:click="closeDetailModal" class="vcal-modal-close" title="Tutup">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="vcal-modal-content">
                    <!-- Employee Profile Box -->
                    <div class="vcal-emp-card">
                        <div class="vcal-emp-info">
                            <div class="vcal-avatar-circle">
                                {{ strtoupper(substr($selectedItinerary['employee_name'], 0, 2)) }}
                            </div>
                            <div>
                                <div style="font-size: 16px; font-weight: 800; color: #0f172a;" class="dark:text-white">
                                    {{ $selectedItinerary['employee_name'] }}
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px;">
                                    <span class="vcal-tag-pill">NIK: {{ $selectedItinerary['employee_no'] }}</span>
                                    <span class="vcal-tag-pill" style="background: #e0e7ff; color: #3730a3;" class="dark:bg-indigo-900/50 dark:text-indigo-300">{{ $selectedItinerary['position'] }}</span>
                                    <span class="vcal-tag-pill" style="background: #f0fdf4; color: #166534;" class="dark:bg-emerald-900/50 dark:text-emerald-300">Area: {{ $selectedItinerary['area'] }}</span>
                                    <span class="vcal-tag-pill">Prinsiple: {{ $selectedItinerary['principal'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="vcal-status-badge {{ $selectedItinerary['status'] }}">
                                ● {{ $selectedItinerary['status'] }}
                            </span>
                        </div>
                    </div>

                    @if (!empty($selectedItinerary['notes']))
                        <div style="padding: 14px 16px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; font-size: 13px; color: #0369a1;" class="dark:bg-sky-900/30 dark:border-sky-800 dark:text-sky-300">
                            <strong>Catatan Kunjungan:</strong> {{ $selectedItinerary['notes'] }}
                        </div>
                    @endif

                    <!-- Locations Table -->
                    <div>
                        <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 10px;" class="dark:text-gray-300">
                            Daftar Titik / Toko Kunjungan ({{ count($selectedItinerary['items']) }} Lokasi)
                        </div>
                        <div class="vcal-table-box">
                            <table class="vcal-table">
                                <thead>
                                    <tr>
                                        <th style="width: 55px; text-align: center;">Urutan</th>
                                        <th>Lokasi / Toko</th>
                                        <th>Prinsiple</th>
                                        <th>Tipe Kunjungan</th>
                                        <th style="text-align: center;">Titik Check-in</th>
                                        <th>Catatan / Agenda</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($selectedItinerary['items'] as $item)
                                        <tr>
                                            <td style="text-align: center;">
                                                <span class="vcal-seq-badge">{{ $item['sequence'] }}</span>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; font-size: 13px; color: #0f172a;" class="dark:text-white">{{ $item['location_name'] }}</div>
                                                @if (!empty($item['location_address']))
                                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;" class="dark:text-gray-400">{{ $item['location_address'] }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <span style="font-weight: 600;">{{ $item['principal_name'] ?: '-' }}</span>
                                            </td>
                                            <td>
                                                <span class="vcal-tag-pill" style="text-transform: capitalize;">
                                                    {{ $item['visit_type'] }}
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                @if ($item['is_checkin_location'])
                                                    <span class="vcal-checkin-badge" style="padding: 3px 8px; font-size: 11px;">
                                                        ✓ Ya (Check-in)
                                                    </span>
                                                @else
                                                    <span style="color: #94a3b8;">-</span>
                                                @endif
                                            </td>
                                            <td style="color: #475569;" class="dark:text-gray-300">{{ $item['notes'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 28px; color: #94a3b8;">
                                                Belum ada lokasi kunjungan yang didaftarkan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="vcal-modal-footer">
                    <button type="button" wire:click="deleteItinerary({{ $selectedItinerary['id'] }})" wire:confirm="Apakah Anda yakin ingin menghapus jadwal visit ini?" class="vcal-btn-danger">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span>Hapus Jadwal</span>
                    </button>
                    <a href="{{ App\Filament\Resources\Itineraries\ItineraryResource::getUrl('edit', ['record' => $selectedItinerary['id']]) }}" class="vcal-btn-primary">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Edit Jadwal Visit</span>
                    </a>
                    <button type="button" wire:click="closeDetailModal" class="vcal-btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
