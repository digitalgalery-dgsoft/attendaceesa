<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations to synchronize passwords and unlock status across duplicate NIK records.
     */
    public function up(): void
    {
        $nik = '3528042504850003';

        // 1. Abdurrahman Jamil: Set password to esa50003 on all matching records & unlock device
        $hashedEsa = Hash::make('esa50003');

        Employee::withTrashed()
            ->where('employee_no', $nik)
            ->update([
                'password' => $hashedEsa,
                'device_id' => null,
                'device_name' => null,
                'fcm_token' => null,
            ]);

        // Also update linked User account if exists
        User::where('email', 'like', '%jamil%')
            ->orWhere('name', 'like', '%Abdurrahman Jamil%')
            ->update(['password' => $hashedEsa]);

        // 2. Synchronize all other duplicate NIK records in the system to the latest active password
        $duplicateNiks = Employee::select('employee_no')
            ->whereNotNull('employee_no')
            ->where('employee_no', '!=', '')
            ->groupBy('employee_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('employee_no');

        foreach ($duplicateNiks as $dupNik) {
            $latest = Employee::where('employee_no', $dupNik)
                ->whereNotNull('password')
                ->where('password', '!=', '')
                ->orderByDesc('updated_at')
                ->first();

            if ($latest && !empty($latest->password)) {
                Employee::where('employee_no', $dupNik)
                    ->update(['password' => $latest->password]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
