@php
  use App\Models\DriverProfile;
  use App\Models\Report;

  $me = auth('admin')->user();

  // Yan menyudakı sayğaclar — gözləyən işin görünməsi üçün.
  $pendingDrivers = DriverProfile::where('status', 'pending')->count();
  $openReports    = Report::where('status', 'open')->count();

  $nav = fn (string $pattern) => request()->routeIs($pattern) ? 'is-active' : '';
@endphp
<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title', 'Panel') — Yol Yoldaşı Admin</title>

<link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('assets/images/favicon-32.png') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/admin/css/admin.css') }}">
</head>
<body>

<div class="shell">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
      <svg viewBox="0 0 48 48" aria-hidden="true">
        <defs><linearGradient id="am" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#00B389"/><stop offset="55%" stop-color="#05D9A5"/><stop offset="100%" stop-color="#7BF3D0"/>
        </linearGradient></defs>
        <rect width="48" height="48" rx="14" fill="url(#am)"/>
        <path d="M15 37c0-7 4.5-8.2 9-9.4s9-2 9-8.2" fill="none" stroke="#081226" stroke-width="4.2" stroke-linecap="round"/>
        <circle cx="15" cy="37" r="3.8" fill="#081226"/><circle cx="15" cy="37" r="1.6" fill="#05D9A5"/>
        <path d="M33 8.5c-3.1 0-5.7 2.5-5.7 5.7 0 4.1 5.7 9.6 5.7 9.6s5.7-5.5 5.7-9.6c0-3.2-2.6-5.7-5.7-5.7z" fill="#081226"/>
        <circle cx="33" cy="14.2" r="2.3" fill="#05D9A5"/>
      </svg>
      <div>
        <strong>Yol Yoldaşı</strong>
        <span>İdarəetmə paneli</span>
      </div>
      <button class="sidebar__toggle" id="sidebarToggle" aria-label="Menyu" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
          <path d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
      </button>
    </div>

    <nav class="sidebar__nav">
      <a href="{{ route('admin.dashboard') }}" class="sidebar__link {{ $nav('admin.dashboard') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/>
          <rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>
        </svg>
        İcmal
      </a>

      <div class="sidebar__section">İnsanlar</div>

      <a href="{{ route('admin.users.index') }}" class="sidebar__link {{ $nav('admin.users.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 20a6.4 6.4 0 0 0-2-4.7"/>
        </svg>
        İstifadəçilər
      </a>

      <a href="{{ route('admin.drivers.index') }}" class="sidebar__link {{ $nav('admin.drivers.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 11l1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11"/>
          <rect x="3" y="11" width="18" height="6" rx="2"/><path d="M7 20v-3M17 20v-3"/>
        </svg>
        Sürücü təsdiqi
        @if ($pendingDrivers) <span class="sidebar__badge">{{ $pendingDrivers }}</span> @endif
      </a>

      <div class="sidebar__section">Fəaliyyət</div>

      <a href="{{ route('admin.rides.index') }}" class="sidebar__link {{ $nav('admin.rides.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path d="M6 15.5c0-5 4-4.5 6-5.5s3-2 3-4"/>
        </svg>
        Səfərlər
      </a>

      <a href="{{ route('admin.ride-requests.index') }}" class="sidebar__link {{ $nav('admin.ride-requests.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 20.5s6.5-5.2 6.5-10a6.5 6.5 0 1 0-13 0c0 4.8 6.5 10 6.5 10z"/><path d="M9.5 10.5h5M12 8v5"/>
        </svg>
        Tələb elanları
      </a>

      <a href="{{ route('admin.bookings.index') }}" class="sidebar__link {{ $nav('admin.bookings.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4" y="4" width="16" height="17" rx="2"/><path d="M8 3v3M16 3v3M8 11h8M8 15.5h5"/>
        </svg>
        Rezervasiyalar
      </a>

      <a href="{{ route('admin.reviews.index') }}" class="sidebar__link {{ $nav('admin.reviews.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="m12 3.8 2.6 5.3 5.9.9-4.2 4.1 1 5.8-5.3-2.8-5.3 2.8 1-5.8L3.5 10l5.9-.9z"/>
        </svg>
        Rəylər
      </a>

      <a href="{{ route('admin.reports.index') }}" class="sidebar__link {{ $nav('admin.reports.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 21V4.5h13l-2.2 4 2.2 4H5"/><path d="M12 8.5v.01"/>
        </svg>
        Şikayətlər
        @if ($openReports) <span class="sidebar__badge">{{ $openReports }}</span> @endif
      </a>

      <div class="sidebar__section">Məzmun</div>

      <a href="{{ route('admin.contact.edit') }}" class="sidebar__link {{ $nav('admin.contact.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 8l9 6 9-6"/>
        </svg>
        Əlaqə məlumatları
      </a>

      <a href="{{ route('admin.pages.index') }}" class="sidebar__link {{ $nav('admin.pages.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h4"/>
        </svg>
        Səhifələr
      </a>

      <a href="{{ route('admin.faqs.index') }}" class="sidebar__link {{ $nav('admin.faqs.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="8.5"/><path d="M9.8 9.4a2.3 2.3 0 1 1 3 2.2v1.2M12 16.5v.01"/>
        </svg>
        Suallar (FAQ)
      </a>

      <div class="sidebar__section">Sistem</div>

      <a href="{{ route('admin.push.index') }}" class="sidebar__link {{ $nav('admin.push.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 15.5V10a6 6 0 1 0-12 0v5.5L4.5 18h15z"/><path d="M10 21h4"/>
        </svg>
        Push göndər
      </a>

      <a href="{{ route('admin.cities.index') }}" class="sidebar__link {{ $nav('admin.cities.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 21s7-5.7 7-11a7 7 0 1 0-14 0c0 5.3 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>
        </svg>
        Şəhərlər
      </a>

      <a href="{{ route('admin.app-version.edit') }}" class="sidebar__link {{ $nav('admin.app-version.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M12 6.5v6M9.5 10.5 12 13l2.5-2.5"/>
        </svg>
        Tətbiq versiyası
      </a>

      <a href="{{ route('admin.logs.index') }}" class="sidebar__link {{ $nav('admin.logs.*') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 7v5l3.2 2"/><circle cx="12" cy="12" r="8.5"/>
        </svg>
        Fəaliyyət jurnalı
      </a>

      @if ($me?->isSuperAdmin())
        <a href="{{ route('admin.admins.index') }}" class="sidebar__link {{ $nav('admin.admins.*') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3.2 19 6v5.4c0 4.2-2.9 7.6-7 9.4-4.1-1.8-7-5.2-7-9.4V6z"/><path d="m9.3 12 1.9 1.9 3.6-3.8"/>
          </svg>
          Adminlər
        </a>
      @endif
    </nav>

    <div class="sidebar__foot">
      <div class="sidebar__me">
        <span class="sidebar__avatar">{{ mb_strtoupper(mb_substr($me?->name ?: '?', 0, 1)) }}</span>
        <div>
          <strong>{{ $me?->name }}</strong>
          <span>{{ $me?->roleLabel() }}</span>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="btn btn--ghost btn--sm btn--block">Çıxış</button>
      </form>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div>
        <h1>@yield('title', 'Panel')</h1>
        @hasSection('subtitle')<div class="muted">@yield('subtitle')</div>@endif
      </div>
      <div class="topbar__actions">@yield('actions')</div>
    </header>

    <main class="content">
      @include('admin.partials.flash')
      @yield('content')
    </main>
  </div>
</div>

<script>
  document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    var bar = document.getElementById('sidebar');
    var open = bar.classList.toggle('is-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Geri dönülməz əməliyyatlar üçün təsdiq.
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });

  // Modal açan düymələr: data-modal="#id"
  document.querySelectorAll('[data-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelector(btn.dataset.modal)?.showModal();
    });
  });

  document.querySelectorAll('[data-close]').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.closest('dialog')?.close(); });
  });
</script>
@stack('scripts')
</body>
</html>
