<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Principal;
use App\Models\ReportFormField;
use App\Models\ReportSubmission;
use App\Models\ReportSubmissionValue;
use App\Models\ReportTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportingApiController extends Controller
{
    /**
     * Helper to safely resolve authenticated Employee instance.
     */
    private function getAuthenticatedEmployee(Request $request): ?Employee
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($user instanceof Employee) {
            return $user;
        }

        return Employee::where('user_id', $user->id)
            ->orWhere('id', $user->employee_id ?? null)
            ->orWhere('email', $user->email ?? null)
            ->first();
    }

    /**
     * Get active report templates assigned to the authenticated employee's principal.
     */
    public function templates(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan untuk akun ini.',
            ], 404);
        }

        $principalId = $employee->principal_id;
        
        // Cari semua template yang ditugaskan ke prinsiple karyawan ini
        $templatesQuery = ReportTemplate::with([
            'fields' => function ($q) {
                $q->orderBy('order_index', 'asc');
            },
            'products' => function ($q) {
                $q->where('is_active', true)->orderBy('name', 'asc');
            },
        ])->where('is_active', true);

        if ($principalId) {
            // Cek apakah prinsiple memiliki subdomain bersama (misal: semua ICI Paints memiliki subdomain 'dulux')
            $principal = Principal::find($principalId);
            $allMatchingPrincipalIds = [$principalId];
            if ($principal && !empty($principal->subdomain)) {
                $allMatchingPrincipalIds = Principal::where('subdomain', $principal->subdomain)->pluck('id')->toArray();
            }

            $templatesQuery->where(function ($q) use ($allMatchingPrincipalIds, $principalId) {
                $q->whereHas('principals', function ($pq) use ($allMatchingPrincipalIds) {
                    $pq->whereIn('principals.id', $allMatchingPrincipalIds);
                })
                ->orWhereIn('principal_id', $allMatchingPrincipalIds)
                ->orWhere('principal_id', $principalId)
                ->orWhereNull('principal_id');
            });
        }

        $templates = $templatesQuery->orderBy('id', 'asc')->get();

        // Format data template dan fields untuk konsumsi mobile
        $formatted = $templates->map(function ($t) use ($employee) {
            $templateProducts = $t->products;
            $productNames = $templateProducts->pluck('name')->toArray();

            return [
                'id' => $t->id,
                'code' => $t->code,
                'title' => $t->title,
                'description' => $t->description,
                'category' => $t->category ?? 'general',
                'icon' => $t->icon ?? 'document-text',
                'color' => $t->color ?? '#0F52BA',
                'require_gps' => (bool) $t->require_gps,
                'require_photo' => (bool) $t->require_photo,
                'require_signature' => (bool) $t->require_signature,
                'fields_count' => $t->fields->count(),
                'products' => $templateProducts->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku_code' => $p->sku_code,
                        'category' => $p->category,
                        'brand' => $p->brand,
                        'price' => (float) ($p->price ?? 0),
                        'formatted_price' => $p->formatted_price,
                        'uom' => $p->uom ?? 'Pcs',
                    ];
                })->values(),
                'fields' => $t->fields->map(function ($f) use ($productNames, $templateProducts) {
                    $options = $f->options ?? [];

                    // Jika tipe product_select atau field adalah dropdown produk tanpa opsi manual
                    if ($f->field_type === 'product_select' || (in_array($f->field_type, ['dropdown', 'select', 'radio', 'checkbox']) && (empty($options) || Str::contains(strtolower($f->field_name), ['produk', 'product', 'sku'])))) {
                        if (!empty($productNames)) {
                            $options = $productNames;
                        }
                    }

                    return [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type === 'product_select' ? 'dropdown' : $f->field_type,
                        'is_required' => (bool) $f->is_required,
                        'options' => $options,
                        'placeholder' => $f->placeholder,
                        'help_text' => $f->help_text,
                        'default_value' => $f->default_value ?? null,
                        'validation_rules' => $f->validation_rules ?? [],
                        'order_index' => $f->order_index ?? 0,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => 'success',
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'nik' => $employee->nik,
                'principal_id' => $employee->principal_id,
                'principal_name' => $employee->principal?->name ?? 'Semua Prinsiple',
            ],
            'count' => $formatted->count(),
            'data' => $formatted,
        ]);
    }

    /**
     * Submit a dynamic form report.
     */
    public function submit(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'report_template_id' => 'required|exists:report_templates,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'work_location_id' => 'nullable|integer',
        ]);

        $template = ReportTemplate::with('fields')->findOrFail($request->report_template_id);
        $principalId = $employee->principal_id ?? $template->principal_id;

        // Generate nomor kode laporan unik (misal: RPT-20260824-0012)
        $dateStr = now()->format('Ymd');
        $randomSeq = strtoupper(Str::random(4));
        $submissionCode = "RPT-{$dateStr}-{$randomSeq}";

        try {
            DB::beginTransaction();

            $submission = ReportSubmission::create([
                'report_template_id' => $template->id,
                'principal_id' => $principalId,
                'employee_id' => $employee->id,
                'work_location_id' => $request->work_location_id,
                'itinerary_item_id' => $request->itinerary_item_id,
                'submission_code' => $submissionCode,
                'store_name' => $request->store_name ?? 'Kunjungan Toko',
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_within_radius' => $request->boolean('is_within_radius', true),
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Decode payload values jika dikirimkan sebagai JSON string atau array
            $valuesInput = $request->input('values');
            if (is_string($valuesInput)) {
                $valuesInput = json_decode($valuesInput, true) ?? [];
            }
            if (!is_array($valuesInput)) {
                $valuesInput = [];
            }

            // Simpan setiap parameter input
            foreach ($template->fields as $field) {
                $fieldId = (string) $field->id;
                $fieldName = $field->field_name;
                
                // Cari value dari input
                $rawValue = $valuesInput[$fieldId] ?? $valuesInput[$fieldName] ?? $request->input("val_{$fieldId}") ?? null;
                
                $valueText = null;
                $valueNumber = null;
                $valueJson = null;
                $photoPath = null;

                // Handle file upload (foto / tanda tangan)
                $fileKey = "photo_{$fieldId}";
                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);
                    $filename = "report_{$submission->id}_{$fieldId}_" . time() . '.' . $file->getClientOriginalExtension();
                    $photoPath = $file->storeAs("reports/" . now()->format('Y-m'), $filename, 'public');
                } elseif ($request->hasFile("photo_{$fieldName}")) {
                    $file = $request->file("photo_{$fieldName}");
                    $filename = "report_{$submission->id}_{$fieldId}_" . time() . '.' . $file->getClientOriginalExtension();
                    $photoPath = $file->storeAs("reports/" . now()->format('Y-m'), $filename, 'public');
                }

                // Handle format data berdasarkan field_type
                if (in_array($field->field_type, ['number', 'integer', 'currency', 'percentage', 'rating', 'rating_star', 'slider'])) {
                    if (is_numeric($rawValue)) {
                        $valueNumber = (float) $rawValue;
                    } elseif (is_string($rawValue)) {
                        // Bersihkan karakter non-digit jika currency (misal: "Rp 1.500.000" -> 1500000)
                        $cleanNum = preg_replace('/[^0-9.]/', '', $rawValue);
                        $valueNumber = is_numeric($cleanNum) ? (float) $cleanNum : null;
                    }
                    $valueText = $rawValue !== null ? (string) $rawValue : null;
                } elseif (in_array($field->field_type, ['multi_select', 'checkbox_group', 'sku_list']) || is_array($rawValue)) {
                    $valueJson = is_array($rawValue) ? $rawValue : [$rawValue];
                    $valueText = is_array($rawValue) ? implode(', ', $rawValue) : (string) $rawValue;
                } else {
                    $valueText = is_string($rawValue) ? $rawValue : ($rawValue !== null ? json_encode($rawValue) : null);
                }

                // Jika berupa media / foto dan ada watermark, bisa disimpan pathnya
                if ($photoPath) {
                    $valueText = $photoPath;
                }

                ReportSubmissionValue::create([
                    'report_submission_id' => $submission->id,
                    'report_form_field_id' => $field->id,
                    'field_name' => $field->field_name,
                    'field_type' => $field->field_type,
                    'value_text' => $valueText,
                    'value_number' => $valueNumber,
                    'value_json' => $valueJson,
                    'media_url' => $photoPath,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil dikirim dan tersimpan di sistem.',
                'data' => [
                    'id' => $submission->id,
                    'submission_code' => $submission->submission_code,
                    'template_title' => $template->title,
                    'submitted_at' => $submission->submitted_at->toDateTimeString(),
                    'status' => $submission->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan laporan: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Get active work locations/stores for reporting, filtered by employee's principal.
     */
    public function stores(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $employee->load(['branch', 'principal', 'company']);
        $employeeArea = $employee->branch?->name ?? ($employee->area ?: null);
        $today = \Carbon\Carbon::today('Asia/Jakarta')->toDateString();

        // Cari ID lokasi yang ada di itinerary hari ini
        $itineraryLocationIds = [];
        $itinerary = \App\Models\Itinerary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->with(['items'])
            ->first();

        if ($itinerary) {
            $itineraryLocationIds = $itinerary->items->pluck('work_location_id')->map(fn($id) => (int)$id)->toArray();
        }

        // Ambil semua work location aktif (sama seperti form visit / availableWorkLocations)
        $locations = \App\Models\WorkLocation::with(['branch', 'principal', 'company'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($loc) use ($itineraryLocationIds) {
                $data = $loc->toArray();
                $areaName = $loc->branch ? $loc->branch->name : ($loc->area ?: ($loc->region ?: 'Lainnya'));
                $data['area'] = $areaName;
                $data['is_today_itinerary'] = in_array((int)$loc->id, $itineraryLocationIds);
                return $data;
            });

        return response()->json([
            'status' => 'success',
            'default_area' => $employeeArea,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'area' => $employeeArea,
                'principal_id' => $employee->principal_id,
                'branch_id' => $employee->branch_id,
                'company_id' => $employee->company_id,
            ],
            'count' => $locations->count(),
            'data' => $locations->values(),
        ]);
    }

    /**
     * Get submission history for the authenticated employee.
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $limit = $request->integer('limit', 20);
        $submissions = ReportSubmission::with(['template', 'principal', 'values'])
            ->where('employee_id', $employee->id)
            ->orderBy('submitted_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $submissions->items(),
            'current_page' => $submissions->currentPage(),
            'total' => $submissions->total(),
            'last_page' => $submissions->lastPage(),
        ]);
    }
}
