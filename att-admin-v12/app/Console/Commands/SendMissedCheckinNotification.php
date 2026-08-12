<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\FirebaseService;
use Carbon\Carbon;

class SendMissedCheckinNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:missed-checkin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to employees who missed check-in today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get today's date
        $today = Carbon::today()->toDateString();
        
        // Exclude weekends if your company doesn't work on weekends
        if (Carbon::today()->isWeekend()) {
            return;
        }

        // Get employees who have FCM token, are active, and don't have attendance for today
        $employees = Employee::whereNotNull('fcm_token')
            ->where('is_active', true)
            ->whereDoesntHave('attendances', function ($query) use ($today) {
                $query->where('attendance_date', $today);
            })
            // You can add more conditions here (e.g., exclude those on leave today)
            ->get();

        $tokens = $employees->pluck('fcm_token')->toArray();

        if (!empty($tokens)) {
            $firebase = new FirebaseService();
            $firebase->sendNotification(
                $tokens,
                'Jangan Lupa Check-in!',
                'Anda belum melakukan absen masuk hari ini. Silakan segera check-in dari aplikasi Anda.'
            );
            $this->info("Sent missed check-in notification to " . count($tokens) . " employees.");
        } else {
            $this->info("No missed check-ins found.");
        }
    }
}
