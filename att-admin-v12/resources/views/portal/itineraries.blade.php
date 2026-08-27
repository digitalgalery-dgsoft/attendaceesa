@extends('portal.layout')

@section('title', 'Jadwal Kunjungan (Itinerari) - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Jadwal Kunjungan (Itinerari Toko)')
@section('breadcrumb_active', 'Itinerari Kunjungan')

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

    .btn-add-itinerary {
        display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.35rem;
        background: var(--brand-primary); color: #ffffff; border: none; border-radius: 10px;
        font-size: 0.88rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px var(--brand-glow);
        transition: all 0.2s ease;
    }
    .btn-add-itinerary:hover { transform: translateY(-2px); filter: brightness(1.1); }

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
        background: #ffffff; border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh;
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

    /* Routing Toggle Switch */
    .toggle-container {
        background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px;
        padding: 0.9rem 1.1rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;
    }
    .switch-toggle { position: relative; display: inline-block; width: 46px; height: 24px; }
    .switch-toggle input { opacity: 0; width: 0; height: 0; }
    .switch-slider {
        position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1;
        transition: .3s; border-radius: 24px;
    }
    .switch-slider:before {
        position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
        background-color: white; transition: .3s; border-radius: 50%;
    }
    input:checked + .switch-slider { background-color: var(--brand-primary); }
    input:checked + .switch-slider:before { transform: translateX(22px); }

    .route-item-row {
        background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px;
        padding: 0.75rem; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 0.6rem;
    }
    .route-badge {
        width: 26px; height: 26px; border-radius: 50%; background: var(--brand-primary);
        color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 800; flex-shrink: 0;
    }
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
        <div class="page-icon-large"><i class="fa-solid fa-route"></i></div>
        <div>
            <h2 class="page-title-text">Jadwal Visitasi & Itinerari Promotor</h2>
            <div style="font-size: 0.82rem; color: var(--text-muted);">Daftar rencana rute kunjungan promotor dan MD per tanggal</div>
        </div>
    </div>
    <div class="page-header-actions">
        @if($canCreateItinerary ?? true)
            <button type="button" class="btn-import-excel" onclick="openImportModal()">
                <i class="fa-solid fa-file-excel"></i> Import Excel Itinerari
            </button>
            <button type="button" class="btn-add-itinerary" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i> Tambah Jadwal Itinerari
            </button>
        @endif
    </div>
</div>

<div class="filter-card">
    <form action="{{ route('portal.itineraries') }}" method="GET" class="filter-grid">
        <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">
        <div>
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Tanggal</label>
            <input type="date" name="date" class="filter-input" value="{{ $date }}" onchange="this.form.submit()">
        </div>
        <div style="flex: 1; min-width: 200px;">
            <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 2px;">Pencarian</label>
            <input type="text" name="q" class="filter-input" style="width: 100%;" placeholder="Cari nama promotor / NIK..." value="{{ $search }}">
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
                <th>Tanggal</th>
                <th>Promotor / SPG</th>
                <th>Cabang / Area</th>
                <th>Daftar Toko Kunjungan (Rute Itinerari)</th>
                <th>Aturan Routing</th>
                <th>Catatan</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itineraries as $itn)
                <tr>
                    <td style="font-weight: 700; white-space: nowrap;">
                        {{ Carbon\Carbon::parse($itn->date)->translatedFormat('d M Y') }}
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--text-heading);">{{ $itn->employee?->full_name ?? 'Petugas' }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">NIK: {{ $itn->employee?->nik ?? '-' }}</div>
                    </td>
                    <td>{{ $itn->employee?->branch?->name ?? '-' }}</td>
                    <td>
                        @if($itn->items && $itn->items->count() > 0)
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                @foreach($itn->items as $item)
                                    <div style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8rem;">
                                        <span style="width: 20px; height: 20px; border-radius: 50%; background: var(--brand-light); color: var(--brand-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800;">
                                            {{ $item->sequence ?? ($loop->index + 1) }}
                                        </span>
                                        <strong>{{ $item->workLocation?->name ?? 'Toko / Outlet' }}</strong>
                                        @if($item->visit_type)
                                            <span style="font-size: 0.7rem; color: var(--text-muted);">({{ $item->visit_type }})</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span style="color: #94a3b8; font-size: 0.8rem;">Belum ada rute toko</span>
                        @endif
                    </td>
                    <td>
                        @if($itn->is_strict_routing)
                            <span style="padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #fee2e2; color: #b91c1c; display: inline-flex; align-items: center; gap: 0.3rem;">
                                <i class="fa-solid fa-lock" style="font-size: 0.7rem;"></i> Wajib Urut
                            </span>
                        @else
                            <span style="padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem; background: #dcfce7; color: #15803d; display: inline-flex; align-items: center; gap: 0.3rem;">
                                <i class="fa-solid fa-unlock" style="font-size: 0.7rem;"></i> Bebas Visit
                            </span>
                        @endif
                    </td>
                    <td>{{ $itn->notes ?? '-' }}</td>
                    <td style="text-align: right; white-space: nowrap;">
                        @if($canUpdateItinerary ?? true)
                            <button type="button" class="btn-icon-action" title="Edit Itinerari" onclick='openEditModal(@json($itn))'>
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        @endif
                        @if($canDeleteItinerary ?? true)
                            <form action="{{ route('portal.itineraries.destroy', ['id' => $itn->id, 'p' => $tenantPrincipal->id]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rute itinerari ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Itinerari">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        Belum ada jadwal itinerari pada tanggal yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-color);">
        {{ $itineraries->appends(request()->query())->links('portal.pagination') }}
    </div>
