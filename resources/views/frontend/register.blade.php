@extends('layout.app')
@section('content')
<main>

<section class="page-head">
  <div class="hero__bg" aria-hidden="true"><div class="hero__glow hero__glow--1"></div></div>
  <div class="hero__grid" aria-hidden="true"></div>
  <div class="container">
    <div class="crumbs"><a href="{{ url('/') }}">Ana səhifə</a><span>/</span><span>Qeydiyyat</span></div>
    <h1 data-split>Bir neçə dəqiqəyə <span class="grad-text">hesabını yarat</span></h1>
    <p class="lead">Telefon nömrəni təsdiqlə, rolunu seç və elan verməyə və ya yer bron etməyə başla. Qeydiyyat tamamilə pulsuzdur.</p>
    
  </div>
</section>


<section class="section section--tight">
  <div class="container auth">

    <!-- ============ FORM ============ -->
    <div class="form-card reveal" data-anim="fade-up">
      <div class="steps-bar" aria-hidden="true">
        <div class="steps-bar__item is-active" data-bar="1"><i></i></div>
        <div class="steps-bar__item" data-bar="2"><i></i></div>
        <div class="steps-bar__item" data-bar="3"><i></i></div>
      </div>

      <div class="alert alert--err" id="formErr" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.01"/></svg>
        <span id="formErrText"></span>
      </div>
      <div class="alert alert--info" id="formInfo" role="status">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-5M12 8v.01"/></svg>
        <span id="formInfoText"></span>
      </div>

      <form class="form-steps" id="signupForm" novalidate>

        <!-- Addım 1 -->
        <div class="form-step is-active" data-step="1">
          <div class="form-card__head">
            <h2>Hesab yarat</h2>
            <p>Rolunu seç və telefon nömrəni daxil et — SMS ilə təsdiq kodu göndərəcəyik.</p>
          </div>

          <div class="field">
            <label>Necə istifadə edəcəksən?</label>
            <div class="roles">
              <div class="role-opt">
                <input type="radio" name="rol" id="rolSernisin" value="passenger" checked>
                <label for="rolSernisin">
                  <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1"/></svg></i>
                  <b>Sərnişin</b>
                  <span>Marşrut axtarıb yer bron edəcəyəm</span>
                </label>
              </div>
              <div class="role-opt">
                <input type="radio" name="rol" id="rolSurucu" value="driver">
                <label for="rolSurucu">
                  <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M6.5 17l-1.2-5.4A2 2 0 017.2 9h9.6a2 2 0 011.9 2.6L17.5 17M4 17v2M20 17v2M8 9l1-3h6l1 3"/></svg></i>
                  <b>Sürücü</b>
                  <span>Marşrut elanı verib sərnişin götürəcəyəm</span>
                </label>
              </div>
            </div>
          </div>

          <div class="field">
            <label for="adSoyad">Ad, Soyad</label>
            <input class="input" type="text" id="adSoyad" name="adSoyad" placeholder="Rəşad Məmmədov" autocomplete="name" required>
          </div>

          <div class="field">
            <label for="telefon">Telefon nömrəsi</label>
            <div class="phone-input">
              <span class="phone-input__cc">
                <svg viewBox="0 0 22 16" aria-hidden="true"><rect width="22" height="5.33" fill="#00B5E2"/><rect y="5.33" width="22" height="5.33" fill="#EF3340"/><rect y="10.66" width="22" height="5.34" fill="#509E2F"/><circle cx="10" cy="8" r="2.4" fill="#fff"/><circle cx="10.8" cy="8" r="2" fill="#EF3340"/></svg>
                +994
              </span>
              <input type="tel" id="telefon" name="telefon" placeholder="50 123 45 67" inputmode="numeric" autocomplete="tel-national" maxlength="12" required>
            </div>
            <p class="field__hint">Nömrə yalnız təsdiq və bron zamanı istifadə olunur, elanlarda göstərilmir.</p>
          </div>

          <div class="field">
            <label for="seher">Əsas şəhərin</label>
            <select class="select" id="seher" name="seher">
              <option value="">Seç (istəyə bağlı)</option>
              @foreach ($cities as $city)
                <option value="{{ $city->id }}">{{ $city->name }}</option>
              @endforeach
            </select>
          </div>

          <label class="checkbox">
            <input type="checkbox" id="qaydalar" required>
            <span><a href="{{route('terms-of-use')}}" target="_blank">İstifadə qaydaları</a> və <a href="{{route('privacy-policy')}}" target="_blank">Məxfilik siyasəti</a> ilə tanış oldum və qəbul edirəm.</span>
          </label>

          <button type="button" class="btn btn--brand btn--lg btn--block" id="sendCode">
            <span>Təsdiq kodu göndər</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
          <div id="recaptcha-container" style="margin-top:14px"></div>
        </div>

        <!-- Addım 2 -->
        <div class="form-step" data-step="2">
          <div class="form-card__head">
            <h2>Nömrəni təsdiqlə</h2>
            <p><b id="phoneEcho">+994 —</b> nömrəsinə 6 rəqəmli kod göndərdik.
              <button type="button" id="changePhone" style="color:var(--brand);font-weight:700;text-decoration:underline">Nömrəni dəyiş</button>
            </p>
          </div>

          <div class="field">
            <label>Təsdiq kodu</label>
            <div class="otp" id="otp">
              <input type="text" inputmode="numeric" maxlength="1" aria-label="1-ci rəqəm">
              <input type="text" inputmode="numeric" maxlength="1" aria-label="2-ci rəqəm">
              <input type="text" inputmode="numeric" maxlength="1" aria-label="3-cü rəqəm">
              <input type="text" inputmode="numeric" maxlength="1" aria-label="4-cü rəqəm">
              <input type="text" inputmode="numeric" maxlength="1" aria-label="5-ci rəqəm">
              <input type="text" inputmode="numeric" maxlength="1" aria-label="6-cı rəqəm">
            </div>
            <p class="field__hint" id="resendHint">Kod gəlmədi? <button type="button" id="resend" style="color:var(--brand);font-weight:700" disabled>Yenidən göndər</button> <span id="timer">(60 san)</span></p>
          </div>

          <button type="button" class="btn btn--brand btn--lg btn--block" id="verifyCode">
            <span>Təsdiqlə və davam et</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
        </div>

        <!-- Addım 3 — sürücü məlumatları -->
        <div class="form-step" data-step="3">
          <div class="form-card__head">
            <h2>Avtomobil məlumatları</h2>
            <p>Sərnişinlər hansı maşınla gedəcəklərini əvvəlcədən görsün.</p>
          </div>

          <div class="mock__row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field"><label for="marka">Marka</label><input class="input" id="marka" placeholder="Toyota"></div>
            <div class="field"><label for="model">Model</label><input class="input" id="model" placeholder="Prius"></div>
          </div>
          <div class="mock__row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field"><label for="reng">Rəng</label><input class="input" id="reng" placeholder="Ağ"></div>
            <div class="field"><label for="il">Buraxılış ili</label><input class="input" id="il" inputmode="numeric" placeholder="2018" maxlength="4"></div>
          </div>
          <div class="field">
            <label for="nomre">Dövlət qeydiyyat nişanı</label>
            <input class="input" id="nomre" placeholder="10-AB-123" style="text-transform:uppercase">
            <p class="field__hint">Nömrə yalnız bron təsdiqlənmiş sərnişinə göstərilir.</p>
          </div>

          <div class="callout callout--warn" style="margin-bottom:22px">
            <p>Sənədlər (şəxsiyyət vəsiqəsi, sürücülük vəsiqəsi, texpasport, sığorta) mobil tətbiqin <b>Profil → Sənədlər</b> bölməsindən yüklənir. Təsdiqdən sonra profilində “Təsdiqlənmiş sürücü” nişanı görünəcək.</p>
          </div>

          <button type="button" class="btn btn--brand btn--lg btn--block" id="finishDriver">
            <span>Qeydiyyatı tamamla</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </button>
          <button type="button" class="btn btn--ghost btn--block" style="margin-top:10px" id="skipDriver">Sonra doldururam</button>
        </div>

        <!-- Addım 4 — uğur -->
        <div class="form-step" data-step="4">
          <div style="text-align:center;padding:10px 0 6px">
            <div id="successMark" style="width:92px;height:92px;margin:0 auto 26px;border-radius:30px;background:var(--grad-brand);display:grid;place-items:center;box-shadow:var(--sh-brand)">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#04301f" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path id="successPath" d="M4 12.5l5.2 5.2L20 7"/></svg>
            </div>
            <h2 style="font-size:clamp(24px,2.6vw,30px)">Xoş gəldin, <span id="welcomeName" class="grad-text">dost</span>!</h2>
            <p style="color:var(--muted);margin-top:12px;font-size:16px">
              Hesabın yaradıldı. İndi mobil tətbiqə eyni nömrə ilə daxil ol və
              <span id="roleHint">marşrut axtarmağa</span> başla.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:30px">
              <a href="{{ url('/') }}" class="btn btn--ghost">Ana səhifəyə qayıt</a>
              <a href="#" class="btn btn--brand">Tətbiqi yüklə</a>
            </div>
            <p style="margin-top:24px;font-size:13.5px;color:var(--muted-2)">Sualın var? <a href="{{ route('contact') }}" style="color:var(--brand);font-weight:700">Bizimlə əlaqə saxla</a></p>
          </div>
        </div>

      </form>
    </div>

    <!-- ============ YAN PANEL ============ -->
    <aside>
      <div class="aside-card reveal" data-anim="fade-left">
        <div class="hero__glow hero__glow--1" aria-hidden="true"></div>
        <h3>Nə əldə edirsən?</h3>
        <p>Qeydiyyat pulsuzdur və 1 dəqiqədən az çəkir.</p>
        <ul class="aside-list">
          <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span><b>Limitsiz elan və bron</b> — heç bir abunə və ya komissiya yoxdur.</span></li>
          <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span><b>Təsdiqlənmiş profillər</b> — hər nömrə SMS ilə yoxlanılır.</span></li>
          <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span><b>Reytinq sistemi</b> — kiminlə yola çıxdığını əvvəlcədən bil.</span></li>
          <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span><b>Anlıq bildirişlər</b> — bron və təsdiqlərdən dərhal xəbər tut.</span></li>
          <li><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12l5 5L20 6"/></svg></i><span><b>Nömrə məxfiliyi</b> — əlaqə yalnız bron təsdiqindən sonra açılır.</span></li>
        </ul>
        <p class="aside-note">Qeydiyyatdan keçməklə <a href="{{route('terms-of-use')}}" style="color:var(--brand-2)">istifadə qaydalarını</a> qəbul etmiş olursan. 18 yaşdan kiçik istifadəçilər qeydiyyatdan keçə bilməz.</p>
      </div>

      <div class="card reveal" data-anim="fade-left" style="margin-top:18px">
        <div class="card__icon card--warm"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <h3>Artıq hesabın var?</h3>
        <p>Mobil tətbiqə eyni telefon nömrəsi ilə daxil ol — məlumatların yerindədir.</p>
      </div>
    </aside>

  </div>
</section>
</main>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets') }}/js/auth.js?v={{ filemtime(public_path('assets/js/auth.js')) }}"></script>
@endpush