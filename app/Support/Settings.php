<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Sayt tənzimləmələrinə giriş.
 *
 * Dəyərlər sorğu ərzində bir dəfə oxunur və statik massivdə saxlanılır.
 * Laravel cache-i qəsdən işlədilmir: sayt əl ilə (scp) yenilənir və köhnə
 * cache səssizcə keçmiş məzmunu göstərərdi.
 */
class Settings
{
    private static ?array $values = null;

    /** Bütün açarlar — açar => dəyər. */
    public static function all(): array
    {
        return self::$values ??= SiteSetting::pluck('value', 'key')->all();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;

        // Boş sətir də "verilməyib" sayılır — admin sahəni təmizləyəndə
        // sayt boş yer yox, ehtiyat dəyəri göstərsin.
        return ($value === null || $value === '') ? $default : $value;
    }

    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'updated_at' => now()]
        );

        self::forget();
    }

    /** @param array<string, string|null> $values */
    public static function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'updated_at' => now()]
            );
        }

        self::forget();
    }

    public static function forget(): void
    {
        self::$values = null;
    }
}
