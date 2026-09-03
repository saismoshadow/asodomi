<?php
/** Elimina un documento caricato da un socio (solo admin) */
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

$stmt = db()->prepare('SELECT socio_id, nome_file FROM documenti_soci WHERE id = ?');
$stmt->execute([$id]);
$doc = $stmt->fetch();

if ($doc) {
    // Rimuovi il file dal disco (solo se dentro uploads)
    $base = realpath(__DIR__ . '/../../uploads/');
    $file = realpath(__DIR__ . '/../../uploads/' . $doc['nome_file']);
    if ($base !== false && $file !== false && strpos($file, $base) === 0 && is_file($file)) {
        @unlink($file);
    }
    db()->prepare('DELETE FROM documenti_soci WHERE id = ?')->execute([$id]);
}

$ritorno = $doc ? admin_url('socio') . '&id=' . (int)$doc['socio_id'] : admin_url('soci');
header('Location: ' . $ritorno . '&docdel=1');
exit;
