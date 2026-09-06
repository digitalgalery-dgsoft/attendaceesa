@extends('portal.layout')

@section('title', ($isEdit ? 'Edit Form: ' . $template->title : 'Buat Form Template Baru') . ' - ' . ($tenantPrincipal->portal_title ?? $tenantPrincipal->name))
@section('page_title', $isEdit ? 'Edit Form Template: ' . $template->title : 'Buat Form Template Laporan Baru')
@section('breadcrumb_active', $isEdit ? 'Edit Form Builder' : 'Tambah Form Builder')

@push('styles')
<style>
    /* Sticky Top Action Header */
    .fb-form-header {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.75rem;
        margin-bottom: 1.75rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        position: sticky;
        top: 80px;
        z-index: 40;
    }

    .fb-form-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .fb-form-header-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }

    .fb-form-header-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .fb-form-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-secondary-custom {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.2rem;
        background: #f8fafc;
        color: var(--text-heading);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-secondary-custom:hover {
        background: #f1f5f9;
        color: var(--text-heading);
    }

    .btn-primary-custom {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.45rem;
        background: var(--brand-gradient);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 14px var(--brand-glow);
        transition: all 0.25s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px var(--brand-glow);
        color: #ffffff;
    }

    /* Section Cards */
    .fb-section-card {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        box-shadow: var(--shadow-sm);
    }

    .fb-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .fb-section-title-wrap {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .fb-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--brand-light);
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .fb-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-heading);
        line-height: 1.2;
    }

    .fb-section-desc {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.15rem;
    }

    /* Form Fields Styling */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--text-heading);
        margin-bottom: 0.45rem;
    }

    .form-label .required-star {
        color: #ef4444;
        margin-left: 0.2rem;
    }

    .form-control-custom {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 0.88rem;
        background: #f8fafc;
        color: var(--text-heading);
        transition: all 0.2s ease;
    }

    .form-control-custom:focus {
        background: #ffffff;
        border-color: var(--brand-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(15, 82, 186, 0.12);
    }

    .form-hint {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.35rem;
        line-height: 1.35;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }

    .grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1.25rem;
    }

    .grid-4 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 1.25rem;
    }

    @media (max-width: 900px) {
        .grid-2, .grid-3, .grid-4 {
            grid-template-columns: 1fr;
        }
    }

    /* Toggle Item Component */
    .fb-toggle-item {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 0.85rem 1.15rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .fb-toggle-info {
        flex: 1;
    }

    .fb-toggle-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-heading);
    }

    .fb-toggle-sub {
        font-size: 0.74rem;
        color: var(--text-muted);
    }

    /* Frequency Day Pills */
    .day-pills-wrap {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.4rem;
    }

    .day-pill-checkbox {
        display: none;
    }

    .day-pill-label {
        padding: 0.45rem 0.9rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .day-pill-checkbox:checked + .day-pill-label {
        background: var(--brand-primary);
        border-color: var(--brand-primary);
        color: #ffffff;
        box-shadow: 0 2px 8px var(--brand-glow);
    }

    /* Dynamic Questions / Fields Builder */
    .questions-container {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .question-card {
        background: #ffffff;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        transition: all 0.25s ease;
        overflow: hidden;
    }

    .question-card.active, .question-card:focus-within {
        border-color: var(--brand-primary);
        box-shadow: 0 6px 20px rgba(15, 82, 186, 0.12);
    }

    .question-card-header {
        background: #f8fafc;
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .question-card-num {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-weight: 800;
        font-size: 0.9rem;
        color: var(--text-heading);
    }

    .question-num-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--brand-primary);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.76rem;
        font-weight: 800;
    }

    .question-actions-right {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-q-action {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: #ffffff;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-q-action:hover {
        background: #f1f5f9;
        color: var(--text-heading);
    }

    .btn-q-action.danger:hover {
        background: #fef2f2;
        color: #ef4444;
        border-color: #fca5a5;
    }

    .question-card-body {
        padding: 1.25rem 1.5rem;
    }

    /* Options Manager */
    .options-manager-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-top: 1rem;
    }

    .options-tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .opt-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.35rem 0.65rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-heading);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .opt-chip-delete {
        color: #94a3b8;
        cursor: pointer;
        font-size: 0.85rem;
        transition: color 0.15s ease;
    }

    .opt-chip-delete:hover {
        color: #ef4444;
    }

    .opt-input-row {
        display: flex;
        gap: 0.5rem;
    }

    .btn-add-question {
        width: 100%;
        padding: 1rem;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        color: var(--brand-primary);
        font-weight: 800;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        cursor: pointer;
        transition: all 0.25s ease;
        margin-top: 1.25rem;
    }

    .btn-add-question:hover {
        background: #eff6ff;
        border-color: var(--brand-primary);
        transform: translateY(-2px);
    }

    /* Product & Position Selectors */
    .selector-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 0.75rem;
        max-height: 240px;
        overflow-y: auto;
        padding: 0.75rem;
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 12px;
    }

    .selector-checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.5rem 0.75rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--text-heading);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .selector-checkbox-label:hover {
        border-color: var(--brand-primary);
        background: #f0f7ff;
    }

    .selector-checkbox-label input:checked + span {
        color: var(--brand-primary);
        font-weight: 700;
    }

    /* Repeater Table for Assignments */
    .assignments-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .assignments-table th {
        background: #f8fafc;
        padding: 0.75rem 1rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        border-bottom: 1px solid var(--border-color);
    }

    .assignments-table td {
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<form action="{{ $isEdit ? route('portal.report_templates.update', ['id' => $template->id, 'p' => $tenantPrincipal->id]) : route('portal.report_templates.store', ['p' => $tenantPrincipal->id]) }}" method="POST" id="formBuilderApp">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <!-- Top Sticky Action Header -->
    <div class="fb-form-header">
        <div class="fb-form-header-left">
            <a href="{{ route('portal.report_templates', ['p' => $tenantPrincipal->id]) }}" class="btn-action-icon" title="Kembali ke Daftar">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="fb-form-header-title">{{ $isEdit ? 'Edit Form: ' . $template->title : 'Buat Form Template Baru' }}</div>
                <div class="fb-form-header-sub">
                    Prinsiple: <strong>{{ $tenantPrincipal->name }}</strong> &bull; {{ $isEdit ? 'Versi ' . ($template->version ?? 1) : 'Draft Baru' }}
                </div>
            </div>
        </div>
        <div class="fb-form-actions">
            <a href="{{ route('portal.report_templates', ['p' => $tenantPrincipal->id]) }}" class="btn-secondary-custom">
                <i class="fa-solid fa-xmark"></i>
                <span>Batal</span>
            </a>
            @if($isEdit)
                <button type="submit" name="save_and_continue" value="1" class="btn-secondary-custom" style="color: var(--brand-primary); border-color: var(--brand-primary);">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan & Lanjutkan Edit</span>
                </button>
            @endif
            <button type="submit" class="btn-primary-custom">
                <i class="fa-solid fa-check"></i>
                <span>{{ $isEdit ? 'Simpan Perubahan' : 'Buat Form Template' }}</span>
            </button>
        </div>
    </div>

    <!-- 1. INFORMASI DASAR TEMPLATE -->
    <div class="fb-section-card">
        <div class="fb-section-header">
            <div class="fb-section-title-wrap">
                <div class="fb-section-icon">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <div class="fb-section-title">Informasi Dasar Template Form</div>
                    <div class="fb-section-desc">Tentukan judul form, kode variabel unik, kategori, dan aturan umum pelaporan.</div>
                </div>
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Judul Form Pelaporan <span class="required-star">*</span></label>
                <input type="text" name="title" id="formTitleInput" value="{{ old('title', $template->title) }}" class="form-control-custom" placeholder="Contoh: Laporan Offtake & Stock Harian" required>
                <div class="form-hint">Nama formulir yang akan tampil di header aplikasi mobile tim lapangan.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Kode Form Template <span class="required-star">*</span></label>
                <input type="text" name="code" id="formCodeInput" value="{{ old('code', $template->code) }}" class="form-control-custom" placeholder="RPT-OFFTAKE-DULUX" style="font-family: monospace; font-weight: 700;" required>
                <div class="form-hint">Kode unik identifikasi database (uppercase).</div>
            </div>

            <div class="form-group">
                <label class="form-label">Kategori Pelaporan <span class="required-star">*</span></label>
                <select name="category" class="form-control-custom" required>
                    @foreach($categories as $catKey => $catLabel)
                        <option value="{{ $catKey }}" {{ old('category', $template->category) === $catKey ? 'selected' : '' }}>
                            {{ $catLabel }}
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">Kategori laporan untuk filtering dan analitik dashboard.</div>
            </div>
        </div>

        <div class="grid-3" style="margin-top: 0.5rem;">
            <div class="fb-toggle-item">
                <div class="fb-toggle-info">
                    <div class="fb-toggle-title">Status Form Aktif</div>
                    <div class="fb-toggle-sub">Form akan langsung tampil di menu pelaporan mobile jika aktif.</div>
                </div>
                <label class="switch-toggle">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                    <span class="slider-toggle"></span>
                </label>
            </div>

            <div class="fb-toggle-item">
                <div class="fb-toggle-info">
                    <div class="fb-toggle-title">Wajib Titik GPS</div>
                    <div class="fb-toggle-sub">Wajib menyertakan koordinat lokasi saat submit laporan.</div>
                </div>
                <label class="switch-toggle">
                    <input type="checkbox" name="require_gps" value="1" {{ old('require_gps', $template->require_gps ?? true) ? 'checked' : '' }}>
                    <span class="slider-toggle"></span>
                </label>
            </div>

            <div class="fb-toggle-item">
                <div class="fb-toggle-info">
                    <div class="fb-toggle-title">Wajib Tanda Tangan</div>
                    <div class="fb-toggle-sub">Wajib tanda tangan digital PIC/Store Manager sebelum submit.</div>
                </div>
                <label class="switch-toggle">
                    <input type="checkbox" name="require_signature" value="1" {{ old('require_signature', $template->require_signature ?? false) ? 'checked' : '' }}>
                    <span class="slider-toggle"></span>
                </label>
            </div>
        </div>

        <div class="form-group" style="margin-top: 1.25rem; margin-bottom: 0;">
            <label class="form-label">Deskripsi & Petunjuk Pengisian untuk Karyawan (Opsional)</label>
            <textarea name="description" rows="2" class="form-control-custom" placeholder="Jelaskan instruksi singkat pengisian form ini bagi SPG/MD di lapangan...">{{ old('description', $template->description) }}</textarea>
        </div>
    </div>

    <!-- 2. JADWAL FREKUENSI & TARGET PENGISIAN -->
    <div class="fb-section-card">
        <div class="fb-section-header">
            <div class="fb-section-title-wrap">
                <div class="fb-section-icon" style="background: #fff7ed; color: #ea580c;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <div class="fb-section-title">🗓️ Pengaturan Frekuensi Jadwal & Target Pengisian</div>
                    <div class="fb-section-desc">Atur apakah form ini wajib diisi secara Harian (Daily), Mingguan (Weekly), atau Bulanan (Monthly) beserta target kuota dan hari aktifnya.</div>
                </div>
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Tipe Frekuensi Jadwal <span class="required-star">*</span></label>
                <select name="schedule_type" id="scheduleTypeSelect" class="form-control-custom" required onchange="handleScheduleChange()">
                    <option value="daily" {{ old('schedule_type', $template->schedule_type ?? 'daily') === 'daily' ? 'selected' : '' }}>📅 Daily (Harian)</option>
                    <option value="weekly" {{ old('schedule_type', $template->schedule_type) === 'weekly' ? 'selected' : '' }}>🗓️ Weekly (Mingguan)</option>
                    <option value="monthly" {{ old('schedule_type', $template->schedule_type) === 'monthly' ? 'selected' : '' }}>📆 Monthly (Bulanan)</option>
                </select>
                <div class="form-hint" id="scheduleTypeHint">Laporan akan muncul dan dikerjakan setiap hari kerja aktif.</div>
            </div>

            <div class="form-group" id="targetCountGroup">
                <label class="form-label" id="targetCountLabel">🎯 Target Pengisian (Per Periode)</label>
                <input type="number" name="target_count" value="{{ old('target_count', $template->target_count ?? 1) }}" min="1" class="form-control-custom">
                <div class="form-hint">Jumlah kuota target pengisian form yang harus dicapai karyawan.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Pilihan Hari Aktif Pelaporan</label>
                @php
                    $selectedDays = old('report_days', $template->report_days ?? []);
                    if (!is_array($selectedDays)) $selectedDays = [];
                    $dayList = [
                        'senin' => 'Sen',
                        'selasa' => 'Sel',
                        'rabu' => 'Rab',
                        'kamis' => 'Kam',
                        'jumat' => 'Jum',
                        'sabtu' => 'Sab',
                        'minggu' => 'Min',
                    ];
                @endphp
                <div class="day-pills-wrap">
                    @foreach($dayList as $dKey => $dShort)
                        <label>
                            <input type="checkbox" name="report_days[]" value="{{ $dKey }}" class="day-pill-checkbox" {{ in_array($dKey, $selectedDays) ? 'checked' : '' }}>
                            <span class="day-pill-label">{{ $dShort }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="form-hint">Jika semua tidak dicentang, form bebas diisi pada hari apa saja.</div>
            </div>
        </div>
    </div>

    <!-- 3. PARAMETER PRODUK TERTENTU (SKU) -->
    <div class="fb-section-card">
        <div class="fb-section-header">
            <div class="fb-section-title-wrap">
                <div class="fb-section-icon" style="background: #faf5ff; color: #9333ea;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                    <div class="fb-section-title">Filter Parameter Produk Tertentu (SKU {{ $tenantPrincipal->name }})</div>
                    <div class="fb-section-desc">Produk yang dipilih di sini akan otomatis menjadi pilihan produk saat tim lapangan mengisi form bertipe <em>Pilihan Produk</em>. (Opsional: Kosongkan jika berlaku untuk semua produk).</div>
                </div>
            </div>
            <div>
                <button type="button" class="btn-secondary-custom" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="toggleSelectAllProducts()">
                    <i class="fa-solid fa-check-double"></i>
                    <span id="btnSelectAllProdText">Pilih Semua</span>
                </button>
            </div>
        </div>

        @php
            $selectedProductIds = old('products', $template->products->pluck('id')->toArray());
        @endphp
        @if($products->count() > 0)
            <div class="selector-grid" id="productsSelectorGrid">
                @foreach($products as $prod)
                    <label class="selector-checkbox-label">
                        <input type="checkbox" name="products[]" value="{{ $prod->id }}" class="prod-checkbox" {{ in_array($prod->id, $selectedProductIds) ? 'checked' : '' }}>
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $prod->name }} {{ $prod->brand ? '[' . $prod->brand . ']' : '' }}
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="form-hint" style="margin-top: 0.5rem;">Tersedia {{ $products->count() }} produk master SKU aktif milik prinsiple ini.</div>
        @else
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                <i class="fa-solid fa-box-open" style="font-size: 1.5rem; margin-bottom: 0.4rem; color: #94a3b8;"></i>
                <div>Belum ada master SKU produk untuk prinsiple ini. Anda dapat menambahkannya terlebih dahulu di menu <strong>Katalog Produk (SKU)</strong>.</div>
            </div>
        @endif
    </div>

    <!-- 4. VISUAL DYNAMIC FORM BUILDER (GOOGLE FORMS STYLE) -->
    <div class="fb-section-card">
        <div class="fb-section-header">
            <div class="fb-section-title-wrap">
                <div class="fb-section-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <div class="fb-section-title">🎨 Visual Dynamic Form Builder (Google Form Style)</div>
                    <div class="fb-section-desc">Rancang daftar pertanyaan dan elemen input dinamis yang akan langsung muncul secara interaktif di aplikasi mobile.</div>
                </div>
            </div>
            <div>
                <span class="tpl-code-badge" id="totalQuestionsBadge">0 Pertanyaan</span>
            </div>
        </div>

        <div class="questions-container" id="questionsContainer">
            <!-- Dynamic Question Cards will be rendered here by JavaScript -->
        </div>

        <button type="button" class="btn-add-question" onclick="addQuestion()">
            <i class="fa-solid fa-plus-circle" style="font-size: 1.2rem;"></i>
            <span>Tambah Pertanyaan / Field Baru</span>
        </button>
    </div>

    <!-- 5. TARGET PENUGASAN FORM (ASSIGNMENTS) -->
    <div class="fb-section-card">
        <div class="fb-section-header">
            <div class="fb-section-title-wrap">
                <div class="fb-section-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <div class="fb-section-title">Penugasan Form Template (Form Assignment)</div>
                    <div class="fb-section-desc">Tentukan jabatan atau nama karyawan spesifik yang wajib mengisi form ini saat kunjungan lapangan. Kosongkan jika berlaku umum.</div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Target Positions -->
            <div class="form-group">
                <label class="form-label">🎯 Target Jabatan (Multi-Select)</label>
                @php
                    $selectedPosIds = old('positions', $template->positions->pluck('id')->toArray());
                @endphp
                <div class="selector-grid" style="max-height: 180px;">
                    @foreach($positions as $pos)
                        <label class="selector-checkbox-label">
                            <input type="checkbox" name="positions[]" value="{{ $pos->id }}" {{ in_array($pos->id, $selectedPosIds) ? 'checked' : '' }}>
                            <span>{{ $pos->name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="form-hint">Pilih jabatan yang wajib mengisi (misal: SPG, MD, TL). Kosongkan jika untuk semua.</div>
            </div>

            <!-- Target Employees -->
            <div class="form-group">
                <label class="form-label">👤 Target Nama Karyawan Spesifik (Opsional)</label>
                @php
                    $selectedEmpIds = old('employees', $template->employees->pluck('id')->toArray());
                @endphp
                <div class="selector-grid" style="max-height: 180px;">
                    @foreach($employees as $emp)
                        <label class="selector-checkbox-label">
                            <input type="checkbox" name="employees[]" value="{{ $emp->id }}" {{ in_array($emp->id, $selectedEmpIds) ? 'checked' : '' }}>
                            <span>{{ $emp->full_name }} ({{ $emp->nik }})</span>
                        </label>
                    @endforeach
                </div>
                <div class="form-hint">Khusus jika form ditugaskan hanya ke personil tertentu.</div>
            </div>
        </div>
    </div>

    <!-- Bottom Action Bar -->
    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; margin-bottom: 3rem;">
        <a href="{{ route('portal.report_templates', ['p' => $tenantPrincipal->id]) }}" class="btn-secondary-custom">
            <i class="fa-solid fa-xmark"></i>
            <span>Batal</span>
        </a>
        <button type="submit" class="btn-primary-custom" style="padding: 0.8rem 2rem; font-size: 0.95rem;">
            <i class="fa-solid fa-check"></i>
            <span>{{ $isEdit ? 'Simpan Perubahan Form' : 'Buat Form Template Baru' }}</span>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // Available Field Types Definition
    const fieldTypesMap = @json($fieldTypes);
    
    // Initial Fields Data
    let existingFields = @json(old('fields', $template->fields->toArray()));

    // Fallback if creating fresh template and no fields
    if (!existingFields || existingFields.length === 0) {
        existingFields = [
            {
                field_label: 'Pilihan Produk',
                field_name: 'product_id',
                field_type: 'product_select',
                placeholder: 'Pilih SKU Produk',
                help_text: 'Pilih produk yang dilaporkan',
                is_required: true,
                is_readonly: false,
                options: []
            },
            {
                field_label: 'Jumlah Qty Terjual',
                field_name: 'qty_sold',
                field_type: 'number',
                placeholder: 'Contoh: 12',
                help_text: 'Masukkan total quantity',
                is_required: true,
                is_readonly: false,
                options: []
            },
            {
                field_label: 'Foto Bukti Display / Toko',
                field_name: 'foto_display',
                field_type: 'camera_photo',
                placeholder: '',
                help_text: 'Ambil foto langsung di lokasi toko',
                is_required: true,
                is_readonly: false,
                options: []
            }
        ];
    }

    let questions = [...existingFields];

    document.addEventListener('DOMContentLoaded', () => {
        renderAllQuestions();
        handleScheduleChange();

        // Auto Slug for Title -> Code on creation
        const titleInput = document.getElementById('formTitleInput');
        const codeInput = document.getElementById('formCodeInput');
        if (titleInput && codeInput && !codeInput.value) {
            titleInput.addEventListener('blur', () => {
                if (!codeInput.value && titleInput.value) {
                    const slug = titleInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '').toUpperCase();
                    codeInput.value = 'RPT-' + slug;
                }
            });
        }
    });

    function handleScheduleChange() {
        const typeSelect = document.getElementById('scheduleTypeSelect');
        const countLabel = document.getElementById('targetCountLabel');
        const typeHint = document.getElementById('scheduleTypeHint');
        const val = typeSelect.value;

        if (val === 'weekly') {
            countLabel.innerHTML = '🎯 Target Pengisian (Per Minggu)';
            typeHint.innerHTML = 'Laporan dikerjakan dengan kuota mingguan pada hari yang ditentukan.';
        } else if (val === 'monthly') {
            countLabel.innerHTML = '🎯 Target Pengisian (Per Bulan / Cut-Off)';
            typeHint.innerHTML = 'Laporan dikerjakan dengan kuota bulanan pada hari yang ditentukan.';
        } else {
            countLabel.innerHTML = '🎯 Target Pengisian (Per Hari)';
            typeHint.innerHTML = 'Laporan akan muncul dan dikerjakan setiap hari kerja aktif.';
        }
    }

    function toggleSelectAllProducts() {
        const checkboxes = document.querySelectorAll('.prod-checkbox');
        const btnText = document.getElementById('btnSelectAllProdText');
        const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);

        checkboxes.forEach(cb => cb.checked = anyUnchecked);
        btnText.innerText = anyUnchecked ? 'Batal Pilih Semua' : 'Pilih Semua';
    }

    function renderAllQuestions() {
        const container = document.getElementById('questionsContainer');
        container.innerHTML = '';

        questions.forEach((q, idx) => {
            container.appendChild(createQuestionCardElement(q, idx));
        });

        document.getElementById('totalQuestionsBadge').innerText = `${questions.length} Pertanyaan`;
    }

    function createQuestionCardElement(q, idx) {
        const card = document.createElement('div');
        card.className = 'question-card';
        card.dataset.index = idx;

        const fieldType = q.field_type || 'text';
        const typeConfig = fieldTypesMap[fieldType] || { label: 'Teks Singkat', icon: 'fa-font', has_options: false };

        // Options array parsing
        let optionsList = [];
        if (Array.isArray(q.options)) {
            optionsList = q.options;
        } else if (typeof q.options === 'string' && q.options.trim()) {
            try {
                optionsList = JSON.parse(q.options);
            } catch (e) {
                optionsList = q.options.split(',').map(s => s.trim()).filter(Boolean);
            }
        }

        // Build Type Options HTML
        let typeSelectOptionsHtml = '';
        for (const [key, conf] of Object.entries(fieldTypesMap)) {
            const selected = key === fieldType ? 'selected' : '';
            typeSelectOptionsHtml += `<option value="${key}" ${selected}>${conf.label}</option>`;
        }

        const showOptions = typeConfig.has_options;

        card.innerHTML = `
            <div class="question-card-header">
                <div class="question-card-num">
                    <div class="question-num-badge">${idx + 1}</div>
                    <span>${escapeHtml(q.field_label || 'Pertanyaan Baru')}</span>
                </div>
                <div class="question-actions-right">
                    ${idx > 0 ? `<button type="button" class="btn-q-action" title="Geser ke Atas" onclick="moveQuestion(${idx}, -1)"><i class="fa-solid fa-arrow-up"></i></button>` : ''}
                    ${idx < questions.length - 1 ? `<button type="button" class="btn-q-action" title="Geser ke Bawah" onclick="moveQuestion(${idx}, 1)"><i class="fa-solid fa-arrow-down"></i></button>` : ''}
                    <button type="button" class="btn-q-action" title="Duplikasi Pertanyaan" onclick="duplicateQuestion(${idx})"><i class="fa-solid fa-copy"></i></button>
                    <button type="button" class="btn-q-action danger" title="Hapus Pertanyaan" onclick="removeQuestion(${idx})"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>

            <div class="question-card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Label Pertanyaan / Field <span class="required-star">*</span></label>
                        <input type="text" name="fields[${idx}][field_label]" value="${escapeHtml(q.field_label || '')}" class="form-control-custom q-label-input" placeholder="Contoh: Jumlah Terjual, Foto Display" required oninput="onLabelChange(${idx}, this.value)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Key / Variable Name <span class="required-star">*</span></label>
                        <input type="text" name="fields[${idx}][field_name]" value="${escapeHtml(q.field_name || '')}" class="form-control-custom q-name-input" placeholder="jumlah_terjual" style="font-family: monospace;" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tipe Input <span class="required-star">*</span></label>
                        <select name="fields[${idx}][field_type]" class="form-control-custom" onchange="onTypeChange(${idx}, this.value)">
                            ${typeSelectOptionsHtml}
                        </select>
                    </div>
                </div>

                <div class="grid-4" style="margin-top: 0.5rem;">
                    <div class="form-group">
                        <label class="form-label">Placeholder Petunjuk</label>
                        <input type="text" name="fields[${idx}][placeholder]" value="${escapeHtml(q.placeholder || '')}" class="form-control-custom" placeholder="Masukkan data...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Keterangan / Hint</label>
                        <input type="text" name="fields[${idx}][help_text]" value="${escapeHtml(q.help_text || '')}" class="form-control-custom" placeholder="Contoh: Wajib foto tampak depan toko">
                    </div>

                    <div class="fb-toggle-item" style="padding: 0.6rem 0.9rem;">
                        <div class="fb-toggle-info">
                            <div class="fb-toggle-title" style="font-size: 0.8rem;">Wajib Diisi</div>
                        </div>
                        <label class="switch-toggle" style="width: 36px; height: 20px;">
                            <input type="checkbox" name="fields[${idx}][is_required]" value="1" ${q.is_required ? 'checked' : ''} onchange="questions[${idx}].is_required = this.checked">
                            <span class="slider-toggle" style="border-radius: 20px;"></span>
                        </label>
                    </div>

                    <div class="fb-toggle-item" style="padding: 0.6rem 0.9rem;">
                        <div class="fb-toggle-info">
                            <div class="fb-toggle-title" style="font-size: 0.8rem;">Read Only</div>
                        </div>
                        <label class="switch-toggle" style="width: 36px; height: 20px;">
                            <input type="checkbox" name="fields[${idx}][is_readonly]" value="1" ${q.is_readonly ? 'checked' : ''} onchange="questions[${idx}].is_readonly = this.checked">
                            <span class="slider-toggle" style="border-radius: 20px;"></span>
                        </label>
                    </div>
                </div>

                <!-- Options Manager Box -->
                <div class="options-manager-box" id="optionsBox_${idx}" style="${showOptions ? '' : 'display: none;'}">
                    <div style="font-weight: 700; font-size: 0.84rem; color: var(--text-heading); margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
                        <span>📋 Daftar Opsi Pilihan (Dropdown / Radio / Checkbox)</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Tekan Enter untuk menambah</span>
                    </div>

                    <div class="options-tags-list" id="optionsTagsList_${idx}">
                        ${optionsList.map((opt, oIdx) => `
                            <div class="opt-chip">
                                <span>${escapeHtml(opt)}</span>
                                <i class="fa-solid fa-xmark opt-chip-delete" onclick="removeOption(${idx}, ${oIdx})"></i>
                                <input type="hidden" name="fields[${idx}][options][]" value="${escapeHtml(opt)}">
                            </div>
                        `).join('')}
                    </div>

                    <div class="opt-input-row">
                        <input type="text" id="newOptInput_${idx}" class="form-control-custom" placeholder="Ketik opsi pilihan baru..." style="flex: 1;" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); addOption(${idx}); }">
                        <button type="button" class="btn-secondary-custom" style="padding: 0.5rem 1rem;" onclick="addOption(${idx})">
                            <i class="fa-solid fa-plus"></i>
                            <span>Tambah Opsi</span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        return card;
    }

    function onLabelChange(idx, val) {
        questions[idx].field_label = val;
        // Auto slug to field_name if empty or synced
        const card = document.querySelector(`.question-card[data-index="${idx}"]`);
        if (card) {
            const numHeader = card.querySelector('.question-card-num span');
            if (numHeader) numHeader.innerText = val || 'Pertanyaan Baru';

            const nameInput = card.querySelector('.q-name-input');
            if (nameInput && (!questions[idx].field_name || questions[idx].field_name === slugify(questions[idx]._prevLabel || ''))) {
                const slug = slugify(val);
                nameInput.value = slug;
                questions[idx].field_name = slug;
            }
            questions[idx]._prevLabel = val;
        }
    }

    function onTypeChange(idx, val) {
        questions[idx].field_type = val;
        const config = fieldTypesMap[val] || {};
        const optionsBox = document.getElementById(`optionsBox_${idx}`);
        if (optionsBox) {
            optionsBox.style.display = config.has_options ? '' : 'none';
        }
    }

    function addOption(qIdx) {
        const input = document.getElementById(`newOptInput_${qIdx}`);
        const val = input.value.trim();
        if (!val) return;

        if (!Array.isArray(questions[qIdx].options)) {
            questions[qIdx].options = [];
        }

        questions[qIdx].options.push(val);
        input.value = '';
        renderAllQuestions();
    }

    function removeOption(qIdx, optIdx) {
        if (Array.isArray(questions[qIdx].options)) {
            questions[qIdx].options.splice(optIdx, 1);
            renderAllQuestions();
        }
    }

    function addQuestion() {
        questions.push({
            field_label: '',
            field_name: '',
            field_type: 'text',
            placeholder: '',
            help_text: '',
            is_required: false,
            is_readonly: false,
            options: []
        });
        renderAllQuestions();

        // Scroll to bottom
        setTimeout(() => {
            const cards = document.querySelectorAll('.question-card');
            if (cards.length > 0) {
                cards[cards.length - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
                const firstInput = cards[cards.length - 1].querySelector('.q-label-input');
                if (firstInput) firstInput.focus();
            }
        }, 100);
    }

    function duplicateQuestion(idx) {
        const cloned = JSON.parse(JSON.stringify(questions[idx]));
        cloned.field_label += ' (Copy)';
        cloned.field_name += '_copy';
        questions.splice(idx + 1, 0, cloned);
        renderAllQuestions();
    }

    function removeQuestion(idx) {
        if (questions.length <= 1) {
            alert('Form template harus memiliki minimal 1 pertanyaan / field.');
            return;
        }
        if (confirm('Hapus pertanyaan ini dari form builder?')) {
            questions.splice(idx, 1);
            renderAllQuestions();
        }
    }

    function moveQuestion(idx, delta) {
        const newIdx = idx + delta;
        if (newIdx < 0 || newIdx >= questions.length) return;

        const temp = questions[idx];
        questions[idx] = questions[newIdx];
        questions[newIdx] = temp;
        renderAllQuestions();
    }

    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '_')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>
@endpush
