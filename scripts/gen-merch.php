<?php
/**
 * ViceHub X — Génère les nouveaux produits goodies (t-shirts, mugs, stylo, carnet…).
 * Produit database/seed_merch.sql (INSERT produits + propulsion CTA).
 *   Usage : php scripts/gen-merch.php
 * Les visuels sont mappés vers le CDN dans config/cdn_map.php.
 */

// key (= fichier image) => [catégorie, nom, description, prix, badge|null, featured, cta]
$M = [
    'shop-pen'            => ['accessory', 'Stylo Vice City',            'Stylo bille mat, accents néon rose et cyan. Glisse parfaitement sur le papier.',         6.90,  'Nouveau', 0, 1],
    'shop-notebook'       => ['accessory', 'Carnet Synthwave',          'Carnet à couverture rigide, design coucher de soleil néon. 120 pages lignées.',        12.90, 'Nouveau', 0, 1],
    'shop-tshirt-palm'    => ['apparel',   'T-shirt « Palm Sunset »',   'T-shirt premium 100% coton, graphique palmier & coucher de soleil néon. Coupe unisexe.', 24.90, 'Best-seller', 1, 1],
    'shop-tshirt-flamingo'=> ['apparel',   'T-shirt « Neon Flamingo »', 'T-shirt blanc premium, flamant rose néon sur la poitrine. Coupe unisexe.',              24.90, null, 0, 0],
    'shop-mug-skyline'    => ['accessory', 'Mug « Skyline »',           'Mug céramique noir, skyline néon tout autour. 33 cl, passe au lave-vaisselle.',          14.90, null, 1, 1],
    'shop-mug-enamel'     => ['accessory', 'Mug émaillé « Palm »',      'Mug émaillé style camping, palmier & soleil. Increvable, pour la route.',                16.90, null, 0, 0],
    'shop-tote'           => ['accessory', 'Tote bag « Vice City »',    'Sac en toile naturelle, skyline néon imprimée. Solide et spacieux.',                     17.90, null, 0, 0],
    'shop-stickers'       => ['accessory', 'Pack de stickers néon',     'Lot de stickers vinyle brillants : palmiers, flamant, sunset et formes synthwave.',      8.90,  'Pack', 0, 1],
    'shop-phonecase'      => ['accessory', 'Coque téléphone Néon',      'Coque smartphone, skyline synthwave néon. Protection et style Vice City.',               18.90, null, 0, 0],
    'shop-keychain'       => ['accessory', 'Porte-clés Palmier',        'Porte-clés acrylique translucide en forme de palmier néon.',                            6.90,  null, 0, 0],
];

function q(string $s): string { return "'" . str_replace("'", "''", $s) . "'"; }

$cols = '(name, slug, description, category, price, currency, image, sale_type, merchant, badge, featured, cta, sort, lang)';
$vals = [];
$sort = 400;
foreach ($M as $key => [$cat, $name, $desc, $price, $badge, $feat, $cta]) {
    $slug = $key; // shop-pen -> slug shop-pen (unique)
    $img  = '/public/assets/img/shop/' . $key . '.png';
    $vals[] = sprintf(
        '(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%d,%d,%s)',
        q($name), q($slug), q($desc), q($cat), number_format($price, 2, '.', ''), q('EUR'),
        q($img), q('stripe'), q('ViceHub Store'), $badge ? q($badge) : 'NULL', $feat, $cta, $sort++, q('fr')
    );
}

$sql  = "-- ViceHub X — Goodies (t-shirts, mugs, stylo, carnet…) — généré par scripts/gen-merch.php\n";
$sql .= "INSERT INTO products $cols VALUES\n" . implode(",\n", $vals) . ";\n\n";
$sql .= "-- Propulse aussi quelques wallpapers vedette en CTA (variété dans les articles)\n";
$sql .= "UPDATE products SET cta=1 WHERE category='wallpaper' AND featured=1;\n";

file_put_contents(dirname(__DIR__) . '/database/seed_merch.sql', $sql);
echo "OK : " . count($M) . " goodies écrits dans database/seed_merch.sql\n";
echo "Entrées cdn_map.php à ajouter (clé => URL CDN) :\n";
foreach (array_keys($M) as $k) { echo "  $k.png\n"; }
