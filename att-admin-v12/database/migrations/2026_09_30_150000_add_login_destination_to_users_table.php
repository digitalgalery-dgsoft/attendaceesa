<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'login_destination')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('login_destination', 30)->default('admin')->nullable()->after('remember_token');
            });
        }

        // Set default 'admin' for all users
        DB::table('users')
            ->whereNull('login_destination')
            ->orWhere('login_destination', '')
            ->update(['login_destination' => 'admin']);

        // Identify users with explicit Principal roles (set to 'portal')
        $portalRoles = ['Principal PIC', 'principal_pic', 'Principal', 'Client'];
        $portalRoleIds = DB::table('roles')->whereIn('name', $portalRoles)->pluck('id');
        
        if ($portalRoleIds->isNotEmpty()) {
            $portalUserIds = DB::table('model_has_roles')
                ->where('model_type', \App\Models\User::class)
                ->whereIn('role_id', $portalRoleIds)
                ->pluck('model_id');

            if ($portalUserIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $portalUserIds)->update(['login_destination' => 'portal']);
            }
        }

        // Ensure users with AS / AE roles, Admin, HR, Manager remain 'admin'
        $adminRoles = ['AS / AE Inhouse', 'AS', 'AE', 'Super Admin', 'super_admin', 'Admin', 'HR', 'HRD', 'Manager'];
        $adminRoleIds = DB::table('roles')->whereIn('name', $adminRoles)->pluck('id');
        if ($adminRoleIds->isNotEmpty()) {
            $adminUserIds = DB::table('model_has_roles')
                ->where('model_type', \App\Models\User::class)
                ->whereIn('role_id', $adminRoleIds)
                ->pluck('model_id');

            if ($adminUserIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $adminUserIds)->update(['login_destination' => 'admin']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'login_destination')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('login_destination');
            });
        }
    }
};
