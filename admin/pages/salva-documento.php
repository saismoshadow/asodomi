<?php
/** Salva un nuovo documento per l'area soci */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('documenti'));
    exit;
}
csrf_verifica();

$titolo      = trim(strip_tags((string)($_POST['titolo'] ?? '')));
$descrizione = trim(strip_tags((string)($_POST['descrizione'] ?? '')));
$url         = trim((string)($_POST['url'] ?? ''));

// Solo http/https
if ($titolo === '' || !preg_match('#^https?://#i', $url) || strlen($url) > 400) {
    header('Location: ' . admin_url('documenti'));
    exit;
}

db()->prepare('INSERT INTO documenti (titolo, descrizione, url) VALUES (?, ?, ?)')
    ->execute([$titolo, $descrizione !== '' ? $descrizione : null, $url]);

header('Location: ' . admin_url('documenti'));
exit;
