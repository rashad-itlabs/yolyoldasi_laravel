<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passenger_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedTinyInteger('seats');
            $table->decimal('total_price', 8, 2);

            $table->enum('status', [
                'pending',
                'confirmed',
                'rejected',
                'cancelled_by_passenger',
                'cancelled_by_driver',
                'completed',
            ])->default('pending');

            $table->string('message', 400)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->boolean('passenger_reviewed')->default(false);
            $table->boolean('driver_reviewed')->default(false);

            $table->timestamps();

            $table->unique(['ride_id', 'passenger_id']);
            $table->index(['passenger_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
