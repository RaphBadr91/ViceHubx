/* ==================================================================
   ViceHub X — moteur d'expérience (vanilla + Canvas + Motion)
   Synthwave hero · tilt 3D · parallaxe · curseur néon · reveal fluide
   ================================================================== */
(function () {
  'use strict';

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isTouch = window.matchMedia('(hover: none)').matches;
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  /* ---------------- Écran de chargement ---------------- */
  function hideLoader() {
    const l = $('.vh-loader');
    if (l) setTimeout(() => l.classList.add('hidden'), 350);
  }
  window.addEventListener('load', hideLoader);
  // filet de sécurité
  setTimeout(hideLoader, 2200);

  /* ---------------- Menu mobile ---------------- */
  const toggle = $('.nav-toggle');
  const nav = $('.site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---------------- Zones de carte ---------------- */
  $$('.zone').forEach((zone) => {
    zone.addEventListener('click', () => {
      const isOpen = zone.getAttribute('aria-expanded') === 'true';
      $$('.zone[aria-expanded="true"]').forEach((z) => z !== zone && z.setAttribute('aria-expanded', 'false'));
      zone.setAttribute('aria-expanded', String(!isOpen));
    });
  });

  /* ---------------- Reveal au scroll ---------------- */
  const reveals = $$('.reveal');
  if (reduced) {
    reveals.forEach((el) => el.classList.add('in'));
  } else if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => entries.forEach((e) => {
        if (e.isIntersecting) {
          // léger stagger par rangée
          const sib = $$('.reveal', e.target.parentElement).indexOf?.(e.target) ?? 0;
          e.target.style.transitionDelay = Math.min(sib, 5) * 50 + 'ms';
          e.target.classList.add('in');
          io.unobserve(e.target);
        }
      }),
      { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    );
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add('in'));
  }

  if (reduced) return; // on s'arrête là pour l'accessibilité

  /* ---------------- Curseur néon ---------------- */
  if (!isTouch) {
    const cursor = $('.fx-cursor');
    if (cursor) {
      let cx = window.innerWidth / 2, cy = window.innerHeight / 2, tx = cx, ty = cy;
      window.addEventListener('pointermove', (e) => {
        tx = e.clientX; ty = e.clientY;
        document.body.classList.add('has-cursor');
      }, { passive: true });
      (function follow() {
        cx += (tx - cx) * 0.18; cy += (ty - cy) * 0.18;
        cursor.style.left = cx + 'px'; cursor.style.top = cy + 'px';
        requestAnimationFrame(follow);
      })();
    }
  }

  /* ---------------- Tilt 3D + lueur (cartes & modules) ---------------- */
  if (!isTouch) {
    $$('.card, .os-module').forEach((card) => {
      let raf = null;
      card.addEventListener('pointermove', (e) => {
        const r = card.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width;
        const py = (e.clientY - r.top) / r.height;
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => {
          card.style.setProperty('--ry', (px - 0.5) * 12 + 'deg');
          card.style.setProperty('--rx', (0.5 - py) * 12 + 'deg');
          card.style.setProperty('--mx', px * 100 + '%');
          card.style.setProperty('--my', py * 100 + '%');
        });
      });
      card.addEventListener('pointerleave', () => {
        card.style.setProperty('--rx', '0deg');
        card.style.setProperty('--ry', '0deg');
      });
    });
  }

  /* ---------------- Boutons magnétiques ---------------- */
  if (!isTouch) {
    $$('.btn--primary, .btn--blue').forEach((btn) => {
      btn.addEventListener('pointermove', (e) => {
        const r = btn.getBoundingClientRect();
        const mx = e.clientX - r.left - r.width / 2;
        const my = e.clientY - r.top - r.height / 2;
        btn.style.transform = `translate(${mx * 0.25}px, ${my * 0.35}px)`;
      });
      btn.addEventListener('pointerleave', () => { btn.style.transform = ''; });
    });
  }

  /* ---------------- Parallaxe du hero ---------------- */
  const hero = $('.hero');
  if (hero && !isTouch) {
    const content = $('.hero__content', hero);
    const palms = $$('.palm', hero);
    hero.addEventListener('pointermove', (e) => {
      const r = hero.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - 0.5;
      const y = (e.clientY - r.top) / r.height - 0.5;
      if (content) content.style.transform = `translate3d(${x * -18}px, ${y * -12}px, 0)`;
      palms.forEach((p, i) => {
        const depth = i % 2 ? 30 : -30;
        p.style.transform = `translateX(${x * depth}px) ${p.classList.contains('palm--r') ? 'scaleX(-1)' : ''}`;
      });
    });
    hero.addEventListener('pointerleave', () => {
      if (content) content.style.transform = '';
    });
  }

  /* ---------------- Canvas synthwave ---------------- */
  const canvas = $('#vh-canvas');
  if (canvas && canvas.getContext) startSynthwave(canvas);

  function startSynthwave(canvas) {
    const ctx = canvas.getContext('2d');
    let w = 0, h = 0, dpr = 1, t = 0;
    const stars = [], embers = [];

    const newEmber = () => ({
      x: Math.random() * w, y: h + Math.random() * 60,
      vy: 0.3 + Math.random() * 0.9, r: Math.random() * 2 + 0.6,
      a: Math.random() * 0.5 + 0.25, color: Math.random() < 0.5 ? '255,46,136' : '43,214,255',
    });

    function resize() {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = canvas.clientWidth; h = canvas.clientHeight;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      stars.length = 0;
      for (let i = 0; i < 110; i++) {
        stars.push({ x: Math.random() * w, y: Math.random() * h * 0.55, r: Math.random() * 1.4 + 0.2, t: Math.random() * Math.PI * 2 });
      }
      embers.length = 0;
      for (let i = 0; i < 46; i++) embers.push(newEmber());
    }

    function grid(horizon) {
      const cx = w / 2;
      ctx.save();
      ctx.globalCompositeOperation = 'lighter';
      ctx.lineWidth = 1;
      // lignes fuyantes (verticales) convergeant vers le point de fuite
      const cols = 16;
      for (let i = -cols; i <= cols; i++) {
        const x = cx + i * (w / cols);
        ctx.strokeStyle = 'rgba(255,46,136,.28)';
        ctx.beginPath(); ctx.moveTo(cx, horizon); ctx.lineTo(x, h); ctx.stroke();
      }
      // lignes horizontales défilantes (perspective)
      const speed = (t * 0.35) % 1;
      for (let i = 0; i < 16; i++) {
        const p = (i + speed) / 16;
        const y = horizon + Math.pow(p, 2.3) * (h - horizon);
        ctx.strokeStyle = 'rgba(43,214,255,' + (0.05 + p * 0.4) + ')';
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke();
      }
      ctx.restore();
    }

    function draw() {
      t += 0.016;
      const horizon = h * 0.62;

      // ciel
      let sky = ctx.createLinearGradient(0, 0, 0, horizon);
      sky.addColorStop(0, '#0a0820'); sky.addColorStop(0.55, '#34123f'); sky.addColorStop(1, '#7a1d59');
      ctx.fillStyle = sky; ctx.fillRect(0, 0, w, horizon);
      // sol
      let gnd = ctx.createLinearGradient(0, horizon, 0, h);
      gnd.addColorStop(0, '#1a0b2e'); gnd.addColorStop(1, '#07060f');
      ctx.fillStyle = gnd; ctx.fillRect(0, horizon, w, h - horizon);

      // étoiles
      for (const s of stars) {
        s.t += 0.03;
        ctx.globalAlpha = (0.4 + 0.6 * Math.sin(s.t)) * 0.9;
        ctx.fillStyle = '#fff';
        ctx.fillRect(s.x, s.y, s.r, s.r);
      }
      ctx.globalAlpha = 1;

      // soleil rétro
      const cx = w / 2, R = Math.min(w, h) * 0.17, sy = horizon - R * 0.55;
      ctx.save();
      ctx.beginPath(); ctx.arc(cx, sy, R, 0, Math.PI * 2); ctx.clip();
      let sun = ctx.createLinearGradient(0, sy - R, 0, sy + R);
      sun.addColorStop(0, '#ffe66d'); sun.addColorStop(0.5, '#ff7b3d'); sun.addColorStop(1, '#ff2e88');
      ctx.fillStyle = sun; ctx.fillRect(cx - R, sy - R, R * 2, R * 2);
      // bandes
      ctx.fillStyle = '#34123f';
      for (let i = 0; i < 6; i++) {
        const by = sy + R * 0.18 + i * (R * 0.17);
        ctx.fillRect(cx - R, by, R * 2, 2 + i * 1.1);
      }
      ctx.restore();
      // halo
      ctx.save();
      ctx.globalCompositeOperation = 'lighter';
      let glow = ctx.createRadialGradient(cx, sy, 0, cx, sy, R * 2.8);
      glow.addColorStop(0, 'rgba(255,123,61,.4)'); glow.addColorStop(1, 'transparent');
      ctx.fillStyle = glow; ctx.fillRect(0, 0, w, horizon + 60);
      ctx.restore();

      grid(horizon);

      // braises montantes
      ctx.save();
      ctx.globalCompositeOperation = 'lighter';
      for (const e of embers) {
        e.y -= e.vy; e.x += Math.sin((e.y + t * 30) * 0.02) * 0.3;
        if (e.y < -10) Object.assign(e, newEmber());
        ctx.globalAlpha = e.a;
        ctx.fillStyle = 'rgba(' + e.color + ',1)';
        ctx.beginPath(); ctx.arc(e.x, e.y, e.r, 0, Math.PI * 2); ctx.fill();
      }
      ctx.restore();
      ctx.globalAlpha = 1;

      requestAnimationFrame(draw);
    }

    resize();
    window.addEventListener('resize', resize, { passive: true });
    requestAnimationFrame(draw);
  }

  /* ---------------- Motion (entrées orchestrées) ---------------- */
  import('https://cdn.jsdelivr.net/npm/motion@12/+esm')
    .then(({ animate, stagger }) => {
      if (typeof animate !== 'function') return;
      const ease = [0.2, 0.8, 0.2, 1];
      animate('.site-header', { opacity: [0, 1], y: [-28, 0] }, { duration: 0.6, ease });
      if ($('.hero__badge')) animate('.hero__badge', { opacity: [0, 1], y: [20, 0] }, { duration: 0.6, ease });
      if ($('.hero h1')) animate('.hero h1', { opacity: [0, 1], y: [34, 0] }, { duration: 0.75, delay: 0.1, ease });
      if ($('.hero__sub')) animate('.hero__sub', { opacity: [0, 1], y: [22, 0] }, { duration: 0.75, delay: 0.25, ease });
      if ($('.hero__actions')) animate('.hero__actions', { opacity: [0, 1], y: [22, 0] }, { duration: 0.75, delay: 0.4, ease });
      const mods = $$('.os-module');
      if (mods.length) animate(mods, { opacity: [0, 1], y: [22, 0] }, { duration: 0.5, delay: stagger(0.06), ease });
    })
    .catch(() => { /* hors-ligne : tout reste fonctionnel via CSS */ });
})();
