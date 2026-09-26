<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OTP kodunu cihaza çatdırır.
 *
 * Bir sinifdir, interfeys + bir neçə implementasiya deyil: provayderlərin
 * API-ləri arasındakı fərq yalnız parametr adlarıdır və bu fərq `config/sms.php`
 * şablonu ilə tam örtülür. Ayrı siniflər yaratmaq həm də service provider-də
 * binding tələb edərdi — bu layihədə `AuthController` sinfi birbaşa type-hint
 * edir və Laravel onu heç bir qeydiyyat olmadan həll edir.
 *
 * **Heç vaxt istisna atmır.** Provayder əlçatmaz olanda giriş axını
 * dayanmamalıdır: kod bazada onsuz da yaradılıb, istifadəçi "yenidən göndər"
 * düyməsi ilə təkrar cəhd edə bilər. Nəticə `bool` kimi qayıdır ki, çağıran
 * tərəf istəsə istifadəçiyə xəbərdarlıq göstərsin.
 */
class SmsSender
{
    /** Kodu göndərir. Uğurlu olubsa `true`. */
    public function sendCode(string $phone, string $code): bool
    {
        $text = str_replace(':code', $code, (string) config('sms.template'));

        return $this->send($phone, $text);
    }

    public function send(string $phone, string $text): bool
    {
        return match ((string) config('sms.driver')) {
            'http'  => $this->sendOverHttp($phone, $text),
            'log'   => $this->logOnly($phone, $text),
            default => false,
        };
    }

    /** Provayder qoşulubmu — `AuthController` kodu cavabda saxlayıb-saxlamamağı buna görə qərarlaşdırır. */
    public function configured(): bool
    {
        return config('sms.driver') === 'http' && config('sms.url') !== '';
    }

    private function sendOverHttp(string $phone, string $text): bool
    {
        $url = (string) config('sms.url');

        if ($url === '') {
            Log::warning('SMS göndərilmədi: SMS_URL boşdur.');

            return false;
        }

        $payload = array_merge(
            (array) config('sms.extra'),
            [
                (string) config('sms.param_phone') => $this->formatPhone($phone),
                (string) config('sms.param_text')  => $text,
            ]
        );

        $senderParam = (string) config('sms.param_sender');
        if ($senderParam !== '' && config('sms.sender') !== '') {
            $payload[$senderParam] = (string) config('sms.sender');
        }

        try {
            $request = Http::timeout((int) config('sms.timeout'))->acceptJson();

            if (config('sms.auth_header') !== '') {
                $request = $request->withHeaders([
                    'Authorization' => (string) config('sms.auth_header'),
                ]);
            }

            $response = config('sms.method') === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);
        } catch (\Throwable $e) {
            // Nömrə loqa yazılmır: loq faylı tətbiqin ən az qorunan yeridir
            // və orada nömrə + vaxt cütü hesabın kimliyini açır.
            Log::warning('SMS provayderi əlçatmaz oldu', ['error' => $e->getMessage()]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('SMS provayderi sorğunu rədd etdi', [
                'status' => $response->status(),
                'body'   => mb_substr($response->body(), 0, 500),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Lokal iş rejimi — kod loqa yazılır.
     *
     * Bu, `OTP_EXPOSE_CODE=true`-dan fərqlidir: kod cavabda getmir, yalnız
     * serverin loqunda görünür. Yəni tətbiqi sınamaq üçün kifayətdir, amma
     * internetdən kimsə onu oxuya bilmir.
     */
    private function logOnly(string $phone, string $text): bool
    {
        Log::info('SMS (log rejimi)', ['phone' => $phone, 'text' => $text]);

        return true;
    }

    private function formatPhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D/', '', $phone);

        return match ((string) config('sms.phone_format')) {
            'plain' => $digits,
            'local' => str_starts_with($digits, '994') ? '0'.substr($digits, 3) : $digits,
            default => '+'.$digits,
        };
    }
}
