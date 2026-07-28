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

        return response()->json([
            'status' => 'success',
            'data' => $itineraries
        ]);
    }

    /**
     * Get available work locations for the user (filtered by branch)
     */
    public function availableWorkLocations(Request $request)
    {
        $employee = $request->user();
        
        $locations = WorkLocation::where('branch_id', $employee->branch_id)
            ->orderBy('name')
            ->get();
            
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
            'locations.*.notes' => 'nullable|string'
        ]);

        $employee = $request->user();
        $date = $request->input('date');

        try {
            DB::beginTransaction();

            // Check if itinerary already exists for this date, if so delete to replace
            $existing = Itinerary::where('employee_id', $employee->id)
                ->where('date', $date)
                ->first();
                
            if ($existing) {
                $existing->items()->delete();
                $existing->delete();
            }

            // Create new itinerary
            $itinerary = Itinerary::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'status' => 'approved', // Auto approved for mobile user creation
                'notes' => 'Created via Mobile App'
            ]);

            // Create items
            $sequence = 1;
            foreach ($request->input('locations') as $loc) {
                ItineraryItem::create([
                    'itinerary_id' => $itinerary->id,
                    'work_location_id' => $loc['work_location_id'],
                    'sequence' => $sequence++,
                    'notes' => $loc['notes'] ?? null
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
}
