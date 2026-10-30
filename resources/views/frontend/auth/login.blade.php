@extends('layout.app')

@section('meta_title', 'Daxil ol | Yol Yoldaşı')

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Daxil ol',
  'title' => 'Hesabına <span class="grad-text">daxil ol</span>',
  'lead'  => 'E-poçt və şifrən ilə daxil ol — profilin və səfərlərin səni gözləyir.',
])

<section class="section section--tight">
  <div class="container auth">

    <div class="form-card">
      @include('frontend.auth._alerts')

      {{-- novalidate: yoxlamanı server edir, mesajlar Azərbaycan dilində olsun deyə. --}}
      <form method="POST" action="{{ route('login.attempt') }}" novalidate>
        @csrf
        <div class="form-card__head">
          <h2>Daxil ol</h2>
          <p>Hesabın yoxdur? <a href="{{ route('register') }}" style="color:var(--brand);font-weight:700">Qeydiyyatdan keç</a></p>
        </div>

        <div class="field">
          <label for="email">E-poçt</label>
          <input class="input" type="email" id="email" name="email" value="{{ old('email') }}"
                 placeholder="ad@example.com" autocomplete="email" required autofocus>
        </div>

        <div class="field">
          <label for="password">Şifrə</label>
          <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
          <p class="field__hint"><a href="{{ route('password.forgot') }}" style="color:var(--brand);font-weight:700">Şifrəni unutmusan?</a></p>
        </div>

        <label class="checkbox">
          <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
          <span>Məni xatırla</span>
        </label>

        <button type="submit" class="btn btn--brand btn--lg btn--block">
          <span>Daxil ol</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>
    </div>

    @include('frontend.auth._aside')

  </div>
</section>
</main>
@endsection
