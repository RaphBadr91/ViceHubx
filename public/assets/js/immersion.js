/* ==========================================================================
   ViceHub X — Immersion « Vice City OS »
   HUD radar (minimap GTA) · horloge Leonida · météo · niveau de recherche
   + bannières MISSION PASSED / WASTED / BUSTED (API window.ViceHUD).
   100% vanilla, sans dépendance, respectueux de prefers-reduced-motion.
   ========================================================================== */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var lang = (document.documentElement.lang || 'fr').slice(0, 2);
  var FR = lang === 'fr';
  var wanted = 0;        // niveau de recherche (0..6)
  var starsEl = null, clockEl = null, weatherEl = null;

  function p2(n) { return (n < 10 ? '0' : '') + n; }

  /* ---------------- Météo « in-universe » selon l'heure ---------------- */
  function weatherFor(h) {
    if (h >= 6 && h < 11) return '🌅';
    if (h >= 11 && h < 17) return '☀️';
    if (h >= 17 && h < 20) return '🌆';
    if (h >= 20 || h < 1) return '🌃';
    return '🌙';
  }

  /* ---------------- Construction du HUD ---------------- */
  function buildHud() {
    var hud = document.createElement('aside');
    hud.className = 'vhud';
    hud.setAttribute('aria-label', FR ? 'HUD Vice City' : 'Vice City HUD');
    hud.innerHTML =
      '<div class="vhud__stars" data-hud-stars aria-label="' + (FR ? 'Niveau de recherche' : 'Wanted level') + '">' +
        '<i>★</i><i>★</i><i>★</i><i>★</i><i>★</i><i>★</i>' +
      '</div>' +
      '<div class="vhud__radar">' +
        '<span class="vhud__rings" aria-hidden="true"></span>' +
        '<span class="vhud__road vhud__road--a" aria-hidden="true"></span>' +
        '<span class="vhud__road vhud__road--b" aria-hidden="true"></span>' +
        '<span class="vhud__road vhud__road--c" aria-hidden="true"></span>' +
        '<span class="vhud__sweep" aria-hidden="true"></span>' +
        '<span class="vhud__blip vhud__blip--1" aria-hidden="true"></span>' +
        '<span class="vhud__blip vhud__blip--2" aria-hidden="true"></span>' +
        '<span class="vhud__player" aria-hidden="true"></span>' +
        '<span class="vhud__n" aria-hidden="true">N</span>' +
      '</div>' +
      '<div class="vhud__bar">' +
        '<span class="vhud__where">📍 LEONIDA</span>' +
        '<span class="vhud__time"><b data-hud-clock>--:--:--</b> <i data-hud-weather>☀️</i></span>' +
      '</div>';
    document.body.appendChild(hud);

    starsEl = hud.querySelector('[data-hud-stars]');
    clockEl = hud.querySelector('[data-hud-clock]');
    weatherEl = hud.querySelector('[data-hud-weather]');

    // Un clic sur les étoiles : on « sème la police » (petit easter egg)
    starsEl.addEventListener('click', function () {
      setWanted(wanted >= 6 ? 0 : wanted + 1);
      if (wanted === 0 && window.ViceHUD) { window.ViceHUD.mission(FR ? 'PROFIL BAS' : 'LOW PROFILE', FR ? 'Recherche perdue' : 'Lost the cops'); }
    });

    renderStars();
    tickClock();
    setInterval(tickClock, 1000);

    // Économie de batterie : on met en pause hors écran
    document.addEventListener('visibilitychange', function () {
      hud.classList.toggle('vhud--paused', document.hidden);
    });
  }

  function tickClock() {
    var d = new Date();
    if (clockEl) clockEl.textContent = p2(d.getHours()) + ':' + p2(d.getMinutes()) + ':' + p2(d.getSeconds());
    if (weatherEl) weatherEl.textContent = weatherFor(d.getHours());
  }

  function renderStars() {
    if (!starsEl) return;
    var st = starsEl.querySelectorAll('i');
    for (var i = 0; i < st.length; i++) st[i].classList.toggle('on', i < wanted);
    starsEl.classList.toggle('vhud__stars--active', wanted > 0);
  }

  function setWanted(n) {
    wanted = Math.max(0, Math.min(6, n | 0));
    renderStars();
    document.body.classList.toggle('is-wanted', wanted >= 4);
  }

  /* ---------------- Bannières MISSION PASSED / WASTED / BUSTED ---------------- */
  var banners = 0;
  function banner(kind, title, sub) {
    if (banners > 2) return;            // garde-fou anti-spam
    banners++;
    var b = document.createElement('div');
    b.className = 'vh-banner vh-banner--' + kind;
    b.setAttribute('role', 'status');
    b.innerHTML =
      '<span class="vh-banner__bg" aria-hidden="true"></span>' +
      '<span class="vh-banner__title">' + title + '</span>' +
      (sub ? '<span class="vh-banner__sub">' + sub + '</span>' : '');
    document.body.appendChild(b);
    requestAnimationFrame(function () { b.classList.add('in'); });
    setTimeout(function () { b.classList.remove('in'); b.classList.add('out'); }, 2600);
    setTimeout(function () { b.remove(); banners--; }, 3300);
  }

  /* ---------------- API publique ---------------- */
  window.ViceHUD = {
    mission: function (title, sub) { banner('mission', title || (FR ? 'MISSION ACCOMPLIE' : 'MISSION PASSED'), sub || (FR ? 'Respect +100' : 'Respect +100')); },
    wasted: function (sub) { banner('wasted', 'WASTED', sub || ''); },
    busted: function (sub) { banner('busted', 'BUSTED', sub || ''); },
    setWanted: setWanted,
    wanted: function () { return wanted; }
  };

  /* ---------------- Déclencheurs déclaratifs [data-mission] ---------------- */
  function fireDeclarative() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-mission]'), function (el) {
      var title = el.getAttribute('data-mission') || '';
      var sub = el.getAttribute('data-mission-sub') || '';
      if (title) window.ViceHUD.mission(title, sub);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    try {
      buildHud();
      fireDeclarative();
    } catch (e) { /* silencieux : le site reste 100% fonctionnel */ }
  });
})();
