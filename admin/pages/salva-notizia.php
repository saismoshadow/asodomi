<?php
/** Salva una notizia (nuova o esistente) con fonte e media facoltativi */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('articoli') . '&tipo=notizie');
    exit;
}
csrf_verifica();
require_once __DIR__ . '/../upload-media.inc.php';

$id        = (int)($_POST['id'] ?? 0);
$titolo    = trim(strip_tags((string)($_POST['titolo'] ?? '')));
$testo     = trim(strip_tags((string)($_POST['testo'] ?? '')));
$fonte_url = trim((string)($_POST['fonte_url'] ?? ''));
$video_url = trim((string)($_POST['video_url'] ?? ''));
$stato     = ($_POST['stato'] ?? '') === 'pubblicato' ? 'pubblicato' : 'bozza';

if ($titolo === '' || $testo === '') {
    header('Location: ' . admin_url('notizia') . ($id > 0 ? '&id=' . $id : ''));
    exit;
}

// Fonte: validata come URL solo se presente
$fonte_db = null;
if ($fonte_url !== '') {
    if (filter_var($fonte_url, FILTER_VALIDATE_URL) === false) {
        header('Location: ' . admin_url('notizia') . ($id > 0 ? '&id=' . $id : '') . '&err=' . rawurlencode('Fonte non valida: inserisci un link completo (https://...).'));
        exit;
    }
    $fonte_db = $fonte_url;
}

// Video URL: solo se è un embed valido
$video_ok = video_embed($video_url);
$video_db = $video_ok ? $video_url : null;

// Gestione upload media (facoltativi)
$errore_file = '';
$immagine    = asodomi_handle_media_upload('immagine', $errore_file);

if ($errore_file !== '') {
    header('Location: ' . admin_url('notizia') . ($id > 0 ? '&id=' . $id : '') . '&err=' . rawurlencode($errore_file));
    exit;
}

if ($id > 0) {
    $ex = db()->prepare('SELECT id FROM notizie WHERE id = ?');
    $ex->execute([$id]);
    if (!$ex->fetch()) {
        header('Location: ' . admin_url('articoli') . '&tipo=notizie');
        exit;
    }
    $campo_imm = $immagine !== null ? 'immagine = ?' : 'immagine = immagine';
    $aggiorna = "UPDATE notizie SET titolo = ?, testo = ?, fonte_url = ?, video_url = ?, stato = ?, $campo_imm WHERE id = ?";
    $param = [$titolo, $testo, $fonte_db, $video_db, $stato];
    if ($immagine !== null) { $param[] = $immagine; }
    $param[] = $id;
    db()->prepare($aggiorna)->execute($param);
} else {
    db()->prepare(
        'INSERT INTO notizie (titolo, testo, fonte_url, immagine, video_url, stato)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $titolo,
        $testo,
        $fonte_db,
        $immagine,
        $video_db,
        $stato,
    ]);
}

header('Location: ' . admin_url('articoli') . '&tipo=notizie');
exit;
