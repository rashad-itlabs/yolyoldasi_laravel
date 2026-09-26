<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Məhsul telemetriyası — tətbiqin göndərdiyi hadisə dəstələri.
 *
 * **Açıq endpoint-dir və bu qəsdəndir.** Funnel-in ən böyük itkisi girişdən
 * əvvəl baş verir: adam elanlara baxır, nömrəsini verməyə çatmır. Yalnız
 * daxil olmuş istifadəçinin hadisələrini yığsaq, məhz ən vacib hissəni
 * görməzdik.
 *
 * Endpoint heç vaxt xəta qaytarmır (validasiyadan başqa): telemetriya
 * istifadəçinin işini dayandırmamalıdır və tətbiq cavabı onsuz da gözləmir.
 */
class EventController extends Controller
{
    /** Bir sorğuda qəbul edilən maksimum hadisə sayı. */
    private const MAX_BATCH = 50;

    public function store(Request $request)
    {
        $data = $request->validate([
            'anonymous_id'      => ['nullable', 'string', 'max:64'],
            'platform'          => ['nullable', 'in:android,ios'],
            'app_version'       => ['nullable', 'string', 'max:24'],
            'events'            => ['required', 'array', 'min:1', 'max:'.self::MAX_BATCH],
            'events.*.name'     => ['required', 'string', 'max:48'],
            'events.*.params'   => ['nullable', 'array'],
            'events.*.occurred_at' => ['nullable', 'date'],
        ]);

        $userId = $request->user()?->id;
        $now    = now();

        $rows = [];

        foreach ($data['events'] as $event) {
            // Ağ siyahıdan kənar ad sakitcə atılır. Xəta qaytarsaq, tətbiqin
            // yeni versiyasında əlavə edilmiş bir hadisə bütün dəstəni
            // itirərdi — halbuki qalan hadisələr tamamilə yararlıdır.
            if (! in_array($event['name'], AnalyticsEvent::NAMES, true)) {
                continue;
            }

            $rows[] = [
                'user_id'      => $userId,
                'anonymous_id' => $data['anonymous_id'] ?? null,
                'name'         => $event['name'],
                'params'       => isset($event['params']) ? json_encode($this->clean($event['params'])) : null,
                'platform'     => $data['platform'] ?? null,
                'app_version'  => $data['app_version'] ?? null,
                'occurred_at'  => isset($event['occurred_at']) ? Carbon::parse($event['occurred_at']) : $now,
                'created_at'   => $now,
            ];
        }

        if ($rows !== []) {
            AnalyticsEvent::insert($rows);
        }

        return response()->json(['accepted' => count($rows)], 202);
    }

    /**
     * `params` içindən şəxsi məlumatı kəsir.
     *
     * Cədvəl məhsul qərarları üçündür, istifadəçi izləmək üçün yox. Klient
     * səhvən nömrə və ya mesaj mətni göndərsə, bura onu saxlamamalıdır:
     * sonradan silmək həmişə gec olur.
     */
    private function clean(array $params): array
    {
        $forbidden = ['phone', 'full_name', 'name', 'message', 'text', 'token', 'email', 'note'];

        return collect($params)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $forbidden, true))
            // Yalnız sadə dəyərlər: iç-içə obyekt analiz üçün lazım deyil və
            // JSON sütununu şişirdir.
            ->filter(fn ($value) => is_scalar($value) || $value === null)
            ->take(12)
            ->all();
    }
}
