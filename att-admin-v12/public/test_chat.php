<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

try {
    $employeeId = 5; // assume 5
    $conversation = \App\Models\Conversation::firstOrCreate([
        'employee_id' => $employeeId,
    ]);

    $message = $conversation->messages()->create([
        'sender_type' => 'employee',
        'sender_id' => $employeeId,
        'message' => 'test test',
        'is_read' => false,
    ]);
    
    echo "Success! Message ID: " . $message->id . "\n";
    echo "JSON Output: \n";
    echo json_encode([
        'status' => 'success',
        'data' => $message,
    ], JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
