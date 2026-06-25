<?php
/**
 * ViceHub X — Générateur de la collection wallpapers (30 nouveaux + thèmes).
 * Produit database/seed_wallpapers.sql :
 *   - 30 INSERT produits (catégorie wallpaper, sous-thème voiture/avion/ville/nuit/fille)
 *   - UPDATE des sous-thèmes pour les 28 wallpapers existants
 * Liste aussi les clés CDN à renseigner dans config/wallpapers.php.
 *   Usage : php scripts/gen-wallpapers.php
 */

// key (= nom de fichier propre & ?p=) => [sous-thème, nom FR, description FR]
$NEW = [
    // 🚗 Voiture (6)
    'wp-car-pink-vice'      => ['voiture', 'Cabriolet Rose — Vice Boulevard', 'Un cabriolet rose vif sur un boulevard bordé de palmiers au coucher de soleil. L’essence de Vice City.'],
    'wp-car-supercar-night' => ['voiture', 'Supercar Néon — Downtown', 'Une supercar fend la nuit du centre-ville, reflets magenta et cyan sur l’asphalte mouillé.'],
    'wp-car-muscle-beach'   => ['voiture', 'Muscle Car — Front de Mer', 'Une muscle car américaine longe l’océan turquoise sous une lumière dorée. Pur style rétro.'],
    'wp-car-lowrider'       => ['voiture', 'Lowrider Chromé — Néon', 'Un lowrider rutilant glisse dans une rue néon, chromes étincelants et ambiance eighties.'],
    'wp-car-offroad-glades' => ['voiture', 'Tout-Terrain — Everglades', 'Un pick-up tout-terrain éclabousse les marais au lever du soleil. Aventure dans les Everglades.'],
    'wp-car-classic-deco'   => ['voiture', 'Cabriolet Vintage — Ocean Drive', 'Un cabriolet d’époque garé devant les hôtels art déco illuminés de néons. Nuit sur Ocean Drive.'],
    // ✈️ Avion (5)
    'wp-plane-seaplane'     => ['avion', 'Hydravion — Décollage Doré', 'Un hydravion décolle d’une eau turquoise face à la skyline néon, au soleil couchant.'],
    'wp-plane-jet-skyline'  => ['avion', 'Jet Privé — Skyline', 'Un jet privé survole la ville illuminée au crépuscule. Luxe et altitude.'],
    'wp-plane-biplane'      => ['avion', 'Biplan — Plage Ensoleillée', 'Un biplan vintage survole une plage de palmiers sous un ciel bleu éclatant.'],
    'wp-plane-fighter-storm'=> ['avion', 'Chasseur — Orage Tropical', 'Un chasseur traverse des nuages d’orage spectaculaires au-dessus de la côte. Tension maximale.'],
    'wp-plane-heli-city'    => ['avion', 'Hélico — Survol Néon', 'Un hélicoptère survole la skyline néon de nuit, projecteur allumé. Ambiance polar.'],
    // 🌆 Ville (6)
    'wp-city-aerial-day'    => ['ville', 'Vue Aérienne — Métropole', 'Vue aérienne grandiose d’une métropole côtière tropicale à l’heure dorée. Plages et gratte-ciels.'],
    'wp-city-downtown-dusk' => ['ville', 'Downtown — Heure Bleue', 'Les gratte-ciels du centre reflètent un ciel violet et orangé à l’heure bleue.'],
    'wp-city-canal-district'=> ['ville', 'Quartier des Canaux', 'Un quartier de canaux aux façades art déco pastel, palmiers et petits bateaux. Douceur de vivre.'],
    'wp-city-skyline-water' => ['ville', 'Skyline sur l’Eau', 'La skyline néon se reflète sur une baie calme, rose et cyan. Carte postale de Vice City.'],
    'wp-city-bridge-sunset' => ['ville', 'Pont au Coucher de Soleil', 'Un long pont suspendu mène à la ville illuminée, lumières chaudes et reflets sur l’eau.'],
    'wp-city-market-street' => ['ville', 'Marché de Rue Latino', 'Un marché de rue latino animé au crépuscule, guirlandes lumineuses et étals colorés.'],
    // 🌃 Nuit (6)
    'wp-night-neon-strip'   => ['nuit', 'Strip Néon — Casino', 'Le strip des casinos s’embrase de néons, enseignes éclatantes et voitures de luxe.'],
    'wp-night-rooftop-pool' => ['nuit', 'Piscine sur le Toit', 'Une piscine à débordement sur un toit, skyline en fond et lueur néon. Glamour absolu.'],
    'wp-night-rain-street'  => ['nuit', 'Rue sous la Pluie', 'Une rue néon sous la pluie, reflets vifs sur l’asphalte. Atmosphère cinématographique.'],
    'wp-night-club-alley'   => ['nuit', 'Ruelle des Clubs', 'Une ruelle de clubs vibrante d’enseignes néon et de foule. L’énergie de la nuit.'],
    'wp-night-pier-lights'  => ['nuit', 'Jetée Illuminée', 'Une jetée et sa grande roue illuminées au bord de l’océan. Fête et lumières.'],
    'wp-night-skyline-storm'=> ['nuit', 'Skyline sous l’Orage', 'La skyline de nuit sous un orage spectaculaire, éclairs au-dessus des tours.'],
    // 💃 Fille (7)
    'wp-girl-convertible'   => ['fille', 'Au Volant du Cabriolet', 'Une jeune femme stylée en mode 80s conduit un cabriolet rose au coucher de soleil.'],
    'wp-girl-beach-sunset'  => ['fille', 'Silhouette sur la Plage', 'Une silhouette élégante marche sur une plage de palmiers au soleil couchant.'],
    'wp-girl-neon-portrait' => ['fille', 'Portrait Néon', 'Un portrait artistique baigné de néons rose et cyan. Esthétique rétro eighties.'],
    'wp-girl-rooftop'       => ['fille', 'Sur le Toit, Face à la Ville', 'Une femme assurée sur un toit domine la skyline néon de nuit.'],
    'wp-girl-poolside'      => ['fille', 'Au Bord de la Piscine', 'Ambiance resort de luxe au bord de la piscine, palmiers et heure dorée.'],
    'wp-girl-biker'         => ['fille', 'L’Esprit Biker', 'Veste en cuir et moto dans une rue néon. Attitude rétro et caractère.'],
    'wp-girl-marina'        => ['fille', 'Élégance à la Marina', 'Sur un yacht à la marina ensoleillée, eaux turquoise et palmiers. Douceur estivale.'],
];

