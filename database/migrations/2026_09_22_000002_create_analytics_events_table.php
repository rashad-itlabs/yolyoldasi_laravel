<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Məhsul telemetriyası.
 |
 | Üçüncü tərəf SDK-sı (Firebase Analytics, Amplitude) qəsdən götürülmür: öz
 | backend-imiz var, data Azərbaycanda qalır, tətbiqə yeni asılılıq düşmür və
 | hadisələri admin panelindəki SQL ilə oxumaq olur.
 |
 | Cədvəl **yalnız məhsul qərarları üçündür**. Bir sətir bir hadisədir və
 | şəxsi məlumat daşımır: `params` içinə nömrə, ad, mesaj mətni yazılmır —
 | yalnız marşrut id-ləri, saylar və ekran adları.
 |
 | `anonymous_id` girişdən əvvəlki addımları tutur. Funnel-in ən böyük itkisi
 | məhz orada baş verir (adam elanları görür, nömrə verməyə çatmır), ona görə
 | hesabsız hadisə qəbul edilməsi bu cədvəlin əsas mənasıdır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();

            // Hesab silinsə hadisələr qalır, sadəcə sahibsizləşir — funnel
            // rəqəmləri geriyə doğru dəyişməməlidir.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Girişdən əvvəlki cihaz. Tətbiq ilk açılışda yaradır və saxlayır.
            $table->string('anonymous_id', 64)->nullable();

            $table->string('name', 48);

            // Sərbəst açar-dəyər. Sxem qoyulmur: yeni hadisəyə yeni sahə lazım
            // olanda miqrasiya gözləmək telemetriyanı işlədilməz edərdi.
            $table->json('params')->nullable();

            $table->string('platform', 8)->nullable();   // android | ios
            $table->string('app_version', 24)->nullable();

            // Cihazın öz saatı. Oflayn yığılıb sonra göndərilən hadisələr üçün
            // `created_at`-dan fərqli ola bilər.
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['name', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('anonymous_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
