<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>document.documentElement.classList.add('has-js');</script>
@php
  use App\Support\Settings;

  // Bölmə keçidləri yalnız ana səhifədə mövcuddur. Orada nisbi anker
  // saxlanılır ki, main.js-in hamar sürüşməsi və aktiv bölmə vurğusu
  // (hər ikisi `href^="#"` seçicisinə baxır) işləsin; başqa səhifədə isə
  // tam ünvan verilir, yoxsa keçid heç nəyə aparmır.
  $onHome  = request()->is('/');
  $section = fn (string $id) => ($onHome ? '' : url('/')).'#'.$id;

  $emailGeneral = Settings::get('contact.email_general', 'info@yolyodasi.az');
  $emailSupport = Settings::get('contact.email_support', 'destek@yolyodasi.az');
  $socials = array_filter([
    'Instagram' => Settings::get('social.instagram'),
    'Facebook'  => Settings::get('social.facebook'),
    'WhatsApp'  => Settings::get('social.whatsapp'),
  ]);
@endphp
<title>@hasSection('meta_title')@yield('meta_title')@else Yol Yoldaşı — Şəhərlərarası səfər yoldaşı tap | yolyodasi.az @endif</title>
<meta name="description" content="@hasSection('meta_description')@yield('meta_description')@else Yol Yoldaşı ilə şəhərlərarası səfərini bölüş. Sürücü marşrut elanı verir, sərnişin yer bron edir. Reytinq, sənəd təsdiqi və bildirişlərlə təhlükəsiz, sərfəli səfər. @endif">
<meta name="theme-color" content="#081226">
<meta name="keywords" content="yol yoldaşı, şəhərlərarası, sərnişin, sürücü, marşrut, bron, Bakı Gəncə, carpooling Azərbaycan">
<link rel="canonical" href="https://yolyodasi.az/">

<meta property="og:type" content="website">
<meta property="og:locale" content="az_AZ">
<meta property="og:site_name" content="Yol Yoldaşı">
<meta property="og:title" content="Yol Yoldaşı — Şəhərlərarası səfəri bölüş, yolu ucuzlaşdır">
<meta property="og:description" content="Sürücü elan verir, sərnişin yer bron edir — sadə, təhlükəsiz, ucuz.">
<meta property="og:url" content="https://yolyodasi.az/">
<meta property="og:image" content="https://yolyodasi.az/images/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="{{asset('assets')}}/images/favicon.svg" type="image/svg+xml">
<link rel="alternate icon" href="{{asset('assets')}}/images/favicon-32.png">
<link rel="apple-touch-icon" href="{{asset('assets')}}/images/apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{asset('assets')}}/css/style.css">

@verbatim
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"MobileApplication",
  "name":"Yol Yoldaşı",
  "url":"https://yolyodasi.az/",
  "operatingSystem":"Android, iOS",
  "applicationCategory":"TravelApplication",
  "inLanguage":"az",
  "offers":{"@type":"Offer","price":"0","priceCurrency":"AZN"},
  "description":"Şəhərlərarası səfər paylaşımı platforması: sürücülər marşrut elanı verir, sərnişinlər yer bron edir."
}
</script>
@endverbatim
</head>
<body>

<!-- ================= NAV ================= -->
<header class="nav" id="nav">
  <div class="container nav__inner">
    <a href="{{asset('')}}" class="brand" aria-label="Yol Yoldaşı — ana səhifə">
      <svg class="brand__mark" viewBox="0 0 48 48" aria-hidden="true">
        <defs><linearGradient id="bm" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#00B389"/><stop offset="55%" stop-color="#05D9A5"/><stop offset="100%" stop-color="#7BF3D0"/>
        </linearGradient></defs>
        <rect width="48" height="48" rx="14" fill="url(#bm)"/>
        <path class="brand__route" d="M15 37c0-7 4.5-8.2 9-9.4s9-2 9-8.2" fill="none" stroke="#081226" stroke-width="4.2" stroke-linecap="round"/>
        <circle cx="15" cy="37" r="3.8" fill="#081226"/><circle cx="15" cy="37" r="1.6" fill="#05D9A5"/>
        <path d="M33 8.5c-3.1 0-5.7 2.5-5.7 5.7 0 4.1 5.7 9.6 5.7 9.6s5.7-5.5 5.7-9.6c0-3.2-2.6-5.7-5.7-5.7z" fill="#081226"/>
        <circle cx="33" cy="14.2" r="2.3" fill="#05D9A5"/>
      </svg>
      <span class="brand__name">Yol <em>Yoldaşı</em></span>
    </a>

    <ul class="nav__links">
      <li><a class="nav__link" href="{{ $section('nece-isleyir') }}">Necə işləyir</a></li>
      <li><a class="nav__link" href="{{ $section('rollar') }}">Sürücü &amp; Sərnişin</a></li>
      <li><a class="nav__link" href="{{ $section('imkanlar') }}">İmkanlar</a></li>
      <li><a class="nav__link" href="{{ $section('tehlukesizlik') }}">Təhlükəsizlik</a></li>
      <li><a class="nav__link" href="{{ $section('faq') }}">FAQ</a></li>
      <li><a class="nav__link {{ request()->routeIs('about') ? 'is-active' : '' }}" href="{{ route('about') }}">Haqqımızda</a></li>
    </ul>

    <div class="nav__actions">
      <a href="{{ route('contact') }}" class="btn btn--ghost">Əlaqə</a>
      <a href="{{ $section('yukle') }}" class="btn btn--brand">Tətbiqi yüklə</a>
      <button class="nav__burger" id="burger" aria-label="Menyu" aria-expanded="false" aria-controls="mobileMenu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
  <div class="nav__progress" id="navProgress"></div>