// Sous-thèmes des 28 wallpapers déjà en boutique (clé fichier => thème)
$EXISTING = [
    'voiture' => ['wall-supercar', 'wp-pink-cruiser', 'wp-speedboat', 'wp-muscle-diner', 'wp-airboat', 'wp-desert-road'],
    'avion'   => ['wp-heli-night'],
    'ville'   => ['wall-skyline', 'wall-beach', 'wall-aerial', 'wall-marina', 'wp-aerial-sunset', 'wp-downtown-blue', 'wp-marina-dusk', 'wp-ocean-drive', 'wp-storm-bay', 'wp-street-market', 'wp-beach-sunset', 'wp-bridge'],
    'nuit'    => ['wall-synthwave', 'wall-nightlife', 'wall-flamingo', 'wp-rain-street', 'wp-synthwave', 'wp-club-alley', 'wp-casino', 'wp-pool-party', 'wp-flamingo'],
];

function q(string $s): string { return "'" . str_replace("'", "''", $s) . "'"; }

$featured_keys = ['wp-car-pink-vice', 'wp-city-skyline-water', 'wp-night-rooftop-pool', 'wp-girl-convertible', 'wp-plane-seaplane'];
$cols = '(name, slug, description, category, subcategory, price, currency, image, sale_type, digital_file, merchant, badge, featured, sort, lang)';
$values = [];
$sort = 300;
foreach ($NEW as $key => [$sub, $name, $desc]) {
    $slug = 'wallpaper-' . substr($key, 3);
    $img  = '/preview.php?p=' . $key;
    $file = 'storage/wallpapers/' . $key . '.png';
    $feat = in_array($key, $featured_keys, true) ? 1 : 0;
    $values[] = sprintf(
        '(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%d,%s)',
        q($name), q($slug), q($desc), q('wallpaper'), q($sub), '5.00', q('EUR'),
        q($img), q('stripe'), q($file), q('ViceHub Store'), q('HD'), $feat, $sort++, q('fr')
    );
}

$sql  = "-- ViceHub X — Collection wallpapers (30 nouveaux + thèmes) — généré par scripts/gen-wallpapers.php\n";
$sql .= "INSERT INTO products $cols VALUES\n" . implode(",\n", $values) . ";\n\n";
$sql .= "-- Sous-thèmes des wallpapers déjà en boutique\n";
foreach ($EXISTING as $theme => $keys) {
    $files = array_map(fn($k) => q('storage/wallpapers/' . $k . '.png'), $keys);
    $sql .= "UPDATE products SET subcategory=" . q($theme) . " WHERE digital_file IN (" . implode(',', $files) . ");\n";
}

$out = dirname(__DIR__) . '/database/seed_wallpapers.sql';
file_put_contents($out, $sql);

echo "OK : " . count($NEW) . " nouveaux wallpapers + " . array_sum(array_map('count', $EXISTING)) . " mises à jour de thèmes\n";
echo "Clés CDN à renseigner dans config/wallpapers.php :\n";
foreach (array_keys($NEW) as $k) { echo "  $k\n"; }
