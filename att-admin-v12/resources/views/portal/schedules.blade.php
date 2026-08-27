@extends('portal.layout')

@section('title', 'Roster & Jadwal Kerja - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Roster & Jadwal Kerja Promotor')
@section('breadcrumb_active', 'Roster Jadwal')

@push('styles')
<style>
    .page-header-card {
        background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .page-header-left { display: flex; align-items: center; gap: 1.1rem; }
    .page-icon-large {
        width: 52px; height: 52px; border-radius: 14px; background: var(--brand-light); color: var(--brand-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;
    }
    .page-title-text { font-size: 1.35rem; font-weight: 800; color: var(--text-heading); margin-bottom: 0.25rem; }
    .page-header-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    
    .btn-add-schedule {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.35rem;
        background: var(--brand-primary); color: #ffffff; border: none; border-radius: 10px;
        font-size: 0.88rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px var(--brand-glow);
        transition: all 0.2s ease;
    }
    .btn-add-schedule:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .btn-working-group {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem;
        background: #6366f1; color: #ffffff; border: none; border-radius: 10px;
        font-size: 0.88rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        transition: all 0.2s ease;
    }
    .btn-working-group:hover { transform: translateY(-2px); filter: brightness(1.1); }
    
    .btn-import-excel {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem;
        background: #16a34a; color: #ffffff; border: none; border-radius: 10px;
        font-size: 0.88rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }
    .btn-import-excel:hover { transform: translateY(-2px); filter: brightness(1.1); }

    .filter-card { background: #ffffff; border: 1px solid var(--border-color); border-radius: 14px; padding: 1.1rem 1.4rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); }
    .filter-grid { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .filter-input { padding: 0.55rem 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; color: var(--text-heading); outline: none; background: #ffffff; }
    .btn-action-primary {
        background: var(--brand-gradient); color: #ffffff; padding: 0.55rem 1.2rem; border-radius: 8px; font-weight: 700;
        font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;
    }
    
    .table-container { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .custom-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left; }
    .custom-table th { background: #f8fafc; padding: 0.9rem 1.1rem; font-weight: 700; color: var(--text-heading); border-bottom: 1px solid var(--border-color); }
    .custom-table td { padding: 0.9rem 1.1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-body); }
    
    .btn-icon-action {
        width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center;
        justify-content: center; border: 1px solid var(--border-color); background: #ffffff;
        color: var(--text-body); cursor: pointer; transition: all 0.15s ease; font-size: 0.8rem;
    }
    .btn-icon-action:hover { background: var(--brand-light); color: var(--brand-primary); border-color: var(--brand-primary); }
    .btn-icon-delete:hover { background: #fee2e2; color: #dc2626; border-color: #dc2626; }

    /* Modern Modals */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px);
        z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        opacity: 0; pointer-events: none; transition: all 0.25s ease;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }
    .modal-box {
        background: #ffffff; border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh;
        overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: translateY(20px);
        transition: all 0.25s ease; border: 1px solid var(--border-color);
    }
    .modal-overlay.active .modal-box { transform: translateY(0); }
    .modal-header {
        padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; justify-content: space-between; background: #f8fafc;
    }
    .modal-title { font-size: 1.15rem; font-weight: 800; color: var(--text-heading); margin: 0; }
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
    
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.4rem; }
    .form-control {
        width: 100%; padding: 0.65rem 0.9rem; border: 1.5px solid var(--border-color); border-radius: 10px;
        font-size: 0.88rem; color: var(--text-heading); outline: none; transition: all 0.2s ease; background: #ffffff;
    }
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 3px var(--brand-light); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    /* Professional Dropzone */
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
    .dropzone-title { font-size: 0.95rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.25rem; }
    .dropzone-subtitle { font-size: 0.78rem; color: var(--text-muted); }
    .file-input-hidden { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }

    .template-card-box {
        display: flex; gap: 0.75rem; margin-bottom: 1.25rem;
    }
    .template-opt-btn {
        flex: 1; padding: 0.85rem 1rem; border: 1.5px solid var(--border-color); border-radius: 12px;
        background: #ffffff; text-align: left; text-decoration: none; display: flex; align-items: center;
        gap: 0.75rem; transition: all 0.2s ease; color: var(--text-heading);
    }
    .template-opt-btn:hover {
        border-color: var(--brand-primary); background: var(--brand-light); transform: translateY(-2px);
    }
    .template-opt-icon {
        width: 36px; height: 36px; border-radius: 8px; background: #e2f6ee; color: #16a34a;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
</style>
@endpush

@section('content')
<div class="content-container">

    <!-- Page Header -->
    <div class="page-header-card">
        <div class="page-header-left">
            <div class="page-icon-large">
                <i class="bi bi-calendar-range-fill"></i>
            </div>
            <div>
                <h1 class="page-title-text">Roster & Jadwal Kerja Karyawan</h1>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                    Atur jadwal shift harian, rentang tanggal, atau generate otomatis via Pola Kerja (Working Group).
                </p>
            </div>
        </div>
        <div class="page-header-actions">
            @if($canCreateRoster ?? true)
                <button type="button" class="btn-working-group" onclick="openWorkingGroupModal()">
                    <i class="bi bi-diagram-3-fill"></i> Input via Pola Kerja
                </button>
                <button type="button" class="btn-import-excel" onclick="openImportModal()">
                    <i class="bi bi-file-earmark-excel-fill"></i> Import Excel (2 Template)
                </button>
                <button type="button" class="btn-add-schedule" onclick="openAddScheduleModal()">
                    <i class="bi bi-plus-circle-fill"></i> + Tambah Roster
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-card">
        <form method="GET" action="{{ route('portal.schedules') }}" class="filter-grid">
            <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama karyawan / NIK..." class="filter-input" style="width: 100%;">
            </div>
            <div>
                <select name="month" class="filter-input" style="min-width: 130px;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <select name="year" class="filter-input" style="min-width: 90px;">
                    @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn-action-primary">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
            @if($search || $month != now()->month || $year != now()->year)
                <a href="{{ route('portal.schedules', ['p' => $tenantPrincipal->id]) }}" class="filter-input" style="text-decoration: none; text-align: center;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Schedule List Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>KARYAWAN</th>
                    <th>TANGGAL JADWAL</th>
                    <th>TIPE JADWAL</th>
                    <th>SHIFT KERJA</th>
                    <th>PENEMPATAN TOKO / LOKASI</th>
                    <th>CATATAN</th>
                    @if(($canUpdateRoster ?? true) || ($canDeleteRoster ?? true))
                        <th style="text-align: right;">AKSI</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $sch)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $sch->employee?->full_name ?? '-' }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $sch->employee?->nik ?? '-' }} &bull; Area: {{ $sch->employee?->branch?->name ?? 'Pusat' }}</div>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--text-heading);">
                                {{ \Carbon\Carbon::parse($sch->schedule_date)->isoFormat('dddd, D MMMM Y') }}
                            </span>
                        </td>
                        <td>
                            @php
                                $type = $sch->schedule_type ?? 'workday';
                                $badges = [
                                    'workday' => ['bg' => '#e2f6ee', 'text' => '#16a34a', 'label' => 'Hari Kerja'],
                                    'dayoff' => ['bg' => '#fee2e2', 'text' => '#dc2626', 'label' => 'Libur (Off)'],
                                    'holiday' => ['bg' => '#fef3c7', 'text' => '#d97706', 'label' => 'Hari Libur Nasional'],
                                    'remote' => ['bg' => '#e0e7ff', 'text' => '#4f46e5', 'label' => 'Remote / WFH'],
                                    'field' => ['bg' => '#f3e8ff', 'text' => '#9333ea', 'label' => 'Dinas Lapangan'],
                                ];
                                $b = $badges[$type] ?? $badges['workday'];
                            @endphp
                            <span style="background: {{ $b['bg'] }}; color: {{ $b['text'] }}; padding: 0.25rem 0.65rem; border-radius: 6px; font-weight: 700; font-size: 0.75rem;">
                                {{ $b['label'] }}
                            </span>
                        </td>
                        <td>
                            @if($sch->schedule_type === 'dayoff')
                                <span style="color: #94a3b8; font-style: italic;">- Libur -</span>
                            @elseif($sch->shift)
                                <span style="font-weight: 700; color: var(--text-heading);">{{ $sch->shift->name }}</span>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ \Carbon\Carbon::parse($sch->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sch->shift->end_time)->format('H:i') }}
                                </div>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-heading);">
                                {{ $sch->workLocation?->name ?? 'Toko / Lokasi Default' }}
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $sch->notes ?: '-' }}</span>
                        </td>
                        @if(($canUpdateRoster ?? true) || ($canDeleteRoster ?? true))
                            <td style="text-align: right; white-space: nowrap;">
                                @if($canUpdateRoster ?? true)
                                    <button type="button" class="btn-icon-action" title="Edit Jadwal" onclick="editSchedule({{ json_encode($sch) }})">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                @endif
                                @if($canDeleteRoster ?? true)
                                    <form action="{{ route('portal.schedules.destroy', ['id' => $sch->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Hapus jadwal karyawan pada tanggal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Jadwal">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <i class="bi bi-calendar-x" style="font-size: 2.5rem; opacity: 0.4; display: block; margin-bottom: 0.5rem;"></i>
                            Tidak ada jadwal roster pada periode bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($schedules->hasPages())
            <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
                {{ $schedules->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD / EDIT SCHEDULE -->
<!-- ========================================== -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal-box">
        <form id="scheduleForm" method="POST" action="{{ route('portal.schedules.store', ['p' => $tenantPrincipal->id]) }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Tambah Jadwal Roster Karyawan</h3>
                <button type="button" class="modal-close-btn" onclick="closeScheduleModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group" id="employeeSelectGroup">
                    <label class="form-label">Pilih Karyawan / Promotor *</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" data-location="{{ $emp->work_location_id }}">
                                {{ $emp->full_name }} ({{ $emp->nik }}) - {{ $emp->branch?->name ?? 'Pusat' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tipe Jadwal *</label>
                    <select name="schedule_type" id="schedule_type" class="form-control" required onchange="toggleScheduleType()">
                        <option value="workday">Hari Kerja (Workday)</option>
                        <option value="dayoff">Libur (Day Off)</option>
                        <option value="holiday">Hari Libur Nasional (Holiday)</option>
                        <option value="remote">Remote / WFH</option>
                        <option value="field">Dinas Luar / Lapangan (Field)</option>
                    </select>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai *</label>
                        <input type="date" name="schedule_date" id="schedule_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group" id="endDateGroup">
                        <label class="form-label">Tanggal Akhir (Opsional - Rentang)</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" placeholder="Kosongkan jika 1 hari">
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Isi jika ingin input jadwal berurutan sekaligus.</small>
                    </div>
                </div>

                <div class="form-grid-2" id="shiftAndLocationGrid">
                    <div class="form-group" id="shiftGroup">
                        <label class="form-label">Shift Kerja *</label>
                        <select name="shift_id" id="shift_id" class="form-control">
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->name }} ({{ \Carbon\Carbon::parse($sh->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sh->end_time)->format('H:i') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Penempatan Toko / Lokasi</label>
                        <select name="work_location_id" id="work_location_id" class="form-control">
                            <option value="">-- Sesuai Toko Homebase Karyawan --</option>
                            @foreach($workLocations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Contoh: Covering Toko A, Event Promo Weekend..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="filter-input" onclick="closeScheduleModal()">Batal</button>
                <button type="submit" class="btn-action-primary" id="btnSubmitSchedule">
                    <i class="bi bi-check-circle-fill"></i> Simpan Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: GENERATE VIA WORKING GROUP -->
<!-- ========================================== -->
<div class="modal-overlay" id="workingGroupModal">
    <div class="modal-box">
        <form method="POST" action="{{ route('portal.schedules.working_group', ['p' => $tenantPrincipal->id]) }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="bi bi-diagram-3-fill" style="color: #6366f1;"></i> Generate Jadwal via Pola Kerja (Working Group)
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeWorkingGroupModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    Generate jadwal kerja & hari libur (Day Off) otomatis berdasarkan aturan Pola Kerja (Working Group) yang sudah dikonfigurasi.
                </p>

                <div class="form-group">
                    <label class="form-label">Pilih Pola Kerja (Working Group) *</label>
                    <select name="working_group_id" class="form-control" required>
                        <option value="">-- Pilih Pola Kerja --</option>
                        @foreach($workingGroups as $wg)
                            <option value="{{ $wg->id }}">
                                {{ $wg->name }} &bull; ({{ $wg->members->count() }} Anggota terdaftar)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai *</label>
                        <input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Akhir *</label>
                        <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-t') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Karyawan</label>
                    <select name="employee_ids[]" class="form-control" multiple size="4" style="height: auto;">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" selected>{{ $emp->full_name }} ({{ $emp->nik }})</option>
                        @endforeach
                    </select>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Tekan Ctrl / Cmd untuk memilih beberapa karyawan tertentu. Kosongkan untuk semua anggota Pola Kerja.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="filter-input" onclick="closeWorkingGroupModal()">Batal</button>
                <button type="submit" class="btn-working-group">
                    <i class="bi bi-magic"></i> Generate Jadwal Otomatis
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: IMPORT EXCEL (2 TEMPLATE OPTIONS) -->
<!-- ========================================== -->
<div class="modal-overlay" id="importModal">
    <div class="modal-box">
        <form method="POST" action="{{ route('portal.schedules.import', ['p' => $tenantPrincipal->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="bi bi-file-earmark-excel-fill" style="color: #16a34a;"></i> Import Jadwal Karyawan via Excel
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <label class="form-label">Langkah 1: Unduh Format Template Excel</label>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                    Pilih format template yang Anda inginkan (Matrix Harian 1..31 atau Format Rentang Tanggal):
                </p>
                <div class="template-card-box">
                    <a href="{{ route('portal.schedules.template', ['p' => $tenantPrincipal->id, 'type' => 'matrix']) }}" class="template-opt-btn" title="Unduh Template Matrix Per-Tanggal">
                        <div class="template-opt-icon"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.85rem;">1. Format Matrix Harian</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Kolom Tanggal 1..31 (Excel)</div>
                        </div>
                    </a>
                    <a href="{{ route('portal.schedules.template', ['p' => $tenantPrincipal->id, 'type' => 'range']) }}" class="template-opt-btn" title="Unduh Template Rentang Tanggal">
                        <div class="template-opt-icon"><i class="bi bi-arrow-left-right"></i></div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.85rem;">2. Format Rentang Tanggal</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Tanggal Mulai s/d Akhir</div>
                        </div>
                    </a>
                </div>

                <label class="form-label">Langkah 2: Upload File Excel / CSV Hasil Pengisian</label>
                <div class="dropzone-container" id="dropzoneSchedule">
                    <input type="file" name="file" id="scheduleFileInput" class="file-input-hidden" accept=".xlsx,.xls,.csv" required onchange="handleFileSelected(this)">
                    <div class="dropzone-icon">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                    </div>
                    <div class="dropzone-title" id="dropzoneTitle">Klik atau Drag & Drop File Excel Disini</div>
                    <div class="dropzone-subtitle" id="dropzoneSubtitle">Mendukung format .xlsx, .xls, .csv (Maks. 10MB)</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="filter-input" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="btn-import-excel">
                    <i class="bi bi-upload"></i> Proses Import File
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddScheduleModal() {
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('scheduleForm').action = "{{ route('portal.schedules.store', ['p' => $tenantPrincipal->id]) }}";
        document.getElementById('modalTitle').innerText = 'Tambah Jadwal Roster Karyawan';
        document.getElementById('btnSubmitSchedule').innerHTML = '<i class="bi bi-check-circle-fill"></i> Simpan Jadwal';
        document.getElementById('employeeSelectGroup').style.display = 'block';
        document.getElementById('endDateGroup').style.display = 'block';
        document.getElementById('employee_id').value = '';
        document.getElementById('schedule_type').value = 'workday';
        document.getElementById('shift_id').value = "{{ $shifts->first()?->id ?? '' }}";
        document.getElementById('work_location_id').value = '';
        document.getElementById('notes').value = '';
        toggleScheduleType();
        document.getElementById('scheduleModal').classList.add('active');
    }

    function editSchedule(sch) {
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('scheduleForm').action = "/portal/schedules/" + sch.id + "?p={{ $tenantPrincipal->id }}";
        document.getElementById('modalTitle').innerText = 'Edit Jadwal Roster: ' + (sch.employee ? sch.employee.full_name : '');
        document.getElementById('btnSubmitSchedule').innerHTML = '<i class="bi bi-check-circle-fill"></i> Update Jadwal';
        document.getElementById('employeeSelectGroup').style.display = 'none';
        document.getElementById('endDateGroup').style.display = 'none';
        document.getElementById('schedule_date').value = sch.schedule_date;
        document.getElementById('schedule_type').value = sch.schedule_type || 'workday';
        document.getElementById('shift_id').value = sch.shift_id || '';
        document.getElementById('work_location_id').value = sch.work_location_id || '';
        document.getElementById('notes').value = sch.notes || '';
        toggleScheduleType();
        document.getElementById('scheduleModal').classList.add('active');
    }

    function closeScheduleModal() {
        document.getElementById('scheduleModal').classList.remove('active');
    }

    function openWorkingGroupModal() {
        document.getElementById('workingGroupModal').classList.add('active');
    }

    function closeWorkingGroupModal() {
        document.getElementById('workingGroupModal').classList.remove('active');
    }

    function openImportModal() {
        document.getElementById('importModal').classList.add('active');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.remove('active');
    }

    function toggleScheduleType() {
        const type = document.getElementById('schedule_type').value;
        const shiftGroup = document.getElementById('shiftGroup');
        const shiftSelect = document.getElementById('shift_id');
        if (type === 'dayoff') {
            shiftGroup.style.opacity = '0.4';
            shiftSelect.removeAttribute('required');
            shiftSelect.value = '';
        } else {
            shiftGroup.style.opacity = '1';
            shiftSelect.setAttribute('required', 'required');
        }
    }

    function handleFileSelected(input) {
        if (input.files && input.files[0]) {
            const f = input.files[0];
            document.getElementById('dropzoneTitle').innerHTML = '<strong style="color: #16a34a;">' + f.name + '</strong>';
            document.getElementById('dropzoneSubtitle').innerText = 'Ukuran: ' + (f.size / 1024).toFixed(1) + ' KB - Siap diupload!';
            document.getElementById('dropzoneSchedule').style.borderColor = '#16a34a';
            document.getElementById('dropzoneSchedule').style.background = '#e2f6ee';
        }
    }
</script>
@endpush
@endsection
