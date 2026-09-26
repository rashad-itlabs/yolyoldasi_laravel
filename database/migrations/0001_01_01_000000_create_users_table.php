<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 20)->unique(); // E.164: +994501234567
            $table->timestamp('phone_verified_at')->nullable();

            $table->string('full_name')->default('');
            $table->string('photo_url')->nullable();
            $table->string('about', 300)->nullable();

            $table->enum('gender', ['male', 'female', 'unspecified'])->default('unspecified');
            $table->unsignedSmallInteger('birth_year')->nullable();

            $table->enum('active_mode', ['passenger', 'driver'])->default('passenger');
            $table->boolean('has_driver_profile')->default(false);

            $table->char('language_code', 2)->default('az');

            $table->boolean('is_admin')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_blocked');
            $table->index('is_admin');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
