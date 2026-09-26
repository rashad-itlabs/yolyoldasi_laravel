<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Giriş — Yol Yoldaşı Admin</title>
<link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/admin/css/admin.css') }}">
</head>
<body>

<div class="login-wrap">
  <div class="login-card">

    <div class="brand-row">
      <svg viewBox="0 0 48 48" aria-hidden="true">
        <defs><linearGradient id="lm" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#00B389"/><stop offset="55%" stop-color="#05D9A5"/><stop offset="100%" stop-color="#7BF3D0"/>
        </linearGradient></defs>
        <rect width="48" height="48" rx="14" fill="url(#lm)"/>
        <path d="M15 37c0-7 4.5-8.2 9-9.4s9-2 9-8.2" fill="none" stroke="#081226" stroke-width="4.2" stroke-linecap="round"/>
        <circle cx="15" cy="37" r="3.8" fill="#081226"/><circle cx="15" cy="37" r="1.6" fill="#05D9A5"/>
        <path d="M33 8.5c-3.1 0-5.7 2.5-5.7 5.7 0 4.1 5.7 9.6 5.7 9.6s5.7-5.5 5.7-9.6c0-3.2-2.6-5.7-5.7-5.7z" fill="#081226"/>
        <circle cx="33" cy="14.2" r="2.3" fill="#05D9A5"/>
      </svg>
      <div>
        <strong>Yol Yoldaşı</strong>
        <span>İdarəetmə panelinə giriş</span>
      </div>
    </div>

    @include('admin.partials.flash')

    <form method="POST" action="{{ route('admin.login.attempt') }}">
      @csrf

      <div class="field">
        <label for="email">E-poçt</label>
        <input class="input" type="email" id="email" name="email"
               value="{{ old('email') }}" required autofocus autocomplete="username">
      </div>

      <div class="field">
        <label for="password">Şifrə</label>
        <input class="input" type="password" id="password" name="password"
               required autocomplete="current-password">
      </div>

      <div class="field">
        <label class="check">
          <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
          Məni xatırla
        </label>
      </div>

      <button type="submit" class="btn btn--primary btn--block">Daxil ol</button>
    </form>

  </div>
</div>

</body>
</html>
