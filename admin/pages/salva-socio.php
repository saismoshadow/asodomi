<?php
/** Salva le modifiche a un socio */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('soci'));
    exit;
}
csrf_verifica();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . admin_url('soci'));
    exit;
}

$nome      = trim(strip_tags((string)($_POST['nome'] ?? '')));
$email     = strtolower(trim((string)($_POST['email'] ?? '')));
$telefono  = trim(strip_tags((string)($_POST['telefono'] ?? '')));
$indirizzo = trim(strip_tags((string)($_POST['indirizzo'] ?? '')));
$comune    = trim(strip_tags((string)($_POST['comune'] ?? '')));
$stato     = in_array($_POST['stato'] ?? '', ['attivo', 'in_attesa', 'dimesso'], true) ? $_POST['stato'] : 'in_attesa';
$note      = trim(strip_tags((string)($_POST['note'] ?? '')));

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . admin_url('socio') . '&id=' . $id);
    exit;
}

db()->prepare(
    'UPDATE soci SET nome = ?, email = ?, telefono = ?, indirizzo = ?, comune = ?, stato = ?, note = ? WHERE id = ?'
)->execute([$nome, $email, $telefono, $indirizzo, $comune, $stato, $note, $id]);

// Nuova password per l'area soci (se compilata)
$password = (string)($_POST['password'] ?? '');
if ($password !== '') {
    if (strlen($password) >= 8) {
        db()->prepare('UPDATE soci SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    } else {
        header('Location: ' . admin_url('socio') . '&id=' . $id . '&errpass=1');
        exit;
    }
}

header('Location: ' . admin_url('socio') . '&id=' . $id);
exit;
