<?php
/**
 * ViceHub X — Outil VIRAL « Ton perso Vice City ».
 * Le visiteur entre son prénom → on génère (de façon déterministe, donc partageable)
 * son alias de gangster, son rôle, son quartier, sa caisse, ses stats et un AVATAR IA.
 * Gros potentiel de partages réseaux = backlinks + trafic organique gratuit.
 */
require_once dirname(__DIR__) . '/config/config.php';

$fr = lang() === 'fr';

/** Banque d'avatars IA (Higgsfield), servie depuis le CDN — aucun coût par visiteur. */
function vp_avatars(): array
{
    $b = 'https://d8j0ntlcm91z4.cloudfront.net/user_3DO7HqDJu2i1Hy0ZwCkmP0PQX9E/';
    return [
        'h' => [
            $b . 'hf_20260712_101427_3ac4b512-b7bc-42c6-b121-22592da81a69_min.webp',
            $b . 'hf_20260712_101431_a9194e16-da95-4eac-bc94-17138dcf42ba_min.webp',
            $b . 'hf_20260712_101433_efb2c914-1369-4c2c-8c27-7a7b5517641f_min.webp',
            $b . 'hf_20260712_101435_0b4552e4-0228-412d-8692-e8a749369ee4_min.webp',
            $b . 'hf_20260712_101445_efb2082d-400e-4ea4-bd35-901ef4c43a56_min.webp',
        ],
        'f' => [
            $b . 'hf_20260712_101438_9e3f18d8-ce52-4095-89f6-5c3b63824d27_min.webp',
            $b . 'hf_20260712_101441_b81cc808-ae79-4b77-be69-e08e07d4d376_min.webp',
            $b . 'hf_20260712_101443_c68d3000-7d42-4255-bfef-d0e228b1c08a_min.webp',
        ],
    ];
}

function vp_pick(array $arr, string $seed): string
{
    return (string) $arr[crc32($seed) % max(1, count($arr))];
}
function vp_stat(string $seed): int
{
    return 55 + (int) (hexdec(substr(md5($seed), 0, 6)) % 45); // 55–99 (tout le monde a la classe)
}

/** Génère un perso Vice City déterministe (même prénom = même résultat = partageable). */
function vice_persona(string $name, string $gender): array
{
    $n = mb_strtolower(trim($name));
    $epithets = ['Viper', 'Cobra', 'Sunset', 'Chrome', 'Riptide', 'Diamond', 'Shadow', 'Flamingo', 'Blaze', 'Neon', 'Havana', 'Storm', 'Ace', 'Onyx', 'Mirage', 'Sable'];
    $surnames = ['Vice', 'Cruz', 'Diaz', 'Santos', 'Rivera', 'Kane', 'Moreno', 'Vega', 'Delgado', 'Castillo', 'Marino', 'Reyes', 'Costa', 'Salazar'];
    $roles = ['Baron·ne de la nuit', 'Pilote de course clandestine', 'DJ résident du Malibu Club', 'Braqueur·euse de génie',
        'Roi/Reine d’Ocean Drive', 'Contrebandier·ère des Keys', 'Boss de cartel', 'Star montante de Vice City', 'Chasseur·euse de primes'];
    $districts = ['Ocean Drive', 'Little Havana', 'Downtown Vice City', 'les Keys', 'Port Gellhorn', 'Vice Beach', 'le quartier des clubs', 'Leaf Links'];
    $rides = ['une décapotable rose', 'une muscle car noire mate', 'un jet-ski turbo', 'une moto néon', 'une supercar italienne', 'un cigarette boat', 'un lowrider chromé'];
    $quotes = ['La nuit m’appartient.', 'Vice City, c’est mon terrain de jeu.', 'On ne me double jamais deux fois.',
        'Le néon coule dans mes veines.', 'Fast money, faster cars.', 'Je joue, je gagne, je disparais.', 'Bienvenue dans ma ville.'];

    $av = vp_avatars();
    $pool = $gender === 'h' ? $av['h'] : ($gender === 'f' ? $av['f'] : array_merge($av['h'], $av['f']));

    $alias = mb_convert_case(trim($name), MB_CASE_TITLE) . ' « ' . vp_pick($epithets, $n . 'e') . ' » ' . vp_pick($surnames, $n . 's');
    return [
        'alias'    => $alias,
        'role'     => vp_pick($roles, $n . 'r'),
        'district' => vp_pick($districts, $n . 'd'),
        'ride'     => vp_pick($rides, $n . 'v'),
        'quote'    => vp_pick($quotes, $n . 'q'),
        'avatar'   => vp_pick($pool, $n . 'a'),
        'stats'    => [
            'Réputation' => vp_stat($n . 'rep'),
            'Style'      => vp_stat($n . 'sty'),
            'Sang-froid' => vp_stat($n . 'cool'),
            'Fortune'    => vp_stat($n . 'cash'),
        ],
    ];
}

$name   = trim((string) ($_GET['name'] ?? ''));
$name   = mb_substr(preg_replace('/[^\p{L}\p{N} \'\-]/u', '', $name) ?? '', 0, 24);
$gender = in_array($_GET['g'] ?? '', ['h', 'f', 'x'], true) ? $_GET['g'] : 'x';
$p      = $name !== '' ? vice_persona($name, $gender) : null;

$SEO_TITLE = $fr
    ? 'Ton perso Vice City — Générateur de nom & avatar GTA 6 | ViceHub X'
    : 'Your Vice City persona — GTA 6 name & avatar generator | ViceHub X';
