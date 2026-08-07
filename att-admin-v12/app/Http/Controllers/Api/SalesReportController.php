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
            'store_name' => 'required|string',
            'oos_status' => 'nullable|string',
            'oos_notes' => 'nullable|string',
            'plano_status' => 'nullable|string',
            'plano_notes' => 'nullable|string',
            'promo_status' => 'nullable|string',
            'promo_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
            'location' => 'nullable|string',
            'photo_oos' => 'nullable|image|max:5120',
            'photo_plano' => 'nullable|image|max:5120',
            'photo_promo' => 'nullable|image|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $report = new SalesReport();
            $report->employee_id = $employee->id;
            $report->store_name = $request->store_name;
            $report->oos_status = $request->oos_status;
            $report->oos_notes = $request->oos_notes;
            $report->plano_status = $request->plano_status;
            $report->plano_notes = $request->plano_notes;
            $report->promo_status = $request->promo_status;
            $report->promo_notes = $request->promo_notes;
            $report->notes = $request->notes;
            $report->report_date = now()->toDateString();
            $report->status = $request->status ?? 'submitted';
            $report->location = $request->location;

            // Optional: link to today's active attendance log
            $todayLog = \App\Models\AttendanceLog::where('employee_id', $employee->id)
                ->whereDate('logged_at', now()->toDateString())
                ->first();
            if ($todayLog) {
                $report->attendance_log_id = $todayLog->id;
            }

            if ($request->hasFile('photo_oos')) {
                $report->photo_oos = $request->file('photo_oos')->store('sales_reports', 'public');
            }
            if ($request->hasFile('photo_plano')) {
                $report->photo_plano = $request->file('photo_plano')->store('sales_reports', 'public');
            }
            if ($request->hasFile('photo_promo')) {
                $report->photo_promo = $request->file('photo_promo')->store('sales_reports', 'public');
            }

            $report->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan toko berhasil disimpan.',
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
            $report->status = $request->status;
            if ($request->has('notes')) {
                $report->notes = $request->notes;
            }
            $report->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Status laporan berhasil diperbarui.',
                'data' => $report
            ]);
        } catch (\Exception $e) {
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
