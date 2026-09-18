{{-- FILAMENT ADMIN UNIVERSAL NON-INTRUSIVE LOADER (TIDAK MENUTUP TAMPILAN LAYAR) --}}
@php
    $isDashboard = request()->routeIs('filament.admin.pages.dashboard')
        || request()->is('admin')
        || request()->is('admin/')
        || request()->path() === 'admin';
@endphp

@if($isDashboard)
<style id="adminDashboardLoaderDisabler">
    #adminLoadingOverlay,
    .admin-loading-overlay,
    .admin-top-progress-bar,
    .admin-loading-pill {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
</style>
@endif

<div id="adminLoadingOverlay" class="admin-loading-overlay" aria-hidden="true" @if($isDashboard) style="display: none !important; opacity: 0 !important; visibility: hidden !important; pointer-events: none !important;" @endif>
    <!-- Top Linear Indeterminate Progress Bar (Garis indikator 3.5px di tepi paling atas layar) -->
    <div class="admin-top-progress-bar">
        <div class="admin-top-progress-indicator"></div>
    </div>

    <!-- Floating Compact Dynamic Island Pill (Badge mengambang di pojok kanan atas, tidak menghalangi konten layar) -->
    <div class="admin-loading-pill">
        <div class="admin-pill-spinner">
            <svg class="admin-pill-svg" viewBox="0 0 24 24">
                <circle class="admin-pill-circle-track" cx="12" cy="12" r="9" fill="none" stroke-width="2.5"></circle>
                <circle class="admin-pill-circle-head" cx="12" cy="12" r="9" fill="none" stroke-width="2.5" stroke-dasharray="16 38" stroke-linecap="round"></circle>
            </svg>
        </div>
        <div class="admin-pill-text-group">
            <span id="adminLoadingTitle" class="admin-pill-title">Memproses...</span>
            <span class="admin-pill-dots"><span>.</span><span>.</span><span>.</span></span>
        </div>
        <span id="adminLoadingSubtitle" style="display: none;"></span>
    </div>
</div>

<style>
/* ===============================================================
   FILAMENT ADMIN NON-INTRUSIVE LOADER STYLES
   Desain minimalis modern: TIDAK menutupi layar, TIDAK blur layar,
   dan pointer-events none agar interaksi layar tetap terlihat jelas.
   =============================================================== */
.admin-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 0;
    z-index: 999999;
    pointer-events: none !important;
    background: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Explicitly disable on dashboard page */
body.fi-page-dashboard #adminLoadingOverlay,
body.fi-page-dashboard .admin-loading-overlay,
html[data-page="dashboard"] #adminLoadingOverlay {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

/* Top Linear Progress Bar */
.admin-top-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3.5px;
    background: rgba(15, 82, 186, 0.12);
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1000000;
    pointer-events: none !important;
}

.dark .admin-top-progress-bar {
    background: rgba(96, 165, 250, 0.18);
}

.admin-loading-overlay.active .admin-top-progress-bar {
    opacity: 1;
    visibility: visible;
}

.admin-top-progress-indicator {
    position: absolute;
    top: 0;
    bottom: 0;
    left: -40%;
    width: 45%;
    background: linear-gradient(90deg, #0F52BA, #00d2ff, #0F52BA);
    border-radius: 999px;
    box-shadow: 0 0 10px rgba(0, 210, 255, 0.7), 0 0 4px rgba(15, 82, 186, 0.8);
    animation: adminTopProgressAnim 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.dark .admin-top-progress-indicator {
    background: linear-gradient(90deg, #3b82f6, #67e8f9, #3b82f6);
    box-shadow: 0 0 10px rgba(103, 232, 249, 0.8);
}

/* Floating Dynamic Island Pill (Pojok Kanan Atas) */
.admin-loading-pill {
    position: fixed;
    top: 18px;
    right: 24px;
    z-index: 1000000;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(15, 82, 186, 0.18);
    box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.15),
                0 4px 10px -2px rgba(15, 82, 186, 0.12);
    border-radius: 999px;
    padding: 7px 16px 7px 12px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-12px) scale(0.96);
    transition: opacity 0.22s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), visibility 0.22s ease;
    pointer-events: none !important;
}

.dark .admin-loading-pill {
    background: rgba(15, 23, 42, 0.94) !important;
    border-color: rgba(96, 165, 250, 0.25) !important;
    box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.55),
                0 4px 10px -2px rgba(59, 130, 246, 0.25) !important;
}

