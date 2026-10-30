@extends('layout.app')

@section('meta_title', 'Səfərlərim | Yol Yoldaşı')

@php
  // Status → [mətn, pill sinfi]. Admin panelindəki adlarla eynidir.
  $rideStatus = [
    'active'    => ['Aktiv', 'pill--ok'],
    'inactive'  => ['Passiv', ''],
    'completed' => ['Tamamlanıb', ''],
    'cancelled' => ['Ləğv edilib', 'pill--err'],
  ];
  $bookingStatus = [
    'pending'                => ['Gözləyir', 'pill--warn'],
    'confirmed'              => ['Təsdiqlənib', 'pill--ok'],
    'rejected'               => ['Rədd edilib', 'pill--err'],
    'cancelled_by_passenger' => ['Ləğv etdin', 'pill--err'],
    'cancelled_by_driver'    => ['Sürücü ləğv edib', 'pill--err'],
    'completed'              => ['Tamamlanıb', ''],
  ];
  // Vaxt Bakı saatı ilə — sütun UTC-dədir (bax `LocalTime`).
  $when = fn ($ride) => $ride?->departureLocal()?->format('d.m.Y, H:i') ?? '—';
@endphp

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Səfərlərim',
  'title' => '<span class="grad-text">Səfərlərim</span>',
  'lead'  => 'Elanların və rezervasiyaların. Yeni elan və bron mobil tətbiqdən edilir.',
])

<section class="section section--tight">
  <div class="container acct">

    @include('frontend.account._layout', ['active' => 'rides'])

    <div class="acct-main">

      <div class="form-card">
        <div class="form-card__head"><h2>Rezervasiyalarım</h2><p>Sərnişin kimi bron etdiyin yerlər.</p></div>

        @if ($bookings->isEmpty())
          <div class="acct-empty">
            <b>Hələ rezervasiya yoxdur</b>
            Yer bron etmək üçün mobil tətbiqi yüklə.
            <div style="margin-top:16px"><a href="{{ url('/') }}#yukle" class="btn btn--brand">Tətbiqi yüklə</a></div>
          </div>
        @else
          <div class="acct-list">
            @foreach ($bookings as $booking)
              @php [$label, $class] = $bookingStatus[$booking->status] ?? [$booking->status, '']; @endphp
              <div class="acct-row">
                <div>
                  <b>{{ $booking->ride?->fromCity?->name ?? '—' }} → {{ $booking->ride?->toCity?->name ?? '—' }}</b>
                  <small>{{ $when($booking->ride) }} · {{ $booking->seats }} yer · {{ number_format((float) $booking->total_price, 2) }} ₼</small>
                </div>
                <span class="pill {{ $class }}">{{ $label }}</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="form-card">
        <div class="form-card__head"><h2>Elanlarım</h2><p>Sürücü kimi yerləşdirdiyin səfərlər.</p></div>

        @if ($rides->isEmpty())
          <div class="acct-empty">
            <b>Hələ elan yoxdur</b>
            Elan yerləşdirmək üçün mobil tətbiqdə sürücü rejiminə keç.
          </div>
        @else
          <div class="acct-list">
            @foreach ($rides as $ride)
              @php [$label, $class] = $rideStatus[$ride->status] ?? [$ride->status, '']; @endphp
              <div class="acct-row">
                <div>
                  <b>{{ $ride->fromCity?->name ?? '—' }} → {{ $ride->toCity?->name ?? '—' }}</b>
                  <small>{{ $when($ride) }} · {{ $ride->booked_seats }}/{{ $ride->total_seats }} yer · {{ number_format((float) $ride->price_per_seat, 2) }} ₼ · {{ $ride->bookings_count }} müraciət</small>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                  <span class="pill {{ $class }}">{{ $label }}</span>
                  <a href="{{ route('ride.share', $ride) }}" class="btn btn--ghost" style="padding:8px 14px;font-size:14px">Paylaş</a>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

    </div>
  </div>
</section>
</main>
@endsection
