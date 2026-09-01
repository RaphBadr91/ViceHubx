<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? 'Codes de triche GTA VI — la console secrète' : 'GTA VI cheat codes — secret console') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'La console de triche façon GTA : tape un code secret et active des effets néon sur ViceHub X. Hommage non officiel aux cheat codes cultes de Grand Theft Auto.'
    : 'GTA-style cheat console: type a secret code and trigger neon effects on ViceHub X.';
$SEO_OG_IMAGE = cdn_url('night.png');
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:820px">
    <span class="eyebrow"><?= vhx_icon('dice') ?> ViceHub X</span>
    <h1><?= $fr ? 'Codes de triche' : 'Cheat codes' ?></h1>
    <p class="muted"><?= $fr
        ? 'Hommage aux codes cultes de GTA. Tape un code dans la console et regarde Vice City réagir. 🌴 (Astuce : tu peux aussi taper le code directement au clavier n’importe où sur le site.)'
        : 'A tribute to GTA’s cult cheat codes. Type a code in the console and watch Vice City react.' ?></p>

    <div class="cheat-console glass">
        <div class="cheat-console__bar"><span></span><span></span><span></span> CHEAT&nbsp;CONSOLE</div>
        <div class="cheat-log" id="cheatLog"><p class="muted"><?= $fr ? '> En attente d’un code…' : '> Waiting for a code…' ?></p></div>
        <form class="cheat-form" id="cheatForm">
            <input type="text" id="cheatInput" autocomplete="off" spellcheck="false" placeholder="<?= $fr ? 'Tape un code (ex. VICECITY)…' : 'Type a code (e.g. VICECITY)…' ?>" aria-label="Code">
            <button class="btn btn--primary" type="submit"><?= $fr ? 'Activer' : 'Run' ?></button>
        </form>
    </div>

    <h2 style="margin-top:2.4rem"><?= $fr ? 'Codes connus' : 'Known codes' ?></h2>
    <p class="muted" style="font-size:.85rem"><?= $fr ? 'Clique sur un code pour l’activer. D’autres sont cachés… à toi de les trouver. 😉' : 'Click a code to run it. More are hidden…' ?></p>
    <div class="cheat-grid" id="cheatGrid"></div>
</section>

<script>
(function () {
  var FR = <?= $fr ? 'true' : 'false' ?>;
  function toast(msg, sub) {
    var w = document.createElement('div');
    w.className = 'cheat-pop';
    w.innerHTML = '<div class="cheat-stars">★★★★★</div><div class="cheat-msg">' + msg + '</div>' + (sub ? '<div class="muted" style="margin-top:.4rem">' + sub + '</div>' : '');
    document.body.appendChild(w);
    document.body.classList.add('cheat-flash');
    setTimeout(function () { document.body.classList.remove('cheat-flash'); }, 700);
    setTimeout(function () { w.classList.add('out'); }, 2400);
    setTimeout(function () { w.remove(); }, 3000);
  }
  function rain(emoji, n) {
    for (var i = 0; i < n; i++) (function (k) {
      var s = document.createElement('span');
      s.className = 'fx-rain'; s.textContent = emoji;
      s.style.left = Math.round(Math.random() * 100) + 'vw';
      s.style.animationDelay = (Math.random() * 0.8).toFixed(2) + 's';
      s.style.fontSize = (1 + Math.random() * 1.8).toFixed(2) + 'rem';
      document.body.appendChild(s);
      setTimeout(function () { s.remove(); }, 4200);
    })(i);
  }
  function radio() { if (window.ViceFM) window.ViceFM.play(); }

  var CODES = [
    { code: 'VICECITY', label: FR ? 'Bienvenue à Vice City' : 'Welcome to Vice City', fx: function () { rain('🌴', 28); radio(); toast('🌴 ' + (FR ? 'BIENVENUE À VICE CITY' : 'WELCOME TO VICE CITY')); } },
    { code: 'LEONIDA',  label: FR ? 'Accès Leonida' : 'Leonida access', fx: function () { toast('⭐ ' + (FR ? 'ACCÈS LEONIDA DÉBLOQUÉ' : 'LEONIDA UNLOCKED')); } },
    { code: 'WANTED',   label: FR ? 'Niveau de recherche max' : 'Max wanted level', fx: function () { toast('🚔 ' + (FR ? 'AVIS DE RECHERCHE : 5 ÉTOILES' : 'WANTED: 5 STARS')); } },
    { code: 'GETRICH',  label: FR ? 'Plein aux as' : 'Loaded', fx: function () { rain('💵', 30); toast('💰 +1 000 000 $', FR ? '(pour de faux, évidemment 😄)' : '(fake money, of course 😄)'); } },
    { code: 'PALMTREE', label: FR ? 'Pluie de palmiers' : 'Palm rain', fx: function () { rain('🌴', 36); toast('🌴 PALM CITY'); } },
    { code: 'NEONMODE', label: FR ? 'Mode néon à fond' : 'Neon overdrive', fx: function () { document.body.classList.toggle('neon-boost'); toast('💜 NEON MODE'); } },
    { code: 'SUNSET',   label: FR ? 'Coucher de soleil' : 'Sunset mode', fx: function () { document.body.classList.toggle('sunset-mode'); toast('🌅 SUNSET'); } },
    { code: 'VICEFM',   label: FR ? 'Allume la radio' : 'Turn on the radio', fx: function () { radio(); toast('📻 VICE FM ON'); } }
  ];
  var map = {}; CODES.forEach(function (c) { map[c.code] = c; });

  var log = document.getElementById('cheatLog');
  function logLine(txt, ok) {
    var p = document.createElement('p'); p.innerHTML = (ok ? '<b style="color:var(--blue)">✓</b> ' : '<b style="color:#ff5d5d">✗</b> ') + txt;
    log.appendChild(p); log.scrollTop = log.scrollHeight;
  }
  function run(raw) {
    var code = (raw || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (!code) return;
    if (map[code]) { logLine(code + ' — ' + map[code].label, true); map[code].fx(); }
    else { logLine(code + (FR ? ' — code inconnu' : ' — unknown code'), false); }
  }

  document.getElementById('cheatForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var inp = document.getElementById('cheatInput');
    run(inp.value); inp.value = '';
  });

  var grid = document.getElementById('cheatGrid');
  CODES.forEach(function (c) {
    var b = document.createElement('button');
    b.className = 'cheat-code'; b.type = 'button';
    b.innerHTML = '<code>' + c.code + '</code><span>' + c.label + '</span>';
    b.addEventListener('click', function () { run(c.code); });
    grid.appendChild(b);
  });
})();
</script>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
