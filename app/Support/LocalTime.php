<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Bazarın saatı ilə serverin saatı arasında körpü.
 *
 * Server UTC-də işləyir (`app.timezone`) və vaxtları UTC-də saxlayır — bu
 * dəyişməməlidir: `created_at`-dan tutmuş bütün sütunlar artıq UTC-dədir.
 * İstifadəçi isə Bakı saatı ilə yaşayır. Elanın vaxtı səhv saxlanırdı:
 * tətbiq "09:00"-ı ofsetsiz göndərirdi, server onu 09:00 UTC oxuyurdu və
 * tətbiq geri gələni Bakıda 13:00 göstərirdi.
 *
 * Qayda: klientdən gələn vaxt UTC-yə çevrilib saxlanılır, istifadəçiyə
 * göstəriləndə Bakı saatına çevrilir, "gün" (axtarış tarixi, tələb elanının
 * tarixi) Bakı təqvim günü kimi oxunur.
 */
final class LocalTime
{
    /**
     * Tətbiqin bazarı. Azərbaycan UTC+4-dür və yay vaxtı yoxdur, amma ad
     * zona ilə yazılır, ofsetlə yox — qayda dəyişsə, tzdata özü bilər.
     */
    public const ZONE = 'Asia/Baku';

    /**
     * Klientdən gələn vaxtı UTC anına çevirir.
     *
     * Ofsetli sətir (`…Z`, `…+04:00`) olduğu kimi oxunur — yeni tətbiq belə
     * göndərir. Ofsetsiz sətir Bakı saatı sayılır: telefonlarda qalmış köhnə
     * versiyalar "2026-10-18T09:00:00.000" göndərir və bu, sürücünün Bakıda
     * seçdiyi 09:00-dır, UTC-dəki yox.
     */
    public static function parse(string $value): Carbon
    {
        $value = trim($value);
        $hasOffset = (bool) preg_match('/(Z|[+-]\d{2}:?\d{2})$/i', $value);

        $time = $hasOffset
            ? Carbon::parse($value)
            : Carbon::parse($value, self::ZONE);

        return $time->utc();
    }

    /**
     * Bakı təqvim gününün (`Y-m-d`) UTC sərhədləri, istəsən hər tərəfə
     * [$padDays] gün əlavə ilə — `whereBetween('departure_at', ...)` üçün.
     *
     * `whereDate` UTC günü ilə müqayisə edərdi: Bakıda gecə 00:00–04:00
     * arası yola düşən səfər UTC-də əvvəlki günə düşür və axtarışda səhv
     * tarixdə görünərdi.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dayRange(string $date, int $padDays = 0): array
    {
        $day = Carbon::createFromFormat('Y-m-d', $date, self::ZONE);

        return [
            $day->copy()->subDays($padDays)->startOfDay()->utc(),
            $day->copy()->addDays($padDays)->endOfDay()->utc(),
        ];
    }

    /** Göstərmək üçün: eyni an, Bakı saatı ilə. */
    public static function toLocal(?\DateTimeInterface $time): ?Carbon
    {
        return $time === null ? null : Carbon::instance($time)->copy()->setTimezone(self::ZONE);
    }
}
