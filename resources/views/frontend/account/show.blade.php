@extends('layout.app')

@section('meta_title', 'Hesabım | Yol Yoldaşı')

@php
  $firstName = trim(explode(' ', trim($user->full_name))[0] ?? '') ?: 'dost';
  $genders   = ['male' => 'Kişi', 'female' => 'Qadın'];
@endphp

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Hesabım',
  'title' => 'Salam, <span class="grad-text">'.e($firstName).'</span>',
  'lead'  => 'Profilin və səfərlərin bir yerdə.',
])

<section class="section section--tight">
  <div class="container acct">

    @include('frontend.account._layout', ['active' => 'profile'])

    <div class="acct-main">
      @include('frontend.auth._alerts')

      <div class="form-card">
        <div class="form-card__head" style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
          <div>
            <h2>Profil</h2>
            <p>Qeydiyyat: {{ $user->created_at?->format('d.m.Y') }}</p>
          </div>
          <a href="{{ route('account.edit') }}" class="btn btn--ghost">Redaktə et</a>
        </div>

        <dl class="kv">
          <dt>Ad, soyad</dt>
          <dd>{{ $user->full_name ?: '—' }}</dd>

          <dt>E-poçt</dt>
          <dd>{{ $user->email }} @if ($user->email_verified_at)<span class="pill pill--ok">təsdiqlənib</span>@endif</dd>

          <dt>Şəhər</dt>
          <dd>{{ $user->city?->name ?? '—' }}</dd>

          <dt>Cins</dt>
          <dd>{{ $genders[$user->gender] ?? '—' }}</dd>

          <dt>Doğum ili</dt>
          <dd>{{ $user->birth_year ?? '—' }}</dd>

          <dt>Haqqında</dt>
          <dd style="font-weight:500">{{ $user->about ?: '—' }}</dd>
        </dl>
      </div>

      <div class="callout">
        <p>Elan yerləşdirmək və yer bron etmək mobil tətbiqdədir. Tətbiqə hələlik telefon nömrəsi ilə ayrıca daxil olunur — sayt və tətbiq hesablarını birləşdirmək tezliklə mümkün olacaq.</p>
      </div>
    </div>

  </div>
</section>
</main>
@endsection
