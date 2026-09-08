<?php
/**
 * ASODOMI – Conferma iscrizione newsletter (double opt-in).
 * Verifica il token, conferma l'iscrizione, invalida il token (single-use),
 * e mostra l'esito. Non richiede login.
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
$daConfermare = isset($_GET['pending']);

if (!$ok && $token !== '') {
    $ok = nl_conferma($token);              // conferma e invalida il token
    if ($ok) nl_invalida_token($token);
}

require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
    <div class="container narrow">
        <h1><?= e($nwl['conferma_titolo'] ?? 'Conferma iscrizione') ?></h1>
    </div>
</section>

<section class="section">
    <div class="container narrow center">
        <?php if ($ok): ?>
            <div class="alert alert-success">✅ <?= e($nwl['conferma_ok'] ?? 'Iscrizione confermata! Grazie.') ?></div>
        <?php elseif ($daConfermare): ?>
            <div class="alert alert-info">📧 <?= e($nwl['conferma_pending'] ?? 'Controlla la tua email: ti abbiamo inviato il link di conferma.') ?></div>
        <?php else: ?>
            <div class="alert alert-err">⚠️ <?= e($nwl['conferma_errore'] ?? 'Link non valido o scaduto. Verifica il link o iscriviti di nuovo.') ?></div>
        <?php endif; ?>
        <p><a class="btn btn-primary" href="<?= e(url($lang, 'inicio')) ?>"><?= e($nwl['indietro'] ?? 'Torna al sito') ?></a></p>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>