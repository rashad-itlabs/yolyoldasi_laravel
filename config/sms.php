<?php

/*
 |--------------------------------------------------------------------------
 | SMS provayderi
 |--------------------------------------------------------------------------
 | OTP kodunun cihaza çatan yolu. Bura qədər kod `POST /auth/phone/request`
 | cavabında qayıdırdı — yəni nömrəni bilən hər kəs istənilən hesaba girə
 | bilirdi. Provayder qoşulan kimi `OTP_EXPOSE_CODE=false` edilməlidir.
 |
 | Azərbaycan provayderlərinin (Atlas SMS, LSIM, SMS.az, Poster) API-ləri
 | demək olar eynidir: bir URL, bir neçə sabit parametr, bir nömrə, bir mətn.
 | Ona görə burada hazır sinif yox, **şablon** var: provayder dəyişəndə kod
 | yox, `.env` dəyişir.
 |
 | Nümunə (Atlas SMS):
 |
 |   SMS_DRIVER=http
 |   SMS_URL=https://apps.atlassms.com/api/v1/sms/send
 |   SMS_METHOD=POST
 |   SMS_PARAM_PHONE=to
 |   SMS_PARAM_TEXT=message
 |   SMS_SENDER=YolYoldasi
 |   SMS_PARAM_SENDER=from
 |   SMS_AUTH_HEADER="Bearer xxxxx"
 |
 | Nümunə (istifadəçi adı/parol ilə işləyən köhnə GET API):
 |
 |   SMS_DRIVER=http
 |   SMS_METHOD=GET
 |   SMS_URL=https://api.provayder.az/send.php
 |   SMS_EXTRA='{"user":"yolyoldasi","password":"xxx","sender":"YolYoldasi"}'
 |   SMS_PARAM_PHONE=number
 |   SMS_PARAM_TEXT=msg
 */

return [
    /*
     | log  — kod yalnız `storage/logs`-a yazılır (lokal iş üçün)
     | http — aşağıdakı şablonla real sorğu gedir
     | null — heç nə göndərilmir (SMS söndürülüb)
     */
    'driver' => (string) env('SMS_DRIVER', 'log'),

    'url'    => (string) env('SMS_URL', ''),
    'method' => strtoupper((string) env('SMS_METHOD', 'POST')),

    /* Provayderin nömrə və mətn üçün gözlədiyi parametr adları. */
    'param_phone' => (string) env('SMS_PARAM_PHONE', 'to'),
    'param_text'  => (string) env('SMS_PARAM_TEXT', 'text'),

    /* Göndərən adı — parametr adı boş olsa ümumiyyətlə göndərilmir. */
    'sender'       => (string) env('SMS_SENDER', ''),
    'param_sender' => (string) env('SMS_PARAM_SENDER', ''),

    /*
     | Sorğuya əlavə olunan sabit parametrlər (JSON obyekt). Çox provayderdə
     | istifadəçi adı və parol məhz belə gedir.
     */
    'extra' => json_decode((string) env('SMS_EXTRA', '{}'), true) ?: [],

    /* Başlıqla işləyən provayderlər üçün — məsələn "Bearer xxx" və ya "Key xxx". */
    'auth_header' => (string) env('SMS_AUTH_HEADER', ''),

    /*
     | Nömrənin formatı. Bəzi provayderlər `+` qəbul etmir, bəziləri ölkə
     | kodsuz istəyir.
     |
     |   e164  → +994501234567
     |   plain → 994501234567
     |   local → 0501234567
     */
    'phone_format' => (string) env('SMS_PHONE_FORMAT', 'e164'),

    /*
     | Kodun mətni. `:code` əvəzlənir. Qısa saxlanılır — bir SMS seqmenti
     | latın hərfləri ilə 160 simvoldur, Azərbaycan hərfləri (ə, ğ, ş) isə
     | mesajı Unicode-a keçirib 70 simvola endirir və qiyməti ikiqat edir.
     | Ona görə mətndə diakritik yoxdur.
     */
    'template' => (string) env('SMS_TEMPLATE', 'Yol Yoldasi tesdiq kodu: :code'),

    'timeout' => (int) env('SMS_TIMEOUT', 8),
];
