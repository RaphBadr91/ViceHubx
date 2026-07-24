<?php
/**
 * ViceHub X — Impression à la demande (Print-on-Demand) via Printful.
 * ------------------------------------------------------------------
 * MODE DORMANT : tout est prêt mais INACTIF tant que :
 *   1) une clé API Printful n'est pas renseignée (Admin → Réglages), ET
 *   2) l'interrupteur « Traitement automatique » n'est pas activé.
 *
 * Une fois activé : à chaque commande payée (webhook Stripe), les articles
 * physiques associés à une variante Printful sont envoyés automatiquement à
 * Printful qui imprime + expédie directement au client (zéro stock, zéro
 * manutention). Aucune clé n'est stockée dans le code / Git : tout passe par
 * les réglages en base (comme Stripe, Resend, TikTok, Meta).
 *
 * Doc API : https://developers.printful.com/docs/
 */

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

/* ------------------------------------------------------------------ */
/*  Configuration / état                                               */
/* ------------------------------------------------------------------ */

/** Clé API Printful (variable d'environnement OU réglage en base). Jamais dans Git. */
function printful_key(): string
{
    return (string) (getenv('PRINTFUL_API_KEY') ?: get_setting('printful_api_key', ''));
}

/** ID de boutique Printful (nécessaire si le jeton a accès à plusieurs boutiques). */
function printful_store_id(): string
{
    return trim((string) (getenv('PRINTFUL_STORE_ID') ?: get_setting('printful_store_id', '')));
}

/**
 * Le traitement automatique est-il RÉELLEMENT actif ?
 * Dormant tant qu'il manque la clé OU que l'interrupteur est sur OFF.
 */
function printful_enabled(): bool
{
    return printful_key() !== '' && get_setting('printful_enabled', '0') === '1';
}

/** Confirme automatiquement (= lance l'impression + débit) ? Sinon la commande arrive en brouillon. */
function printful_auto_confirm(): bool
{
    return get_setting('printful_auto_confirm', '0') === '1';
}

/** État synthétique pour l'admin. */
function printful_status(): array
{
    return [
        'key'          => printful_key() !== '',
        'store_id'     => printful_store_id(),
        'enabled'      => printful_enabled(),
        'auto_confirm' => printful_auto_confirm(),
    ];
}

/* ------------------------------------------------------------------ */
/*  Schéma (colonne produit + table de suivi) — best-effort            */
/* ------------------------------------------------------------------ */

/**
 * Garantit la colonne `products.printful_variant_id` + la table `printful_orders`.
 * Vérifie information_schema d'abord (compatible droits DDL limités : silencieux si impossible).
 */
function printful_ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $has = (int) db()->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'printful_variant_id'"
        )->fetchColumn();
        if ($has === 0) {
            db()->exec("ALTER TABLE products ADD COLUMN printful_variant_id VARCHAR(64) DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // Colonne déjà présente ou droits limités : on continue.
    }
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS printful_orders (
                id                INT AUTO_INCREMENT PRIMARY KEY,
                order_id          INT DEFAULT NULL,
                stripe_session    VARCHAR(190) UNIQUE,
                printful_order_id VARCHAR(64) DEFAULT NULL,
                status            VARCHAR(40) NOT NULL DEFAULT 'pending',
                error             VARCHAR(500) DEFAULT NULL,
                created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );
    } catch (Throwable $e) {
        // Table déjà présente ou droits limités.
    }
}

/* ------------------------------------------------------------------ */
/*  Appel API bas niveau                                               */
/* ------------------------------------------------------------------ */

/** Appel REST minimal à l'API Printful (sans SDK, via cURL). */
function printful_api(string $method, string $path, array $body = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL est requis pour Printful.');
    }
    $key = printful_key();
    if ($key === '') {
        throw new RuntimeException('Clé API Printful manquante.');
    }
    $ch = curl_init('https://api.printful.com/' . ltrim($path, '/'));
    $headers = [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    $store = printful_store_id();
    if ($store !== '') {
        $headers[] = 'X-PF-Store-Id: ' . $store;
    }
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_TIMEOUT        => 25,
    ];
    if ($body && strtoupper($method) !== 'GET') {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    curl_setopt_array($ch, $opts);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException('Connexion à Printful impossible : ' . $err);
    }
    $data = json_decode((string) $raw, true) ?: [];
    if ($code >= 400) {
        $msg = '';
        if (isset($data['error']['message'])) {
            $msg = (string) $data['error']['message'];
        } elseif (isset($data['result']) && is_string($data['result'])) {
            $msg = $data['result'];
        }
        throw new RuntimeException($msg !== '' ? $msg : ('Erreur Printful (' . $code . ')'));
    }
    return $data;
}