</div>

<!-- Modal Tambah Jadwal Itinerari -->
<div class="modal-overlay" id="addItineraryModal">
    <div class="modal-container">
        <form action="{{ route('portal.itineraries.store', ['p' => $tenantPrincipal->id]) }}" method="POST">
            @csrf
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-route" style="color: var(--brand-primary);"></i>
                    <span>Tambah Jadwal Visitasi (Itinerari)</span>
                </div>
                <button type="button" class="btn-icon-action" onclick="closeModal('addItineraryModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Promotor / SPG / Petugas <span class="req">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- Pilih Promotor --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->nik }}) - {{ $emp->branch?->name ?? 'Cabang' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Kunjungan <span class="req">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ Carbon\Carbon::today()->format('Y-m-d') }}" required>
                </div>

                <!-- Toggle Routing Strict Rule -->
                <div class="toggle-container">
                    <div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-heading); display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid fa-signs-post" style="color: var(--brand-primary);"></i>
                            <span>Aturan Routing Visitasi</span>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                            Aktifkan toggle ini jika karyawan wajib check-in mengikuti urutan toko (1 -> 2 -> 3).
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span id="routingLabelAdd" style="font-size: 0.78rem; font-weight: 700; color: #15803d;">Bebas Visit</span>
                        <label class="switch-toggle">
                            <input type="checkbox" name="is_strict_routing" value="1" id="strictToggleAdd" onchange="toggleRoutingLabel(this, 'routingLabelAdd')">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Dynamic Stores Container -->
                <div class="form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                        <label class="form-label" style="margin-bottom: 0;">Rute Toko / Lokasi Kunjungan <span class="req">*</span></label>
                        <button type="button" class="btn-action-primary" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;" onclick="addStoreRow('addStoresList')">
                            <i class="fa-solid fa-plus"></i> Tambah Toko
                        </button>
                    </div>
                    <div id="addStoresList">
                        <!-- Initial 1st row -->
                        <div class="route-item-row">
                            <div class="route-badge">1</div>
                            <div style="flex: 2;">
                                <select name="locations[]" class="form-control" style="padding: 0.45rem 0.7rem; font-size: 0.82rem;" required>
                                    <option value="">-- Pilih Toko Kunjungan --</option>
                                    @foreach($workLocations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->city ?? $loc->branch?->name ?? 'Area' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex: 1.2;">
                                <select name="visit_types[]" class="form-control" style="padding: 0.45rem 0.7rem; font-size: 0.82rem;">
                                    <option value="Reguler">Reguler</option>
                                    <option value="Stock Check">Stock Check</option>
                                    <option value="Display Promo">Display Promo</option>
                                    <option value="Audit">Audit</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <button type="button" class="btn-icon-action btn-icon-delete" onclick="removeStoreRow(this, 'addStoresList')" style="flex-shrink: 0;"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan / Rencana Kerja</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan atau instruksi khusus untuk promotor..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-primary" style="background: #e2e8f0; color: #475569;" onclick="closeModal('addItineraryModal')">Batal</button>
                <button type="submit" class="btn-add-itinerary"><i class="fa-solid fa-save"></i> Simpan Itinerari</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Jadwal Itinerari -->
<div class="modal-overlay" id="editItineraryModal">
    <div class="modal-container">
        <form id="editItineraryForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-pen-to-square" style="color: var(--brand-primary);"></i>
                    <span>Edit Jadwal Itinerari</span>
                </div>
                <button type="button" class="btn-icon-action" onclick="closeModal('editItineraryModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Promotor</label>
                    <input type="text" id="edit_itn_employee_name" class="form-control" readonly style="background: #f1f5f9;">
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Kunjungan <span class="req">*</span></label>
                    <input type="date" name="date" id="edit_itn_date" class="form-control" required>
                </div>

                <!-- Toggle Routing Strict Rule -->
                <div class="toggle-container">
                    <div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-heading); display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid fa-signs-post" style="color: var(--brand-primary);"></i>
                            <span>Aturan Routing Visitasi</span>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                            Aktifkan toggle ini jika karyawan wajib check-in mengikuti urutan toko (1 -> 2 -> 3).
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span id="routingLabelEdit" style="font-size: 0.78rem; font-weight: 700; color: #15803d;">Bebas Visit</span>
                        <label class="switch-toggle">
                            <input type="checkbox" name="is_strict_routing" value="1" id="strictToggleEdit" onchange="toggleRoutingLabel(this, 'routingLabelEdit')">
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Dynamic Stores Container -->
                <div class="form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                        <label class="form-label" style="margin-bottom: 0;">Rute Toko / Lokasi Kunjungan <span class="req">*</span></label>
                        <button type="button" class="btn-action-primary" style="padding: 0.35rem 0.85rem; font-size: 0.78rem;" onclick="addStoreRow('editStoresList')">
                            <i class="fa-solid fa-plus"></i> Tambah Toko
                        </button>
                    </div>
                    <div id="editStoresList">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan / Rencana Kerja</label>
                    <textarea name="notes" id="edit_itn_notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action-primary" style="background: #e2e8f0; color: #475569;" onclick="closeModal('editItineraryModal')">Batal</button>
                <button type="submit" class="btn-add-itinerary"><i class="fa-solid fa-save"></i> Perbarui Itinerari</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Excel Itinerari -->
