@extends('portal.layout')

@section('title', $template->title . ' - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', $template->title)
@section('breadcrumb_active', $template->code)

@push('styles')
<style>
    /* Top Header */
    .template-detail-header {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.25rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .template-header-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .template-icon-large {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: var(--brand-light);
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .template-title-text {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 0.25rem;
    }

    .template-meta-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.82rem;
        color: var(--text-muted);
        flex-wrap: wrap;
    }

    .template-code-pill {
        font-family: monospace;
        font-weight: 700;
        background: #f1f5f9;
        color: var(--text-heading);
        padding: 0.2rem 0.6rem;
        border-radius: 6px;
        border: 1px solid var(--border-color);
    }

    .studio-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .studio-badge-active {
        background: #ede9fe;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
    }

    .header-actions-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-studio-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.15rem;
        background: #4f46e5;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        transition: all 0.2s ease;
    }

    .btn-studio-toggle:hover {
        background: #4338ca;
        transform: translateY(-2px);
    }

    .btn-studio-toggle.active {
        background: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.35);
    }

    .btn-export-excel {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.15rem;
        background: #16a34a;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }

    .btn-export-excel:hover {
        background: #15803d;
        transform: translateY(-2px);
    }

    /* Odoo Studio Bar (Active when in customization mode) */
    .odoo-studio-bar {
        display: none;
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        border: 1px solid #4338ca;
        border-radius: 14px;
        padding: 0.95rem 1.5rem;
        margin-bottom: 1.25rem;
        color: #ffffff;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        box-shadow: 0 10px 25px -5px rgba(49, 46, 129, 0.4);
        animation: fadeIn 0.25s ease-in-out;
    }

    .odoo-studio-bar.show {
        display: flex;
    }

    .studio-bar-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .studio-logo-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #6366f1;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }

    .studio-bar-title {
        font-size: 0.95rem;
        font-weight: 800;
        letter-spacing: 0.2px;
    }

    .studio-bar-sub {
        font-size: 0.76rem;
        color: #c7d2fe;
    }

    .studio-bar-actions {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .btn-studio-action {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.9rem;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-studio-add {
        background: #6366f1;
        color: #ffffff;
        border-color: #818cf8;
    }
    .btn-studio-add:hover { background: #4f46e5; }

    .btn-studio-save {
        background: #10b981;
        color: #ffffff;
        border-color: #34d399;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }
    .btn-studio-save:hover { background: #059669; }

    .btn-studio-reset {
        background: rgba(255, 255, 255, 0.12);
        color: #fca5a5;
        border-color: rgba(252, 165, 165, 0.3);
    }
    .btn-studio-reset:hover { background: rgba(239, 68, 68, 0.2); }

    .btn-studio-close {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.2);
    }
    .btn-studio-close:hover { background: rgba(255, 255, 255, 0.25); }

    /* Filter Bar */
    .filter-bar {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 0.85rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        flex-wrap: wrap;
    }

    .filter-group-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .filter-select-btn {
        padding: 0.5rem 0.95rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-heading);
        outline: none;
        cursor: pointer;
    }

    .filter-search-input {
        padding: 0.5rem 0.85rem;
        font-size: 0.85rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: #f8fafc;
        outline: none;
        width: 220px;
    }

    /* 12-Column Responsive Dashboard Grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .col-span-12 { grid-column: span 12; }
    .col-span-8  { grid-column: span 8; }
    .col-span-6  { grid-column: span 6; }
    .col-span-4  { grid-column: span 4; }
    .col-span-3  { grid-column: span 3; }

    @media (max-width: 1024px) {
        .col-span-3, .col-span-4 { grid-column: span 6; }
        .col-span-8 { grid-column: span 12; }
    }

    @media (max-width: 640px) {
        .col-span-3, .col-span-4, .col-span-6, .col-span-8 { grid-column: span 12; }
    }

    /* Studio Mode Canvas State */
    .dashboard-grid.studio-active-grid .widget-card {
        border: 2px dashed #818cf8 !important;
        position: relative;
        background: #fafbff;
    }

    .dashboard-grid.studio-active-grid .widget-card:hover {
        border-color: #4f46e5 !important;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
    }

    .studio-widget-toolbar {
        display: none;
        position: absolute;
        top: -12px;
        right: 12px;
        background: #1e1b4b;
        color: #ffffff;
        border-radius: 6px;
        padding: 2px 6px;
        align-items: center;
        gap: 6px;
        font-size: 0.72rem;
        z-index: 10;
        box-shadow: var(--shadow-md);
    }

    .dashboard-grid.studio-active-grid .studio-widget-toolbar {
        display: flex;
    }

    .studio-btn-icon {
        color: #c7d2fe;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        border: none;
        background: transparent;
    }
    .studio-btn-icon:hover { color: #ffffff; background: rgba(255, 255, 255, 0.2); }
    .studio-btn-icon.del:hover { color: #fca5a5; background: rgba(239, 68, 68, 0.4); }

    .studio-drag-handle {
        cursor: grab;
        color: #a5b4fc;
        padding: 2px 4px;
    }
    .studio-drag-handle:active { cursor: grabbing; }

    /* KPI Card Style */
    .widget-kpi-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        transition: all 0.2s ease;
    }

    .widget-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .kpi-info-box {
        flex: 1;
        min-width: 0;
    }

    .kpi-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 0.35rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .kpi-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
    }

    .kpi-icon-badge {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    /* Color Palettes */
    .color-blue .kpi-icon-badge { background: #eff6ff; color: #2563eb; }
    .color-emerald .kpi-icon-badge { background: #ecfdf5; color: #059669; }
    .color-purple .kpi-icon-badge { background: #f5f3ff; color: #7c3aed; }
    .color-orange .kpi-icon-badge { background: #fff7ed; color: #ea580c; }
    .color-rose .kpi-icon-badge { background: #fff1f2; color: #e11d48; }
    .color-amber .kpi-icon-badge { background: #fffbeb; color: #d97706; }
    .color-indigo .kpi-icon-badge { background: #eef2ff; color: #4f46e5; }

    /* Chart & Table Card Style */
    .widget-content-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }

    .widget-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid var(--border-color);
    }

    .widget-card-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .widget-card-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: normal;
    }

    /* Breakdown / Ranking List */
    .breakdown-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .breakdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.85rem;
    }

    .breakdown-bar-bg {
        flex: 1;
        height: 8px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
        margin: 0 0.5rem;
    }

    .breakdown-bar-fill {
        height: 100%;
        background: var(--brand-gradient);
        border-radius: 999px;
    }

    /* Submissions Table Card */
    .table-container-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .custom-table th {
        background: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        text-align: left;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .custom-table td {
        padding: 0.95rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: var(--text-body);
        vertical-align: middle;
    }

    .custom-table tr:hover td {
        background: #f8fafc;
    }

    /* Studio Modal */
    .portal-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
    }

    .portal-modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .portal-modal-card {
        background: #ffffff;
        border-radius: 18px;
        width: 100%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
        transform: scale(0.95);
        transition: all 0.2s ease;
    }

    .portal-modal-overlay.active .portal-modal-card {
        transform: scale(1);
    }

    .portal-modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .portal-modal-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .portal-modal-body {
        padding: 1.5rem;
    }

    .btn-close-modal {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 6px;
    }
    .btn-close-modal:hover { color: var(--text-heading); background: #f1f5f9; }

    .form-group-row {
        margin-bottom: 1.15rem;
    }

    .form-label-custom {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-heading);
        margin-bottom: 0.35rem;
    }

    .form-input-custom, .form-select-custom {
        width: 100%;
        padding: 0.65rem 0.85rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 0.88rem;
        color: var(--text-heading);
        outline: none;
        background: #f8fafc;
        transition: border-color 0.2s;
    }

    .form-input-custom:focus, .form-select-custom:focus {
        border-color: #4f46e5;
        background: #ffffff;
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
    }
</style>
@endpush

@section('content')

    <!-- Header Card -->
    <div class="template-detail-header">
        <div class="template-header-left">
            <div class="template-icon-large">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div>
                <h2 class="template-title-text">{{ $template->title }}</h2>
                <div class="template-meta-row">
                    <span class="template-code-pill">{{ $template->code }}</span>
                    <span>&bull;</span>
                    <span>Kategori: <strong>{{ ucfirst($template->category ?? 'General') }}</strong></span>
                    <span>&bull;</span>
                    <span>{{ $template->fields->count() }} Field Input</span>
                    @if(!empty($dashboardConfig['is_custom']))
                        <span>&bull;</span>
                        <span class="studio-badge studio-badge-active">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Custom Studio Dashboard
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="header-actions-group">
            <button type="button" class="btn-studio-toggle" id="btn_toggle_studio" onclick="toggleStudioMode()">
                <i class="fa-solid fa-layer-group"></i>
                <span id="studio_btn_text">🎨 Studio Dashboard</span>
            </button>

            <a href="{{ route('portal.report.export', ['code' => $template->code, 'month' => $month, 'year' => $year, 'p' => $tenantPrincipal->id]) }}" class="btn-export-excel">
                <i class="fa-solid fa-file-excel"></i>
                Export Rekap CSV / Excel
            </a>
        </div>
    </div>

    <!-- Odoo Studio Bar (When Studio Mode Active) -->
    <div id="odoo_studio_bar" class="odoo-studio-bar">
        <div class="studio-bar-left">
            <div class="studio-logo-icon">
                <i class="fa-solid fa-palette"></i>
            </div>
            <div>
                <div class="studio-bar-title">✨ Dashboard Studio Mode</div>
                <div class="studio-bar-sub">Seret & lepas (Drag & Drop) untuk mengatur urutan. Tambah grafik atau KPI sesuai field laporan.</div>
            </div>
        </div>

        <div class="studio-bar-actions">
            <button type="button" class="btn-studio-action btn-studio-add" onclick="openAddWidgetModal()">
                <i class="fa-solid fa-plus-circle"></i> Tambah Widget / Grafik
            </button>
            <button type="button" class="btn-studio-action btn-studio-save" onclick="saveDashboardLayout()">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Tata Letak
            </button>
            <button type="button" class="btn-studio-action btn-studio-reset" onclick="resetDashboardLayout()">
                <i class="fa-solid fa-rotate-left"></i> Reset ke Standar
            </button>
            <button type="button" class="btn-studio-action btn-studio-close" onclick="toggleStudioMode()">
                <i class="fa-solid fa-xmark"></i> Selesai
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" method="GET" class="filter-bar">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div class="filter-group-left">
            <select name="month" class="filter-select-btn" onchange="this.form.submit()">
                @for ($m = 1; $m <= 12; $m++)
                    @php
                        $dateObj = Carbon\Carbon::create(null, $m, 1);
                    @endphp
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ $dateObj->translatedFormat('F') }} {{ $year }}
                    </option>
                @endfor
            </select>

            <select name="employee_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">👥 Semua Petugas / SPG</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>

            <select name="location_id" class="filter-select-btn" onchange="this.form.submit()">
                <option value="">🏢 Semua Toko</option>
                @foreach($workLocations as $loc)
                    <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                        {{ $loc->name }}
                    </option>
                @endforeach
            </select>

            <input type="text" name="q" class="filter-search-input" placeholder="Cari petugas / toko..." value="{{ $search }}">
        </div>

        <div>
            <button type="submit" class="filter-select-btn" style="background: var(--brand-gradient); color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px var(--brand-glow);">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
        </div>
    </form>

    <!-- Dynamic 12-Column Dashboard Canvas (Sortable in Studio Mode) -->
    <div id="dashboard_canvas" class="dashboard-grid">
        @php
            $widgets = $dashboardConfig['widgets'] ?? [];
        @endphp

        @foreach($widgets as $w)
            @php
                $wId = $w['id'] ?? uniqid('w_');
                $type = $w['type'] ?? 'kpi_card';
                $colSpan = $w['col_span'] ?? 6;
                $color = $w['color'] ?? 'blue';
                $title = $w['title'] ?? 'Widget';
                $res = $widgetResults[$wId] ?? null;
            @endphp

            <div class="widget-card col-span-{{ $colSpan }}" id="{{ $wId }}" data-widget-id="{{ $wId }}" data-widget-json="{{ json_encode($w) }}">
                
                <!-- Studio Toolbar for this Widget -->
                <div class="studio-widget-toolbar">
                    <span class="studio-drag-handle" title="Tahan & Seret untuk memindahkan urutan"><i class="fa-solid fa-grip-vertical"></i></span>
                    <span style="font-weight: 700;">{{ $colSpan }}/12</span>
                    <button type="button" class="studio-btn-icon" title="Ubah Lebar Kolom" onclick="cycleWidgetWidth('{{ $wId }}')"><i class="fa-solid fa-arrows-left-right"></i></button>
                    <button type="button" class="studio-btn-icon" title="Edit Pengaturan Widget" onclick="openEditWidgetModal('{{ $wId }}')"><i class="fa-solid fa-gear"></i></button>
                    <button type="button" class="studio-btn-icon del" title="Hapus Widget" onclick="deleteWidget('{{ $wId }}')"><i class="fa-solid fa-trash"></i></button>
                </div>

                @if($type === 'kpi_card')
                    <!-- KPI Card Widget -->
                    <div class="widget-kpi-card color-{{ $color }}">
                        <div class="kpi-info-box">
                            <div class="kpi-label">{{ $title }}</div>
                            <div class="kpi-value">{{ $res['formatted_value'] ?? '0' }}</div>
                        </div>
                        <div class="kpi-icon-badge">
                            <i class="fa-solid {{ $w['icon'] ?? 'fa-chart-pie' }}"></i>
                        </div>
                    </div>

                @elseif(in_array($type, ['bar_chart', 'donut_chart', 'pie_chart', 'line_chart']))
                    <!-- ApexChart Widget -->
                    <div class="widget-content-card color-{{ $color }}">
                        <div class="widget-card-header">
                            <div class="widget-card-title">
                                <i class="fa-solid {{ $type === 'bar_chart' ? 'fa-chart-column' : ($type === 'line_chart' ? 'fa-chart-line' : 'fa-chart-pie') }}" style="color: var(--brand-primary);"></i>
                                {{ $title }}
                            </div>
                            <div class="widget-card-sub">
                                Total: <strong>{{ number_format($res['total'] ?? 0) }}</strong>
                            </div>
                        </div>
                        <div id="chart_{{ $wId }}" style="min-height: 260px;"></div>
                    </div>

                @elseif($type === 'breakdown_table')
                    <!-- Top Breakdown Summary Pivot Widget -->
                    <div class="widget-content-card color-{{ $color }}">
                        <div class="widget-card-header">
                            <div class="widget-card-title">
                                <i class="fa-solid fa-ranking-star" style="color: var(--brand-primary);"></i>
                                {{ $title }}
                            </div>
                            <div class="widget-card-sub">Top 10 Data</div>
                        </div>
                        @if(!empty($res['groups']) && count($res['groups']) > 0)
                            @php
                                $maxVal = max(array_values($res['groups'])) ?: 1;
                            @endphp
                            <ul class="breakdown-list">
                                @foreach($res['groups'] as $gLabel => $gVal)
                                    @php $pct = round(($gVal / $maxVal) * 100); @endphp
                                    <li class="breakdown-item">
                                        <div style="font-weight: 700; width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $gLabel }}">{{ $gLabel }}</div>
                                        <div class="breakdown-bar-bg">
                                            <div class="breakdown-bar-fill" style="width: {{ $pct }}%;"></div>
                                        </div>
                                        <div style="font-weight: 800; color: var(--text-heading); width: 60px; text-align: right;">{{ number_format($gVal) }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); font-size: 0.85rem;">
                                Belum ada data untuk periode ini.
                            </div>
                        @endif
                    </div>

                @elseif($type === 'data_table')
                    <!-- Custom Submissions Data Table Widget -->
                    <div class="table-container-card">
                        <div class="widget-card-header" style="margin-bottom: 0.85rem;">
                            <div class="widget-card-title">
                                <i class="fa-solid fa-table-list" style="color: var(--brand-primary);"></i>
                                {{ $title }}
                            </div>
                            <div class="widget-card-sub">{{ $submissions->total() }} Data Submission</div>
                        </div>

                        @if($submissions->isNotEmpty())
                            <div style="overflow-x: auto;">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px; text-align: center;">No</th>
                                            <th style="width: 140px;">No. Laporan</th>
                                            <th>Tanggal & Waktu</th>
                                            <th>Petugas (SPG/MD)</th>
                                            <th>Toko / Outlet</th>
                                            <th style="text-align: center;">Validasi GPS</th>
                                            <th style="text-align: center;">Status</th>
                                            <th style="text-align: right; width: 140px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($submissions as $idx => $sub)
                                            @php
                                                $status = $sub->status ?? 'pending';
                                                $storeName = $sub->workLocation?->name ?? $sub->itineraryItem?->destination ?? $sub->store_name ?? 'Kunjungan Toko';
                                            @endphp
                                            <tr>
                                                <td style="color: var(--text-muted); font-weight: 700; text-align: center;">
                                                    {{ $submissions->firstItem() + $idx }}
                                                </td>
                                                <td>
                                                    <span class="template-code-pill" style="font-size: 0.78rem; color: #0F52BA; background: rgba(15, 82, 186, 0.08); border-color: rgba(15, 82, 186, 0.2);">
                                                        {{ $sub->submission_code }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--text-heading);">
                                                        {{ $sub->submitted_at ? $sub->submitted_at->translatedFormat('d M Y') : '-' }}
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        {{ $sub->submitted_at ? $sub->submitted_at->format('H:i:s') : '-' }} WIB
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--text-heading);">
                                                        {{ $sub->employee?->full_name ?? $sub->employee?->name ?? 'Petugas' }}
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        NIK: {{ $sub->employee?->nik ?? '-' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--text-heading);">
                                                        {{ $storeName }}
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                        {{ $sub->employee?->branch?->name ?? ($sub->workLocation?->address ? \Illuminate\Support\Str::limit($sub->workLocation->address, 35) : '-') }}
                                                    </div>
                                                </td>
                                                <td style="text-align: center;">
                                                    @if($sub->is_within_radius)
                                                        <span style="font-size: 0.74rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-circle-check"></i> Valid
                                                        </span>
                                                    @else
                                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.65rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-triangle-exclamation"></i> Luar Radius
                                                        </span>
                                                    @endif
                                                </td>
                                                <td style="text-align: center;">
                                                    @if(in_array($status, ['approved', 'verified']))
                                                        <span style="font-size: 0.74rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                                            Terverifikasi
                                                        </span>
                                                    @elseif($status === 'rejected')
                                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                                            Ditolak
                                                        </span>
                                                    @else
                                                        <span style="font-size: 0.74rem; font-weight: 700; color: #b45309; background: #fef3c7; padding: 0.25rem 0.65rem; border-radius: 8px;">
                                                            Menunggu
                                                        </span>
                                                    @endif
                                                </td>
                                                <td style="text-align: right;">
                                                    <a href="{{ route('portal.report.submission', ['code' => $template->code, 'id' => $sub->id, 'p' => $tenantPrincipal->id]) }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.45rem 0.85rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; background: #f1f5f9; color: #0F52BA; border: 1px solid #cbd5e1; transition: all 0.15s ease;" onmouseover="this.style.background='#0F52BA'; this.style.color='#fff';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#0F52BA';">
                                                        <i class="fa-solid fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top: 1.25rem;">
                                {{ $submissions->appends(request()->query())->links('portal.pagination') }}
                            </div>
                        @else
                            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-heading);">Belum Ada Data Laporan Masuk</div>
                                <p style="font-size: 0.85rem; max-width: 420px; margin: 0.35rem auto 0;">
                                    Data submission untuk formulir <strong>{{ $template->title }}</strong> akan otomatis terisi saat petugas SPG/MD mengirimkan laporan melalui aplikasi mobile.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    <!-- MODAL: TAMBAH / EDIT WIDGET STUDIO -->
    <div id="modalWidgetConfig" class="portal-modal-overlay">
        <div class="portal-modal-card">
            <div class="portal-modal-header">
                <h3 id="modal_widget_title" class="portal-modal-title">
                    <i class="fa-solid fa-cubes" style="color: #6366f1;"></i> Konfigurasi Widget Studio
                </h3>
                <button type="button" class="btn-close-modal" onclick="closeWidgetModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="formWidgetConfig" onsubmit="handleWidgetFormSubmit(event)" class="portal-modal-body">
                <input type="hidden" id="cfg_widget_id" value="">

                <div class="form-group-row">
                    <label class="form-label-custom">Tipe Komponen / Widget <span style="color: #ef4444;">*</span></label>
                    <select id="cfg_type" class="form-select-custom" onchange="handleWidgetTypeChange(this.value)">
                        <option value="kpi_card">📊 Kartu Metrik / Angka KPI (Stat Card)</option>
                        <option value="bar_chart">📊 Grafik Batang (Bar / Column Chart)</option>
                        <option value="donut_chart">🍩 Grafik Donut / Lingkaran (Donut Chart)</option>
                        <option value="line_chart">📈 Grafik Garis / Tren Waktu (Line Chart)</option>
                        <option value="breakdown_table">📑 Tabel Peringkat / Top Breakdown</option>
                        <option value="data_table">📋 Tabel Rincian Data Submission</option>
                    </select>
                </div>

                <div class="form-group-row">
                    <label class="form-label-custom">Judul Widget <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="cfg_title" class="form-input-custom" placeholder="Contoh: Total Produk Terjual" required>
                </div>

                <div class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Lebar Kolom Grid</label>
                        <select id="cfg_col_span" class="form-select-custom">
                            <option value="3">3 Kolom (25% - Seperempat Lebar)</option>
                            <option value="4">4 Kolom (33% - Sepertiga Lebar)</option>
                            <option value="6" selected>6 Kolom (50% - Setengah Lebar)</option>
                            <option value="8">8 Kolom (66% - Dua Pertiga Lebar)</option>
                            <option value="12">12 Kolom (100% - Lebar Penuh)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-custom">Tema / Warna Aksen</label>
                        <select id="cfg_color" class="form-select-custom">
                            <option value="blue">Biru (Blue)</option>
                            <option value="emerald">Hijau (Emerald)</option>
                            <option value="purple">Ungu (Purple)</option>
                            <option value="orange">Oranye (Orange)</option>
                            <option value="rose">Merah (Rose)</option>
                            <option value="amber">Kuning / Amber</option>
                            <option value="indigo">Indigo</option>
                        </select>
                    </div>
                </div>

                <!-- Dimension & Metric Settings (for Charts / KPIs) -->
                <div id="row_dimension_metric" class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Kelompokkan Berdasarkan (Dimensi / X-Axis)</label>
                        <select id="cfg_dim_field" class="form-select-custom">
                            <optgroup label="Sistem / Pengiriman">
                                <option value="_submitted_date">📅 Tanggal Laporan Harian</option>
                                <option value="_employee">👥 Petugas / SPG / MD</option>
                                <option value="_store">🏢 Toko / Outlet</option>
                                <option value="_status">Status Verifikasi</option>
                            </optgroup>
                            <optgroup label="Field Formulir Laporan">
                                @foreach($template->fields as $f)
                                    <option value="{{ $f->field_name }}">{{ $f->field_label }} ({{ $f->field_type }})</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-custom">Field Metrik / Nilai (Ukuran)</label>
                        <select id="cfg_metric_field" class="form-select-custom">
                            <optgroup label="Metrik Bawaan">
                                <option value="_submission">Jumlah Laporan (Count)</option>
                                <option value="_unique_store">Jumlah Toko Unik</option>
                                <option value="_unique_employee">Jumlah Petugas Unik</option>
                            </optgroup>
                            <optgroup label="Field Angka Formulir">
                                @foreach($template->fields as $f)
                                    @if(in_array($f->field_type, ['number', 'integer', 'currency', 'price', 'percentage', 'rating']))
                                        <option value="{{ $f->field_name }}">{{ $f->field_label }} (Nilai Angka)</option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div id="row_kpi_extras" class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Fungsi Perhitungan</label>
                        <select id="cfg_aggregation" class="form-select-custom">
                            <option value="COUNT">COUNT (Jumlah Kemunculan)</option>
                            <option value="SUM">SUM (Penjumlahan Nilai)</option>
                            <option value="AVG">AVG (Rata-Rata)</option>
                            <option value="MAX">MAX (Nilai Tertinggi)</option>
                            <option value="MIN">MIN (Nilai Terendah)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label-custom">Icon Widget (KPI)</label>
                        <select id="cfg_icon" class="form-select-custom">
                            <option value="fa-chart-pie">Pie Chart (fa-chart-pie)</option>
                            <option value="fa-file-invoice">Laporan (fa-file-invoice)</option>
                            <option value="fa-store">Toko (fa-store)</option>
                            <option value="fa-users">Petugas (fa-users)</option>
                            <option value="fa-boxes-stacked">Stok / Barang (fa-boxes-stacked)</option>
                            <option value="fa-cart-shopping">Penjualan (fa-cart-shopping)</option>
                            <option value="fa-money-bill-wave">Uang / Omset (fa-money-bill-wave)</option>
                            <option value="fa-triangle-exclamation">Peringatan / OOS (fa-triangle-exclamation)</option>
                            <option value="fa-calendar-days">Kalender (fa-calendar-days)</option>
                        </select>
                    </div>
                </div>

                <div id="row_formatting" class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Awalan / Prefix</label>
                        <input type="text" id="cfg_prefix" class="form-input-custom" placeholder="Contoh: Rp ">
                    </div>
                    <div>
                        <label class="form-label-custom">Akhiran / Satuan (Suffix)</label>
                        <input type="text" id="cfg_suffix" class="form-input-custom" placeholder="Contoh: Pcs / Toko / %">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="filter-select-btn" onclick="closeWidgetModal()">Batal</button>
                    <button type="submit" class="btn-studio-action btn-studio-add" style="padding: 0.6rem 1.25rem;">
                        <i class="fa-solid fa-check"></i> Simpan Widget
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    var isStudioMode = false;
    var sortableInstance = null;
    var currentDashboardConfig = @json($dashboardConfig);
    var widgetResults = @json($widgetResults);
    var templateCode = @json($template->code);
    var tenantPrincipalId = @json($tenantPrincipal->id);
    var csrfToken = '{{ csrf_token() }}';

    // Inisialisasi Grafik ApexCharts
    document.addEventListener('DOMContentLoaded', function () {
        initAllCharts();
    });

    function initAllCharts() {
        var widgets = currentDashboardConfig.widgets || [];
        widgets.forEach(function (w) {
            if (['bar_chart', 'donut_chart', 'pie_chart', 'line_chart'].includes(w.type)) {
                var el = document.getElementById('chart_' + w.id);
                if (el && widgetResults[w.id]) {
                    renderApexChart(w, widgetResults[w.id], el);
                }
            }
        });
    }

    function renderApexChart(widget, res, el) {
        var categories = res.categories || [];
        var seriesData = res.series || [];
        var type = widget.type;
        var themeColor = getThemeHexColor(widget.color || 'blue');

        var options = {
            chart: {
                height: 280,
                type: type === 'bar_chart' ? 'bar' : (type === 'line_chart' ? 'area' : 'donut'),
                fontFamily: 'Outfit, sans-serif',
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 400 }
            },
            colors: type === 'donut_chart' || type === 'pie_chart'
                ? ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899', '#06B6D4', '#64748B']
                : [themeColor],
            dataLabels: { enabled: type === 'donut_chart' || type === 'pie_chart' },
            stroke: { curve: 'smooth', width: type === 'line_chart' ? 3 : 0 },
            fill: {
                type: type === 'line_chart' ? 'gradient' : 'solid',
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100] }
            },
            grid: { borderColor: '#f1f5f9' },
            tooltip: {
                theme: 'light',
                y: {
                    formatter: function (val) {
                        return (widget.prefix || '') + Number(val).toLocaleString('id-ID') + (widget.suffix || '');
                    }
                }
            }
        };

        if (type === 'donut_chart' || type === 'pie_chart') {
            options.series = seriesData;
            options.labels = categories;
            options.legend = { position: 'bottom', fontSize: '12px' };
        } else {
            options.series = [{ name: widget.title || 'Nilai', data: seriesData }];
            options.xaxis = {
                categories: categories,
                labels: { style: { fontSize: '11px', colors: '#64748B' } }
            };
        }

        var chart = new ApexCharts(el, options);
        chart.render();
    }

    function getThemeHexColor(c) {
        var map = {
            blue: '#2563EB',
            emerald: '#059669',
            purple: '#7C3AED',
            orange: '#EA580C',
            rose: '#E11D48',
            amber: '#D97706',
            indigo: '#4F46E5'
        };
        return map[c] || '#2563EB';
    }

    // Toggle Studio Mode (Odoo Studio Concept)
    function toggleStudioMode() {
        isStudioMode = !isStudioMode;
        var bar = document.getElementById('odoo_studio_bar');
        var canvas = document.getElementById('dashboard_canvas');
        var btn = document.getElementById('btn_toggle_studio');
        var btnText = document.getElementById('studio_btn_text');

        if (isStudioMode) {
            bar.classList.add('show');
            canvas.classList.add('studio-active-grid');
            btn.classList.add('active');
            btnText.textContent = '👁️ Keluar Studio';

            // Init SortableJS for drag and drop
            if (!sortableInstance) {
                sortableInstance = new Sortable(canvas, {
                    animation: 200,
                    handle: '.studio-drag-handle',
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        syncConfigFromDomOrder();
                    }
                });
            }
        } else {
            bar.classList.remove('show');
            canvas.classList.remove('studio-active-grid');
            btn.classList.remove('active');
            btnText.textContent = '🎨 Studio Dashboard';
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }
        }
    }

    function syncConfigFromDomOrder() {
        var cards = document.querySelectorAll('#dashboard_canvas .widget-card');
        var newWidgets = [];
        cards.forEach(function (card) {
            var raw = card.getAttribute('data-widget-json');
            if (raw) {
                try {
                    newWidgets.push(JSON.parse(raw));
                } catch (e) {}
            }
        });
        currentDashboardConfig.widgets = newWidgets;
    }

    // Cycle Width: 3 -> 4 -> 6 -> 8 -> 12 -> 3
    function cycleWidgetWidth(wId) {
        var card = document.getElementById(wId);
        if (!card) return;
        var raw = card.getAttribute('data-widget-json');
        var w = raw ? JSON.parse(raw) : null;
        if (!w) return;

        var currentSpan = w.col_span || 6;
        var nextSpan = 6;
        if (currentSpan === 3) nextSpan = 4;
        else if (currentSpan === 4) nextSpan = 6;
        else if (currentSpan === 6) nextSpan = 8;
        else if (currentSpan === 8) nextSpan = 12;
        else if (currentSpan === 12) nextSpan = 3;

        w.col_span = nextSpan;
        card.className = card.className.replace(/col-span-\d+/, 'col-span-' + nextSpan);
        card.setAttribute('data-widget-json', JSON.stringify(w));

        var spanBadge = card.querySelector('.studio-widget-toolbar span:nth-child(2)');
        if (spanBadge) spanBadge.textContent = nextSpan + '/12';

        syncConfigFromDomOrder();
    }

    function deleteWidget(wId) {
        if (!confirm('Apakah Anda yakin ingin menghapus widget ini dari dashboard?')) return;
        var card = document.getElementById(wId);
        if (card) card.remove();
        syncConfigFromDomOrder();
    }

    // Modal Add / Edit Widget
    function openAddWidgetModal() {
        document.getElementById('modal_widget_title').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: #6366f1;"></i> Tambah Widget Baru';
        document.getElementById('cfg_widget_id').value = '';
        document.getElementById('cfg_type').value = 'kpi_card';
        document.getElementById('cfg_title').value = '';
        document.getElementById('cfg_col_span').value = '6';
        document.getElementById('cfg_color').value = 'blue';
        document.getElementById('cfg_aggregation').value = 'COUNT';
        document.getElementById('cfg_prefix').value = '';
        document.getElementById('cfg_suffix').value = '';
        handleWidgetTypeChange('kpi_card');
        document.getElementById('modalWidgetConfig').classList.add('active');
    }

    function openEditWidgetModal(wId) {
        var card = document.getElementById(wId);
        if (!card) return;
        var raw = card.getAttribute('data-widget-json');
        var w = raw ? JSON.parse(raw) : null;
        if (!w) return;

        document.getElementById('modal_widget_title').innerHTML = '<i class="fa-solid fa-gear" style="color: #6366f1;"></i> Edit Pengaturan Widget';
        document.getElementById('cfg_widget_id').value = w.id || wId;
        document.getElementById('cfg_type').value = w.type || 'kpi_card';
        document.getElementById('cfg_title').value = w.title || '';
        document.getElementById('cfg_col_span').value = w.col_span || 6;
        document.getElementById('cfg_color').value = w.color || 'blue';
        document.getElementById('cfg_dim_field').value = w.dimension_field || '_submitted_date';
        document.getElementById('cfg_metric_field').value = w.metric_field || '_submission';
        document.getElementById('cfg_aggregation').value = w.aggregation || 'COUNT';
        document.getElementById('cfg_icon').value = w.icon || 'fa-chart-pie';
        document.getElementById('cfg_prefix').value = w.prefix || '';
        document.getElementById('cfg_suffix').value = w.suffix || '';

        handleWidgetTypeChange(w.type || 'kpi_card');
        document.getElementById('modalWidgetConfig').classList.add('active');
    }

    function closeWidgetModal() {
        document.getElementById('modalWidgetConfig').classList.remove('active');
    }

    function handleWidgetTypeChange(type) {
        var rowDim = document.getElementById('row_dimension_metric');
        var rowKpi = document.getElementById('row_kpi_extras');
        var rowFmt = document.getElementById('row_formatting');

        if (type === 'kpi_card') {
            rowDim.style.display = 'grid';
            rowKpi.style.display = 'grid';
            rowFmt.style.display = 'grid';
        } else if (['bar_chart', 'donut_chart', 'pie_chart', 'line_chart', 'breakdown_table'].includes(type)) {
            rowDim.style.display = 'grid';
            rowKpi.style.display = 'none';
            rowFmt.style.display = 'grid';
        } else {
            rowDim.style.display = 'none';
            rowKpi.style.display = 'none';
            rowFmt.style.display = 'none';
        }
    }

    function handleWidgetFormSubmit(e) {
        e.preventDefault();
        var wId = document.getElementById('cfg_widget_id').value || ('w_' + Date.now());
        var type = document.getElementById('cfg_type').value;
        var title = document.getElementById('cfg_title').value;
        var colSpan = parseInt(document.getElementById('cfg_col_span').value) || 6;
        var color = document.getElementById('cfg_color').value;
        var dimField = document.getElementById('cfg_dim_field').value;
        var metricField = document.getElementById('cfg_metric_field').value;
        var aggregation = document.getElementById('cfg_aggregation').value;
        var icon = document.getElementById('cfg_icon').value;
        var prefix = document.getElementById('cfg_prefix').value;
        var suffix = document.getElementById('cfg_suffix').value;

        var widgetObj = {
            id: wId,
            type: type,
            title: title,
            col_span: colSpan,
            color: color,
            dimension_field: dimField,
            metric_field: metricField,
            aggregation: aggregation,
            icon: icon,
            prefix: prefix,
            suffix: suffix
        };

        var widgets = currentDashboardConfig.widgets || [];
        var idx = widgets.findIndex(function (w) { return w.id === wId; });
        if (idx >= 0) {
            widgets[idx] = widgetObj;
        } else {
            widgets.push(widgetObj);
        }
        currentDashboardConfig.widgets = widgets;

        closeWidgetModal();
        saveDashboardLayout(true);
    }

    // Save Dashboard Layout to Backend via AJAX
    function saveDashboardLayout(autoReload) {
        syncConfigFromDomOrder();

        fetch('/portal/report/' + templateCode + '/dashboard-config?p=' + tenantPrincipalId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                dashboard_config: currentDashboardConfig
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                alert('✅ Tata letak dashboard laporan berhasil disimpan!');
                window.location.reload();
            } else {
                alert('❌ Gagal menyimpan tata letak: ' + (data.message || 'Error'));
            }
        })
        .catch(function (err) {
            alert('❌ Terjadi kesalahan jaringan saat menyimpan tata letak.');
        });
    }

    // Reset Dashboard Layout to Default
    function resetDashboardLayout() {
        if (!confirm('Kembalikan tata letak dashboard laporan ini ke tampilan standar bawaan?')) return;

        fetch('/portal/report/' + templateCode + '/dashboard-reset?p=' + tenantPrincipalId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                alert('✅ Dashboard berhasil dikembalikan ke tampilan standar!');
                window.location.reload();
            } else {
                alert('❌ Gagal mereset: ' + (data.message || 'Error'));
            }
        })
        .catch(function (err) {
            alert('❌ Terjadi kesalahan saat mereset dashboard.');
        });
    }
</script>
@endpush
