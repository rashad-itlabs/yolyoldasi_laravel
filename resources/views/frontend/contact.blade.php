@extends('layout.app')

@section('meta_title', 'Əlaqə — Yol Yoldaşı')

@php
  use App\Support\Settings;

  $cards = array_values(array_filter([
    ['Ümumi məlumat',        Settings::get('contact.email_general'), 'Əməkdaşlıq və media sorğuları',      'mail'],
    ['İstifadəçi dəstəyi',   Settings::get('contact.email_support'), 'Hesab, bron və texniki problemlər',  'chat'],
    ['Məxfilik və məlumatlar', Settings::get('contact.email_privacy'), 'Məlumatların silinməsi və hüquqlar', 'lock'],
  ], fn ($c) => ! empty($c[1])));

  $icons = [
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 8l9 6 9-6"/>',
    'chat' => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
    'lock' => '<rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 018 0v3"/>',
  ];
@endphp

@section('content')
<main>

<section class="page-head">
  <div class="hero__bg" aria-hidden="true"><div class="hero__glow hero__glow--1"></div></div>
  <div class="hero__grid" aria-hidden="true"></div>
  <div class="container">
    <div class="crumbs"><a href="{{asset('')}}">Ana səhifə</a><span>/</span><span>Əlaqə</span></div>
    <h1 data-split>Bizimlə <span class="grad-text">əlaqə saxla</span></h1>
    <p class="lead">Sualın, təklifin və ya şikayətin var? {{ Settings::get('contact.response_time', 'Müraciətlərə iş günləri ərzində 24 saat içində cavab veririk.') }}</p>
    
  </div>
</section>


<section class="section section--tight">
  <div class="container">
    <div class="contact-grid">
      @foreach ($cards as [$title, $email, $note, $icon])
        <div class="contact-card reveal" data-anim="fade-up">
          <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$icon] !!}</svg></i>
          <b>{{ $title }}</b>
          <a href="mailto:{{ $email }}">{{ $email }}</a>
          <span>{{ $note }}</span>
        </div>
      @endforeach

      @if ($phone = Settings::get('contact.phone'))
        <div class="contact-card reveal" data-anim="fade-up">
          <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a1 1 0 01-1 1A16 16 0 014 5a1 1 0 011-1z"/></svg></i>
          <b>Telefon</b>
          <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>
          <span>{{ Settings::get('contact.address', 'Bakı, Azərbaycan') }}</span>
        </div>
      @endif
    </div>

    <div class="auth">
      <div class="form-card reveal" data-anim="fade-up">
        <div class="form-card__head">
          <h2>Mesaj göndər</h2>
          <p>Formanı doldur — e-poçt tətbiqin hazır mətnlə açılacaq.</p>
        </div>
        <form id="contactForm" novalidate>
          <div class="field">
            <label for="cName">Adın</label>
            <input class="input" id="cName" placeholder="Adın və soyadın" required>
          </div>
          <div class="field">
            <label for="cEmail">E-poçt və ya telefon</label>
            <input class="input" id="cEmail" placeholder="sen@nümunə.az və ya +994 50 ..." required>
          </div>
          <div class="field">
            <label for="cTopic">Mövzu</label>
            <select class="select" id="cTopic">
              <option>Texniki problem</option>
              <option>Hesabla bağlı sual</option>
              <option>Şikayət (sürücü/sərnişin)</option>
              <option>Əməkdaşlıq təklifi</option>
              <option>Digər</option>
            </select>
          </div>
          <div class="field">
            <label for="cMsg">Mesajın</label>
            <textarea class="textarea" id="cMsg" placeholder="Nə baş verdiyini qısaca yaz..." required></textarea>
          </div>
          <button type="submit" class="btn btn--brand btn--lg btn--block">
            <span>Mesajı göndər</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
        </form>
      </div>

      <aside>
        <div class="aside-card reveal" data-anim="fade-left">
          <div class="hero__glow hero__glow--1" aria-hidden="true"></div>
          <h3>Tez cavab lazımdır?</h3>
          <p>Ən çox verilən suallara cavablar artıq hazırdır.</p>
          <ul class="aside-list">
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span>Bron və ləğv qaydaları</span></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span>Sürücü sənədlərinin təsdiqi</span></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span>Ödəniş və qiymətlər</span></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span>Hesabın silinməsi</span></li>
          </ul>
          <a href="{{ url('/') }}#faq" class="btn btn--white" style="margin-top:26px;width:100%">FAQ bölməsinə keç</a>
          @if ($urgent = Settings::get('contact.urgent_note'))
            <p class="aside-note">
              {{ $urgent }}
              @if ($support = Settings::get('contact.email_support'))
                <a href="mailto:{{ $support }}" style="color:var(--brand-2)">{{ $support }}</a>
              @endif
            </p>
          @endif
        </div>
      </aside>
    </div>
  </div>
</section>
</main>
@endsection