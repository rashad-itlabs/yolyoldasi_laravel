@extends('layout.app')

@php
  // Soyad qısaldılır: link açıq internetdədir və istənilən qrupa düşə bilər.
  // Tətbiqin içində tam ad görünür, burada isə "Rəşad M." kifayətdir.
  // `driver` null ola bilər — hesab silinib. Onda ad əvəzinə "Sürücü" yazılır.
  $parts   = preg_split('/\s+/', trim((string) $ride->driver?->full_name)) ?: [];
  $driver  = count($parts) > 1
      ? $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.'
      : ($parts[0] ?? 'Sürücü');

  $route   = $ride->fromCity->name.' → '.$ride->toCity->name;
  $when    = $ride->departure_at->translatedFormat('d F, H:i');
  $price   = rtrim(rtrim(number_format((float) $ride->price_per_seat, 2, '.', ''), '0'), '.');
  // `stats` sətri olmaya bilər (çox köhnə hesab) — `?->` olmadan bu sətir
  // səhifəni tamamilə sındırardı.
  $rating  = (float) ($ride->driver?->stats?->driver_rating ?? 0);
  $trips   = (int) ($ride->driver?->stats?->driver_trip_count ?? 0);
  $verified = $ride->driver?->driverProfile?->status === 'approved';
@endphp

@section('meta_title', e($route.' — '.$when.' | Yol Yoldaşı'))
@section('meta_description', e($route.' səfəri, '.$when.'. Yer başına '.$price.' ₼. Yol Yoldaşı tətbiqində bron et.'))

@section('content')
<main>

<section class="page-head">
  <div class="hero__bg" aria-hidden="true"><div class="hero__glow hero__glow--1"></div></div>
  <div class="hero__grid" aria-hidden="true"></div>
  <div class="container">
    <div class="crumbs"><a href="{{ url('/') }}">Ana səhifə</a><span>/</span><span>Səfər</span></div>

    <h1 data-split>{{ $route }}</h1>
    <p class="lead">{{ $when }} · yer başına <strong>{{ $price }} ₼</strong></p>

    @unless ($isOpen)
      <div style="margin-top:26px">
        <span class="update-chip">Bu səfər artıq keçərli deyil — tətbiqdə oxşar marşrutlara baxın</span>
      </div>
    @endunless
  </div>
</section>

<section class="section section--tight">
  <div class="container" style="max-width:720px">

    <article class="prose" style="padding:28px;border-radius:18px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">

      <h3 style="margin-top:0">Marşrut</h3>
      <ul>
        <li><strong>Çıxış:</strong> {{ $ride->fromCity->name }}@if ($ride->pickup_point) — {{ $ride->pickup_point }}@endif</li>
        <li><strong>Çatış:</strong> {{ $ride->toCity->name }}@if ($ride->dropoff_point) — {{ $ride->dropoff_point }}@endif</li>
        <li><strong>Vaxt:</strong> {{ $when }}</li>
        <li><strong>Boş yer:</strong> {{ $ride->seats_left }} / {{ $ride->total_seats }}</li>
        <li><strong>Qiymət:</strong> {{ $price }} ₼ (yer başına)</li>
      </ul>

      <h3>Sürücü</h3>
      <ul>
        <li><strong>{{ $driver }}</strong>@if ($verified) · təsdiqlənmiş sənədlər @endif</li>
        @if ($trips > 0)
          {{-- Reytinq direktiv yox, ifadə ilə qoşulur: `səfər@if` yazılışında
               Blade direktivi tanımır (`@`-dən əvvəl hərf var) və `@if` mətn
               kimi qalıb aşağıdakı `@else`-i sahibsiz qoyurdu. --}}
          <li>{{ $trips }} səfər{{ $rating > 0 ? ' · '.number_format($rating, 1).' ★' : '' }}</li>
        @else
          <li>Yeni sürücü</li>
        @endif
        @if ($ride->vehicle)
          {{-- Qeydiyyat nişanı qəsdən yoxdur: açıq səhifədə göstərilməməlidir. --}}
          <li>{{ $ride->vehicle->brand }} {{ $ride->vehicle->model }}@if ($ride->vehicle->color) · {{ $ride->vehicle->color }}@endif</li>
        @endif
      </ul>

      @if ($ride->instant_booking || $ride->women_only)
        <h3>Şərtlər</h3>
        <ul>
          @if ($ride->instant_booking)<li>Ani bron — təsdiq gözləmədən yer tutulur</li>@endif
          @if ($ride->women_only)<li>Yalnız qadın sərnişinlər üçün</li>@endif
        </ul>
      @endif

      @if ($ride->note)
        <h3>Sürücünün qeydi</h3>
        <p>{{ $ride->note }}</p>
      @endif

      <div style="margin-top:32px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="{{ url('/') }}#yukle" class="btn btn--brand btn--lg">Tətbiqi yüklə və bron et</a>
      </div>

      <p style="margin-top:18px;font-size:14px;opacity:.7">
        Bron, yazışma və sürücünün nömrəsi yalnız tətbiqdə açılır.
      </p>
    </article>

  </div>
</section>
</main>
@endsection
