<?php
/**
 * Attiva/Disattiva iscritto newsletter
 */
require_once __DIR__ . '/../../inc/auth.php';
admin_required();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url($lang, 'iscritti'));
    exit;
}

csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
$attivo = (int)($_POST['attivo'] ?? 0);

if ($id) {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE newsletter_iscritti SET attivo = ? WHERE id = ?");
    $stmt->execute([$attivo, $id]);
}

header('Location: ' . url($lang, 'iscritti'));
exit;