</header>

<nav class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <a href="{{ $section('nece-isleyir') }}">Necə işləyir</a>
  <a href="{{ $section('rollar') }}">Sürücü &amp; Sərnişin</a>
  <a href="{{ $section('imkanlar') }}">İmkanlar</a>
  <a href="{{ $section('tehlukesizlik') }}">Təhlükəsizlik</a>
  <a href="{{ $section('faq') }}">FAQ</a>
  <a href="{{ route('about') }}">Haqqımızda</a>
  <a href="{{ route('contact') }}">Əlaqə</a>
  <a href="{{ $section('yukle') }}" class="btn btn--brand btn--lg">Tətbiqi yüklə</a>
</nav>

@yield('content')


<!-- ================= FOOTER ================= -->
<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <div class="footer__about">
        <a href="index.html" class="brand">
          <svg class="brand__mark" viewBox="0 0 48 48" aria-hidden="true">
            <defs><linearGradient id="bf" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#00B389"/><stop offset="55%" stop-color="#05D9A5"/><stop offset="100%" stop-color="#7BF3D0"/>
            </linearGradient></defs>
            <rect width="48" height="48" rx="14" fill="url(#bf)"/>
            <path d="M15 37c0-7 4.5-8.2 9-9.4s9-2 9-8.2" fill="none" stroke="#081226" stroke-width="4.2" stroke-linecap="round"/>
            <circle cx="15" cy="37" r="3.8" fill="#081226"/><circle cx="15" cy="37" r="1.6" fill="#05D9A5"/>
            <path d="M33 8.5c-3.1 0-5.7 2.5-5.7 5.7 0 4.1 5.7 9.6 5.7 9.6s5.7-5.5 5.7-9.6c0-3.2-2.6-5.7-5.7-5.7z" fill="#081226"/>
            <circle cx="33" cy="14.2" r="2.3" fill="#05D9A5"/>
          </svg>
          <span class="brand__name" style="color:#fff">Yol <em>Yoldaşı</em></span>
        </a>
        <p>{{ Settings::get('site.footer_about', 'Azərbaycanda şəhərlərarası səfər paylaşımı platforması. Boş oturacağı dəyərləndir, yol xərcini bölüş.') }}</p>
        @if ($socials)
          <div class="socials" style="margin-top:24px">
            @isset($socials['Instagram'])
              <a href="{{ $socials['Instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
            @endisset
            @isset($socials['Facebook'])
              <a href="{{ $socials['Facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V5.5h-3c-2.2 0-4 1.8-4 4V12H7.5v3.5H10V22h3.5v-6.5H16L16.5 12H13.5V9.5c0-.3.2-.5.5-.5z"/></svg></a>
            @endisset
            @isset($socials['WhatsApp'])
              <a href="{{ $socials['WhatsApp'] }}" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1012 2zm5.6 14.2c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.6-1.1-4.3-3.8-4.5-4-.1-.2-1-1.4-1-2.6 0-1.2.6-1.8.9-2 .2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.3 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.5 1.9 1 .9 1.9 1.2 2.2 1.3.2.1.4.1.6-.1l.8-1c.2-.2.3-.2.6-.1l1.8.9c.3.1.5.2.5.3.1.2.1.6-.1 1.1z"/></svg></a>
            @endisset
          </div>
        @endif
      </div>

      <div>
        <h4>Platforma</h4>
        <ul>
          <li><a href="{{asset('')}}#nece-isleyir">Necə işləyir</a></li>
          <li><a href="{{asset('')}}#rollar">Sürücü üçün</a></li>
          <li><a href="{{asset('')}}#rollar">Sərnişin üçün</a></li>
          <li><a href="{{asset('')}}#imkanlar">İmkanlar</a></li>
          <li><a href="{{ url('/') }}#yukle">Tətbiqi yüklə</a></li>
        </ul>
      </div>

      <div>
        <h4>Hüquqi</h4>
        <ul>
          <li><a href="{{route('about')}}">Haqqımızda</a></li>
          <li><a href="{{route('privacy-policy')}}">Məxfilik siyasəti</a></li>
          <li><a href="{{route('terms-of-use')}}">İstifadə qaydaları</a></li>
          <li><a href="{{route('account-deletion')}}">Hesabın silinməsi</a></li>
          <li><a href="{{asset('')}}#faq">FAQ</a></li>
        </ul>
      </div>

      <div>
        <h4>Əlaqə</h4>
        <ul>
          @if ($emailGeneral)<li><a href="mailto:{{ $emailGeneral }}">{{ $emailGeneral }}</a></li>@endif
          @if ($emailSupport)<li><a href="mailto:{{ $emailSupport }}">{{ $emailSupport }}</a></li>@endif
          @if ($phone = Settings::get('contact.phone'))
            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></li>
          @endif
          <li><a href="{{route('contact')}}">Əlaqə forması</a></li>
          <li><span style="color:rgba(255,255,255,.45);font-size:15px">{{ Settings::get('contact.address', 'Bakı, Azərbaycan') }}</span></li>
        </ul>
      </div>
    </div>

    <div class="footer__bottom">
      <span>© <span id="year">2026</span> Yol Yoldaşı. Bütün hüquqlar qorunur.</span>
      <span>yolyodasi.az</span>
    </div>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="{{asset('assets')}}/js/main.js"></script>
@stack('scripts')
</body>
</html>
