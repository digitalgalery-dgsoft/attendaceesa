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
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL direct safe drop of unique constraints and indexes
            DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_odoo_id_unique;");
            DB::statement("DROP INDEX IF EXISTS employees_odoo_id_unique;");

            DB::statement("ALTER TABLE principals DROP CONSTRAINT IF EXISTS principals_odoo_id_unique;");
            DB::statement("DROP INDEX IF EXISTS principals_odoo_id_unique;");

            DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_company_id_employee_no_unique;");
            DB::statement("DROP INDEX IF EXISTS employees_company_id_employee_no_unique;");

            // Add non-unique performance indexes
            DB::statement("CREATE INDEX IF NOT EXISTS employees_odoo_id_idx ON employees (odoo_id);");
            DB::statement("CREATE INDEX IF NOT EXISTS employees_nik_principal_idx ON employees (employee_no, principal_id);");
            DB::statement("CREATE INDEX IF NOT EXISTS principals_odoo_id_idx ON principals (odoo_id);");
        } else {
            // MySQL / SQLite fallback using Schema builder
            Schema::table('employees', function (Blueprint $table) {
                try {
                    $table->dropUnique('employees_odoo_id_unique');
                } catch (\Throwable $e) {}
                try {
                    $table->dropUnique(['company_id', 'employee_no']);
                } catch (\Throwable $e) {}
            });

            Schema::table('principals', function (Blueprint $table) {
                try {
                    $table->dropUnique('principals_odoo_id_unique');
                } catch (\Throwable $e) {}
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to restore restrictive unique constraints as odoo_id is non-unique across databases/entities
    }
};
