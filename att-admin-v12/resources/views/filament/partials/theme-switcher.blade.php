@php
    $currentSetting = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
    $defaultTheme = $currentSetting?->dark_mode_theme ?? 'dark_navy';
@endphp

<div x-data="{
        open: false,
        currentTheme: localStorage.getItem('esa_dark_theme') || '{{ $defaultTheme }}',
        isDark: false,
        themes: [
            { id: 'dark_navy', name: 'Dark Navy', color: '#0f172a', border: '#3b82f6', icon: '🌌', desc: 'Midnight Blue' },
            { id: 'pitch_black', name: 'Pitch Black', color: '#000000', border: '#60a5fa', icon: '⬛', desc: 'AMOLED Pure' },
            { id: 'dark_grey', name: 'Dark Grey', color: '#27272a', border: '#38bdf8', icon: '🔘', desc: 'Charcoal Slate' },
            { id: 'dark_emerald', name: 'Dark Emerald', color: '#064e3b', border: '#34d399', icon: '🌲', desc: 'Forest Green' },
            { id: 'dark_purple', name: 'Dark Purple', color: '#2e1065', border: '#c084fc', icon: '🔮', desc: 'Royal Amethyst' }
        ],
        init() {
            this.checkDarkMode();
            this.applyTheme(this.currentTheme, false);
            
            // Watch for changes in class 'dark' on html element
            const observer = new MutationObserver(() => this.checkDarkMode());
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },
        checkDarkMode() {
            this.isDark = document.documentElement.classList.contains('dark') || localStorage.getItem('theme') === 'dark';
        },
        toggleDarkMode() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                this.isDark = false;
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                this.isDark = true;
                this.applyTheme(this.currentTheme, false);
            }
            window.dispatchEvent(new CustomEvent('dark-mode-toggled', { detail: { isDark: this.isDark } }));
        },
        applyTheme(themeId, activateDark = true) {
            this.currentTheme = themeId;
            document.documentElement.setAttribute('data-dark-theme', themeId);
            localStorage.setItem('esa_dark_theme', themeId);
            
            if (activateDark && !document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.add('dark');
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
    <div style="display: flex; align-items: center; gap: 4px; background: rgba(241, 245, 249, 0.8); border: 1px solid rgba(203, 213, 225, 0.8); border-radius: 12px; padding: 3px 6px;">
        <!-- Sun / Moon Quick Toggle -->
        <button type="button" 
                @click="toggleDarkMode()" 
                title="Toggle Light / Dark Mode"
                style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 9px; border: none; background: transparent; cursor: pointer; transition: all 0.2s ease; color: #475569;"
                class="hover:bg-white dark:hover:bg-slate-700">
            <template x-if="!isDark">
                <i class="fa-solid fa-sun" style="color: #f59e0b; font-size: 0.95rem;"></i>
            </template>
            <template x-if="isDark">
                <i class="fa-solid fa-moon" style="color: #60a5fa; font-size: 0.95rem;"></i>
            </template>
        </button>

        <!-- Palette Variant Trigger -->
        <button type="button" 
                @click="open = !open" 
                title="Pilih Variasi Warna Dark Mode"
                style="display: flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 8px; border: none; background: transparent; cursor: pointer; font-size: 0.78rem; font-weight: 700; color: #334155;"
                class="hover:bg-white dark:hover:bg-slate-700 dark:text-slate-200">
            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 9999px;"
                  :style="'background: ' + (themes.find(t => t.id === currentTheme)?.border || '#3b82f6')"></span>
            <span x-text="themes.find(t => t.id === currentTheme)?.name || 'Theme'" class="hidden sm:inline" style="font-size: 0.75rem;"></span>
            <i class="fa-solid fa-chevron-down" style="font-size: 0.65rem; opacity: 0.6;"></i>
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
         style="position: absolute; right: 0; top: 100%; margin-top: 8px; width: 230px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05); padding: 6px; z-index: 99999;"
         class="dark:bg-slate-900 dark:border-slate-700"
         x-cloak>
        <div style="padding: 6px 10px 8px 10px; border-bottom: 1px solid #f1f5f9; margin-bottom: 4px;" class="dark:border-slate-800">
            <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;" class="dark:text-slate-400">
                Pilih Nuansa Dark Mode
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 2px;">
            <template x-for="t in themes" :key="t.id">
                <button type="button" 
                        @click="applyTheme(t.id, true)"
                        style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 8px 10px; border-radius: 9px; border: none; background: transparent; cursor: pointer; text-align: left; transition: all 0.15s ease;"
                        :style="currentTheme === t.id ? 'background: rgba(59, 130, 246, 0.1); font-weight: 700;' : ''"
                        class="hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 18px; height: 18px; border-radius: 6px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"
                             :style="'background: ' + t.color + '; border: 1.5px solid ' + t.border">
                        </div>
                        <div>
                            <div style="font-size: 0.82rem; line-height: 1.2;" x-text="t.name"></div>
                            <div style="font-size: 0.68rem; color: #94a3b8;" x-text="t.desc"></div>
                        </div>
                    </div>
                    <template x-if="currentTheme === t.id">
                        <i class="fa-solid fa-check" style="color: #3b82f6; font-size: 0.75rem;"></i>
                    </template>
                </button>
            </template>
        </div>
    </div>
</div>
