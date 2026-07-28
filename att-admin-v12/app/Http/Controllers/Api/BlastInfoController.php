<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlastInfo;
use Illuminate\Http\Request;

class BlastInfoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['data' => []]); // No employee data, no specific blast infos
        }

        $today = now()->toDateString();

        $blastInfos = BlastInfo::where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where(function ($query) use ($employee) {
                $query->where('target_type', 'all')
                      ->orWhere(function ($q) use ($employee) {
                          $q->where('target_type', 'department')
                            ->where('department_id', $employee->department_id);
                      });
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $blastInfos
        ]);
    }
}
