@extends('layout.app')

@section('meta_title', 'Şifrəni bərpa et | Yol Yoldaşı')

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Şifrə bərpası',
  'title' => 'Şifrəni <span class="grad-text">bərpa et</span>',
  'lead'  => 'Qeydiyyatda yazdığın e-poçtu daxil et — ona şifrəni yeniləmək üçün kod göndərəcəyik.',
])

<section class="section section--tight">
  <div class="container auth">

    <div class="form-card">
      @include('frontend.auth._alerts')

      <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="form-card__head">
          <h2>Şifrəni unutmusan?</h2>
          <p>Yadına düşdü? <a href="{{ route('login') }}" style="color:var(--brand);font-weight:700">Daxil ol</a></p>
        </div>

        <div class="field">
          <label for="email">E-poçt</label>
          <input class="input" type="email" id="email" name="email" value="{{ old('email') }}"
                 placeholder="ad@example.com" autocomplete="email" required autofocus>
        </div>

        <button type="submit" class="btn btn--brand btn--lg btn--block">
          <span>Kod göndər</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>
    </div>

    @include('frontend.auth._aside')

  </div>
</section>
</main>
@endsection
