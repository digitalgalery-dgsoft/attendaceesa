<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationRequest;
use App\Services\GoogleMapsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationRequestApiController extends Controller
{
    /**
     * Submit a new location request from employee.
     */
    public function store(Request $request): JsonResponse
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'type' => 'nullable|string|in:store,client,office,project,warehouse,other',
            'address' => 'nullable|string|max:500',
            'maps_url' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meter' => 'nullable|integer|min:10|max:5000',
            'photo' => 'nullable|image|max:10240',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $longitude = $request->filled('longitude') ? (float) $request->longitude : null;
        $mapsUrl = $request->input('maps_url');

        // If coordinates missing, attempt auto-extraction from maps_url
        if ((empty($latitude) || empty($longitude)) && !empty($mapsUrl)) {
            $parsed = GoogleMapsService::parseCoordinates($mapsUrl);
            if ($parsed['success']) {
                $latitude = $parsed['latitude'];
                $longitude = $parsed['longitude'];
            }
        }

        if (empty($latitude) || empty($longitude)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Titik koordinat (Latitude & Longitude) tidak ditemukan. Pastikan memasukkan Link Google Maps yang valid atau tentukan koordinat lokasi.',
                'instructions' => [
                    'title' => 'Cara Mendapatkan Link Google Maps',
                    'steps' => [
                        '1. Buka aplikasi Google Maps di HP Anda.',
                        '2. Cari atau tentukan titik lokasi toko / kantor yang akan didaftarkan.',
                        '3. Tekan tombol Bagikan (Share).',
                        '4. Pilih Salin Link (Copy Link).',
                        '5. Tempelkan link tersebut ke kolom Link Google Maps di aplikasi ini.',
                    ]
                ]
            ], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('location_requests', 'public');
        }

        $locationRequest = LocationRequest::create([
            'employee_id' => $employee->id,
            'principal_id' => $employee->principal_id,
            'branch_id' => $employee->branch_id,
            'company_id' => $employee->company_id,
            'name' => $request->name,
            'type' => $request->input('type', 'store'),
            'address' => $request->address,
            'maps_url' => $mapsUrl,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_meter' => (int) $request->input('radius_meter', 100),
            'photo_path' => $photoPath,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lokasi baru berhasil dikirim dan menunggu persetujuan Administrator.',
            'data' => [
                'id' => $locationRequest->id,
                'name' => $locationRequest->name,
                'status' => $locationRequest->status,
                'latitude' => $locationRequest->latitude,
                'longitude' => $locationRequest->longitude,
                'radius_meter' => $locationRequest->radius_meter,
                'photo_url' => $locationRequest->photo_path ? asset('storage/' . $locationRequest->photo_path) : null,
                'created_at' => $locationRequest->created_at->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * List location requests submitted by the authenticated employee.
     */
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user();
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $requests = LocationRequest::where('employee_id', $employee->id)
            ->with(['principal:id,name', 'branch:id,name', 'workLocation:id,name,address,latitude,longitude,radius_meter'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        $formatted = $requests->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'address' => $item->address,
                'maps_url' => $item->maps_url,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'radius_meter' => $item->radius_meter,
                'photo_url' => $item->photo_path ? asset('storage/' . $item->photo_path) : null,
                'notes' => $item->notes,
                'status' => $item->status,
                'admin_notes' => $item->admin_notes,
                'approved_at' => $item->approved_at ? $item->approved_at->toIso8601String() : null,
                'work_location' => $item->workLocation,
                'created_at' => $item->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted,
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
            'total' => $requests->total(),
            'instructions' => [
                'title' => 'Petunjuk Pengajuan Lokasi Google Maps',
                'steps' => [
                    '1. Buka aplikasi Google Maps pada HP Anda.',
                    '2. Cari nama toko atau tahan jari pada titik koordinat lokasi di peta.',
                    '3. Pilih menu "Bagikan" (Share) lalu tekan "Salin Link" (Copy Link).',
                    '4. Tempelkan link tersebut pada form pengajuan lokasi.',
                ]
            ]
        ]);
    }

    /**
     * Parse Google Maps URL / Share link to coordinates.
     */
    public function parseMapsUrl(Request $request): JsonResponse
    {
        $url = $request->input('url') ?? $request->input('maps_url');
        if (empty($url)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter url atau maps_url wajib diisi.'
            ], 422);
        }

        $result = GoogleMapsService::parseCoordinates($url);

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'data' => [
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
                'resolved_url' => $result['resolved_url'],
            ],
            'instructions' => [
                'title' => 'Cara Mendapatkan Link Google Maps',
                'steps' => [
                    '1. Buka aplikasi Google Maps pada HP Anda.',
                    '2. Cari titik lokasi toko/kantor.',
                    '3. Klik tombol Bagikan (Share).',
                    '4. Pilih Salin Link (Copy link).',
                    '5. Tempelkan link di aplikasi.',
                ]
            ]
        ], $result['success'] ? 200 : 422);
    }
}
