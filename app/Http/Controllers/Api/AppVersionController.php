<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /api/v1/app-version` — tətbiq açılanda və fona düşüb qayıdanda soruşur.
 *
 * Açıqdır: yeniləmə divarı giriş ekranından da əvvəl qalxmalıdır, ona görə
 * cavab üçün token tələb etmək olmaz.
 *
 * Qərarı burada server verir (`status`), tətbiq isə sadəcə ona baxır. Beləliklə
 * qaydanı dəyişmək üçün mağazaya yeni buraxılış göndərmək lazım gəlmir.
 *
 * Bir prinsip bütün metoda hakimdir: **şübhə olanda yol ver**. Naməlum
 * platforma, cədvəldə olmayan sətir, oxunmayan build — hamısı `ok` qaytarır.
 * Səhv bir cavabın nəticəsi istifadəçinin tətbiqə ümumiyyətlə girə bilməməsidir,
 * ona görə yalnız açıq-aydın göstəriş olanda bloklanır.
 */
class AppVersionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $platform = strtolower(trim((string) $request->query('platform')));
        $build = (int) $request->query('build');
        $lang = (string) $request->query('lang', 'az');

        if (! in_array($lang, ['az', 'ru', 'en'], true)) {
            $lang = 'az';
        }

        $row = in_array($platform, AppVersion::PLATFORMS, true)
            ? AppVersion::find($platform)
            : null;

        if (! $row) {
            return $this->reply(AppVersion::STATUS_OK);
        }

        $status = $row->statusFor($build);

        // `ok` cavabına mətn və link qoşmuruq — göstəriləsi ekran yoxdur.
        if ($status === AppVersion::STATUS_OK) {
            return $this->reply(AppVersion::STATUS_OK);
        }

        return $this->reply($status, [
            'min_build'      => $row->min_build,
            'min_version'    => $row->min_version,
            'latest_build'   => $row->latest_build,
            'latest_version' => $row->latest_version,
            'store_url'      => $row->store_url ?: null,
            'message'        => $row->message($lang),
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function reply(string $status, array $extra = []): JsonResponse
    {
        return response()->json([
            'data' => ['status' => $status] + $extra,
        ]);
    }
}
