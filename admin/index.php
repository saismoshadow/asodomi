<?php
/**
 * ASODOMI – Pannello di amministrazione (in italiano).
 * Accesso: /admin  (le pagine interne usano ?route=...)
 */
header('Cache-Control: no-cache, must-revalidate');
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';


$route = preg_replace('/[^a-z0-9-_.]/', '', strtolower($_GET['route'] ?? 'dashboard'));

// ── Logout e login non richiedono autenticazione ─────────────────────
if ($route === 'logout') {
    logout();
    header('Location: ' . admin_url('login'));
    exit;
}

if ($route === 'login') {
    $errore = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verifica();
        if (login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            header('Location: ' . admin_url('dashboard'));
            exit;
        }
        $errore = 'Email o password non corretti.';
    }
    require __DIR__ . '/pages/login.php';
    exit;
}

// ── Tutte le altre rotte richiedono l'accesso ────────────────────────
$utente = utente_corrente();
if (!$utente) {
    header('Location: ' . admin_url('login'));
    exit;
}

// Soci e utenti: solo amministratori (i redattori gestiscono solo il blog)
$solo_admin = [
    'soci', 'socio', 'salva-socio', 'elimina-socio', 'cambia-stato-socio', 'soci-export',
    'elimina-documento-socio',
    'utenti', 'salva-utente', 'elimina-utente',
    'newsletter', 'newsletter_iscritti', 'newsletter_nuova', 'newsletter_elimina',
    'newsletter_export', 'newsletter_iscritto_delete',
];
// Articoli, notizie ed eventi sono gestibili sia da admin che da redattore
// (le rotte eventi/notizie NON sono in $solo_admin)
if ($utente['ruolo'] !== 'admin' && in_array($route, $solo_admin, true)) {
    http_response_code(403);
    exit('Operazione riservata agli amministratori.');
}

$pagine_admin = [
    'dashboard',
    'articoli', 'articolo', 'salva-articolo', 'elimina-articolo',
    'eventi', 'evento', 'salva-evento', 'elimina-evento', 'stato-evento',
    'notizie', 'notizia', 'salva-notizia', 'elimina-notizia',
    'documenti', 'salva-documento', 'elimina-documento',
    'soci', 'socio', 'salva-socio', 'elimina-socio', 'cambia-stato-socio', 'soci-export',
    'elimina-documento-socio',
    'utenti', 'salva-utente', 'elimina-utente',
    'newsletter', 'newsletter_iscritti', 'newsletter_nuova', 'newsletter_elimina',
    'newsletter_export', 'newsletter_iscritto_delete',
];
if (!in_array($route, $pagine_admin, true)) {
    $route = 'dashboard';
}

// Azioni POST ed export: girano SENZA layout (fanno redirect o inviano file)
$senza_layout = [
    'salva-articolo', 'elimina-articolo',
    'salva-evento', 'elimina-evento', 'stato-evento',
    'salva-notizia', 'elimina-notizia',
    'salva-documento', 'elimina-documento',
    'salva-socio', 'elimina-socio', 'cambia-stato-socio', 'soci-export',
    'elimina-documento-socio',
    'salva-utente', 'elimina-utente',
    'newsletter_elimina', 'newsletter_export', 'newsletter_iscritto_delete',
];
if (in_array($route, $senza_layout, true)) {
    require __DIR__ . '/pages/' . $route . '.php';
    exit;
}

require __DIR__ . '/layout.php';
