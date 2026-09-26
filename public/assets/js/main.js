/* =========================================================================
   Yol Yoldaşı — UI + GSAP animasiyaları
   ========================================================================= */
(function () {
  'use strict';

  var root = document.documentElement;
  var hasGSAP = typeof window.gsap !== 'undefined';
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!hasGSAP || reduced) root.classList.add('no-gsap');
  if (hasGSAP && window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

  /* ---------------------------------------------------------------
     Köməkçilər
  --------------------------------------------------------------- */
  function $(s, c) { return (c || document).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

  /** Mətni sözlərə bölür, daxili teqləri qoruyur */
  function splitWords(root) {
    var words = [];
    if (root.dataset && root.dataset.splitDone) return words;
    if (root.dataset) root.dataset.splitDone = '1';
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          if (!child.textContent.trim()) return;
          var frag = document.createDocumentFragment();
          child.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) return;
            if (!part.trim()) { frag.appendChild(document.createTextNode(part)); return; }
            var s = document.createElement('span');
            s.className = 'split-word';
            s.textContent = part;
            frag.appendChild(s);
            words.push(s);
          });
          node.replaceChild(frag, child);
        } else if (child.nodeType === 1) walk(child);
      });
    })(root);
    return words;
  }

  /* ---------------------------------------------------------------
     1. İl + naviqasiya vəziyyəti
  --------------------------------------------------------------- */
  var yearEl = $('#year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  var nav = $('#nav');
  var progress = $('#navProgress');

  function onScroll() {
    var y = window.pageYOffset;
    if (nav) nav.classList.toggle('is-stuck', y > 30);
    if (progress) {
      var h = document.body.scrollHeight - window.innerHeight;
      progress.style.transform = 'scaleX(' + (h > 0 ? Math.min(y / h, 1) : 0) + ')';
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* Aktiv bölmə vurğusu */
  var sectionLinks = $$('.nav__link[href^="#"]');
  if (sectionLinks.length && 'IntersectionObserver' in window) {
    var byId = {};
    sectionLinks.forEach(function (a) { byId[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        var link = byId[e.target.id];
        if (!link) return;
        if (e.isIntersecting) {
          sectionLinks.forEach(function (l) { l.classList.remove('is-active'); });
          link.classList.add('is-active');
        }
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    Object.keys(byId).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) io.observe(el);
    });
  }

  /* ---------------------------------------------------------------
     2. Mobil menyu
  --------------------------------------------------------------- */
  var burger = $('#burger');
  var menu = $('#mobileMenu');
  var menuOpen = false;

  function toggleMenu(open) {
    if (!menu || !nav) return;
    menuOpen = typeof open === 'boolean' ? open : !menuOpen;
    nav.classList.toggle('is-open', menuOpen);
    menu.classList.toggle('is-open', menuOpen);
    menu.setAttribute('aria-hidden', String(!menuOpen));
    if (burger) burger.setAttribute('aria-expanded', String(menuOpen));
    document.body.classList.toggle('noscroll', menuOpen);

    var items = $$('a', menu);
    if (!hasGSAP || reduced) {
      menu.style.clipPath = menuOpen ? 'inset(0 0 0% 0)' : 'inset(0 0 100% 0)';
      items.forEach(function (i) { i.style.opacity = menuOpen ? 1 : 0; });
      return;
    }
    if (menuOpen) {
      gsap.timeline()
        .to(menu, { clipPath: 'inset(0 0 0% 0)', duration: .6, ease: 'power4.inOut' })
        .fromTo(items, { y: 26, opacity: 0 }, { y: 0, opacity: 1, duration: .5, stagger: .05, ease: 'power3.out' }, '-=.3');
    } else {
      gsap.timeline()
        .to(items, { y: 12, opacity: 0, duration: .22, stagger: .02 })
        .to(menu, { clipPath: 'inset(0 0 100% 0)', duration: .45, ease: 'power4.inOut' }, '-=.1');
    }
  }

  if (burger) burger.addEventListener('click', function () { toggleMenu(); });
  if (menu) $$('a', menu).forEach(function (a) { a.addEventListener('click', function () { toggleMenu(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && menuOpen) toggleMenu(false); });

  /* ---------------------------------------------------------------
     3. Hero giriş animasiyası
  --------------------------------------------------------------- */
  function heroIntro() {
    var hero = $('#hero') || $('.page-head');
    if (!hero) return;

    // CSS gizlətməsini götürürük ki, gsap.from() son vəziyyəti düzgün oxusun
    var heroReveals = $$('.reveal', hero);
    if (heroReveals.length) gsap.set(heroReveals, { opacity: 1 });

    var heading = $('[data-split]', hero);
    var words = heading ? splitWords(heading) : [];
    if (heading) heading.style.opacity = 1;

    var tl = gsap.timeline({ defaults: { ease: 'power3.out' }, delay: .12 });

    // Boş selektorlara tween verməmək üçün köməkçi
    function step(sel, vars, pos) {
      var targets = typeof sel === 'string' ? $$(sel, hero) : sel;
      if (!targets || !targets.length) return;
      tl.from(targets, vars, pos);
    }

    step('.hero__badge, .crumbs', { y: 22, opacity: 0, duration: .7 });
    step(words, { y: 46, opacity: 0, rotateX: -55, duration: .85, stagger: .045 }, '-=.45');
    step('.hero__sub, .page-head .lead, .update-chip', { y: 22, opacity: 0, duration: .7 }, '-=.55');
    step('.hero__cta > *', { y: 22, opacity: 0, duration: .6, stagger: .09 }, '-=.45');
    step('.hero__stat', { y: 20, opacity: 0, duration: .6, stagger: .07 }, '-=.4');

    // Telefon maketi
    var phone = $('#phone');
    if (phone) {
      step([phone], { y: 70, opacity: 0, rotateY: -16, rotateX: 8, duration: 1.1, ease: 'power3.out' }, '-=1.1');
      step($$('[data-ride]'), { y: 26, opacity: 0, duration: .55, stagger: .12 }, '-=.6');
      step($$('.phone__cta'), { y: 16, opacity: 0, duration: .5 }, '-=.3');
      step($$('[data-float]'), { scale: .7, opacity: 0, duration: .6, stagger: .12, ease: 'back.out(2)' }, '-=.45');

      // yumşaq üzmə effekti — `yPercent` ayrıca xassədir, intro/parallaks `y`-ni sərbəst buraxır
      gsap.to(phone, { yPercent: -2.2, duration: 3.4, ease: 'sine.inOut', yoyo: true, repeat: -1 });
      $$('[data-float]').forEach(function (chip, i) {
        gsap.to(chip, { y: i % 2 ? 12 : -12, duration: 2.8 + i * .5, ease: 'sine.inOut', yoyo: true, repeat: -1, delay: i * .3 });
      });
    }

    // Loqo marşrutunun çəkilməsi
    var route = $('.brand__route');
    if (route && route.getTotalLength) {
      var len = route.getTotalLength();
      gsap.fromTo(route, { strokeDasharray: len, strokeDashoffset: len }, {
        strokeDashoffset: 0, duration: 1.3, ease: 'power2.inOut', delay: .2,
        onComplete: function () { route.style.strokeDasharray = 'none'; }
      });
    }
  }

  /* ---------------------------------------------------------------
     4. Scroll reveal
  --------------------------------------------------------------- */
  var PRESETS = {
    'fade-up':   { y: 34, opacity: 0 },
    'fade-left': { x: 46, opacity: 0 },
    'fade-right':{ x: -46, opacity: 0 },
    'scale':     { scale: .94, opacity: 0 }
  };

  /** Gizli konteynerdədirsə (məs. aktiv olmayan tab paneli) animasiya etmirik */
  function isHidden(el) {
    return !el.offsetParent && getComputedStyle(el).position !== 'fixed';
  }

  /** fromTo — son vəziyyət həmişə açıq yazılır, heç vaxt clearProps istifadə olunmur */
  function revealTween(targets, from, extra) {
    var to = { opacity: 1, ease: 'power3.out', overwrite: 'auto' };
    if ('y' in from) to.y = 0;
    if ('x' in from) to.x = 0;
    if ('scale' in from) to.scale = 1;
    if ('rotateX' in from) to.rotateX = 0;
    Object.assign(to, extra);
    return gsap.fromTo(targets, from, to);
  }

  function revealOnScroll() {
    // 1. Başlıqlar — sözbəsöz
    $$('[data-split]').forEach(function (el) {
      if (el.dataset.splitDone) return;
      if (el.closest('#hero') || el.closest('.page-head')) return;
      var words = splitWords(el);
      el.style.opacity = 1;
      if (!words.length) return;
      revealTween(words, { y: 34, opacity: 0, rotateX: -45 }, {
        duration: .8, stagger: .035, transformPerspective: 700,
        scrollTrigger: { trigger: el, start: 'top 88%' }
      });
    });

    // 2. Kart şəbəkələri — TƏK trigger, pilləli.
    //    Kartlar aşağıdakı ümumi keçiddən kənarlaşdırılır ki, iki tween eyni elementi idarə etməsin.
    $$('.grid').forEach(function (grid) {
      var cards = $$(':scope > .card', grid);
      if (cards.length < 2) return;
      cards.forEach(function (c) { c.dataset.revealDone = '1'; });
      revealTween(cards, { y: 30, opacity: 0 }, {
        duration: .7, stagger: .08,
        scrollTrigger: { trigger: grid, start: 'top 85%' }
      });
    });

    // 3. Qalan elementlər
    $$('.reveal').forEach(function (el) {
      if (el.dataset.revealDone) return;
      el.dataset.revealDone = '1';
      if (el.closest('#hero') || el.closest('.page-head')) { gsap.set(el, { opacity: 1 }); return; }
      if (isHidden(el)) { gsap.set(el, { opacity: 1 }); return; }
      var from = PRESETS[el.getAttribute('data-anim') || 'fade-up'] || PRESETS['fade-up'];
      revealTween(el, Object.assign({}, from), {
        duration: .85,
        scrollTrigger: { trigger: el, start: 'top 90%' }
      });
    });

    // Addım xətti
    var line = $('.steps__line');
    if (line) {
      gsap.fromTo(line, { scaleX: 0 }, {
        scaleX: 1, transformOrigin: 'left center', duration: 1.4, ease: 'power2.inOut',
        scrollTrigger: { trigger: '.steps', start: 'top 75%' }
      });
    }

    // Hero parallaksı — #phone-a yox, onu saxlayan konteynerə tətbiq olunur
    // (əks halda üzmə və intro tween-ləri ilə eyni `y` xassəsi üstündə toqquşur).
    var visual = $('.hero__visual');
    if (visual && window.innerWidth > 1080) {
      gsap.to(visual, {
        scrollTrigger: { trigger: '#hero', start: 'top top', end: 'bottom top', scrub: 1 },
        y: 90, opacity: .55, ease: 'none'
      });
    }

    // Tünd bölmələrdə işıq hərəkəti
    $$('.cta__glow, .hero__glow').forEach(function (g, i) {
      gsap.to(g, {
        scrollTrigger: { trigger: g.closest('section') || g.parentElement, start: 'top bottom', end: 'bottom top', scrub: 1.2 },
        y: i % 2 ? -70 : 70, ease: 'none'
      });
    });
  }

  /* ---------------------------------------------------------------
     5. Rəqəm sayğacları
  --------------------------------------------------------------- */
  function counters() {
    $$('[data-count]').forEach(function (el) {
      var target = parseFloat(el.getAttribute('data-count'));
      var dec = parseInt(el.getAttribute('data-decimals') || '0', 10);
      var suffix = el.getAttribute('data-suffix') || '';
      var obj = { v: 0 };

      function render() {
        // Minlik ayırıcı kimi boşluq — toLocaleString('az-AZ') bəzi brauzerlərdə
        // vergülə düşür ("12,400") və onluq kəsr kimi oxunur.
        var v = dec ? obj.v.toFixed(dec)
                    : String(Math.round(obj.v)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        el.textContent = v + suffix;
      }
      render();

      if (!hasGSAP || reduced) {
        obj.v = target; render(); return;
      }

      function play(delay) {
        gsap.to(obj, { v: target, duration: 1.8, ease: 'power2.out', onUpdate: render, delay: delay || 0 });
      }

      // Yüklənən anda artıq ekrandadırsa, ScrollTrigger-i gözləmirik —
      // əks halda qısa ekranlarda hero rəqəmləri "0" olaraq donub qalır.
      var rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight && rect.bottom > 0) {
        play(el.closest('#hero') ? 1.1 : .2);
      } else {
        ScrollTrigger.create({
          trigger: el, start: 'top 95%', once: true,
          onEnter: function () { play(0); }
        });
      }
    });
  }

  /* ---------------------------------------------------------------
     6. Marşrut lenti (marquee)
  --------------------------------------------------------------- */
  function marquee() {
    var track = $('#marquee');
    if (!track) return;
    var items = $$('.marquee__item', track);
    if (!items.length) return;

    items.forEach(function (i) { track.appendChild(i.cloneNode(true)); });
    // Dəqiq sürüşmə: ilk klonun offsetLeft-i = bir dəstin eni + aralıq (gap).
    // scrollWidth/2 istifadə etsək, flex gap səbəbindən hər dövrədə kiçik tullanma olur.
    var shift = track.children[items.length].offsetLeft;

    if (!hasGSAP || reduced) return;
    var tween = gsap.to(track, { x: -shift, duration: shift / 55, ease: 'none', repeat: -1 });
    track.parentElement.addEventListener('mouseenter', function () { gsap.to(tween, { timeScale: .25, duration: .4 }); });
    track.parentElement.addEventListener('mouseleave', function () { gsap.to(tween, { timeScale: 1, duration: .4 }); });
  }

  /* ---------------------------------------------------------------
     7. Rol tabları
  --------------------------------------------------------------- */
  function tabs() {
    var wrap = $('#roleTabs');
    if (!wrap) return;
    var pill = $('#tabsPill', wrap);
    var btns = $$('.tab', wrap);

    function movePill(btn, animate) {
      if (!pill) return;
      var props = { left: btn.offsetLeft + 'px', width: btn.offsetWidth + 'px' };
      if (hasGSAP && animate && !reduced) gsap.to(pill, { left: props.left, width: props.width, duration: .45, ease: 'power3.out' });
      else { pill.style.left = props.left; pill.style.width = props.width; }
    }

    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.classList.contains('is-active')) return;
        btns.forEach(function (b) { b.classList.remove('is-active'); b.setAttribute('aria-selected', 'false'); });
        btn.classList.add('is-active');
        btn.setAttribute('aria-selected', 'true');
        movePill(btn, true);

        var id = btn.getAttribute('data-tab');
        $$('.panel').forEach(function (p) { p.classList.remove('is-active'); });
        var panel = document.getElementById(id);
        if (!panel) return;
        panel.classList.add('is-active');

        if (hasGSAP && !reduced) {
          gsap.fromTo(panel, { opacity: 0, y: 18 },
            { opacity: 1, y: 0, duration: .5, ease: 'power3.out', overwrite: 'auto' });
          // clearProps BURADA İSTİFADƏ EDİLMİR: CSS `.reveal { opacity: 0 }` qaydası
          // inline dəyər silinən kimi qayıdır və maket yenidən görünməz olurdu.
          gsap.fromTo($$('.role__list li, .mock', panel), { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: .5, stagger: .06, ease: 'power3.out', delay: .05, overwrite: 'auto' });
        }
        if (window.ScrollTrigger) ScrollTrigger.refresh();
      });
    });

    var active = $('.tab.is-active', wrap) || btns[0];
    if (active) movePill(active, false);
    window.addEventListener('resize', function () {
      var a = $('.tab.is-active', wrap);
      if (a) movePill(a, false);
    });
  }

  /* ---------------------------------------------------------------
     8. Akkordeon (FAQ)
  --------------------------------------------------------------- */
  function accordion() {
    $$('.faq__item').forEach(function (item) {
      var q = $('.faq__q', item);
      var a = $('.faq__a', item);
      if (!q || !a) return;

      q.setAttribute('aria-expanded', 'false');
      q.addEventListener('click', function () {
        var open = item.classList.contains('is-open');

        $$('.faq__item.is-open').forEach(function (other) {
          if (other === item) return;
          other.classList.remove('is-open');
          var oa = $('.faq__a', other);
          var oq = $('.faq__q', other);
          if (oq) oq.setAttribute('aria-expanded', 'false');
          if (hasGSAP && !reduced) gsap.to(oa, { height: 0, duration: .35, ease: 'power2.inOut' });
          else oa.style.height = '0px';
        });

        item.classList.toggle('is-open', !open);
        q.setAttribute('aria-expanded', String(!open));

        if (hasGSAP && !reduced) {
          gsap.to(a, { height: open ? 0 : 'auto', duration: .45, ease: 'power3.inOut' });
        } else {
          a.style.height = open ? '0px' : 'auto';
        }
      });
    });
  }

  /* ---------------------------------------------------------------
     9. Kiçik detallar: maqnit düymələr, kart işığı, telefon tilt
  --------------------------------------------------------------- */
  function microInteractions() {
    if (!hasGSAP || reduced) return;
    var fine = window.matchMedia('(pointer: fine)').matches;
    if (!fine) return;

    $$('.btn--brand, .store-btn').forEach(function (btn) {
      btn.addEventListener('mousemove', function (e) {
        var r = btn.getBoundingClientRect();
        gsap.to(btn, {
          x: (e.clientX - r.left - r.width / 2) * .16,
          y: (e.clientY - r.top - r.height / 2) * .22,
          duration: .5, ease: 'power3.out'
        });
      });
      btn.addEventListener('mouseleave', function () {
        gsap.to(btn, { x: 0, y: 0, duration: .6, ease: 'elastic.out(1,.5)' });
      });
    });

    var phone = $('#phone');
    var hero = $('#hero');
    if (phone && hero) {
      hero.addEventListener('mousemove', function (e) {
        var r = hero.getBoundingClientRect();
        gsap.to(phone, {
          rotateY: ((e.clientX - r.left) / r.width - .5) * 14,
          rotateX: ((e.clientY - r.top) / r.height - .5) * -10,
          duration: .8, ease: 'power3.out', transformPerspective: 900
        });
      });
      hero.addEventListener('mouseleave', function () {
        gsap.to(phone, { rotateY: 0, rotateX: 0, duration: 1, ease: 'power3.out' });
      });
    }
  }

  /* ---------------------------------------------------------------
     9b. Mündəricat izləyicisi (hüquqi səhifələr)
  --------------------------------------------------------------- */
  function tocSpy() {
    var links = $$('.toc a');
    if (!links.length || !('IntersectionObserver' in window)) return;
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var link = map[e.target.id];
        if (!link) return;
        links.forEach(function (l) { l.classList.remove('is-active'); });
        link.classList.add('is-active');
      });
    }, { rootMargin: '-12% 0px -75% 0px' });
    Object.keys(map).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) io.observe(el);
    });
  }

  /* ---------------------------------------------------------------
     10. Daxili keçidlər üçün hamar sürüşmə (nav hündürlüyü nəzərə alınır)
  --------------------------------------------------------------- */
  function smoothAnchors() {
    $$('a[href^="#"]').forEach(function (a) {
      var id = a.getAttribute('href');
      if (!id || id === '#') return;
      a.addEventListener('click', function (e) {
        var t = document.querySelector(id);
        if (!t) return;
        e.preventDefault();
        var top = t.getBoundingClientRect().top + window.pageYOffset - 84;
        window.scrollTo({ top: top, behavior: reduced ? 'auto' : 'smooth' });
      });
    });
  }

  /* ---------------------------------------------------------------
     Başlat
  --------------------------------------------------------------- */
  function init() {
    counters();
    marquee();
    tabs();
    accordion();
    tocSpy();
    smoothAnchors();

    if (hasGSAP && !reduced) {
      heroIntro();
      if (window.ScrollTrigger) revealOnScroll();
      microInteractions();
    } else {
      $$('.reveal, [data-split]').forEach(function (el) { el.style.opacity = 1; });
    }

    // Şriftlər yükləndikdən sonra ölçüləri yenilə
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () {
        if (window.ScrollTrigger) ScrollTrigger.refresh();
        var wrap = $('#roleTabs');
        if (wrap) {
          var a = $('.tab.is-active', wrap), pill = $('#tabsPill', wrap);
          if (a && pill) { pill.style.left = a.offsetLeft + 'px'; pill.style.width = a.offsetWidth + 'px'; }
        }
      });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
