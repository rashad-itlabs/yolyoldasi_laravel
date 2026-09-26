<?php

/*
 |--------------------------------------------------------------------------
 | OneSignal
 |--------------------------------------------------------------------------
 | Push FCM/APNs-ə birbaşa yox, OneSignal üzərindən gedir. Firebase server
 | açarı və APNs auth açarı OneSignal-da saxlanılır — buna görə tətbiqdə nə
 | `google-services.json`, nə də `GoogleService-Info.plist` var.
 |
 | `app_id` gizli deyil, tətbiqin içində də var. `rest_api_key` isə göndərmə
 | icazəsi verir — yalnız `.env`-də qalmalıdır, repoya düşməməlidir.
 */

return [
    'app_id' => (string) env('ONESIGNAL_APP_ID', ''),

    'rest_api_key' => (string) env('ONESIGNAL_REST_API_KEY', ''),

    /*
     | Açarlardan biri boş olsa göndərmə tamamilə dayanır — bildiriş sətri
     | yenə yazılır, sadəcə push getmir. Lokal işləyəndə `false` qoyub
     | səs-küyün qarşısını almaq olar.
     */
    'enabled' => (bool) env('ONESIGNAL_ENABLED', true),

    'endpoint' => (string) env('ONESIGNAL_ENDPOINT', 'https://api.onesignal.com/notifications'),

    /*
     | Göndərmə sorğu axını daxilində sinxron gedir, ona görə timeout qısadır:
     | OneSignal ləngisə, mesaj göndərən istifadəçi gözləməməlidir.
     */
    'timeout' => (int) env('ONESIGNAL_TIMEOUT', 5),

    /*
     |----------------------------------------------------------------------
     | Android kanalları
     |----------------------------------------------------------------------
     | `existing_android_channel_id` — tətbiqin özünün yaratdığı kanallar
     | üçündür (`android_channel_id` isə OneSignal panelindəki kateqoriyanın
     | UUID-si, bizdə yoxdur). Dəyərlər tətbiqdəki `PushChannel` enum-u ilə
     | hərfi-hərfinə eyni olmalıdır — uyğun gəlməsə Android bildirişi
     | OneSignal-ın öz defolt kanalına salır və istifadəçinin kanal üzrə
     | susdurma seçimi işləmir.
     |
     | Kanalın vaciblik dərəcəsi yaradılan anda dondurulur, ona görə
     | `marketing` aşağı, qalanları yüksəkdir — bax tətbiqdəki
     | `notification_channels.dart`.
     */
    'channels' => [
        'messages'  => 'yolyoldasi_messages',
        'bookings'  => 'yolyoldasi_bookings',
        'reminders' => 'yolyoldasi_reminders',
        'marketing' => 'yolyoldasi_marketing',
        'fallback'  => 'yolyoldasi_default',
    ],

    /*
     |----------------------------------------------------------------------
     | Bildiriş növü → kanal + istifadəçi tənzimləməsi
     |----------------------------------------------------------------------
     | `preference` — `notification_preferences` cədvəlindəki sütun adı.
     | `null` olanda yalnız `push_enabled` yoxlanılır: sənəd təsdiqi kimi
     | hesab bildirişlərinin ayrıca söndürmə açarı yoxdur.
     |
     | Siyahıda olmayan növ `fallback` kanalına düşür və göndərilir —
     | serverin tətbiqdən tez öyrəndiyi yeni bir növ susdurulmamalıdır.
     */
    'types' => [
        'newMessage'        => ['channel' => 'messages',  'preference' => 'messages'],
        'bookingRequested'  => ['channel' => 'bookings',  'preference' => 'bookings'],
        'bookingConfirmed'  => ['channel' => 'bookings',  'preference' => 'bookings'],
        // Sürücüyə: ani bronlu səfərinə kimsə yer tutdu. `bookingConfirmed`
        // sərnişinin mətnidir, ona görə sürücü üçün ayrıca növdür.
        'bookingInstant'    => ['channel' => 'bookings',  'preference' => 'bookings'],
        'bookingRejected'   => ['channel' => 'bookings',  'preference' => 'bookings'],
        'bookingCancelled'  => ['channel' => 'bookings',  'preference' => 'bookings'],
        'rideCancelled'     => ['channel' => 'bookings',  'preference' => 'bookings'],
        'reviewRequest'     => ['channel' => 'bookings',  'preference' => 'bookings'],
        'rideReminder'      => ['channel' => 'reminders', 'preference' => 'reminders'],
        'documentsApproved' => ['channel' => 'fallback',  'preference' => null],
        'documentsRejected' => ['channel' => 'fallback',  'preference' => null],

        /*
         | Tələb elanları. İkisi də `reminders` kanalındadır, `bookings` yox:
         | bunlar hələ baş verməmiş bir şeyin xəbəridir, konkret bronla bağlı
         | hadisə deyil. Sərnişin bron bildirişlərini açıq saxlayıb "marşrutda
         | səfər yarandı" xəbərlərini söndürmək istəyə bilər — ayrı kanal ona
         | bu seçimi verir.
         */
        'rideRequestMatched' => ['channel' => 'reminders', 'preference' => 'reminders'],
        'rideRequestPosted'  => ['channel' => 'reminders', 'preference' => 'reminders'],

        /*
         | Admin panelindən əl ilə göndərilənlər. Mətn formadan gəlir, ona görə
         | `headings` siyahısında qarşılığı yoxdur.
         |
         | İkiyə bölünməsinin səbəbi tənzimləmə sütunudur: xidmət elanı (server
         | işləri, təhlükəsizlik) yalnız `push_enabled`-ə tabedir, kampaniya isə
         | `marketing` açarına da — reklamı susduran istifadəçi onu almamalıdır.
         */
        'adminMessage'   => ['channel' => 'fallback',  'preference' => null],
        'adminMarketing' => ['channel' => 'marketing', 'preference' => 'marketing'],
    ],

    /*
     |----------------------------------------------------------------------
     | Başlıqlar
     |----------------------------------------------------------------------
     | Tətbiqdəki `strings_az/ru/en.dart` ilə eyni mətnlər. Hər üçü bir
     | sorğuda gedir və OneSignal cihazın dilinə görə seçir — dil tətbiqdə
     | `OneSignal.User.setLanguage()` ilə hesabın dilinə bərabərləşdirilir.
     |
     | `en` məcburidir: OneSignal tanımadığı dili ona qaytarır.
     */
    'headings' => [
        'newMessage' => [
            'az' => 'Yeni mesaj',
            'ru' => 'Новое сообщение',
            'en' => 'New message',
        ],
        'bookingRequested' => [
            'az' => 'Yeni bron sorğusu',
            'ru' => 'Новый запрос на бронь',
            'en' => 'New booking request',
        ],
        'bookingConfirmed' => [
            'az' => 'Bronunuz təsdiqləndi',
            'ru' => 'Ваша бронь подтверждена',
            'en' => 'Your booking is confirmed',
        ],
        'bookingInstant' => [
            'az' => 'Səfərinizə yer bron edildi',
            'ru' => 'Место в вашей поездке забронировано',
            'en' => 'A seat on your ride was booked',
        ],
        'bookingRejected' => [
            'az' => 'Bronunuz rədd edildi',
            'ru' => 'Ваша бронь отклонена',
            'en' => 'Your booking was declined',
        ],
        'bookingCancelled' => [
            'az' => 'Bron ləğv edildi',
            'ru' => 'Бронь отменена',
            'en' => 'Booking cancelled',
        ],
        'rideReminder' => [
            'az' => 'Səfər xatırlatması',
            'ru' => 'Напоминание о поездке',
            'en' => 'Trip reminder',
        ],
        'rideCancelled' => [
            'az' => 'Səfər ləğv edildi',
            'ru' => 'Поездка отменена',
            'en' => 'Ride cancelled',
        ],
        'reviewRequest' => [
            'az' => 'Səfəri qiymətləndirin',
            'ru' => 'Оцените поездку',
            'en' => 'Rate your trip',
        ],
        'documentsApproved' => [
            'az' => 'Sənədləriniz təsdiqləndi',
            'ru' => 'Документы подтверждены',
            'en' => 'Your documents are approved',
        ],
        'documentsRejected' => [
            'az' => 'Sənədləriniz rədd edildi',
            'ru' => 'Документы отклонены',
            'en' => 'Your documents were rejected',
        ],
        'rideRequestMatched' => [
            'az' => 'Axtardığınız marşrutda səfər var',
            'ru' => 'Появилась поездка по вашему маршруту',
            'en' => 'A ride on your route is available',
        ],
        'rideRequestPosted' => [
            'az' => 'Marşrutunuzda sərnişin axtarır',
            'ru' => 'На вашем маршруте ищут место',
            'en' => 'Someone needs a seat on your route',
        ],
        'unknown' => [
            'az' => 'Yeni bildiriş',
            'ru' => 'Новое уведомление',
            'en' => 'New notification',
        ],
    ],
];
