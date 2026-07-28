<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesReport;
use App\Models\SalesPipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user();
        
        $reports = SalesReport::where('employee_id', $employee->id)
            ->with('attendanceLog')
            ->orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user();

        $request->validate([
            'client_name' => 'required|string',
            'client_company' => 'nullable|string',
            'revenue' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'status' => 'required|string',
            'location' => 'nullable|string',
            'receipt_image' => 'nullable|image|max:5120',
            // Pipeline specific
            'create_pipeline' => 'nullable|boolean',
            'stage' => 'nullable|string',
            'expected_revenue' => 'nullable|numeric',
            'probability' => 'nullable|numeric|min:0|max:100',
            'expected_close_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $report = new SalesReport();
            $report->employee_id = $employee->id;
            $report->client_name = $request->client_name;
            $report->client_company = $request->client_company;
            $report->revenue = $request->revenue ?? 0;
            $report->notes = $request->notes;
            $report->report_date = now()->toDateString();
            $report->status = $request->status;
            $report->location = $request->location;

            // Optional: link to today's active attendance log
            $todayLog = \App\Models\AttendanceLog::where('employee_id', $employee->id)
                ->whereDate('logged_at', now()->toDateString())
                ->first();
            if ($todayLog) {
                $report->attendance_log_id = $todayLog->id;
            }

            if ($request->hasFile('receipt_image')) {
                $path = $request->file('receipt_image')->store('sales_receipts', 'public');
                $report->receipt_image = $path;
            }

            $report->save();

            // Create pipeline if requested
            if ($request->create_pipeline) {
                $pipeline = new SalesPipeline();
                $pipeline->sales_report_id = $report->id;
                $pipeline->employee_id = $employee->id;
                $pipeline->lead_name = $request->client_name;
                $pipeline->lead_company = $request->client_company;
                $pipeline->stage = $request->stage ?? 'prospecting';
                $pipeline->expected_revenue = $request->expected_revenue ?? 0;
                $pipeline->probability = $request->probability ?? 0;
                $pipeline->expected_close_date = $request->expected_close_date;
                $pipeline->notes = $request->notes;
                $pipeline->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan penjualan berhasil disimpan.',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $employee = $request->user();
        
        $report = SalesReport::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Laporan tidak ditemukan atau Anda tidak memiliki akses.'
            ], 404);
        }

        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $report->status = $request->status;
            if ($request->has('notes')) {
                // Append notes or replace
                // Here we just replace or we can append. Let's just replace as per standard form.
                $report->notes = $request->notes;
            }
            $report->save();
            
            // Also update pipeline if exists
            $pipeline = SalesPipeline::where('sales_report_id', $report->id)->first();
            if ($pipeline) {
                // If status is Deal, update pipeline stage to closed_won
                if ($request->status === 'Deal') {
                    $pipeline->stage = 'closed_won';
                    $pipeline->probability = 100;
                } else if ($request->status === 'Lost') {
                    $pipeline->stage = 'closed_lost';
                    $pipeline->probability = 0;
                } else {
                    $pipeline->stage = 'negotiation';
                }
                $pipeline->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Status laporan berhasil diperbarui.',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function analyze(Request $request, $id)
    {
        $employee = $request->user();
        
        $report = SalesReport::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$report) {
            return response()->json([
                'status' => 'error',
                'message' => 'Laporan tidak ditemukan atau Anda tidak memiliki akses.'
            ], 404);
        }

        $analysis = \App\Services\AIService::generateSalesAnalysis($report);

        return response()->json([
            'status' => 'success',
            'data' => [
                'analysis' => $analysis
            ]
        ]);
    }
}
