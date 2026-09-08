<?php
/**
 * Richiesta reset password - form per inserire email
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/config.php';

$lang = $_GET['lang'] ?? DEFAULT_LANG;
if (!in_array($lang, $GLOBALS['ASODOMI_LANGS'], true)) {
    $lang = DEFAULT_LANG;
}
$page = 'password_dimenticata';
$t = asodomi_load_lang($lang);

$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifica();
    $email = trim(strtolower((string)($_POST['email'] ?? '')));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errore = $t['area_soci']['email'] ?? 'Email non valida';
    } else {
        $result = create_password_reset_request($email, $lang);
        
        if ($result['success']) {
            $messaggio = $result['generic_message'] ?? $t['area_soci']['richiesta_inviata'] ?? 'Richiesta inviata.';
            
            // Invia email con token se socio esiste
            if (isset($result['token']) && isset($result['socio_id'])) {
                $resetUrl = (defined('SITE_URL') && SITE_URL ? rtrim(SITE_URL, '/') : 
                    ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(base_url(), '/'))
                    . url($lang, 'reimposta_password') . '?token=' . $result['token'];
                
                $subject = 'Reimposta la tua password - ASODOMI';
                $body = "Ciao,\n\n" .
                    "Hai richiesto di reimpostare la password per il tuo account ASODOMI.\n\n" .
                    "Clicca il link seguente per impostare una nuova password:\n" .
                    $resetUrl . "\n\n" .
                    "Il link scadrà tra 60 minuti.\n\n" .
                    "Se non hai richiesto tu questo cambio, ignora questa email.\n\n" .
                    "— ASODOMI";
                
                $headers = "From: ASODOMI <" . CONTACT_EMAIL . ">\r\n";
                $headers .= "Reply-To: " . CONTACT_EMAIL . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
            }
        } else {
            $errore = $result['error'] === 'rate_limit_reset' 
                ? ($t['area_soci']['rate_limit_messaggio'] ?? 'Troppe richieste. Riprova tra un\'ora.')
                : ($t['forms']['required'] ?? 'Errore durante la richiesta');
        }
    }
}

require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
    <div class="container narrow">
        <h1><?= e($t['area_soci']['password_dimenticata_title'] ?? 'Recupera password') ?></h1>
        <p class="lead"><?= e($t['area_soci']['password_dimenticata_test'] ?? 'Inserisci la tua email per ricevere le istruzioni.') ?></p>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <?php if ($messaggio): ?>
            <div class="alert alert-success"><?= e($messaggio) ?></div>
        <?php endif; ?>
        <?php if ($errore): ?>
            <div class="alert alert-error"><?= e($errore) ?></div>
        <?php endif; ?>

        <form class="form" method="post" action="<?= e(url($lang, 'password_dimenticata')) ?>">
            <?= csrf_campo() ?>
            <label><?= e($t['area_soci']['email'] ?? 'Email') ?>
                <input type="email" name="email" required maxlength="160" autocomplete="email" placeholder="<?= e($t['newsletter']['email_placeholder'] ?? 'La tua email') ?>">
            </label>
            <button type="submit" class="btn btn-primary btn-block"><?= e($t['area_soci']['password_dimenticata_invia'] ?? 'Invia link') ?></button>
        </form>

        <p class="center muted" style="margin-top:1.5rem">
            <a href="<?= e(url($lang, 'area-soci')) ?>"><?= e($t['area_soci']['accedi'] ?? 'Torna al login') ?></a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
