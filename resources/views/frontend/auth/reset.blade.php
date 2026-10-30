@extends('layout.app')

@section('meta_title', 'Yeni şifrə | Yol Yoldaşı')

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Şifrə bərpası',
  'title' => 'Yeni <span class="grad-text">şifrə</span> təyin et',
  'lead'  => 'E-poçtuna gələn kodu və yeni şifrəni yaz.',
])

<section class="section section--tight">
  <div class="container auth">

    <div class="form-card">
      @include('frontend.auth._alerts')

      <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <div class="form-card__head">
          <h2>Yeni şifrə</h2>
          <p><b>{{ $email }}</b> &middot;
            <a href="{{ route('password.forgot') }}" style="color:var(--brand);font-weight:700;text-decoration:underline">Kodu yenidən göndər</a></p>
        </div>

        @include('frontend.auth._code-input')

        <div class="field">
          <label for="password">Yeni şifrə</label>
          <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
          <p class="field__hint">Ən az 8 simvol.</p>
        </div>

        <div class="field">
          <label for="password_confirmation">Yeni şifrənin təkrarı</label>
          <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn--brand btn--lg btn--block">
          <span>Şifrəni yenilə və daxil ol</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>
    </div>

    @include('frontend.auth._aside')

  </div>
</section>
</main>
@endsection
