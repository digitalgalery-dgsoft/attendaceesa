@php
    $currentSetting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
    $defaultTheme = $currentSetting?->dark_mode_theme ?? 'dark_navy';
@endphp

<div x-data="{
        open: false,
        currentTheme: '{{ $defaultTheme }}',
        isDark: false,
        themes: [
            { id: 'pitch_black', name: 'Pitch Black', color: '#000000', border: '#60a5fa', desc: 'AMOLED Pure Black' },
            { id: 'dark_navy', name: 'Dark Navy', color: '#0b1120', border: '#3b82f6', desc: 'Midnight Blue' },
            { id: 'dark_grey', name: 'Dark Grey', color: '#18181b', border: '#38bdf8', desc: 'Modern Slate Charcoal' },
            { id: 'dark_emerald', name: 'Dark Emerald', color: '#022c22', border: '#34d399', desc: 'Forest Green' },
            { id: 'dark_purple', name: 'Dark Purple', color: '#0f0728', border: '#c084fc', desc: 'Royal Amethyst' }
        ],
        init() {
            // Read saved theme or fallback to server default
            const local = localStorage.getItem('esa_dark_theme');
            if (local && ['pitch_black','dark_navy','dark_grey','dark_emerald','dark_purple'].includes(local)) {
                this.currentTheme = local;
            } else {
                this.currentTheme = '{{ $defaultTheme }}';
            }
            this.checkDarkMode();
            this.applyTheme(this.currentTheme, false);

            window.addEventListener('esa-theme-changed', (e) => {
                if (e.detail?.theme) {
                    this.currentTheme = e.detail.theme;
                    this.applyTheme(e.detail.theme, false);
                }
            });

            const observer = new MutationObserver(() => this.checkDarkMode());
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-dark-theme'] });
        },
        checkDarkMode() {
            this.isDark = document.documentElement.classList.contains('dark');
            const attrTheme = document.documentElement.getAttribute('data-dark-theme');
            if (attrTheme && attrTheme !== this.currentTheme) {
                this.currentTheme = attrTheme;
            }
        },
        toggleDarkMode() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                if (document.body) document.body.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                this.isDark = false;
            } else {
                html.classList.add('dark');
                if (document.body) document.body.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                this.isDark = true;
                this.applyTheme(this.currentTheme, false);
            }
            window.dispatchEvent(new CustomEvent('dark-mode-toggled', { detail: { isDark: this.isDark } }));
        },
        applyTheme(themeId, activateDark = true) {
            this.currentTheme = themeId;
            document.documentElement.setAttribute('data-dark-theme', themeId);
            if (document.body) document.body.setAttribute('data-dark-theme', themeId);
            localStorage.setItem('esa_dark_theme', themeId);

            if (activateDark && !document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.add('dark');
                if (document.body) document.body.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                this.isDark = true;
            }
            this.open = false;
        }
    }"
    class="relative inline-flex items-center"
    style="margin-right: 0.5rem;"
>
    <!-- Toggle / Theme Menu Trigger Button -->
    <div style="display: flex; align-items: center; gap: 4px; background: rgba(241, 245, 249, 0.9); border: 1px solid rgba(203, 213, 225, 0.9); border-radius: 12px; padding: 2px 5px;"
         class="dark:!bg-slate-800/90 dark:!border-slate-700">
        <!-- Sun / Moon Quick Toggle -->
        <button type="button" 
                @click="toggleDarkMode()" 
                title="Toggle Light / Dark Mode"
                style="display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; border: none; background: transparent; cursor: pointer; transition: all 0.2s ease;"
                class="hover:bg-white dark:hover:bg-slate-700">
            <span x-show="!isDark" style="display: flex; align-items: center; justify-content: center;">
                <svg style="width: 16px; height: 16px; color: #f59e0b;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                </svg>
            </span>
            <span x-show="isDark" style="display: flex; align-items: center; justify-content: center;">
                <svg style="width: 15px; height: 15px; color: #60a5fa;" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                </svg>
            </span>
        </button>

        <div style="width: 1px; height: 16px; background: rgba(203, 213, 225, 0.8);" class="dark:!bg-slate-700"></div>

        <!-- Palette Variant Trigger -->
        <button type="button" 
                @click="open = !open" 
                title="Pilih Variasi Warna Dark Mode"
                style="display: flex; align-items: center; gap: 6px; padding: 3px 6px; border-radius: 7px; border: none; background: transparent; cursor: pointer; font-size: 0.75rem; font-weight: 700;"
                class="hover:bg-white text-slate-700 dark:hover:bg-slate-700 dark:text-slate-200">
            <span style="display: inline-block; width: 9px; height: 9px; border-radius: 9999px; box-shadow: 0 0 0 1.5px rgba(0,0,0,0.15);"
                  :style="'background: ' + (themes.find(t => t.id === currentTheme)?.border || '#3b82f6')"></span>
            <span x-text="themes.find(t => t.id === currentTheme)?.name || 'Theme'" class="hidden sm:inline" style="font-size: 0.75rem;"></span>
            <svg style="width: 12px; height: 12px; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         @click.away="open = false" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 transform scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 transform scale-95 -translate-y-2"
         style="position: absolute; right: 0; top: 100%; margin-top: 8px; width: 230px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15), 0 8px 10px -6px rgba(0,0,0,0.05); padding: 6px; z-index: 99999;"
         class="dark:!bg-slate-900 dark:!border-slate-700"
         x-cloak>
        <div style="padding: 6px 10px 8px 10px; border-bottom: 1px solid #f1f5f9; margin-bottom: 4px;" class="dark:!border-slate-800">
            <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;" class="dark:!text-slate-400">
                Pilih Nuansa Dark Mode
            </div>
            <div style="font-size: 0.68rem; color: #94a3b8; margin-top: 1px;">
                Klik untuk mengganti tema seketika
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 2px;">
            <template x-for="t in themes" :key="t.id">
                <button type="button" 
                        @click="applyTheme(t.id, true)"
                        style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 7px 10px; border-radius: 9px; border: none; cursor: pointer; text-align: left; transition: all 0.15s ease;"
                        :style="currentTheme === t.id && isDark ? 'background: rgba(59, 130, 246, 0.12); font-weight: 700;' : 'background: transparent;'"
                        class="hover:bg-slate-50 dark:hover:!bg-slate-800">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 14px; height: 14px; border-radius: 4px; border: 1.5px solid;"
                              :style="'background: ' + t.color + '; border-color: ' + t.border"></span>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.8rem; color: inherit;" 
                                  :style="currentTheme === t.id && isDark ? 'color: #3b82f6;' : ''"
                                  class="text-slate-800 dark:!text-slate-200"
                                  x-text="t.name"></span>
                            <span style="font-size: 0.65rem; color: #94a3b8;" x-text="t.desc"></span>
                        </div>
                    </div>
                    <span x-show="currentTheme === t.id && isDark" style="display: flex; align-items: center; color: #3b82f6;">
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </button>
            </template>
        </div>
    </div>
</div>