/** Test de connectivité : renvoie le nom de la boutique si la clé est valide. */
function printful_test(): array
{
    try {
        if (printful_store_id() !== '') {
            $r = printful_api('GET', 'store');
            $name = $r['result']['name'] ?? '';
        } else {
            $r = printful_api('GET', 'stores');
            $name = $r['result'][0]['name'] ?? '';
        }
        return ['ok' => true, 'name' => (string) $name];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Liste les produits synchronisés de la boutique Printful, avec pour chaque
 * variante son `sync_variant_id` (à coller dans la fiche produit du site).
 * Évite à l'utilisateur toute manipulation technique de l'API.
 */
function printful_sync_products(int $maxProducts = 60): array
{
    $out  = [];
    $list = printful_api('GET', 'store/products?limit=' . max(1, min(100, $maxProducts)));
    foreach (($list['result'] ?? []) as $p) {
        $pid = (int) ($p['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $variants = [];
        try {
            $detail = printful_api('GET', 'store/products/' . $pid);
            foreach (($detail['result']['sync_variants'] ?? []) as $v) {
                $variants[] = [
                    'sync_variant_id' => (string) ($v['id'] ?? ''),
                    'name'            => (string) ($v['name'] ?? ''),
                    'retail_price'    => (string) ($v['retail_price'] ?? ''),
                    'currency'        => (string) ($v['currency'] ?? ''),
                ];
            }
        } catch (Throwable $e) {
            // Détail indisponible pour ce produit : on liste au moins son nom.
        }
        $out[] = [
            'id'       => $pid,
            'name'     => (string) ($p['name'] ?? ('Produit #' . $pid)),
            'variants' => $variants,
        ];
    }
    return $out;
}

/* ------------------------------------------------------------------ */
/*  Fabrique une commande Printful à partir d'une session Stripe       */
/* ------------------------------------------------------------------ */

/**
 * Transforme la métadonnée « phys » (id:qty,id:qty…) en articles Printful.
 * Ne garde que les produits reliés à une variante Printful (printful_variant_id).
 */
function printful_items_from_spec(string $spec): array
{
    $items = [];
    if (trim($spec) === '') {
        return $items;
    }
    $q = db()->prepare('SELECT printful_variant_id FROM products WHERE id = ? LIMIT 1');
    foreach (array_filter(array_map('trim', explode(',', $spec))) as $pair) {
        [$id, $qty] = array_pad(explode(':', $pair, 2), 2, '1');
        $id  = (int) $id;
        $qty = max(1, (int) $qty);
        if ($id <= 0) {
            continue;
        }
        $q->execute([$id]);
        $vid = trim((string) ($q->fetchColumn() ?: ''));
        if ($vid === '') {
            continue; // produit sans variante Printful : ignoré (ex. affiche perso, wallpaper).
        }
        $item = ['quantity' => $qty];
        if (ctype_digit($vid)) {
            // ID numérique = variante synchronisée de VOTRE boutique Printful.
            $item['sync_variant_id'] = (int) $vid;
        } else {
            // Sinon on suppose un identifiant externe défini côté Printful.
            $item['external_variant_id'] = $vid;
        }
        $items[] = $item;
    }
    return $items;
}

/** Extrait le destinataire (nom + adresse) d'une session Stripe Checkout. */
function printful_recipient_from_session(array $s): array
{
    $ship = $s['shipping_details']
        ?? ($s['collected_information']['shipping_details'] ?? ($s['shipping'] ?? []));
    $cust = $s['customer_details'] ?? [];
    if (!is_array($ship)) {
        $ship = [];
    }
    if (!is_array($cust)) {
        $cust = [];
    }
    $addr = $ship['address'] ?? ($cust['address'] ?? []);
    if (!is_array($addr)) {
        $addr = [];
    }
    $name  = $ship['name'] ?? ($cust['name'] ?? '');
    $email = $cust['email'] ?? ($s['customer_email'] ?? '');
    $phone = $cust['phone'] ?? ($ship['phone'] ?? '');

    return array_filter([
        'name'         => trim((string) $name),
        'address1'     => trim((string) ($addr['line1'] ?? '')),
        'address2'     => trim((string) ($addr['line2'] ?? '')),
        'city'         => trim((string) ($addr['city'] ?? '')),
        'state_code'   => trim((string) ($addr['state'] ?? '')),
        'country_code' => trim((string) ($addr['country'] ?? '')),
        'zip'          => trim((string) ($addr['postal_code'] ?? '')),
        'email'        => trim((string) $email),
        'phone'        => trim((string) $phone),
    ], static fn ($v) => $v !== '');
}

/** Crée la commande côté Printful (brouillon, ou confirmée si auto-confirm). */
function printful_create_order(array $recipient, array $items, array $opts = []): array
{
    $path = 'orders' . (printful_auto_confirm() ? '?confirm=1' : '');
    $body = ['recipient' => $recipient, 'items' => $items];
    if (!empty($opts['external_id'])) {
        $body['external_id'] = (string) $opts['external_id'];
    }
    return printful_api('POST', $path, $body);
}

/** Enregistre/actualise le suivi d'une tentative de commande Printful. */
function printful_record(string $sid, ?string $pid, string $status, ?string $error): void
{
    try {
        $oid = order_id_for_session($sid);
        $st = db()->prepare(
            'INSERT INTO printful_orders (order_id, stripe_session, printful_order_id, status, error)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE printful_order_id = VALUES(printful_order_id),
                                     status = VALUES(status), error = VALUES(error)'
        );
        $st->execute([$oid ?: null, $sid, $pid, $status, $error !== null ? mb_substr($error, 0, 480) : null]);
    } catch (Throwable $e) {
        // Suivi best-effort : ne bloque jamais.
    }
}

/**
 * Point d'entrée appelé par le webhook Stripe après un paiement.
 * Totalement inoffensif tant que Printful est dormant (renvoie un « skip »).
 * Ne lève JAMAIS d'exception : le webhook doit toujours répondre 200 à Stripe.
 */
function printful_fulfill_session(array $s): array
{
    try {
        if (!printful_enabled()) {
            return ['skipped' => 'dormant'];
        }
        printful_ensure_schema();

        $sid = (string) ($s['id'] ?? '');
        if ($sid === '') {
            return ['skipped' => 'no_session'];
        }
        // Idempotence : déjà traité avec succès ? on ne renvoie pas deux fois.
        $chk = db()->prepare('SELECT printful_order_id, status FROM printful_orders WHERE stripe_session = ? LIMIT 1');
        $chk->execute([$sid]);
        if ($prev = $chk->fetch()) {
            if (in_array($prev['status'], ['created', 'confirmed'], true)) {
                return ['skipped' => 'already', 'printful_order_id' => $prev['printful_order_id']];
            }
        }

        $items = printful_items_from_spec((string) ($s['metadata']['phys'] ?? ''));
        if (!$items) {
            return ['skipped' => 'no_printful_items'];
        }

        $recipient = printful_recipient_from_session($s);
        if (empty($recipient['address1']) || empty($recipient['country_code'])) {
            printful_record($sid, null, 'error', 'Adresse de livraison incomplète (address1/country manquant).');
            return ['error' => 'missing_address'];
        }

        $oid = order_id_for_session($sid);
        $res = printful_create_order($recipient, $items, ['external_id' => 'vhx-' . ($oid ?: $sid)]);
        $pid = (string) ($res['result']['id'] ?? ($res['id'] ?? ''));
        $status = printful_auto_confirm() ? 'confirmed' : 'created';
        printful_record($sid, $pid, $status, null);
        return ['ok' => true, 'printful_order_id' => $pid, 'status' => $status];
    } catch (Throwable $e) {
        if (!empty($s['id'])) {
            printful_record((string) $s['id'], null, 'error', $e->getMessage());
        }
        return ['error' => $e->getMessage()];
    }
}

/** Dernières tentatives de commande Printful (pour l'admin). */
function printful_recent(int $limit = 15): array
{
    try {
        printful_ensure_schema();
        $st = db()->prepare('SELECT * FROM printful_orders ORDER BY id DESC LIMIT ?');
        $st->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}
