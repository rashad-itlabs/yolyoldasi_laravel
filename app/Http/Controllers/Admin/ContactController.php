<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Əlaqə məlumatları və sosial linklər — saytın altlığı və əlaqə səhifəsi
 * bu dəyərlərdən oxuyur.
 */
class ContactController extends Controller
{
    /** Sahə => [etiket, qrup, doğrulama qaydası, izah]. */
    public const FIELDS = [
        'contact.email_general' => ['Ümumi e-poçt', 'contact', ['nullable', 'email', 'max:190'], 'Əməkdaşlıq və media sorğuları'],
        'contact.email_support' => ['Dəstək e-poçtu', 'contact', ['nullable', 'email', 'max:190'], 'Hesab, bron və texniki problemlər'],
        'contact.email_privacy' => ['Məxfilik e-poçtu', 'contact', ['nullable', 'email', 'max:190'], 'Məlumatların silinməsi və hüquqlar'],
        'contact.phone'         => ['Telefon', 'contact', ['nullable', 'string', 'max:40'], 'Boş buraxsanız saytda göstərilmir'],
        'contact.address'       => ['Ünvan', 'contact', ['nullable', 'string', 'max:190'], 'Altlıqda və məxfilik səhifəsində'],
        'contact.response_time' => ['Cavab müddəti mətni', 'contact', ['nullable', 'string', 'max:300'], 'Əlaqə səhifəsinin başlığı altında'],
        'contact.urgent_note'   => ['Təcili hal qeydi', 'contact', ['nullable', 'string', 'max:300'], 'Əlaqə səhifəsinin yan panelində'],
        'site.footer_about'     => ['Altlıq təsviri', 'site', ['nullable', 'string', 'max:500'], 'Loqonun altındakı qısa mətn'],
        'social.instagram'      => ['Instagram linki', 'social', ['nullable', 'url', 'max:190'], 'Boş buraxsanız ikon gizlədilir'],
        'social.facebook'       => ['Facebook linki', 'social', ['nullable', 'url', 'max:190'], 'Boş buraxsanız ikon gizlədilir'],
        'social.whatsapp'       => ['WhatsApp linki', 'social', ['nullable', 'url', 'max:190'], 'Boş buraxsanız ikon gizlədilir'],
    ];

    public function edit()
    {
        return view('admin.content.contact', [
            'fields' => self::FIELDS,
            'values' => Settings::all(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::FIELDS as $key => [, , $rule]) {
            $rules[$this->inputName($key)] = $rule;
        }

        $data = $request->validate($rules);

        DB::transaction(function () use ($data) {
            $changed = [];

            foreach (self::FIELDS as $key => [$label, $group]) {
                $new = $data[$this->inputName($key)] ?? null;
                $old = Settings::get($key);

                if ((string) $new !== (string) $old) {
                    $changed[$key] = ['from' => $old, 'to' => $new];
                }

                Settings::set($key, $new, $group);
            }

            if ($changed) {
                AdminActivityLog::record('contact.updated', null, $changed);
            }
        });

        return back()->with('success', 'Əlaqə məlumatları yeniləndi.');
    }

    /** `contact.email_general` → `contact_email_general` (forma sahəsi adı). */
    private function inputName(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}
