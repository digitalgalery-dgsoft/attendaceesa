<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class InstallController extends Controller
{
    public function index()
    {
        return view('install');
    }

    public function process(Request $request)
    {
        $request->validate([
            'app_url' => 'required|url',
            'db_host' => 'required',
            'db_port' => 'required|numeric',
            'db_name' => 'required',
            'db_user' => 'required',
            'db_password' => 'nullable',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:6',
            'db_connection' => 'required|in:mysql,pgsql',
        ]);

        try {
            // Update .env
            $this->updateEnv([
                'APP_URL' => $request->app_url,
                'DB_CONNECTION' => $request->db_connection,
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_name,
                'DB_USERNAME' => $request->db_user,
                'DB_PASSWORD' => $request->db_password ?? '',
            ]);

            // Clear config cache so the new .env is loaded in the current request
            Artisan::call('config:clear');

            $dbConn = $request->db_connection;

            // Force reconnect to the new database configuration
            config([
                "database.connections.{$dbConn}.host" => $request->db_host,
                "database.connections.{$dbConn}.port" => $request->db_port,
                "database.connections.{$dbConn}.database" => $request->db_name,
                "database.connections.{$dbConn}.username" => $request->db_user,
                "database.connections.{$dbConn}.password" => $request->db_password ?? '',
            ]);
            
            DB::purge($dbConn);
            config(['database.default' => $dbConn]);
            
            // Test Connection
            DB::connection($dbConn)->getPdo();
            
            // Run migrations
            Artisan::call('migrate:fresh', [
                '--force' => true,
                '--database' => $dbConn,
            ]);

            // Run only PermissionsSeeder
            Artisan::call('db:seed', [
                '--class' => 'PermissionsSeeder',
                '--force' => true,
            ]);

            // Create or update Super Admin
            $adminUser = User::updateOrCreate(
                ['email' => $request->admin_email],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make($request->admin_password),
                ]
            );

            $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
            $adminUser->assignRole($superAdminRole);

            // Set default settings
            \App\Models\Setting::create([
                'app_name' => 'AbsensiKu',
                'theme_color' => '#0A192F',
            ]);

            // Create .installed file
            file_put_contents(storage_path('app/.installed'), 'Installed on ' . now()->toDateTimeString());

            return redirect('/admin/login')->with('success', 'Instalasi berhasil! Silakan login.');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function updateEnv($data = [])
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $env = file_get_contents($path);

            foreach ($data as $key => $value) {
                // Ensure value doesn't have spaces unless quoted
                if (preg_match('/\s/', $value) && !preg_match('/^".*"$/', $value)) {
                    $value = '"' . $value . '"';
                }

                $keyPosition = strpos($env, "{$key}=");

                if ($keyPosition !== false) {
                    $endOfLinePosition = strpos($env, "\n", $keyPosition);
                    if ($endOfLinePosition === false) {
                        $endOfLinePosition = strlen($env);
                    }
                    $oldLine = substr($env, $keyPosition, $endOfLinePosition - $keyPosition);
                    $env = str_replace($oldLine, "{$key}={$value}", $env);
                } else {
                    $env .= "\n{$key}={$value}\n";
                }
            }

            file_put_contents($path, $env);
        }
    }
}
