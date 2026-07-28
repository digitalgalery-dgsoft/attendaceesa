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
        $today = now()->toDateString();

        $blastInfos = BlastInfo::where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(function ($query) use ($employee) {
                $query->where('target_type', 'all');
                if ($employee) {
                    $query->orWhere(function ($q) use ($employee) {
                        $q->where('target_type', 'department')
                          ->where('department_id', $employee->department_id);
                    });
                }
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $blastInfos
        ]);
    }
}
