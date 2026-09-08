<?php
/** Elimina un socio */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('soci'));
    exit;
}
csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    db()->prepare('DELETE FROM soci WHERE id = ?')->execute([$id]);
}
header('Location: ' . admin_url('soci'));
exit;
