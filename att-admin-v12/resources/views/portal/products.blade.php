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

    @media (max-width: 992px) {
        .mini-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')

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
                <a href="{{ route('portal.products') }}" class="filter-select-btn" style="text-decoration: none; color: #64748b; background: #e2e8f0;">
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
                            <th>Satuan</th>
                            <th>Barcode</th>
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
                                        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 320px;">
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
                                    <span style="color: var(--text-muted); font-weight: 600;">{{ $prod->uom ?? 'Pcs' }}</span>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">
                                        {{ $prod->barcode ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1.25rem;">
                {{ $products->links() }}
            </div>
        @else
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-boxes-packing" style="font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1;"></i>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-heading);">Belum Ada Data Produk Terdaftar</div>
                <p style="font-size: 0.85rem; max-width: 420px; margin: 0.35rem auto 0;">
                    Daftar SKU produk prinsiple dapat diinput melalui Admin Panel pada menu <strong>Master Data > Products / SKU</strong>.
                </p>
            </div>
        @endif
    </div>

@endsection
