<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RideResource;
use App\Models\RecentSearch;
use App\Models\Ride;
use App\Services\BookingService;
use App\Services\ReferralService;
use App\Services\RideMatchService;
use App\Support\LocalTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RideController extends Controller
{
    /**
     * Elan cavabının tam dəsti.
     *
     * `driver.driverProfile` siyahıya sonradan əlavə olundu: təsdiqlənmiş
     * sürücü nişanı artıq axtarış nəticəsində göstərilir və onsuz hər kart
     * üçün ayrıca sorğu gedərdi (N+1).
     */
    private const RELATIONS = [
        'driver.stats', 'driver.driverProfile', 'vehicle', 'fromCity', 'toCity',
    ];

    /**
     * Axtarış. Marşrut ixtiyaridir — şəhərsiz sorğu bütün aktiv elanları qaytarır
     * və index(status, departure_at) ilə qarşılanır; şəhər veriləndə
     * index(from_city_id, to_city_id, status, departure_at) işə düşür.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'to_city_id'   => ['nullable', 'integer', 'exists:cities,id'],
            'date'         => ['nullable', 'date_format:Y-m-d'],
            'seats'        => ['nullable', 'integer', 'min:1', 'max:4'],
            'sort'         => ['nullable', 'in:departure_at,price_per_seat'],

            // Qadın sərnişinlər üçün. Şəhərlərarası yolda tək qadının əsas
            // maneəsi tanımadığı kişi sürücü ilə yola çıxmaqdır — bu filtr
            // olmadan həmin sərnişin kütləsi tətbiqdən kənarda qalır.
            'driver_gender' => ['nullable', 'in:male,female'],
            'women_only'    => ['nullable', 'boolean'],

            // Sərnişin üçün ən pis hal cavabsız gözləməkdir; ani bron onu
            // tamamilə aradan qaldırır.
            'instant_only' => ['nullable', 'boolean'],

            'verified_only' => ['nullable', 'boolean'],
        ]);

        // `different:from_city_id` burada yazılmır: ConvertEmptyStringsToNull
        // `?to_city_id=` boşluğunu null-a çevirir, hər iki şəhər verilmədikdə
        // qayda gözlənilməz davranır. Müqayisəni açıq şəkildə özümüz edirik.
        if (isset($filters['from_city_id'], $filters['to_city_id'])
            && (int) $filters['from_city_id'] === (int) $filters['to_city_id']) {
            throw ValidationException::withMessages([
                'to_city_id' => 'Gediş və çatış şəhəri eyni ola bilməz.',
            ]);
        }

        $seats = $filters['seats'] ?? 1;

        $rides = Ride::query()
            ->active()
            ->upcoming()
            ->whereRaw('total_seats - booked_seats >= ?', [$seats])
            ->with(self::RELATIONS)
            ->when(
                isset($filters['from_city_id']),
                fn ($q) => $q->where('from_city_id', $filters['from_city_id'])
            )
            ->when(
                isset($filters['to_city_id']),
                fn ($q) => $q->where('to_city_id', $filters['to_city_id'])
            )
            ->when(
                ! empty($filters['date']),
                // Bakı günü, UTC günü yox — bax `LocalTime::dayRange`.
                fn ($q) => $q->whereBetween('departure_at', LocalTime::dayRange($filters['date']))
            )
            ->when(
                ! empty($filters['driver_gender']),
                fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('gender', $filters['driver_gender']))
            )
            ->when(
                ! empty($filters['women_only']),
                fn ($q) => $q->where('women_only', true)
            )
            ->when(
                ! empty($filters['instant_only']),
                fn ($q) => $q->where('instant_booking', true)
            )
            ->when(
                ! empty($filters['verified_only']),
                fn ($q) => $q->whereHas('driver.driverProfile', fn ($p) => $p->where('status', 'approved'))
            )
            // Dəvət mükafatı qazanmış sürücülər başda. Sıralama meyarı bundan
            // sonra gəlir ki, mükafat nəticələrin məntiqini pozmasın —
            // "ucuzdan bahaya" seçimi boost-lu elanların içində də işləyir.
            ->boostedFirst()
            ->orderBy($filters['sort'] ?? 'departure_at')
            ->paginate(20)
            ->withQueryString();

        // Yalnız tam marşrut yadda saxlanılır — recent_searches-də şəhər sütunları
        // NOT NULL-dır, marşrutsuz sorğunu yazmaq cəhdi 500 verərdi.
        //
        // Hesabsız axtarış da mümkündür (endpoint açıqdır), ona görə istifadəçi
        // yoxlanılır: qonağın axtarışını yazacaq sətir sahibi olmazdı.
        if ($request->user() && isset($filters['from_city_id'], $filters['to_city_id'])) {
            $this->rememberSearch($request, $filters, $seats, $rides->total());
        }

        return RideResource::collection($rides);
    }

    /**
     * Yeni elan yalnız dörd sənədi admin tərəfindən təsdiqlənmiş sürücüdən.
     *
     * Sərnişin tanımadığı adamın maşınına minir; sənədləri yoxlanmamış
     * sürücünün elanı axtarışa düşməməlidir. Status `DriverVerification`-dan
     * gəlir. Qaydadan əvvəl verilmiş elanlara toxunulmur.
     */
    private function ensureApprovedDriver($user): void
    {
        $status = $user->driverProfile?->status;

        abort_unless($status === 'approved', 422, match ($status) {
            'pending'  => 'Sənədləriniz yoxlanılır. Təsdiqdən sonra elan verə biləcəksiniz.',
            'rejected' => 'Sənədləriniz rədd edilib. Yenidən yükləyin.',
            default    => 'Elan vermək üçün sürücü sənədlərinizi yükləyin.',
        });
    }

    public function show(Ride $ride)
    {
        return new RideResource($ride->load(self::RELATIONS));
    }

    public function store(
        Request $request,
        RideMatchService $matcher,
        ReferralService $referrals
    ) {
        $user = $request->user();

        abort_unless($user->has_driver_profile, 422, 'Əvvəlcə sürücü profilini yarat.');
        $this->ensureApprovedDriver($user);

        $data = $request->validate([
            'vehicle_id'      => ['required', 'integer', 'exists:vehicles,id'],
            'from_city_id'    => ['required', 'integer', 'exists:cities,id'],
            'to_city_id'      => ['required', 'integer', 'exists:cities,id', 'different:from_city_id'],
            'departure_at'    => ['required', 'date', 'after:now'],
            'total_seats'     => ['required', 'integer', 'min:1', 'max:4'],
            'price_per_seat'  => ['required', 'numeric', 'min:1', 'max:500'],
            'note'            => ['nullable', 'string', 'max:400'],
            'pickup_point'    => ['nullable', 'string', 'max:255'],
            'dropoff_point'   => ['nullable', 'string', 'max:255'],
            'instant_booking' => ['nullable', 'boolean'],
            'women_only'      => ['nullable', 'boolean'],

            // Həftədə bir dəfə rayona gedən sürücü eyni formanı hər dəfə
            // doldurmamalıdır. 1 = yalnız bu səfər.
            'repeat_weeks' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        abort_unless(
            $user->vehicles()->whereKey($data['vehicle_id'])->exists(),
            422,
            'Bu avtomobil sənin deyil.'
        );

        // Yalnız qadın sürücü qadın sərnişin şərti qoya bilər — əks halda
        // nişan sərnişini qoruma əvəzinə aldadardı.
        if (! empty($data['women_only'])) {
            abort_unless(
                $user->gender === 'female',
                422,
                'Yalnız qadın sürücülər bu rejimi seçə bilər.'
            );
        }

        $weeks = (int) ($data['repeat_weeks'] ?? 1);
        unset($data['repeat_weeks']);

        // null göndərilə bilər, ona görə `+` operatoru ilə yox, açıq təyinlə —
        // sütun NOT NULL-dır və null insert xətaya düşərdi.
        $data['instant_booking'] = $data['instant_booking']
            ?? $user->driverProfile?->instant_booking_default
            ?? false;

        $data['women_only'] = (bool) ($data['women_only'] ?? false);

        // Ofsetli və ya ofsetsiz (köhnə tətbiq, Bakı saatı) — hər ikisi eyni UTC
        // anına çevrilir (bax `LocalTime::parse`). `after:now` ofsetsiz sətri
        // UTC kimi oxuyur və Bakıda artıq keçmiş vaxtı buraxa bilərdi, ona görə
        // yoxlama çevrilmiş vaxtla bir də aparılır.
        $departure = LocalTime::parse($data['departure_at']);
        abort_unless($departure->isFuture(), 422, 'Yola düşmə vaxtı gələcəkdə olmalıdır.');

        $first = null;

        for ($week = 0; $week < $weeks; $week++) {
            $row = $data;
            $row['departure_at'] = $departure->copy()->addWeeks($week);

            // `boosted_until` `fillable` deyil (bax `Ride`), ona görə `create()`
            // massivinin içində səssizcə atılırdı və heç bir elan boost
            // almırdı. `make()` + `forceFill()` eyni bir INSERT-dir.
            $ride = $user->rides()->make($row);
            $ride->forceFill([
                'boosted_until' => $referrals->boostFor($user, $row['departure_at']),
            ])->save();
            $first ??= $ride;

            // Bu marşrutu gözləyən sərnişinlərə xəbər. Tələb elanı sisteminin
            // bütün mənası buradadır: sərnişin boş axtarışdan sonra çıxıb
            // getmir, səfər yaranan gün geri çağırılır.
            $matcher->notifyWaitingPassengers($ride);
        }

        // Dəvət olunmuş sürücünün ilk real hərəkəti — dəvət edənə mükafat.
        $referrals->reward($user);

        return (new RideResource($first->load(self::RELATIONS)))
            ->additional(['created_count' => $weeks])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mövcud elanı yeni tarixlə təkrarlayır.
     *
     * Üç addımlı forma ilk dəfə üçün normaldır, təkrar üçün ağırdır — və
     * sürücünün ikinci, üçüncü elanı bazarın sağlamlığının əsas göstəricisidir.
     * Bu endpoint həmin formanı iki toxunuşa endirir.
     */
    public function repeat(
        Request $request,
        Ride $ride,
        RideMatchService $matcher,
        ReferralService $referrals
    ) {
        $user = $request->user();

        abort_unless($ride->driver_id === $user->id, 403, 'Bu səfər sənin deyil.');

        // Təkrar da yeni elandır — sənəd qaydası buna da aiddir.
        $this->ensureApprovedDriver($user);

        $data = $request->validate([
            'departure_at' => ['required', 'date', 'after:now'],
            'repeat_weeks' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        // Avtomobil silinmiş ola bilər — köhnə elandan kor-koranə köçürsək,
        // yaranmayacaq foreign key ilə 500 alardıq.
        abort_unless(
            $user->vehicles()->whereKey($ride->vehicle_id)->exists(),
            422,
            'Bu səfərin avtomobili artıq mövcud deyil.'
        );

        $weeks = (int) ($data['repeat_weeks'] ?? 1);
        $first = null;

        // `store` ilə eyni çevirmə və eyni yoxlama.
        $start = LocalTime::parse($data['departure_at']);
        abort_unless($start->isFuture(), 422, 'Yola düşmə vaxtı gələcəkdə olmalıdır.');

        for ($week = 0; $week < $weeks; $week++) {
            $departure = $start->copy()->addWeeks($week);

            $copy = $user->rides()->make([
                'vehicle_id'      => $ride->vehicle_id,
                'from_city_id'    => $ride->from_city_id,
                'to_city_id'      => $ride->to_city_id,
                'departure_at'    => $departure,
                'total_seats'     => $ride->total_seats,
                'price_per_seat'  => $ride->price_per_seat,
                'note'            => $ride->note,
                'pickup_point'    => $ride->pickup_point,
                'dropoff_point'   => $ride->dropoff_point,
                'instant_booking' => $ride->instant_booking,
                'women_only'      => $ride->women_only,
            ]);

            // `store()`-dakı səbəbdən ayrıca — `create()` bu sahəni atırdı.
            $copy->forceFill([
                'boosted_until' => $referrals->boostFor($user, $departure),
            ])->save();

            $first ??= $copy;
            $matcher->notifyWaitingPassengers($copy);
        }

        return (new RideResource($first->load(self::RELATIONS)))
            ->additional(['created_count' => $weeks])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Ride $ride, RideMatchService $matcher, ReferralService $referrals)
    {
        $user = $request->user();

        abort_unless($ride->driver_id === $user->id, 403, 'Bu səfər sənin deyil.');
        abort_unless($ride->status === 'active', 422, 'Yalnız aktiv səfəri redaktə etmək olar.');

        $data = $request->validate([
            'departure_at'    => ['sometimes', 'date', 'after:now'],
            'total_seats'     => ['sometimes', 'integer', 'min:1', 'max:4'],
            'price_per_seat'  => ['sometimes', 'numeric', 'min:1', 'max:500'],
            'note'            => ['sometimes', 'nullable', 'string', 'max:400'],
            'pickup_point'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'dropoff_point'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'instant_booking' => ['sometimes', 'boolean'],
            'women_only'      => ['sometimes', 'boolean'],
            'status'          => ['sometimes', 'in:active,inactive'],
        ]);

        if (! empty($data['women_only'])) {
            abort_unless(
                $user->gender === 'female',
                422,
                'Yalnız qadın sürücülər bu rejimi seçə bilər.'
            );
        }

        // Yer sayını artıq satılmış yerlərdən aşağı salmaq olmaz.
        if (isset($data['total_seats'])) {
            abort_if(
                $data['total_seats'] < $ride->booked_seats,
                422,
                'Yer sayı artıq təsdiqlənmiş rezervasiyalardan az ola bilməz.'
            );
        }

        if (isset($data['departure_at'])) {
            // `store` ilə eyni çevirmə və eyni yoxlama.
            $data['departure_at'] = LocalTime::parse($data['departure_at']);
            abort_unless($data['departure_at']->isFuture(), 422, 'Yola düşmə vaxtı gələcəkdə olmalıdır.');
        }

        $seatsBefore = $ride->seats_left;

        $ride->update($data);

        // Boost elanın yola düşmə vaxtında bitir. Vaxt dəyişəndə köhnə sərhəd
        // qalsaydı, sonraya çəkilmiş elan yola düşməmiş boost-u itirərdi.
        if (isset($data['departure_at'])) {
            $ride->forceFill([
                'boosted_until' => $referrals->boostFor($user, $ride->departure_at),
            ])->save();
        }

        // Yer sayı artdısa və ya tarix dəyişdisə, elan əvvəl uyğun gəlməyən
        // sərnişinlərə indi uyğun gələ bilər. `notifyWaitingPassengers` özü
        // gündə bir dəfədən çox yazmır, ona görə redaktə spam yaratmır.
        if ($ride->fresh()->seats_left > $seatsBefore || isset($data['departure_at'])) {
            $matcher->notifyWaitingPassengers($ride->fresh());
        }

        return new RideResource($ride->load(self::RELATIONS));
    }

    /** Səfərin ləğvi — sətir silinmir, status dəyişir. */
    public function destroy(Request $request, Ride $ride, BookingService $bookings)
    {
        abort_unless($ride->driver_id === $request->user()->id, 403, 'Bu səfər sənin deyil.');
        abort_unless(in_array($ride->status, ['active', 'inactive'], true), 422, 'Bu səfər artıq bağlanıb.');

        foreach ($ride->bookings()->whereIn('status', ['pending', 'confirmed'])->get() as $booking) {
            $bookings->cancel($booking, $request->user(), 'Sürücü səfəri ləğv etdi.');
        }

        $ride->update(['status' => 'cancelled']);

        return new RideResource($ride->fresh());
    }

    public function complete(Request $request, Ride $ride, BookingService $bookings)
    {
        return new RideResource($bookings->completeRide($ride, $request->user()));
    }

    /** Sürücünün öz elanları. */
    public function mine(Request $request)
    {
        $rides = $request->user()->rides()
            ->with(['vehicle', 'fromCity', 'toCity'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('departure_at')
            ->paginate(20);

        return RideResource::collection($rides);
    }

    private function rememberSearch(Request $request, array $filters, int $seats, int $resultCount): void
    {
        RecentSearch::updateOrCreate(
            [
                'user_id'      => $request->user()->id,
                'from_city_id' => $filters['from_city_id'],
                'to_city_id'   => $filters['to_city_id'],
            ],
            [
                'searched_date' => $filters['date'] ?? null,
                'seats'         => $seats,
                'result_count'  => min($resultCount, 65535),
                'searched_at'   => now(),
            ]
        );
    }
}
