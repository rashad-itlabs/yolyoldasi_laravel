@extends('layout.app')
@section('content')
<main>
<!-- ================= HERO ================= -->
<section class="hero" id="hero">
  <div class="hero__bg" aria-hidden="true">
    <div class="hero__glow hero__glow--1"></div>
    <div class="hero__glow hero__glow--2"></div>
  </div>
  <div class="hero__grid" aria-hidden="true"></div>

  <div class="container hero__inner">
    <div class="hero__copy">
      <div class="hero__badge reveal" data-anim="fade-up">
        <b>Yeni</b> Azərbaycan üzrə 40+ marşrut aktivdir
      </div>

      <h1 data-split>Şəhərlərarası səfəri <span class="grad-text">bölüş,</span> yolu ucuzlaşdır.</h1>

      <p class="hero__sub reveal" data-anim="fade-up">
        Sürücü marşrutunu elan edir, sərnişin bir toxunuşla yer bron edir.
        Reytinq, sənəd təsdiqi və anlıq bildirişlərlə — tanımadığın yolu tanış et.
      </p>

      <div class="hero__cta reveal" data-anim="fade-up">
        <a href="#yukle" class="btn btn--brand btn--lg">
          Tətbiqi yüklə
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
        <a href="#nece-isleyir" class="btn btn--ghost btn--lg">Necə işləyir?</a>
      </div>

      <div class="hero__stats">
        <div class="hero__stat reveal" data-anim="fade-up"><b data-count="12400" data-suffix="+">0</b><span>Qeydiyyatlı istifadəçi</span></div>
        <div class="hero__stat reveal" data-anim="fade-up"><b data-count="3800" data-suffix="+">0</b><span>Tamamlanmış səfər</span></div>
        <div class="hero__stat reveal" data-anim="fade-up"><b data-count="48" data-suffix="%">0</b><span>Ortalama qənaət</span></div>
        <div class="hero__stat reveal" data-anim="fade-up"><b data-count="4.9" data-decimals="1">0</b><span>Ortalama reytinq</span></div>
      </div>
    </div>

    <div class="hero__visual">
      <div class="phone" id="phone">
        <div class="phone__screen">
          <div class="phone__top">
            <div class="phone__route">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#05D9A5" stroke-width="2.6" stroke-linecap="round"><circle cx="12" cy="12" r="4"/></svg>
              Bakı <span>→</span> Gəncə
            </div>
            <div style="margin-top:10px;display:flex;gap:7px;flex-wrap:wrap">
              <em style="font-style:normal;font-size:10.5px;font-weight:700;color:#05D9A5;background:rgba(5,217,165,.14);padding:5px 10px;border-radius:99px">Sabah, 08:00</em>
              <em style="font-style:normal;font-size:10.5px;font-weight:700;color:rgba(255,255,255,.65);background:rgba(255,255,255,.07);padding:5px 10px;border-radius:99px">3 nəticə</em>
            </div>
          </div>
          <div class="phone__body">
            <div class="ride-card" data-ride>
              <div class="ride-card__ava">RM</div>
              <div class="ride-card__body">
                <div class="ride-card__name">Rəşad M. <i>★ 4.9</i></div>
                <div class="ride-card__meta">Toyota Prius • 3 yer • 08:00</div>
              </div>
              <div class="ride-card__price">14 ₼</div>
            </div>
            <div class="ride-card" data-ride>
              <div class="ride-card__ava">EN</div>
              <div class="ride-card__body">
                <div class="ride-card__name">Elvin N. <i>★ 4.8</i></div>
                <div class="ride-card__meta">Hyundai Elantra • 2 yer • 09:30</div>
              </div>
              <div class="ride-card__price">15 ₼</div>
            </div>
            <div class="ride-card" data-ride>
              <div class="ride-card__ava">GA</div>
              <div class="ride-card__body">
                <div class="ride-card__name">Günel A. <i>★ 5.0</i></div>
                <div class="ride-card__meta">Kia Optima • 4 yer • 11:00</div>
              </div>
              <div class="ride-card__price">13 ₼</div>
            </div>
            <div class="phone__cta">Yer bron et</div>
          </div>
        </div>
      </div>

      <div class="float-chip float-chip--1" data-float>
        <i>✓</i> Bron təsdiqləndi
      </div>
      <div class="float-chip float-chip--2" data-float>
        <i>★</i> Sürücü reytinqi 4.9
      </div>
    </div>
  </div>
