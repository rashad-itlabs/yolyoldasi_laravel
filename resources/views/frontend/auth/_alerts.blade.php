{{--
  Uğur / məlumat / xəta mesajları.
  $bag — bir səhifədə iki forma olanda ikincinin adı: xətaları həmin adlı
  çantadan, uğur mesajını isə "{$bag}_success" açarından oxuyur.
--}}
@php
  $bagName    = $bag ?? 'default';
  $errorBag   = $errors->getBag($bagName);
  $successKey = $bagName === 'default' ? 'success' : $bagName.'_success';
@endphp

@if (session($successKey))
  <div class="alert alert--ok is-shown" role="status">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5 5L20 7"/></svg>
    <span>{{ session($successKey) }}</span>
  </div>
@endif

@if (session('info') && $bagName === 'default')
  <div class="alert alert--info is-shown" role="status">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8v.01"/></svg>
    <span>{{ session('info') }}</span>
  </div>
@endif

@if ($errorBag->any())
  <div class="alert alert--err is-shown" role="alert">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.01"/></svg>
    @if ($errorBag->count() === 1)
      <span>{{ $errorBag->first() }}</span>
    @else
      <ul style="margin:0;padding-left:18px">
        @foreach ($errorBag->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    @endif
  </div>
@endif
