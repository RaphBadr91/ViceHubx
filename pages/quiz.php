<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$SEO_TITLE = ($fr ? 'Quiz : quel personnage de GTA VI es-tu ?' : 'Quiz: which GTA VI character are you?') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Lucia, Jason, le Maire ou DJ Solaris ? Fais le quiz ViceHub X et découvre quel personnage de Vice City te ressemble.'
    : 'Lucia, Jason, the Mayor or DJ Solaris? Take the ViceHub X quiz and find your Vice City character.';
$SEO_OG_IMAGE = cdn_url('nightlife.png');
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:760px">
    <span class="eyebrow"><?= vhx_icon('controller') ?> ViceHub X</span>
    <h1><?= $fr ? 'Quel personnage de Vice City es-tu ?' : 'Which Vice City character are you?' ?></h1>
    <p class="muted"><?= $fr ? '5 questions, 1 minute. Branche Vice FM et c’est parti. 🌴' : '5 questions, 1 minute.' ?></p>

    <div class="quiz glass" id="quiz" data-lang="<?= e(lang()) ?>">
        <div class="quiz__progress"><i></i></div>
        <div class="quiz__stage"></div>
    </div>
</section>

<script>
(function () {
  var FR = <?= $fr ? 'true' : 'false' ?>;
  var Q = [
    { q: FR ? 'Ton terrain de jeu idéal ?' : 'Your ideal playground?', a: [
      [FR?'La plage et les néons':'Beach & neon','L'], [FR?'L’autoroute, plein gaz':'The highway, full throttle','J'],
      [FR?'Les couloirs du pouvoir':'Halls of power','M'], [FR?'La cabine radio':'The radio booth','S'] ] },
    { q: FR ? 'Ton arme de prédilection ?' : 'Your weapon of choice?', a: [
      [FR?'Le sang-froid':'Cold blood','L'], [FR?'L’instinct':'Instinct','J'],
      [FR?'L’influence':'Influence','M'], [FR?'Le micro':'The mic','S'] ] },
    { q: FR ? 'Un casse tourne mal. Tu...' : 'A heist goes wrong. You...', a: [
      [FR?'Gardes la tête froide':'Stay calm','L'], [FR?'Fonces dans le tas':'Charge in','J'],
      [FR?'Négocies une sortie':'Negotiate','M'], [FR?'Mets l’ambiance':'Set the vibe','S'] ] },
    { q: FR ? 'Ta voiture ?' : 'Your ride?', a: [
      [FR?'Une supercar néon':'A neon supercar','L'], [FR?'Une muscle car':'A muscle car','J'],
      [FR?'Une limousine':'A limo','M'], [FR?'Un van de tournée':'A tour van','S'] ] },
    { q: FR ? 'Ton rêve à Vice City ?' : 'Your Vice City dream?', a: [
      [FR?'Un nouveau départ':'A fresh start','L'], [FR?'La gloire':'Glory','J'],
      [FR?'Le contrôle':'Control','M'], [FR?'La légende':'Legend','S'] ] }
  ];
  var R = {
    L: { name:'Lucia', emoji:'🌹', d: FR?'Déterminée et stratège. Tu avances malgré les cicatrices — une vraie héroïne de Vice City.':'Determined strategist.' },
    J: { name:'Jason', emoji:'🔥', d: FR?'Impulsif et loyal. Tu fonces au cœur de l’action, quitte à improviser.':'Impulsive and loyal.' },
    M: { name:'Le Maire', emoji:'🎩', d: FR?'Manipulateur né. Tu tires les ficelles dans l’ombre de la ville.':'A born manipulator.' },
    S: { name:'DJ Solaris', emoji:'🎧', d: FR?'Charismatique et cool. Tu es l’âme sonore de Vice City.':'Charismatic and cool.' }
  };
  var el = document.getElementById('quiz'), stage = el.querySelector('.quiz__stage'), bar = el.querySelector('.quiz__progress i');
  var i = 0, score = { L:0, J:0, M:0, S:0 };

  function render() {
    bar.style.width = (i / Q.length * 100) + '%';
    var item = Q[i];
    var h = '<h2 class="quiz__q">' + item.q + '</h2><div class="quiz__opts">';
    item.a.forEach(function (o, n) { h += '<button class="quiz__opt" data-k="' + o[1] + '">' + o[0] + '</button>'; });
    h += '</div><p class="muted quiz__step">' + (FR ? 'Question ' : 'Question ') + (i + 1) + ' / ' + Q.length + '</p>';
    stage.innerHTML = h;
    Array.prototype.forEach.call(stage.querySelectorAll('.quiz__opt'), function (b) {
      b.addEventListener('click', function () { score[b.getAttribute('data-k')]++; i++; (i < Q.length) ? render() : result(); });
    });
  }
  function result() {
    bar.style.width = '100%';
    var best = 'L'; for (var k in score) if (score[k] > score[best]) best = k;
    var r = R[best];
    var url = location.href.split('#')[0];
    var txt = (FR ? 'Je suis ' : 'I am ') + r.name + ' ' + r.emoji + ' sur ViceHub X !';
    stage.innerHTML =
      '<div class="quiz__result"><div class="quiz__emoji">' + r.emoji + '</div>' +
      '<span class="muted">' + (FR ? 'Tu es' : 'You are') + '</span>' +
      '<h2 class="grad-text">' + r.name + '</h2><p>' + r.d + '</p>' +
      '<div class="quiz__actions">' +
        '<a class="btn btn--primary" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=' + encodeURIComponent(txt + ' ' + url) + '">' + (FR ? 'Partager' : 'Share') + ' 𝕏</a>' +
        '<button class="btn btn--ghost" id="quiz-retry">' + (FR ? 'Recommencer' : 'Retry') + '</button>' +
      '</div></div>';
    document.getElementById('quiz-retry').addEventListener('click', function () { i = 0; score = { L:0, J:0, M:0, S:0 }; render(); });
  }
  render();
})();
</script>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
