/* ==================================================================
   ViceHub X — interactions front (vanilla JS, zéro dépendance requise)
   ================================================================== */
(function () {
  'use strict';

  /* ---- Menu mobile ---- */
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      const open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---- Zones de carte (Map Lab) ---- */
  document.querySelectorAll('.zone').forEach(function (zone) {
    zone.addEventListener('click', function () {
      const isOpen = zone.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll('.zone[aria-expanded="true"]').forEach(function (z) {
        if (z !== zone) z.setAttribute('aria-expanded', 'false');
      });
      zone.setAttribute('aria-expanded', String(!isOpen));
    });
  });

  /* ---- Reveal au scroll (IntersectionObserver) ---- */
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    const io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12 }
    );
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  /* ----------------------------------------------------------------
     Amélioration progressive optionnelle avec Motion (motion.dev).
     Chargée uniquement si VICEHUB_USE_MOTION est activé (data-attr).
     Le site reste 100 % fonctionnel sans CDN ni Motion.
  ---------------------------------------------------------------- */
  const wantsMotion = document.documentElement.getAttribute('data-motion') === 'on';
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (wantsMotion && !reduced) {
    import('https://cdn.jsdelivr.net/npm/motion@12/+esm')
      .then(function (mod) {
        const animate = mod.animate;
        if (typeof animate !== 'function') return;
        document.querySelectorAll('.os-module').forEach(function (el, i) {
          animate(
            el,
            { opacity: [0, 1], transform: ['translateY(18px)', 'translateY(0)'] },
            { duration: 0.5, delay: i * 0.05, easing: 'ease-out' }
          );
        });
      })
      .catch(function () { /* hors-ligne : on garde le fallback CSS */ });
  }
})();
