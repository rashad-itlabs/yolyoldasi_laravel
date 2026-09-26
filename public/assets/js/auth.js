/* =========================================================================
   Yol Yoldaşı — veb qeydiyyat axını (telefon + SMS OTP)
   Firebase konfiqurasiyası js/firebase-config.js faylındadır.
   Konfiqurasiya boşdursa DEMO rejim işə düşür.
   ========================================================================= */
import { firebaseConfig, USERS_COLLECTION, COUNTRY_CODE } from './firebase-config.js';

const FB_VERSION = '10.12.2';
const DEMO = !firebaseConfig.apiKey || !firebaseConfig.projectId;

const $ = (s, c = document) => c.querySelector(s);
const $$ = (s, c = document) => Array.prototype.slice.call(c.querySelectorAll(s));
const gsapReady = typeof window.gsap !== 'undefined'
  && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const form = $('#signupForm');
if (form) initSignup();

function initSignup() {
  /* ------------------------------------------------------------------
     Vəziyyət
  ------------------------------------------------------------------ */
  const state = {
    step: 1,
    role: 'passenger',
    name: '',
    phone: '',      // yalnız rəqəmlər: 501234567
    cityId: '',
    cityName: '',
    uid: null,
    confirmation: null,
    idToken: null,  // Firebase təsdiqindən sonra dolur
    apiToken: null  // Laravel Sanctum tokeni
  };

  /* ------------------------------------------------------------------
     Laravel API
  ------------------------------------------------------------------ */
  const API = '/api/v1';

  async function api(path, body, token) {
    const res = await fetch(API + path, {
      method: 'POST',
      headers: Object.assign(
        { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        token ? { Authorization: 'Bearer ' + token } : {}
      ),
      body: JSON.stringify(body)
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const first = data.errors && Object.values(data.errors)[0];
      throw new Error((first && first[0]) || data.message || 'Server xətası (' + res.status + ')');
    }
    return data;
  }

  let auth = null, db = null, fbApi = null, recaptcha = null;

  /* ------------------------------------------------------------------
     Xəbərdarlıq mesajları
  ------------------------------------------------------------------ */
  const errBox = $('#formErr'), errText = $('#formErrText');
  const infoBox = $('#formInfo'), infoText = $('#formInfoText');

  function showErr(msg) {
    if (!errBox) return;
    errText.textContent = msg;
    errBox.classList.add('is-shown');
    if (gsapReady) gsap.fromTo(errBox, { y: -8, opacity: 0 }, { y: 0, opacity: 1, duration: .35 });
    errBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }
  function hideErr() { if (errBox) errBox.classList.remove('is-shown'); }
  function showInfo(msg) {
    if (!infoBox) return;
    infoText.innerHTML = msg;
    infoBox.classList.add('is-shown');
  }

  if (DEMO) {
    showInfo('<b>Demo rejim:</b> Firebase konfiqurasiyası əlavə edilməyib, ona görə SMS göndərilmir. ' +
             'Axını sınamaq üçün istənilən 6 rəqəm daxil et. Aktivləşdirmək üçün <code>js/firebase-config.js</code> faylını doldur.');
  }

  /* ------------------------------------------------------------------
     Addımlar arası keçid
  ------------------------------------------------------------------ */
  function goStep(n) {
    const current = $('.form-step.is-active', form);
    const next = $('.form-step[data-step="' + n + '"]', form);
    if (!next) return;
    state.step = n;
    hideErr();

    // progress bar
    $$('.steps-bar__item').forEach((bar) => {
      const idx = parseInt(bar.getAttribute('data-bar'), 10);
      const reached = n >= 3 ? 3 : n;
      bar.classList.toggle('is-done', idx < reached);
      bar.classList.toggle('is-active', idx <= reached);
    });

    const swap = () => {
      if (current) current.classList.remove('is-active');
      next.classList.add('is-active');
      if (gsapReady) {
        gsap.fromTo(next, { opacity: 0, x: 26 }, { opacity: 1, x: 0, duration: .45, ease: 'power3.out' });
        gsap.fromTo(next.querySelectorAll('.field, .btn, .callout, .checkbox, .form-card__head'),
          { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: .45, stagger: .05, ease: 'power3.out', clearProps: 'all' });
      }
      window.scrollTo({ top: form.getBoundingClientRect().top + window.pageYOffset - 110, behavior: 'smooth' });
    };

    if (gsapReady && current) gsap.to(current, { opacity: 0, x: -20, duration: .25, ease: 'power2.in', onComplete: swap });
    else swap();
  }

  /* ------------------------------------------------------------------
     Rol seçimi
  ------------------------------------------------------------------ */
  $$('input[name="rol"]').forEach((r) => {
    r.addEventListener('change', () => {
      state.role = r.value;
      if (gsapReady) {
        const card = r.nextElementSibling;
        gsap.fromTo(card, { scale: .97 }, { scale: 1, duration: .4, ease: 'back.out(2.5)' });
      }
    });
  });

  /* ------------------------------------------------------------------
     Telefon formatlama: 50 123 45 67
  ------------------------------------------------------------------ */
  const phoneInput = $('#telefon');
  if (phoneInput) {
    phoneInput.addEventListener('input', () => {
      let d = phoneInput.value.replace(/\D/g, '');
      if (d.startsWith('994')) d = d.slice(3);
      if (d.startsWith('0')) d = d.slice(1);
      d = d.slice(0, 9);
      const p = [d.slice(0, 2), d.slice(2, 5), d.slice(5, 7), d.slice(7, 9)].filter(Boolean);
      phoneInput.value = p.join(' ');
      state.phone = d;
    });
  }

  /* ------------------------------------------------------------------
     OTP xanaları
  ------------------------------------------------------------------ */
  const otpInputs = $$('#otp input');
  otpInputs.forEach((input, i) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '').slice(0, 1);
      input.classList.toggle('is-filled', !!input.value);
      if (input.value && otpInputs[i + 1]) otpInputs[i + 1].focus();
      if (otpCode().length === 6) $('#verifyCode').click();
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !input.value && otpInputs[i - 1]) otpInputs[i - 1].focus();
      if (e.key === 'ArrowLeft' && otpInputs[i - 1]) otpInputs[i - 1].focus();
      if (e.key === 'ArrowRight' && otpInputs[i + 1]) otpInputs[i + 1].focus();
    });
    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      digits.split('').forEach((d, k) => {
        if (otpInputs[k]) { otpInputs[k].value = d; otpInputs[k].classList.add('is-filled'); }
      });
      (otpInputs[Math.min(digits.length, 5)] || input).focus();
      if (digits.length === 6) $('#verifyCode').click();
    });
  });
  const otpCode = () => otpInputs.map((i) => i.value).join('');

  /* ------------------------------------------------------------------
     Firebase-in tənbəl yüklənməsi
  ------------------------------------------------------------------ */
  async function ensureFirebase() {
    if (DEMO || fbApi) return fbApi;
    const base = 'https://www.gstatic.com/firebasejs/' + FB_VERSION + '/';
    const [appMod, authMod, storeMod] = await Promise.all([
      import(base + 'firebase-app.js'),
      import(base + 'firebase-auth.js'),
      import(base + 'firebase-firestore.js')
    ]);
    const app = appMod.getApps().length ? appMod.getApp() : appMod.initializeApp(firebaseConfig);
    auth = authMod.getAuth(app);
    auth.languageCode = 'az';
    db = storeMod.getFirestore(app);
    fbApi = { ...authMod, ...storeMod };
    return fbApi;
  }

  async function ensureRecaptcha(api) {
    if (recaptcha) return recaptcha;
    recaptcha = new api.RecaptchaVerifier(auth, 'recaptcha-container', { size: 'invisible' });
    await recaptcha.render();
    return recaptcha;
  }

  /* ------------------------------------------------------------------
     Yüklənmə vəziyyəti olan düymə
  ------------------------------------------------------------------ */
  function busy(btn, on, text) {
    if (!btn) return;
    const label = btn.querySelector('span');
    if (on) {
      btn.dataset.label = label ? label.textContent : '';
      if (label) label.textContent = text || 'Gözlə...';
      btn.disabled = true;
    } else {
      if (label && btn.dataset.label) label.textContent = btn.dataset.label;
      btn.disabled = false;
    }
  }

  /* ------------------------------------------------------------------
     1. Kod göndər
  ------------------------------------------------------------------ */
  const sendBtn = $('#sendCode');
  sendBtn && sendBtn.addEventListener('click', async () => {
    hideErr();
    state.name = $('#adSoyad').value.trim();
    const citySel = $('#seher');
    state.cityId = citySel.value;
    state.cityName = citySel.value ? citySel.options[citySel.selectedIndex].text : '';

    if (state.name.length < 3) return fail($('#adSoyad'), 'Ad və soyadını tam yaz.');
    if (state.phone.length !== 9) return fail($('#telefon'), 'Telefon nömrəsi 9 rəqəm olmalıdır. Məsələn: 50 123 45 67');
    if (!$('#qaydalar').checked) return showErr('Davam etmək üçün istifadə qaydalarını qəbul etməlisən.');

    const full = COUNTRY_CODE + state.phone;
    $('#phoneEcho').textContent = COUNTRY_CODE + ' ' + $('#telefon').value;

    busy(sendBtn, true, 'Kod göndərilir...');
    try {
      if (!DEMO) {
        const api = await ensureFirebase();
        const verifier = await ensureRecaptcha(api);
        state.confirmation = await api.signInWithPhoneNumber(auth, full, verifier);
      } else {
        await new Promise((r) => setTimeout(r, 700));
      }
      busy(sendBtn, false);
      goStep(2);
      startTimer();
      setTimeout(() => otpInputs[0] && otpInputs[0].focus(), 500);
    } catch (e) {
      busy(sendBtn, false);
      showErr(firebaseError(e));
      if (recaptcha && recaptcha.clear) { try { recaptcha.clear(); } catch (_) {} recaptcha = null; }
    }
  });

  function fail(el, msg) {
    showErr(msg);
    if (el && gsapReady) gsap.fromTo(el.closest('.field') || el, { x: -8 }, { x: 0, duration: .5, ease: 'elastic.out(1,.35)' });
    if (el) el.focus();
  }

  /* ------------------------------------------------------------------
     2. Kodu təsdiqlə
  ------------------------------------------------------------------ */
  const verifyBtn = $('#verifyCode');
  let verifying = false;
  verifyBtn && verifyBtn.addEventListener('click', async () => {
    if (verifying) return;
    hideErr();
    const code = otpCode();
    if (code.length !== 6) return showErr('6 rəqəmli kodu tam daxil et.');

    verifying = true;
    busy(verifyBtn, true, 'Yoxlanılır...');
    try {
      if (!DEMO) {
        // Kod bir dəfə istifadə olunur — API xətasında yalnız sorğunu təkrarlayırıq.
        if (!state.idToken) {
          const res = await state.confirmation.confirm(code);
          state.uid = res.user.uid;
          state.idToken = await res.user.getIdToken();
        }
        const out = await api('/auth/firebase', {
          id_token:  state.idToken,
          full_name: state.name,
          role:      state.role,
          city_id:   state.cityId || null
        });
        state.apiToken = out.token;
      } else {
        await new Promise((r) => setTimeout(r, 700));
        state.uid = 'demo-' + Date.now();
      }
      await saveUser();
      busy(verifyBtn, false);
      verifying = false;
      if (state.role === 'driver') goStep(3);
      else finish();
    } catch (e) {
      verifying = false;
      busy(verifyBtn, false);
      showErr(firebaseError(e));
      // Nömrə artıq təsdiqlənibsə kod xanalarını təmizləmirik — yenidən "Təsdiqlə"
      // düyməsinə basmaq yalnız server sorğusunu təkrarlayacaq.
      if (!state.idToken) {
        otpInputs.forEach((i) => { i.value = ''; i.classList.remove('is-filled'); });
        otpInputs[0].focus();
        if (gsapReady) gsap.fromTo('#otp', { x: -10 }, { x: 0, duration: .5, ease: 'elastic.out(1,.35)' });
      }
    }
  });

  /* ------------------------------------------------------------------
     Yenidən göndərmə sayğacı
  ------------------------------------------------------------------ */
  let timerId = null;
  function startTimer(sec = 60) {
    const resend = $('#resend'), label = $('#timer');
    if (!resend) return;
    let left = sec;
    resend.disabled = true;
    clearInterval(timerId);
    label.textContent = '(' + left + ' san)';
    timerId = setInterval(() => {
      left--;
      label.textContent = '(' + left + ' san)';
      if (left <= 0) {
        clearInterval(timerId);
        resend.disabled = false;
        label.textContent = '';
      }
    }, 1000);
  }

  const resendBtn = $('#resend');
  resendBtn && resendBtn.addEventListener('click', () => {
    otpInputs.forEach((i) => { i.value = ''; i.classList.remove('is-filled'); });
    state.idToken = null;   // yeni kod = yeni token
    if (sendBtn) sendBtn.click();
    startTimer();
  });

  const changeBtn = $('#changePhone');
  changeBtn && changeBtn.addEventListener('click', () => {
    clearInterval(timerId);
    state.idToken = null;
    goStep(1);
  });

  /* ------------------------------------------------------------------
     3. Sürücü məlumatları
  ------------------------------------------------------------------ */
  const finishDriver = $('#finishDriver');
  finishDriver && finishDriver.addEventListener('click', async () => {
    const vehicle = {
      make: $('#marka').value.trim(),
      model: $('#model').value.trim(),
      color: $('#reng').value.trim(),
      year: $('#il').value.trim(),
      plate: $('#nomre').value.trim().toUpperCase()
    };
    if (!vehicle.make || !vehicle.model) return showErr('Ən azı marka və modeli qeyd et.');
    hideErr();
    busy(finishDriver, true, 'Yadda saxlanılır...');
    try {
      if (!DEMO && state.apiToken) {
        await api('/driver/vehicle', {
          brand: vehicle.make,
          model: vehicle.model,
          color: vehicle.color || null,
          plate: vehicle.plate || null,
          year:  vehicle.year ? parseInt(vehicle.year, 10) : null
        }, state.apiToken);
      }
      await saveUser({ vehicle });
      busy(finishDriver, false);
      finish();
    } catch (e) {
      busy(finishDriver, false);
      showErr(firebaseError(e));
    }
  });

  const skipDriver = $('#skipDriver');
  skipDriver && skipDriver.addEventListener('click', () => finish());

  /* ------------------------------------------------------------------
     Firestore-a yazma
  ------------------------------------------------------------------ */
  async function saveUser(extra = {}) {
    const payload = Object.assign({
      uid: state.uid,
      fullName: state.name,
      phoneNumber: COUNTRY_CODE + state.phone,
      role: state.role,
      city: state.cityName || null,
      cityId: state.cityId ? Number(state.cityId) : null,
      rating: 0,
      ratingCount: 0,
      isPhoneVerified: true,
      isDriverVerified: false,
      documentsStatus: state.role === 'driver' ? 'pending' : 'not_required',
      registeredFrom: 'web',
      updatedAt: new Date().toISOString()
    }, extra);

    if (DEMO) { console.info('[demo] istifadəçi:', payload); return; }
    try {
      const api = await ensureFirebase();
      await api.setDoc(
        api.doc(db, USERS_COLLECTION, state.uid),
        Object.assign({}, payload, { createdAt: api.serverTimestamp() }),
        { merge: true }
      );
    } catch (e) {
      // profil yazılmasa belə hesab yaradılıb — istifadəçini dayandırmırıq
      console.error('Firestore yazma xətası:', e);
    }
  }

  /* ------------------------------------------------------------------
     4. Uğur ekranı
  ------------------------------------------------------------------ */
  function finish() {
    const first = (state.name || '').split(' ')[0] || 'dost';
    $('#welcomeName').textContent = first;
    $('#roleHint').textContent = state.role === 'driver' ? 'ilk marşrut elanını verməyə' : 'marşrut axtarmağa';
    goStep(4);

    if (gsapReady) {
      const mark = $('#successMark'), path = $('#successPath');
      gsap.fromTo(mark, { scale: 0, rotate: -25 }, { scale: 1, rotate: 0, duration: .8, ease: 'back.out(1.8)', delay: .15 });
      if (path && path.getTotalLength) {
        const len = path.getTotalLength();
        gsap.fromTo(path, { strokeDasharray: len, strokeDashoffset: len },
          { strokeDashoffset: 0, duration: .6, ease: 'power2.out', delay: .55 });
      }
    }
    $$('.steps-bar__item').forEach((b) => b.classList.add('is-done', 'is-active'));
  }

  /* ------------------------------------------------------------------
     Xəta mətnləri (Azərbaycanca)
  ------------------------------------------------------------------ */
  function firebaseError(e) {
    const code = (e && e.code) || '';
    const map = {
      'auth/invalid-phone-number': 'Telefon nömrəsi düzgün formatda deyil.',
      'auth/missing-phone-number': 'Telefon nömrəsini daxil et.',
      'auth/quota-exceeded': 'SMS limiti doldu. Bir qədər sonra yenidən cəhd et.',
      'auth/too-many-requests': 'Çox sayda cəhd edildi. Bir neçə dəqiqə gözlə.',
      'auth/invalid-verification-code': 'Kod yanlışdır. Yenidən yoxla.',
      'auth/code-expired': 'Kodun vaxtı bitib. Yeni kod istə.',
      'auth/captcha-check-failed': 'Təhlükəsizlik yoxlaması alınmadı. Səhifəni yenilə.',
      'auth/operation-not-allowed': 'Telefonla giriş Firebase-də aktiv edilməyib.',
      'auth/network-request-failed': 'İnternet bağlantısını yoxla.'
    };
    if (map[code]) return map[code];
    console.error(e);
    // Firebase kodu yoxdursa xəta API-dən gəlib — mesajı olduğu kimi göstəririk.
    if (!code && e && e.message) return e.message;
    return 'Gözlənilməz xəta baş verdi. Yenidən cəhd et və ya destek@yolyodasi.az ünvanına yaz.';
  }
}