$SEO_DESC = $fr
    ? 'Entre ton prénom et découvre ton alias de gangster, ton avatar et tes stats dans l’univers de GTA 6 / Vice City. Gratuit, fun et à partager !'
    : 'Enter your name and discover your gangster alias, avatar and stats in the GTA 6 / Vice City universe. Free, fun and shareable!';

$base     = rtrim(site_base_url(), '/');
$shareUrl = $base . '/vice-persona?name=' . rawurlencode($name) . '&g=' . $gender;
$shareTxt = $p ? ('Je suis ' . $p['alias'] . ', ' . $p['role'] . ' de Vice City 🌴 Découvre TON perso GTA 6 :') : '';

require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="max-width:760px;margin:0 auto">
    <span class="eyebrow"><?= vhx_icon('palm') ?> ViceHub X</span>
    <h1 style="font-size:clamp(1.9rem,5vw,3rem);margin:.2rem 0 .4rem">Ton perso Vice City</h1>
    <p class="muted" style="font-size:1.05rem">Entre ton prénom → découvre ton <strong>alias de gangster</strong>, ton <strong>avatar</strong> et tes stats dans l’univers de <strong>GTA 6</strong>. À partager sans modération 🔥</p>

    <form method="get" class="glass" style="padding:1.2rem;border-radius:16px;margin:1.2rem 0;display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end">
        <label style="flex:1;min-width:200px">Ton prénom
            <input type="text" name="name" value="<?= e($name) ?>" maxlength="24" placeholder="ex. Alex" required style="display:block;width:100%;margin-top:.3rem">
        </label>
        <label style="min-width:150px">Style d’avatar
            <select name="g" style="display:block;width:100%;margin-top:.3rem">
                <option value="x" <?= $gender === 'x' ? 'selected' : '' ?>>Peu importe</option>
                <option value="h" <?= $gender === 'h' ? 'selected' : '' ?>>Homme</option>
                <option value="f" <?= $gender === 'f' ? 'selected' : '' ?>>Femme</option>
            </select>
        </label>
        <button class="btn btn--primary" type="submit">🎲 Générer mon perso</button>
    </form>

    <?php if ($p): ?>
        <article class="glass reveal" style="border-radius:20px;overflow:hidden;border:1px solid rgba(255,46,136,.35);box-shadow:0 0 60px rgba(255,46,136,.15)">
            <div style="display:flex;flex-wrap:wrap">
                <div style="flex:1;min-width:240px;position:relative">
                    <img src="<?= e($p['avatar']) ?>" alt="<?= e($p['alias']) ?>" loading="eager" style="width:100%;height:100%;min-height:280px;object-fit:cover;display:block">
                    <span class="badge" style="position:absolute;top:1rem;left:1rem;background:linear-gradient(90deg,#ff2e88,#7a5cff);color:#fff;font-weight:800">🌴 VICE CITY</span>
                </div>
                <div style="flex:1.2;min-width:260px;padding:1.5rem">
                    <span class="card__cat">TON ALIAS</span>
                    <h2 style="font-size:1.6rem;margin:.2rem 0 .1rem;text-transform:none;color:#fff"><?= e($p['alias']) ?></h2>
                    <p style="color:#2bd6ff;font-weight:700;margin:.2rem 0 1rem"><?= e($p['role']) ?></p>
                    <p style="margin:.2rem 0"><strong>📍 Quartier :</strong> <?= e($p['district']) ?></p>
                    <p style="margin:.2rem 0"><strong>🚗 Sa caisse :</strong> <?= e($p['ride']) ?></p>
                    <p style="margin:.6rem 0;font-style:italic;color:#ffb3d9">« <?= e($p['quote']) ?> »</p>

                    <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.5rem">
                        <?php foreach ($p['stats'] as $label => $val): ?>
                            <div>
                                <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:.15rem"><span class="muted"><?= e($label) ?></span><strong style="color:#ff2e88"><?= (int) $val ?></strong></div>
                                <div style="height:8px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden"><div style="height:100%;width:<?= (int) $val ?>%;border-radius:99px;background:linear-gradient(90deg,#ff2e88,#7a5cff)"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="padding:1rem 1.5rem;border-top:1px solid var(--glass-brd);display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
                <strong style="margin-right:.3rem">Partage ton perso :</strong>
                <a class="btn btn--ghost" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareTxt) ?>&url=<?= rawurlencode($shareUrl) ?>">𝕏 / Twitter</a>
                <a class="btn btn--ghost" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>">Facebook</a>
                <a class="btn btn--ghost" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text=<?= rawurlencode($shareTxt . ' ' . $shareUrl) ?>">WhatsApp</a>
                <button class="btn btn--ghost" type="button" data-copy="<?= e($shareUrl) ?>">🔗 Copier le lien</button>
            </div>
        </article>
        <p class="muted" style="text-align:center;margin-top:1rem;font-size:.85rem">Fais tourner à tes potes et compare vos persos 👀 — <a class="link-all" href="<?= e(with_lang(url('pages/vice-persona.php'))) ?>">rejouer</a></p>
    <?php else: ?>
        <div class="glass" style="border-radius:16px;padding:2rem;text-align:center">
            <p style="font-size:3rem;margin:0">🕶️</p>
            <p class="muted">Entre ton prénom ci-dessus pour révéler ton identité secrète à Vice City…</p>
        </div>
    <?php endif; ?>
</section>
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]'); if (!b) return;
    var txt = b.getAttribute('data-copy'); var done = function () { var o = b.textContent; b.textContent = '✅ Copié !'; setTimeout(function () { b.textContent = o; }, 1600); };
    if (navigator.clipboard) { navigator.clipboard.writeText(txt).then(done).catch(done); }
    else { var t = document.createElement('textarea'); t.value = txt; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (x) {} document.body.removeChild(t); done(); }
});
</script>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
