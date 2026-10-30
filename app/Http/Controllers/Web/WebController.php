<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Saytın hesab səhifələri üçün ortaq əsas.
 *
 * Layihədə `lang/` qovluğu yoxdur, ona görə Laravel-in standart doğrulama
 * mesajları ingiliscə çıxır ("The email field is required"). API-də bu
 * görünmür — tətbiq öz mətnini göstərir — amma sayt formasında istifadəçi
 * birbaşa oxuyur. Mesajlar burada bir yerdədir ki, hər forma eyni dildə danışsın.
 */
abstract class WebController extends Controller
{
    private const MESSAGES = [
        'required'         => ':attribute boş ola bilməz.',
        'email'            => 'E-poçt ünvanı düzgün deyil.',
        'confirmed'        => 'Şifrələr eyni deyil.',
        'accepted'         => 'Davam etmək üçün qaydaları qəbul et.',
        'digits'           => 'Kod :digits rəqəmdən ibarət olmalıdır.',
        // Ölçü qaydaları (min/max/between) massiv gözləyir, növə görə açarla —
        // 'min.string' kimi düz açarı Laravel tanımır və ingiliscə mesaja düşür.
        'min'              => [
            'string'  => ':attribute ən az :min simvol olmalıdır.',
            'numeric' => ':attribute ən az :min olmalıdır.',
        ],
        'max'              => [
            'string'  => ':attribute ən çox :max simvol ola bilər.',
            'numeric' => ':attribute ən çox :max ola bilər.',
        ],
        'between'          => [
            'numeric' => ':attribute :min ilə :max arasında olmalıdır.',
            'string'  => ':attribute :min ilə :max simvol arasında olmalıdır.',
        ],
        'integer'          => ':attribute rəqəm olmalıdır.',
        'in'               => ':attribute üçün seçim düzgün deyil.',
        'exists'           => 'Seçilmiş :attribute tapılmadı.',
        'current_password' => 'Hazırkı şifrə yanlışdır.',
    ];

    private const ATTRIBUTES = [
        'full_name'        => 'Ad, soyad',
        'email'            => 'E-poçt',
        'password'         => 'Şifrə',
        'current_password' => 'Hazırkı şifrə',
        'code'             => 'Kod',
        'city_id'          => 'şəhər',
        'gender'           => 'Cins',
        'birth_year'       => 'Doğum ili',
        'about'            => 'Haqqında',
    ];

    /** `$request->validate()`, Azərbaycan dilində mesajlarla. */
    protected function validateAz(Request $request, array $rules, ?string $bag = null): array
    {
        return $bag === null
            ? $request->validate($rules, self::MESSAGES, self::ATTRIBUTES)
            : $request->validateWithBag($bag, $rules, self::MESSAGES, self::ATTRIBUTES);
    }
}