</section>

<!-- ================= MARQUEE ================= -->
<div class="marquee" aria-hidden="true">
  <div class="marquee__track" id="marquee">
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Bakı — Gəncə <b>13 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Bakı — Quba <b>9 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Bakı — Şəki <b>15 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Gəncə — Naxçıvan <b>25 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Bakı — Lənkəran <b>12 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Sumqayıt — Şamaxı <b>8 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Bakı — Qəbələ <b>14 ₼</b></span>
    <span class="marquee__item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>Mingəçevir — Bakı <b>13 ₼</b></span>
  </div>
</div>

<!-- ================= NECƏ İŞLƏYİR ================= -->
<section class="section" id="nece-isleyir">
  <div class="container">
    <div class="sec-head sec-head--center">
      <span class="eyebrow reveal" data-anim="fade-up">Necə işləyir</span>
      <h2 data-split>Üç addımda yol yoldaşını tap</h2>
      <p class="lead reveal" data-anim="fade-up">Mürəkkəb forma yoxdur. Qeydiyyat 40 saniyə, bron isə bir toxunuşdur.</p>
    </div>

    <div class="steps">
      <svg class="steps__line" viewBox="0 0 1000 40" preserveAspectRatio="none" aria-hidden="true">
        <path id="stepsPath" d="M0 20 C 250 -10, 750 50, 1000 20"/>
      </svg>

      <article class="step reveal" data-anim="fade-up">
        <div class="step__num">1</div>
        <h3>Qeydiyyatdan keç</h3>
        <p>Telefon nömrəni yaz, SMS kodunu təsdiqlə. Sonra rolunu seç — sürücü, sərnişin və ya hər ikisi.</p>
      </article>

      <article class="step reveal" data-anim="fade-up">
        <div class="step__num">2</div>
        <h3>Elan ver və ya axtar</h3>
        <p>Sürücüsənsə marşrut, tarix, boş yer sayı və qiyməti yaz. Sərnişinsənsə istiqamət və tarixə görə axtar.</p>
      </article>

      <article class="step reveal" data-anim="fade-up">
        <div class="step__num">3</div>
        <h3>Bron et və yola çıx</h3>
        <p>Yer bron et, sürücü təsdiqləsin — əlaqə nömrəsi açılsın. Səfərdən sonra bir-birinizi qiymətləndirin.</p>
      </article>
    </div>
  </div>
</section>

