@extends('portal.layout')

@section('title', 'Katalog Produk & SKU - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', 'Katalog Master Produk & SKU')
@section('breadcrumb_active', 'Master SKU')

@push('styles')
<style>
    .catalog-header {
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

    .catalog-header-left {
        display: flex;
        align-items: center;
        gap: 1.1rem;
    }

    .catalog-icon-large {
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

    .catalog-title-text {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.25;
        margin-bottom: 0.25rem;
    }

    .catalog-meta-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.82rem;
        color: var(--text-muted);
    }

    .catalog-actions-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-add-product {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.35rem;
        background: var(--brand-primary);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 12px var(--brand-glow);
        transition: all 0.2s ease;
    }

    .btn-add-product:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    .btn-import-excel {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        background: #16a34a;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        transition: all 0.2s ease;
    }

    .btn-import-excel:hover {
        background: #15803d;
        transform: translateY(-2px);
    }

    /* Mini Stats */
    .mini-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .mini-stat-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.15rem 1.25rem;
        box-shadow: var(--shadow-sm);
    }

    .mini-stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }

    .mini-stat-value {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.1;
    }

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
        width: 250px;
    }

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

    .img-thumb-preview {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid var(--border-color);
        background: #f8fafc;
    }

    .sku-badge {
        font-family: monospace;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        border: 1px solid #bfdbfe;
        font-size: 0.78rem;
    }

    .category-badge {
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
    }

    .btn-action-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: #ffffff;
        color: var(--text-heading);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-action-icon:hover {
        background: var(--brand-light);
        color: var(--brand-primary);
        border-color: var(--brand-primary);
    }

    .btn-action-icon.delete:hover {
        background: #fee2e2;
        color: #ef4444;
        border-color: #ef4444;
    }

    /* Modal Overlay & Card */
    .portal-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 100;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .portal-modal-overlay.active {
        display: flex;
    }

    .portal-modal-card {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: 620px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalPop {
        from { transform: scale(0.92); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .portal-modal-header {
        padding: 1.35rem 1.6rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .portal-modal-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: var(--text-heading);
    }

    .btn-close-modal {
        background: #f1f5f9;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: all 0.2s ease;
    }

    .btn-close-modal:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .portal-modal-body {
        padding: 1.6rem;
    }

    .form-group-row {
        margin-bottom: 1.15rem;
    }

    .form-label-custom {
        display: block;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-heading);
        margin-bottom: 0.4rem;
    }

    .form-input-custom {
        width: 100%;
        padding: 0.7rem 0.95rem;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.88rem;
        outline: none;
        background: #f8fafc;
        transition: all 0.2s ease;
    }

    .form-input-custom:focus {
        border-color: var(--brand-primary);
        background: #ffffff;
        box-shadow: 0 0 0 3px var(--brand-light);
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
    }

    .form-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.85rem;
    }

    @media (max-width: 640px) {
        .form-grid-3 {
            grid-template-columns: 1fr;
        }
    }

    .stock-min-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.22rem 0.55rem;
        background: #fef3c7;
        color: #b45309;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 700;
        border: 1px solid #fde68a;
    }

    .excel-file-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: #dcfce7;
        color: #16a34a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    /* Professional Photo Dropzone */
    .pro-upload-dropzone {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 14px;
        padding: 1.5rem 1.25rem;
        text-align: center;
        cursor: pointer;
        position: relative;
        transition: all 0.25s ease;
    }

    .pro-upload-dropzone:hover, .pro-upload-dropzone.dragover {
        border-color: var(--brand-primary);
        background: var(--brand-light);
    }

    .pro-upload-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #ffffff;
        color: var(--brand-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 0.65rem;
        box-shadow: var(--shadow-sm);
    }

    .pro-upload-text-main {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-heading);
        margin-bottom: 0.25rem;
    }

    .pro-upload-text-sub {
        font-size: 0.76rem;
        color: var(--text-muted);
        line-height: 1.4;
    }

    .pro-preview-box {
        display: none;
        align-items: center;
        gap: 1rem;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-top: 0.75rem;
    }

    .pro-preview-box.show {
        display: flex;
    }

    .pro-preview-img {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid var(--border-color);
        background: #f8fafc;
    }

    .pro-preview-info {
        flex: 1;
        min-width: 0;
    }

    .pro-preview-filename {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-heading);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pro-preview-filesize {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .pro-preview-remove {
        background: #fee2e2;
        color: #ef4444;
        border: none;
        border-radius: 8px;
        padding: 0.35rem 0.65rem;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .pro-preview-remove:hover {
        background: #fecaca;
    }

    .alert-banner {
        padding: 0.95rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .alert-error {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    @media (max-width: 992px) {
        .mini-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

    @if(session('success'))
        <div class="alert-banner alert-success">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-banner alert-error">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Header Card -->
    <div class="catalog-header">
        <div class="catalog-header-left">
            <div class="catalog-icon-large">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <h2 class="catalog-title-text">Master Data Produk & SKU</h2>
                <div class="catalog-meta-row">
                    <span>Prinsiple: <strong>{{ $tenantPrincipal->name }}</strong></span>
                    <span>&bull;</span>
                    <span>Daftar item target pemantauan stok, offtake, dan display lapangan</span>
                </div>
            </div>
        </div>

        <div class="catalog-actions-right">
            <button type="button" class="btn-import-excel" onclick="openImportModal()">
                <i class="fa-solid fa-file-excel"></i>
                Import Excel / CSV
            </button>
            <button type="button" class="btn-add-product" onclick="openAddModal()">
                <i class="fa-solid fa-plus"></i>
                Tambah Produk Baru
            </button>
        </div>
    </div>

    <!-- Mini Stats -->
    <div class="mini-stats-grid">
        <div class="mini-stat-card">
            <div class="mini-stat-label">Total SKU Terdaftar</div>
            <div class="mini-stat-value" style="color: var(--brand-primary);">{{ number_format($totalProducts) }}</div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-label">Total Kategori Produk</div>
            <div class="mini-stat-value">{{ count($categories) }}</div>
        </div>
        <div class="mini-stat-card">
            <div class="mini-stat-label">Total Merek / Brand</div>
            <div class="mini-stat-value">{{ count($brands) }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form action="{{ route('portal.products') }}" method="GET" class="filter-bar">
        @if(request()->has('p'))
            <input type="hidden" name="p" value="{{ request()->query('p') }}">
        @endif

        <div class="filter-group-left">
            @if(!empty($categories))
                <select name="category" class="filter-select-btn" onchange="this.form.submit()">
                    <option value="">🏷️ Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if(!empty($brands))
                <select name="brand" class="filter-select-btn" onchange="this.form.submit()">
                    <option value="">🏢 Semua Brand</option>
                    @foreach($brands as $b)
                        <option value="{{ $b }}" {{ $brand == $b ? 'selected' : '' }}>
                            {{ $b }}
                        </option>
                    @endforeach
                </select>
            @endif

            <input type="text" name="q" class="filter-search-input" placeholder="Cari nama produk, SKU, barcode..." value="{{ $search }}">
        </div>

        <div>
            <button type="submit" class="filter-select-btn" style="background: var(--brand-primary); color: #fff; font-weight: 700;">
                <i class="fa-solid fa-magnifying-glass"></i> Cari SKU
            </button>
            @if($search || $category || $brand)
                <a href="{{ route('portal.products', request()->has('p') ? ['p' => request()->query('p')] : []) }}" class="filter-select-btn" style="text-decoration: none; color: #64748b; background: #e2e8f0;">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Products Table Card -->
    <div class="table-container-card">
        @if($products->isNotEmpty())
            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>Nama Produk / SKU</th>
                            <th>Kode SKU</th>
                            <th>Brand</th>
                            <th>Kategori</th>
                            <th>Harga Standar</th>
                            <th>Stock Min</th>
                            <th>Satuan</th>
                            <th>Barcode</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $idx => $prod)
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 700;">
                                    {{ $products->firstItem() + $idx }}
                                </td>
                                <td>
                                    @if($prod->image_path)
                                        <img src="{{ asset('storage/' . $prod->image_path) }}" alt="{{ $prod->name }}" class="img-thumb-preview">
                                    @else
                                        <div class="img-thumb-preview" style="display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                                            <i class="fa-solid fa-box"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading); font-size: 0.9rem;">
                                        {{ $prod->name }}
                                    </div>
                                    @if($prod->description)
                                        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 300px;">
                                            {{ Str::limit($prod->description, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="sku-badge">{{ $prod->sku_code }}</span>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--text-heading);">{{ $prod->brand ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="category-badge">{{ $prod->category ?? 'Umum' }}</span>
                                </td>
                                <td>
                                    <span style="font-weight: 800; color: #16a34a;">
                                        {{ $prod->formatted_price }}
                                    </span>
                                </td>
                                <td>
                                    <span class="stock-min-badge" title="Stock Minimal Standar Toko">
                                        <i class="fa-solid fa-boxes-stacked" style="font-size: 0.7rem; margin-right: 3px;"></i>
                                        {{ $prod->min_stock ?? 0 }} {{ $prod->uom ?? 'Pcs' }}
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted); font-weight: 600;">{{ $prod->uom ?? 'Pcs' }}</span>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">
                                        {{ $prod->barcode ?? '-' }}
                                    </span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <button type="button" class="btn-action-icon" title="Edit Produk" onclick='openEditModal(@json($prod))'>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('portal.products.destroy', $prod->id) }}" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Yakin ingin menghapus produk ini dari katalog?');">
                                        @csrf
                                        @method('DELETE')
                                        @if(request()->has('p'))
                                            <input type="hidden" name="p" value="{{ request()->query('p') }}">
                                        @endif
                                        <button type="submit" class="btn-action-icon delete" title="Hapus Produk">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.25rem;">
                {{ $products->appends(request()->query())->links('portal.pagination') }}
            </div>
        @else
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-boxes-packing" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-heading);">Belum Ada Data Produk Terdaftar</div>
                <p style="font-size: 0.85rem; max-width: 420px; margin: 0.35rem auto 1.25rem;">
                    Daftar SKU produk prinsiple dapat diinput manual satu per satu atau diimpor massal dari file Excel.
                </p>
                <div style="display: flex; gap: 0.75rem; justify-content: center;">
                    <button type="button" class="btn-import-excel" onclick="openImportModal()">
                        <i class="fa-solid fa-file-excel"></i> Import File Excel
                    </button>
                    <button type="button" class="btn-add-product" onclick="openAddModal()">
                        <i class="fa-solid fa-plus"></i> Tambah Produk Baru
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- MODAL 1: TAMBAH PRODUK BARU -->
    <div id="modalAddProduct" class="portal-modal-overlay">
        <div class="portal-modal-card">
            <div class="portal-modal-header">
                <h3 class="portal-modal-title"><i class="fa-solid fa-plus-circle" style="color: var(--brand-primary);"></i> Tambah Produk Baru</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('modalAddProduct')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('portal.products.store') }}" method="POST" enctype="multipart/form-data" class="portal-modal-body">
                @csrf
                @if(request()->has('p'))
                    <input type="hidden" name="p" value="{{ request()->query('p') }}">
                @endif

                <div class="form-group-row">
                    <label class="form-label-custom">Nama Produk / SKU <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" class="form-input-custom" placeholder="Contoh: SoKlin Liquid Detergent Antibac 720ml" required>
                </div>

                <div class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Kode SKU <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="sku_code" class="form-input-custom" placeholder="Contoh: WNG-SKL-LIQ-720" required>
                    </div>
                    <div>
                        <label class="form-label-custom">Barcode EAN / UPC</label>
                        <input type="text" name="barcode" class="form-input-custom" placeholder="Contoh: 8998866101102">
                    </div>
                </div>

                <div class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Merek / Sub-Brand</label>
                        <input type="text" name="brand" class="form-input-custom" placeholder="Contoh: SoKlin, Nuvo, Mie Sedaap">
                    </div>
                    <div>
                        <label class="form-label-custom">Kategori Produk</label>
                        <input type="text" name="category" class="form-input-custom" placeholder="Contoh: Care, Food, Dairy">
                    </div>
                </div>

                <div class="form-grid-3 form-group-row">
                    <div>
                        <label class="form-label-custom">Harga Standar (Rp)</label>
                        <input type="number" name="price" class="form-input-custom" placeholder="0" min="0">
                    </div>
                    <div>
                        <label class="form-label-custom">Stock Minimal Toko</label>
                        <input type="number" name="min_stock" class="form-input-custom" placeholder="0" min="0" value="0">
                    </div>
                    <div>
                        <label class="form-label-custom">Satuan Unit (UoM)</label>
                        <input type="text" name="uom" class="form-input-custom" placeholder="Pcs / Pouch / Pack / Btl" value="Pcs">
                    </div>
                </div>

                <!-- Professional Photo Upload Dropzone -->
                <div class="form-group-row">
                    <label class="form-label-custom">Foto Kemasan Produk</label>
                    <div class="pro-upload-dropzone" onclick="document.getElementById('add_product_image_input').click()">
                        <div class="pro-upload-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="pro-upload-text-main">Klik atau seret file foto ke sini</div>
                        <div class="pro-upload-text-sub">Format: PNG, JPG, JPEG, atau WebP (Maks. 4MB)</div>
                        <input type="file" id="add_product_image_input" name="image" accept="image/*" style="display: none;" onchange="handleImagePreview(this, 'add_preview_box', 'add_preview_img', 'add_preview_name', 'add_preview_size')">
                    </div>
                    
                    <div id="add_preview_box" class="pro-preview-box">
                        <img id="add_preview_img" src="" alt="Preview" class="pro-preview-img">
                        <div class="pro-preview-info">
                            <div id="add_preview_name" class="pro-preview-filename">-</div>
                            <div id="add_preview_size" class="pro-preview-filesize">-</div>
                        </div>
                        <button type="button" class="pro-preview-remove" onclick="removeImagePreview('add_product_image_input', 'add_preview_box')">
                            <i class="fa-solid fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>

                <div class="form-group-row">
                    <label class="form-label-custom">Deskripsi Singkat</label>
                    <textarea name="description" class="form-input-custom" rows="2" placeholder="Catatan spesifikasi produk..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="filter-select-btn" onclick="closeModal('modalAddProduct')">Batal</button>
                    <button type="submit" class="btn-add-product"><i class="fa-solid fa-check"></i> Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT PRODUK -->
    <div id="modalEditProduct" class="portal-modal-overlay">
        <div class="portal-modal-card">
            <div class="portal-modal-header">
                <h3 class="portal-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--brand-primary);"></i> Edit Data Produk</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('modalEditProduct')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="formEditProduct" action="" method="POST" enctype="multipart/form-data" class="portal-modal-body">
                @csrf
                @method('PUT')
                @if(request()->has('p'))
                    <input type="hidden" name="p" value="{{ request()->query('p') }}">
                @endif

                <div class="form-group-row">
                    <label class="form-label-custom">Nama Produk / SKU <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="edit_name" name="name" class="form-input-custom" required>
                </div>

                <div class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Kode SKU <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="edit_sku_code" name="sku_code" class="form-input-custom" required>
                    </div>
                    <div>
                        <label class="form-label-custom">Barcode EAN / UPC</label>
                        <input type="text" id="edit_barcode" name="barcode" class="form-input-custom">
                    </div>
                </div>

                <div class="form-grid-2 form-group-row">
                    <div>
                        <label class="form-label-custom">Merek / Sub-Brand</label>
                        <input type="text" id="edit_brand" name="brand" class="form-input-custom">
                    </div>
                    <div>
                        <label class="form-label-custom">Kategori Produk</label>
                        <input type="text" id="edit_category" name="category" class="form-input-custom">
                    </div>
                </div>

                <div class="form-grid-3 form-group-row">
                    <div>
                        <label class="form-label-custom">Harga Standar (Rp)</label>
                        <input type="number" id="edit_price" name="price" class="form-input-custom" min="0">
                    </div>
                    <div>
                        <label class="form-label-custom">Stock Minimal Toko</label>
                        <input type="number" id="edit_min_stock" name="min_stock" class="form-input-custom" min="0">
                    </div>
                    <div>
                        <label class="form-label-custom">Satuan Unit (UoM)</label>
                        <input type="text" id="edit_uom" name="uom" class="form-input-custom">
                    </div>
                </div>

                <!-- Professional Photo Upload Dropzone for Edit -->
                <div class="form-group-row">
                    <label class="form-label-custom">Foto Kemasan Produk</label>
                    <div class="pro-upload-dropzone" onclick="document.getElementById('edit_product_image_input').click()">
                        <div class="pro-upload-icon">
                            <i class="fa-solid fa-image"></i>
                        </div>
                        <div class="pro-upload-text-main">Klik untuk mengganti foto kemasan</div>
                        <div class="pro-upload-text-sub">Format: PNG, JPG, JPEG, atau WebP (Maks. 4MB)</div>
                        <input type="file" id="edit_product_image_input" name="image" accept="image/*" style="display: none;" onchange="handleImagePreview(this, 'edit_preview_box', 'edit_preview_img', 'edit_preview_name', 'edit_preview_size')">
                    </div>
                    
                    <div id="edit_preview_box" class="pro-preview-box">
                        <img id="edit_preview_img" src="" alt="Preview" class="pro-preview-img">
                        <div class="pro-preview-info">
                            <div id="edit_preview_name" class="pro-preview-filename">-</div>
                            <div id="edit_preview_size" class="pro-preview-filesize">-</div>
                        </div>
                        <button type="button" class="pro-preview-remove" onclick="removeImagePreview('edit_product_image_input', 'edit_preview_box')">
                            <i class="fa-solid fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>

                <div class="form-group-row">
                    <label class="form-label-custom">Deskripsi Singkat</label>
                    <textarea id="edit_description" name="description" class="form-input-custom" rows="2"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="filter-select-btn" onclick="closeModal('modalEditProduct')">Batal</button>
                    <button type="submit" class="btn-add-product"><i class="fa-solid fa-save"></i> Perbarui Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: IMPORT EXCEL / CSV -->
    <div id="modalImportProduct" class="portal-modal-overlay">
        <div class="portal-modal-card">
            <div class="portal-modal-header">
                <h3 class="portal-modal-title"><i class="fa-solid fa-file-excel" style="color: #16a34a;"></i> Import Data Produk via Excel / CSV</h3>
                <button type="button" class="btn-close-modal" onclick="closeModal('modalImportProduct')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('portal.products.import') }}" method="POST" enctype="multipart/form-data" class="portal-modal-body">
                @csrf
                @if(request()->has('p'))
                    <input type="hidden" name="p" value="{{ request()->query('p') }}">
                @endif

                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                    <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-heading); margin-bottom: 0.35rem;">
                        <i class="fa-solid fa-circle-info" style="color: #2563eb;"></i> Format Kolom File Excel / CSV:
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                        Pastikan baris pertama (Header) berisi kolom berikut:
                        <br>
                        <code style="font-weight: 700; color: #0f172a; background: #e2e8f0; padding: 0.2rem 0.4rem; border-radius: 4px; display: inline-block; margin-top: 0.35rem;">
                            nama_produk, kode_sku, barcode, brand, kategori, harga, satuan, stok_minimal, deskripsi
                        </code>
                    </div>
                    <div style="margin-top: 0.85rem;">
                        <a href="{{ route('portal.products.template') }}" class="filter-select-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; background: #ffffff;">
                            <i class="fa-solid fa-download"></i> Unduh Format Template CSV / Excel
                        </a>
                    </div>
                </div>

                <!-- Professional Excel/CSV Upload Dropzone -->
                <div class="form-group-row">
                    <label class="form-label-custom">Upload File Excel / CSV <span style="color: #ef4444;">*</span></label>
                    <div class="pro-upload-dropzone" id="excel_dropzone" onclick="document.getElementById('import_excel_file_input').click()">
                        <div class="pro-upload-icon" style="color: #16a34a; background: #f0fdf4;">
                            <i class="fa-solid fa-file-excel"></i>
                        </div>
                        <div class="pro-upload-text-main">Klik untuk memilih atau seret file Excel/CSV ke sini</div>
                        <div class="pro-upload-text-sub">Format yang didukung: <strong>.xlsx, .xls, .csv</strong> (Maks. 10MB)</div>
                        <input type="file" id="import_excel_file_input" name="file" accept=".xlsx,.xls,.csv,.txt" style="display: none;" required onchange="handleExcelFilePreview(this)">
                    </div>
                    
                    <div id="excel_preview_box" class="pro-preview-box">
                        <div class="excel-file-icon-badge">
                            <i class="fa-solid fa-file-excel"></i>
                        </div>
                        <div class="pro-preview-info">
                            <div id="excel_preview_name" class="pro-preview-filename">-</div>
                            <div id="excel_preview_size" class="pro-preview-filesize" style="font-size: 0.75rem; color: #16a34a; font-weight: 600;">-</div>
                        </div>
                        <button type="button" class="pro-preview-remove" onclick="removeExcelFilePreview()">
                            <i class="fa-solid fa-xmark"></i> Hapus / Ganti
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="filter-select-btn" onclick="closeModal('modalImportProduct')">Batal</button>
                    <button type="submit" class="btn-import-excel"><i class="fa-solid fa-cloud-arrow-up"></i> Upload & Import Data</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openAddModal() {
        removeImagePreview('add_product_image_input', 'add_preview_box');
        document.getElementById('modalAddProduct').classList.add('active');
    }

    function openImportModal() {
        removeExcelFilePreview();
        document.getElementById('modalImportProduct').classList.add('active');
    }

    function openEditModal(prod) {
        document.getElementById('formEditProduct').action = '/portal/products/' + prod.id;
        document.getElementById('edit_name').value = prod.name || '';
        document.getElementById('edit_sku_code').value = prod.sku_code || '';
        document.getElementById('edit_barcode').value = prod.barcode || '';
        document.getElementById('edit_brand').value = prod.brand || '';
        document.getElementById('edit_category').value = prod.category || '';
        document.getElementById('edit_price').value = prod.price || 0;
        document.getElementById('edit_min_stock').value = prod.min_stock || 0;
        document.getElementById('edit_uom').value = prod.uom || 'Pcs';
        document.getElementById('edit_description').value = prod.description || '';

        // Handle existing image in edit modal
        var previewBox = document.getElementById('edit_preview_box');
        var previewImg = document.getElementById('edit_preview_img');
        var previewName = document.getElementById('edit_preview_name');
        var previewSize = document.getElementById('edit_preview_size');
        
        if (prod.image_path) {
            previewImg.src = '/storage/' + prod.image_path;
            previewName.textContent = 'Foto Produk Saat Ini';
            previewSize.textContent = 'Tersimpan di server';
            previewBox.classList.add('show');
        } else {
            removeImagePreview('edit_product_image_input', 'edit_preview_box');
        }

        document.getElementById('modalEditProduct').classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Professional Image Preview Handler
    function handleImagePreview(input, boxId, imgId, nameId, sizeId) {
        var box = document.getElementById(boxId);
        var img = document.getElementById(imgId);
        var nameElem = document.getElementById(nameId);
        var sizeElem = document.getElementById(sizeId);

        if (input.files && input.files[0]) {
            var file = input.files[0];
            
            // Check size (max 4MB)
            if (file.size > 4 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 4MB.');
                input.value = '';
                box.classList.remove('show');
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                nameElem.textContent = file.name;
                var sizeKB = (file.size / 1024).toFixed(1);
                var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                sizeElem.textContent = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';
                box.classList.add('show');
            };
            reader.readAsDataURL(file);
        } else {
            box.classList.remove('show');
        }
    }

    function removeImagePreview(inputId, boxId) {
        var input = document.getElementById(inputId);
        if (input) input.value = '';
        var box = document.getElementById(boxId);
        if (box) box.classList.remove('show');
    }

    // Professional Excel File Preview Handler
    function handleExcelFilePreview(input) {
        var box = document.getElementById('excel_preview_box');
        var dropzone = document.getElementById('excel_dropzone');
        var nameElem = document.getElementById('excel_preview_name');
        var sizeElem = document.getElementById('excel_preview_size');

        if (input.files && input.files[0]) {
            var file = input.files[0];

            if (file.size > 10 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 10MB.');
                input.value = '';
                box.classList.remove('show');
                return;
            }

            nameElem.textContent = file.name;
            var sizeKB = (file.size / 1024).toFixed(1);
            var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            sizeElem.textContent = (file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB') + ' • Siap diimpor';
            box.classList.add('show');
            if (dropzone) dropzone.style.borderColor = '#16a34a';
        } else {
            box.classList.remove('show');
            if (dropzone) dropzone.style.borderColor = '#cbd5e1';
        }
    }

    function removeExcelFilePreview() {
        var input = document.getElementById('import_excel_file_input');
        if (input) input.value = '';
        var box = document.getElementById('excel_preview_box');
        if (box) box.classList.remove('show');
        var dropzone = document.getElementById('excel_dropzone');
        if (dropzone) dropzone.style.borderColor = '#cbd5e1';
    }

    // Drag and Drop listeners for dropzones
    document.querySelectorAll('.pro-upload-dropzone').forEach(function (zone) {
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        zone.addEventListener('dragleave', function (e) {
            zone.classList.remove('dragover');
        });
        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            var input = zone.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                if (input.id === 'import_excel_file_input') {
                    handleExcelFilePreview(input);
                } else {
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
    });

    // Close on click outside card
    document.querySelectorAll('.portal-modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
</script>
@endpush
