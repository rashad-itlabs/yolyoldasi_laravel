<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();

            $table->boolean('push_enabled')->default(true);
            $table->boolean('bookings')->default(true);
            $table->boolean('messages')->default(true);
            $table->boolean('reminders')->default(true);
            $table->boolean('marketing')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
