<?php
require_once dirname(__DIR__) . '/config/config.php';

// ---- Actions panier (POST + CSRF) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $action = $_POST['action'] ?? '';
        $cart = cart_get();
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'add' && $id) {
            // On n'ajoute que des produits Stripe actifs
            $st = db()->prepare("SELECT id FROM products WHERE id = ? AND active = 1 AND sale_type = 'stripe' LIMIT 1");
            $st->execute([$id]);
            if ($st->fetch()) {
                $cart[$id] = min(20, ($cart[$id] ?? 0) + max(1, (int) ($_POST['qty'] ?? 1)));
                $_SESSION['cart_flash'] = lang() === 'fr' ? 'Produit ajouté au panier.' : 'Added to cart.';
            }
        } elseif ($action === 'update' && $id) {
            $qty = (int) ($_POST['qty'] ?? 1);
            if ($qty <= 0) {
                unset($cart[$id]);
            } else {
                $cart[$id] = min(20, $qty);
            }
        } elseif ($action === 'remove' && $id) {
            unset($cart[$id]);
        } elseif ($action === 'clear') {
            $cart = [];
        }
        cart_set($cart);
    }
    redirect(with_lang(url('pages/cart.php')));
}

$lines = cart_lines();
$total = cart_total();
$cur   = shop_currency();
$flash = $_SESSION['cart_flash'] ?? null;
unset($_SESSION['cart_flash']);

$SEO_TITLE = (lang() === 'fr' ? 'Panier' : 'Cart') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr' ? 'Votre panier ViceHub X.' : 'Your ViceHub X cart.';
$ROBOTS    = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section">
    <span class="eyebrow">🛒 ViceHub X</span>
    <h1><?= lang() === 'fr' ? 'Votre panier' : 'Your cart' ?></h1>

    <?php if ($flash): ?><div class="alert alert--ok"><?= e($flash) ?></div><?php endif; ?>

    <?php if (!$lines): ?>
        <p class="muted" style="margin:1.5rem 0"><?= lang() === 'fr' ? 'Votre panier est vide.' : 'Your cart is empty.' ?></p>
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= lang() === 'fr' ? 'Découvrir la boutique' : 'Browse the shop' ?> →</a>
    <?php else: ?>
        <div class="cart">
            <div class="cart__lines">
                <?php foreach ($lines as $l): ?>
                    <div class="cart-line glass">
                        <div class="cart-line__media">
                            <span class="card__emoji" aria-hidden="true">🛍️</span>
                            <?php if (!empty($l['image'])): ?><img src="<?= e($l['image']) ?>" alt="<?= e($l['name']) ?>" loading="lazy" onerror="this.remove()"><?php endif; ?>
                        </div>
                        <div class="cart-line__info">
                            <h3><?= e($l['name']) ?></h3>
                            <span class="muted"><?= price_html($l['price'], $l['currency']) ?> <?= lang() === 'fr' ? 'l’unité' : 'each' ?></span>
                        </div>
                        <form method="post" class="cart-line__qty">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                            <input type="number" name="qty" min="0" max="20" value="<?= (int) $l['qty'] ?>" aria-label="Quantité" onchange="this.form.submit()">
                        </form>
                        <div class="cart-line__total"><?= price_html($l['line_total'], $l['currency']) ?></div>
                        <form method="post" class="cart-line__rm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                            <button type="submit" aria-label="Retirer" title="Retirer">✕</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="cart__summary glass">
                <h2><?= lang() === 'fr' ? 'Récapitulatif' : 'Summary' ?></h2>
                <div class="cart__row"><span><?= lang() === 'fr' ? 'Total' : 'Total' ?></span><strong><?= price_html($total, $cur) ?></strong></div>
                <?php if (stripe_enabled()): ?>
                    <form method="post" action="<?= e(url('checkout.php')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn--primary btn--lg" type="submit" style="width:100%;justify-content:center">
                            <?= lang() === 'fr' ? 'Payer avec Stripe' : 'Pay with Stripe' ?> →
                        </button>
                    </form>
                    <p class="muted" style="font-size:.78rem;margin:.7rem 0 0">🔒 <?= lang() === 'fr' ? 'Paiement sécurisé par Stripe.' : 'Secure payment by Stripe.' ?></p>
                <?php else: ?>
                    <button class="btn btn--ghost btn--lg" type="button" disabled style="width:100%;justify-content:center;opacity:.6;cursor:not-allowed">
                        <?= lang() === 'fr' ? 'Paiement bientôt disponible' : 'Checkout coming soon' ?>
                    </button>
                    <p class="muted" style="font-size:.78rem;margin:.7rem 0 0"><?= lang() === 'fr' ? 'Le paiement en ligne sera activé très bientôt.' : 'Online checkout will be enabled soon.' ?></p>
                <?php endif; ?>
                <form method="post" style="margin-top:.8rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="clear">
                    <button class="link-all" style="background:none;border:0;cursor:pointer"><?= lang() === 'fr' ? 'Vider le panier' : 'Empty cart' ?></button>
                </form>
            </aside>
        </div>
        <a class="link-all" href="<?= e(with_lang(url('pages/shop.php'))) ?>" style="display:inline-block;margin-top:1.4rem">← <?= lang() === 'fr' ? 'Continuer mes achats' : 'Continue shopping' ?></a>
    <?php endif; ?>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
