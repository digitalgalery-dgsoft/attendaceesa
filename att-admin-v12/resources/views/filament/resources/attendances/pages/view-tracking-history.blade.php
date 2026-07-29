<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Riwayat Rute: {{ $employeeName }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tanggal: {{ $record->attendance_date }}
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
    </div>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trackingData = @json($trackingHistories);
            
            if (trackingData.length > 0) {
                // Initialize the map, set view to the first point
                const map = L.map('map').setView([trackingData[0].latitude, trackingData[0].longitude], 15);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Prepare latlngs for polyline
                const latlngs = trackingData.map(point => [parseFloat(point.latitude), parseFloat(point.longitude)]);

                // Create polyline and add to map
                const polyline = L.polyline(latlngs, {color: 'blue', weight: 4, opacity: 0.7}).addTo(map);

                // Add start marker
                L.marker(latlngs[0]).addTo(map)
                    .bindPopup('Mulai (Check-in)')
                    .openPopup();
                
                // Add end marker if there's more than one point
                if (latlngs.length > 1) {
                    L.marker(latlngs[latlngs.length - 1]).addTo(map)
                        .bindPopup('Lokasi Terakhir');
                }

                // Zoom map to fit polyline
                map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
            } else {
                // Default view if no data (e.g. Jakarta)
                const map = L.map('map').setView([-6.2088, 106.8456], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);
            }
        });
    </script>
</x-filament-panels::page>
