<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    /**
     * Parse and extract coordinates (latitude and longitude) from Google Maps link or text.
     *
     * @param string|null $input
     * @return array{latitude: float|null, longitude: float|null, raw_url: string|null, resolved_url: string|null, success: bool, message: string}
     */
    public static function parseCoordinates(?string $input): array
    {
        if (empty($input)) {
            return [
                'latitude' => null,
                'longitude' => null,
                'raw_url' => null,
                'resolved_url' => null,
                'success' => false,
                'message' => 'Input kosong. Silakan masukkan link atau koordinat Google Maps.'
            ];
        }

        $input = trim($input);

        // 1. Direct coordinate check: e.g. "-6.2087634, 106.845599" or "-6.2087634,106.845599"
        if (preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $input, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => "https://www.google.com/maps?q={$lat},{$lng}",
                    'success' => true,
                    'message' => 'Koordinat berhasil diekstrak secara langsung.'
                ];
            }
        }

        // 1b. Indonesian direct coordinate check with comma decimals: e.g. "-7,306806, 112,6566062" or "-7,306806; 112,6566062"
        if (preg_match('/^\s*(-?\d{1,2},\d+)\s*[,;\s]\s*(-?\d{1,3},\d+)\s*$/', $input, $matches)) {
            $lat = (float) str_replace(',', '.', $matches[1]);
            $lng = (float) str_replace(',', '.', $matches[2]);
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => "https://www.google.com/maps?q={$lat},{$lng}",
                    'success' => true,
                    'message' => 'Koordinat format lokal berhasil diekstrak secara langsung.'
                ];
            }
        }

        // 2. If it's a URL
        $targetUrl = $input;
        $htmlBody = null;

        // If short link or needs redirect resolution
        if (str_contains($input, 'maps.app.goo.gl') || str_contains($input, 'goo.gl') || str_contains($input, 'page.link') || str_contains($input, 'google.com/maps')) {
            try {
                // Follow redirects to get final URL and HTML body
                $response = Http::timeout(10)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])
                    ->get($input);

                $effectiveUri = (string) $response->effectiveUri();
                if (!empty($effectiveUri)) {
                    $targetUrl = $effectiveUri;
                }
                $htmlBody = $response->body();
            } catch (\Throwable $e) {
                Log::warning('Failed to resolve Google Maps URL: ' . $e->getMessage());
            }
        }

        // 3. Extract coordinates from target URL via regex patterns
        // Pattern A: @lat,lng,zoom e.g. @-6.2087634,106.845599,17z
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $targetUrl, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => $targetUrl,
                    'success' => true,
                    'message' => 'Koordinat berhasil diekstrak dari parameter URL (@lat,lng).'
                ];
            }
        }

        // Pattern B: q=lat,lng or query=lat,lng or ll=lat,lng or destination=lat,lng or daddr=lat,lng
        if (preg_match('/(?:q|query|ll|destination|daddr|saddr|center)=(-?\d+\.\d+)(?:%2C|,)(-?\d+\.\d+)/i', $targetUrl, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => $targetUrl,
                    'success' => true,
                    'message' => 'Koordinat berhasil diekstrak dari query parameter URL.'
                ];
            }
        }

        // Pattern C1: !3d(lat)!4d(lng) in Google Maps place URLs
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $targetUrl, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => $targetUrl,
                    'success' => true,
                    'message' => 'Koordinat berhasil diekstrak dari format Place Google Maps (!3d,!4d).'
                ];
            }
        }

        // Pattern C2: !2d(lng)!3d(lat) or %212d(lng)%213d(lat) in Google Maps URL (lng then lat)
        if (preg_match('/(?:!2d|%212d)(-?\d+\.\d+)(?:!3d|%213d)(-?\d+\.\d+)/', $targetUrl, $matches)) {
            $lng = (float) $matches[1];
            $lat = (float) $matches[2];
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => $targetUrl,
                    'success' => true,
                    'message' => 'Koordinat berhasil diekstrak dari parameter peta Google Maps (!2d,!3d).'
                ];
            }
        }

        // 4. If URL didn't have coordinates, inspect HTML body metadata & scripts
        if ($htmlBody) {
            // Pattern D1: staticmap image center/markers/ll in meta tag (og:image / itemprop="image")
            if (preg_match('/staticmap\?[^"\'<>]*?(?:center|markers|ll)(?:%3D|=|\\\\u003d)(-?\d+\.\d+)(?:%2C|,)(-?\d+\.\d+)/i', $htmlBody, $bodyMatch)) {
                $lat = (float) $bodyMatch[1];
                $lng = (float) $bodyMatch[2];
                if (self::isValidCoordinate($lat, $lng)) {
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'raw_url' => $input,
                        'resolved_url' => $targetUrl,
                        'success' => true,
                        'message' => 'Koordinat berhasil diekstrak dari peta pratinjau Google Maps.'
                    ];
                }
            }

            // Pattern D2: Protobuf coordinates in HTML body: !2d(lng)!3d(lat) or %212d(lng)%213d(lat)
            if (preg_match('/(?:!2d|%212d)(-?\d+\.\d+)(?:!3d|%213d)(-?\d+\.\d+)/', $htmlBody, $bodyMatch)) {
                $lng = (float) $bodyMatch[1];
                $lat = (float) $bodyMatch[2];
                if (self::isValidCoordinate($lat, $lng)) {
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'raw_url' => $input,
                        'resolved_url' => $targetUrl,
                        'success' => true,
                        'message' => 'Koordinat berhasil diekstrak dari data lokasi Google Maps.'
                    ];
                }
            }

            // Pattern D3: APP_INITIALIZATION_STATE = [[[zoom, lng, lat]
            if (preg_match('/APP_INITIALIZATION_STATE\s*=\s*\[\[\[[\d.]+,\s*(-?\d{1,3}\.\d{4,})\s*,\s*(-?\d{1,2}\.\d{4,})/', $htmlBody, $bodyMatch)) {
                $lng = (float) $bodyMatch[1];
                $lat = (float) $bodyMatch[2];
                if (self::isValidCoordinate($lat, $lng)) {
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'raw_url' => $input,
                        'resolved_url' => $targetUrl,
                        'success' => true,
                        'message' => 'Koordinat berhasil diekstrak dari inisialisasi Google Maps.'
                    ];
                }
            }

            // Pattern D4: Legacy itemprop="latitude" and itemprop="longitude"
            if (preg_match('/itemprop="latitude" content="(-?\d+\.\d+)"/', $htmlBody, $bodyLat) &&
                preg_match('/itemprop="longitude" content="(-?\d+\.\d+)"/', $htmlBody, $bodyLng)) {
                $lat = (float) $bodyLat[1];
                $lng = (float) $bodyLng[1];
                if (self::isValidCoordinate($lat, $lng)) {
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'raw_url' => $input,
                        'resolved_url' => $targetUrl,
                        'success' => true,
                        'message' => 'Koordinat berhasil diekstrak dari metadata Google Maps.'
                    ];
                }
            }
        }

        // Pattern E: Any comma separated coordinates embedded anywhere in URL or string
        if (preg_match('/(-?\d{1,2}\.\d{4,}),\s*(-?\d{1,3}\.\d{4,})/', $targetUrl, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if (self::isValidCoordinate($lat, $lng)) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'raw_url' => $input,
                    'resolved_url' => $targetUrl,
                    'success' => true,
                    'message' => 'Koordinat berhasil dideteksi dari teks link.'
                ];
            }
        }

        return [
            'latitude' => null,
            'longitude' => null,
            'raw_url' => $input,
            'resolved_url' => $targetUrl,
            'success' => false,
            'message' => 'Titik koordinat tidak dapat ditemukan secara otomatis dari link tersebut. Pastikan link Google Maps benar atau gunakan tombol "Gunakan Titik Koordinat Lokasi Saya Sekarang".'
        ];
    }

    /**
     * Check if latitude & longitude values are in valid geographic range.
     */
    public static function isValidCoordinate(float $lat, float $lng): bool
    {
        return ($lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0 && !($lat == 0.0 && $lng == 0.0));
    }
}
