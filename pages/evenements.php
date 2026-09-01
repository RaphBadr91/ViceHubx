<?php
require_once dirname(__DIR__) . '/config/config.php';
$fr = lang() === 'fr';
$events = get_events(12);
$SEO_TITLE = ($fr ? 'Événements GTA VI — comptes à rebours' : 'GTA VI events & countdowns') . ' — ' . APP_NAME;
$SEO_DESC  = $fr
    ? 'Tous les rendez-vous de la communauté GTA VI : sortie du jeu, watch parties, deals et événements ViceHub X, avec comptes à rebours en direct.'
    : 'All GTA VI community dates: release, watch parties, deals and ViceHub X events with live countdowns.';
$SEO_OG_IMAGE = cdn_url('downtown.png');
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow"><?= vhx_icon('calendar') ?> ViceHub X</span>
    <h1><?= $fr ? 'Événements & comptes à rebours' : 'Events & countdowns' ?></h1>
    <p class="muted" style="max-width:720px"><?= $fr
        ? 'Tous les rendez-vous à ne pas manquer avant et après la sortie de GTA VI.'
        : 'All the key dates before and after GTA VI launch.' ?></p>

    <?php if (!$events): ?>
        <p class="muted"><?= e(t('no_content')) ?></p>
    <?php else: ?>
    <div class="events-grid">
        <?php foreach ($events as $ev): $iso = str_replace(' ', 'T', (string) $ev['event_date']); ?>
            <article class="event-card glass reveal">
                <span class="event-ico"><?= e($ev['icon'] ?: '📌') ?></span>
                <div class="event-body">
                    <h3><?= e($ev['title']) ?></h3>
                    <p class="muted"><?= e($ev['description']) ?></p>
                    <div class="event-cd" data-date="<?= e($iso) ?>">
                        <span data-ev="d">--</span><i>j</i>
                        <span data-ev="h">--</span><i>h</i>
                        <span data-ev="m">--</span><i>m</i>
                        <span data-ev="s">--</span><i>s</i>
                    </div>
                    <div class="event-meta">
                        <span class="muted"><?= e(fmt_date($ev['event_date'])) ?></span>
                        <?php if (!empty($ev['link'])): ?><a class="link-all" href="<?= e(with_lang(url(ltrim($ev['link'], '/')))) ?>"><?= $fr ? 'En savoir plus' : 'Learn more' ?> →</a><?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<script>
(function () {
  var FR = <?= $fr ? 'true' : 'false' ?>;
  function tick() {
    var now = Date.now();
    document.querySelectorAll('.event-cd').forEach(function (el) {
      var t = new Date(el.getAttribute('data-date')).getTime();
      var d = Math.max(0, t - now), s = Math.floor(d / 1000);
      var dd = Math.floor(s / 86400), hh = Math.floor(s % 86400 / 3600), mm = Math.floor(s % 3600 / 60), ss = s % 60;
      var set = function (k, v) { var n = el.querySelector('[data-ev=' + k + ']'); if (n) n.textContent = (v < 10 ? '0' : '') + v; };
      set('d', dd); set('h', hh); set('m', mm); set('s', ss);
      if (d === 0 && !el.classList.contains('done')) { el.classList.add('done'); el.innerHTML = '<b>' + (FR ? '🎉 C’est maintenant !' : '🎉 Happening now!') + '</b>'; }
    });
  }
  tick(); setInterval(tick, 1000);
})();
</script>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
