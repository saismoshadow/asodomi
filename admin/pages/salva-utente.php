<?php
/** Crea un nuovo utente – solo admin */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('utenti'));
    exit;
}
csrf_verifica();

if ($utente['ruolo'] !== 'admin') {
    http_response_code(403);
    exit('Operazione riservata agli amministratori.');
}

$nome     = trim(strip_tags((string)($_POST['nome'] ?? '')));
$email    = strtolower(trim((string)($_POST['email'] ?? '')));
$ruolo    = ($_POST['ruolo'] ?? '') === 'admin' ? 'admin' : 'redattore';
$password = (string)($_POST['password'] ?? '');

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    header('Location: ' . admin_url('utenti'));
    exit;
}

$stmt = db()->prepare('SELECT id FROM utenti WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: ' . admin_url('utenti'));
    exit;
}

db()->prepare('INSERT INTO utenti (email, password_hash, nome, ruolo) VALUES (?, ?, ?, ?)')
    ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $nome, $ruolo]);

header('Location: ' . admin_url('utenti'));
exit;
