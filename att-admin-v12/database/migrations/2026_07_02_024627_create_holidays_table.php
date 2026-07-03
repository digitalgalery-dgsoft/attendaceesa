<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('holiday_date');
            $table->string('name', 150);
            $table->enum('type', ['national', 'company', 'regional']);
            $table->boolean('is_paid')->default(true);
            $table->timestamps();
            
            $table->unique(['company_id', 'holiday_date']);
        });
    }
    public function down() { Schema::dropIfExists('holidays'); }
};