<!-- ================= ROLLAR ================= -->
<section class="section section--mist" id="rollar">
  <div class="container">
    <div class="sec-head sec-head--center">
      <span class="eyebrow reveal" data-anim="fade-up">İki rejim, bir hesab</span>
      <h2 data-split>Sürücüsən? Sərnişinsən? Fərqi yoxdur.</h2>
      <p class="lead reveal" data-anim="fade-up">Eyni profil daxilində rejimi dəyişirsən — bu gün sürücü, sabah sərnişin.</p>
    </div>

    <div style="display:flex;justify-content:center">
      <div class="tabs reveal" data-anim="fade-up" role="tablist" id="roleTabs">
        <div class="tabs__pill" id="tabsPill"></div>
        <button class="tab is-active" role="tab" aria-selected="true" data-tab="suruculer">Sürücü üçün</button>
        <button class="tab" role="tab" aria-selected="false" data-tab="sernisinler">Sərnişin üçün</button>
      </div>
    </div>

    <!-- Sürücü -->
    <div class="panel is-active" id="suruculer" role="tabpanel">
      <div class="role">
        <div>
          <h3 style="font-size:clamp(24px,2.8vw,34px)">Boş oturacaq pul qazandırsın</h3>
          <p class="lead" style="margin-top:14px">Onsuz da gedirsən — yanacaq xərcini bölüş, yolu daha ucuza başa gəlsin.</p>
          <ul class="role__list">
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>60 saniyəyə elan</b><span>Haradan → hara, tarix, saat, boş yer və adam başı qiymət.</span></div></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Bronu sən təsdiqləyirsən</b><span>Kimi götürdüyünə özün qərar verirsən — avtomatik təsdiq yoxdur.</span></div></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Təsdiqlənmiş sürücü nişanı</b><span>Sənədlərin yoxlanıldıqdan sonra profilində nişan görünür — daha çox bron deməkdir.</span></div></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Komissiya yoxdur</b><span>Ödənişi sərnişinlə birbaşa həll edirsən, platforma araya girmir.</span></div></li>
          </ul>
        </div>

        <div class="mock reveal" data-anim="fade-left">
          <div class="mock__head">
            <div class="mock__title">Yeni marşrut elanı</div>
            <div class="mock__dot"><span></span><span></span><span></span></div>
          </div>
          <div class="mock__field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="10" r="3"/><path d="M12 21s7-5.5 7-11a7 7 0 10-14 0c0 5.5 7 11 7 11z"/></svg>
            <div><label>Haradan</label><b>Bakı</b></div>
          </div>
          <div class="mock__field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 21s7-5.5 7-11a7 7 0 10-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="3"/></svg>
            <div><label>Hara</label><b>Gəncə</b></div>
          </div>
          <div class="mock__row">
            <div class="mock__field">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>
              <div><label>Tarix</label><b>14 Sen, 08:00</b></div>
            </div>
            <div class="mock__field">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="4"/></svg>
              <div><label>Boş yer</label><b>3 nəfər</b></div>
            </div>
          </div>
          <div class="mock__field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            <div><label>Adam başı qiymət</label><b>13 ₼</b></div>
          </div>
          <div class="mock__btn">Elanı dərc et</div>
        </div>
      </div>
    </div>

    <!-- Sərnişin -->
    <div class="panel" id="sernisinler" role="tabpanel">
      <div class="role">
        <div>
          <h3 style="font-size:clamp(24px,2.8vw,34px)">Avtovağzalda növbə gözləmə</h3>
          <p class="lead" style="margin-top:14px">İstiqaməti yaz, uyğun saatı seç, yerini bron et. Hamısı telefondan.</p>
          <ul class="role__list">
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Marşrut və tarixə görə axtarış</b><span>Qiymət, saat, reytinq və boş yer sayına görə müqayisə et.</span></div></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Kiminlə getdiyini əvvəlcədən bil</b><span>Sürücünün adı, avtomobili, reytinqi və rəylər açıqdır.</span></div></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Bir neçə yer bron et</b><span>Ailə və ya dostlarla gedirsənsə, tək bronla 4 yerədək götür.</span></div></li>
            <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i>
              <div><b>Nömrən gizli qalır</b><span>Telefon nömrələri yalnız bron təsdiqləndikdən sonra qarşılıqlı açılır.</span></div></li>
          </ul>
        </div>

        <div class="mock reveal" data-anim="fade-left">
          <div class="mock__head">
            <div class="mock__title">Axtarış nəticəsi</div>
            <div class="mock__dot"><span></span><span></span><span></span></div>
          </div>
          <div class="mock__field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            <div><label>Marşrut</label><b>Bakı → Gəncə • 14 Sen</b></div>
          </div>
          <div style="display:flex;flex-direction:column;gap:11px;margin-top:16px">
            <div style="padding:15px;border-radius:16px;border:1.5px solid var(--brand);background:var(--brand-soft);display:flex;align-items:center;gap:12px">
              <div style="width:42px;height:42px;border-radius:13px;background:var(--grad-brand);display:grid;place-items:center;font-weight:800;color:#04301f">RM</div>
              <div style="flex:1">
                <div style="font-weight:800;font-size:15px">Rəşad M. <span style="color:var(--accent-2);font-size:12.5px">★ 4.9</span></div>
                <div style="color:var(--muted);font-size:13px">Toyota Prius • 08:00 • 3 yer</div>
              </div>
              <div style="font-weight:800;color:var(--brand);font-size:17px">14 ₼</div>
            </div>
            <div style="padding:15px;border-radius:16px;border:1px solid var(--line);display:flex;align-items:center;gap:12px;opacity:.72">
              <div style="width:42px;height:42px;border-radius:13px;background:var(--mist-2);display:grid;place-items:center;font-weight:800;color:var(--muted)">EN</div>
              <div style="flex:1">
                <div style="font-weight:800;font-size:15px">Elvin N. <span style="color:var(--accent-2);font-size:12.5px">★ 4.8</span></div>
                <div style="color:var(--muted);font-size:13px">Hyundai Elantra • 09:30 • 2 yer</div>
              </div>
              <div style="font-weight:800;font-size:17px">15 ₼</div>
            </div>
          </div>
          <div class="mock__btn">2 yer bron et</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= İMKANLAR ================= -->
