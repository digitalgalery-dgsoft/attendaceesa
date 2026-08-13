<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Riwayat Rute: {{ $employeeName }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tanggal: {{ $record->attendance_date }}
                    &nbsp;·&nbsp;
                    <span class="font-semibold text-primary-600 dark:text-primary-400">
                        {{ count($trackingHistories) }} titik lokasi
                    </span>
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
            <div id="map" style="height: 500px; width: 100%; z-index: 1;" class="rounded-lg"></div>
            @if(empty($trackingHistories))
                <div class="mt-4 text-center text-sm text-gray-500">
                    Tidak ada data riwayat lokasi untuk absensi ini.
                </div>
            @endif
        </div>

        {{-- Tabel titik tracking --}}
        @if(!empty($trackingHistories))
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Latitude</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Longitude</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($trackingHistories as $i => $point)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer tracking-row"
                        data-lat="{{ $point['latitude'] }}"
                        data-lng="{{ $point['longitude'] }}"
                        data-index="{{ $i }}">
                        <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ number_format($point['latitude'], 6) }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ number_format($point['longitude'], 6) }}</td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $point['created_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- CRITICAL: Use Canvas renderer instead of SVG to bypass Tailwind global SVG reset --}}
    {{-- Tailwind's preflight CSS injects styles that override SVG path attributes (fill, stroke)  --}}
    {{-- causing Leaflet's polylines to become invisible. Canvas drawing is immune to CSS. --}}
    <style>
        /* Prevent Tailwind from nuking Leaflet SVG elements */
        #map svg,
        #map svg *,
        #map path,
        #map .leaflet-pane svg path,
        .leaflet-overlay-pane svg path,
        .leaflet-overlay-pane svg polyline {
            fill: inherit !important;
            stroke: inherit !important;
            stroke-width: inherit !important;
        }
        /* The actual canvas drawn polyline is styled via JS options, not CSS.
           These styles are a fallback for any residual SVG overlays. */
        #map { isolation: isolate; }
    </style>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trackingData = @json($trackingHistories);
            let map, markers = [];

            if (trackingData.length > 0) {
                map = L.map('map').setView([trackingData[0].latitude, trackingData[0].longitude], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                const latlngs = trackingData.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);

                // ── KEY FIX: use Canvas renderer ──────────────────────────────────────────
                // Leaflet by default uses SVG, which Tailwind's preflight CSS breaks by
                // resetting fill/stroke on all SVG elements. The Canvas renderer draws
                // directly onto an HTML <canvas> element, which is 100% immune to CSS.
                const canvasRenderer = L.canvas({ padding: 0.5 });

                // Draw route polyline using Canvas renderer
                const polyline = L.polyline(latlngs, {
                    renderer: canvasRenderer,
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.85,
                    lineJoin: 'round',
                    lineCap: 'round',
                }).addTo(map);

                // Draw intermediate dots using Canvas renderer
                trackingData.forEach((point, index) => {
                    const lat = parseFloat(point.latitude);
                    const lng = parseFloat(point.longitude);
                    let marker;

                    if (index === 0) {
                        // First point: standard marker (not SVG path, so safe)
                        marker = L.marker([lat, lng])
                            .addTo(map)
                            .bindPopup(`<b>Titik Awal</b><br>${point.created_at}`);
                    } else if (index === latlngs.length - 1) {
                        // Last point: standard marker
                        marker = L.marker([lat, lng])
                            .addTo(map)
                            .bindPopup(`<b>Titik Akhir</b><br>${point.created_at}`);
                    } else {
                        // Intermediate: canvas circle marker
                        marker = L.circleMarker([lat, lng], {
                            renderer: canvasRenderer,
                            radius: 4,
                            color: '#1d4ed8',
                            weight: 1.5,
                            fillColor: '#3b82f6',
                            fillOpacity: 0.85,
                        }).addTo(map).bindPopup(`<b>Titik ${index + 1}</b><br>${point.created_at}`);
                    }
                    markers.push(marker);
                });

                // Fit map to show entire route
                map.fitBounds(polyline.getBounds(), { padding: [40, 40] });

                // Click on table row → jump to that point on the map
                document.querySelectorAll('.tracking-row').forEach(row => {
                    row.addEventListener('click', function () {
                        const lat = parseFloat(this.dataset.lat);
                        const lng = parseFloat(this.dataset.lng);
                        const idx = parseInt(this.dataset.index);
                        map.setView([lat, lng], 18);
                        if (markers[idx]) markers[idx].openPopup();
                    });
                });

            } else {
                // No data — default Surabaya view
                map = L.map('map').setView([-7.2575, 112.7521], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
            }
        });
    </script>
</x-filament-panels::page>
