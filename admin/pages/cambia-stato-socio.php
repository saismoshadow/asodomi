<?php
/** Cambia lo stato di un socio (attivo / in_attesa / dimesso) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('soci'));
    exit;
}
csrf_verifica();

$id    = (int)($_POST['id'] ?? 0);
$stato = in_array($_POST['stato'] ?? '', ['attivo', 'in_attesa', 'dimesso'], true) ? $_POST['stato'] : '';

if ($id > 0 && $stato !== '') {
    db()->prepare('UPDATE soci SET stato = ? WHERE id = ?')->execute([$stato, $id]);
}

$filtro = in_array($_POST['filtro'] ?? '', ['attivo', 'in_attesa', 'dimesso'], true) ? $_POST['filtro'] : '';
header('Location: ' . admin_url('soci') . ($filtro !== '' ? '&stato=' . $filtro : ''));
exit;