<section class="section section--ink" id="imkanlar">
  <div class="container">
    <div class="sec-head sec-head--center">
      <span class="eyebrow reveal" data-anim="fade-up">İmkanlar</span>
      <h2 data-split>Səfəri idarə etmək üçün hər şey</h2>
      <p class="lead reveal" data-anim="fade-up">Elandan reytinqə qədər bütün axın bir tətbiqin içindədir.</p>
    </div>

    <div class="grid grid--3">
      <article class="card reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18M9 15l2 2 4-4"/></svg></div>
        <h3>Bron sistemi</h3>
        <p>Yer seçimi, təsdiq gözləmə, ləğv və tam bron tarixçəsi — həm sürücü, həm sərnişin tərəfində.</p>
      </article>

      <article class="card reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8z"/></svg></div>
        <h3>Reytinq və rəylər</h3>
        <p>Səfərdən sonra qarşılıqlı qiymətləndirmə. Ortalama bal profildə açıq göstərilir.</p>
      </article>

      <article class="card reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 8-3 8h18s-3-1-3-8z"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></div>
        <h3>Anlıq bildirişlər</h3>
        <p>Yeni bron, təsdiq, ləğv və səfər xatırlatmaları push bildiriş kimi gəlir.</p>
      </article>

      <article class="card reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5M9 14l2 2 4-4"/></svg></div>
        <h3>Sənəd təsdiqi</h3>
        <p>Sürücülük vəsiqəsi, şəxsiyyət vəsiqəsi və sığorta moderasiyadan keçir, status profildə görünür.</p>
      </article>

      <article class="card reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
        <h3>Təhlükəsiz əlaqə</h3>
        <p>Nömrə yalnız bron təsdiqindən sonra açılır. İstəsən tətbiqdaxili yazışmadan istifadə et.</p>
      </article>

      <article class="card reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8z"/></svg></div>
        <h3>Marşrut xəritəsi</h3>
        <p>Görüş nöqtəsi və marşrut xəritədə göstərilir — “harada gözləyim?” sualı bitir.</p>
      </article>
    </div>
  </div>
</section>

<!-- ================= TƏHLÜKƏSİZLİK ================= -->
<section class="section" id="tehlukesizlik">
  <div class="container">
    <div class="sec-head">
      <span class="eyebrow reveal" data-anim="fade-up">Təhlükəsizlik</span>
      <h2 data-split>Etibar təsadüf deyil, sistemdir</h2>
      <p class="lead reveal" data-anim="fade-up">Hər istifadəçi nömrə təsdiqindən keçir, hər sürücü sənəd yoxlamasından. Şikayət bir toxunuşdadır.</p>
    </div>

    <div class="trust reveal" data-anim="fade-up">
      <div class="trust__item"><b data-count="100" data-suffix="%">0</b><span>SMS kodu ilə təsdiqlənmiş nömrə</span></div>
      <div class="trust__item"><b data-count="24" data-suffix="/7">0</b><span>Şikayət və dəstək baxışı</span></div>
      <div class="trust__item"><b data-count="4.9" data-decimals="1">0</b><span>Ortalama sürücü reytinqi</span></div>
      <div class="trust__item"><b data-count="0" data-suffix="₼">0</b><span>Qeydiyyat və elan haqqı</span></div>
    </div>

    <div class="grid grid--3" style="margin-top:28px">
      <article class="card card--ink reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg></div>
        <h3>Moderasiya</h3>
        <p>Şübhəli elan və profil admin panelindən bloklanır, şikayətlər 24 saat ərzində baxılır.</p>
      </article>
      <article class="card card--ink reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 018 0v3"/></svg></div>
        <h3>Məlumat məxfiliyi</h3>
        <p>Şəxsi məlumatlar şifrələnmiş şəkildə saxlanılır, üçüncü tərəflərə satılmır.</p>
      </article>
      <article class="card card--ink reveal" data-anim="fade-up">
        <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg></div>
        <h3>Şəffaf qaydalar</h3>
        <p>Ləğv, gecikmə və davranış qaydaları əvvəlcədən bəllidir — sürprizsiz səfər.</p>
      </article>
    </div>
  </div>
