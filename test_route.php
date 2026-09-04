<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$_SESSION['utente_id'] = 1;
$_SESSION['utente_email'] = 'asodomich@gmail.com';
$_SESSION['utente_nome'] = 'Amministratore';
$_SESSION['utente_ruolo'] = 'admin';

$route = 'newsletter';

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

echo "Route: $route\n";
echo "In pagine_admin: " . (in_array($route, ['dashboard','articoli','articolo','salva-articolo','elimina-articolo','eventi','evento','salva-evento','elimina-evento','stato-evento','notizie','notizia','salva-notizia','elimina-notizia','documenti','salva-documento','elimina-documento','soci','socio','salva-socio','elimina-socio','cambia-stato-socio','soci-export','elimina-documento-socio','utenti','salva-utente','elimina-utente','newsletter','newsletter_iscritti','newsletter_nuova','newsletter_elimina','newsletter_export','newsletter_iscritto_delete'], true) ? 'YES' : 'NO') . "\n";

$file = __DIR__ . '/admin/pages/' . $route . '.php';
echo "File exists: " . (is_file($file) ? 'YES' : 'NO') . "\n";

if (is_file($file)) {
    ob_start();
    require $file;
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . "\n";
    echo "First 300 chars:\n" . substr($output, 0, 300) . "\n";
}
