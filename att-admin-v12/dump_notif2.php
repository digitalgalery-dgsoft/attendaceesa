<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
\Filament\Notifications\Notification::make()->title('Test')->body('Hello')->sendToDatabase($user);
$notif = $user->notifications()->latest()->first();
echo json_encode($notif);
