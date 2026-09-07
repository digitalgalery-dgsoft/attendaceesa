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
    .col-span-10 { grid-column: span 10; }
    .col-span-9  { grid-column: span 9; }
    .col-span-8  { grid-column: span 8; }
    .col-span-7  { grid-column: span 7; }
    .col-span-6  { grid-column: span 6; }
    .col-span-5  { grid-column: span 5; }
    .col-span-4  { grid-column: span 4; }
    .col-span-3  { grid-column: span 3; }
    .col-span-2  { grid-column: span 2; }

    @media (max-width: 1024px) {
        .col-span-2, .col-span-3, .col-span-4 { grid-column: span 6; }
        .col-span-5, .col-span-7, .col-span-8, .col-span-9, .col-span-10 { grid-column: span 12; }
    }

    @media (max-width: 640px) {
        .col-span-2, .col-span-3, .col-span-4, .col-span-5, .col-span-6, .col-span-7, .col-span-8, .col-span-9, .col-span-10 { grid-column: span 12; }
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

    .ytd-comparison-table thead th {
        background: var(--brand-primary) !important;
        color: #ffffff !important;
        border-bottom: none !important;
        font-weight: 800 !important;
        letter-spacing: 0.5px;
    }

    .ytd-store-table thead th {
        background: var(--brand-primary) !important;
        color: #ffffff !important;
        border-bottom: none !important;
        font-weight: 800 !important;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .ytd-tab-btn {
        border: none;
        padding: 0.5rem 1.15rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: transparent;
        color: #64748b;
    }

    .ytd-tab-btn.active {
        background: var(--brand-primary) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(15, 82, 186, 0.25);
    }

    .ytd-tab-btn:not(.active):hover {
        background: #ffffff;
        color: var(--brand-primary);
    }

    /* CBP Custom Dashboard Styles */
    .cbp-main-nav {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 6px;
        display: inline-flex;
        gap: 6px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }
    .cbp-nav-btn {
        border: none;
        padding: 0.65rem 1.35rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: transparent;
        color: #64748b;
    }
    .cbp-nav-btn.active {
        background: var(--brand-primary) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px var(--brand-glow);
    }
    .cbp-nav-btn:not(.active):hover {
        background: #f1f5f9;
        color: var(--text-heading);
    }
    .custom-cbp-wrapper {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .cbp-pane-container {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .cbp-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .cbp-kpi-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.15rem 1.35rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        min-width: 0;
    }
    .cbp-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .cbp-kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .cbp-sec-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.5rem;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .cbp-sec-header {
        background: #f8fafc;
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }
    .cbp-sec-title {
        font-size: 1.08rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .cbp-benchmark-tag {
        font-size: 0.75rem;
        font-weight: 800;
        background: #dbeafe;
        color: #1d4ed8;
        padding: 0.25rem 0.65rem;
        border-radius: 6px;
        border: 1px solid #bfdbfe;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .cbp-toggle-group {
        background: #e2e8f0;
        padding: 3px;
        border-radius: 8px;
        display: inline-flex;
        gap: 3px;
    }
    .cbp-toggle-btn {
        border: none;
        padding: 0.35rem 0.85rem;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        background: transparent;
        color: #475569;
        transition: all 0.15s ease;
    }
    .cbp-toggle-btn.active {
        background: #ffffff;
        color: var(--brand-primary);
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }
    .cbp-table th {
        background: #f8fafc;
        font-size: 0.76rem;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.85rem 0.95rem;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .cbp-table td {
        padding: 0.8rem 0.95rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.84rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .cbp-row-an {
        background: #f0f7ff !important;
    }
    .cbp-row-an:hover td {
        background: #e0effe !important;
    }
    .cbp-pill-bm {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        font-weight: 800;
        font-size: 0.8rem;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .cbp-pill-cheaper {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8rem;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }
    .cbp-pill-expensive {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8rem;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .cbp-brand-badge {
        font-size: 0.72rem;
        font-weight: 800;
        padding: 0.15rem 0.45rem;
        border-radius: 4px;
        display: inline-block;
    }
    .cbp-brand-an {
        background: #1e40af;
        color: #ffffff;
    }
    .cbp-brand-comp {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    /* CBP Raw Data Table (Menggunakan Design System Bawaan Portal) */
    .cbp-raw-viewport {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-height: 75vh;
        border-top: 1px solid var(--border-color);
        background: #ffffff;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .cbp-raw-table {
        width: max-content;
        min-width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .cbp-raw-table th {
        background: #f8fafc;
        font-size: 0.74rem;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem 0.85rem;
        border-bottom: 1px solid var(--border-color);
        border-right: 1px solid #f1f5f9;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .cbp-raw-table thead tr:nth-child(2) th {
        top: 36px;
    }
    .cbp-raw-table td {
        padding: 0.7rem 0.85rem;
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f8fafc;
        white-space: nowrap;
        vertical-align: middle;
        font-size: 0.82rem;
    }
    .cbp-raw-table tr:hover td {
        background-color: #f8fafc;
    }
    .cell-peach-portal {
        background-color: #fff7ed !important;
    }
    .excel-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .excel-page-btn:hover {
        background: #f1f5f9;
        color: #0F52BA;
        border-color: #0F52BA;
    }
    .excel-page-btn.active {
        background: #0F52BA;
        color: #ffffff;
        border-color: #0F52BA;
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
            @if((!isset($isCbpReport) || !$isCbpReport) && (!isset($isOfftakeReport) || !$isOfftakeReport) && (!isset($isStockReport) || !$isStockReport) && (!isset($isOosReport) || !$isOosReport) && (!isset($isDailyMaintenanceReport) || !$isDailyMaintenanceReport) && (!isset($isCustomerDbReport) || !$isCustomerDbReport))
            <button type="button" class="btn-studio-toggle" id="btn_toggle_studio" onclick="toggleStudioMode()">
                <i class="fa-solid fa-layer-group"></i>
                <span id="studio_btn_text">🎨 Studio Dashboard</span>
            </button>
            @endif

            @if(isset($isCustomerDbReport) && $isCustomerDbReport)
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'cust_raw', 'p' => $tenantPrincipal->id])) }}" class="btn-export-excel">
                    <i class="fa-solid fa-file-excel"></i>
                    Export Data Mentah (CSV)
                </a>
            @elseif(isset($isDailyMaintenanceReport) && $isDailyMaintenanceReport)
                <a href="{{ route('portal.report.export', array_merge(request()->query(), ['code' => $template->code, 'export_type' => 'dm_raw', 'p' => $tenantPrincipal->id])) }}" class="btn-export-excel">
                    <i class="fa-solid fa-file-excel"></i>
                    Export Data Mentah (CSV)
                </a>
            @else
                <a href="{{ route('portal.report.export', ['code' => $template->code, 'start_month' => $startMonth, 'start_year' => $startYear, 'end_month' => $endMonth, 'end_year' => $endYear, 'region' => $selectedRegion, 'area_id' => $selectedAreaId, 'location_id' => $selectedLocationId, 'p' => $tenantPrincipal->id]) }}" class="btn-export-excel">
                    <i class="fa-solid fa-file-excel"></i>
                    Export Rekap CSV / Excel
                </a>
            @endif
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

    <!-- Enhanced Filter Bar (Range Bulan Awal - Akhir, Region, Area, Store / Toko) -->
    <form action="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" method="GET" class="filter-bar" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; width: 100%; max-width: 100%; min-width: 0;">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        @if(isset($isDailyMaintenanceReport) && $isDailyMaintenanceReport)
            <input type="hidden" name="tab" value="{{ $activeTab ?? 'summary' }}">
        @endif
        
        <!-- Filter Fields Container (Aligned firmly to the Left) -->
        <div class="filter-group-left" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; flex: 1; min-width: 0; justify-content: flex-start;">
            
            <!-- Rentang Bulan Awal s/d Bulan Akhir -->
            <div style="display: inline-flex; align-items: center; gap: 0.35rem; background: #f8fafc; padding: 0.35rem 0.65rem; border: 1px solid var(--border-color); border-radius: 10px; flex-wrap: wrap;">
                <i class="fa-regular fa-calendar-days" style="color: var(--brand-primary); font-size: 0.88rem;"></i>
                <span style="font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Dari:</span>
                <select name="start_month" class="filter-select-btn" style="padding: 0.25rem 0.45rem; border: none; background: transparent; font-weight: 700; font-size: 0.82rem;">
                    @for ($m = 1; $m <= 12; $m++)
                        @php $dateObj = Carbon\Carbon::create(null, $m, 1); @endphp
                        <option value="{{ $m }}" {{ $startMonth == $m ? 'selected' : '' }}>
                            {{ $dateObj->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="start_year" class="filter-select-btn" style="padding: 0.25rem 0.45rem; border: none; background: transparent; font-weight: 700; font-size: 0.82rem;">
                    @for ($y = Carbon\Carbon::now()->year + 1; $y >= 2023; $y--)
                        <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>

                <span style="font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 0 0.25rem;">s/d</span>

                <i class="fa-regular fa-calendar-check" style="color: #10b981; font-size: 0.88rem;"></i>
                <span style="font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Sampai:</span>
                <select name="end_month" class="filter-select-btn" style="padding: 0.25rem 0.45rem; border: none; background: transparent; font-weight: 700; font-size: 0.82rem;">
                    @for ($m = 1; $m <= 12; $m++)
                        @php $dateObj = Carbon\Carbon::create(null, $m, 1); @endphp
                        <option value="{{ $m }}" {{ $endMonth == $m ? 'selected' : '' }}>
                            {{ $dateObj->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="end_year" class="filter-select-btn" style="padding: 0.25rem 0.45rem; border: none; background: transparent; font-weight: 700; font-size: 0.82rem;">
                    @for ($y = Carbon\Carbon::now()->year + 1; $y >= 2023; $y--)
                        <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <!-- Filter RSM (Region) -->
            <div style="position: relative;">
                <select name="region" id="filter_region" class="filter-select-btn" onchange="onRegionFilterChange(this.value)" style="padding-left: 2rem;">
                    <option value="">🗺️ Semua RSM</option>
                    @foreach($regions as $r)
                        @php
                            $rArr = is_array($r) ? $r : (is_object($r) ? (array)$r : []);
                            $rStr = !empty($rArr) ? ($rArr['rsm_area'] ?? $rArr['regional'] ?? $rArr['region'] ?? '') : (is_scalar($r) ? (string)$r : '');
                            $rTrim = trim(preg_replace('/^(rsm|region)\s+/i', '', $rStr));
                            $rDisplay = !empty($rTrim) ? 'RSM ' . $rTrim : $rStr;
                        @endphp
                        <option value="{{ $rStr }}" {{ $selectedRegion == $rStr ? 'selected' : '' }}>
                            {{ $rDisplay }}
                        </option>
                    @endforeach
                </select>
                <i class="fa-solid fa-map-location-dot" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
            </div>

            <!-- Filter Area / Cabang -->
            <div style="position: relative;">
                <select name="area_id" id="filter_area" class="filter-select-btn" onchange="onAreaFilterChange(this.value)" style="padding-left: 2rem;">
                    <option value="">📍 Semua Area / Cabang</option>
                    @foreach($areas as $area)
                        @php
                            $aArr = is_array($area) ? $area : (is_object($area) ? (array)$area : ['id' => $area, 'name' => $area, 'region' => '']);
                            $aId = $aArr['id'] ?? (is_scalar($area) ? (string)$area : '');
                            $aName = $aArr['name'] ?? $aId;
                            $aRegion = $aArr['region'] ?? '';
                        @endphp
                        <option value="{{ $aId }}" data-region="{{ $aRegion }}" {{ (string)$selectedAreaId === (string)$aId ? 'selected' : '' }}>
                            {{ $aName }}
                        </option>
                    @endforeach
                </select>
                <i class="fa-solid fa-location-crosshairs" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
            </div>

            <!-- Filter Store / Toko -->
            <div style="position: relative;">
                <select name="location_id" id="filter_location" class="filter-select-btn" style="padding-left: 2rem; max-width: 250px;">
                    <option value="">🏢 Semua Store / Toko</option>
                    @foreach($workLocations as $loc)
                        @php
                            $lArr = is_array($loc) ? $loc : (is_object($loc) ? (array)$loc : ['id' => $loc, 'name' => $loc, 'region' => '', 'area' => '']);
                            $lId = $lArr['id'] ?? (is_scalar($loc) ? (string)$loc : '');
                            $lName = $lArr['name'] ?? $lId;
                            $lRegion = $lArr['region'] ?? '';
                            $lArea = $lArr['area'] ?? '';
                        @endphp
                        <option value="{{ $lId }}" data-region="{{ $lRegion }}" data-area="{{ strtoupper(trim($lArea)) }}" {{ (string)$selectedLocationId === (string)$lId ? 'selected' : '' }}>
                            {{ $lName }}
                        </option>
                    @endforeach
                </select>
                <i class="fa-solid fa-store" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
            </div>

            @if(isset($isDailyMaintenanceReport) && $isDailyMaintenanceReport)
                @if(!empty($machineTypes))
                <!-- Filter Tipe Mesin POST -->
                <div style="position: relative;">
                    <select name="machine_type" id="filter_machine_type" class="filter-select-btn" style="padding-left: 2rem;">
                        <option value="">⚙️ Semua Mesin</option>
                        @foreach($machineTypes as $mt)
                            <option value="{{ $mt }}" {{ ($selectedMachineType ?? '') === $mt ? 'selected' : '' }}>{{ $mt }}</option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-gears" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
                </div>
                @endif

                @if(!empty($categories))
                <!-- Filter Kategori Toko -->
                <div style="position: relative;">
                    <select name="category" id="filter_category" class="filter-select-btn" style="padding-left: 2rem;">
                        <option value="">🏷️ Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ ($selectedCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-tags" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
                </div>
                @endif
            @endif

            @if(isset($isStockReport) && $isStockReport)
                <!-- Filter Brand (Dulux & Catylac) -->
                <div style="position: relative;">
                    <select name="brand" id="filter_brand" class="filter-select-btn" style="padding-left: 2rem;">
                        <option value="ALL" {{ ($selectedBrand ?? 'ALL') === 'ALL' ? 'selected' : '' }}>🎨 Semua Brand (Dulux & Catylac)</option>
                        <option value="DULUX" {{ ($selectedBrand ?? '') === 'DULUX' ? 'selected' : '' }}>🔵 Dulux</option>
                        <option value="CATYLAC" {{ ($selectedBrand ?? '') === 'CATYLAC' ? 'selected' : '' }}>🟢 Catylac</option>
                    </select>
                    <i class="fa-solid fa-brush" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
                </div>
            @endif

            <!-- Search Input -->
            <div style="position: relative;">
                <input type="text" name="q" class="filter-search-input" placeholder="Cari data / toko..." value="{{ $search }}" style="padding-left: 2rem; width: 190px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.85rem;"></i>
            </div>
        </div>

        <!-- Action Buttons Group (Aligned to the Right) -->
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-shrink: 0; margin-left: auto;">
            <button type="submit" class="filter-select-btn" style="background: var(--brand-gradient); color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px var(--brand-glow); padding: 0.55rem 1.15rem; display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            @if($selectedRegion || $selectedAreaId || $selectedLocationId || (!empty($selectedBrand) && $selectedBrand !== 'ALL') || !empty($selectedMachineType) || !empty($selectedCategory) || $search || $startMonth != Carbon\Carbon::now()->month || $endMonth != Carbon\Carbon::now()->month)
                <a href="{{ route('portal.report.detail', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}" class="filter-select-btn" style="background: #f1f5f9; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            @endif
        </div>
    </form>

    <script>
    function onRegionFilterChange(regVal) {
        var areaSelect = document.getElementById('filter_area');
        var locSelect = document.getElementById('filter_location');
        if (!areaSelect || !locSelect) return;

        var currentAreaVal = areaSelect.value;
        var areaStillValid = false;

        for (var i = 0; i < areaSelect.options.length; i++) {
            var opt = areaSelect.options[i];
            if (!opt.value) continue;
            var optReg = opt.getAttribute('data-region') || '';
            if (!regVal || !optReg || optReg.toUpperCase() === regVal.toUpperCase()) {
                opt.hidden = false;
                opt.disabled = false;
                if (opt.value === currentAreaVal) areaStillValid = true;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        }
        if (currentAreaVal && !areaStillValid) {
            areaSelect.value = '';
        }

        onAreaFilterChange(areaSelect.value);
    }

    function onAreaFilterChange(areaVal) {
        var regSelect = document.getElementById('filter_region');
        var locSelect = document.getElementById('filter_location');
        if (!locSelect) return;

        var regVal = regSelect ? regSelect.value : '';
        var currentLocVal = locSelect.value;
        var locStillValid = false;

        for (var j = 0; j < locSelect.options.length; j++) {
            var opt = locSelect.options[j];
            if (!opt.value) continue;
            var optReg = opt.getAttribute('data-region') || '';
            var optArea = (opt.getAttribute('data-area') || '').toUpperCase();
            var matchReg = !regVal || !optReg || optReg.toUpperCase() === regVal.toUpperCase();
            var matchArea = !areaVal || !optArea || optArea === areaVal.toUpperCase();

            if (matchReg && matchArea) {
                opt.hidden = false;
                opt.disabled = false;
                if (opt.value === currentLocVal) locStillValid = true;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        }
        if (currentLocVal && !locStillValid) {
            locSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var regSelect = document.getElementById('filter_region');
        if (regSelect && regSelect.value) {
            onRegionFilterChange(regSelect.value);
        } else {
            var areaSelect = document.getElementById('filter_area');
            if (areaSelect && areaSelect.value) {
                onAreaFilterChange(areaSelect.value);
            }
        }
    });
    </script>

    @if(isset($isYtdReport) && $isYtdReport)
        <div class="widget-content-card" style="margin-bottom: 1.5rem; border: 2px solid var(--brand-primary); padding: 0; overflow: hidden;">
            <div class="widget-card-header" style="background: #f8fafc; padding: 1.15rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
                <div>
                    <div class="widget-card-title" style="font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-chart-column" style="color: var(--brand-primary); font-size: 1.35rem;"></i>
                        YTD Comparison Laporan (Volume Liter)
                    </div>
                    <div class="widget-card-sub" style="font-size: 0.85rem; margin-top: 0.25rem;">
                        Berdasarkan tanggal: 1 Jan - Akhir {{ Carbon\Carbon::create(null, $endMonth, 1)->translatedFormat('F') }} ({{ $endYear }} vs {{ $endYear - 1 }})
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div style="background: #e2e8f0; padding: 4px; border-radius: 10px; display: inline-flex; gap: 4px;">
                    <button type="button" class="ytd-tab-btn active" id="btn_ytd_product" onclick="switchYtdTab('product')">
                        <i class="fa-solid fa-boxes-stacked"></i> Berdasarkan Produk (Dulux / Catylac)
                    </button>
                    <button type="button" class="ytd-tab-btn" id="btn_ytd_store" onclick="switchYtdTab('store')">
                        <i class="fa-solid fa-store"></i> Berdasarkan Store / Toko
                    </button>
                </div>
            </div>

            <!-- Tab 1: Berdasarkan Produk (Dulux / Catylac) -->
            <div id="ytd_pane_product" class="ytd-pane" style="padding: 1.5rem;">
                <div class="dashboard-grid" style="margin-bottom: 0;">
                    <div class="col-span-7">
                        <div class="table-container-card" style="box-shadow: none; border: 1px solid var(--border-color); padding: 0; border-radius: 12px; overflow: hidden;">
                            <table class="custom-table ytd-comparison-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr style="background: var(--brand-primary);">
                                        <th style="background: var(--brand-primary) !important; color: #ffffff !important; border: none !important; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px; padding: 0.9rem 1rem;">DESKRIPSI</th>
                                        <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: right; border: none !important; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px; padding: 0.9rem 1rem;">YTD {{ $endYear }}<br><span style="font-size: 0.72rem; font-weight: 500; color: rgba(255,255,255,0.85);">(Tahun Berjalan)</span></th>
                                        <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: center; border: none !important; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px; padding: 0.9rem 1rem;">%</th>
                                        <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: right; border: none !important; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px; padding: 0.9rem 1rem;">YTD {{ $endYear - 1 }}<br><span style="font-size: 0.72rem; font-weight: 500; color: rgba(255,255,255,0.85);">(Tahun Sebelumnya)</span></th>
                                        <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: right; border: none !important; font-weight: 800; font-size: 0.8rem; letter-spacing: 0.5px; padding: 0.9rem 1rem;">GROWTH</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($ytdData['details']))
                                        @foreach($ytdData['details'] as $row)
                                            <tr>
                                                <td style="font-weight: 700; color: var(--brand-primary);">{{ $row['brand'] }}</td>
                                                <td style="text-align: right; font-weight: 700;">{{ number_format($row['cy_volume'], 2) }}</td>
                                                <td style="text-align: center; font-weight: 600;">{{ number_format($row['percentage'], 1) }}%</td>
                                                <td style="text-align: right; color: var(--text-muted);">{{ number_format($row['py_volume'], 2) }}</td>
                                                <td style="text-align: right; font-weight: 700; color: {{ $row['growth'] > 0 ? '#10b981' : ($row['growth'] < 0 ? '#ef4444' : 'var(--text-muted)') }};">
                                                    @if($row['growth'] > 0)<i class="fa-solid fa-arrow-trend-up"></i>@endif
                                                    @if($row['growth'] < 0)<i class="fa-solid fa-arrow-trend-down"></i>@endif
                                                    {{ number_format($row['growth'], 1) }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr style="background: #f1f5f9; border-top: 2px solid var(--border-color);">
                                            <td style="font-weight: 800; font-size: 0.95rem;">{{ $ytdData['total']['brand'] ?? 'Total Akzonobel' }}</td>
                                            <td style="text-align: right; font-weight: 800; font-size: 0.95rem;">{{ number_format($ytdData['total']['cy_volume'], 2) }}</td>
                                            <td style="text-align: center; font-weight: 800;">100%</td>
                                            <td style="text-align: right; font-weight: 800; color: var(--text-muted);">{{ number_format($ytdData['total']['py_volume'], 2) }}</td>
                                            <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: {{ $ytdData['total']['growth'] > 0 ? '#10b981' : ($ytdData['total']['growth'] < 0 ? '#ef4444' : 'var(--text-muted)') }};">
                                                @if($ytdData['total']['growth'] > 0)<i class="fa-solid fa-arrow-trend-up"></i>@endif
                                                @if($ytdData['total']['growth'] < 0)<i class="fa-solid fa-arrow-trend-down"></i>@endif
                                                {{ number_format($ytdData['total']['growth'], 1) }}%
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="5" style="text-align: center; padding: 2rem;">Belum ada data YTD untuk periode ini.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-span-5">
                        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.15rem; height: 100%; box-shadow: var(--shadow-sm); display: flex; flex-direction: column;">
                            <div style="font-weight: 700; font-size: 0.92rem; color: #1e293b; margin-bottom: 0.35rem; display: flex; align-items: center; justify-content: space-between;">
                                <span style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-chart-line" style="color: var(--brand-primary);"></i> Tren Offtake ({{ $endYear }} vs {{ $endYear - 1 }})
                                </span>
                                <span style="font-size: 0.74rem; font-weight: 600; color: #64748b;">
                                    Jan – {{ Carbon\Carbon::create(null, $endMonth, 1)->translatedFormat('M') }}
                                </span>
                            </div>
                            <div id="chart_ytd_comparison" style="min-height: 260px; flex-grow: 1;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Berdasarkan Store / Toko -->
            <div id="ytd_pane_store" class="ytd-pane" style="display: none; padding: 1.5rem;">
                <!-- Summary KPI Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display: flex; align-items: center; gap: 0.85rem;">
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(15, 82, 186, 0.12); color: var(--brand-primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Toko Aktif</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: #1e293b;">{{ number_format($ytdData['stores']['total']['count'] ?? 0) }} Toko</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display: flex; align-items: center; gap: 0.85rem;">
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(16, 185, 129, 0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-cubes-stacked"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase;">YTD {{ $endYear }} (Volume)</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: #1e293b;">{{ number_format($ytdData['stores']['total']['cy_volume'] ?? 0, 2) }} <span style="font-size: 0.78rem; font-weight: 500; color: #64748b;">Liter</span></div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display: flex; align-items: center; gap: 0.85rem;">
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(100, 116, 139, 0.12); color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase;">YTD {{ $endYear - 1 }} (Volume)</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: #1e293b;">{{ number_format($ytdData['stores']['total']['py_volume'] ?? 0, 2) }} <span style="font-size: 0.78rem; font-weight: 500; color: #64748b;">Liter</span></div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem; display: flex; align-items: center; gap: 0.85rem;">
                        @php
                            $stGrowth = $ytdData['stores']['total']['growth'] ?? 0;
                        @endphp
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: {{ $stGrowth >= 0 ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $stGrowth >= 0 ? '#10b981' : '#ef4444' }}; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid {{ $stGrowth >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Overall Growth</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: {{ $stGrowth >= 0 ? '#10b981' : '#ef4444' }};">
                                {{ number_format($stGrowth, 1) }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Top 10 Stores -->
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: #1e293b; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fa-solid fa-ranking-star" style="color: #f59e0b;"></i> Top 10 Store / Toko Tertinggi (Volume Liter YTD {{ $endYear }} vs {{ $endYear - 1 }})</span>
                        <span style="font-size: 0.75rem; font-weight: 500; color: #64748b;">Diurutkan dari volume terbesar</span>
                    </div>
                    <div id="chart_ytd_store" style="min-height: 340px;"></div>
                </div>

                <!-- Full Store Table with Live Search and CSV Export -->
                <div class="table-container-card" style="box-shadow: none; border: 1px solid var(--border-color); padding: 0; border-radius: 12px; overflow: hidden;">
                    <!-- Filter Toolbar -->
                    <div style="background: #f8fafc; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-grow: 1; max-width: 450px;">
                            <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8;"></i>
                            <input type="text" id="storeSearchInput" onkeyup="filterStoreTable()" placeholder="Cari nama toko, kode SAP, atau area..." style="width: 100%; padding: 0.45rem 0.85rem; border: 1px solid #cbd5e1; border-radius: 7px; font-size: 0.85rem; outline: none;">
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">
                                Menampilkan <span id="filteredStoreCount" style="font-weight: 800; color: var(--brand-primary);">{{ number_format(count($ytdData['stores']['details'] ?? [])) }}</span> dari {{ number_format(count($ytdData['stores']['details'] ?? [])) }} Toko
                            </span>
                            <button type="button" onclick="exportStoreYtdToCsv()" class="btn btn-sm" style="background: #10b981; color: #ffffff; font-weight: 700; border-radius: 7px; padding: 0.42rem 0.85rem; border: none; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);">
                                <i class="fa-solid fa-file-excel"></i> Export CSV
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Table -->
                    <div style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                        <table class="custom-table ytd-store-table" id="storeYtdTable" style="margin-bottom: 0;">
                            <thead>
                                <tr style="background: var(--brand-primary);">
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem; width: 60px; text-align: center;">NO</th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem;">NAMA TOKO / STORE</th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem; width: 140px;">REGION</th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem; width: 140px;">AREA</th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem; width: 110px;">CHANNEL</th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: right; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem;">YTD {{ $endYear }}<br><span style="font-size: 0.72rem; font-weight: 500; color: rgba(255,255,255,0.85);">(Tahun Berjalan)</span></th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: center; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem; width: 75px;">%</th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: right; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem;">YTD {{ $endYear - 1 }}<br><span style="font-size: 0.72rem; font-weight: 500; color: rgba(255,255,255,0.85);">(Tahun Sebelumnya)</span></th>
                                    <th style="background: var(--brand-primary) !important; color: #ffffff !important; text-align: right; border: none !important; font-weight: 800; font-size: 0.8rem; padding: 0.85rem 1rem; width: 100px;">GROWTH</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($ytdData['stores']['details']))
                                    @php $no = 1; @endphp
                                    @foreach($ytdData['stores']['details'] as $sRow)
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; color: #64748b; font-size: 0.8rem;">{{ $no++ }}</td>
                                            <td style="font-weight: 700; color: #1e293b;">
                                                {{ $sRow['store_name'] }}
                                            </td>
                                            <td style="font-size: 0.82rem; color: #475569; font-weight: 500;">{{ $sRow['region'] }}</td>
                                            <td style="font-size: 0.82rem; color: #64748b;">{{ $sRow['area'] }}</td>
                                            <td>
                                                <span style="background: #f1f5f9; color: #475569; font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 4px;">
                                                    {{ $sRow['channel'] }}
                                                </span>
                                            </td>
                                            <td style="text-align: right; font-weight: 700; color: var(--brand-primary);">{{ number_format($sRow['cy_volume'], 2) }}</td>
                                            <td style="text-align: center; font-weight: 600; font-size: 0.82rem;">{{ number_format($sRow['percentage'], 2) }}%</td>
                                            <td style="text-align: right; color: var(--text-muted);">{{ number_format($sRow['py_volume'], 2) }}</td>
                                            <td style="text-align: right; font-weight: 700; font-size: 0.85rem; color: {{ $sRow['growth'] > 0 ? '#10b981' : ($sRow['growth'] < 0 ? '#ef4444' : 'var(--text-muted)') }};">
                                                @if($sRow['growth'] > 0)<i class="fa-solid fa-arrow-trend-up"></i>@endif
                                                @if($sRow['growth'] < 0)<i class="fa-solid fa-arrow-trend-down"></i>@endif
                                                {{ number_format($sRow['growth'], 1) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr style="background: #f1f5f9; border-top: 2px solid var(--border-color);" class="no-filter">
                                        <td colspan="5" style="font-weight: 800; font-size: 0.95rem; text-align: right; padding-right: 1.5rem;">TOTAL KESELURUHAN ({{ count($ytdData['stores']['details']) }} TOKO):</td>
                                        <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: var(--brand-primary);">{{ number_format($ytdData['stores']['total']['cy_volume'], 2) }}</td>
                                        <td style="text-align: center; font-weight: 800;">100%</td>
                                        <td style="text-align: right; font-weight: 800; color: var(--text-muted);">{{ number_format($ytdData['stores']['total']['py_volume'], 2) }}</td>
                                        <td style="text-align: right; font-weight: 800; font-size: 0.95rem; color: {{ ($ytdData['stores']['total']['growth'] ?? 0) >= 0 ? '#10b981' : '#ef4444' }};">
                                            @if(($ytdData['stores']['total']['growth'] ?? 0) > 0)<i class="fa-solid fa-arrow-trend-up"></i>@endif
                                            @if(($ytdData['stores']['total']['growth'] ?? 0) < 0)<i class="fa-solid fa-arrow-trend-down"></i>@endif
                                            {{ number_format($ytdData['stores']['total']['growth'] ?? 0, 1) }}%
                                        </td>
                                    </tr>
                                @else
                                    <tr class="no-filter">
                                        <td colspan="9" style="text-align: center; padding: 2rem;">Belum ada data toko untuk periode ini.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- CBP EXECUTIVE DASHBOARD (DASHBOARD 1 & DASHBOARD 2) --}}
    @if(isset($isCbpReport) && $isCbpReport && !empty($cbpData))
        @include('portal.partials.cbp_dashboard', ['cbpData' => $cbpData])
    @endif

    {{-- OFFTAKE EXECUTIVE DASHBOARD (SHEET 2 PIVOT & SHEET 1 RAW DATA) --}}
    @if(isset($isOfftakeReport) && $isOfftakeReport && !empty($offtakeData))
        @include('portal.partials.offtake_dashboard', ['offtakeData' => $offtakeData])
    @endif

    {{-- STOCK END EXECUTIVE DASHBOARD (PIVOTABLE, SUMM SCM & RAW DATA) --}}
    @if(isset($isStockReport) && $isStockReport && !empty($stockData))
        @include('portal.partials.stock_dashboard', [
            'stockData' => $stockData,
            'monthlyCompareData' => $monthlyCompareData ?? [],
            'selectedBrand' => $selectedBrand ?? 'ALL',
            'activeTab' => $activeTab ?? 'monthly'
        ])
    @endif

    {{-- OUT OF STOCK (OOS) EXECUTIVE DASHBOARD (SUMMARY, WEEKLY PIVOT & RAW SUBMISSIONS) --}}
    @if(isset($isOosReport) && $isOosReport && !empty($oosData))
        @include('portal.partials.oos_dashboard', ['oosData' => $oosData])
    @endif

    {{-- DAILY MAINTENANCE EXECUTIVE DASHBOARD (SUMMARY, STORE MATRIX & RAW SUBMISSIONS) --}}
    @if(isset($isDailyMaintenanceReport) && $isDailyMaintenanceReport && !empty($dailyMaintenanceData))
        @include('portal.partials.daily_maintenance_dashboard', ['dmData' => $dailyMaintenanceData])
    @endif

    {{-- CUSTOMER DATABASE & CONSUMER INSIGHTS DASHBOARD (INSIGHTS, REGIONAL & RAW SUBMISSIONS) --}}
    @if(isset($isCustomerDbReport) && $isCustomerDbReport && !empty($customerDbData))
        @include('portal.partials.customer_database_dashboard', ['custData' => $customerDbData])
    @endif

    @if((!isset($isCbpReport) || !$isCbpReport) && (!isset($isOfftakeReport) || !$isOfftakeReport) && (!isset($isStockReport) || !$isStockReport) && (!isset($isOosReport) || !$isOosReport) && (!isset($isDailyMaintenanceReport) || !$isDailyMaintenanceReport) && (!isset($isCustomerDbReport) || !$isCustomerDbReport))
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
    @endif

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
                            <option value="2">2 Kolom (2/12 - 16.6% Lebar / Pasangan 10/12)</option>
                            <option value="3">3 Kolom (3/12 - 25% Lebar / Pasangan 9/12)</option>
                            <option value="4">4 Kolom (4/12 - 33.3% Lebar / Pasangan 8/12)</option>
                            <option value="6" selected>6 Kolom (6/12 - 50% Setengah Lebar)</option>
                            <option value="8">8 Kolom (8/12 - 66.6% Dua Pertiga Lebar / Pasangan 4/12)</option>
                            <option value="9">9 Kolom (9/12 - 75% Tiga Perempat Lebar / Pasangan 3/12)</option>
                            <option value="10">10 Kolom (10/12 - 83.3% Lebar / Pasangan 2/12)</option>
                            <option value="12">12 Kolom (12/12 - 100% Lebar Penuh)</option>
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
        if (!cards || cards.length === 0) return;

        var idMap = {};
        (currentDashboardConfig.widgets || []).forEach(function(w) {
            idMap[w.id] = w;
        });

        var orderedWidgets = [];
        cards.forEach(function (card) {
            var wId = card.getAttribute('data-widget-id');
            if (wId && idMap[wId]) {
                orderedWidgets.push(idMap[wId]);
            }
        });

        // Pastikan widget baru yang belum ada di DOM tetap disertakan
        (currentDashboardConfig.widgets || []).forEach(function(w) {
            if (!orderedWidgets.some(function(ow) { return ow.id === w.id; })) {
                orderedWidgets.push(w);
            }
        });

        if (orderedWidgets.length > 0) {
            currentDashboardConfig.widgets = orderedWidgets;
        }
    }

    // Cycle Width: 2 -> 3 -> 4 -> 6 -> 8 -> 10 -> 12 -> 2
    function cycleWidgetWidth(wId) {
        var card = document.getElementById(wId);
        if (!card) return;

        var currentSpan = 6;
        var wIndex = (currentDashboardConfig.widgets || []).findIndex(function(w) { return w.id === wId; });
        if (wIndex >= 0) {
            currentSpan = parseInt(currentDashboardConfig.widgets[wIndex].col_span) || 6;
        }

        var spans = [2, 3, 4, 6, 8, 10, 12];
        var currentIdx = spans.indexOf(currentSpan);
        var nextSpan = (currentIdx >= 0 && currentIdx < spans.length - 1) ? spans[currentIdx + 1] : spans[0];

        if (wIndex >= 0) {
            currentDashboardConfig.widgets[wIndex].col_span = nextSpan;
        }

        card.className = card.className.replace(/col-span-\d+/, 'col-span-' + nextSpan);
        var spanBadge = card.querySelector('.studio-widget-toolbar span:nth-child(2)');
        if (spanBadge) spanBadge.textContent = nextSpan + '/12';
    }

    function deleteWidget(wId) {
        if (!confirm('Apakah Anda yakin ingin menghapus widget ini dari dashboard?')) return;
        var card = document.getElementById(wId);
        if (card) card.remove();
        if (currentDashboardConfig.widgets) {
            currentDashboardConfig.widgets = currentDashboardConfig.widgets.filter(function(w) { return w.id !== wId; });
        }
    }

    // ====== YTD CHART & TAB FUNCTIONALITY ======
    function switchYtdTab(tab) {
        var btnProd = document.getElementById('btn_ytd_product');
        var btnStore = document.getElementById('btn_ytd_store');
        var paneProd = document.getElementById('ytd_pane_product');
        var paneStore = document.getElementById('ytd_pane_store');

        if (!btnProd || !btnStore || !paneProd || !paneStore) return;

        if (tab === 'product') {
            btnProd.classList.add('active');
            btnStore.classList.remove('active');
            paneProd.style.display = 'block';
            paneStore.style.display = 'none';
        } else {
            btnStore.classList.add('active');
            btnProd.classList.remove('active');
            paneStore.style.display = 'block';
            paneProd.style.display = 'none';

            setTimeout(function() {
                window.dispatchEvent(new Event('resize'));
                if (window.storeChartInstance) {
                    window.storeChartInstance.resize();
                }
            }, 60);
        }
    }

    function filterStoreTable() {
        var input = document.getElementById('storeSearchInput');
        if (!input) return;
        var filter = input.value.toLowerCase().trim();
        var table = document.getElementById('storeYtdTable');
        if (!table) return;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        var trs = tbody.querySelectorAll('tr:not(.no-filter)');
        var visibleCount = 0;

        for (var i = 0; i < trs.length; i++) {
            var row = trs[i];
            var text = row.textContent.toLowerCase();
            if (text.indexOf(filter) > -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }

        var countBadge = document.getElementById('filteredStoreCount');
        if (countBadge) countBadge.textContent = visibleCount.toLocaleString('id-ID');
    }

    function exportStoreYtdToCsv() {
        var table = document.getElementById('storeYtdTable');
        if (!table) return;
        var trs = table.querySelectorAll('tr');
        var csv = [];
        
        for (var i = 0; i < trs.length; i++) {
            if (trs[i].style.display === 'none') continue;
            var row = [];
            var cols = trs[i].querySelectorAll('th, td');
            for (var j = 0; j < cols.length; j++) {
                var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""').trim();
                row.push('"' + text + '"');
            }
            if (row.length > 0) {
                csv.push(row.join(','));
            }
        }

        var blob = new Blob(["\uFEFF" + csv.join("\r\n")], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", "YTD_Store_Comparison_{{ $template->code }}_{{ $endYear }}.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    @if(isset($isYtdReport) && $isYtdReport)
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Render Offtake Comparison Chart (Monthly Line/Area Trend)
        @if(!empty($ytdData['monthly_trend']['categories']))
            var ytdCategories = {!! json_encode($ytdData['monthly_trend']['categories']) !!};
            var cyData = {!! json_encode($ytdData['monthly_trend']['cy_total']) !!};
            var pyData = {!! json_encode($ytdData['monthly_trend']['py_total']) !!};
            
            var ytdOptions = {
                series: [{
                    name: 'Offtake {{ $endYear }} (Tahun Berjalan)',
                    data: cyData
                }, {
                    name: 'Offtake {{ $endYear - 1 }} (Tahun Sebelumnya)',
                    data: pyData
                }],
                chart: {
                    type: 'area',
                    height: 270,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    },
                    fontFamily: 'Outfit, sans-serif'
                },
                colors: ['#0b3d88', '#f59e0b'],
                stroke: {
                    curve: 'smooth',
                    width: [3, 2],
                    dashArray: [0, 4]
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: [0.35, 0.12],
                        opacityTo: [0.03, 0.0],
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: [4, 3],
                    hover: { size: 6 }
                },
                xaxis: {
                    categories: ytdCategories,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '11px',
                            fontWeight: 600
                        }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    title: {
                        text: 'Volume (Liter)',
                        style: { color: '#475569', fontWeight: 600, fontSize: '11px' }
                    },
                    labels: {
                        formatter: function (val) {
                            if (val >= 1000000) return (val/1000000).toFixed(1) + "M L";
                            if (val >= 1000) return (val/1000).toFixed(1) + "k L";
                            return val ? val.toLocaleString('id-ID') + " L" : "0 L";
                        },
                        style: { colors: '#64748b', fontSize: '11px' }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return (val !== null && val !== undefined) ? val.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + " Liter" : "0.00 Liter";
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px',
                    fontWeight: 600,
                    markers: { radius: 12 }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4
                }
            };
            
            var ytdChart = new ApexCharts(document.querySelector("#chart_ytd_comparison"), ytdOptions);
            ytdChart.render();
        @elseif(!empty($ytdData['details']))
            // Fallback product comparison if monthly trend not available
            var ytdCategories = {!! json_encode(array_column($ytdData['details'], 'brand')) !!};
            var cyData = {!! json_encode(array_column($ytdData['details'], 'cy_volume')) !!};
            var pyData = {!! json_encode(array_column($ytdData['details'], 'py_volume')) !!};
            
            var ytdOptions = {
                series: [{
                    name: '{{ $endYear }} (Tahun Berjalan)',
                    data: cyData
                }, {
                    name: '{{ $endYear - 1 }} (Tahun Sebelumnya)',
                    data: pyData
                }],
                chart: {
                    type: 'line',
                    height: 270,
                    toolbar: { show: false },
                    fontFamily: 'Outfit, sans-serif'
                },
                colors: ['#0b3d88', '#f59e0b'],
                stroke: {
                    curve: 'smooth',
                    width: [3, 2],
                    dashArray: [0, 4]
                },
                markers: { size: 5 },
                xaxis: {
                    categories: ytdCategories,
                    labels: { style: { fontWeight: 600 } }
                },
                yaxis: {
                    title: { text: 'Volume (Liter)', style: { fontWeight: 600 } },
                    labels: {
                        formatter: function (val) {
                            if (val >= 1000) return (val/1000).toFixed(1) + "k";
                            return val.toFixed(0);
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + " Liter";
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                }
            };
            var ytdChart = new ApexCharts(document.querySelector("#chart_ytd_comparison"), ytdOptions);
            ytdChart.render();
        @endif

        // 2. Render Top 10 Store Comparison Chart
        @if(!empty($ytdData['stores']['top10']))
            var topStores = {!! json_encode($ytdData['stores']['top10']) !!};
            var storeCategories = topStores.map(function(s) { return s.store_name; });
            var storeCyData = topStores.map(function(s) { return s.cy_volume; });
            var storePyData = topStores.map(function(s) { return s.py_volume; });

            var storeOptions = {
                series: [{
                    name: 'YTD {{ $endYear }}',
                    data: storeCyData
                }, {
                    name: 'YTD {{ $endYear - 1 }}',
                    data: storePyData
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false },
                    fontFamily: 'Outfit, sans-serif'
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '65%',
                        borderRadius: 4
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 1,
                    colors: ['#fff']
                },
                xaxis: {
                    categories: storeCategories,
                    labels: {
                        formatter: function (val) {
                            if (val >= 1000) return (val/1000).toFixed(1) + "k";
                            return val.toFixed(0);
                        }
                    },
                    title: { text: 'Volume (Liter)', style: { fontWeight: 600 } }
                },
                yaxis: {
                    labels: {
                        maxWidth: 220,
                        style: { fontWeight: 600 }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + " Liter"
                        }
                    }
                },
                colors: ['var(--brand-primary)', '#94a3b8'],
                legend: {
                    position: 'top',
                    horizontalAlign: 'right'
                }
            };

            window.storeChartInstance = new ApexCharts(document.querySelector("#chart_ytd_store"), storeOptions);
            window.storeChartInstance.render();
        @endif
    });
    @endif

    // Modal Add / Edit Widget
    function openAddWidgetModal() {
        document.getElementById('modal_widget_title').innerHTML = '<i class="fa-solid fa-plus-circle" style="color: #6366f1;"></i> Tambah Widget Baru';
        document.getElementById('cfg_widget_id').value = '';
        document.getElementById('cfg_type').value = 'kpi_card';
        document.getElementById('cfg_title').value = '';
        document.getElementById('cfg_col_span').value = '6';
        document.getElementById('cfg_color').value = 'blue';
        document.getElementById('cfg_dim_field').value = '_submitted_date';
        document.getElementById('cfg_metric_field').value = '_submission';
        document.getElementById('cfg_aggregation').value = 'COUNT';
        document.getElementById('cfg_icon').value = 'fa-chart-pie';
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

        if (!currentDashboardConfig.widgets) {
            currentDashboardConfig.widgets = [];
        }

        var idx = currentDashboardConfig.widgets.findIndex(function (w) { return w.id === wId; });
        if (idx >= 0) {
            currentDashboardConfig.widgets[idx] = widgetObj;
        } else {
            currentDashboardConfig.widgets.push(widgetObj);
        }

        closeWidgetModal();
        saveDashboardLayout(true);
    }

    // Save Dashboard Layout to Backend via AJAX
    function saveDashboardLayout(skipSync) {
        if (!skipSync) {
            syncConfigFromDomOrder();
        }

        var saveUrl = "{{ route('portal.report.dashboard.save', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}";

        fetch(saveUrl, {
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
            console.error(err);
            alert('❌ Terjadi kesalahan jaringan saat menyimpan tata letak.');
        });
    }

    // Reset Dashboard Layout to Default
    function resetDashboardLayout() {
        if (!confirm('Kembalikan tata letak dashboard laporan ini ke tampilan standar bawaan?')) return;

        var resetUrl = "{{ route('portal.report.dashboard.reset', ['code' => $template->code, 'p' => $tenantPrincipal->id]) }}";

        fetch(resetUrl, {
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
            console.error(err);
            alert('❌ Terjadi kesalahan saat mereset dashboard.');
        });
    }

    @if(isset($isCbpReport) && $isCbpReport && !empty($cbpData))
    document.addEventListener('DOMContentLoaded', function() {
        var chartEl = document.getElementById('cbp_mop_trend_chart');
        if (chartEl && typeof ApexCharts !== 'undefined') {
            var monthsMeta = {!! json_encode($cbpData['months']) !!};
            var monthLabels = Object.values(monthsMeta).map(function(m) { return m.short + ' {{ $endYear }}'; });
            var trendSeriesRaw = {!! json_encode($cbpData['trend_series'] ?? []) !!};

            var series = [];
            var colors = ['#1e40af', '#ea580c', '#0284c7', '#16a34a', '#9333ea'];

            for (var brandName in trendSeriesRaw) {
                var brandData = trendSeriesRaw[brandName];
                var dataPoints = [];
                for (var mKey in monthsMeta) {
                    dataPoints.push(brandData[mKey] || null);
                }
                series.push({
                    name: brandName,
                    data: dataPoints
                });
            }

            var options = {
                series: series,
                chart: {
                    type: 'line',
                    height: 330,
                    toolbar: { show: true },
                    fontFamily: 'Outfit, sans-serif'
                },
                colors: colors,
                stroke: {
                    curve: 'smooth',
                    width: [3.5, 2, 2, 2, 2]
                },
                markers: {
                    size: 5,
                    hover: { size: 7 }
                },
                xaxis: {
                    categories: monthLabels,
                    labels: { style: { fontWeight: 600 } }
                },
                yaxis: {
                    title: { text: 'Harga Rata-Rata Galon (Rp)', style: { fontWeight: 600 } },
                    labels: {
                        formatter: function (val) {
                            if (!val) return '0';
                            if (val >= 1000) return 'Rp ' + (val/1000).toFixed(0) + 'k';
                            return 'Rp ' + val.toFixed(0);
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            if (!val) return '-';
                            return 'Rp ' + val.toLocaleString('id-ID');
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontWeight: 700
                },
                grid: {
                    borderColor: '#f1f5f9'
                }
            };

            var chart = new ApexCharts(chartEl, options);
            chart.render();
        }
    });
    @endif
</script>
@endpush
