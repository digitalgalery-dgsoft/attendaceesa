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

    /* Modal Styles */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
        z-index: 9999; display: none; align-items: center; justify-content: center; padding: 1.5rem;
    }
    .modal-overlay.active { display: flex; animation: fadeIn 0.2s ease-out; }
    .modal-container {
        background: #ffffff; border-radius: 20px; width: 100%; max-width: 600px; max-height: 90vh;
        overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid var(--border-color);
    }
    .modal-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex;
        align-items: center; justify-content: space-between; position: sticky; top: 0; background: #ffffff; z-index: 10;
    }
    .modal-title { font-size: 1.15rem; font-weight: 800; color: var(--text-heading); display: flex; align-items: center; gap: 0.6rem; }
    .modal-body { padding: 1.5rem; }
    .modal-footer {
        padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc;
        display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; border-radius: 0 0 20px 20px;
    }
    .form-group { margin-bottom: 1.1rem; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.35rem; }
    .form-label span.req { color: #dc2626; }
    .form-control {
        width: 100%; padding: 0.65rem 0.9rem; border: 1px solid var(--border-color); border-radius: 10px;
        font-size: 0.88rem; color: var(--text-heading); outline: none; background: #ffffff; transition: border-color 0.2s ease;
    }
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 3px var(--brand-glow); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
@endpush

@section('content')
<!-- Success & Error Alert -->
@if(session('success'))
    <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.88rem; font-weight: 600;">
        <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.88rem; font-weight: 600;">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem;"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="page-header-card">
    <div class="page-header-left">
        <div class="page-icon-large"><i class="fa-solid fa-calendar-week"></i></div>
        <div>
            <h2 class="page-title-text">Jadwal Roster Promotor</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Jadwal penugasan shift dan lokasi toko kerja promotor</div>
        </div>
    </div>
    <div class="page-header-actions">
        @if($canCreateRoster ?? true)
            <button type="button" class="btn-import-excel" onclick="openImportModal()">
                <i class="fa-solid fa-file-excel"></i> Import Excel Roster
            </button>
            <button type="button" class="btn-add-schedule" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i> Tambah Jadwal Roster
            </button>
        @endif
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.schedules') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Bulan</label>
            <select name="month" class="filter-input" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
        </div>
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tahun</label>
            <select name="year" class="filter-input" onchange="this.form.submit()">
                @for($y = Carbon\Carbon::now()->year - 1; $y <= Carbon\Carbon::now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama karyawan / NIK..." value="{{ $search }}">
        </div>
        <div style="margin-top: 18px;">
            <button type="submit" class="btn-action-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Tanggal Jadwal</th>
                <th>Karyawan / SPG</th>
                <th>Cabang / Area</th>
                <th>Penempatan Toko</th>
                <th>Shift Kerja</th>
                <th>Jam Masuk - Pulang</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $sch)
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ Carbon\Carbon::parse($sch->schedule_date)->translatedFormat('d M Y') }}
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $sch->employee?->full_name ?? 'Karyawan' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $sch->employee?->nik ?? '-' }}</div>
                    </td>
                    <td>{{ $sch->employee?->branch?->name ?? '-' }}</td>
                    <td>{{ $sch->workLocation?->name ?? ($sch->employee?->workLocation?->name ?? 'Toko Default') }}</td>
                    <td>
                        <span style="font-weight: 700; color: var(--brand-primary);">{{ $sch->shift?->name ?? 'Normal Shift' }}</span>
                    </td>
                    <td>{{ $sch->shift?->start_time ?? '08:00' }} - {{ $sch->shift?->end_time ?? '17:00' }}</td>
                    <td style="text-align: right; white-space: nowrap;">
                        @if($canUpdateRoster ?? true)
                            <button type="button" class="btn-icon-action" title="Edit Jadwal" onclick='openEditModal(@json($sch))'>
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        @endif
                        @if($canDeleteRoster ?? true)
                            <form action="{{ route('portal.schedules.destroy', ['id' => $sch->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal roster ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Jadwal">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada jadwal roster pada periode bulan yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $schedules->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>

