<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | Tələb elanlarının iki bildiriş növü.
 |
 | `rideRequestMatched` — sərnişinə: istədiyi marşrutda səfər yarandı. Bu,
 | tələb elanının bütün mənasıdır: sərnişin boş axtarışdan sonra çıxıb getmir,
 | səfər yaranan gün geri çağırılır.
 |
 | `rideRequestPosted` — sürücüyə: onun marşrutunda kimsə yer axtarır. Elan
 | verməyi qumar olmaqdan çıxarıb hazır tələbə cavab verməyə çevirir.
 |
 | Enum-un genişləndirilməsi `2026_09_21_000001`-dəki üsulla eynidir: Laravel-in
 | `change()` metodu MySQL-də enum-u etibarlı dəyişmir, ona görə birbaşa MODIFY.
 */
return new class extends Migration
{
    private const OLD_TYPES = [
        'bookingRequested', 'bookingConfirmed', 'bookingRejected', 'bookingCancelled',
        'rideReminder', 'rideCancelled', 'newMessage', 'reviewRequest',
        'documentsApproved', 'documentsRejected',
        'adminMessage', 'adminMarketing',
    ];

    private const NEW_TYPES = ['rideRequestMatched', 'rideRequestPosted'];

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
