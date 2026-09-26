<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | Ani bron üçün sürücü bildirişi.
 |
 | `bookingInstant` — sürücüyə: ani bronlu səfərinə sərnişin yer tutdu. Əvvəl
 | bu halda sürücüyə `bookingConfirmed` gedirdi, o isə sərnişinin bildirişidir
 | ("Bronunuz təsdiqləndi") — sürücü öz səfərinə bron edildiyini yox, özünün
 | bron etdiyini oxuyurdu.
 |
 | Sürücüyə əvvəl getmiş `bookingConfirmed` sətirləri çevrilmir, miqrasiya
 | yalnız enum-u genişləndirir. Çevrilsəydi, `down()` yeni növlü sətirləri
 | silərkən o köhnə tarixçəni də aparardı.
 |
 | Enum-un genişləndirilməsi `2026_09_22_000004`-dəki üsulla eynidir: Laravel-in
 | `change()` metodu MySQL-də enum-u etibarlı dəyişmir, ona görə birbaşa MODIFY.
 */
return new class extends Migration
{
    /** Enum-un indiki tam siyahısı — əvvəlki üç miqrasiyanın cəmi. */
    private const OLD_TYPES = [
        'bookingRequested', 'bookingConfirmed', 'bookingRejected', 'bookingCancelled',
        'rideReminder', 'rideCancelled', 'newMessage', 'reviewRequest',
        'documentsApproved', 'documentsRejected',
        'adminMessage', 'adminMarketing',
        'rideRequestMatched', 'rideRequestPosted',
    ];

    private const NEW_TYPES = ['bookingInstant'];

    public function up(): void
    {
        DB::statement($this->enumSql(array_merge(self::OLD_TYPES, self::NEW_TYPES)));
    }

    public function down(): void
    {
        // Enum daralmadan əvvəl yeni dəyərli sətirlər getməlidir, yoxsa
        // MODIFY onları boş sətrə çevirir.
        DB::table('notifications')->whereIn('type', self::NEW_TYPES)->delete();

        DB::statement($this->enumSql(self::OLD_TYPES));
    }

    private function enumSql(array $types): string
    {
        $values = implode(', ', array_map(fn ($type) => "'".$type."'", $types));

        return "ALTER TABLE notifications MODIFY type ENUM({$values}) NOT NULL";
    }
};
