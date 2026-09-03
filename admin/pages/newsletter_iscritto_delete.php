<?php
/**
 * ASODOMI – Elimina un iscritto newsletter (POST, senza layout).
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('newsletter_iscritti'));
    exit;
}
csrf_verifica();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM newsletter_consensi WHERE subscriber_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM newsletter_campaign_logs WHERE subscriber_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM newsletter_iscritti WHERE id = ?')->execute([$id]);
}
header('Location: ' . admin_url('newsletter_iscritti'));
exit;