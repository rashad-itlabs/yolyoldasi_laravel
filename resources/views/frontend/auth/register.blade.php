@extends('layout.app')

@section('meta_title', 'Qeydiyyat | Yol Yoldaşı')

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Qeydiyyat',
  'title' => 'Bir dəqiqəyə <span class="grad-text">hesabını yarat</span>',
  'lead'  => 'Adını, e-poçtunu və şifrəni yaz — e-poçtuna təsdiq kodu göndərəcəyik. Qeydiyyat pulsuzdur.',
])

<section class="section section--tight">
  <div class="container auth">

    <div class="form-card">
      <div class="steps-bar" aria-hidden="true">
        <div class="steps-bar__item is-active"><i></i></div>
        <div class="steps-bar__item"><i></i></div>
      </div>

      @include('frontend.auth._alerts')

      <form method="POST" action="{{ route('register.send') }}" novalidate>
        @csrf
        <div class="form-card__head">
          <h2>Hesab yarat</h2>
          <p>Artıq hesabın var? <a href="{{ route('login') }}" style="color:var(--brand);font-weight:700">Daxil ol</a></p>
        </div>

        <div class="field">
          <label for="full_name">Ad, soyad</label>
          <input class="input" type="text" id="full_name" name="full_name" value="{{ old('full_name') }}"
                 placeholder="Rəşad Məmmədov" autocomplete="name" maxlength="100" required autofocus>
        </div>

        <div class="field">
          <label for="email">E-poçt</label>
          <input class="input" type="email" id="email" name="email" value="{{ old('email') }}"
                 placeholder="ad@example.com" autocomplete="email" maxlength="191" required>
          <p class="field__hint">Təsdiq kodu bu ünvana gələcək.</p>
        </div>

        <div class="field">
          <label for="password">Şifrə</label>
          <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
          <p class="field__hint">Ən az 8 simvol.</p>
        </div>

        <div class="field">
          <label for="password_confirmation">Şifrənin təkrarı</label>
          <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <label class="checkbox">
          <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
          <span><a href="{{ route('terms-of-use') }}" target="_blank">İstifadə qaydaları</a> və <a href="{{ route('privacy-policy') }}" target="_blank">Məxfilik siyasəti</a> ilə tanış oldum və qəbul edirəm.</span>
        </label>

        <button type="submit" class="btn btn--brand btn--lg btn--block">
          <span>Təsdiq kodu göndər</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>
    </div>

    @include('frontend.auth._aside')

  </div>
</section>
</main>
@endsection
