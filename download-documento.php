<?php
/**
 * ASODOMI – Download di un documento caricato da un socio.
 * Accessibile SOLO agli utenti admin autenticati (pannello /admin).
 * Il download è forzato (attachement) e il file resta nella cartella uploads.
 */
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';

// Solo utenti autenticati del pannello (admin o redattore → leggono i documenti soci)
$utente = utente_corrente();
if (!$utente) {
    http_response_code(403);
    exit('Accesso negato.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    exit('Documento non trovato.');
}

$stmt = db()->prepare('SELECT ds.*, s.nome AS socio_nome FROM documenti_soci ds JOIN soci s ON s.id = ds.socio_id WHERE ds.id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc) {
    http_response_code(404);
    exit('Documento non trovato.');
}

$percorso = __DIR__ . '/uploads/' . $doc['nome_file'];

// Sicurezza: il file deve stare davvero in /uploads e non essere un percorso traversal
$base = realpath(__DIR__ . '/uploads/');
$file = realpath($percorso);
if ($file === false || $base === false || strpos($file, $base) !== 0 || !is_dir($base)) {
    http_response_code(404);
    exit('File non trovato.');
}

if (!is_file($file) || !is_readable($file)) {
    http_response_code(404);
    exit('File non trovato.');
}

header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . rawurlencode($doc['nome_originale']) . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-store, no-cache, must-revalidate');
readfile($file);
exit;
