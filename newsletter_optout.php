<?php
/**
 * ASODOMI – Disiscrizione newsletter (endpoint pubblico, senza login).
 * Usa un token sicuro univoco incluso in ogni newsletter.
 */
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/newsletter.php';

$lang = isset($_GET['lang']) && in_array($_GET['lang'], $GLOBALS['ASODOMI_LANGS'], true) ? $_GET['lang'] : DEFAULT_LANG;
$t = asodomi_load_lang($lang);
$nwl = $t['newsletter'] ?? [];
$page = 'area-soci';

$token = (string)($_GET['token'] ?? '');
$ok = false;

if ($token !== '') {
    $ok = nl_disiscrivi($token, $_SERVER['REMOTE_ADDR'] ?? '');
}

require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
    <div class="container narrow">
        <h1><?= e($nwl['unsub_titolo'] ?? 'Disiscrizione dalla newsletter') ?></h1>
    </div>
</section>

<section class="section">
    <div class="container narrow center">
        <?php if ($ok): ?>
            <div class="alert alert-success">✅ <?= e($nwl['unsub_ok'] ?? 'Ti sei disiscritto dalla newsletter ASODOMI.') ?></div>
            <p class="muted"><?= e($nwl['unsub_ok_note'] ?? 'Non riceverai più le nostre comunicazioni email. Puoi iscriverti di nuovo in qualsiasi momento.') ?></p>
        <?php else: ?>
            <div class="alert alert-err">⚠️ <?= e($nwl['unsub_errore'] ?? 'Link non valido. Verifica il link nel messaggio ricevuto.') ?></div>
        <?php endif; ?>
        <p><a class="btn btn-primary" href="<?= e(url($lang, 'inicio')) ?>"><?= e($nwl['indietro'] ?? 'Torna al sito') ?></a></p>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>