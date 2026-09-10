<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('competitor_products')) {
            Schema::create('competitor_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('principal_id')->nullable()->constrained('principals')->nullOnDelete();
                $table->string('brand', 100)->index();
                $table->string('subbrand', 150)->index();
                $table->string('category', 100)->nullable()->index();
                $table->json('packaging_sizes')->nullable();
                $table->decimal('benchmark_price_tin', 15, 2)->nullable();
                $table->decimal('benchmark_price_galon', 15, 2)->nullable();
                $table->decimal('benchmark_price_pail', 15, 2)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('order_index')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_products');
    }
};
