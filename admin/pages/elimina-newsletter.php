<?php
/**
 * Elimina campagna newsletter
 */
require_once __DIR__ . '/../../inc/auth.php';
admin_required();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url($lang, 'newsletter'));
    exit;
}

csrf_verifica();
$id = (int)($_POST['id'] ?? 0);

if ($id) {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM newsletter_campagne WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: ' . url($lang, 'newsletter'));
exit;
