<?php

namespace App\Http\Controllers;

/**
 * Sahiblik faylları — `/r/{id}` linkinin brauzerdə yox, tətbiqdə açılması üçün.
 *
 * Telefon linki tətbiqə yalnız domen "bu tətbiq mənimdir" deyəndə verir:
 * Android `assetlinks.json`-u, iOS `apple-app-site-association`-u yoxlayır.
 * Fayl olmasa və ya barmaq izi uyğun gəlməsə, link sadəcə brauzerdə açılır —
 * yəni səhv bir şey sındırmır, sadəcə işləmir.
 *
 * Statik fayl yox, route: `public/.well-known` nginx-in nöqtəli qovluq
 * qadağasına ilişə bilər, Apple faylının isə uzantısı yoxdur və
 * `application/json` başlığını burada Laravel özü qoyur.
 */
class AppLinksController extends Controller
{
    private const ANDROID_PACKAGE = 'yolyoldasi.az';

    /**
     * İmza açarlarının SHA-256 barmaq izləri.
     *
     * Play-dən quraşdırılan build (test də daxil) **Play-in** açarı ilə
     * imzalanır, ona görə onun izi burada olmalıdır: Play Console → Test and
     * release → App integrity → App signing key certificate.
     */
    private const ANDROID_SHA256 = [
        // Upload key (android/app/upload-keystore.jks) — əl ilə quraşdırılan
        // release build-lər üçün.
        'CF:E1:DD:BF:00:D7:81:C7:D4:2E:94:CD:3E:9C:A4:4A:4F:B0:F0:15:88:E0:C1:AE:28:C1:5E:A4:80:24:E4:35',
        // Play App Signing key — Play-dən quraşdırılan bütün build-lər.
        '88:28:F2:35:03:17:FF:46:56:33:2E:A9:A4:7D:29:D3:9B:D4:93:62:BB:3C:29:04:AD:74:14:FC:4C:49:91:3D',
    ];

    /** Team ID + bundle id. */
    private const IOS_APP_ID = '8CS5782JF7.az.yolyoldasi.app';

    public function android()
    {
        return response()->json([[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target'   => [
                'namespace'                => 'android_app',
                'package_name'             => self::ANDROID_PACKAGE,
                'sha256_cert_fingerprints' => self::ANDROID_SHA256,
            ],
        ]]);
    }

    public function apple()
    {
        return response()->json([
            'applinks' => [
                'details' => [[
                    'appIDs'     => [self::IOS_APP_ID],
                    'components' => [['/' => '/r/*']],
                ]],
            ],
        ]);
    }
}
