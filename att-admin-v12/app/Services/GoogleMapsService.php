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

        // 2. If it's a URL
        $targetUrl = $input;

        // If short link or needs redirect resolution
        if (str_contains($input, 'maps.app.goo.gl') || str_contains($input, 'goo.gl/maps') || str_contains($input, 'page.link')) {
            try {
                // Follow redirects to get final URL
                $response = Http::timeout(8)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])
                    ->get($input);

                $effectiveUri = (string) $response->effectiveUri();
                if (!empty($effectiveUri)) {
                    $targetUrl = $effectiveUri;
                }

                // Check HTML body for coordinates if meta tags or place data exist
                $body = $response->body();
                if (preg_match('/itemprop="latitude" content="(-?\d+\.\d+)"/', $body, $bodyLat) &&
                    preg_match('/itemprop="longitude" content="(-?\d+\.\d+)"/', $body, $bodyLng)) {
                    return [
                        'latitude' => (float) $bodyLat[1],
                        'longitude' => (float) $bodyLng[1],
                        'raw_url' => $input,
                        'resolved_url' => $targetUrl,
                        'success' => true,
                        'message' => 'Koordinat berhasil diekstrak dari metadata Google Maps.'
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to resolve Google Maps short URL: ' . $e->getMessage());
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

        // Pattern C: !3d(lat)!4d(lng) in Google Maps place URLs
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

        // Pattern D: Any comma separated coordinates embedded anywhere in URL or string
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
            'message' => 'Titik koordinat tidak dapat ditemukan secara otomatis dari link tersebut. Pastikan link Google Maps benar atau masukkan titik koordinat (lat, lng) secara manual.'
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
