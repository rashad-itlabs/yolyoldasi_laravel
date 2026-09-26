<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mobil tətbiqin minimum və sonuncu versiyası — platforma başına bir sətir.
 *
 * Tətbiq açılanda `GET /api/v1/app-version` sorğusu atır; cavab `required`
 * olanda ekranı bloklayır, `optional` olanda keçiləbilən xəbərdarlıq göstərir.
 * Qərarı server verir, ona görə yeni buraxılışı məcburi etmək üçün tətbiqi
 * yenidən yığmaq lazım deyil — admin panelindən bir rəqəm dəyişmək kifayətdir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            // `android` | `ios`. Build nömrələri iki mağazada müstəqil artır,
            // ona görə hər platforma öz sətrini saxlayır.
            $table->string('platform', 10)->primary();

            // Bundan aşağı build bloklanır. `min_build` = 1 heç kimi bloklamır.
            $table->unsignedInteger('min_build')->default(1);

            // Yalnız ekranda göstərmək üçün — qərar build nömrəsi ilə verilir,
            // çünki o, hər buraxılışda mütləq artır, semantik versiya isə yox.
            $table->string('min_version', 20)->default('1.0.0');

            // Mağazadakı sonuncu buraxılış: bundan aşağı, amma `min_build`-dan
            // yuxarı olan tətbiq keçiləbilən xəbərdarlıq görür.
            $table->unsignedInteger('latest_build')->default(1);
            $table->string('latest_version', 20)->default('1.0.0');

            // Boş buraxılsa tətbiq öz ehtiyat linkindən istifadə edir.
            $table->string('store_url', 300)->nullable();

            // Xəbərdarlıq ekranının izah mətni. Tətbiq üç dildədir, sətir də —
            // istifadəçinin dili `lang` parametri ilə gəlir, tapılmayan dil
            // Azərbaycan dilinə düşür.
            $table->string('message_az', 300)->nullable();
            $table->string('message_ru', 300)->nullable();
            $table->string('message_en', 300)->nullable();

            // Söndürüləndə endpoint həmişə `ok` qaytarır. Səhv yazılmış bir
            // rəqəmin bütün istifadəçiləri tətbiqdən kənarda qoyduğu halda
            // geri dönüş yolu budur.
            $table->boolean('is_enabled')->default(true);

            $table->timestamp('updated_at')->nullable();
        });

        // Başlanğıc sətirlər heç nə bloklamır: `min_build` = 1, `latest_build`
        // isə indiki buraxılış. Cədvəl boş qalsaydı endpoint onsuz da `ok`
        // deyərdi, amma admin panelinin redaktə edəcəyi sətir olmalıdır.
        DB::table('app_versions')->insert([
            [
                'platform'       => 'android',
                'min_build'      => 1,
                'min_version'    => '1.0.0',
                'latest_build'   => 4,
                'latest_version' => '1.0.0',
                'store_url'      => 'https://play.google.com/store/apps/details?id=yolyoldasi.az',
                'is_enabled'     => true,
                'updated_at'     => now(),
            ],
            [
                'platform'       => 'ios',
                'min_build'      => 1,
                'min_version'    => '1.0.0',
                'latest_build'   => 4,
                'latest_version' => '1.0.0',
                // App Store linki tətbiq dərc olunandan sonra admin panelindən
                // yazılır — id əvvəlcədən məlum deyil.
                'store_url'      => null,
                'is_enabled'     => true,
                'updated_at'     => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
