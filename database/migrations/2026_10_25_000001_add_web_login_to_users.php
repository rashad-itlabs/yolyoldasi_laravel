<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Saytın öz girişi: e-poçt + şifrə.
 |
 | Sayt hesabları hələlik tətbiq hesablarından ayrıdır — tətbiq telefon və SMS
 | kodu ilə girir, sayt e-poçt və şifrə ilə. Hər ikisi eyni `users` cədvəlindədir
 | ki, sonra tətbiqdə birləşdirmək bir sətri yeniləmək olsun, köçürmə yox.
 |
 | Sayt hesabının telefonu yoxdur, ona görə `phone` nullable olur. MySQL-in
 | unique indeksi birdən çox NULL-a icazə verir, tətbiq hesablarının nömrə
 | təkliyi isə əvvəlki kimi qalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->unique()->after('phone');
            }

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'password')) {
                $table->string('password')->nullable()->after('email_verified_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['email', 'email_verified_at', 'password']);
        });

        // `phone` NOT NULL-a qaytarılmır: sayt hesabları qalıbsa, ALTER yarıda
        // dayanardı. Telefonsuz sətirləri silmək isə geri qaytarma deyil.
    }
};
