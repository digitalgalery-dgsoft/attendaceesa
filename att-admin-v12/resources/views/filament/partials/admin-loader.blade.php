{{-- FILAMENT ADMIN UNIVERSAL PREMIUM LOADING OVERLAY --}}
<div id="adminLoadingOverlay" class="admin-loading-overlay" aria-hidden="true">
    <div class="admin-loading-card">
        <!-- Ambient Glow -->
        <div class="admin-loading-glow"></div>

        <!-- Modern Dual-Orbit Spinner -->
        <div class="admin-spinner-wrapper">
            <div class="admin-spinner-outer"></div>
            <div class="admin-spinner-inner"></div>
            <div class="admin-spinner-icon">
                @php
                    $setting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
                    $logoPath = $setting?->logo_path;
                @endphp
                @if(!empty($logoPath))
                    <img src="/app-logo" alt="ESA" class="admin-spinner-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="admin-spinner-fallback" style="display: none;">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                @else
                    <div class="admin-spinner-fallback">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; color: #0F52BA;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                @endif
            </div>
        </div>

        <!-- Text & Status Message -->
        <div class="admin-loading-text-group">
            <h4 id="adminLoadingTitle" class="admin-loading-title">Memuat Dashboard Admin...</h4>
            <p id="adminLoadingSubtitle" class="admin-loading-subtitle">
                Sedang memproses dan menyajikan data dari server, mohon tunggu
                <span class="loading-dots"><span>.</span><span>.</span><span>.</span></span>
            </p>
        </div>

        <!-- Shimmering Indeterminate Progress Bar -->
        <div class="admin-loading-progress-track">
            <div class="admin-loading-progress-bar"></div>
        </div>

        <div class="admin-loading-meta">
            <span class="admin-meta-brand">
                <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                ESA Groups Admin
            </span>
            <span class="admin-meta-tip">
                <svg class="admin-spin-icon" style="width: 12px; height: 12px; display: inline-block; vertical-align: -1px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Sinkronisasi Sistem
            </span>
        </div>
    </div>
</div>

<style>
/* ===============================================================
   FILAMENT ADMIN UNIVERSAL LOADING SCREEN STYLES
   =============================================================== */
.admin-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                visibility 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.admin-loading-overlay.active {
    opacity: 1;
    visibility: visible;
    pointer-events: all;
}

.admin-loading-card {
    position: relative;
    width: 90%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(255, 255, 255, 0.85);
    box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.3),
                0 0 0 1px rgba(0, 0, 0, 0.05),
                0 12px 32px -4px rgba(15, 82, 186, 0.25);
    border-radius: 24px;
    padding: 2.25rem 2rem 1.65rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    overflow: hidden;
    transform: scale(0.92) translateY(14px);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.2s ease, border-color 0.2s ease;
}

.dark .admin-loading-card {
    background: rgba(15, 23, 42, 0.94) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.08),
                0 12px 32px -4px rgba(59, 130, 246, 0.3) !important;
}

.admin-loading-overlay.active .admin-loading-card {
    transform: scale(1) translateY(0);
}

.admin-loading-glow {
    position: absolute;
    top: -45px;
    left: 50%;
    transform: translateX(-50%);
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(15, 82, 186, 0.3) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius: 50%;
    filter: blur(25px);
    pointer-events: none;
    z-index: 0;
}

.dark .admin-loading-glow {
    background: radial-gradient(circle, rgba(59, 130, 246, 0.35) 0%, rgba(15, 23, 42, 0) 70%) !important;
}

/* Spinner Wrapper & Orbit Rings */
.admin-spinner-wrapper {
    position: relative;
    width: 92px;
    height: 92px;
    margin-bottom: 1.35rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.admin-spinner-outer {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #0F52BA;
    border-right-color: #2563EB;
    animation: adminSpin 1.1s cubic-bezier(0.5, 0.1, 0.5, 0.9) infinite;
}

.admin-spinner-inner {
    position: absolute;
    inset: 7px;
    border-radius: 50%;
    border: 2px dashed rgba(15, 82, 186, 0.3);
    border-bottom-color: #0F52BA;
    animation: adminSpinReverse 2.2s linear infinite;
}

.dark .admin-spinner-outer {
    border-top-color: #3b82f6;
    border-right-color: #60a5fa;
}

.dark .admin-spinner-inner {
    border-color: rgba(96, 165, 250, 0.3);
    border-bottom-color: #3b82f6;
}

.admin-spinner-icon {
    width: 50px;
    height: 50px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    animation: adminPulse 2s ease-in-out infinite;
    z-index: 2;
}

.dark .admin-spinner-icon {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.admin-spinner-logo {
    max-width: 34px;
    max-height: 34px;
    object-fit: contain;
}

.admin-spinner-fallback {
    color: #0F52BA;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dark .admin-spinner-fallback {
    color: #60a5fa !important;
}

/* Text Group */
.admin-loading-text-group {
    position: relative;
    z-index: 1;
    margin-bottom: 1.25rem;
    max-width: 330px;
}

.admin-loading-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
    margin: 0 0 0.35rem 0;
}

.dark .admin-loading-title {
    color: #f8fafc !important;
}

.admin-loading-subtitle {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.45;
    font-weight: 500;
    margin: 0;
}

.dark .admin-loading-subtitle {
    color: #94a3b8 !important;
}

/* Animated Dots */
.loading-dots span {
    display: inline-block;
    animation: adminLoadingDots 1.4s infinite;
    font-weight: 800;
}
.loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.loading-dots span:nth-child(3) { animation-delay: 0.4s; }

/* Progress Track & Shimmer Bar */
.admin-loading-progress-track {
    position: relative;
    z-index: 1;
    width: 100%;
    height: 6px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 1.15rem;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);
}

