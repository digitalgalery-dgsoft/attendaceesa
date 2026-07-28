<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SalesPipeline;
use Illuminate\Support\Facades\Validator;

class SalesPipelineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found for this user',
            ], 404);
        }

        $pipelines = SalesPipeline::where('employee_id', $employee->id)
            ->with(['salesReport:id,title,report_date'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pipelines,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee record not found for this user',
            ], 404);
        }

        $pipeline = SalesPipeline::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$pipeline) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pipeline record not found or unauthorized',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'stage' => 'sometimes|string|in:prospecting,negotiation,closed_won,closed_lost',
            'expected_revenue' => 'sometimes|numeric',
            'probability' => 'sometimes|numeric',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $pipeline->fill($validator->validated());
        
        // Auto update probability based on stage
        if ($request->has('stage')) {
            if ($request->stage === 'closed_won') {
                $pipeline->probability = 100;
            } elseif ($request->stage === 'closed_lost') {
                $pipeline->probability = 0;
            }
        }
        
        $pipeline->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Sales pipeline updated successfully',
            'data' => $pipeline,
        ]);
    }
}
