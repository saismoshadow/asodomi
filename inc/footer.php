<?php
/**
 * Pie de página común del sitio.
 * Variables esperadas: $t (textos), $lang
 */
require_once __DIR__ . '/functions.php';
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="footer-brand">ASO<span>DOMI</span></p>
            <p class="footer-about"><?= e($t['footer']['about']) ?></p>
        </div>
        <div>
            <h3><?= e($t['footer']['quick_links']) ?></h3>
            <ul>
                <?php foreach ($t['nav'] as $key => $label): ?>
                    <li><a href="<?= e(url($lang, $key)) ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h3><?= e($t['footer']['contact']) ?></h3>
            <ul>
                <li><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></li>
                <li><a href="tel:<?= e(preg_replace('/\s+/', '', CONTACT_PHONE)) ?>"><?= e(CONTACT_PHONE) ?></a></li>
                <li><a href="https://wa.me/<?= e(ltrim(CONTACT_WHATSAPP, '+')) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
                <li><?= e(CONTACT_ADDRESS) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer-newsletter">
        <h3><?= e($t['newsletter']['iscriviti'] ?? 'Iscriviti alla newsletter') ?></h3>
        <form method="post" action="<?= e(url($lang, 'newsletter_subscribe')) ?>" class="newsletter-form">
            <?= csrf_campo() ?>
            <input type="email" name="email" placeholder="<?= e($t['newsletter']['email_placeholder'] ?? 'La tua email') ?>" required>
            <input type="text" name="nome" placeholder="<?= e($t['newsletter']['nome_placeholder'] ?? 'Il tuo nome (opzionale)') ?>">
            <button type="submit" class="btn btn-primary"><?= e($t['newsletter']['iscriviti_btn'] ?? 'Iscriviti') ?></button>
        </form>
    </div>
    <div class="container footer-bottom">
        <p>© <?= date('Y') ?> ASODOMI – <?= e(SITE_FULL_NAME) ?>. <?= e($t['footer']['rights']) ?></p>
        <p>
            <button type="button" id="installBtn" class="install-btn" hidden>⬇ <?= e($t['footer']['installa']) ?></button>
            <a href="<?= e(url($lang, 'privacy')) ?>"><?= e($t['privacy']['title']) ?></a> · <?= e($t['footer']['made_with']) ?> 🇩🇴🇨🇭
        </p>
    </div>
</footer>

<script src="<?= e(asset_v('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
