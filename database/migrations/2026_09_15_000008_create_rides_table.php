<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('from_city_id')->constrained('cities');
            $table->foreignId('to_city_id')->constrained('cities');

            $table->dateTime('departure_at');

            $table->unsignedTinyInteger('total_seats');  // 1..4
            $table->unsignedTinyInteger('booked_seats')->default(0);
            $table->decimal('price_per_seat', 8, 2);      // 1..500

            $table->enum('status', ['active', 'inactive', 'completed', 'cancelled'])
                ->default('active');

            $table->string('note', 400)->nullable();
            $table->string('pickup_point')->nullable();
            $table->string('dropoff_point')->nullable();
            $table->boolean('instant_booking')->default(false);

            $table->timestamps();

            $table->index(['from_city_id', 'to_city_id', 'status', 'departure_at']);
            $table->index(['driver_id', 'departure_at']);
            $table->index(['status', 'departure_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
