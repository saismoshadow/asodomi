<?php
/**
 * ASODOMI – Esportazione iscritti newsletter in CSV (senza layout).
 * Solo admin (garantito dal routing). Non espone campi sensibili vuoti.
 */
$pdo = db();
$stato = $_GET['stato'] ?? '';
$q     = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 120));

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(email LIKE ? OR nome LIKE ? OR cognome LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if (in_array($stato, ['attivo','pending','unsubscribed'], true)) {
    $where[] = 'status = ?';
    $params[] = $stato;
}
$sql = 'SELECT email, nome, cognome, lingua, status, creato_il, confirmed_at, unsubscribed_at
        FROM newsletter_iscritti'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY creato_il DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Output CSV UTF-8 con BOM (per Excel)
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="newsletter_iscritti_' . date('Ymd_His') . '.csv"');
echo "\xEF\xBB\xBF"; // BOM UTF-8
$fh = fopen('php://output', 'w');
fputcsv($fh, ['email', 'nome', 'cognome', 'lingua', 'stato', 'iscritto_il', 'confermato_il', 'disiscritto_il']);
foreach ($rows as $r) {
    $r['stato'] = $r['status'] === 'confirmed' ? 'attivo' : $r['status'];
    fputcsv($fh, [
        $r['email'], $r['nome'], $r['cognome'], strtoupper($r['lingua']),
        $r['stato'], $r['creato_il'], $r['confirmed_at'], $r['unsubscribed_at'],
    ]);
}
fclose($fh);
exit;