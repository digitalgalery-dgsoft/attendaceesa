@extends('portal.layout')

@section('title', 'Attendance Roster - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Attendance Roster')
@section('breadcrumb_active', 'Attendance Roster')

@push('styles')
<style>
    /* Roster Page Wrapper & Cards matching Admin Filament */
    .roster-page-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .roster-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* KPI Grid 6 Columns matching Screenshot 1 */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 14px;
    }
    @media (min-width: 640px) {
        .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 1440px) {
        .kpi-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        min-width: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }

    /* Grand Total Banner */
    .grand-total-banner {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px 16px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #334155;
        gap: 10px;
    }

    /* Filter Form Grid */
    .filter-grid-roster {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 14px;
        align-items: flex-end;
    }
    .form-group-roster {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group-roster label {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }
    .form-group-roster .form-control-roster {
        width: 100%;
        padding: 8px 12px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #0f172a;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .form-group-roster .form-control-roster:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
    }

    /* Table Container & Bordered Matrix Table */
    .roster-table-container {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #94a3b8;
        border-radius: 8px;
        background: #ffffff;
    }

    .roster-bordered-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
        color: #1e293b;
        min-width: max-content;
    }

    .roster-bordered-table th {
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 10px 12px;
        border-right: 1px solid #cbd5e1;
        border-bottom: 2px solid #94a3b8;
        white-space: nowrap;
    }

    .roster-bordered-table th.weekend-header {
        background: #fef2f2;
        color: #991b1b;
    }

    .roster-bordered-table td {
        padding: 8px 10px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .roster-bordered-table th:last-child,
    .roster-bordered-table td:last-child {
        border-right: none;
    }

    .roster-bordered-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    .roster-bordered-table tbody tr:hover {
        background: #f1f5f9 !important;
    }

    /* Sticky First Column */
    .roster-bordered-table .sticky-col {
        position: sticky;
        left: 0;
        z-index: 10;
        background: #ffffff;
        border-right: 2px solid #94a3b8 !important;
        min-width: 260px;
        max-width: 280px;
    }
    .roster-bordered-table tbody tr:nth-child(even) .sticky-col {
        background: #f8fafc;
    }
    .roster-bordered-table thead .sticky-col {
        background: #f1f5f9;
        z-index: 20;
    }

    /* Clickable Cell & Badges matching Admin */
    .roster-cell-clickable {
        cursor: pointer;
        border-radius: 6px;
        padding: 4px;
        transition: all 0.15s ease-in-out;
        min-height: 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .roster-cell-clickable:hover {
        background: #e0e7ff;
        outline: 2px solid #6366f1;
        transform: scale(1.02);
    }

    .att-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .att-badge-present {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .att-badge-late {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        box-shadow: 0 1px 2px rgba(217, 119, 6, 0.15);
    }
    .att-badge-absent {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .att-badge-leave {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    .att-badge-permit {
        background: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }
    .att-badge-sick {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .att-badge-off {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    .att-badge-shift {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-weight: 700;
    }
    .att-badge-lr {
        background: #fef9c3;
        color: #854d0e;
        border: 1px solid #fde047;
        font-weight: 800;
        padding: 2px 7px;
        letter-spacing: 0.5px;
    }
    .att-badge-import {
        background: #f3e8ff;
        color: #7e22ce;
        border: 1px solid #d8b4fe;
        box-shadow: 0 1px 2px rgba(126, 34, 206, 0.12);
    }

    .time-pill {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #334155;
        font-family: monospace;
        font-weight: 600;
    }

    /* Modal Overlay & Container */
    .portal-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    }
    .portal-modal-container {
        background: #ffffff;
        border-radius: 16px;
        max-width: 820px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }
    @keyframes modalScaleIn {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }
    .portal-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
    }
    .portal-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        line-height: 1;
        color: #64748b;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 6px;
        transition: all 0.15s ease;
    }
    .portal-modal-close:hover {
        background: #fee2e2;
        color: #ef4444;
    }
    .portal-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }
    .portal-modal-footer {
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-start;
        background: #f8fafc;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }
    .btn-portal-modal-close {
        background: #ffffff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-portal-modal-close:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
</style>
@endpush

@section('content')
@php
    $todayStr = $todayStr ?? \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
@endphp
<div class="roster-page-wrapper">
    {{-- Breadcrumb & Top Action Header (Sesuai Screenshot 1) --}}
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
        <div>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">
                <span>Attendances</span> &rsaquo; <span style="color: #4f46e5;">Attendance Roster</span>
            </div>
            <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.5px;">Attendance Roster</h1>
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
            <a href="{{ route('portal.attendances.export', array_merge(request()->query(), ['p' => $tenantPrincipal->id])) }}" class="btn-action-primary" style="background: #10b981; color: #ffffff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>
        </div>
    </div>

    {{-- TOP 6 KPI CARDS (Persis Screenshot 1) --}}
    <div class="kpi-grid">
        {{-- 1. Total Employee Aktif --}}
        <div class="kpi-card">
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 11px; font-weight: 700; color: #4338ca; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Total Employee Aktif">Total Employee Aktif</div>
                <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 3px; line-height: 1.15;">{{ number_format($summary['total_active_employees']) }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <span style="font-size: 11px; font-weight: 700; color: #4338ca; background: #e0e7ff; padding: 1px 6px; border-radius: 4px; display: inline-block;">
                        {{ number_format($summary['total_scheduled_employees']) }} Terjadwal
                    </span>
                </div>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 6px; font-size: 18px;">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        {{-- 2. Total Hadir (On-Time) --}}
        <div class="kpi-card">
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Total Hadir (On-Time)">Total Hadir (On-Time)</div>
                <div style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 3px; line-height: 1.15;">{{ number_format($summary['total_ontime']) }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Check-in tepat waktu</div>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 6px; font-size: 18px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        {{-- 3. Total Telat --}}
        <div class="kpi-card">
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 11px; font-weight: 700; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Total Telat">Total Telat</div>
                <div style="font-size: 24px; font-weight: 800; color: #d97706; margin-top: 3px; line-height: 1.15;">{{ number_format($summary['total_late']) }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Check-in melebihi jadwal</div>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 6px; font-size: 18px;">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>

        {{-- 4. Total Cuti --}}
        <div class="kpi-card">
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 11px; font-weight: 700; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Total Cuti">Total Cuti</div>
                <div style="font-size: 24px; font-weight: 800; color: #0284c7; margin-top: 3px; line-height: 1.15;">{{ number_format($summary['total_cuti']) }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Cuti disetujui</div>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 6px; font-size: 18px;">
                <i class="fa-solid fa-calendar"></i>
            </div>
        </div>

        {{-- 5. Total Ijin / Sakit --}}
        <div class="kpi-card">
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 11px; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Total Ijin / Sakit">Total Ijin / Sakit</div>
                <div style="font-size: 24px; font-weight: 800; color: #7c3aed; margin-top: 3px; line-height: 1.15;">{{ number_format($summary['total_permit_sick']) }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Izin resmi & surat sakit</div>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 6px; font-size: 18px;">
                <i class="fa-solid fa-file-lines"></i>
            </div>
        </div>

        {{-- 6. Total Alpha --}}
        <div class="kpi-card">
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 11px; font-weight: 700; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Total Alpha">Total Alpha</div>
                <div style="font-size: 24px; font-weight: 800; color: #e11d48; margin-top: 3px; line-height: 1.15;">{{ number_format($summary['total_alpha']) }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Tidak hadir / belum absen</div>
            </div>
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #ffe4e6; color: #e11d48; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 6px; font-size: 18px;">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>
    </div>

    {{-- GRAND TOTAL FORMULA BANNER (Persis Screenshot 1) --}}
    <div class="grand-total-banner">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calculator" style="color: #4f46e5; font-size: 16px;"></i>
            <span>
                <strong>Grand Total:</strong>
                Employee Aktif (<strong>{{ number_format($summary['total_active_employees']) }}</strong>) =
                Hadir On-Time (<strong>{{ number_format($summary['total_ontime']) }}</strong>) +
                Telat (<strong>{{ number_format($summary['total_late']) }}</strong>) +
                Cuti (<strong>{{ number_format($summary['total_cuti']) }}</strong>) +
                Ijin/Sakit (<strong>{{ number_format($summary['total_permit_sick']) }}</strong>) +
                Alpha (<strong>{{ number_format($summary['total_alpha']) }}</strong>)
            </span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; color: #059669; font-weight: 600;">
            <i class="fa-solid fa-circle-check"></i>
            <span>Status Sinkron &bull; Evaluasi Presensi: {{ \Carbon\Carbon::parse($summary['evaluation_date'])->translatedFormat('l, d F Y') }}</span>
        </div>
    </div>

    {{-- FILTER FORM CARD (Persis Screenshot 1) --}}
    <div class="roster-card">
        <form action="{{ route('portal.attendances') }}" method="GET" class="filter-grid-roster">
            <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">

            {{-- 1. Tanggal Mulai --}}
            <div class="form-group-roster">
                <label>Tanggal Mulai <span style="color: #dc2626;">*</span></label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="form-control-roster" required>
            </div>

            {{-- 2. Tanggal Akhir --}}
            <div class="form-group-roster">
                <label>Tanggal Akhir <span style="color: #dc2626;">*</span></label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="form-control-roster" required>
            </div>

            {{-- 3. Region / Area --}}
            <div class="form-group-roster">
                <label>Region / Area</label>
                <select name="branch_id" class="form-control-roster searchable-filter-select">
                    <option value="">Semua Region</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" {{ (string)$filterBranchId === (string)$b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 4. Prinsiple --}}
            <div class="form-group-roster">
                <label>Prinsiple</label>
                <select name="principal_id" class="form-control-roster searchable-filter-select">
                    <option value="">Semua Prinsiple</option>
                    @foreach ($tenantPrincipalsAll as $p)
                        <option value="{{ $p->id }}" {{ (string)$filterPrincipalId === (string)$p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 5. Karyawan Spesifik --}}
            <div class="form-group-roster">
                <label>Karyawan Spesifik</label>
                <select name="employee_id" class="form-control-roster searchable-filter-select">
                    <option value="">Semua Karyawan</option>
                    @foreach ($filterEmployees as $emp)
                        <option value="{{ $emp->id }}" {{ (string)$filterEmployeeId === (string)$emp->id ? 'selected' : '' }}>
                            {{ strtoupper($emp->full_name) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Submit & Reset Buttons --}}
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" class="btn-action-primary" style="padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; background: #4f46e5; color: #ffffff; border: none; cursor: pointer;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="{{ route('portal.attendances', ['p' => $tenantPrincipal->id]) }}" class="btn-portal-modal-close" style="text-decoration: none; padding: 8px 14px; font-size: 13px;">
                    Reset
                </a>
            </div>
        </form>

        <div style="margin-top: 10px; font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-circle-info" style="color: #6366f1;"></i>
            <span>Rentang kalender roster menampilkan maksimal <strong>31 hari</strong>. Hari libur (weekend / tanggal merah) otomatis diselaraskan dengan jam kerja departemen.</span>
        </div>
    </div>

    {{-- MATRIKS KEHADIRAN HARIAN TABLE CARD (Persis Screenshot 1) --}}
    <div class="roster-card">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-table-cells" style="color: #4f46e5; font-size: 18px;"></i>
                <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Matriks Kehadiran Harian (Attendance Roster)</span>
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 700; background: #e0e7ff; color: #3730a3;">
                    <span>{{ number_format($totalEmployeesCount) }} Karyawan Aktif</span>
                    <span style="opacity: 0.5;">&bull;</span>
                    <span style="font-weight: 600; font-size: 11px;">{{ number_format($totalScheduledEmployees) }} Terjadwal</span>
                </span>
            </div>

            {{-- Live Search Input --}}
            <div style="min-width: 280px;">
                <form action="{{ route('portal.attendances') }}" method="GET" style="display: flex;">
                    <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    @if($filterBranchId) <input type="hidden" name="branch_id" value="{{ $filterBranchId }}"> @endif
                    @if($filterPrincipalId) <input type="hidden" name="principal_id" value="{{ $filterPrincipalId }}"> @endif
                    
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari nama, NIK, area, prinsiple..."
                        style="width: 100%; padding: 7px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a; outline: none;"
                    />
                </form>
            </div>
        </div>

        <div class="roster-table-container">
            <table class="roster-bordered-table">
                <thead>
                    <tr>
                        <th class="sticky-col" style="text-align: left;">Karyawan</th>
                        @for ($d = 1; $d <= $daysInPeriod; $d++)
                            @php
                                $colDate = $startDate->copy()->addDays($d - 1);
                                $isWeekend = in_array($colDate->dayOfWeek, [0, 6]);
                                $isNatHoliday = isset($holidayMap[$colDate->toDateString()]);
                            @endphp
                            <th class="{{ ($isWeekend || $isNatHoliday) ? 'weekend-header' : '' }}" style="text-align: center; min-width: 125px;">
                                <div style="font-weight: 800; font-size: 12px;">{{ $colDate->format('d M') }}</div>
                                <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; opacity: 0.85;">
                                    {{ $colDate->translatedFormat('l') }}
                                </div>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pagedEmployees as $employee)
                        @php
                            $empName = strtoupper($employee->full_name);
                            $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($empName) . '&background=4f46e5&color=fff&size=64';
                            if (!empty($employee->photo)) {
                                try {
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
                                        $photoUrl = asset('storage/' . $employee->photo);
                                    } elseif (\Illuminate\Support\Facades\Storage::exists($employee->photo)) {
                                        $photoUrl = \Illuminate\Support\Facades\Storage::url($employee->photo);
                                    }
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr>
                            {{-- Sticky Karyawan Column with UPPERCASE Name --}}
                            <td class="sticky-col">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img
                                        src="{{ $photoUrl }}"
                                        alt="{{ $empName }}"
                                        style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;"
                                        loading="lazy"
                                    />
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase;" title="{{ $empName }}">
                                            {{ $empName }}
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; font-family: monospace;">
                                            NIK: {{ $employee->employee_no ?? '-' }}
                                        </div>
                                        <div style="font-size: 10px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                                            <span style="font-weight: 600; color: #4338ca;">{{ $employee->position_name ?? 'Staff' }}</span> &bull; 
                                            <span>{{ $employee->branch_name ?? ($employee->principal_name ?? '-') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Daily Date Matrix Cells --}}
                            @for ($d = 1; $d <= $daysInPeriod; $d++)
                                @php
                                    $colDateObj = $startDate->copy()->addDays($d - 1);
                                    $dateStr = $colDateObj->toDateString();
                                    $isWeekend = in_array($colDateObj->dayOfWeek, [0, 6]);
                                    $isNatHoliday = isset($holidayMap[$dateStr]);
                                    $isDeptWorkDay = \App\Http\Controllers\Portal\PrincipalPortalController::isWorkingDay($colDateObj, $employee->dept_working_days);

                                    $empAtts = $attendances->get($employee->id);
                                    $att = $empAtts ? $empAtts->firstWhere('attendance_date', $dateStr) : null;

                                    $empScheds = $schedules->get($employee->id);
                                    $sched = $empScheds ? $empScheds->firstWhere('schedule_date', $dateStr) : null;

                                    $empLeaves = $leaves->get($employee->id);
                                    $activeLeave = null;
                                    if ($empLeaves) {
                                        $activeLeave = $empLeaves->first(function($l) use ($dateStr) {
                                            return $dateStr >= $l->start_date && $dateStr <= $l->end_date;
                                        });
                                    }

                                    $isLate = false;
                                    $lateText = '';

                                    if ($att) {
                                        if ($att->status === 'late' || (int)$att->late_minutes > 0) {
                                            $isLate = true;
                                            $lateText = (int)$att->late_minutes > 0 ? '+' . (int)$att->late_minutes . 'm' : '';
                                        } elseif (!empty($att->checkin_at)) {
                                            $checkin = \Carbon\Carbon::parse($att->checkin_at)->timezone('Asia/Jakarta');
                                            if (!empty($att->shift_start_time)) {
                                                $shiftStart = \Carbon\Carbon::parse($att->attendance_date . ' ' . $att->shift_start_time);
                                                $grace = (int)($att->grace_checkin_minutes ?? 0);
                                                if ($checkin->greaterThan($shiftStart->copy()->addMinutes($grace))) {
                                                    $isLate = true;
                                                    $diffM = (int)$checkin->diffInMinutes($shiftStart);
                                                    $lateText = '+' . $diffM . 'm';
                                                }
                                            } elseif (!empty($att->planned_start_at)) {
                                                $plannedStart = \Carbon\Carbon::parse($att->planned_start_at);
                                                if ($checkin->greaterThan($plannedStart)) {
                                                    $isLate = true;
                                                    $diffM = (int)$checkin->diffInMinutes($plannedStart);
                                                    $lateText = '+' . $diffM . 'm';
                                                }
                                            } else {
                                                $defaultStart = \Carbon\Carbon::parse($att->attendance_date . ' 08:30:00');
                                                if ($checkin->greaterThan($defaultStart)) {
                                                    $isLate = true;
                                                    $diffM = (int)$checkin->diffInMinutes($defaultStart);
                                                    $lateText = '+' . $diffM . 'm';
                                                }
                                            }
                                        }
                                    }
                                @endphp

                                <td style="text-align: center; background: {{ $isLate ? '#fffbeb' : (($isWeekend || $isNatHoliday || !$isDeptWorkDay) ? '#fdf2f2' : 'inherit') }};">
                                    @if ($activeLeave)
                                        <div class="roster-cell-clickable" onclick="openAttendanceModal({{ $employee->id }}, '{{ $dateStr }}')" style="min-height: 48px; display: flex; flex-direction: column; align-items: center; justify-content: center;" title="{{ $activeLeave->status === 'pending' ? 'Leave Request (Pengajuan ' . ucfirst($activeLeave->type) . ' Menunggu Approval)' : ($activeLeave->notes ?? 'Izin Disetujui') }}">
                                            @if ($activeLeave->status === 'pending')
                                                <span class="att-badge att-badge-lr" title="Leave Request / Izin Menunggu Approval">LR</span>
                                                <div style="font-size: 9.5px; color: #b45309; font-weight: 700; margin-top: 2px;">{{ ucfirst($activeLeave->type ?? 'Izin') }}</div>
                                            @else
                                                @php
                                                    $lType = strtolower($activeLeave->type);
                                                @endphp
                                                @if (in_array($lType, ['sakit', 'medical_leave']))
                                                    <span class="att-badge att-badge-sick">Sakit</span>
                                                @elseif (in_array($lType, ['cuti', 'annual_leave', 'cuti_peraturan']))
                                                    <span class="att-badge att-badge-leave">Cuti</span>
                                                @else
                                                    <span class="att-badge att-badge-permit">Izin</span>
                                                @endif
                                            @endif
                                        </div>
                                    @elseif ($att)
                                        <div class="roster-cell-clickable" onclick="openAttendanceModal({{ $employee->id }}, '{{ $dateStr }}')" title="Klik untuk melihat rincian presensi & aktivitas">
                                            <div>
                                                @if ($isLate)
                                                     <span class="att-badge att-badge-late">
                                                         Telat {{ $lateText }}
                                                     </span>
                                                 @elseif ($att->status === 'present')
                                                     <span class="att-badge att-badge-present">Hadir</span>
                                                 @elseif ($att->status === 'absent')
                                                     <span class="att-badge att-badge-absent">Alpha</span>
                                                 @elseif ($att->status === 'leave')
                                                     <span class="att-badge att-badge-leave">Cuti</span>
                                                 @elseif ($att->status === 'permit')
                                                     <span class="att-badge att-badge-permit">Izin</span>
                                                 @elseif ($att->status === 'sick')
                                                     <span class="att-badge att-badge-sick">Sakit</span>
                                                 @else
                                                     <span class="att-badge att-badge-permit">{{ ucfirst($att->status) }}</span>
                                                 @endif
                                             </div>

                                             @if (!empty($att->is_manual_correction))
                                                 <div style="margin-top: 3px;">
                                                     <span class="att-badge att-badge-import" style="font-size: 8.5px; font-weight: 800; padding: 1px 4px; letter-spacing: 0.03em;" title="{{ $att->correction_note ?? 'Data Hasil Penyesuaian / Import Excel' }}">⚡ IMPORT</span>
                                                 </div>
                                             @endif

                                             @if (!empty($att->checkin_at))
                                                 <div style="margin-top: 4px; display: flex; flex-direction: column; gap: 2px; align-items: center;">
                                                     <div class="time-pill">
                                                         <span style="color: {{ $isLate ? '#d97706' : '#059669' }}; font-weight: 700;">In:</span>
                                                         <span style="color: {{ $isLate ? '#b45309' : 'inherit' }}; font-weight: {{ $isLate ? '800' : '600' }};">
                                                             {{ \Carbon\Carbon::parse($att->checkin_at)->timezone('Asia/Jakarta')->format('H:i') }}
                                                         </span>
                                                     </div>
                                                     @if (!empty($att->checkout_at))
                                                         <div class="time-pill">
                                                             <span style="color: #dc2626; font-weight: 700;">Out:</span>
                                                             <span>{{ \Carbon\Carbon::parse($att->checkout_at)->timezone('Asia/Jakarta')->format('H:i') }}</span>
                                                         </div>
                                                     @endif
                                                 </div>
                                             @endif
                                        </div>
                                    @elseif ($isNatHoliday || !$isDeptWorkDay || ($sched && in_array($sched->schedule_type, ['dayoff', 'holiday'])))
                                        <div style="min-height: 48px; display: flex; align-items: center; justify-content: center;" title="{{ $isNatHoliday ? 'Hari Libur Nasional' : 'Hari Libur / Weekend' }}">
                                            <span class="att-badge att-badge-off">Libur</span>
                                        </div>
                                    @elseif ($sched && in_array($sched->schedule_type, ['workday', 'remote', 'field']))
                                        @if ($dateStr < $todayStr)
                                            <div style="min-height: 48px; display: flex; align-items: center; justify-content: center;" title="Jadwal kerja aktif namun tidak melakukan absensi">
                                                <span class="att-badge att-badge-absent">Alpha</span>
                                            </div>
                                        @elseif ($dateStr === $todayStr)
                                            @php
                                                $now = \Carbon\Carbon::now('Asia/Jakarta');
                                                $shiftStart = null;
                                                if (!empty($sched->shift_start_time)) {
                                                    $shiftStart = \Carbon\Carbon::parse($dateStr . ' ' . $sched->shift_start_time, 'Asia/Jakarta');
                                                } elseif (!empty($sched->planned_start_at)) {
                                                    $shiftStart = \Carbon\Carbon::parse($sched->planned_start_at, 'Asia/Jakarta');
                                                } else {
                                                    $shiftStart = \Carbon\Carbon::parse($dateStr . ' 08:30:00', 'Asia/Jakarta');
                                                }
                                                $isShiftStarted = $now->greaterThanOrEqualTo($shiftStart);
                                            @endphp

                                            @if ($isShiftStarted)
                                                <div style="min-height: 48px; display: flex; align-items: center; justify-content: center;" title="Jadwal kerja aktif telah dimulai namun belum melakukan absensi">
                                                    <span class="att-badge att-badge-absent">Alpha</span>
                                                </div>
                                            @else
                                                <div class="roster-cell-clickable" onclick="openAttendanceModal({{ $employee->id }}, '{{ $dateStr }}')" title="Jadwal: {{ $sched->shift_name ?? ($sched->shift_code ?? 'Shift') }} (Belum dimulai)">
                                                    <span class="att-badge att-badge-shift">
                                                        {{ $sched->shift_name ?? ($sched->shift_code ?? 'Shift') }}
                                                    </span>
                                                    @if (!empty($sched->shift_start_time))
                                                        <div style="font-size: 10px; color: #64748b; font-weight: 600; margin-top: 2px;">
                                                            Jam: {{ substr($sched->shift_start_time, 0, 5) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            <div style="min-height: 48px; display: flex; flex-direction: column; align-items: center; justify-content: center;" title="{{ $sched->shift_name ?? 'Jadwal Kerja' }}">
                                                @if (!empty($sched->shift_name) || !empty($sched->shift_code))
                                                    <span style="font-size: 10.5px; color: #64748b; font-weight: 600;">
                                                        {{ $sched->shift_code ?? $sched->shift_name }}
                                                    </span>
                                                @else
                                                    <span style="color: #94a3b8; font-size: 11px;">-</span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div style="min-height: 48px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 13px;">
                                            -
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $daysInPeriod + 1 }}" style="padding: 40px; text-align: center; color: #64748b;">
                                Tidak ada data karyawan yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION TOOLBAR --}}
        @if ($pagination['total_pages'] > 1 || $totalEmployeesCount > 0)
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
                <div>
                    Menampilkan <strong style="color: #0f172a;">{{ $pagination['from'] }}</strong> - <strong style="color: #0f172a;">{{ $pagination['to'] }}</strong> dari <strong style="color: #0f172a;">{{ number_format($totalEmployeesCount) }}</strong> karyawan
                </div>

                <div style="display: flex; align-items: center; gap: 16px;">
                    <form action="{{ route('portal.attendances') }}" method="GET" style="display: flex; align-items: center; gap: 6px;">
                        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
                        <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                        <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                        @if($filterBranchId) <input type="hidden" name="branch_id" value="{{ $filterBranchId }}"> @endif
                        @if($filterPrincipalId) <input type="hidden" name="principal_id" value="{{ $filterPrincipalId }}"> @endif
                        @if($search) <input type="hidden" name="q" value="{{ $search }}"> @endif
                        
                        <span>Per halaman:</span>
                        <select name="per_page" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; color: #0f172a;">
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        @if ($pagination['page'] > 1)
                            <a
                                href="{{ route('portal.attendances', array_merge(request()->query(), ['page' => $pagination['page'] - 1])) }}"
                                class="btn-portal-modal-close"
                                style="text-decoration: none; padding: 6px 12px; font-size: 12px;"
                            >
                                &laquo; Sebelumnya
                            </a>
                        @else
                            <span style="padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #94a3b8; opacity: 0.5;">
                                &laquo; Sebelumnya
                            </span>
                        @endif

                        <span style="font-weight: 700; padding: 0 4px;">
                            Halaman {{ $pagination['page'] }} dari {{ $pagination['total_pages'] }}
                        </span>

                        @if ($pagination['page'] < $pagination['total_pages'])
                            <a
                                href="{{ route('portal.attendances', array_merge(request()->query(), ['page' => $pagination['page'] + 1])) }}"
                                class="btn-portal-modal-close"
                                style="text-decoration: none; padding: 6px 12px; font-size: 12px;"
                            >
                                Selanjutnya &raquo;
                            </a>
                        @else
                            <span style="padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px; color: #94a3b8; opacity: 0.5;">
                                Selanjutnya &raquo;
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- MODAL DETAIL PRESENSI (PERSIS SCREENSHOT 4) --}}
<div id="attendanceDetailModalOverlay" class="portal-modal-overlay" style="display: none;" onclick="closeAttendanceModal(event)">
    <div class="portal-modal-container" onclick="event.stopPropagation()">
        <div class="portal-modal-header">
            <h3 id="modalTitle" style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Rincian Presensi & Aktivitas</h3>
            <button type="button" class="portal-modal-close" onclick="closeAttendanceModal()">&times;</button>
        </div>
        <div id="modalBodyContent" class="portal-modal-body">
            {{-- Dimuat melalui AJAX --}}
        </div>
        <div class="portal-modal-footer">
            <button type="button" class="btn-portal-modal-close" onclick="closeAttendanceModal()">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openAttendanceModal(employeeId, dateStr) {
        const overlay = document.getElementById('attendanceDetailModalOverlay');
        const body = document.getElementById('modalBodyContent');
        const title = document.getElementById('modalTitle');
        
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        title.innerText = 'Rincian Presensi & Aktivitas – ' + dateStr;
        
        body.innerHTML = `
            <div style="padding: 40px; text-align: center; color: #64748b;">
                <div style="width: 36px; height: 36px; border: 3px solid #cbd5e1; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px;"></div>
                <div style="font-weight: 700; color: #1e293b;">Memuat rincian presensi & aktivitas...</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Mengambil data lokasi GPS, shift kerja, dan activity log</div>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
        
        const url = "{{ route('portal.attendances.modal') }}?employee_id=" + employeeId + "&date=" + dateStr + "&p={{ $tenantPrincipal->id }}";
        
        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Status: ' + res.status);
                return res.text();
            })
            .then(html => {
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `
                    <div style="padding: 30px; text-align: center; color: #dc2626;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; margin-bottom: 8px;"></i>
                        <div style="font-weight: 700;">Gagal memuat rincian presensi.</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Silakan periksa koneksi atau coba beberapa saat lagi.</div>
                    </div>
                `;
            });
    }

    function closeAttendanceModal(e) {
        if (e && e.target !== document.getElementById('attendanceDetailModalOverlay') && !e.target.classList.contains('portal-modal-close') && !e.target.classList.contains('btn-portal-modal-close')) {
            return;
        }
        const overlay = document.getElementById('attendanceDetailModalOverlay');
        overlay.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Keyboard ESC shortcut to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('attendanceDetailModalOverlay');
            if (overlay && overlay.style.display !== 'none') {
                closeAttendanceModal();
            }
        }
    });
</script>
@endpush
