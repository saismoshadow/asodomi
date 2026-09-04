<?php
// Test admin newsletter page with authentication simulation
$_SESSION['utente_id'] = 1;
$_SESSION['utente_email'] = 'asodomich@gmail.com';
$_SESSION['utente_nome'] = 'Amministratore';
$_SESSION['utente_ruolo'] = 'admin';
$_GET['route'] = 'newsletter';

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/newsletter.php';

$route = 'newsletter';
$file = __DIR__ . '/admin/pages/' . str_replace(['..', '\\'], '', $route) . '.php';

if (is_file($file)) {
    ob_start();
    require $file;
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . "\n";
    echo "First 500 chars:\n" . substr($output, 0, 500) . "\n";
} else {
    echo "File not found\n";
}
