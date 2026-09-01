<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('positions') && Schema::hasColumn('positions', 'require_face_recognition')) {
            // Ubah default value menjadi false (OFF secara default)
            Schema::table('positions', function (Blueprint $table) {
                $table->boolean('require_face_recognition')->default(false)->change();
            });

            // Set seluruh posisi yang ada saat ini menjadi false (OFF)
            DB::table('positions')->update(['require_face_recognition' => false]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('positions') && Schema::hasColumn('positions', 'require_face_recognition')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->boolean('require_face_recognition')->default(true)->change();
            });
        }
    }
};