<!-- Modal Tambah Jadwal Roster -->
<div class="modal-overlay" id="addScheduleModal">
    <div class="modal-container">
        <form action="{{ route('portal.schedules.store', ['p' => $tenantPrincipal->id]) }}" method="POST">
            @csrf
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-calendar-plus" style="color: var(--brand-primary);"></i>
                    <span>Tambah Jadwal Roster</span>
                </div>
                <button type="button" class="btn-icon-action" onclick="closeModal('addScheduleModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Karyawan / Promotor <span class="req">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->nik }}) - {{ $emp->branch?->name ?? 'Cabang' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Dari Tanggal <span class="req">*</span></label>
                        <input type="date" name="schedule_date" class="form-control" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sampai Tanggal (Opsional Range)</label>
                        <input type="date" name="end_date" class="form-control" placeholder="Pilih jika lebih dari 1 hari">
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">Kosongkan jika hanya 1 hari</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Shift Kerja <span class="req">*</span></label>
                    <select name="shift_id" class="form-control" required>
                        @foreach($shifts as $shf)
                            <option value="{{ $shf->id }}">{{ $shf->name }} ({{ $shf->start_time }} - {{ $shf->end_time }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Penempatan Lokasi Toko</label>
                    <select name="work_location_id" class="form-control">
                        <option value="">-- Gunakan Toko Default Karyawan --</option>
                        @foreach($workLocations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->city ?? $loc->branch?->name ?? 'Area' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan / Keterangan</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan penugasan khusus..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-primary" style="background: #e2e8f0; color: #475569;" onclick="closeModal('addScheduleModal')">Batal</button>
                <button type="submit" class="btn-add-schedule"><i class="fa-solid fa-save"></i> Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Jadwal Roster -->
<div class="modal-overlay" id="editScheduleModal">
    <div class="modal-container">
        <form id="editScheduleForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-pen-to-square" style="color: var(--brand-primary);"></i>
                    <span>Edit Jadwal Roster</span>
                </div>
                <button type="button" class="btn-icon-action" onclick="closeModal('editScheduleModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Karyawan</label>
                    <input type="text" id="edit_employee_name" class="form-control" readonly style="background: #f1f5f9;">
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Jadwal <span class="req">*</span></label>
                    <input type="date" name="schedule_date" id="edit_schedule_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Shift Kerja <span class="req">*</span></label>
                    <select name="shift_id" id="edit_shift_id" class="form-control" required>
                        @foreach($shifts as $shf)
                            <option value="{{ $shf->id }}">{{ $shf->name }} ({{ $shf->start_time }} - {{ $shf->end_time }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Penempatan Lokasi Toko</label>
                    <select name="work_location_id" id="edit_work_location_id" class="form-control">
                        <option value="">-- Toko Default Karyawan --</option>
                        @foreach($workLocations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan / Keterangan</label>
                    <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-primary" style="background: #e2e8f0; color: #475569;" onclick="closeModal('editScheduleModal')">Batal</button>
                <button type="submit" class="btn-add-schedule"><i class="fa-solid fa-save"></i> Perbarui Jadwal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Excel Roster -->
<div class="modal-overlay" id="importScheduleModal">
    <div class="modal-container">
        <form action="{{ route('portal.schedules.import', ['p' => $tenantPrincipal->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-file-excel" style="color: #16a34a;"></i>
                    <span>Import Jadwal Roster dari Excel / CSV</span>
                </div>
                <button type="button" class="btn-icon-action" onclick="closeModal('importScheduleModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; color: #166534; margin-bottom: 0.35rem; font-size: 0.88rem;">Panduan Import:</div>
                    <div style="font-size: 0.8rem; color: #15803d; line-height: 1.5;">
                        Pastikan kolom pada file Excel / CSV memuat: <strong>nik_karyawan, nama_karyawan, tanggal_jadwal (YYYY-MM-DD), nama_shift, nama_toko_lokasi, catatan</strong>.
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <a href="{{ route('portal.schedules.template', ['p' => $tenantPrincipal->id]) }}" class="btn-action-primary" style="background: #16a34a; font-size: 0.78rem; padding: 0.4rem 0.9rem;">
                            <i class="fa-solid fa-download"></i> Download Template CSV
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Pilih File Excel / CSV <span class="req">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-primary" style="background: #e2e8f0; color: #475569;" onclick="closeModal('importScheduleModal')">Batal</button>
                <button type="submit" class="btn-import-excel"><i class="fa-solid fa-upload"></i> Unggah & Proses Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openAddModal() {
        document.getElementById('addScheduleModal').classList.add('active');
    }
    function openImportModal() {
        document.getElementById('importScheduleModal').classList.add('active');
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    function openEditModal(sch) {
        const form = document.getElementById('editScheduleForm');
        form.action = `/portal/schedules/${sch.id}?p={{ $tenantPrincipal->id }}`;
        document.getElementById('edit_employee_name').value = `${sch.employee?.full_name || 'Karyawan'} (${sch.employee?.nik || '-'})`;
        document.getElementById('edit_schedule_date').value = sch.schedule_date;
        document.getElementById('edit_shift_id').value = sch.shift_id;
        document.getElementById('edit_work_location_id').value = sch.work_location_id || '';
        document.getElementById('edit_notes').value = sch.notes || '';
        document.getElementById('editScheduleModal').classList.add('active');
    }
</script>
@endpush
