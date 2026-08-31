<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('principal_id')->nullable()->after('id')->constrained('principals')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->change();
        });

        // Auto-assign existing shifts to first principal if null
        $firstPrincipal = \Illuminate\Support\Facades\DB::table('principals')->where('is_active', true)->orderBy('id')->first()
            ?? \Illuminate\Support\Facades\DB::table('principals')->orderBy('id')->first();
        if ($firstPrincipal) {
            \Illuminate\Support\Facades\DB::table('shifts')->whereNull('principal_id')->update(['principal_id' => $firstPrincipal->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['principal_id']);
            $table->dropColumn('principal_id');
        });
    }
};
