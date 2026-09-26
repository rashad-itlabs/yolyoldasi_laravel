<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('brand');
            $table->string('model');
            $table->string('color', 20)->nullable(); // açar: white, black, silver, ...
            $table->string('plate', 15)->nullable();  // 10-AA-123

            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('seats')->default(4);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
