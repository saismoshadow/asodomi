<?php
/**
 * ASODOMI – Iscrizione newsletter (endpoint pubblico, double opt-in).
 * Salva lo subscriber come 'pending', genera token sicuro, invia email di
 * conferma con link unico di validazione. Nessun indirizzo diventa attivo
 * senza aver cliccato il link di conferma.
 */
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/newsletter.php';

$lang = isset($_POST['lang']) && in_array($_POST['lang'], $GLOBALS['ASODOMI_LANGS'], true)
    ? $_POST['lang'] : (isset($_GET['lang']) && in_array($_GET['lang'], $GLOBALS['ASODOMI_LANGS'], true) ? $_GET['lang'] : DEFAULT_LANG);
$t = asodomi_load_lang($lang);
$nwl = $t['newsletter'] ?? [];

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url($lang, 'area-soci'));
    exit;
}

// Anti-spam: campo trappola nascosto
if (!empty($_POST['website'])) {
    header('Location: ' . url($lang, 'area-soci'));
    exit;
}

$nome    = trim(mb_substr((string)($_POST['nome'] ?? ''), 0, 120));
$cognome = trim(mb_substr((string)($_POST['cognome'] ?? ''), 0, 120));
$email   = nl_email_valida((string)($_POST['email'] ?? ''));
$consenso = (($_POST['consenso'] ?? '') === '1' || ($_POST['consenso'] ?? '') === 'on');
$canale  = in_array(($_POST['canale'] ?? ''), ['area_soci', 'modulo', 'admin'], true) ? $_POST['canale'] : 'modulo';

// Rate-limit anti-spam di iscrizione
if (!nl_rate_permesso($ip, 5, 60)) {
    nl_rate_registra($ip);
    header('Location: ' . url($lang, 'area-soci') . '?nl_err=rate');
    exit;
}

if ($nome === '' || $cognome === '' || $email === '' || !$consenso) {
    header('Location: ' . url($lang, 'area-soci') . '?nl_err=campi');
    exit;
}

$esito = nl_iscrivi($email, $nome, $cognome, $lang, $canale, $ip);

if (!$esito['ok'] && $esito['status'] === 'gia_attivo') {
    header('Location: ' . url($lang, 'area-soci') . '?nl_err=gia_iscritto');
    exit;
}
if (!$esito['ok']) {
    header('Location: ' . url($lang, 'area-soci') . '?nl_err=campi');
    exit;
}

// Invia email di conferma (nuovo token dal DB)
$pdo = db();
$stmt = $pdo->prepare('SELECT confirmation_token, nome, cognome FROM newsletter_iscritti WHERE id = ?');
$stmt->execute([$esito['id']]);
$riga = $stmt->fetch();
$token = $riga['confirmation_token'] ?? '';

if ($token !== '') {
    $linkConferma = nl_url_pubblica('newsletter_conferma', ['token' => $token], $lang);
    $oggetto = $nwl['conferma_oggetto'] ?? 'Conferma la tua iscrizione alla newsletter ASODOMI';
    $testoInt = ($riga['nome'] ?? '') . ' ' . ($riga['cognome'] ?? '');

    $corpo = '<p>' . nl_e_attr($nwl['conferma_ciao_txt'] ?? 'Ciao') . ' ' . nl_e_attr(trim($testoInt)) . ',</p>'
        . '<p>' . nl_e_attr($nwl['conferma_intro_txt'] ?? 'Per completare la tua iscrizione alla newsletter ASODOMI, conferma il tuo indirizzo email cliccando sul pulsante qui sotto.') . '</p>'
        . '<p style="text-align:center;margin:28px 0;">'
        . '<a href="' . nl_e_attr($linkConferma) . '" style="background-color:#0b3d91;color:#ffffff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:bold;">'
        . nl_e_attr($nwl['conferma_btn_txt'] ?? 'Conferma iscrizione') . '</a></p>'
        . '<p style="font-size:13px;color:#6a7180;">' . nl_e_attr($nwl['conferma_ignora_txt'] ?? 'Se non intendi iscriverti, ignora questa email. Il link scade dopo 72 ore.') . '</p>';

    nl_invia($email, $oggetto, nl_body_html($corpo, url($lang, 'area-soci'), $lang), NL_SENDER_NAME, NL_SENDER_EMAIL, NL_REPLY_TO);
}

nl_rate_registra($ip);
header('Location: ' . url($lang, 'area-soci') . '?nl_ok=1');
exit;