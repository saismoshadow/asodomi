<?php
/**
 * Disiscrizione newsletter (public endpoint)
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/config.php';

$lang = $_GET['lang'] ?? DEFAULT_LANG;
if (!in_array($lang, $GLOBALS['ASODOMI_LANGS'], true)) {
    $lang = DEFAULT_LANG;
}
$page = 'newsletter_unsubscribe';
$t = asodomi_load_lang($lang);

$token = $_GET['token'] ?? '';
$messaggio = '';
$errore = '';

if ($token) {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE newsletter_iscritti SET attivo = 0 WHERE token_unsubscribe = ?");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() > 0) {
        $messaggio = $t['newsletter']['disiscriviti_ok'] ?? 'Ti sei disiscritto correttamente';
    } else {
        $errore = $t['newsletter']['disiscriviti_errore'] ?? 'Token non valido o scaduto';
    }
} else {
    $errore = $t['newsletter']['disiscriviti_errore'] ?? 'Token non valido o scaduto';
}

require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
    <div class="container narrow">
        <h1><?= e($t['newsletter']['disiscriviti'] ?? 'Disiscriviti') ?></h1>
    </div>
</section>

<section class="section">
    <div class="container narrow center">
        <?php if ($messaggio): ?>
            <div class="alert alert-success"><?= e($messaggio) ?></div>
        <?php endif; ?>
        <?php if ($errore): ?>
            <div class="alert alert-error"><?= e($errore) ?></div>
        <?php endif; ?>
        <p><a class="btn btn-primary" href="<?= e(url($lang, 'inicio')) ?>"><?= e($t['newsletter']['indietro'] ?? 'Torna al sito') ?></a></p>
    </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
