<?php
/** Elimina una notizia */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('articoli') . '&tipo=notizie');
    exit;
}
csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    db()->prepare('DELETE FROM notizie WHERE id = ?')->execute([$id]);
}
header('Location: ' . admin_url('articoli') . '&tipo=notizie');
exit;