</section>

<!-- ================= FAQ ================= -->
<section class="section section--mist" id="faq">
  <div class="container">
    <div class="sec-head sec-head--center">
      <span class="eyebrow reveal" data-anim="fade-up">Tez-tez verilən suallar</span>
      <h2 data-split>Ağlına gələni burada cavablandırdıq</h2>
    </div>

    <div class="faq">
      @forelse ($faqs as $faq)
      <div class="faq__item">
        <button class="faq__q">{{ $faq->question }}
          <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></i></button>
        <div class="faq__a"><p>{!! $faq->answer !!}</p></div>
      </div>
      @empty
      <p class="lead" style="text-align:center">Hazırda sual əlavə edilməyib.</p>
      @endforelse
    </div>
  </div>
</section>

<!-- ================= CTA ================= -->
<section class="section section--tight" id="yukle">
  <div class="container">
    <div class="cta reveal" data-anim="scale">
      <div class="cta__glow cta__glow--1" aria-hidden="true"></div>
      <div class="cta__glow cta__glow--2" aria-hidden="true"></div>
      <span class="eyebrow">Yola çıxmağa hazırsan?</span>
      <h2>Növbəti səfərini təkbaşına getmə</h2>
      <p class="lead">Tətbiqi yüklə, nömrəni təsdiqlə və ilk elanını ver və ya ilk bronunu et — cəmi bir neçə dəqiqə çəkir.</p>
      <div class="cta__btns">
        <a href="#" class="store-btn" aria-label="Google Play-dən yüklə">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.6 2.3c-.3.3-.5.8-.5 1.4v16.6c0 .6.2 1.1.5 1.4l.1.1 9.3-9.3v-.2L3.7 2.2zM16.8 15.5l-3.1-3.1v-.2l3.1-3.1.1.1 3.7 2.1c1.1.6 1.1 1.6 0 2.2zM15.9 16.4l-3.2-3.2-9.1 9.1c.4.4 1 .4 1.7.1zM4.6 1.6l9.1 9-1.9-1.9-7.2-7.1z"/></svg>
          <div><span>Yüklə</span><b>Google Play</b></div>
        </a>
        <a href="#" class="store-btn" aria-label="App Store-dan yüklə">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.4 12.7c0-2.4 2-3.6 2.1-3.7-1.1-1.7-2.9-1.9-3.5-1.9-1.5-.2-2.9.9-3.7.9s-1.9-.9-3.2-.8c-1.6 0-3.1.9-4 2.4-1.7 3-.4 7.4 1.2 9.8.8 1.2 1.8 2.5 3.1 2.4 1.2 0 1.7-.8 3.2-.8s1.9.8 3.2.8 2.2-1.2 3-2.4c.9-1.4 1.3-2.7 1.3-2.8-.1 0-2.7-1-2.7-3.9zM14 4.9c.7-.8 1.1-2 1-3.2-1 0-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.5 2.9-1.3z"/></svg>
          <div><span>Yüklə</span><b>App Store</b></div>
        </a>
      </div>
      <p style="margin-top:22px;font-size:13.5px;color:rgba(255,255,255,.45)">Tətbiq mağazalarda yayım mərhələsindədir — linklər tezliklə aktiv olacaq.</p>
    </div>
  </div>
</section>
</main>
@endsection