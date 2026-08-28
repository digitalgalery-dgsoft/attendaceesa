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

        $principalId = $employee->principal_id ?? $employee->department?->principal_id;
        
        // Cari semua template yang ditugaskan ke prinsiple karyawan ini
        $templatesQuery = ReportTemplate::with([
            'fields' => function ($q) {
                $q->orderBy('order_index', 'asc');
            },
            'products' => function ($q) {
                $q->where(function ($sq) {
                    $sq->where('is_active', true)->orWhere('is_active', 1)->orWhereNull('is_active');
                })->orderBy('name', 'asc');
            },
            'principals',
        ])->where('is_active', true);

        $allMatchingPrincipalIds = [];
        if ($principalId) {
            $principal = Principal::find($principalId);
            $allMatchingPrincipalIds = [$principalId];
            if ($principal) {
                if (!empty($principal->subdomain)) {
                    $allMatchingPrincipalIds = Principal::where('subdomain', $principal->subdomain)->pluck('id')->toArray();
                } else {
                    $allMatchingPrincipalIds = Principal::where('name', $principal->name)->pluck('id')->toArray();
                }
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
        $formatted = $templates->map(function ($t) use ($employee, $allMatchingPrincipalIds) {
            $templateProducts = $t->products;

            // Jika template belum memiliki mapping produk spesifik di pivot table, cari otomatis dari master produk principal template / employee
            if ($templateProducts->isEmpty()) {
                $targetPrincipalIds = collect($allMatchingPrincipalIds);

                if ($t->principal_id) {
                    $targetPrincipalIds->push($t->principal_id);
                }
                if ($t->principals->isNotEmpty()) {
                    $targetPrincipalIds = $targetPrincipalIds->merge($t->principals->pluck('id'));
                }

                // Jika template adalah Wings Surya / Lion Wings (kode: RPT-WINGS-...)
                if (Str::startsWith($t->code, 'RPT-WINGS-') || Str::contains(strtoupper($t->title), 'WINGS')) {
                    $wingsIds = Principal::where('name', 'LIKE', '%WINGS%')
                        ->orWhere('code', 'LIKE', '%WINGS%')
                        ->orWhere('subdomain', 'wings')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($wingsIds);
                }
                // Jika template adalah Dulux / ICI Paints
                elseif (Str::startsWith($t->code, 'RPT-DULUX-') || Str::contains(strtoupper($t->title), 'DULUX')) {
                    $duluxIds = Principal::where('name', 'LIKE', '%DULUX%')
                        ->orWhere('name', 'LIKE', '%AKZONOBEL%')
                        ->orWhere('name', 'LIKE', '%ICI%')
                        ->orWhere('subdomain', 'dulux')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($duluxIds);
                }
                // Jika template adalah Fonterra
                elseif (Str::startsWith($t->code, 'RPT-FONTERRA-') || Str::contains(strtoupper($t->title), 'FONTERRA')) {
                    $fonterraIds = Principal::where('name', 'LIKE', '%FONTERRA%')
                        ->orWhere('subdomain', 'fonterra')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($fonterraIds);
                }
                // Jika template adalah Mamasuka / Daesang / Miwon
                elseif (Str::startsWith($t->code, 'RPT-MAMASUKA-') || Str::contains(strtoupper($t->title), 'MAMASUKA')) {
                    $mamasukaIds = Principal::where('name', 'LIKE', '%MAMASUKA%')
                        ->orWhere('name', 'LIKE', '%DAESANG%')
                        ->orWhere('name', 'LIKE', '%MIWON%')
                        ->orWhere('subdomain', 'mamasuka')
                        ->pluck('id');
                    $targetPrincipalIds = $targetPrincipalIds->merge($mamasukaIds);
                }

                $cleanIds = $targetPrincipalIds->filter()->unique()->toArray();

                if (!empty($cleanIds)) {
                    $templateProducts = \App\Models\Product::whereIn('principal_id', $cleanIds)
                        ->where(function ($q) {
                            $q->where('is_active', true)->orWhere('is_active', 1)->orWhereNull('is_active');
                        })
                        ->orderBy('name', 'asc')
                        ->get();
                }
            }

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
                        'barcode' => $p->barcode,
                        'category' => $p->category,
                        'brand' => $p->brand,
                        'price' => (float) ($p->price ?? 0),
                        'formatted_price' => $p->formatted_price,
                        'uom' => $p->uom ?? 'Pcs',
                    ];
                })->values(),
                'fields' => $t->fields->map(function ($f) use ($productNames, $templateProducts) {
                    $options = $f->options ?? [];

                    // HANYA isi options dari productNames jika field_type adalah product_select dan opsi manual kosong
                    if ($f->field_type === 'product_select' && empty($options)) {
                        if (!empty($productNames)) {
                            $options = $productNames;
                        }
                    }

                    return [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type,
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

        $workLocationId = $request->work_location_id;
        $itineraryItemId = $request->itinerary_item_id;
        $storeName = $request->store_name;
        $address = $request->address;

        $today = now()->toDateString();
        
        // Cek jika sedang visit aktif hari ini
        $lastVisitIn = \App\Models\AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('logged_at', $today)
            ->where('log_type', 'visit_in')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVisitIn && isset($lastVisitIn->metadata['visit_location_id'])) {
            $vLocId = $lastVisitIn->metadata['visit_location_id'];
            $loc = \App\Models\WorkLocation::find($vLocId);
            if ($loc) {
                $workLocationId = $workLocationId ?: $loc->id;
                $storeName = $storeName ?: $loc->name;
                $address = $address ?: $loc->address;
            }
        }

        // Jika belum dapat storeName, cek dari absensi check-in hari ini
        if (empty($storeName) || empty($workLocationId)) {
            $todayAtt = \App\Models\Attendance::where('employee_id', $employee->id)
                ->where('attendance_date', $today)
                ->with(['workLocation', 'branch'])
                ->first();

            if ($todayAtt) {
                if ($todayAtt->workLocation) {
                    $workLocationId = $workLocationId ?: $todayAtt->workLocation->id;
                    $storeName = $storeName ?: $todayAtt->workLocation->name;
                    $address = $address ?: $todayAtt->workLocation->address;
                } elseif ($todayAtt->branch) {
                    $storeName = $storeName ?: $todayAtt->branch->name;
                    $address = $address ?: $todayAtt->branch->address;
                }
            }
        }

        if (empty($storeName)) {
            $storeName = 'Lokasi Kunjungan Terdaftar';
        }

        try {
            DB::beginTransaction();

            $submission = ReportSubmission::create([
                'report_template_id' => $template->id,
                'principal_id' => $principalId,
                'employee_id' => $employee->id,
                'work_location_id' => $workLocationId,
                'itinerary_item_id' => $itineraryItemId,
                'submission_code' => $submissionCode,
                'store_name' => $storeName,
                'address' => $address,
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

                $isMediaField = in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature']);

                // Handle file upload (single foto, multi-foto, atau tanda tangan)
                $savedPhotos = $this->saveUploadedPhotos($request, $submission->id, $fieldId, $fieldName);
                if (!empty($savedPhotos)) {
                    $photoPath = $savedPhotos[0];
                    $valueJson = $savedPhotos;
                    $valueText = implode(', ', $savedPhotos);
                }

                // Handle format data berdasarkan field_type (HANYA untuk non-media)
                if (!$isMediaField) {
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
                }

                // Jika berupa media / foto dan belum ada valueText
                if ($photoPath && empty($valueText)) {
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

        $limit = $request->integer('limit', 50);
        $submissions = ReportSubmission::with([
            'template' => function ($q) {
                $q->with(['fields' => fn($fq) => $fq->orderBy('order_index', 'asc'), 'products']);
            },
            'principal',
            'workLocation',
            'values.formField',
        ])
            ->where('employee_id', $employee->id)
            ->orderBy('submitted_at', 'desc')
            ->paginate($limit);

        $items = collect($submissions->items())->map(function ($s) {
            $isApproved = in_array(strtolower($s->status ?? ''), ['approved', 'verified']);
            $canEdit = !$isApproved;

            $valuesFormatted = $s->values->map(function ($v) {
                $mediaFullUrls = $this->formatMediaFullUrls($v);
                $mediaFullUrl = $mediaFullUrls[0] ?? null;

                return [
                    'id' => $v->id,
                    'report_form_field_id' => $v->report_form_field_id,
                    'field_name' => $v->field_name,
                    'field_label' => $v->formField?->field_label ?? Str::title(str_replace('_', ' ', $v->field_name)),
                    'field_type' => $v->field_type,
                    'value_text' => $v->value_text,
                    'value_number' => $v->value_number !== null ? (float) $v->value_number : null,
                    'value_json' => $v->value_json,
                    'media_url' => $v->media_url,
                    'media_full_url' => $mediaFullUrl,
                    'media_full_urls' => $mediaFullUrls,
                ];
            });

            return [
                'id' => $s->id,
                'submission_code' => $s->submission_code,
                'report_template_id' => $s->report_template_id,
                'template_title' => $s->template?->title ?? 'Laporan',
                'template_code' => $s->template?->code ?? '',
                'template_category' => $s->template?->category ?? 'general',
                'store_name' => $s->store_name,
                'address' => $s->address,
                'work_location_id' => $s->work_location_id,
                'status' => $s->status ?? 'pending',
                'status_label' => match(strtolower($s->status ?? '')) {
                    'approved', 'verified' => 'Terverifikasi (Approve)',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Verifikasi (Pending)',
                },
                'can_edit' => $canEdit,
                'is_within_radius' => (bool) $s->is_within_radius,
                'latitude' => $s->latitude ? (float) $s->latitude : null,
                'longitude' => $s->longitude ? (float) $s->longitude : null,
                'submitted_at' => $s->submitted_at ? $s->submitted_at->toDateTimeString() : null,
                'submitted_at_formatted' => $s->submitted_at ? $s->submitted_at->format('d M Y, H:i') : null,
                'principal_name' => $s->principal?->name,
                'template' => $s->template ? [
                    'id' => $s->template->id,
                    'code' => $s->template->code,
                    'title' => $s->template->title,
                    'description' => $s->template->description,
                    'category' => $s->template->category,
                    'color' => $s->template->color ?? '#0F52BA',
                    'icon' => $s->template->icon ?? 'document-text',
                    'fields' => $s->template->fields->map(fn($f) => [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type === 'product_select' ? 'dropdown' : $f->field_type,
                        'is_required' => (bool) $f->is_required,
                        'options' => $f->options ?? [],
                        'placeholder' => $f->placeholder,
                        'help_text' => $f->help_text,
                    ]),
                    'products' => $s->template->products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'brand' => $p->brand,
                        'price' => (float) $p->price,
                        'formatted_price' => $p->formatted_price,
                    ]),
                ] : null,
                'values' => $valuesFormatted,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'current_page' => $submissions->currentPage(),
            'total' => $submissions->total(),
            'last_page' => $submissions->lastPage(),
        ]);
    }

    /**
     * Get single report submission detail.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $submission = ReportSubmission::with([
            'template' => function ($q) {
                $q->with(['fields' => fn($fq) => $fq->orderBy('order_index', 'asc'), 'products']);
            },
            'principal',
            'workLocation',
            'values.formField',
        ])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $isApproved = in_array(strtolower($submission->status ?? ''), ['approved', 'verified']);
        $canEdit = !$isApproved;

        $valuesFormatted = $submission->values->map(function ($v) {
            $mediaFullUrls = $this->formatMediaFullUrls($v);
            $mediaFullUrl = $mediaFullUrls[0] ?? null;

            return [
                'id' => $v->id,
                'report_form_field_id' => $v->report_form_field_id,
                'field_name' => $v->field_name,
                'field_label' => $v->formField?->field_label ?? Str::title(str_replace('_', ' ', $v->field_name)),
                'field_type' => $v->field_type,
                'value_text' => $v->value_text,
                'value_number' => $v->value_number !== null ? (float) $v->value_number : null,
                'value_json' => $v->value_json,
                'media_url' => $v->media_url,
                'media_full_url' => $mediaFullUrl,
                'media_full_urls' => $mediaFullUrls,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $submission->id,
                'submission_code' => $submission->submission_code,
                'report_template_id' => $submission->report_template_id,
                'template_title' => $submission->template?->title ?? 'Laporan',
                'template_code' => $submission->template?->code ?? '',
                'template_category' => $submission->template?->category ?? 'general',
                'store_name' => $submission->store_name,
                'address' => $submission->address,
                'work_location_id' => $submission->work_location_id,
                'status' => $submission->status ?? 'pending',
                'status_label' => match(strtolower($submission->status ?? '')) {
                    'approved', 'verified' => 'Terverifikasi (Approve)',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Verifikasi (Pending)',
                },
                'can_edit' => $canEdit,
                'is_within_radius' => (bool) $submission->is_within_radius,
                'latitude' => $submission->latitude ? (float) $submission->latitude : null,
                'longitude' => $submission->longitude ? (float) $submission->longitude : null,
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toDateTimeString() : null,
                'submitted_at_formatted' => $submission->submitted_at ? $submission->submitted_at->format('d M Y, H:i') : null,
                'principal_name' => $submission->principal?->name,
                'template' => $submission->template ? [
                    'id' => $submission->template->id,
                    'code' => $submission->template->code,
                    'title' => $submission->template->title,
                    'description' => $submission->template->description,
                    'category' => $submission->template->category,
                    'color' => $submission->template->color ?? '#0F52BA',
                    'icon' => $submission->template->icon ?? 'document-text',
                    'fields' => $submission->template->fields->map(fn($f) => [
                        'id' => $f->id,
                        'field_name' => $f->field_name,
                        'field_label' => $f->field_label,
                        'field_type' => $f->field_type === 'product_select' ? 'dropdown' : $f->field_type,
                        'is_required' => (bool) $f->is_required,
                        'options' => $f->options ?? [],
                        'placeholder' => $f->placeholder,
                        'help_text' => $f->help_text,
                    ]),
                    'products' => $submission->template->products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'brand' => $p->brand,
                        'price' => (float) $p->price,
                        'formatted_price' => $p->formatted_price,
                    ]),
                ] : null,
                'values' => $valuesFormatted,
            ],
        ]);
    }

    /**
     * Update an existing report submission if not yet approved.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $employee = $this->getAuthenticatedEmployee($request);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $submission = ReportSubmission::with(['template.fields', 'values'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        // Cek apakah status sudah Approve / Verified
        if (in_array(strtolower($submission->status ?? ''), ['approved', 'verified'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Laporan ini sudah disetujui (Approved) dan tidak dapat diubah lagi.',
            ], 422);
        }

        $template = $submission->template;
        if (!$template) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template form pelaporan tidak ditemukan.',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Update data header jika dikirimkan
            if ($request->filled('store_name')) {
                $submission->store_name = $request->store_name;
            }
            if ($request->filled('address')) {
                $submission->address = $request->address;
            }
            if ($request->filled('work_location_id')) {
                $submission->work_location_id = $request->work_location_id;
            }
            $submission->updated_at = now();
            $submission->save();

            // Decode input values
            $valuesInput = $request->input('values');
            if (is_string($valuesInput)) {
                $valuesInput = json_decode($valuesInput, true) ?? [];
            }
            if (!is_array($valuesInput)) {
                $valuesInput = [];
            }

            // Update setiap field parameter
            foreach ($template->fields as $field) {
                $fieldId = (string) $field->id;
                $fieldName = $field->field_name;

                $existingVal = $submission->values->first(function ($val) use ($field, $fieldId, $fieldName) {
                    return $val->report_form_field_id == $field->id || $val->field_name == $fieldName;
                });

                // Cek apakah ada input nilai baru
                $hasNewValue = array_key_exists($fieldId, $valuesInput) || 
                               array_key_exists($fieldName, $valuesInput) || 
                               $request->has("val_{$fieldId}") || 
                               $request->has("val_{$fieldName}");

                $rawValue = $valuesInput[$fieldId] ?? $valuesInput[$fieldName] ?? $request->input("val_{$fieldId}") ?? $request->input("val_{$fieldName}") ?? ($existingVal?->value_text);

                $valueText = $existingVal?->value_text;
                $valueNumber = $existingVal?->value_number;
                $valueJson = $existingVal?->value_json;
                $photoPath = $existingVal?->media_url;

                // Handle upload foto / media baru jika dikirimkan
                $savedPhotos = $this->saveUploadedPhotos($request, $submission->id, $fieldId, $fieldName);

                // Cek apakah ada existing photos yang dipertahankan dari request
                $existingPhotosInput = $request->input("existing_photos_{$fieldId}") ?? $request->input("existing_photos_{$fieldName}");
                $keptExistingPhotos = [];
                if ($existingPhotosInput) {
                    if (is_string($existingPhotosInput)) {
                        $rawArr = json_decode($existingPhotosInput, true) ?? [$existingPhotosInput];
                    } elseif (is_array($existingPhotosInput)) {
                        $rawArr = $existingPhotosInput;
                    } else {
                        $rawArr = [];
                    }
                    foreach ((array)$rawArr as $item) {
                        if (empty($item) || !is_string($item)) continue;
                        $cleaned = trim($item);
                        // Abaikan jika berupa path lokal perangkat android
                        if (str_starts_with($cleaned, '/data/user/') || str_starts_with($cleaned, 'data/user/') || str_contains($cleaned, 'cache/wm_')) {
                            continue;
                        }
                        if (str_contains($cleaned, '/storage/')) {
                            $parts = explode('/storage/', $cleaned);
                            $cleaned = ltrim(end($parts), '/');
                        } elseif (str_starts_with($cleaned, 'storage/')) {
                            $cleaned = ltrim(substr($cleaned, 8), '/');
                        }
                        $keptExistingPhotos[] = $cleaned;
                    }
                } elseif (empty($savedPhotos) && $existingVal) {
                    // Jika tidak ada upload baru dan tidak ada manipulasi existing photos, gunakan existing value
                    if (is_array($existingVal->value_json)) {
                        foreach ($existingVal->value_json as $item) {
                            if (empty($item) || !is_string($item)) continue;
                            $cleaned = trim($item);
                            if (str_starts_with($cleaned, '/data/user/') || str_starts_with($cleaned, 'data/user/') || str_contains($cleaned, 'cache/wm_')) {
                                continue;
                            }
                            if (str_contains($cleaned, '/storage/')) {
                                $parts = explode('/storage/', $cleaned);
                                $cleaned = ltrim(end($parts), '/');
                            } elseif (str_starts_with($cleaned, 'storage/')) {
                                $cleaned = ltrim(substr($cleaned, 8), '/');
                            }
                            $keptExistingPhotos[] = $cleaned;
                        }
                    } elseif ($existingVal->media_url) {
                        $cleaned = trim($existingVal->media_url);
                        if (!str_starts_with($cleaned, '/data/user/') && !str_starts_with($cleaned, 'data/user/') && !str_contains($cleaned, 'cache/wm_')) {
                            if (str_contains($cleaned, '/storage/')) {
                                $parts = explode('/storage/', $cleaned);
                                $cleaned = ltrim(end($parts), '/');
                            } elseif (str_starts_with($cleaned, 'storage/')) {
                                $cleaned = ltrim(substr($cleaned, 8), '/');
                            }
                            $keptExistingPhotos[] = $cleaned;
                        }
                    }
                }

                // Gabungkan foto yang dipertahankan + foto yang baru diupload
                $allPhotos = array_values(array_unique(array_filter(array_merge($keptExistingPhotos, $savedPhotos))));
                if (!empty($allPhotos)) {
                    $photoPath = $allPhotos[0];
                    $valueJson = $allPhotos;
                    $valueText = implode(', ', $allPhotos);
                }

                $isMediaField = in_array($field->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature']);

                // Format data non-media
                if (!$isMediaField && $hasNewValue) {
                    if (in_array($field->field_type, ['number', 'integer', 'currency', 'percentage', 'rating', 'rating_star', 'slider'])) {
                        if (is_numeric($rawValue)) {
                            $valueNumber = (float) $rawValue;
                        } elseif (is_string($rawValue)) {
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
                }

                if ($photoPath && empty($valueText)) {
                    $valueText = $photoPath;
                }

                ReportSubmissionValue::updateOrCreate(
                    [
                        'report_submission_id' => $submission->id,
                        'report_form_field_id' => $field->id,
                    ],
                    [
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'value_text' => $valueText,
                        'value_number' => $valueNumber,
                        'value_json' => $valueJson,
                        'media_url' => $photoPath,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Laporan berhasil diperbarui.',
                'data' => [
                    'id' => $submission->id,
                    'submission_code' => $submission->submission_code,
                    'status' => $submission->status,
                    'updated_at' => $submission->updated_at->toDateTimeString(),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui laporan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to collect and save uploaded files for a field (single or multi-photo).
     * @return array array of saved relative file paths
     */
    private function saveUploadedPhotos(Request $request, int $submissionId, string $fieldId, string $fieldName): array
    {
        $savedPaths = [];
        $uploadedFiles = [];

        // 1. Cek jika dikirim sebagai array photo_{fieldId}[] atau photos_{fieldId}[]
        if ($request->hasFile("photo_{$fieldId}")) {
            $f = $request->file("photo_{$fieldId}");
            if (is_array($f)) {
                $uploadedFiles = array_merge($uploadedFiles, $f);
            } else {
                $uploadedFiles[] = $f;
            }
        }
        if ($request->hasFile("photos_{$fieldId}")) {
            $f = $request->file("photos_{$fieldId}");
            if (is_array($f)) {
                $uploadedFiles = array_merge($uploadedFiles, $f);
            } else {
                $uploadedFiles[] = $f;
            }
        }
        if ($request->hasFile("photo_{$fieldName}")) {
            $f = $request->file("photo_{$fieldName}");
            if (is_array($f)) {
                $uploadedFiles = array_merge($uploadedFiles, $f);
            } else {
                $uploadedFiles[] = $f;
            }
        }

        // 2. Cek jika dikirim dengan suffix index (misal: photo_{fieldId}_0, photo_{fieldId}_1, dst)
        foreach ($request->allFiles() as $key => $file) {
            if (str_starts_with($key, "photo_{$fieldId}_") || str_starts_with($key, "photos_{$fieldId}_") || str_starts_with($key, "photo_{$fieldName}_")) {
                if (is_array($file)) {
                    $uploadedFiles = array_merge($uploadedFiles, $file);
                } else {
                    $uploadedFiles[] = $file;
                }
            }
        }

        foreach ($uploadedFiles as $idx => $file) {
            if ($file && $file->isValid()) {
                $filename = "report_{$submissionId}_{$fieldId}_{$idx}_" . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs("reports/" . now()->format('Y-m'), $filename, 'public');
                if ($path) {
                    $savedPaths[] = $path;
                }
            }
        }

        return $savedPaths;
    }

    /**
     * Helper to format media full URLs cleanly and prevent duplicate/broken paths.
     */
    private function formatMediaFullUrls($v): array
    {
        $rawPaths = [];
        if (is_array($v->value_json) && !empty($v->value_json)) {
            $rawPaths = $v->value_json;
        } elseif (!empty($v->media_url)) {
            $rawPaths = [$v->media_url];
        } elseif (!empty($v->file_path)) {
            $rawPaths = [$v->file_path];
        } elseif (!empty($v->value_text) && (str_contains($v->value_text, 'reports/') || str_contains($v->value_text, 'storage/'))) {
            $rawPaths = array_map('trim', explode(',', $v->value_text));
        }

        $urls = [];
        foreach ($rawPaths as $p) {
            if (empty($p) || !is_string($p)) continue;
            $clean = trim($p);
            // Abaikan jika berupa path lokal perangkat android
            if (str_starts_with($clean, '/data/user/') || str_starts_with($clean, 'data/user/') || str_contains($clean, 'cache/wm_')) {
                continue;
            }
            if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
                $urls[] = str_replace('/storage/storage/', '/storage/', $clean);
            } else {
                if (str_starts_with($clean, 'storage/')) {
                    $clean = substr($clean, 8);
                } elseif (str_starts_with($clean, '/storage/')) {
                    $clean = substr($clean, 9);
                }
                $urls[] = asset('storage/' . ltrim($clean, '/'));
            }
        }

        // Fallback: Jika urls kosong padahal ini media field, cari file di disk yang sesuai
        if (empty($urls) && in_array($v->field_type, ['photo', 'camera_photo', 'multi_photo', 'signature'])) {
            $subId = $v->report_submission_id;
            $fieldId = $v->report_form_field_id;
            $pattern = "reports/*/report_{$subId}_{$fieldId}_*.jpg";
            $matches = glob(storage_path("app/public/{$pattern}"));
            if (empty($matches)) {
                $pattern2 = "reports/*/report_{$subId}_*.jpg";
                $matches = glob(storage_path("app/public/{$pattern2}"));
            }
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $rel = str_replace(storage_path('app/public/'), '', $match);
                    $rel = str_replace('\\', '/', $rel);
                    $urls[] = asset('storage/' . ltrim($rel, '/'));
                }
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }
}
