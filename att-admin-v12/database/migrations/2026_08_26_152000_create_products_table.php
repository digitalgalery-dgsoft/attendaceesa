<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principal_id')->nullable()->constrained('principals')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('sku_code', 100)->nullable()->index();
            $table->string('barcode', 100)->nullable()->index();
            $table->string('category', 100)->nullable()->index();
            $table->string('brand', 100)->nullable()->index();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('uom', 50)->default('Pcs');
            $table->string('image_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['principal_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
