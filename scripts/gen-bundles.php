<?php
/**
 * ViceHub X — Crée des BUNDLES de wallpapers (packs à prix réduit).
 *
 * Un bundle est un produit (category 'wallpaper', subcategory 'pack', vendu via
 * Stripe) dont la colonne bundle_items liste les IDs des wallpapers inclus.
 * À l'achat, le webhook expanse le bundle et livre tous les fichiers par e-mail
 * (PNG + JPEG + PDF par image). Augmente le panier moyen + booste le SEO « pack
 * de fonds d'écran Vice City ».
 *
 * Usage : php scripts/gen-bundles.php
 * Idempotent : met à jour les bundles existants (prix, composition) au lieu de doublonner.
 */
require_once __DIR__ . '/../config/config.php';

$pdo = db();

// Colonne bundle_items (MariaDB supporte IF NOT EXISTS).
try {
    $pdo->exec('ALTER TABLE products ADD COLUMN IF NOT EXISTS bundle_items VARCHAR(255) DEFAULT NULL');
} catch (Throwable $e) {
    // MySQL < 8 : on tente sans IF NOT EXISTS, en ignorant l'erreur "déjà présente".
    try { $pdo->exec('ALTER TABLE products ADD COLUMN bundle_items VARCHAR(255) DEFAULT NULL'); } catch (Throwable $e2) {}
}

// Récupère les wallpapers par thème (numériques, vendables).
$themes = ['voiture', 'avion', 'ville', 'nuit', 'fille'];
$byTheme = [];
$imgOf   = [];
$st = $pdo->prepare("SELECT id, image FROM products WHERE category='wallpaper' AND subcategory=? AND digital_file IS NOT NULL AND digital_file<>'' ORDER BY sort ASC, id ASC");
foreach ($themes as $th) {
    $st->execute([$th]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $byTheme[$th] = array_map(static fn($r) => (int) $r['id'], $rows);
    $imgOf[$th]   = $rows[0]['image'] ?? null;
}

// Tous les wallpapers (pour le pack intégral).
$allIds = [];
foreach ($byTheme as $ids) { $allIds = array_merge($allIds, $ids); }
$allIds = array_values(array_unique($allIds));

// Sélection « méga pack 20 » : 4 par thème (puis complété si besoin).
$mega = [];
foreach ($themes as $th) { $mega = array_merge($mega, array_slice($byTheme[$th], 0, 4)); }
$mega = array_slice(array_values(array_unique($mega)), 0, 20);

// Définition des bundles : [slug, nom FR, nom EN, IDs, prix €, badge, image, featured, cta].
$labels = ['voiture' => 'Voitures', 'avion' => 'Avions', 'ville' => 'Ville', 'nuit' => 'Nuit', 'fille' => 'Glamour'];
$priceByCount = static function (int $n): float {
    // ~ 70-85 % de réduction vs 5 €/unité, arrondi en .99
    $map = [6 => 11.99, 7 => 12.99, 12 => 17.99, 15 => 19.99, 18 => 21.99];
    if (isset($map[$n])) return $map[$n];
    if ($n >= 50) return 39.99;
    if ($n >= 20) return 22.99;
    return max(9.99, round($n * 1.4, 2) - 0.01);
};

$bundles = [];
foreach ($themes as $th) {
    $ids = $byTheme[$th];
    if (count($ids) < 3) continue;
    $bundles[] = [
        'slug'  => 'pack-' . $th,
        'fr'    => 'Pack ' . $labels[$th] . ' — ' . count($ids) . ' fonds d’écran HD',
        'en'    => $labels[$th] . ' Pack — ' . count($ids) . ' HD wallpapers',
        'ids'   => $ids,
        'price' => $priceByCount(count($ids)),
        'badge' => 'PACK -' . (int) round((1 - $priceByCount(count($ids)) / (count($ids) * 5)) * 100) . '%',
        'image' => $imgOf[$th],
        'feat'  => 1,
        'cta'   => 0,
    ];
}
if (count($mega) >= 12) {
    $bundles[] = ['slug' => 'pack-mega-20', 'fr' => 'Méga Pack — ' . count($mega) . ' fonds d’écran Vice City',
        'en' => 'Mega Pack — ' . count($mega) . ' Vice City wallpapers', 'ids' => $mega,
        'price' => 22.99, 'badge' => 'BEST-SELLER', 'image' => $imgOf['ville'] ?? $imgOf['nuit'], 'feat' => 1, 'cta' => 1];
}
if (count($allIds) >= 20) {
    $bundles[] = ['slug' => 'pack-integral', 'fr' => 'Pack Intégral — les ' . count($allIds) . ' fonds d’écran',
        'en' => 'Complete Pack — all ' . count($allIds) . ' wallpapers', 'ids' => $allIds,
        'price' => 39.99, 'badge' => 'ULTIME', 'image' => $imgOf['nuit'] ?? $imgOf['ville'], 'feat' => 1, 'cta' => 1];
}

$find = $pdo->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
$ins = $pdo->prepare(
    "INSERT INTO products (name, slug, description, category, subcategory, price, currency, image,
        sale_type, digital_file, bundle_items, merchant, badge, featured, cta, active, sort, lang)
     VALUES (?,?,?,'wallpaper','pack',?, 'EUR', ?, 'stripe', NULL, ?, 'ViceHub X', ?, ?, ?, 1, -100, 'fr')"
);
$upd = $pdo->prepare(
    'UPDATE products SET name=?, description=?, price=?, image=?, bundle_items=?, badge=?, featured=?, cta=?, sort=-100, active=1 WHERE id=?'
);

$made = 0; $updated = 0;
foreach ($bundles as $b) {
    $n = count($b['ids']);
    $descFr = "Pack de {$n} fonds d’écran GTA VI / Vice City en haute définition, livrés immédiatement par e-mail (PNG, JPEG et PDF), sans filigrane. Économise gros par rapport à l’achat à l’unité. 🌴";
    $items = implode(',', $b['ids']);
    $find->execute([$b['slug']]);
    $existing = (int) ($find->fetchColumn() ?: 0);
    if ($existing > 0) {
        $upd->execute([$b['fr'], $descFr, $b['price'], $b['image'], $items, $b['badge'], $b['feat'], $b['cta'], $existing]);
        $updated++;
        echo "~ maj  {$b['slug']} ({$n} wp · {$b['price']}€)\n";
    } else {
        $ins->execute([$b['fr'], $b['slug'], $descFr, $b['price'], $b['image'], $items, $b['badge'], $b['feat'], $b['cta']]);
        $made++;
        echo "+ créé {$b['slug']} ({$n} wp · {$b['price']}€)\n";
    }
}

echo "✓ Bundles : {$made} créé(s), {$updated} mis à jour.\n";
$tot = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE subcategory='pack'")->fetchColumn();
echo "→ {$tot} packs en boutique.\n";
