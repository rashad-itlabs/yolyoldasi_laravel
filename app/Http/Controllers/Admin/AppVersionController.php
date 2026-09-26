<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mobil tətbiqin versiya qaydası — hər iki platforma bir formada.
 *
 * `min_build`-u qaldırmaq bütün köhnə quraşdırmaları bloklayır, ona görə
 * dəyişiklik jurnala yazılır və formada açıq xəbərdarlıq var.
 */
class AppVersionController extends Controller
{
    /** Platforma => [başlıq, izah]. */
    public const PLATFORMS = [
        'android' => ['Android', 'Google Play — versionCode'],
        'ios'     => ['iOS', 'App Store — CFBundleVersion'],
    ];

    public function edit()
    {
        return view('admin.content.app-version', [
            'platforms' => self::PLATFORMS,
            'rows'      => $this->rows(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];

        foreach (array_keys(self::PLATFORMS) as $platform) {
            $rules[$platform] = ['required', 'array'];
            $rules[$platform.'.min_build'] = ['required', 'integer', 'min:1', 'max:2000000000'];
            $rules[$platform.'.min_version'] = ['required', 'string', 'max:20'];
            $rules[$platform.'.latest_build'] = ['required', 'integer', 'min:1', 'max:2000000000'];
            $rules[$platform.'.latest_version'] = ['required', 'string', 'max:20'];
            $rules[$platform.'.store_url'] = ['nullable', 'url', 'max:300'];
            $rules[$platform.'.message_az'] = ['nullable', 'string', 'max:300'];
            $rules[$platform.'.message_ru'] = ['nullable', 'string', 'max:300'];
            $rules[$platform.'.message_en'] = ['nullable', 'string', 'max:300'];
        }

        $data = $request->validate($rules, [], $this->attributeNames());

        // `latest_build` "mağazadakı sonuncu buraxılış" deməkdir; ondan böyük
        // `min_build` heç vaxt yenilənə bilməyəcək bir divar qurardı —
        // istifadəçi mağazaya gedər, ən yeni versiyanı yükləyər və yenə
        // bloklanmış qalardı.
        foreach (array_keys(self::PLATFORMS) as $platform) {
            if ((int) $data[$platform]['min_build'] > (int) $data[$platform]['latest_build']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        $platform.'.min_build' => 'Minimum build mağazadakı sonuncu build-dən böyük ola bilməz — istifadəçi yeniləyəndən sonra da bloklu qalardı.',
                    ]);
            }
        }

        DB::transaction(function () use ($request, $data) {
            $rows = $this->rows();
            $changed = [];

            foreach (array_keys(self::PLATFORMS) as $platform) {
                $input = $data[$platform];

                // Açıq siyahı: boş sahə sorğudan tamamilə düşəndə də köhnə
                // dəyər qalmasın, `null` yazılsın.
                $fields = [
                    'min_build'      => (int) $input['min_build'],
                    'min_version'    => $input['min_version'],
                    'latest_build'   => (int) $input['latest_build'],
                    'latest_version' => $input['latest_version'],
                    'store_url'      => $input['store_url'] ?? null,
                    'message_az'     => $input['message_az'] ?? null,
                    'message_ru'     => $input['message_ru'] ?? null,
                    'message_en'     => $input['message_en'] ?? null,
                    // İşarələnməmiş checkbox sorğuya heç düşmür.
                    'is_enabled'     => $request->boolean($platform.'.is_enabled'),
                ];

                $row = $rows[$platform];

                foreach ($fields as $key => $new) {
                    $old = $row->{$key};

                    if ((string) $old !== (string) $new) {
                        $changed[$platform.'.'.$key] = ['from' => $old, 'to' => $new];
                    }
                }

                $row->fill($fields);
                $row->updated_at = now();
                $row->save();
            }

            if ($changed) {
                AdminActivityLog::record('app_version.updated', null, $changed);
            }
        });

        return back()->with('success', 'Tətbiq versiyası yeniləndi.');
    }

    /**
     * Hər platforma üçün sətir — yoxdursa yaradılır.
     *
     * Miqrasiya hər ikisini yazır, amma cədvəl əl ilə təmizlənsə forma yenə
     * açılmalıdır: boş səhifə admin üçün çıxılmaz vəziyyətdir.
     *
     * @return array<string, AppVersion>
     */
    private function rows(): array
    {
        $rows = [];

        foreach (array_keys(self::PLATFORMS) as $platform) {
            $rows[$platform] = AppVersion::firstOrCreate(
                ['platform' => $platform],
                ['updated_at' => now()]
            );
        }

        return $rows;
    }

    /** @return array<string, string> */
    private function attributeNames(): array
    {
        $names = [];

        foreach (self::PLATFORMS as $platform => [$label]) {
            $names[$platform.'.min_build'] = $label.' minimum build';
            $names[$platform.'.min_version'] = $label.' minimum versiya';
            $names[$platform.'.latest_build'] = $label.' sonuncu build';
            $names[$platform.'.latest_version'] = $label.' sonuncu versiya';
            $names[$platform.'.store_url'] = $label.' mağaza linki';
            $names[$platform.'.message_az'] = $label.' mətn (az)';
            $names[$platform.'.message_ru'] = $label.' mətn (ru)';
            $names[$platform.'.message_en'] = $label.' mətn (en)';
        }

        return $names;
    }
}