.admin-loading-overlay.active .admin-loading-pill {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

/* Spinner */
.admin-pill-spinner {
    position: relative;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.admin-pill-svg {
    width: 18px;
    height: 18px;
    animation: adminPillSpin 0.8s linear infinite;
    flex-shrink: 0;
}

.admin-pill-circle-track {
    stroke: rgba(15, 82, 186, 0.18);
}

.dark .admin-pill-circle-track {
    stroke: rgba(96, 165, 250, 0.22);
}

.admin-pill-circle-head {
    stroke: #0F52BA;
}

.dark .admin-pill-circle-head {
    stroke: #60a5fa;
}

/* Text */
.admin-pill-text-group {
    display: inline-flex;
    align-items: center;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
}

.dark .admin-pill-text-group {
    color: #f8fafc !important;
}

.admin-pill-title {
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.dark .admin-pill-title {
    color: #f8fafc !important;
}

.admin-pill-dots span {
    display: inline-block;
    animation: adminDotsAnim 1.4s infinite;
    font-weight: 800;
}
.admin-pill-dots span:nth-child(2) { animation-delay: 0.2s; }
.admin-pill-dots span:nth-child(3) { animation-delay: 0.4s; }

/* Keyframes */
@keyframes adminPillSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes adminTopProgressAnim {
    0% {
        left: -40%;
        width: 35%;
    }
    50% {
        left: 25%;
        width: 55%;
    }
    100% {
        left: 105%;
        width: 40%;
    }
}

@keyframes adminDotsAnim {
    0%, 20% { opacity: 0; transform: translateY(0); }
    50% { opacity: 1; transform: translateY(-1.5px); }
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

    function isDashboardPage() {
        try {
            const path = window.location.pathname.replace(/\/+$/, '');
            if (path === '/admin' || path === '' || window.location.pathname === '/admin' || window.location.pathname === '/admin/') {
                return true;
            }
            if (document.body && (document.body.classList.contains('fi-page-dashboard') || document.body.dataset.page === 'dashboard')) {
                return true;
            }
            if (document.querySelector('.fi-wi-active-employees-hourly') && (path === '/admin' || path.endsWith('/admin'))) {
                return true;
            }
        } catch(e) {}
        return false;
    }

    function syncDashboardOverlayState() {
        if (!overlay) return;
        if (isDashboardPage()) {
            overlay.classList.remove('active');
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.setProperty('display', 'none', 'important');
            overlay.style.setProperty('opacity', '0', 'important');
            overlay.style.setProperty('visibility', 'hidden', 'important');
            overlay.style.setProperty('pointer-events', 'none', 'important');
        } else {
            overlay.style.removeProperty('display');
            overlay.style.removeProperty('opacity');
            overlay.style.removeProperty('visibility');
            overlay.style.removeProperty('pointer-events');
        }
    }

    window.showAdminLoader = function(title, subtitle) {
        if (!overlay || isDashboardPage()) return;
        if (title && titleEl) {
            titleEl.textContent = title;
        }
        if (subtitle && subEl) {
            subEl.textContent = subtitle;
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
        syncDashboardOverlayState();
        setTimeout(function() {
            window.hideAdminLoader();
            syncDashboardOverlayState();
        }, 120);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        dismissInitialLoader();
    } else {
        document.addEventListener('DOMContentLoaded', dismissInitialLoader);
    }
    window.addEventListener('load', dismissInitialLoader);
    window.addEventListener('pageshow', dismissInitialLoader);

    // Initial sync
    syncDashboardOverlayState();

    // 1. Livewire Navigation & Request Hooks
    document.addEventListener('livewire:navigating', function(e) {
        try {
            const url = e.detail?.url || '';
            const urlObj = new URL(url, window.location.origin);
            const path = urlObj.pathname.replace(/\/+$/, '');
            if (path === '/admin' || path === '') {
                syncDashboardOverlayState();
                return;
            }
        } catch(err) {}

        if (isDashboardPage()) return;
        window.showAdminLoader('Memuat Halaman...', 'Sedang menyiapkan data');
    });

    document.addEventListener('livewire:navigated', function() {
        syncDashboardOverlayState();
        window.hideAdminLoader();
    });

    document.addEventListener('livewire:init', function() {
        if (typeof Livewire !== 'undefined' && Livewire.hook) {
            Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                if (isDashboardPage()) return;

                // Check if this request is a background poll
                const payloadStr = JSON.stringify(payload || {});
                const isPoll = payloadStr.includes('poll') 
                    || payloadStr.includes('updateChartData') 
                    || payloadStr.includes('databaseNotifications');

                if (isPoll) return;

                // Only show for explicit user interactions
                const activeEl = document.activeElement;
                const isUserAction = activeEl && (
                    activeEl.tagName === 'BUTTON' || 
                    activeEl.closest('button') || 
                    activeEl.tagName === 'A' || 
                    activeEl.closest('a') || 
                    (activeEl.tagName === 'INPUT' && activeEl.type === 'submit')
                );

                if (!isUserAction) return;

                if (livewireDebounceTimer) clearTimeout(livewireDebounceTimer);
                livewireDebounceTimer = setTimeout(function() {
                    if (isDashboardPage()) return;

                    let title = 'Memproses Permintaan...';
                    if (activeEl && (activeEl.tagName === 'BUTTON' || activeEl.closest('button'))) {
                        const btnText = activeEl.innerText?.trim() || 'Aksi';
                        title = btnText.length < 25 ? (btnText + '...') : 'Menjalankan Aksi...';
                    }

                    window.showAdminLoader(title);
                }, 200);

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

    // 2. Auto-attach to all Forms
    document.addEventListener('submit', function(e) {
        if (isDashboardPage()) return;

        const form = e.target;
        if (!form || form.hasAttribute('data-no-loader') || form.closest('[data-no-loader]')) return;

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;

        if (form.action && form.action.includes('logout')) {
            window.showAdminLoader('Keluar dari Akun...', 'Mengakhiri sesi');
            return;
        }

        let title = 'Menyimpan Perubahan...';
        const submitBtn = e.submitter || form.querySelector('[type="submit"]');
        if (submitBtn && submitBtn.innerText?.trim()) {
            const btnLabel = submitBtn.innerText.trim();
            if (btnLabel.length < 25) {
                title = btnLabel + '...';
            }
        }

        window.showAdminLoader(title);
    });

    // 3. Universal Link & Menu Click Listener
    document.addEventListener('click', function(e) {
        const actionBtn = e.target.closest('.fi-btn, .fi-ta-action, .fi-ac-action, [data-filament-action]');
        if (actionBtn && !actionBtn.hasAttribute('data-no-loader')) {
            if (isDashboardPage()) return;

            const label = actionBtn.innerText?.trim() || actionBtn.getAttribute('title') || 'Aksi';
            const cleanLabel = label.replace(/\s+/g, ' ').trim();
            if (actionBtn.type === 'submit' || actionBtn.getAttribute('wire:click') || actionBtn.getAttribute('wire:submit')) {
                window.showAdminLoader((cleanLabel.length < 25 ? cleanLabel : 'Memproses') + '...');
                return;
            }
        }

        const link = e.target.closest('a');
        if (!link) return;

        if (e.ctrlKey || e.shiftKey || e.metaKey || e.which === 2) return;
        if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-loader') || link.closest('[data-no-loader]')) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('tel:') || href.startsWith('mailto:')) return;

        if (href === '/admin' || href === '/admin/' || href.endsWith('/admin')) {
            syncDashboardOverlayState();
            return;
        }

        if (isDashboardPage() && !href.startsWith('/admin/')) return;

        // A. Sidebar navigation
        if (link.closest('.fi-sidebar-item') || link.closest('.fi-sidebar-group') || link.classList.contains('fi-sidebar-item')) {
            const navText = link.querySelector('.fi-sidebar-item-label, span')?.textContent?.trim() || 'Menu';
            window.showAdminLoader('Membuka ' + navText + '...');
            return;
        }

        // B. Table pagination
        if (link.closest('.fi-ta-pagination') || link.closest('.fi-pagination') || link.classList.contains('fi-pagination-item')) {
            window.showAdminLoader('Memuat Halaman Data...');
            return;
        }

        // C. Export Excel / CSV links
        if (href.includes('/export') || link.classList.contains('btn-export') || href.includes('export=')) {
            window.showAdminLoader('Menyiapkan Ekspor Data...');
            setTimeout(window.hideAdminLoader, 7000);
            return;
        }

        // D. Breadcrumb navigation
        if (link.closest('.fi-breadcrumbs') || link.classList.contains('fi-breadcrumbs-item')) {
            const bText = link.innerText?.trim() || 'Halaman';
            window.showAdminLoader('Navigasi ke ' + bText + '...');
            return;
        }

        // E. General internal link
        const isInternal = href.startsWith('/') || href.includes(window.location.host);
        if (isInternal && !link.closest('form')) {
            const rawText = link.getAttribute('title') || link.innerText?.trim() || '';
            const cleanText = rawText.replace(/\s+/g, ' ').trim();
            const displayTitle = (cleanText && cleanText.length < 25) ? ('Memuat ' + cleanText + '...') : 'Memuat Halaman...';
            window.showAdminLoader(displayTitle);
        }
    });
})();
</script>