.dark .admin-loading-progress-track {
    background: #1e293b !important;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.3);
}

.admin-loading-progress-bar {
    position: absolute;
    top: 0;
    bottom: 0;
    left: -40%;
    width: 45%;
    background: linear-gradient(90deg, #0F52BA, #3b82f6);
    border-radius: 999px;
    box-shadow: 0 0 12px rgba(15, 82, 186, 0.4);
    animation: adminIndeterminate 1.6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.dark .admin-loading-progress-bar {
    background: linear-gradient(90deg, #2563eb, #60a5fa) !important;
    box-shadow: 0 0 12px rgba(59, 130, 246, 0.5) !important;
}

/* Meta Footer */
.admin-loading-meta {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    font-size: 0.74rem;
    color: #94a3b8;
    font-weight: 600;
    padding-top: 0.75rem;
    border-top: 1px dashed #e2e8f0;
}

.dark .admin-loading-meta {
    border-top-color: rgba(255, 255, 255, 0.08) !important;
    color: #64748b !important;
}

.admin-meta-brand {
    color: #0F52BA;
    display: inline-flex;
    align-items: center;
    font-weight: 700;
}

.dark .admin-meta-brand {
    color: #60a5fa !important;
}

.admin-meta-tip {
    display: inline-flex;
    align-items: center;
}

.admin-spin-icon {
    animation: adminSpin 2s linear infinite;
}

/* Keyframes */
@keyframes adminSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes adminSpinReverse {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(-360deg); }
}

@keyframes adminPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes adminIndeterminate {
    0% {
        left: -45%;
        width: 35%;
    }
    50% {
        left: 25%;
        width: 60%;
    }
    100% {
        left: 105%;
        width: 40%;
    }
}

@keyframes adminLoadingDots {
    0%, 20% { opacity: 0; transform: translateY(0); }
    50% { opacity: 1; transform: translateY(-2px); }
    80%, 100% { opacity: 0; transform: translateY(0); }
}
</style>

<script>
(function() {
    const overlay = document.getElementById('adminLoadingOverlay');
    const titleEl = document.getElementById('adminLoadingTitle');
    const subEl = document.getElementById('adminLoadingSubtitle');
    let safetyTimer = null;
    let livewireDebounceTimer = null;

    window.showAdminLoader = function(title, subtitle) {
        if (!overlay) return;
        if (title && titleEl) titleEl.textContent = title;
        if (subtitle && subEl) {
            subEl.innerHTML = subtitle + '<span class="loading-dots"><span>.</span><span>.</span><span>.</span></span>';
        }
        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');

        // Safety timeout in case navigation or download is cancelled
        if (safetyTimer) clearTimeout(safetyTimer);
        safetyTimer = setTimeout(function() {
            window.hideAdminLoader();
        }, 15000);
    };

    window.hideAdminLoader = function() {
        if (!overlay) return;
        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');
        if (safetyTimer) clearTimeout(safetyTimer);
        if (livewireDebounceTimer) clearTimeout(livewireDebounceTimer);
    };

    // Smoothly dismiss loader on page ready / restore
    function dismissInitialLoader() {
        setTimeout(function() {
            window.hideAdminLoader();
        }, 180);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        dismissInitialLoader();
    } else {
        document.addEventListener('DOMContentLoaded', dismissInitialLoader);
    }
    window.addEventListener('load', window.hideAdminLoader);
    window.addEventListener('pageshow', window.hideAdminLoader);

    // 1. Livewire Navigation & Request Hooks
    document.addEventListener('livewire:navigating', function() {
        window.showAdminLoader('Memuat Halaman...', 'Sedang menyiapkan data dan komponen antarmuka admin');
    });

    document.addEventListener('livewire:navigated', function() {
        window.hideAdminLoader();
    });

    document.addEventListener('livewire:init', function() {
        if (typeof Livewire !== 'undefined' && Livewire.hook) {
            Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                // For actions, table filters, bulk actions, modal actions, form saves:
                // Show loader if request takes longer than 220ms (smooth, non-flickering for fast micro-updates)
                if (livewireDebounceTimer) clearTimeout(livewireDebounceTimer);
                livewireDebounceTimer = setTimeout(function() {
                    const activeEl = document.activeElement;
                    let title = 'Memproses Permintaan...';
                    let sub = 'Sedang berkomunikasi dengan server aman, mohon tunggu';

                    if (activeEl && (activeEl.tagName === 'BUTTON' || activeEl.closest('button'))) {
                        const btnText = activeEl.innerText?.trim() || 'Aksi';
                        title = btnText.length < 30 ? (btnText + '...') : 'Menjalankan Aksi...';
                        sub = 'Sedang memproses perubahan data di server';
                    }

                    window.showAdminLoader(title, sub);
                }, 220);

                respond(() => {
                    if (livewireDebounceTimer) clearTimeout(livewireDebounceTimer);
                    window.hideAdminLoader();
                });

                succeed(() => {
                    if (livewireDebounceTimer) clearTimeout(livewireDebounceTimer);
                    window.hideAdminLoader();
                });

                fail(() => {
                    if (livewireDebounceTimer) clearTimeout(livewireDebounceTimer);
                    window.hideAdminLoader();
                });
            });
        }
    });

    // 2. Auto-attach to all Forms (save, create, edit, filter, search)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.hasAttribute('data-no-loader') || form.closest('[data-no-loader]')) return;

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        if (form.action && form.action.includes('logout')) {
            window.showAdminLoader('Keluar dari Akun...', 'Mengakhiri sesi admin secara aman');
            return;
        }

        let title = 'Menyimpan Perubahan...';
        let sub = 'Sedang memvalidasi dan memperbarui data di database';

        const submitBtn = e.submitter || form.querySelector('[type="submit"]');
        if (submitBtn && submitBtn.innerText?.trim()) {
            const btnLabel = submitBtn.innerText.trim();
            if (btnLabel.length < 28) {
                title = btnLabel + '...';
            }
        }

        window.showAdminLoader(title, sub);
    });

    // 3. Universal Link & Menu Click Listener
    document.addEventListener('click', function(e) {
        // Check for interactive button clicks inside tables/headers that execute heavy actions
        const actionBtn = e.target.closest('.fi-btn, .fi-ta-action, .fi-ac-action, [data-filament-action]');
        if (actionBtn && !actionBtn.hasAttribute('data-no-loader')) {
            const label = actionBtn.innerText?.trim() || actionBtn.getAttribute('title') || 'Aksi';
            const cleanLabel = label.replace(/\s+/g, ' ').trim();
            if (actionBtn.type === 'submit' || actionBtn.getAttribute('wire:click') || actionBtn.getAttribute('wire:submit')) {
                window.showAdminLoader((cleanLabel.length < 25 ? cleanLabel : 'Memproses') + '...', 'Sedang mengeksekusi perintah di server, mohon tunggu');
                return;
            }
        }

        const link = e.target.closest('a');
        if (!link) return;

        // Ignore modifier keys / middle click / external targets / downloads / bootstrap / Alpine toggles
        if (e.ctrlKey || e.shiftKey || e.metaKey || e.which === 2) return;
        if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-loader') || link.closest('[data-no-loader]')) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) return;

        // A. Sidebar navigation
        if (link.closest('.fi-sidebar-item') || link.closest('.fi-sidebar-group') || link.classList.contains('fi-sidebar-item')) {
            const navText = link.querySelector('.fi-sidebar-item-label, span')?.textContent?.trim() || 'Menu Admin';
            window.showAdminLoader('Membuka ' + navText + '...', 'Sedang menyiapkan data dan antarmuka modul');
            return;
        }

        // B. Table pagination
        if (link.closest('.fi-ta-pagination') || link.closest('.fi-pagination') || link.classList.contains('fi-pagination-item')) {
            window.showAdminLoader('Memuat Halaman Data...', 'Sedang mengambil baris data tabel berikutnya');
            return;
        }

        // C. Export Excel / CSV links (auto-hide after 7s)
        if (href.includes('/export') || link.classList.contains('btn-export') || href.includes('export=')) {
            window.showAdminLoader('Menyiapkan Ekspor Data...', 'Sedang menyusun baris data ke format Excel');
            setTimeout(window.hideAdminLoader, 7000);
            return;
        }

        // D. Breadcrumb navigation
        if (link.closest('.fi-breadcrumbs') || link.classList.contains('fi-breadcrumbs-item')) {
            const bText = link.innerText?.trim() || 'Halaman';
            window.showAdminLoader('Navigasi ke ' + bText + '...', 'Menyiapkan tampilan halaman');
            return;
        }

        // E. General internal link
        const isInternal = href.startsWith('/') || href.includes(window.location.host);
        if (isInternal && !link.closest('form')) {
            const rawText = link.getAttribute('title') || link.innerText?.trim() || '';
            const cleanText = rawText.replace(/\s+/g, ' ').trim();
            const displayTitle = (cleanText && cleanText.length < 30) ? ('Memuat ' + cleanText + '...') : 'Memuat Halaman Admin...';
            window.showAdminLoader(displayTitle, 'Sedang menghubungkan ke server aman, mohon tunggu');
        }
    });
})();
</script>
