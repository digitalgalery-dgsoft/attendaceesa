<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\LeaveRequest::observe(\App\Observers\LeaveRequestObserver::class);
        \App\Models\EmployeeSchedule::observe(\App\Observers\EmployeeScheduleObserver::class);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $setting = \App\Models\Setting::first();
                if ($setting && $setting->smtp_host) {
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $setting->smtp_host,
                        'mail.mailers.smtp.port' => $setting->smtp_port,
                        'mail.mailers.smtp.encryption' => $setting->smtp_encryption,
                        'mail.mailers.smtp.username' => $setting->smtp_username,
                        'mail.mailers.smtp.password' => $setting->smtp_password,
                        'mail.from.address' => $setting->mail_from_address,
                        'mail.from.name' => $setting->mail_from_name,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Ignore during migrations or when DB is not ready
        }
    }
}
