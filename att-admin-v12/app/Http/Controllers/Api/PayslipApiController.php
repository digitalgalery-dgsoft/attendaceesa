<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payslip;
use Illuminate\Support\Facades\Storage;

class PayslipApiController extends Controller
{
    public function getPayslips(Request $request)
    {
        $employee = $request->user()->employee;
        
        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee data not found',
            ], 404);
        }

        $payslips = Payslip::where('employee_id', $employee->id)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $payslips->transform(function ($payslip) {
            return [
                'id' => $payslip->id,
                'month_year' => $payslip->month_year,
                'file_url' => asset('storage/' . $payslip->file_path),
                'created_at' => $payslip->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $payslips,
        ]);
    }
}
