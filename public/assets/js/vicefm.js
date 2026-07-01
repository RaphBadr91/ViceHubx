/* ==========================================================================
   ViceHub X — Vice FM
   • WebRadio auto-hébergée : joue les .mp3 déposés dans /public/assets/radio/
     (playlist via /radio-playlist.php), en continu comme une vraie station.
   • Repli : si aucun fichier audio, radio synthwave GÉNÉRÉE (Web Audio, sans fichier)
     → jamais de silence.
   • Easter egg « codes de triche » façon GTA.
   ========================================================================== */
(function () {
  'use strict';
  var midi = function (m) { return 440 * Math.pow(2, (m - 69) / 12); };

  /* ============ 1) Radio synthwave générée (repli sans fichier) ============ */
  var STATIONS = [
    { name: 'Vice FM 99.7', tag: 'Synthwave', bpm: 88, chords: [[57,60,64],[53,57,60],[60,64,67],[55,59,62]] },
    { name: 'Wave 102',     tag: 'Chillwave', bpm: 80, chords: [[50,53,57],[46,50,53],[53,57,60],[48,52,55]] },
    { name: 'Sunset 95',    tag: 'Retro',     bpm: 96, chords: [[48,51,55],[44,48,51],[51,55,58],[46,50,53]] }
  ];
  var ac, master, delay, station = 0, synthOn = false, timer = null;
  var nextTime = 0, step = 0, chordIdx = 0;

  function build() {
    var C = window.AudioContext || window.webkitAudioContext;
    if (!C) return false;
    ac = new C();
    master = ac.createGain(); master.gain.value = 0; master.connect(ac.destination);
    delay = ac.createDelay(); delay.delayTime.value = 0.34;
    var fb = ac.createGain(); fb.gain.value = 0.3;
    var wet = ac.createGain(); wet.gain.value = 0.22;
    delay.connect(fb); fb.connect(delay); delay.connect(wet); wet.connect(master);
    return true;
  }
  function tone(freq, t, dur, type, gain, target) {
    var o = ac.createOscillator(), g = ac.createGain();
    o.type = type; o.frequency.value = freq;
    g.gain.setValueAtTime(0.0001, t);
    g.gain.linearRampToValueAtTime(gain, t + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    o.connect(g); g.connect(target || master);
    o.start(t); o.stop(t + dur + 0.05);
  }
  function pad(chord, t, dur) {
    var f = ac.createBiquadFilter(); f.type = 'lowpass'; f.frequency.value = 1300; f.Q.value = 0.7;
    f.connect(master); f.connect(delay);
    chord.forEach(function (m) { tone(midi(m), t, dur, 'sawtooth', 0.045, f); tone(midi(m - 12), t, dur, 'triangle', 0.03, f); });
  }
  function kick(t) {
    var o = ac.createOscillator(), g = ac.createGain();
    o.type = 'sine'; o.frequency.setValueAtTime(140, t); o.frequency.exponentialRampToValueAtTime(45, t + 0.12);
    g.gain.setValueAtTime(0.45, t); g.gain.exponentialRampToValueAtTime(0.001, t + 0.16);
    o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.2);
  }
  function hat(t) {
    var len = Math.floor(ac.sampleRate * 0.05), b = ac.createBuffer(1, len, ac.sampleRate), d = b.getChannelData(0);
    for (var i = 0; i < len; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, 2);
    var s = ac.createBufferSource(); s.buffer = b;
    var f = ac.createBiquadFilter(); f.type = 'highpass'; f.frequency.value = 7000;
    var g = ac.createGain(); g.gain.value = 0.06;
    s.connect(f); f.connect(g); g.connect(master); s.start(t);
  }
  function scheduler() {
    var st = STATIONS[station], spb = 60 / st.bpm, stepDur = spb / 2;
    while (nextTime < ac.currentTime + 0.12) {
      var t = nextTime, chord = st.chords[chordIdx];
      if (step % 8 === 0) pad(chord, t, spb * 4 * 0.97);
      if (step % 2 === 0) tone(midi(chord[0] - 12), t, stepDur * 0.9, 'triangle', 0.11);
      tone(midi(chord[step % chord.length] + 12), t, stepDur * 0.8, 'square', 0.045, delay);
      if (step % 4 === 0) kick(t);
      if (step % 2 === 1) hat(t);
      step++;
      if (step % 8 === 0) chordIdx = (chordIdx + 1) % st.chords.length;
      nextTime += stepDur;
    }
    timer = setTimeout(scheduler, 25);
  }
  function synthStart() {
    if (!ac && !build()) return;
    if (ac.state === 'suspended') ac.resume();
    synthOn = true; nextTime = ac.currentTime + 0.1; step = 0; chordIdx = 0;
    master.gain.cancelScheduledValues(ac.currentTime);
    master.gain.setValueAtTime(master.gain.value, ac.currentTime);
    master.gain.linearRampToValueAtTime(0.42, ac.currentTime + 1.2);
    scheduler();
  }
  function synthStop() {
    synthOn = false;
    if (timer) { clearTimeout(timer); timer = null; }
    if (ac) { master.gain.cancelScheduledValues(ac.currentTime); master.gain.setValueAtTime(master.gain.value, ac.currentTime); master.gain.linearRampToValueAtTime(0, ac.currentTime + 0.4); }
  }

  /* ============ 2) WebRadio auto-hébergée (fichiers .mp3 réels) ============ */
  var RADIO = {
    audio: null, list: [], i: 0, ready: false, vol: 0.6,
    init: function () {
      this.audio = new Audio();
      this.audio.preload = 'none';
      this.audio.volume = this.vol;
      var self = this;
      this.audio.addEventListener('ended', function () { self.next(); });
      this.audio.addEventListener('error', function () { if (self.list.length > 1) self.next(); });
    },
    load: function (cb) {
      var self = this;
      fetch('/radio-playlist.php', { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var t = (d && d.tracks) || [];
          // Mélange (Fisher-Yates) pour un ordre « station » différent à chaque visite.
          for (var k = t.length - 1; k > 0; k--) { var j = Math.floor(Math.random() * (k + 1)); var tmp = t[k]; t[k] = t[j]; t[j] = tmp; }
          self.list = t; self.ready = t.length > 0; cb(self.ready);
        })
        .catch(function () { self.ready = false; cb(false); });
    },
    cur: function () { return this.list[this.i] || null; },
    play: function () { if (!this.list.length) return; var tr = this.list[this.i]; if (this.audio.src.indexOf(tr.src) === -1) { this.audio.src = tr.src; } var p = this.audio.play(); if (p && p.catch) p.catch(function () {}); },
    pause: function () { this.audio.pause(); },
    next: function () { if (!this.list.length) return; this.i = (this.i + 1) % this.list.length; this.audio.src = this.list[this.i].src; this.play(); if (this.onchange) this.onchange(); },
    setVol: function (v) { this.vol = v; this.audio.volume = v; }
  };

  /* ============ 3) Widget + contrôleur unifié ============ */
  var mode = 'synth', playing = false;

  function render() {
    var el = document.createElement('div');
    el.className = 'vfm';
    el.innerHTML =
      '<button class="vfm__toggle" aria-label="Vice FM" title="Vice FM">' +
        '<span class="vfm__eq"><i></i><i></i><i></i><i></i></span><span class="vfm__ico">📻</span>' +
      '</button>' +
      '<div class="vfm__panel" hidden>' +
        '<div class="vfm__head"><b class="vfm__name">Vice City Radio</b><span class="vfm__tag">📻 En direct</span></div>' +
        '<div class="vfm__ctrls">' +
          '<button class="vfm__play" aria-label="Lecture">▶</button>' +
          '<button class="vfm__next" aria-label="Piste suivante">⏭</button>' +
          '<input class="vfm__vol" type="range" min="0" max="100" value="60" aria-label="Volume">' +
        '</div>' +
      '</div>';
    document.body.appendChild(el);

    var toggle = el.querySelector('.vfm__toggle');
    var panel = el.querySelector('.vfm__panel');
    var playBtn = el.querySelector('.vfm__play');
    var nextBtn = el.querySelector('.vfm__next');
    var vol = el.querySelector('.vfm__vol');
    var nameEl = el.querySelector('.vfm__name');
    var tagEl = el.querySelector('.vfm__tag');

    function refresh() {
      playBtn.textContent = playing ? '⏸' : '▶';
      el.classList.toggle('vfm--on', playing);
      if (mode === 'radio') {
        var tr = RADIO.cur();
        nameEl.textContent = tr ? tr.title : 'Vice City Radio';
        tagEl.textContent = '📻 En direct';
      } else {
        nameEl.textContent = STATIONS[station].name;
        tagEl.textContent = STATIONS[station].tag;
      }
    }
    RADIO.onchange = refresh;

    function play() {
      if (mode === 'radio') { RADIO.play(); } else { synthStart(); }
      playing = true; refresh();
    }
    function stop() {
      if (mode === 'radio') { RADIO.pause(); } else { synthStop(); }
      playing = false; refresh();
    }
    window.__vfmPlay = play; // utilisé par les easter eggs

    toggle.addEventListener('click', function () { panel.hidden = !panel.hidden; });
    playBtn.addEventListener('click', function () { playing ? stop() : play(); });
    nextBtn.addEventListener('click', function () {
      if (mode === 'radio') { RADIO.next(); playing = true; }
      else { station = (station + 1) % STATIONS.length; if (playing) { step = 0; chordIdx = 0; } }
      refresh();
    });
    vol.addEventListener('input', function () {
      var v = vol.value / 100;
      if (mode === 'radio') { RADIO.setVol(v); }
      else if (ac && synthOn) { master.gain.cancelScheduledValues(ac.currentTime); master.gain.setValueAtTime(v * 0.6, ac.currentTime); }
    });

    // Charge la playlist réelle : si des fichiers existent → mode radio, sinon synth.
    RADIO.init();
    RADIO.load(function (hasTracks) { mode = hasTracks ? 'radio' : 'synth'; refresh(); });
    refresh();

    window.ViceFM = {
      play: function () { if (!playing) play(); panel.hidden = false; },
      stop: function () { if (playing) stop(); }
    };
    Array.prototype.forEach.call(document.querySelectorAll('[data-vfm-play]'), function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); window.ViceFM.play(); });
    });
  }

  /* ---------------- Easter egg : codes de triche GTA ---------------- */
  function cheats() {
    var buf = '';
    var KONAMI = 'ArrowUpArrowUpArrowDownArrowDownArrowLeftArrowRightArrowLeftArrowRightba';
    var kbuf = '';
    function fire(label) {
      var w = document.createElement('div');
      w.className = 'cheat-pop';
      w.innerHTML = '<div class="cheat-stars">★★★★★</div><div class="cheat-msg">' + label + '</div>';
      document.body.appendChild(w);
      document.body.classList.add('cheat-flash');
      setTimeout(function () { document.body.classList.remove('cheat-flash'); }, 700);
      setTimeout(function () { w.classList.add('out'); }, 2600);
      setTimeout(function () { w.remove(); }, 3200);
      if (window.__vfmPlay) { try { window.__vfmPlay(); } catch (e) {} }
      if (window.ViceHUD) { window.ViceHUD.setWanted(5); }
    }
    document.addEventListener('keydown', function (e) {
      if (/^[a-zA-Z]$/.test(e.key)) {
        buf = (buf + e.key.toLowerCase()).slice(-12);
        if (buf.indexOf('vicecity') !== -1) { buf = ''; fire('TRICHE : BIENVENUE À VICE CITY 🌴'); }
        else if (buf.indexOf('leonida') !== -1) { buf = ''; fire('TRICHE : ACCÈS LEONIDA DÉBLOQUÉ ⭐'); }
        else if (buf.indexOf('sixstars') !== -1) { buf = ''; fire('TRICHE : AVIS DE RECHERCHE ★★★★★★'); if (window.ViceHUD) window.ViceHUD.setWanted(6); }
        else if (buf.indexOf('lowprofile') !== -1) { buf = ''; if (window.ViceHUD) { window.ViceHUD.setWanted(0); window.ViceHUD.mission('PROFIL BAS', 'Recherche effacée'); } }
        else if (buf.indexOf('respect') !== -1) { buf = ''; if (window.ViceHUD) window.ViceHUD.mission('RESPECT +', 'Réputation de Vice City'); }
      }
      kbuf = (kbuf + e.key).slice(-KONAMI.length);
      if (kbuf === KONAMI) { kbuf = ''; fire('CODE KONAMI : MODE VICE ACTIVÉ 🎮'); }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    try { render(); cheats(); } catch (err) { /* silencieux */ }
  });
})();

/* Barre de progression de défilement (perf : passive + rAF) */
(function () {
  var bar = document.createElement('div');
  bar.className = 'scroll-progress';
  document.addEventListener('DOMContentLoaded', function () { document.body.appendChild(bar); });
  var ticking = false;
  function update() {
    var h = document.documentElement;
    var max = (h.scrollHeight - h.clientHeight) || 1;
    bar.style.width = Math.min(100, (h.scrollTop || document.body.scrollTop) / max * 100) + '%';
    ticking = false;
  }
  window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
})();
