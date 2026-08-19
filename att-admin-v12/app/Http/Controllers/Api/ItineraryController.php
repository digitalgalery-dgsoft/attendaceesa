<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItineraryController extends Controller
{
    /**
     * Get itineraries for the logged in user
     */
    public function index(Request $request)
    {
        $employee = $request->user();
        
        $startDate = Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString();
        $endDate = Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString();
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
        }

        $itineraries = Itinerary::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['items' => function($q) {
                $q->orderBy('sequence');
            }, 'items.workLocation'])
            ->orderBy('date', 'asc')
            ->get();

        // Get visited locations
        $visitedLocationIds = \App\Models\AttendanceLog::where('employee_id', $employee->id)
            ->where('log_type', 'visit_in')
            ->whereBetween('logged_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->map(function ($log) {
                $meta = is_array($log->metadata) ? $log->metadata : (is_string($log->metadata) ? json_decode($log->metadata, true) : []);
                return is_array($meta) ? ($meta['visit_location_id'] ?? null) : null;
            })
            ->filter()
            ->unique()
            ->toArray();

        $itineraries->each(function($itinerary) use ($visitedLocationIds) {
            $itinerary->items->each(function($item) use ($visitedLocationIds) {
                $item->is_visited = in_array((int)$item->work_location_id, $visitedLocationIds);
            });
        });

        return response()->json([
            'status' => 'success',
            'data' => $itineraries
        ]);
    }

    public function availableWorkLocations(Request $request)
    {
        $employee = $request->user();
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        // Ambil ID lokasi yang sudah di-visit hari ini
        $visitedLocationIds = \App\Models\AttendanceLog::where('employee_id', $employee->id)
            ->where('log_type', 'visit_in')
            ->whereDate('logged_at', $today)
            ->get()
            ->map(function ($log) {
                $meta = is_array($log->metadata) ? $log->metadata : (is_string($log->metadata) ? json_decode($log->metadata, true) : []);
                $id = is_array($meta) ? ($meta['visit_location_id'] ?? null) : null;
                return $id ? (int) $id : null;
            })
            ->filter()
            ->unique()
            ->toArray();
        
        $locations = WorkLocation::with('branch')
            ->where('is_active', true)
            ->whereNotIn('id', $visitedLocationIds)
            ->orderBy('name')
            ->get()
            ->map(function ($loc) {
                $data = $loc->toArray();
                $data['area'] = $loc->branch ? $loc->branch->name : null;
                return $data;
            });
            
        return response()->json([
            'status' => 'success',
            'data' => $locations
        ]);
    }

    /**
     * Store new itinerary
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'locations' => 'required|array',
            'locations.*.work_location_id' => 'required|integer|exists:work_locations,id',
            'locations.*.principal_id' => 'nullable|integer|exists:principals,id',
            'locations.*.notes' => 'nullable|string',
            'locations.*.visit_type' => 'nullable|string',
            'locations.*.meeting_type' => 'nullable|string',
            'locations.*.agenda' => 'nullable|string'
        ]);

        $employee = $request->user();
        $date = $request->input('date');

        try {
            DB::beginTransaction();

            // Check if itinerary already exists for this date, if so append items
            $itinerary = Itinerary::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();
                
            if (!$itinerary) {
                // Create new itinerary
                $itinerary = Itinerary::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => 'approved', // Auto approved for mobile user creation
                    'notes' => 'Created via Mobile App'
                ]);
                $sequence = 1;
            } else {
                $sequence = $itinerary->items()->max('sequence') + 1;
            }

            // Create items
            foreach ($request->input('locations') as $loc) {
                ItineraryItem::create([
                    'itinerary_id' => $itinerary->id,
                    'work_location_id' => $loc['work_location_id'],
                    'principal_id' => $loc['principal_id'] ?? null,
                    'sequence' => $sequence++,
                    'notes' => $loc['notes'] ?? null,
                    'visit_type' => $loc['visit_type'] ?? null,
                    'meeting_type' => $loc['meeting_type'] ?? null,
                    'agenda' => $loc['agenda'] ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Itinerary created successfully',
                'data' => $itinerary->load('items.workLocation')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating itinerary via API: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create itinerary'
            ], 500);
        }
    }

    public function getPrincipals()
    {
        $principals = \App\Models\Principal::select('id', 'name', 'code')->orderBy('name')->get();
        return response()->json([
            'status' => 'success',
            'data' => $principals
        ]);
    }

    public function destroy($id, Request $request)
    {
        $employee = $request->user();
        $item = ItineraryItem::where('id', $id)
            ->whereHas('itinerary', function($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            })
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan atau tidak berhak dihapus'], 404);
        }
        
        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Jadwal berhasil dihapus'
        ]);
    }
}
