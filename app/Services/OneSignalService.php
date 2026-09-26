<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Bildiriş sətrini OneSignal-a ötürür — telefon kilidli olanda da çatan
 * yeganə yol.
 *
 * OneSignal FCM və APNs-in qarşısında dayanan ötürücüdür: Firebase server
 * açarı və APNs auth açarı onun serverindədir, ona görə tətbiqdə nə
 * `google-services.json`, nə də `GoogleService-Info.plist` var.
 *
 * **Ünvanlama cihaz üzrə deyil, hesab üzrədir.** Tətbiq girişdən sonra
 * `OneSignal.login(user_id)` çağırır; bundan sonra burada sadəcə "42 nömrəli
 * istifadəçiyə göndər" deyilir və OneSignal həmin hesabın bütün cihazlarına
 * paylayır. Cihaz siyahısı ilə müqayisədə fərq boşluqlardadır: token
 * dəyişəndə, `POST /me/device-tokens` alınmayanda və ya tətbiq yenidən
 * quraşdırılanda cihaz siyahıdan sakitcə düşür. Alias düşmür.
 *
 * `device_tokens` yenə də doldurulur və alias olmayan hesablar üçün ehtiyat
 * yol kimi işlədilir — məsələn, tətbiqin köhnə versiyasında qalmış cihaz.
 */
class OneSignalService
{
    /**
     * Bildirişi göndərir. Uğur qazanıbsa `true`.
     *
     * Heç vaxt istisna atmır: push mesajın özündən daha az vacibdir və
     * OneSignal-ın əlçatmaz olması mesaj göndərməyi dayandırmamalıdır.
     */
    public function send(Notification $notification): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $user = User::find($notification->user_id);
        if (! $user || ! $this->allowedBy($user, $notification->type)) {
            return false;
        }

        $target = $this->target($user);
        if ($target === null) {
            // Nə alias, nə də cihaz var — heç vaxt giriş etməmiş və ya
            // bildiriş icazəsi verməmiş hesab. Sətir onsuz da yazılıb,
            // tətbiq açılanda siyahıda görünəcək.
            return false;
        }

        $result = $this->post(
            array_merge($target, $this->message($notification)),
            ['notification_id' => $notification->id]
        );

