<?php
/** Archivia o riattiva un evento (toggle) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('articoli') . '&tipo=eventi');
    exit;
}
csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
$archiviato = (string)($_POST['archiviato'] ?? '') === '1' ? 1 : 0;
if ($id > 0) {
    db()->prepare('UPDATE eventi SET archiviato = ? WHERE id = ?')->execute([$archiviato, $id]);
}
$q = $archiviato ? '&ev=archivio' : '';
header('Location: ' . admin_url('articoli') . '&tipo=eventi' . $q);
exit;
