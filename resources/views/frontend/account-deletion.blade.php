@extends('layout.app')
@section('content')
<main>

<section class="page-head">
  <div class="hero__bg" aria-hidden="true"><div class="hero__glow hero__glow--1"></div></div>
  <div class="hero__grid" aria-hidden="true"></div>
  <div class="container">
    <div class="crumbs"><a href="index.html">Ana səhifə</a><span>/</span><span>Hesabın silinməsi</span></div>
    <h1 data-split>Hesabın və məlumatların silinməsi</h1>
    <p class="lead">Hesabını istənilən vaxt silə bilərsən. Aşağıda tətbiqdaxili yol və onlayn müraciət forması göstərilib.</p>
    
  </div>
</section>


<section class="section section--tight">
  <div class="container">
    <div class="grid grid--2" style="align-items:start">

      <div>
        <div class="sec-head" style="margin-bottom:28px">
          <span class="eyebrow reveal" data-anim="fade-up">Üsul 1 — tətbiqdən</span>
          <h2 style="font-size:clamp(24px,2.6vw,32px)" class="reveal" data-anim="fade-up">3 toxunuşla sil</h2>
        </div>
        <div class="steps" style="grid-template-columns:1fr;gap:22px">
          <article class="step reveal" data-anim="fade-up">
            <div class="step__num">1</div>
            <h3>Tənzimləmələr bölməsinə keç</h3>
            <p>Tətbiqi aç → aşağı menyudan <strong>Profil</strong> → yuxarı sağda <strong>Tənzimləmələr</strong> ikonu.</p>
          </article>
          <article class="step reveal" data-anim="fade-up">
            <div class="step__num">2</div>
            <h3>“Hesabı sil” düyməsini seç</h3>
            <p>Səhifənin ən altında yerləşir. Aktiv bronun varsa, əvvəlcə onları ləğv etməli olacaqsan.</p>
          </article>
          <article class="step reveal" data-anim="fade-up">
            <div class="step__num">3</div>
            <h3>SMS kodu ilə təsdiqlə</h3>
            <p>Təhlükəsizlik üçün nömrənə kod göndərilir. Kodu daxil etdikdən sonra hesab dərhal deaktiv olunur.</p>
          </article>
        </div>

        <div class="callout" style="margin-top:32px">
          <p>Hesabı silmək geri qaytarıla bilməz. Səfər tarixçən, reytinqin və rəylərin bərpa olunmur.</p>
        </div>

        <h3 style="margin-top:44px;margin-bottom:14px">Nə silinir?</h3>
        <div class="prose" style="max-width:none">
          <table>
            <tr><th>Məlumat</th><th>Nə baş verir</th><th>Müddət</th></tr>
            <tr><td>Profil (ad, foto, telefon)</td><td>Tam silinir</td><td>30 gün</td></tr>
            <tr><td>Sürücü sənədləri</td><td>Tam silinir</td><td>30 gün</td></tr>
            <tr><td>Aktiv elanlar</td><td>Dərhal siyahıdan çıxarılır</td><td>Dərhal</td></tr>
            <tr><td>Bron tarixçəsi</td><td>Anonimləşdirilir</td><td>30 gün</td></tr>
            <tr><td>Yazdığın rəylər</td><td>Ad gizlədilir, mətn qalır</td><td>—</td></tr>
            <tr><td>Mühasibat/hüquqi qeydlər</td><td>Qanun tələbi ilə saxlanılır</td><td>Qanunla müəyyən müddət</td></tr>
          </table>
        </div>
      </div>

      <div>
        <div class="form-card reveal" data-anim="fade-left">
          <div class="form-card__head">
            <span class="eyebrow">Üsul 2 — onlayn müraciət</span>
            <h2>Silinmə tələbi göndər</h2>
            <p>Tətbiqə girişin yoxdursa, formanı doldur — müraciətini 30 gün ərzində icra edirik.</p>
          </div>

          <form id="deleteForm" novalidate>
            <div class="field">
              <label for="dName">Ad, Soyad</label>
              <input class="input" id="dName" placeholder="Hesabda qeydiyyatda olan ad" required>
            </div>
            <div class="field">
              <label for="dPhone">Hesabın telefon nömrəsi</label>
              <div class="phone-input">
                <span class="phone-input__cc">+994</span>
                <input type="tel" id="dPhone" placeholder="50 123 45 67" inputmode="numeric" maxlength="12" required>
              </div>
            </div>
            <div class="field">
              <label for="dReason">Səbəb (istəyə bağlı)</label>
              <select class="select" id="dReason">
                <option value="">Seçim et</option>
                <option>Artıq istifadə etmirəm</option>
                <option>Məxfilik narahatlığı</option>
                <option>Çox bildiriş gəlir</option>
                <option>Texniki problemlər</option>
                <option>Digər</option>
              </select>
            </div>
            <div class="field">
              <label for="dNote">Əlavə qeyd</label>
              <textarea class="textarea" id="dNote" placeholder="Bizə nəyi düzəltməli olduğumuzu yaza bilərsən..."></textarea>
            </div>
            <label class="checkbox">
              <input type="checkbox" id="dConfirm" required>
              <span>Hesabımın və bütün məlumatlarımın <strong>geri dönməz şəkildə</strong> silinməsini təsdiqləyirəm.</span>
            </label>
            <button type="submit" class="btn btn--lg btn--block" style="background:#E5484D;color:#fff">
              Silinmə tələbini göndər
            </button>
            <p class="field__hint" style="text-align:center;margin-top:14px">
              Forma e-poçt tətbiqini açacaq. Birbaşa yaza bilərsən:
              <a href="mailto:destek@yolyodasi.az" style="color:var(--brand);font-weight:700">destek@yolyodasi.az</a>
            </p>
          </form>
        </div>

        <div class="card reveal" data-anim="fade-left" style="margin-top:18px">
          <div class="card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
          <h3>Silmək əvəzinə fasilə?</h3>
          <p>Yalnız bildirişlərdən narahatsansa, tətbiqin <strong>Tənzimləmələr → Bildirişlər</strong> bölməsindən hamısını söndürə bilərsən — hesab qalır.</p>
        </div>
      </div>

    </div>
  </div>
</section>
</main>
@endsection