<div class="modal-overlay" id="importItineraryModal">
    <div class="modal-container">
        <form action="{{ route('portal.itineraries.import', ['p' => $tenantPrincipal->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fa-solid fa-file-excel" style="color: #16a34a;"></i>
                    <span>Import Jadwal Itinerari dari Excel / CSV</span>
                </div>
                <button type="button" class="btn-icon-action" onclick="closeModal('importItineraryModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; color: #166534; margin-bottom: 0.35rem; font-size: 0.88rem;">Panduan Import:</div>
                    <div style="font-size: 0.8rem; color: #15803d; line-height: 1.5;">
                        Kolom file Excel / CSV: <strong>nik_promotor, nama_promotor, tanggal (YYYY-MM-DD), aturan_routing (Wajib Urut / Bebas), toko_1, toko_2, toko_3, catatan</strong>.
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <a href="{{ route('portal.itineraries.template', ['p' => $tenantPrincipal->id]) }}" class="btn-action-primary" style="background: #16a34a; font-size: 0.78rem; padding: 0.4rem 0.9rem;">
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
                <button type="button" class="btn-action-primary" style="background: #e2e8f0; color: #475569;" onclick="closeModal('importItineraryModal')">Batal</button>
                <button type="submit" class="btn-import-excel"><i class="fa-solid fa-upload"></i> Unggah & Proses Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const availableWorkLocations = @json($workLocations);

    function openAddModal() {
        document.getElementById('addItineraryModal').classList.add('active');
    }
    function openImportModal() {
        document.getElementById('importItineraryModal').classList.add('active');
    }
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function toggleRoutingLabel(checkbox, labelId) {
        const label = document.getElementById(labelId);
        if (checkbox.checked) {
            label.innerText = 'Wajib Urut (Strict)';
            label.style.color = '#b91c1c';
        } else {
            label.innerText = 'Bebas Visit';
            label.style.color = '#15803d';
        }
    }

    function addStoreRow(containerId, selectedLocId = '', selectedType = 'Reguler') {
        const container = document.getElementById(containerId);
        const rows = container.querySelectorAll('.route-item-row');
        const nextSeq = rows.length + 1;

        let optionsHtml = '<option value="">-- Pilih Toko Kunjungan --</option>';
        availableWorkLocations.forEach(loc => {
            const isSel = (loc.id == selectedLocId) ? 'selected' : '';
            optionsHtml += `<option value="${loc.id}" ${isSel}>${loc.name} (${loc.city || 'Area'})</option>`;
        });

        const row = document.createElement('div');
        row.className = 'route-item-row';
        row.innerHTML = `
            <div class="route-badge">${nextSeq}</div>
            <div style="flex: 2;">
                <select name="locations[]" class="form-control" style="padding: 0.45rem 0.7rem; font-size: 0.82rem;" required>
                    ${optionsHtml}
                </select>
            </div>
            <div style="flex: 1.2;">
                <select name="visit_types[]" class="form-control" style="padding: 0.45rem 0.7rem; font-size: 0.82rem;">
                    <option value="Reguler" ${selectedType === 'Reguler' ? 'selected' : ''}>Reguler</option>
                    <option value="Stock Check" ${selectedType === 'Stock Check' ? 'selected' : ''}>Stock Check</option>
                    <option value="Display Promo" ${selectedType === 'Display Promo' ? 'selected' : ''}>Display Promo</option>
                    <option value="Audit" ${selectedType === 'Audit' ? 'selected' : ''}>Audit</option>
                    <option value="Urgent" ${selectedType === 'Urgent' ? 'selected' : ''}>Urgent</option>
                </select>
            </div>
            <button type="button" class="btn-icon-action btn-icon-delete" onclick="removeStoreRow(this, '${containerId}')" style="flex-shrink: 0;"><i class="fa-solid fa-trash-can"></i></button>
        `;
        container.appendChild(row);
    }

    function removeStoreRow(btn, containerId) {
        const container = document.getElementById(containerId);
        const rows = container.querySelectorAll('.route-item-row');
        if (rows.length <= 1) {
            alert('Minimal harus ada 1 toko dalam rute itinerari!');
            return;
        }
        btn.closest('.route-item-row').remove();
        // Re-number badges
        const remaining = container.querySelectorAll('.route-item-row');
        remaining.forEach((r, idx) => {
            r.querySelector('.route-badge').innerText = idx + 1;
        });
    }

    function openEditModal(itn) {
        const form = document.getElementById('editItineraryForm');
        form.action = `/portal/itineraries/${itn.id}?p={{ $tenantPrincipal->id }}`;
        document.getElementById('edit_itn_employee_name').value = `${itn.employee?.full_name || 'Petugas'} (${itn.employee?.nik || '-'})`;
        document.getElementById('edit_itn_date').value = itn.date;
        document.getElementById('edit_itn_notes').value = itn.notes || '';

        const strictToggle = document.getElementById('strictToggleEdit');
        strictToggle.checked = itn.is_strict_routing ? true : false;
        toggleRoutingLabel(strictToggle, 'routingLabelEdit');

        const editList = document.getElementById('editStoresList');
        editList.innerHTML = '';

        if (itn.items && itn.items.length > 0) {
            itn.items.forEach(item => {
                addStoreRow('editStoresList', item.work_location_id, item.visit_type || 'Reguler');
            });
        } else {
            addStoreRow('editStoresList');
        }

        document.getElementById('editItineraryModal').classList.add('active');
    }
</script>
@endpush
