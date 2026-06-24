/* ==========================================================================
   ViceHub X — Vice FM (radio synthwave générée en direct via Web Audio)
   + Easter egg « code de triche » façon GTA.  100% client, sans fichier audio.
   ========================================================================== */
(function () {
  'use strict';
  var midi = function (m) { return 440 * Math.pow(2, (m - 69) / 12); };

  // Stations : progressions d'accords (notes MIDI), tempo, ambiance
  var STATIONS = [
    { name: 'Vice FM 99.7', tag: 'Synthwave', bpm: 88, chords: [[57,60,64],[53,57,60],[60,64,67],[55,59,62]] }, // Am F C G
    { name: 'Wave 102',     tag: 'Chillwave', bpm: 80, chords: [[50,53,57],[46,50,53],[53,57,60],[48,52,55]] }, // Dm Bb F C
    { name: 'Sunset 95',    tag: 'Retro',     bpm: 96, chords: [[48,51,55],[44,48,51],[51,55,58],[46,50,53]] }  // Cm Ab Eb Bb
  ];

  var ac, master, delay, station = 0, playing = false, timer = null;
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
    chord.forEach(function (m) {
      tone(midi(m), t, dur, 'sawtooth', 0.045, f);
      tone(midi(m - 12), t, dur, 'triangle', 0.03, f);
    });
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

  function start() {
    if (!ac && !build()) return;
    if (ac.state === 'suspended') ac.resume();
    playing = true;
    nextTime = ac.currentTime + 0.1; step = 0; chordIdx = 0;
    master.gain.cancelScheduledValues(ac.currentTime);
    master.gain.setValueAtTime(master.gain.value, ac.currentTime);
    master.gain.linearRampToValueAtTime(0.42, ac.currentTime + 1.2);
    scheduler();
  }
  function stop() {
    playing = false;
    if (timer) { clearTimeout(timer); timer = null; }
    if (ac) { master.gain.cancelScheduledValues(ac.currentTime); master.gain.setValueAtTime(master.gain.value, ac.currentTime); master.gain.linearRampToValueAtTime(0, ac.currentTime + 0.4); }
  }

  /* ---------------- Widget UI ---------------- */
  function render() {
    var el = document.createElement('div');
    el.className = 'vfm';
    el.innerHTML =
      '<button class="vfm__toggle" aria-label="Vice FM" title="Vice FM">' +
        '<span class="vfm__eq"><i></i><i></i><i></i><i></i></span>' +
        '<span class="vfm__ico">📻</span>' +
      '</button>' +
      '<div class="vfm__panel" hidden>' +
        '<div class="vfm__head"><b class="vfm__name">Vice FM 99.7</b><span class="vfm__tag">Synthwave</span></div>' +
        '<div class="vfm__ctrls">' +
          '<button class="vfm__play" aria-label="Lecture">▶</button>' +
          '<button class="vfm__next" aria-label="Station suivante">⏭</button>' +
          '<input class="vfm__vol" type="range" min="0" max="100" value="42" aria-label="Volume">' +
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
      nameEl.textContent = STATIONS[station].name;
      tagEl.textContent = STATIONS[station].tag;
      playBtn.textContent = playing ? '⏸' : '▶';
      el.classList.toggle('vfm--on', playing);
    }
    toggle.addEventListener('click', function () { panel.hidden = !panel.hidden; });
    playBtn.addEventListener('click', function () { playing ? stop() : start(); refresh(); });
    nextBtn.addEventListener('click', function () {
      station = (station + 1) % STATIONS.length;
      if (playing) { step = 0; chordIdx = 0; }
      refresh();
    });
    vol.addEventListener('input', function () {
      if (ac && playing) { master.gain.cancelScheduledValues(ac.currentTime); master.gain.setValueAtTime(vol.value / 100 * 0.6, ac.currentTime); }
    });
    refresh();
  }

  /* ---------------- Easter egg : code de triche GTA ---------------- */
  function cheats() {
    var buf = '';
    var KONAMI = 'ArrowUpArrowUpArrowDownArrowDownArrowLeftArrowRightArrowLeftArrowRightba';
    var kbuf = '';
    function fire(label) {
      // étoiles "wanted" + toast néon
      var w = document.createElement('div');
      w.className = 'cheat-pop';
      w.innerHTML = '<div class="cheat-stars">★★★★★</div><div class="cheat-msg">' + label + '</div>';
      document.body.appendChild(w);
      document.body.classList.add('cheat-flash');
      setTimeout(function () { document.body.classList.remove('cheat-flash'); }, 700);
      setTimeout(function () { w.classList.add('out'); }, 2600);
      setTimeout(function () { w.remove(); }, 3200);
      if (!playing) { start(); var p = document.querySelector('.vfm__play'); if (p) p.textContent = '⏸'; var v = document.querySelector('.vfm'); if (v) v.classList.add('vfm--on'); }
    }
    document.addEventListener('keydown', function (e) {
      if (/^[a-zA-Z]$/.test(e.key)) {
        buf = (buf + e.key.toLowerCase()).slice(-12);
        if (buf.indexOf('vicecity') !== -1) { buf = ''; fire('TRICHE : BIENVENUE À VICE CITY 🌴'); }
        else if (buf.indexOf('leonida') !== -1) { buf = ''; fire('TRICHE : ACCÈS LEONIDA DÉBLOQUÉ ⭐'); }
      }
      kbuf = (kbuf + e.key).slice(-KONAMI.length);
      if (kbuf === KONAMI) { kbuf = ''; fire('CODE KONAMI : MODE VICE ACTIVÉ 🎮'); }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    try { render(); cheats(); } catch (err) { /* silencieux */ }
  });
})();
