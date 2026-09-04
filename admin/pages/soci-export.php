<?php
/** Esporta i soci in CSV (apribile con Excel) */
$filtro = in_array($_GET['stato'] ?? '', ['attivo', 'in_attesa', 'dimesso'], true) ? $_GET['stato'] : '';
if ($filtro !== '') {
    $stmt = db()->prepare('SELECT * FROM soci WHERE stato = ? ORDER BY creato_il DESC');
    $stmt->execute([$filtro]);
} else {
    $stmt = db()->query('SELECT * FROM soci ORDER BY creato_il DESC');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=asodomi-soci-' . date('Y-m-d') . '.csv');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM per Excel
fputcsv($out, ['Nome', 'Email', 'Telefono', 'Indirizzo', 'Comune', 'Stato', 'Iscritto il', 'Note'], ';');
foreach ($stmt->fetchAll() as $s) {
    fputcsv($out, [
        $s['nome'], $s['email'], $s['telefono'], $s['indirizzo'],
        $s['comune'], $s['stato'],
        date('d/m/Y', strtotime($s['creato_il'])),
        $s['note'],
    ], ';');
}
fclose($out);
exit;
