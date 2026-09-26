<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();

            $table->decimal('driver_rating', 3, 2)->default(0);
            $table->unsignedInteger('driver_review_count')->default(0);
            $table->unsignedInteger('driver_trip_count')->default(0);

            $table->decimal('passenger_rating', 3, 2)->default(0);
            $table->unsignedInteger('passenger_review_count')->default(0);
            $table->unsignedInteger('passenger_trip_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
