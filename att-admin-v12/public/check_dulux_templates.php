<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

header('Content-Type: application/json');

use App\Models\Principal;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$action = $_GET['action'] ?? 'list';

if ($action === 'seed') {
    Artisan::call('db:seed', ['--class' => 'ReportTemplatePresetsSeeder', '--force' => true]);
    $seedOutput = Artisan::output();
}

$duluxPrincipals = Principal::where(function ($q) {
    $q->where('name', 'LIKE', '%ICI%')
      ->orWhere('name', 'LIKE', '%PAINT%')
      ->orWhere('name', 'LIKE', '%DULUX%')
      ->orWhere('name', 'LIKE', '%AKZONOBEL%')
      ->orWhere('code', 'LIKE', '%ICI%')
      ->orWhere('code', 'LIKE', '%DULUX%');
})->get(['id', 'code', 'name']);

$duluxIds = $duluxPrincipals->pluck('id')->toArray();

// Get all templates with code LIKE '%DULUX%' or linked to Dulux principals
$allDuluxTemplates = ReportTemplate::with(['principals:id,code,name', 'fields:id,report_template_id,field_label,field_name,field_type'])
    ->where(function($q) use ($duluxIds) {
        $q->where('code', 'LIKE', '%DULUX%')
          ->orWhereIn('principal_id', $duluxIds)
          ->orWhereHas('principals', function($pq) use ($duluxIds) {
              $pq->whereIn('principals.id', $duluxIds);
          });
    })
    ->get();

$migrations = DB::table('migrations')->where('migration', 'LIKE', '%dulux%')->get();

echo json_encode([
    'dulux_principals' => $duluxPrincipals,
    'templates_count' => $allDuluxTemplates->count(),
    'templates' => $allDuluxTemplates->map(function($t) {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'title' => $t->title,
            'category' => $t->category,
            'report_days' => $t->report_days,
            'is_active' => $t->is_active,
            'principals' => $t->principals->pluck('name'),
            'fields_count' => $t->fields->count(),
        ];
    }),
    'migrations' => $migrations,
    'seed_output' => $seedOutput ?? null,
], JSON_PRETTY_PRINT);
