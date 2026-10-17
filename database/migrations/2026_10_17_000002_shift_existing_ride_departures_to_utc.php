<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | Mövcud elanların yola düşmə vaxtını UTC-yə çevirir.
 |
 | Sütun UTC saxlamalıdır (server `app.timezone = UTC`), amma tətbiq vaxtı
 | ofsetsiz göndərirdi və sürücünün Bakıda seçdiyi 09:00 bazaya 09:00 UTC kimi
 | yazılırdı — tətbiq onu 13:00 göstərirdi. Kod düzəldi (bax `LocalTime`);
 | bu miqrasiya düzəlişdən əvvəl yazılmış sətirləri 4 saat geri çəkir.
 |
 | Sərhəd ID ilədir, vaxtla yox: deploy anında yeni kodla yaranan elan artıq
 | düzgün saxlanılır və ikinci dəfə çəkilməməlidir. Düzəliş vaxtı bazada 6
 | elan var idi, ən böyük ID 16 idi; hamısı tətbiqdən Bakı saatı ilə gəlmişdi.
 |
 | Azərbaycanda yay vaxtı yoxdur — fərq həmişə dəqiq 4 saatdır. `boosted_until`
 | toxunulmur: o vaxta qədər heç bir elana yazılmırdı (bax `RideController`).
 */
return new class extends Migration
{
    private const LAST_ID_BEFORE_FIX = 16;

    public function up(): void
    {
        DB::table('rides')
            ->where('id', '<=', self::LAST_ID_BEFORE_FIX)
            ->update(['departure_at' => DB::raw('DATE_SUB(departure_at, INTERVAL 4 HOUR)')]);
    }

    public function down(): void
    {
        DB::table('rides')
            ->where('id', '<=', self::LAST_ID_BEFORE_FIX)
            ->update(['departure_at' => DB::raw('DATE_ADD(departure_at, INTERVAL 4 HOUR)')]);
    }
};
