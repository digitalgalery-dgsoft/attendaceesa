@extends('portal.layout')

@section('title', 'Visit Schedule (Itinerari) - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Visit Schedule (Itinerari Kalender)')
@section('breadcrumb_active', 'Visit Schedule')

@push('styles')
<style>
    /* Main Calendar Wrapper */
    .vcal-wrapper {
        display: flex;
        flex-direction: column;
        gap: 18px;
        width: 100%;
        margin-bottom: 2rem;
    }

    /* Top Controls & Filter Card */
    .vcal-header-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 18px 22px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .vcal-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
    }

    .vcal-nav-group {
        display: inline-flex;
        background: #f1f5f9;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
    }

    .vcal-nav-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-heading);
        text-decoration: none;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .vcal-nav-btn:hover {
        background: #ffffff;
        color: var(--brand-primary);
    }

    .vcal-current-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-heading);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .vcal-actions-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .vcal-counter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: var(--brand-light);
        color: var(--brand-primary);
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
    }

    .btn-action-green {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.15rem;
        background: #16a34a; color: #ffffff; border: none; border-radius: 10px;
        font-size: 0.85rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }
    .btn-action-green:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .btn-action-blue {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;
        background: var(--brand-primary); color: #ffffff; border: none; border-radius: 10px;
        font-size: 0.85rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px var(--brand-glow);
        transition: all 0.2s ease;
    }
    .btn-action-blue:hover { transform: translateY(-2px); filter: brightness(1.1); }

    /* Filters Grid */
    .vcal-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--border-color);
    }

    .vcal-field-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .vcal-field-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-muted);
    }

    .vcal-select, .vcal-input {
        width: 100%;
        padding: 7px 11px;
        border: 1.5px solid var(--border-color);
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-heading);
        background: #ffffff;
        outline: none;
        transition: all 0.15s ease;
    }
    .vcal-select:focus, .vcal-input:focus {
        border-color: var(--brand-primary);
        box-shadow: 0 0 0 3px var(--brand-light);
    }

    /* Monthly Calendar Grid Box */
    .vcal-calendar-box {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .vcal-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        background: var(--border-color);
        gap: 1px;
    }

    .vcal-day-header {
        background: #f8fafc;
        padding: 10px 8px;
        text-align: center;
        font-size: 12px;
        font-weight: 800;
        color: var(--text-heading);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .vcal-day-header.weekend {
        color: #ef4444;
        background: #fef2f2;
    }

    .vcal-cell {
        background: #ffffff;
        min-height: 125px;
        padding: 6px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        position: relative;
        transition: background 0.15s ease;
    }
    .vcal-cell.other-month {
        background: #fbfcfe;
        opacity: 0.55;
    }
    .vcal-cell.today {
        background: #f0f7ff;
        border: 1.5px solid var(--brand-primary);
    }

    .vcal-cell-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 2px;
    }

    .vcal-date-badge {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-heading);
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .vcal-cell.today .vcal-date-badge {
        background: var(--brand-primary);
        color: #ffffff;
    }

    .vcal-quick-add {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        font-size: 14px;
        line-height: 1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.15s ease;
    }
    .vcal-cell:hover .vcal-quick-add {
        opacity: 1;
    }
    .vcal-quick-add:hover {
        background: var(--brand-primary);
        border-color: var(--brand-primary);
        color: #ffffff;
    }

    .vcal-cards-container {
        display: flex;
        flex-direction: column;
        gap: 4px;
        overflow-y: auto;
        max-height: 180px;
    }

    /* Individual Itinerary Event Card */
    .vcal-event-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-left: 3.5px solid var(--brand-primary);
        border-radius: 6px;
        padding: 5px 6px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .vcal-event-card:hover {
        border-color: var(--brand-primary);
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }
    .vcal-event-card.draft { border-left-color: #f59e0b; }
    .vcal-event-card.cancelled { border-left-color: #ef4444; opacity: 0.6; }

    .vcal-card-title {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-heading);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vcal-card-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 10px;
        color: var(--text-muted);
    }

    .vcal-store-count {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        background: #e2e8f0;
        color: #334155;
        font-weight: 700;
        padding: 1px 4px;
        border-radius: 4px;
        font-size: 9px;
    }

    .vcal-lock-icon {
        font-size: 10px;
        color: #e11d48;
    }

    /* Detail Modal Styles */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px);
        z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        opacity: 0; pointer-events: none; transition: all 0.25s ease;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal-box {
        background: #ffffff; border-radius: 20px; width: 100%; max-width: 780px; max-height: 90vh;
        overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: translateY(20px);
        transition: all 0.25s ease; border: 1px solid var(--border-color);
    }
    .modal-overlay.active .modal-box { transform: translateY(0); }
    .modal-header {
        padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between; background: #f8fafc;
    }
    .modal-title { font-size: 1.2rem; font-weight: 800; color: var(--text-heading); margin: 0; }
    .modal-close-btn {
        background: transparent; border: none; font-size: 1.4rem; color: var(--text-muted);
        cursor: pointer; line-height: 1; padding: 0.25rem; border-radius: 6px;
    }
    .modal-close-btn:hover { color: var(--text-heading); background: #e2e8f0; }
    .modal-body { padding: 1.75rem; }
    .modal-footer {
        padding: 1.25rem 1.75rem; border-top: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; background: #f8fafc;
    }

    /* Detail Profile Card */
    .detail-emp-banner {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 1.25rem;
    }

    .detail-emp-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .detail-avatar {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: var(--brand-primary);
        color: #ffffff;
        font-weight: 800;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-transform: uppercase;
    }

    /* Route Sequence Table */
    .route-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        text-align: left;
    }
    .route-table th {
        background: #f1f5f9;
        padding: 8px 12px;
        font-weight: 700;
        color: var(--text-heading);
        border-bottom: 1px solid var(--border-color);
        text-transform: uppercase;
        font-size: 11px;
    }
    .route-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: var(--text-body);
    }

    .seq-circle {
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

    /* Form Styles */
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.4rem; }
    .form-control {
        width: 100%; padding: 0.65rem 0.9rem; border: 1.5px solid var(--border-color); border-radius: 10px;
        font-size: 0.88rem; color: var(--text-heading); outline: none; transition: all 0.2s ease; background: #ffffff;
    }
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 3px var(--brand-light); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    /* Repeater Row */
    .repeater-box {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem;
        background: #f8fafc;
        margin-bottom: 1rem;
    }
    .store-row-item {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Dropzone */
    .dropzone-container {
        border: 2px dashed #94a3b8; border-radius: 14px; padding: 2rem 1.5rem; text-align: center;
        background: #f8fafc; cursor: pointer; transition: all 0.2s ease; position: relative;
    }
    .dropzone-container:hover, .dropzone-container.dragover {
        border-color: var(--brand-primary); background: var(--brand-light);
    }
    .dropzone-icon {
        width: 56px; height: 56px; border-radius: 14px; background: #e2f6ee; color: #16a34a;
        display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 0.75rem;
    }
    .file-input-hidden { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
</style>
@endpush

@section('content')
<div class="content-container">

    <div class="vcal-wrapper">
        <!-- Top Controls & Summary Bar -->
        <div class="vcal-header-card">
            <div class="vcal-top-bar">
                <div class="vcal-nav-group">
                    @php
                        $prevDate = \Carbon\Carbon::create($year, $month, 1)->subMonth();
                        $nextDate = \Carbon\Carbon::create($year, $month, 1)->addMonth();
                    @endphp
                    <a href="{{ route('portal.itineraries', ['p' => $tenantPrincipal->id, 'month' => $prevDate->month, 'year' => $prevDate->year, 'branch_id' => $branchId, 'employee_id' => $employeeId, 'q' => $search]) }}" class="vcal-nav-btn">
                        <i class="bi bi-chevron-left"></i> Bulan Lalu
                    </a>
                    <a href="{{ route('portal.itineraries', ['p' => $tenantPrincipal->id, 'month' => now()->month, 'year' => now()->year]) }}" class="vcal-nav-btn" style="border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color);">
                        Hari Ini
                    </a>
                    <a href="{{ route('portal.itineraries', ['p' => $tenantPrincipal->id, 'month' => $nextDate->month, 'year' => $nextDate->year, 'branch_id' => $branchId, 'employee_id' => $employeeId, 'q' => $search]) }}" class="vcal-nav-btn">
                        Bulan Depan <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="vcal-current-title">
                    <i class="bi bi-calendar3" style="color: var(--brand-primary);"></i>
                    <span>{{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</span>
                </div>

                <div class="vcal-actions-right">
                    <div class="vcal-counter-pill">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>{{ $totalSchedulesInMonth }} Jadwal &bull; {{ $totalStoresInMonth }} Kunjungan Toko</span>
                    </div>
                    @if($canCreateItinerary ?? true)
                        <button type="button" class="btn-action-green" onclick="openImportModal()">
                            <i class="bi bi-file-earmark-excel-fill"></i> Import Visit
                        </button>
                        <button type="button" class="btn-action-blue" onclick="openAddItineraryModal()">
                            <i class="bi bi-plus-circle-fill"></i> + Tambah Visit
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filter Controls -->
            <form method="GET" action="{{ route('portal.itineraries') }}" class="vcal-filter-grid">
                <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
                
                <div class="vcal-field-group">
                    <label class="vcal-field-label">Pilih Periode</label>
                    <div style="display: flex; gap: 6px;">
                        <select name="month" class="vcal-select" style="flex: 1.4;" onchange="this.form.submit()">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                        <select name="year" class="vcal-select" style="flex: 1;" onchange="this.form.submit()">
                            @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Region / Area</label>
                    <select name="branch_id" class="vcal-select" onchange="this.form.submit()">
                        <option value="">Semua Area</option>
                        @foreach ($branches as $br)
                            <option value="{{ $br->id }}" {{ $branchId == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Karyawan / Promotor</label>
                    <select name="employee_id" class="vcal-select" onchange="this.form.submit()">
                        <option value="">Semua Karyawan</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->nik }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="vcal-field-group">
                    <label class="vcal-field-label">Pencarian Cepat</label>
                    <div style="display: flex; gap: 4px;">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Nama promotor / toko..." class="vcal-input">
                        <button type="submit" class="btn-action-blue" style="padding: 7px 12px; border-radius: 8px;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
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
                @foreach ($calendarDays as $day)
                    <div class="vcal-cell {{ $day['is_current_month'] ? '' : 'other-month' }} {{ $day['is_today'] ? 'today' : '' }}">
                        <div class="vcal-cell-top">
                            <span class="vcal-date-badge">{{ $day['day_number'] }}</span>
                            @if ($day['is_current_month'] && ($canCreateItinerary ?? true))
                                <button type="button" class="vcal-quick-add" title="Tambah Jadwal Visit pada tanggal {{ $day['date_string'] }}" onclick="openAddItineraryModal('{{ $day['date_string'] }}')">+</button>
                            @endif
                        </div>

                        <!-- Schedules List inside Day -->
                        <div class="vcal-cards-container">
                            @foreach ($day['schedules'] as $itin)
                                <div class="vcal-event-card {{ $itin->status }}" onclick="showItineraryDetail({{ json_encode($itin) }})">
                                    <div class="vcal-card-title">
                                        {{ $itin->employee?->full_name ?? 'Promotor' }}
                                    </div>
                                    <div class="vcal-card-meta">
                                        <span class="vcal-store-count">
                                            📍 {{ $itin->items->count() }} Toko
                                        </span>
                                        <span>
                                            @if($itin->is_strict_routing)
                                                <i class="bi bi-lock-fill vcal-lock-icon" title="Wajib Urut (Strict Routing)"></i>
                                            @else
                                                <i class="bi bi-unlock" style="color: #94a3b8; font-size: 10px;" title="Bebas Visit"></i>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: DETAIL ITINERARY & ROUTE STORES -->
<!-- ========================================== -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="detailEmpName">Detail Rute Kunjungan Toko</h3>
                <span id="detailDateFormatted" style="font-size: 0.82rem; color: var(--text-muted);"></span>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Profile Banner -->
            <div class="detail-emp-banner">
                <div class="detail-emp-info">
                    <div class="detail-avatar" id="detailAvatar">PS</div>
                    <div>
                        <div style="font-weight: 800; font-size: 1rem; color: var(--text-heading);" id="detailEmpFullName">-</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted);" id="detailEmpMeta">-</div>
                    </div>
                </div>
                <div id="detailRoutingBadge"></div>
            </div>

            <!-- Notes if any -->
            <div id="detailNotesContainer" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 12px; font-size: 0.82rem; color: #92400e; margin-bottom: 1rem;">
                <strong>Catatan:</strong> <span id="detailNotesText"></span>
            </div>

            <label class="form-label" style="margin-bottom: 0.6rem;">
                <i class="bi bi-signpost-split-fill" style="color: var(--brand-primary);"></i> Urutan Rute Toko Kunjungan (<span id="detailTotalStores">0</span> Toko)
            </label>

            <!-- Route Table -->
            <table class="route-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">NO</th>
                        <th>NAMA TOKO / OUTLET</th>
                        <th>TIPE VISIT</th>
                        <th>CHECK-IN AWAL</th>
                    </tr>
                </thead>
                <tbody id="detailRouteBody">
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            @if($canDeleteItinerary ?? true)
                <form id="deleteItineraryForm" method="POST" action="" style="margin-right: auto;" onsubmit="return confirm('Hapus seluruh jadwal visit itinerari pada tanggal ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-action-green" style="background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">
                        <i class="bi bi-trash-fill"></i> Hapus Itinerari
                    </button>
                </form>
            @endif

            @if($canUpdateItinerary ?? true)
                <button type="button" class="btn-action-blue" id="btnEditItinFromDetail" onclick="editItineraryFromDetail()">
                    <i class="bi bi-pencil-fill"></i> Edit Rute
                </button>
            @endif
            <button type="button" class="filter-input" onclick="closeDetailModal()">Tutup</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD / EDIT ITINERARY (FORM) -->
<!-- ========================================== -->
<div class="modal-overlay" id="itineraryModal">
    <div class="modal-box" style="max-width: 840px;">
        <form id="itineraryForm" method="POST" action="{{ route('portal.itineraries.store', ['p' => $tenantPrincipal->id]) }}">
            @csrf
            <input type="hidden" name="_method" id="itinFormMethod" value="POST">
            <div class="modal-header">
                <h3 class="modal-title" id="itinModalTitle">Tambah Penjadwalan Rute Visit Promotor</h3>
                <button type="button" class="modal-close-btn" onclick="closeItineraryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group" id="itinEmployeeGroup">
                        <label class="form-label">Pilih Karyawan / Promotor *</label>
                        <select name="employee_id" id="itin_employee_id" class="form-control" required>
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->full_name }} ({{ $emp->nik }}) - {{ $emp->branch?->name ?? 'Pusat' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="creationTypeGroup">
                        <label class="form-label">Tipe Penjadwalan *</label>
                        <select name="creation_type" id="creation_type" class="form-control" required onchange="toggleCreationType()">
                            <option value="single">Satu Hari Tertentu (Single Day)</option>
                            <option value="month">Satu Bulan Penuh (Full Month)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group" id="singleDateGroup">
                        <label class="form-label">Tanggal Visit *</label>
                        <input type="date" name="date" id="itin_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="form-group" id="monthYearGroup" style="display: none;">
                        <label class="form-label">Bulan & Tahun *</label>
                        <div style="display: flex; gap: 6px;">
                            <select name="month" class="form-control" style="flex: 1.4;">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select name="year" class="form-control" style="flex: 1;">
                                @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Otomatis membuat jadwal visit untuk semua hari kerja (Senin-Sabtu) dalam bulan tersebut.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Penjadwalan *</label>
                        <select name="status" id="itin_status" class="form-control" required>
                            <option value="approved">Approved (Siap Dijalankan)</option>
                            <option value="draft">Draft (Konsep)</option>
                            <option value="cancelled">Cancelled (Dibatalkan)</option>
                        </select>
                    </div>
                </div>

                <!-- Strict Routing Toggle Box -->
                <div style="background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 12px; padding: 12px 16px; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-weight: 800; color: #9f1239; font-size: 0.88rem;">
                            <i class="bi bi-shield-lock-fill"></i> Aturan Routing Visit Wajib Berurutan (Strict Routing)
                        </div>
                        <div style="font-size: 0.75rem; color: #be123c;">
                            Jika diaktifkan, Promotor wajib menyelesaikan check-in di toko 1 sebelum bisa check-in di toko 2, dst.
                        </div>
                    </div>
                    <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                        <input type="checkbox" name="is_strict_routing" id="is_strict_routing" value="1" style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px;" onclick="this.previousElementSibling.checked = !this.previousElementSibling.checked"></span>
                    </label>
                </div>

                <!-- Dynamic Store Repeater -->
                <div class="repeater-box">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <label class="form-label" style="margin: 0;">
                            <i class="bi bi-shop"></i> Toko / Outlet Kunjungan (Urutan Rute)
                        </label>
                        <button type="button" class="btn-action-blue" style="padding: 4px 10px; font-size: 0.75rem;" onclick="addStoreRow()">
                            + Tambah Toko
                        </button>
                    </div>

                    <div id="storeRowsContainer">
                        <!-- Dynamic Rows will be inserted here -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Umum (Opsional)</label>
                    <textarea name="notes" id="itin_notes" rows="2" class="form-control" placeholder="Contoh: Fokus cek expired date produk, display promo weekend..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="filter-input" onclick="closeItineraryModal()">Batal</button>
                <button type="submit" class="btn-action-blue" id="btnSubmitItinerary">
                    <i class="bi bi-check-circle-fill"></i> Simpan Penjadwalan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: IMPORT VISIT SCHEDULE (EXCEL) -->
<!-- ========================================== -->
<div class="modal-overlay" id="importModal">
    <div class="modal-box">
        <form method="POST" action="{{ route('portal.itineraries.import', ['p' => $tenantPrincipal->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="bi bi-file-earmark-excel-fill" style="color: #16a34a;"></i> Import Jadwal Visit via Excel
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <label class="form-label">Langkah 1: Unduh Format Template Excel</label>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                    Unduh file template resmi berikut dan isi data rute toko promotor:
                </p>
                <div style="margin-bottom: 1.25rem;">
                    <a href="{{ route('portal.itineraries.template', ['p' => $tenantPrincipal->id]) }}" class="btn-action-green" style="text-decoration: none; display: inline-flex;">
                        <i class="bi bi-download"></i> Unduh Template_Import_Visit_Schedule.xlsx
                    </a>
                </div>

                <label class="form-label">Langkah 2: Upload File Excel / CSV Hasil Pengisian</label>
                <div class="dropzone-container" id="dropzoneItin">
                    <input type="file" name="file" id="itinFileInput" class="file-input-hidden" accept=".xlsx,.xls,.csv" required onchange="handleItinFileSelected(this)">
                    <div class="dropzone-icon">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                    </div>
                    <div class="dropzone-title" id="dropzoneItinTitle">Klik atau Drag & Drop File Excel Disini</div>
                    <div class="dropzone-subtitle" id="dropzoneItinSubtitle">Mendukung format .xlsx, .xls, .csv (Maks. 10MB)</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="filter-input" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="btn-action-green">
                    <i class="bi bi-upload"></i> Proses Import File
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const workLocationsData = @json($workLocations);
    let currentSelectedItin = null;

    function openAddItineraryModal(defaultDate = null) {
        document.getElementById('itinFormMethod').value = 'POST';
        document.getElementById('itineraryForm').action = "{{ route('portal.itineraries.store', ['p' => $tenantPrincipal->id]) }}";
        document.getElementById('itinModalTitle').innerText = 'Tambah Penjadwalan Rute Visit Promotor';
        document.getElementById('btnSubmitItinerary').innerHTML = '<i class="bi bi-check-circle-fill"></i> Simpan Penjadwalan';
        document.getElementById('itinEmployeeGroup').style.display = 'block';
        document.getElementById('creationTypeGroup').style.display = 'block';
        document.getElementById('creation_type').value = 'single';
        document.getElementById('itin_employee_id').value = '';
        document.getElementById('itin_date').value = defaultDate || "{{ date('Y-m-d') }}";
        document.getElementById('itin_status').value = 'approved';
        document.getElementById('is_strict_routing').checked = false;
        document.getElementById('itin_notes').value = '';
        
        toggleCreationType();
        resetStoreRows();
        addStoreRow(); // Default 1 row

        document.getElementById('itineraryModal').classList.add('active');
    }

    function editItineraryFromDetail() {
        if (!currentSelectedItin) return;
        closeDetailModal();

        document.getElementById('itinFormMethod').value = 'PUT';
        document.getElementById('itineraryForm').action = "/portal/itineraries/" + currentSelectedItin.id + "?p={{ $tenantPrincipal->id }}";
        document.getElementById('itinModalTitle').innerText = 'Edit Rute Visit: ' + (currentSelectedItin.employee ? currentSelectedItin.employee.full_name : '');
        document.getElementById('btnSubmitItinerary').innerHTML = '<i class="bi bi-check-circle-fill"></i> Update Penjadwalan';
        document.getElementById('itinEmployeeGroup').style.display = 'none';
        document.getElementById('creationTypeGroup').style.display = 'none';
        document.getElementById('singleDateGroup').style.display = 'block';
        document.getElementById('monthYearGroup').style.display = 'none';

        document.getElementById('itin_date').value = currentSelectedItin.date;
        document.getElementById('itin_status').value = currentSelectedItin.status || 'approved';
        document.getElementById('is_strict_routing').checked = !!currentSelectedItin.is_strict_routing;
        document.getElementById('itin_notes').value = currentSelectedItin.notes || '';

        resetStoreRows();
        if (currentSelectedItin.items && currentSelectedItin.items.length > 0) {
            currentSelectedItin.items.forEach(item => {
                addStoreRow(item.work_location_id, item.visit_type);
            });
        } else {
            addStoreRow();
        }

        document.getElementById('itineraryModal').classList.add('active');
    }

    function closeItineraryModal() {
        document.getElementById('itineraryModal').classList.remove('active');
    }

    function showItineraryDetail(itin) {
        currentSelectedItin = itin;
        const emp = itin.employee || {};
        
        document.getElementById('detailEmpFullName').innerText = emp.full_name || 'Promotor';
        document.getElementById('detailEmpMeta').innerText = 'NIK: ' + (emp.nik || '-') + ' • Area: ' + (emp.branch ? emp.branch.name : 'Pusat');
        document.getElementById('detailAvatar').innerText = (emp.full_name ? emp.full_name.substring(0, 2) : 'PS');
        document.getElementById('detailDateFormatted').innerText = 'Tanggal Visit: ' + itin.date;
        document.getElementById('deleteItineraryForm').action = "/portal/itineraries/" + itin.id + "?p={{ $tenantPrincipal->id }}";

        // Routing badge
        const badgeDiv = document.getElementById('detailRoutingBadge');
        if (itin.is_strict_routing) {
            badgeDiv.innerHTML = '<span style="background: #ffe4e6; color: #e11d48; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800;"><i class="bi bi-lock-fill"></i> WAJIB URUT (STRICT)</span>';
        } else {
            badgeDiv.innerHTML = '<span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;"><i class="bi bi-unlock"></i> BEBAS VISIT</span>';
        }

        // Notes
        const notesCont = document.getElementById('detailNotesContainer');
        if (itin.notes) {
            document.getElementById('detailNotesText').innerText = itin.notes;
            notesCont.style.display = 'block';
        } else {
            notesCont.style.display = 'none';
        }

        // Render items
        const tbody = document.getElementById('detailRouteBody');
        tbody.innerHTML = '';
        const items = itin.items || [];
        document.getElementById('detailTotalStores').innerText = items.length;

        items.forEach((item, index) => {
            const loc = item.work_location || {};
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align: center;"><span class="seq-circle">${index + 1}</span></td>
                <td>
                    <div style="font-weight: 700; color: var(--text-heading);">${loc.name || 'Toko'}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">${loc.address || ''}</div>
                </td>
                <td><span style="background: #e2f6ee; color: #16a34a; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700;">${item.visit_type || 'Reguler'}</span></td>
                <td>${item.is_checkin_location ? '<span style="color: #2563eb; font-weight: 700; font-size: 11px;">📍 Check-in Awal</span>' : '<span style="color: var(--text-muted);">-</span>'}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('detailModal').classList.add('active');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('active');
    }

    function toggleCreationType() {
        const type = document.getElementById('creation_type').value;
        if (type === 'month') {
            document.getElementById('singleDateGroup').style.display = 'none';
            document.getElementById('monthYearGroup').style.display = 'block';
        } else {
            document.getElementById('singleDateGroup').style.display = 'block';
            document.getElementById('monthYearGroup').style.display = 'none';
        }
    }

    function resetStoreRows() {
        document.getElementById('storeRowsContainer').innerHTML = '';
    }

    function addStoreRow(selectedLocId = null, selectedVisitType = 'Reguler') {
        const container = document.getElementById('storeRowsContainer');
        const rowIndex = container.children.length + 1;

        let optionsHtml = '<option value="">-- Pilih Toko / Lokasi --</option>';
        workLocationsData.forEach(loc => {
            const sel = (selectedLocId && selectedLocId == loc.id) ? 'selected' : '';
            optionsHtml += `<option value="${loc.id}" ${sel}>${loc.name}</option>`;
        });

        const visitTypes = ['Reguler', 'Stock Check', 'Display Promo', 'Audit', 'Urgent'];
        let visitTypeOptions = '';
        visitTypes.forEach(vt => {
            const sel = (selectedVisitType && selectedVisitType == vt) ? 'selected' : '';
            visitTypeOptions += `<option value="${vt}" ${sel}>${vt}</option>`;
        });

        const rowDiv = document.createElement('div');
        rowDiv.className = 'store-row-item';
        rowDiv.innerHTML = `
            <span class="seq-circle">${rowIndex}</span>
            <select name="locations[]" class="form-control" style="flex: 2;" required>
                ${optionsHtml}
            </select>
            <select name="visit_types[]" class="form-control" style="flex: 1.2;">
                ${visitTypeOptions}
            </select>
            <button type="button" class="btn-icon-action btn-icon-delete" onclick="removeStoreRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        `;
        container.appendChild(rowDiv);
    }

    function removeStoreRow(btn) {
        const container = document.getElementById('storeRowsContainer');
        if (container.children.length <= 1) {
            alert('Minimal 1 toko kunjungan harus ada!');
            return;
        }
        btn.closest('.store-row-item').remove();
        // Update sequence numbers
        Array.from(container.children).forEach((el, i) => {
            el.querySelector('.seq-circle').innerText = (i + 1);
        });
    }

    function openImportModal() {
        document.getElementById('importModal').classList.add('active');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.remove('active');
    }

    function handleItinFileSelected(input) {
        if (input.files && input.files[0]) {
            const f = input.files[0];
            document.getElementById('dropzoneItinTitle').innerHTML = '<strong style="color: #16a34a;">' + f.name + '</strong>';
            document.getElementById('dropzoneItinSubtitle').innerText = 'Ukuran: ' + (f.size / 1024).toFixed(1) + ' KB - Siap diupload!';
            document.getElementById('dropzoneItin').style.borderColor = '#16a34a';
            document.getElementById('dropzoneItin').style.background = '#e2f6ee';
        }
    }
</script>
@endpush
@endsection
