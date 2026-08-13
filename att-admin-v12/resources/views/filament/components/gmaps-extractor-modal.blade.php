{{-- Google Maps Coordinate Extractor Modal Content --}}
{{-- This is injected as modalContent() inside the Filament Action modal --}}

<div
    x-data="{
        url: '',
        lat: null,
        lng: null,
        error: '',
        extracting: false,

        patterns: [
            // /@lat,lng   (most common)
            /@(-?\d+\.?\d+),(-?\d+\.?\d+)/,
            // !3dLAT!4dLNG  (embed/short links)
            /!3d(-?\d+\.?\d+).*?!4d(-?\d+\.?\d+)/,
            // ?q=lat,lng
            /[?&]q=(-?\d+\.?\d+),(-?\d+\.?\d+)/,
            // ll=lat,lng
            /[?&]ll=(-?\d+\.?\d+),(-?\d+\.?\d+)/,
            // /maps/place/.../@lat,lng
            /place\/[^@]+@(-?\d+\.?\d+),(-?\d+\.?\d+)/,
            // mlat=...&mlon=...
            /mlat=(-?\d+\.?\d+).*?mlon=(-?\d+\.?\d+)/,
        ],

        extract() {
            this.lat = null;
            this.lng = null;
            this.error = '';

            if (!this.url.trim()) {
                this.error = 'Masukkan URL terlebih dahulu.';
                return;
            }

            // Jika link pendek, biarkan server yang proses
            if (this.url.includes('goo.gl') || this.url.includes('maps.app.goo.gl')) {
                this.error = 'Mendeteksi link pendek. Silakan langsung klik tombol "Gunakan Titik" di bagian bawah layar untuk memproses link ini secara otomatis oleh sistem.';
                return;
            }

            let matched = false;
            for (const pattern of this.patterns) {
                const m = this.url.match(pattern);
                if (m) {
                    this.lat = parseFloat(m[1]);
                    this.lng = parseFloat(m[2]);
                    matched = true;
                    break;
                }
            }

            if (!matched) {
                this.error = 'Koordinat tidak ditemukan. Pastikan URL valid, atau jika ini link khusus, klik "Gunakan Titik" agar diproses oleh sistem.';
            } else {
                // Sync ke hidden Filament fields agar bisa dibaca sisi server
                document.querySelectorAll('input[type=\"hidden\"]').forEach(el => {
                    if (el.id && el.id.includes('extracted_lat')) {
                        el.value = this.lat;
                        el.dispatchEvent(new Event('input'));
                    }
                    if (el.id && el.id.includes('extracted_lng')) {
                        el.value = this.lng;
                        el.dispatchEvent(new Event('input'));
                    }
                });
            }
        },

        applyCoords() {
            // Kita sudah menggunakan Modal Submit button bawaan Filament. 
            // Jadi fungsi ini mungkin tidak digunakan lagi (karena Action action() sudah di-handle oleh ModalSubmitAction).
            // Tapi biarkan saja untuk berjaga-jaga jika dipanggil manual.
        }
    }"
    class="space-y-4 pb-2"
>
    {{-- Header card --}}
    <div class="rounded-xl overflow-hidden" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 1.5rem; text-align: center; color: white;">
        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📍</div>
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem 0;">Ekstrak Koordinat Maps</h2>
        <p style="font-size: 0.8rem; opacity: 0.85; margin: 0;">Dapatkan titik Latitude dan Longitude dari URL Google Maps</p>
    </div>

    {{-- URL Input --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
            Masukkan Tautan Google Maps
        </label>
        <div class="flex gap-2">
            <input
                x-model="url"
                type="text"
                placeholder="https://www.google.com/maps/place/..."
                class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                @keyup.enter="extract()"
            />
            <button
                @click="extract()"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                Ekstrak
            </button>
        </div>
    </div>

    {{-- Warning note --}}
    <div class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 px-3 py-2.5 flex gap-2 text-xs text-amber-800 dark:text-amber-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/>
        </svg>
        <span>
            <strong>Catatan:</strong> Jika Anda menggunakan tautan pendek (misal: <em>maps.app.goo.gl</em>), buka terlebih dahulu di browser sampai memuat ulang, lalu salin URL panjang yang muncul di kotak pencarian browser.
        </span>
    </div>

    {{-- Error --}}
    <template x-if="error">
        <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-3 py-2.5 text-sm text-red-700 dark:text-red-300 flex gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span x-text="error"></span>
        </div>
    </template>

    {{-- Results --}}
    <template x-if="lat !== null && lng !== null">
        <div class="space-y-3">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Hasil Ekstraksi Koordinat</p>

            {{-- Lat / Lng cards --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Latitude (Lintang)</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-gray-100" x-text="lat"></p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Longitude (Bujur)</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-gray-100" x-text="lng"></p>
                </div>
            </div>

            {{-- Full format + Gunakan Titik button --}}
            <div class="rounded-xl" style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem;">
                <p class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-1">Format Lengkap (Lat, Lng)</p>
                <p class="text-lg font-bold text-blue-700" x-text="`${lat}, ${lng}`"></p>
            </div>
        </div>
    </template>
</div>
