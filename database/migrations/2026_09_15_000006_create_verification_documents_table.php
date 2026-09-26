<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['id_card', 'driver_license', 'vehicle_registration', 'insurance']);
            $table->enum('status', ['not_uploaded', 'pending', 'approved', 'rejected'])
                ->default('not_uploaded');

            $table->string('file_url')->nullable();
            $table->string('back_file_url')->nullable(); // yalnız id_card və driver_license

            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->unique(['driver_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
    }
};
