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
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5000)->by($request->user()?->id ?: $request->ip());
        });

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
            // Ignore if DB not ready
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('itineraries') && !\Illuminate\Support\Facades\Schema::hasColumn('itineraries', 'is_strict_routing')) {
                \Illuminate\Support\Facades\Schema::table('itineraries', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('is_strict_routing')->default(false)->after('status');
                });
            }
        } catch (\Exception $e) {
            // Ignore if DB not ready
        }

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('report_template_product') && \Illuminate\Support\Facades\Schema::hasTable('report_templates') && \Illuminate\Support\Facades\Schema::hasTable('products')) {
                \Illuminate\Support\Facades\Schema::create('report_template_product', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->foreignId('report_template_id')->constrained('report_templates')->cascadeOnDelete();
                    $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                    $table->timestamps();
                    $table->unique(['report_template_id', 'product_id']);
                });
            }
        } catch (\Exception $e) {
            // Ignore if DB not ready
        }
    }
}
