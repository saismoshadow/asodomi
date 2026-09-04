<?php
/** Elimina un evento */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('articoli') . '&tipo=eventi');
    exit;
}
csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    db()->prepare('DELETE FROM eventi WHERE id = ?')->execute([$id]);
}
header('Location: ' . admin_url('articoli') . '&tipo=eventi');
exit;
