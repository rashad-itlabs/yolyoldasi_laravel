<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | E-poçta göndərilən birdəfəlik kodlar — sayt qeydiyyatı və şifrə bərpası.
 |
 | Qeydiyyatda hesab kod təsdiqlənənə qədər yaradılmır: ad və şifrənin hash-i
 | `payload`-da (şifrələnmiş) gözləyir. Belə olmasa, başqasının e-poçtunu yazan
 | adam həmin ünvanı təsdiqsiz hesabla tutub saxlaya bilərdi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('purpose', 16); // register | reset
            $table->string('code_hash');
            $table->text('payload')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_codes');
    }
};
