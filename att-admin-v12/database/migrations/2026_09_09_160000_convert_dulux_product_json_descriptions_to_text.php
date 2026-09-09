<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $products = DB::table('products')
            ->where('description', 'LIKE', '{%')
            ->get();

        foreach ($products as $p) {
            $raw = trim((string) $p->description);
            if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
                $clean = Product::formatDescriptionText($raw);
                if ($clean && $clean !== $raw) {
                    DB::table('products')->where('id', $p->id)->update([
                        'description' => $clean,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