        return $result['ok'];
    }

    /**
     * Admin panelindən göndərilən bildirişin alıcıları.
     *
     * Ayrıca metoddur, çünki çağıran tərəf həmin siyahıdan həm push üçün, həm
     * də `notifications` sətirləri üçün istifadə edir — iki dəfə hesablansa,
     * push getdiyi halda siyahıda görünməyən (və ya əksi) alıcı yarana bilər.
     *
     * `$only` verilibsə yalnız o hesab yoxlanılır və blok vəziyyəti nəzərə
     * alınmır: admin konkret adama yazanda blokun səbəbini bildirmək istəyə
     * bilər. Kütləvi göndərişdə bloklananlar siyahıdan çıxır.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function recipients(string $type, ?User $only = null)
    {
        $column = config("onesignal.types.{$type}.preference");

        return User::query()
            ->when(
                $only,
                fn ($query) => $query->whereKey($only->id),
                fn ($query) => $query->where('is_blocked', false)
            )
            // Sətri olmayan istifadəçi də daxildir: `firstOrCreate` defoltları
            // `true`-dur, yəni sətrin yoxluğu "hamısı açıqdır" deməkdir.
            ->whereDoesntHave('notificationPreference', function ($query) use ($column) {
                // Şərtlər mütləq mötərizəyə alınmalıdır: `whereDoesntHave`
                // alt sorğuya `notification_preferences.user_id = users.id`
                // bağlantısını əlavə edir və qruplaşdırılmamış `orWhere`
                // həmin bağlantıdan qopardı — nəticədə cədvəldə `marketing=0`
                // olan bir sətir bütün istifadəçiləri siyahıdan silərdi.
                $query->where(function ($inner) use ($column) {
                    $inner->where('push_enabled', false);

                    if ($column !== null) {
                        $inner->orWhere($column, false);
                    }
                });
            })
            ->get(['id', 'onesignal_external_id']);
    }

    /**
     * Verilmiş alıcılara admin mətnini göndərir.
     *
     * Ünvanlar bazadan yığılır, OneSignal-ın hazır seqmentindən yox: seqment
     * tətbiqdəki `notification_preferences` açarlarını tanımır və reklamı
     * söndürmüş istifadəçiyə də kampaniya göndərərdi.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return array{ok: bool, aliases: int, devices: int, error: ?string}
     */
    public function deliverTo($users, string $heading, string $content, string $type): array
    {
        if (! $this->configured()) {
            return $this->result(false, 0, 0, 'OneSignal açarları təyin edilməyib.');
        }

        if ($users->isEmpty()) {
            return $this->result(false, 0, 0, 'Bu bildirişi qəbul edəcək alıcı yoxdur — tənzimləmələr söndürülüb.');
        }

        [$aliases, $devices] = $this->addresses($users);

        if ($aliases === [] && $devices === []) {
            return $this->result(false, 0, 0, 'Alıcıların qeydiyyatdan keçmiş cihazı yoxdur.');
        }

        $message = $this->custom($heading, $content, $type);
        $error   = null;
        $sent    = false;

        // Bir sorğuda 2000 ünvan limiti var; alias və subscription id-ləri isə
        // OneSignal ayrı sahələrdə gözləyir, ona görə iki ayrı dəstə gedir.
        foreach ($this->chunks('include_aliases', $aliases) as $target) {
            $result = $this->post(array_merge($target, $message), ['type' => $type, 'target' => 'aliases']);
            $sent   = $sent || $result['ok'];
            $error  = $error ?? $result['error'];
        }

        foreach ($this->chunks('include_subscription_ids', $devices) as $target) {
            $result = $this->post(array_merge($target, $message), ['type' => $type, 'target' => 'devices']);
            $sent   = $sent || $result['ok'];
            $error  = $error ?? $result['error'];
        }

        return $this->result($sent, count($aliases), count($devices), $error);
    }

    /**
     * Bu növ bildirişin neçə ünvana çatacağı — admin panelində göndərmədən
     * əvvəl göstərilir.
     *
     * @return array{aliases: int, devices: int}
     */
    public function reach(string $type): array
    {
        [$aliases, $devices] = $this->addresses($this->recipients($type));

        return ['aliases' => count($aliases), 'devices' => count($devices)];
    }

    /**
     * Alıcı hesabları OneSignal ünvanlarına çevirir: alias-ı olanlar hesab
     * kimi, qalanları cihaz id-ləri kimi ünvanlanır.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function addresses($users): array
    {
        $aliases = $users->pluck('onesignal_external_id')->filter()->values()->all();

        $devices = DeviceToken::whereIn('user_id', $users->whereNull('onesignal_external_id')->pluck('id'))
            ->oneSignal()
            ->pluck('token')
            ->all();

        return [$aliases, $devices];
    }

    /**
     * Ünvan siyahısını OneSignal-ın qəbul etdiyi ölçüdə dəstələrə bölür.
     *
     * @return array<int, array<string, mixed>>
     */
    private function chunks(string $field, array $values): array
    {
        $targets = [];

        foreach (array_chunk($values, 2000) as $chunk) {
            $targets[] = [
                'app_id'         => config('onesignal.app_id'),
                'target_channel' => 'push',
                $field           => $field === 'include_aliases'
                    ? ['external_id' => $chunk]
                    : $chunk,
            ];
        }

        return $targets;
    }

    /**
     * Admin mətni üçün gövdə. `message()`-dən fərqi başlığın konfiqurasiyadan
     * yox, formadan gəlməsi və bütün dillərdə eyni olmasıdır — mətni admin
     * özü yazır, tərcüməsi yoxdur.
     */
    private function custom(string $heading, string $content, string $type): array
    {
        $channel = config("onesignal.types.{$type}.channel", 'fallback');

        return [
            'headings' => $this->everyLanguage($heading),
            'contents' => $this->everyLanguage($content),

            'data' => [
                'type'       => $type,
                'created_at' => now()->toIso8601String(),
            ],

            'existing_android_channel_id' => config("onesignal.channels.{$channel}"),

            // Hər göndəriş ayrı sətirdir: elanlar bir-birini əvəz etməməlidir.
            'ios_badgeType'  => 'Increase',
            'ios_badgeCount' => 1,

            'priority' => $channel === 'marketing' ? 5 : 10,
        ];
    }

    /**
     * Sorğunu göndərir və nəticəni loqa yazır.
     *
     * Heç vaxt istisna atmır — çağıranlar üçün uğursuzluq normal nəticədir.
     *
     * @return array{ok: bool, error: ?string}
     */
    private function post(array $payload, array $context = []): array
    {
        try {
            $response = Http::timeout(config('onesignal.timeout'))
                ->withHeaders([
                    // Yeni format. Köhnə `Basic <key>` açarları da qəbul
                    // edilir, amma yeni açarlar `os_v2_app_...` ilə başlayır
                    // və `Key` sxemini tələb edir.
                    'Authorization' => 'Key '.config('onesignal.rest_api_key'),
                ])
                ->acceptJson()
                ->post(config('onesignal.endpoint'), $payload);
        } catch (\Throwable $e) {
            Log::warning('OneSignal sorğusu alınmadı', $context + ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'OneSignal əlçatmaz oldu.'];
        }

        if ($response->failed()) {
            Log::warning('OneSignal bildirişi rədd etdi', $context + [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['ok' => false, 'error' => 'OneSignal sorğunu rədd etdi ('.$response->status().').'];
        }

        // OneSignal 200 qaytarıb da heç kimə çatmaya bilər — `errors` sahəsi
        // "invalid_aliases" kimi hallarda gəlir və bu, loqda görünməsə
        // səssiz itki olur.
        $errors = $response->json('errors');

        if (! empty($errors)) {
            Log::warning('OneSignal bildirişi qismən çatdı', $context + ['errors' => $errors]);

            return [
                'ok'    => true,
                'error' => is_array($errors) ? implode('; ', Arr::flatten($errors)) : (string) $errors,
            ];
        }

        return ['ok' => true, 'error' => null];
    }

    /** @return array{ok: bool, aliases: int, devices: int, error: ?string} */
    private function result(bool $ok, int $aliases, int $devices, ?string $error): array
    {
        return ['ok' => $ok, 'aliases' => $aliases, 'devices' => $devices, 'error' => $error];
    }

    private function configured(): bool
    {
        return config('onesignal.enabled')
            && config('onesignal.app_id') !== ''
            && config('onesignal.rest_api_key') !== '';
    }

    /**
     * İstifadəçinin tənzimləmələri bu növə icazə verirmi.
     *
     * `push_enabled` baş açardır. Növün öz açarı yoxdursa (hesab bildirişləri)
     * yalnız o yoxlanılır. Sətir ümumiyyətlə yoxdursa hamısı açıq sayılır —
     * `firstOrCreate` defoltları `true`-dur.
     */
    private function allowedBy(User $user, string $type): bool
    {
        $prefs = $user->notificationPreference;
        if (! $prefs) {
            return true;
        }

        if (! $prefs->push_enabled) {
            return false;
        }

        $column = config("onesignal.types.{$type}.preference");

        return $column === null || (bool) $prefs->{$column};
    }

    /**
     * Kimə göndəriləcəyi — əvvəlcə alias, alınmasa cihaz id-ləri.
     *
     * `null` heç bir ünvan tapılmadığını bildirir; boş `include_subscription_ids`
     * göndərmək OneSignal-dan 400 alır.
     */
    private function target(User $user): ?array
    {
        if ($user->onesignal_external_id) {
            return [
                'app_id'          => config('onesignal.app_id'),
                'target_channel'  => 'push',
                'include_aliases' => [
                    'external_id' => [$user->onesignal_external_id],
                ],
            ];
        }

        $ids = DeviceToken::where('user_id', $user->id)
            ->oneSignal()
            ->pluck('token')
            ->all();

        if ($ids === []) {
            return null;
        }

        return [
            'app_id'                   => config('onesignal.app_id'),
            'target_channel'           => 'push',
            'include_subscription_ids' => $ids,
        ];
    }

    /**
     * Bildirişin mətni, qruplaşdırılması və `data` bloku.
     */
    private function message(Notification $notification): array
    {
        $type      = $notification->type;
        $actorName = $notification->actor?->full_name;
        $preview   = $notification->payload['preview'] ?? null;
        $lines     = config("onesignal.headings.{$type}")
            ?? config('onesignal.headings.unknown');

        // Tətbiq ön planda olanda bildirişi özü çəkir (bax `app_startup.dart`)
        // və eyni qaydadan istifadə edir: ad başlıqda, mətn altda. İki yol
        // fərqlənsə, eyni hadisə ekran açıq və bağlı olanda fərqli görünərdi.
        // Ad yoxdursa başlıq ümumiyyətlə göndərilmir — əks halda başlıq və
        // mətn eyni sətir olardı və bildiriş özünü təkrar edərdi.
        $headings = $actorName ? $this->everyLanguage($actorName) : null;
        $contents = $preview ? $this->everyLanguage($preview) : $lines;

        $group   = $this->collapseKey($notification);
        $channel = config("onesignal.types.{$type}.channel", 'fallback');

        return array_filter([
            'headings' => $headings,
            'contents' => $contents,

            'data' => $this->data($notification, $actorName),

            // Tətbiqin özünün yaratdığı kanal (`android_channel_id` panel
            // kateqoriyasının UUID-sidir, bizdə yoxdur). Ad tətbiqdəki
            // `PushChannel` ilə eyni olmalıdır, yoxsa istifadəçinin kanal üzrə
            // susdurma seçimi işləmir.
            'existing_android_channel_id' => config("onesignal.channels.{$channel}"),

            // Eyni söhbətdən gələn ikinci mesaj yenisini yığmır, köhnəsini
            // əvəz edir — trey bir sətir qalır. `android_group` Android-də,
            // `thread_id` iOS-da eyni işi görür.
            'collapse_id'   => $group,
            'android_group' => $group,
            'thread_id'     => $group,

            'ios_badgeType'  => 'Increase',
            'ios_badgeCount' => 1,

            // Marketinqdən başqa hər şey vaxta həssasdır; kampaniya bildirişi
            // cihazı oyatmamalıdır.
            'priority' => $channel === 'marketing' ? 5 : 10,
        ], fn ($value) => $value !== null);
    }

    /**
     * `data` bloku — hamısı sətir, iç-içə heç nə.
     *
     * Bu, təsadüfi deyil: FCM `data` bloku yalnız `Map<String, String>`
     * saxlayır və tətbiqdəki `PushMessage.fromData` da buna görə yazılıb.
     * İç-içə `actor` obyekti göndərilsə, klientdə səssizcə itərdi — ona görə
     * yalnız ad, düz açar kimi gedir.
     */
    private function data(Notification $notification, ?string $actorName): array
    {
        return array_filter([
            'type'            => $notification->type,
            'notification_id' => (string) $notification->id,
            'ride_id'         => $notification->ride_id ? (string) $notification->ride_id : null,
            'booking_id'      => $notification->booking_id ? (string) $notification->booking_id : null,
            'conversation_id' => $notification->conversation_id ? (string) $notification->conversation_id : null,
            'actor_name'      => $actorName,
            'created_at'      => $notification->created_at?->toIso8601String(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Trey sətrinin qrup açarı.
     *
     * Tətbiqdəki `PushMessage.collapseKey` ilə eyni ardıcıllıq — fərqlənsə,
     * ön planda çəkilən bildiriş arxa planda gələnin üstünə yığılmaz.
     */
    private function collapseKey(Notification $notification): string
    {
        if ($notification->conversation_id && $notification->type === 'newMessage') {
            return 'conv_'.$notification->conversation_id;
        }
        if ($notification->booking_id) {
            return 'booking_'.$notification->booking_id;
        }
        if ($notification->ride_id) {
            return 'ride_'.$notification->ride_id;
        }
        if ($notification->conversation_id) {
            return 'conv_'.$notification->conversation_id;
        }

        return 'general';
    }

    /**
     * Tərcüməsi olmayan mətn — ad, mesaj önizləməsi — hər dil üçün eyni.
     *
     * `en` məcburidir: OneSignal tanımadığı dili ona qaytarır.
     */
    private function everyLanguage(string $text): array
    {
        return ['en' => $text, 'az' => $text, 'ru' => $text];
    }
}
