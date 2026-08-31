<?php
/** Salva un evento (nuovo o esistente) con upload media facoltativi */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('articoli') . '&tipo=eventi');
    exit;
}
csrf_verifica();
require_once __DIR__ . '/../upload-media.inc.php';

$id         = (int)($_POST['id'] ?? 0);
$titolo     = trim(strip_tags((string)($_POST['titolo'] ?? '')));
$descrizione= trim(strip_tags((string)($_POST['descrizione'] ?? '')));
$data       = trim((string)($_POST['data'] ?? ''));
$luogo      = trim(strip_tags((string)($_POST['luogo'] ?? '')));
$modalita   = in_array($_POST['modalita'] ?? '', ['presenza', 'online', 'mista'], true) ? $_POST['modalita'] : 'presenza';
$video_url  = trim((string)($_POST['video_url'] ?? ''));
$stato      = ($_POST['stato'] ?? '') === 'pubblicato' ? 'pubblicato' : 'bozza';

if ($titolo === '' || $data === '') {
    header('Location: ' . admin_url('evento') . ($id > 0 ? '&id=' . $id : ''));
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    header('Location: ' . admin_url('evento') . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

// Video URL: solo se è un embed valido
$video_ok = video_embed($video_url);
$video_db = $video_ok ? $video_url : null;

// Gestione upload media (facoltativi)
$errore_file = '';
$immagine    = asodomi_handle_media_upload('immagine', $errore_file);
$videofile   = asodomi_handle_media_upload('videofile', $errore_file);

if ($errore_file !== '') {
    header('Location: ' . admin_url('evento') . ($id > 0 ? '&id=' . $id : '') . '&err=' . rawurlencode($errore_file));
    exit;
}

if ($id > 0) {
    $ex = db()->prepare('SELECT id FROM eventi WHERE id = ?');
    $ex->execute([$id]);
    if (!$ex->fetch()) {
        header('Location: ' . admin_url('articoli') . '&tipo=eventi');
        exit;
    }
    $campo_imm = $immagine !== null ? 'immagine = ?' : 'immagine = immagine';
    $campo_vid = $videofile !== null ? 'videofile = ?' : 'videofile = videofile';
    $aggiorna = "UPDATE eventi SET titolo = ?, descrizione = ?, data = ?, luogo = ?, modalita = ?, video_url = ?, stato = ?, $campo_imm, $campo_vid WHERE id = ?";
    $param = [$titolo, $descrizione !== '' ? $descrizione : null, $data, $luogo !== '' ? $luogo : null, $modalita, $video_db, $stato];
    if ($immagine !== null) { $param[] = $immagine; }
    if ($videofile !== null) { $param[] = $videofile; }
    $param[] = $id;
    db()->prepare($aggiorna)->execute($param);
} else {
    db()->prepare(
        'INSERT INTO eventi (titolo, descrizione, data, luogo, modalita, videofile, video_url, immagine, stato)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $titolo,
        $descrizione !== '' ? $descrizione : null,
        $data,
        $luogo !== '' ? $luogo : null,
        $modalita,
        $videofile,
        $video_db,
        $immagine,
        $stato,
    ]);
}

header('Location: ' . admin_url('articoli') . '&tipo=eventi');
exit;
