<?php
/**
 * ASODOMI – Elimina una campagna newsletter (POST, senza layout).
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('newsletter'));
    exit;
}
csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM newsletter_campaign_logs WHERE campaign_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM newsletter_campagne WHERE id = ?')->execute([$id]);
}
header('Location: ' . admin_url('newsletter'));
exit;