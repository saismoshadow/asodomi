<?php
/**
 * Form nuova password dopo reset
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/config.php';

$lang = $_GET['lang'] ?? DEFAULT_LANG;
if (!in_array($lang, $GLOBALS['ASODOMI_LANGS'], true)) {
    $lang = DEFAULT_LANG;
}
$page = 'reimposta_password';
$token = $_GET['token'] ?? '';
$t = asodomi_load_lang($lang);

$messaggio = '';
$errore = '';
$showForm = true;

if ($token) {
    $validation = validate_password_reset_token($token);
    if (!$validation['valid']) {
        $errore = $t['area_soci'][$validation['error']] ?? 'Link non valido o scaduto.';
        $showForm = false;
    }
} else {
    $errore = $t['area_soci']['token_invalido'] ?? 'Link non valido.';
    $showForm = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    csrf_verifica();
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
    
    if ($password !== $passwordConfirm) {
        $errore = $t['area_soci']['conferma_password'] ?? 'Le password non coincidono.';
    } elseif (strlen($password) < 8) {
        $errore = $t['area_soci']['password_corta'] ?? 'La password deve essere di almeno 8 caratteri.';
    } else {
        $result = complete_password_reset($token, $password);
        if ($result['success']) {
            $messaggio = $t['area_soci']['password_modificata'] ?? 'Password modificata con successo. Puoi ora accedere.';
            $showForm = false;
        } else {
            $errore = $t['area_soci'][$result['error']] ?? 'Errore durante il cambio password.';
        }
    }
}

require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
    <div class="container narrow">
        <h1><?= e($t['area_soci']['reimposta_password'] ?? 'Nuova password') ?></h1>
        <p class="lead"><?= e($t['area_soci']['reimposta_password_title'] ?? 'Imposta una nuova password per il tuo account.') ?></p>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <?php if ($messaggio): ?>
            <div class="alert alert-success"><?= e($messaggio) ?></div>
            <p class="center" style="margin-top:1.5rem">
                <a class="btn btn-primary" href="<?= e(url($lang, 'area-soci')) ?>"><?= e($t['area_soci']['accedi'] ?? 'Vai al login') ?></a>
            </p>
        <?php endif; ?>
        
        <?php if ($errore && $showForm): ?>
            <div class="alert alert-error"><?= e($errore) ?></div>
        <?php endif; ?>
        <?php if ($errore && !$showForm): ?>
            <div class="alert alert-error"><?= e($errore) ?></div>
            <p class="center" style="margin-top:1.5rem">
                <a class="btn btn-primary" href="<?= e(url($lang, 'password_dimenticata')) ?>"><?= e($t['area_soci']['password_dimenticata_invia'] ?? 'Richiedi nuovo link') ?></a>
                <a class="btn btn-ghost" href="<?= e(url($lang, 'area-soci')) ?>"><?= e($t['area_soci']['accedi'] ?? 'Torna al login') ?></a>
            </p>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <form class="form" method="post" action="<?= e(url($lang, 'reimposta_password') . '?token=' . e($_GET['token'] ?? '')) ?>">
                <?= csrf_campo() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                
                <label><?= e($t['area_soci']['nueva_password'] ?? 'Nuova password') ?>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="<?= e($t['area_soci']['nueva_password'] ?? 'Nuova password') ?>">
                </label>
                <label><?= e($t['area_soci']['conferma_password'] ?? 'Conferma nuova password') ?>
                    <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="<?= e($t['area_soci']['conferma_password'] ?? 'Conferma nuova password') ?>">
                </label>
                <p class="muted small">Minimo 8 caratteri.</p>
                
                <button type="submit" class="btn btn-primary btn-block"><?= e($t['area_soci']['salva'] ?? 'Salva') ?></button>
            </form>
            
            <p class="center muted" style="margin-top:1.5rem">
                <a href="<?= e(url($lang, 'area-soci')) ?>"><?= e($t['area_soci']['accedi'] ?? 'Torna al login') ?></a>
            </p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
