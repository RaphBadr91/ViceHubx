<?php
require_once dirname(__DIR__) . '/config/config.php';
$SEO_TITLE = (lang() === 'fr' ? 'Paiement annulé' : 'Payment cancelled') . ' — ' . APP_NAME;
$SEO_DESC  = lang() === 'fr' ? 'Paiement annulé.' : 'Payment cancelled.';
$ROBOTS    = 'noindex,nofollow';
require ROOT_PATH . '/includes/header.php';
?>
<section class="section" style="text-align:center;max-width:640px">
    <div style="font-size:3.4rem">🛒</div>
    <h1><?= lang() === 'fr' ? 'Paiement annulé' : 'Payment cancelled' ?></h1>
    <p class="muted"><?= lang() === 'fr' ? 'Aucun montant n’a été débité. Votre panier est conservé.' : 'You were not charged. Your cart has been kept.' ?></p>
    <div style="margin-top:1.6rem;display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap">
        <a class="btn btn--primary" href="<?= e(with_lang(url('pages/cart.php'))) ?>"><?= lang() === 'fr' ? 'Retour au panier' : 'Back to cart' ?></a>
        <a class="btn btn--ghost" href="<?= e(with_lang(url('pages/shop.php'))) ?>"><?= lang() === 'fr' ? 'Continuer mes achats' : 'Continue shopping' ?></a>
    </div>
</section>
<?php require ROOT_PATH . '/includes/footer.php'; ?>
