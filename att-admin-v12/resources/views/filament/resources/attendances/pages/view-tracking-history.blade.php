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
            {{-- Map container: relative so canvas overlay positions correctly --}}
            <div id="map-wrapper" style="position: relative; height: 500px; width: 100%; border-radius: 8px; overflow: hidden;">
                <div id="map" style="position: absolute; inset: 0; z-index: 1;"></div>
                {{-- Custom canvas overlay — drawn by our own JS, 100% CSS-immune --}}
                <canvas id="route-canvas" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none;"></canvas>
            </div>
            @if(empty($trackingHistories))
                <div class="mt-4 text-center text-sm text-gray-500">
                    Tidak ada data riwayat lokasi untuk absensi ini.
                </div>
            @endif
        </div>

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

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const trackingData = @json($trackingHistories);
        let map;
        let markers = [];

        const canvas = document.getElementById('route-canvas');
        const ctx    = canvas.getContext('2d');

        // ─────────────────────────────────────────────────────────────────────
        // drawRoute — convert lat/lng to container pixels, draw on our own
        // <canvas> element with the 2D Context API.
        //
        // WHY: Both Leaflet's SVG & Canvas renderers are affected by Filament /
        // Tailwind CSS global resets in ways that are hard to fully override.
        // Drawing on a separate <canvas> we own is pure JavaScript pixel math —
        // zero CSS involvement, guaranteed to be visible.
        // ─────────────────────────────────────────────────────────────────────
        function drawRoute() {
            if (!map || trackingData.length < 2) return;

            // Match canvas buffer to its CSS display size
            const rect = canvas.getBoundingClientRect();
            canvas.width  = rect.width  || canvas.offsetWidth;
            canvas.height = rect.height || canvas.offsetHeight;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // ── Draw connecting line ──────────────────────────────────────
            ctx.beginPath();
            ctx.strokeStyle = '#2563eb';
            ctx.lineWidth   = 3.5;
            ctx.lineJoin    = 'round';
            ctx.lineCap     = 'round';
            ctx.globalAlpha = 0.9;

            trackingData.forEach(function(point, i) {
                const pt = map.latLngToContainerPoint([
                    parseFloat(point.latitude),
                    parseFloat(point.longitude)
                ]);
                if (i === 0) ctx.moveTo(pt.x, pt.y);
                else         ctx.lineTo(pt.x, pt.y);
            });
            ctx.stroke();

            // ── Draw intermediate dots ────────────────────────────────────
            trackingData.forEach(function(point, i) {
                if (i === 0 || i === trackingData.length - 1) return;

                const pt = map.latLngToContainerPoint([
                    parseFloat(point.latitude),
                    parseFloat(point.longitude)
                ]);

                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 4, 0, 2 * Math.PI);
                ctx.fillStyle   = '#3b82f6';
                ctx.globalAlpha = 0.85;
                ctx.fill();
                ctx.strokeStyle = '#1d4ed8';
                ctx.lineWidth   = 1.5;
                ctx.globalAlpha = 1;
                ctx.stroke();
            });

            ctx.globalAlpha = 1;
        }

        if (trackingData.length > 0) {
            // Initialise Leaflet — tiles + markers only (no polyline from Leaflet)
            map = L.map('map').setView(
                [parseFloat(trackingData[0].latitude), parseFloat(trackingData[0].longitude)],
                15
            );

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // First & last point markers (Leaflet <img>-based — unaffected by CSS)
            var first = trackingData[0];
            var last  = trackingData[trackingData.length - 1];

            var startMarker = L.marker([parseFloat(first.latitude), parseFloat(first.longitude)])
                .addTo(map)
                .bindPopup('<b>Titik Awal</b><br>' + first.created_at);

            var endMarker = L.marker([parseFloat(last.latitude), parseFloat(last.longitude)])
                .addTo(map)
                .bindPopup('<b>Titik Akhir</b><br>' + last.created_at);

            // Build markers array (nulls for intermediate; row-click still works)
            markers.push(startMarker);
            for (var j = 1; j < trackingData.length - 1; j++) markers.push(null);
            markers.push(endMarker);

            // Fit to full route bounds
            var latlngs = trackingData.map(function(p) {
                return [parseFloat(p.latitude), parseFloat(p.longitude)];
            });
            map.fitBounds(L.latLngBounds(latlngs), { padding: [40, 40] });

            // Redraw on every viewport change
            map.on('move zoom viewreset zoomstart zoomend moveend', drawRoute);

            // Initial draw with small delay so Leaflet finishes layout
            setTimeout(drawRoute, 300);

        } else {
            map = L.map('map').setView([-7.2575, 112.7521], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
        }

        // Click on table row → jump to that location on the map
        document.querySelectorAll('.tracking-row').forEach(function(row) {
            row.addEventListener('click', function () {
                var lat = parseFloat(this.dataset.lat);
                var lng = parseFloat(this.dataset.lng);
                var idx = parseInt(this.dataset.index);
                map.setView([lat, lng], 18);
                if (markers[idx]) markers[idx].openPopup();
                setTimeout(drawRoute, 300);
            });
        });

        // Redraw when window is resized
        window.addEventListener('resize', function() {
            setTimeout(drawRoute, 100);
        });
    });
    </script>
</x-filament-panels::page>


