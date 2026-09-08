<?php
/** Elimina un utente – solo admin */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('utenti'));
    exit;
}
csrf_verifica();

if ($utente['ruolo'] !== 'admin') {
    http_response_code(403);
    exit('Operazione riservata agli amministratori.');
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0 && $id !== (int)$utente['id']) { // non può eliminare se stesso
    db()->prepare('DELETE FROM utenti WHERE id = ?')->execute([$id]);
}
header('Location: ' . admin_url('utenti'));
exit;
