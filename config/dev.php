<?php

/*
 |--------------------------------------------------------------------------
 | Development köməkçiləri
 |--------------------------------------------------------------------------
 | Yalnız inkişaf mərhələsi üçündür. İstismara verməzdən əvvəl
 | DEV_AUTH_ENABLED=false et və ya bu faylı DevAuthController ilə
 | birlikdə tamamilə sil.
 */

return [
    'auth_enabled' => (bool) env('DEV_AUTH_ENABLED', false),
    'auth_secret'  => (string) env('DEV_AUTH_SECRET', ''),

    /*
     | POST /auth/phone/request cavabında 6 rəqəmli kodu da qaytarır ki,
     | Flutter onu ekranda göstərə bilsin.
     |
     | İki qat müdafiə var və hər ikisi qəsdəndir. Birincisi, defolt artıq
     | `false`-dur — kod `config/sms.php` üzərindən SMS ilə gedir. İkincisi,
     | `APP_ENV=production` olanda dəyər nə olursa-olsun söndürülür: bu şərt
     | olmasa, serverdə səhvən qalmış bir `OTP_EXPOSE_CODE=true` bütün
     | hesabları açıq qoyardı — nömrəni bilən hər kəs kodu cavabdan oxuyub
     | həmin hesaba girə bilərdi.
     |
     | Klient tərəfdə heç nə silmək lazım deyil: sahə gəlməyəndə giriş
     | ekranındakı banner öz-özünə yox olur.
     */
    'otp_expose_code' => (bool) env('OTP_EXPOSE_CODE', false)
        && env('APP_ENV') !== 'production',
];
