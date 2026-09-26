<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ride_id')->constrained()->cascadeOnDelete();

            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('last_sender_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_locked')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
