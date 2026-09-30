<?php

namespace App\Http\Controllers;

use App\Models\Ride;

/**
 * Səfərin ictimai veb səhifəsi — `/r/{id}`.
 *
 * Azərbaycanda şəhərlərarası yol razılaşmaları hələ də WhatsApp və Telegram
 * qruplarında olur. Sürücünün ən təbii böyümə hərəkəti elanını həmin qrupa
 * atmaqdır; bu səhifə olmadan onun ata biləcəyi heç nə yox idi.
 *
 * Səhifə tətbiqi əvəz etmir və qəsdən etmir: bron yalnız tətbiqdə olur.
 * Səhifənin işi linkə toxunanı elanın nə olduğuna inandırmaq və mağazaya
 * yönəltməkdir.
 *
 * Telefon nömrəsi, avtomobilin qeydiyyat nişanı və sürücünün soyadı burada
 * **göstərilmir** — link açıq internetdədir və istənilən qrupa düşə bilər.
 */
class RideShareController extends Controller
{
    public function show(Ride $ride)
    {
        $ride->load(['driver.stats', 'driver.driverProfile', 'vehicle', 'fromCity', 'toCity']);

        // Ləğv edilmiş və ya vaxtı keçmiş elan silinmir, sadəcə "bu səfər
        // artıq keçərli deyil" halında göstərilir: köhnə linkə toxunan adam
        // 404 yox, işləyən bir səhifə görməlidir — mağaza düyməsi orada da var.
        // Sürücünün hesabı silinibsə (`driver` null), elan da bağlı sayılır.
        $isOpen = $ride->status === 'active'
            && $ride->departure_at->isFuture()
            && $ride->driver !== null;

        return view('frontend.ride', [
            'ride'   => $ride,
            'isOpen' => $isOpen,
        ]);
    }
}
