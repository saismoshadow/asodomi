<?php
/**
 * Iscrizione newsletter (public endpoint) - CSRF disabled for public form
 */
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/config.php';

$lang = $_GET['lang'] ?? DEFAULT_LANG;
if (!in_array($lang, $GLOBALS['ASODOMI_LANGS'], true)) {
    $lang = DEFAULT_LANG;
}
$t = asodomi_load_lang($lang);

$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF not required for public newsletter form
    $email = trim(strtolower((string)($_POST['email'] ?? '')));
    $nome = trim((string)($_POST['nome'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errore = $t['newsletter']['email_placeholder'] ?? 'Email non valida';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, attivo FROM newsletter_iscritti WHERE email = ?");
        $stmt->execute([$email]);
        $esistente = $stmt->fetch();

        if ($esistente) {
            if ($esistente['attivo']) {
                $errore = $t['newsletter']['già_iscritto'] ?? 'Sei già iscritto';
            } else {
                // Reactivate
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE newsletter_iscritti SET attivo = 1, nome = ?, lingua = ?, token_unsubscribe = ? WHERE email = ?");
                $stmt->execute([$nome, $lang, $token, $email]);
                $messaggio = $t['newsletter']['iscrizione_ok'] ?? 'Iscrizione confermata!';
            }
        } else {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO newsletter_iscritti (email, nome, lingua, token_unsubscribe) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $nome, $lang, $token]);
            $messaggio = $t['newsletter']['iscrizione_ok'] ?? 'Iscrizione confermata!';
        }
    }
}

// Redirect back with message
$redirect = url($lang, 'inicio');
if ($messaggio) $redirect .= '?nl_ok=1';
if ($errore) $redirect .= '?nl_err=1';
header('Location: ' . $redirect);
exit;
