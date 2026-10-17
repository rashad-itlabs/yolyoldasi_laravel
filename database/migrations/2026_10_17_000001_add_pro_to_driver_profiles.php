<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Sürücü PRO-su.
 |
 |  driver_profiles.pro_until         — PRO-nun bitmə vaxtı (null = heç olmayıb
 |                                      və ya admin ləğv edib)
 |  driver_profiles.pro_trial_used_at — pulsuz sınağın başladıldığı an
 |
 | Hər ikisi sürücü profilindədir, `users`-də yox: PRO sürücünün statusudur,
 | sərnişin hesabının bununla işi yoxdur. Boost-un özü isə əvvəlki kimi
 | `rides.boosted_until`-dədir — axtarış sıralaması yenə tək cədvəl üzərindən
 | gedir, bu sütunlar yalnız ora nə yazılacağını müəyyən edir.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sütun yoxlaması böyümə miqrasiyasındakı səbəbdəndir: yarımçıq
        // qalmış cəhddən sonra təkrar `migrate` "duplicate column" verməsin.
        Schema::table('driver_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_profiles', 'pro_until')) {
                // `boost_until` ilə eyni tip: ikisi `DriverProfile`-da müqayisə
                // olunur və hansı gec bitirsə, elan o vaxta qədər boost alır.
                $table->timestamp('pro_until')->nullable();
            }

            if (! Schema::hasColumn('driver_profiles', 'pro_trial_used_at')) {
                // Sınaq hesab başına bir dəfədir. Ayrıca sütundur, `pro_until`-dən
                // çıxarılmır: admin PRO-nu ləğv edəndə `pro_until` null olur, və
                // sınağın istifadə edildiyi faktı onunla birlikdə itməməlidir.
                $table->timestamp('pro_trial_used_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn(['pro_until', 'pro_trial_used_at']);
        });
    }
};
