<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recent_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('to_city_id')->constrained('cities')->cascadeOnDelete();

            $table->date('searched_date')->nullable();   // axtarılan səfər tarixi
            $table->unsignedTinyInteger('seats')->default(1);
            $table->unsignedSmallInteger('result_count')->default(0);
            $table->timestamp('searched_at');

            $table->unique(['user_id', 'from_city_id', 'to_city_id']);
            $table->index(['from_city_id', 'to_city_id', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recent_searches');
    }
};
