@extends('layout.app')

@section('meta_title', 'E-poçtu təsdiqlə | Yol Yoldaşı')

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Qeydiyyat',
  'title' => 'E-poçtunu <span class="grad-text">təsdiqlə</span>',
  'lead'  => 'Məktubdakı 6 rəqəmli kodu yaz — hesabın dərhal yaradılacaq.',
])

<section class="section section--tight">
  <div class="container auth">

    <div class="form-card">
      <div class="steps-bar" aria-hidden="true">
        <div class="steps-bar__item is-done"><i></i></div>
        <div class="steps-bar__item is-active"><i></i></div>
      </div>

      @include('frontend.auth._alerts')

      <form method="POST" action="{{ route('register.confirm') }}" novalidate>
        @csrf
        <div class="form-card__head">
          <h2>Kodu yaz</h2>
          <p><b>{{ $email }}</b> ünvanına kod göndərdik.
            <a href="{{ route('register') }}" style="color:var(--brand);font-weight:700;text-decoration:underline">E-poçtu dəyiş</a></p>
        </div>

        @include('frontend.auth._code-input')

        <button type="submit" class="btn btn--brand btn--lg btn--block">
          <span>Təsdiqlə və daxil ol</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
      </form>

      <form method="POST" action="{{ route('register.resend') }}" style="margin-top:18px;text-align:center">
        @csrf
        <p class="field__hint">
          Kod gəlmədi? Spam qovluğuna da bax.
          <button type="submit" id="resend" style="color:var(--brand);font-weight:700" @disabled($cooldown > 0)>Yenidən göndər</button>
          @if ($cooldown > 0)<span id="resendTimer">({{ $cooldown }} san)</span>@endif
        </p>
      </form>
    </div>

    @include('frontend.auth._aside')

  </div>
</section>
</main>
@endsection

@if ($cooldown > 0)
@push('scripts')
<script>
  // Düymə gözləmə bitəndə açılır. JS işləməsə, server yenə də vaxtı yoxlayır.
  (function () {
    var left = {{ (int) $cooldown }};
    var btn = document.getElementById('resend');
    var timer = document.getElementById('resendTimer');
    var tick = setInterval(function () {
      left -= 1;
      if (left <= 0) { clearInterval(tick); btn.disabled = false; timer.remove(); return; }
      timer.textContent = '(' + left + ' san)';
    }, 1000);
  })();
</script>
@endpush
@endif
