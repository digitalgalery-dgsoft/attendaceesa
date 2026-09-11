@extends('portal.layout')

@section('title', 'Master Produk Kompetitor - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Master Produk & Subbrand Kompetitor')
@section('breadcrumb_active', 'Produk Kompetitor')

@push('styles')
<style>
    .comp-header {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .comp-header-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .comp-icon-large {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: #fef2f2;
        color: #e11d48;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .comp-title-text {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 0.25rem;
    }

    .comp-meta-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.82rem;
        color: var(--text-muted);
    }

    .btn-add-comp {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.35rem;
        background: #e11d48;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
        transition: all 0.2s ease;
    }

    .btn-add-comp:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    .stats-grid-comp {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card-comp {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.25rem 1.4rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .filter-card-comp {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .comp-table-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .comp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.86rem;
    }

    .comp-table th {
        background: #f8fafc;
        padding: 0.85rem 1.2rem;
        text-align: left;
        font-weight: 700;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .comp-table td {
        padding: 0.9rem 1.2rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .comp-table tr:hover td {
        background: #fafafa;
    }

    .brand-badge {
        display: inline-block;
        padding: 0.25rem 0.65rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        background: #e0e7ff;
        color: #3730a3;
    }

    .category-badge {
        display: inline-block;
        padding: 0.25rem 0.65rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-card {
        background: #ffffff;
        border-radius: 18px;
        width: 100%;
        max-width: 580px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
</style>
@endpush

@php
    $isDulux = str_contains(strtoupper($tenantPrincipal->name ?? ''), 'DULUX') || str_contains(strtoupper($tenantPrincipal->name ?? ''), 'ICI');
    $catalogLabel = 'Katalog ' . ($tenantPrincipal->short_name ?? $tenantPrincipal->name ?? 'Produk');
@endphp

@section('content')
<div style="width: 100%; max-width: 100%;">

    <!-- ALERT MESSAGES -->
    @if(session('success'))
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 0.9rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.88rem;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem; color: #16a34a;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.9rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; font-size: 0.88rem;">
            <div style="font-weight: 700; margin-bottom: 4px;"><i class="fa-solid fa-triangle-exclamation"></i> Gagal menyimpan data:</div>
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- HEADER SECTION -->
    <div class="comp-header">
        <div class="comp-header-left">
            <div class="comp-icon-large">
                <i class="fa-solid fa-store-slash"></i>
            </div>
            <div>
                <h2 class="comp-title-text">Master Produk & Subbrand Kompetitor</h2>
                <div class="comp-meta-row">
                    <span><i class="fa-solid fa-database"></i> Database Master Acuan Formulir & Pembanding Kompetitor</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-building-shield"></i> {{ $tenantPrincipal->name }}</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('portal.products', ['p' => $tenantPrincipal->id]) }}" class="btn-import-excel" style="background: #64748b; text-decoration: none; padding: 0.65rem 1.15rem;">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>{{ $catalogLabel }}</span>
            </a>
            <button type="button" class="btn-add-comp" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Produk Kompetitor</span>
            </button>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid-comp">
        <div class="stat-card-comp">
            <div class="stat-icon-wrap" style="background: #e0f2fe; color: #0284c7;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Subbrand Terdaftar</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: var(--text-heading);">{{ $totalCompetitors }}</div>
            </div>
        </div>

        <div class="stat-card-comp">
            <div class="stat-icon-wrap" style="background: #fef3c7; color: #d97706;">
                <i class="fa-solid fa-tags"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Merk Kompetitor Aktif</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: var(--text-heading);">{{ count($brands) }} Brand</div>
            </div>
        </div>

        <div class="stat-card-comp">
            <div class="stat-icon-wrap" style="background: #dcfce7; color: #16a34a;">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kategori Segmen</div>
                <div style="font-size: 1.45rem; font-weight: 800; color: var(--text-heading);">{{ count($categories) }} Kategori</div>
            </div>
        </div>
    </div>

    <!-- FILTER SECTION -->
    <div class="filter-card-comp">
        <form method="GET" action="{{ route('portal.competitor_products') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="p" value="{{ $tenantPrincipal->id }}">

            <div style="flex: 1; min-width: 220px; position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama subbrand atau merk..." style="width: 100%; padding: 0.6rem 1rem 0.6rem 2.25rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.85rem; outline: none;">
            </div>

            <div style="min-width: 180px;">
                <select name="brand" style="width: 100%; padding: 0.6rem 1rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.85rem; background: #ffffff; outline: none;" onchange="this.form.submit()">
                    <option value="">Semua Brand</option>
                    @foreach($brands as $b)
                        <option value="{{ $b }}" {{ $brand == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            <div style="min-width: 180px;">
                <select name="category" style="width: 100%; padding: 0.6rem 1rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.85rem; background: #ffffff; outline: none;" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $c)
                        <option value="{{ $c }}" {{ $category == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" style="padding: 0.6rem 1.25rem; background: var(--brand-primary); color: #ffffff; border: none; border-radius: 10px; font-size: 0.85rem; font-weight: 700; cursor: pointer;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>

            @if($search || $brand || $category)
                <a href="{{ route('portal.competitor_products', ['p' => $tenantPrincipal->id]) }}" style="padding: 0.6rem 1rem; background: #f1f5f9; color: var(--text-muted); border-radius: 10px; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- TABLE LIST -->
    <div class="comp-table-card">
        <div style="overflow-x: auto;">
            <table class="comp-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Merk / Brand</th>
                        <th>Nama Subbrand Kompetitor</th>
                        <th>Kategori / Segmen</th>
                        <th style="text-align: right;">{{ $isDulux ? 'Acuan Tin (1L)' : 'Acuan Kecil' }}</th>
                        <th style="text-align: right;">{{ $isDulux ? 'Acuan Galon (2.5L)' : 'Acuan Sedang' }}</th>
                        <th style="text-align: right;">{{ $isDulux ? 'Acuan Pail (20L)' : 'Acuan Besar' }}</th>
                        <th style="text-align: center; width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($competitorProducts as $index => $item)
                        <tr>
                            <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $competitorProducts->firstItem() + $index }}</td>
                            <td>
                                <span class="brand-badge">{{ $item->brand }}</span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-heading); font-size: 0.9rem;">
                                    {{ $item->subbrand }}
                                </div>
                            </td>
                            <td>
                                <span class="category-badge">{{ $item->category ?: 'Umum' }}</span>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #475569;">
                                {{ $item->benchmark_price_tin > 0 ? 'Rp ' . number_format($item->benchmark_price_tin, 0, ',', '.') : '-' }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--brand-primary);">
                                {{ $item->benchmark_price_galon > 0 ? 'Rp ' . number_format($item->benchmark_price_galon, 0, ',', '.') : '-' }}
                            </td>
                            <td style="text-align: right; font-weight: 600; color: #475569;">
                                {{ $item->benchmark_price_pail > 0 ? 'Rp ' . number_format($item->benchmark_price_pail, 0, ',', '.') : '-' }}
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <button type="button" onclick='openEditModal(@json($item))' style="width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--border-color); background: #ffffff; color: var(--brand-primary); cursor: pointer;" title="Edit Data">
                                        <i class="fa-solid fa-pen" style="font-size: 0.78rem;"></i>
                                    </button>
                                    <form method="POST" action="{{ route('portal.competitor_products.destroy', ['id' => $item->id, 'p' => $tenantPrincipal->id]) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk kompetitor ini?')" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="width: 30px; height: 30px; border-radius: 8px; border: 1px solid #fecaca; background: #fff1f2; color: #e11d48; cursor: pointer;" title="Hapus">
                                            <i class="fa-solid fa-trash" style="font-size: 0.78rem;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 3.5rem 1.5rem; color: var(--text-muted);">
                                <i class="fa-solid fa-box-open" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 0.75rem; display: block;"></i>
                                <div style="font-weight: 700; font-size: 1rem; color: var(--text-heading); margin-bottom: 4px;">Belum Ada Produk Kompetitor</div>
                                <div style="font-size: 0.85rem;">Belum ada master produk kompetitor yang terdaftar untuk principal <strong>{{ $tenantPrincipal->name }}</strong>.</div>
                                <button type="button" class="btn-add-comp" onclick="openAddModal()" style="margin-top: 1rem;">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Tambah Produk Kompetitor</span>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);">
            {{ $competitorProducts->appends(request()->query())->links('portal.pagination') }}
        </div>
    </div>

</div>

<!-- ADD MODAL -->
<div id="addCompModal" class="modal-overlay">
    <div class="modal-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-plus-circle" style="color: #e11d48;"></i>
                Tambah Produk Kompetitor Baru
            </div>
            <button type="button" onclick="closeAddModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form method="POST" action="{{ route('portal.competitor_products.store', ['p' => $tenantPrincipal->id]) }}" style="padding: 1.5rem;">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Nama Merk / Brand Kompetitor <span style="color: #e11d48;">*</span></label>
                    <input type="text" name="brand" list="brandListSuggestions" required placeholder="Ketik atau pilih merk kompetitor..." style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.88rem; outline: none; background: #ffffff;">
                    <datalist id="brandListSuggestions">
                        @foreach($brands as $b)
                            <option value="{{ $b }}"></option>
                        @endforeach
                        @if($isDulux)
                            <option value="JOTUN"></option>
                            <option value="NIPPON PAINT"></option>
                            <option value="AVIAN / NO DROP / LENKOTE"></option>
                            <option value="MOWILEX"></option>
                            <option value="PROPAN"></option>
                            <option value="KANSAI / DANAPAINT"></option>
                            <option value="PACIFIC PAINT"></option>
                        @else
                            <option value="UNILEVER"></option>
                            <option value="P&G"></option>
                            <option value="KAO"></option>
                            <option value="LION WINGS"></option>
                            <option value="INDOFOOD"></option>
                            <option value="MAYORA"></option>
                            <option value="RECKITT"></option>
                        @endif
                        <option value="MERK LAINNYA"></option>
                    </datalist>
                </div>

                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Nama Subbrand Kompetitor <span style="color: #e11d48;">*</span></label>
                    <input type="text" name="subbrand" required placeholder="Contoh: {{ $isDulux ? 'Majestic True Beauty / Vinilex / Sunguard' : 'Rinso / Daia / Attack / Lifebuoy' }}" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.88rem; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Kategori / Segmen Produk</label>
                    <input type="text" name="category" list="categoryListSuggestions" placeholder="Ketik atau pilih kategori..." style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.88rem; outline: none; background: #ffffff;">
                    <datalist id="categoryListSuggestions">
                        @foreach($categories as $c)
                            <option value="{{ $c }}"></option>
                        @endforeach
                        @if($isDulux)
                            <option value="Interior Premium"></option>
                            <option value="Interior Medium"></option>
                            <option value="Interior Economy"></option>
                            <option value="Eksterior Premium"></option>
                            <option value="Eksterior Medium"></option>
                            <option value="Waterproofing"></option>
                            <option value="Wood & Metal"></option>
                        @else
                            <option value="Fabric Care / Detergent"></option>
                            <option value="Personal Care / Sabun"></option>
                            <option value="Food & Beverage"></option>
                            <option value="Home Care"></option>
                            <option value="Oral Care"></option>
                            <option value="Skin Care"></option>
                        @endif
                        <option value="Lainnya"></option>
                    </datalist>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">{{ $isDulux ? 'Harga Tin (Rp)' : 'Harga Acuan 1 (Rp)' }}</label>
                        <input type="number" name="benchmark_price_tin" placeholder="0" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">{{ $isDulux ? 'Harga Galon (Rp)' : 'Harga Acuan 2 (Rp)' }}</label>
                        <input type="number" name="benchmark_price_galon" placeholder="0" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">{{ $isDulux ? 'Harga Pail (Rp)' : 'Harga Acuan 3 (Rp)' }}</label>
                        <input type="number" name="benchmark_price_pail" placeholder="0" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none;">
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="closeAddModal()" style="padding: 0.65rem 1.25rem; border: 1px solid var(--border-color); border-radius: 10px; font-weight: 600; background: #f8fafc; cursor: pointer;">Batal</button>
                <button type="submit" style="padding: 0.65rem 1.5rem; background: #e11d48; color: #ffffff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editCompModal" class="modal-overlay">
    <div class="modal-card">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-pen" style="color: var(--brand-primary);"></i>
                Edit Produk Kompetitor
            </div>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <form id="editForm" method="POST" action="" style="padding: 1.5rem;">
            @csrf
            @method('PUT')
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Nama Merk / Brand Kompetitor <span style="color: #e11d48;">*</span></label>
                    <input type="text" id="edit_brand" name="brand" list="brandListSuggestions" required placeholder="Ketik atau pilih merk kompetitor..." style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.88rem; outline: none; background: #ffffff;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Nama Subbrand Kompetitor <span style="color: #e11d48;">*</span></label>
                    <input type="text" id="edit_subbrand" name="subbrand" required style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.88rem; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 4px;">Kategori / Segmen Produk</label>
                    <input type="text" id="edit_category" name="category" list="categoryListSuggestions" placeholder="Ketik atau pilih kategori..." style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.88rem; outline: none; background: #ffffff;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">{{ $isDulux ? 'Harga Tin (Rp)' : 'Harga Acuan 1 (Rp)' }}</label>
                        <input type="number" id="edit_price_tin" name="benchmark_price_tin" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">{{ $isDulux ? 'Harga Galon (Rp)' : 'Harga Acuan 2 (Rp)' }}</label>
                        <input type="number" id="edit_price_galon" name="benchmark_price_galon" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">{{ $isDulux ? 'Harga Pail (Rp)' : 'Harga Acuan 3 (Rp)' }}</label>
                        <input type="number" id="edit_price_pail" name="benchmark_price_pail" style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.85rem; outline: none;">
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="closeEditModal()" style="padding: 0.65rem 1.25rem; border: 1px solid var(--border-color); border-radius: 10px; font-weight: 600; background: #f8fafc; cursor: pointer;">Batal</button>
                <button type="submit" style="padding: 0.65rem 1.5rem; background: var(--brand-primary); color: #ffffff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px var(--brand-glow);">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddModal() {
        document.getElementById('addCompModal').style.display = 'flex';
    }

    function closeAddModal() {
        document.getElementById('addCompModal').style.display = 'none';
    }

    function openEditModal(item) {
        const modal = document.getElementById('editCompModal');
        const form = document.getElementById('editForm');
        form.action = `/portal/competitor-products/${item.id}?p={{ $tenantPrincipal->id }}`;

        document.getElementById('edit_brand').value = item.brand;
        document.getElementById('edit_subbrand').value = item.subbrand;
        document.getElementById('edit_category').value = item.category || 'Interior Premium';
        document.getElementById('edit_price_tin').value = item.benchmark_price_tin ? parseInt(item.benchmark_price_tin) : '';
        document.getElementById('edit_price_galon').value = item.benchmark_price_galon ? parseInt(item.benchmark_price_galon) : '';
        document.getElementById('edit_price_pail').value = item.benchmark_price_pail ? parseInt(item.benchmark_price_pail) : '';

        modal.style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editCompModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const addM = document.getElementById('addCompModal');
        const editM = document.getElementById('editCompModal');
        if (event.target === addM) closeAddModal();
        if (event.target === editM) closeEditModal();
    }
</script>
@endpush
@endsection
