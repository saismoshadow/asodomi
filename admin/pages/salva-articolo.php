<?php
/** Salva un articolo (nuovo o esistente) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('articoli'));
    exit;
}
csrf_verifica();

$id      = (int)($_POST['id'] ?? 0);
$titolo  = trim((string)($_POST['titolo'] ?? ''));
$contenuto = trim((string)($_POST['contenuto'] ?? ''));
$video   = trim((string)($_POST['video_url'] ?? ''));
$stato   = ($_POST['stato'] ?? '') === 'pubblicato' ? 'pubblicato' : 'bozza';
$visibilita = ($_POST['visibilita'] ?? '') === 'riservato' ? 'riservato' : 'pubblico';

if ($titolo === '') {
    header('Location: ' . admin_url('articoli'));
    exit;
}

// Testo semplice → paragrafi HTML
if ($contenuto !== '' && !preg_match('/<[a-z][^>]*>/i', $contenuto)) {
    $paragrafi = preg_split('/\n{2,}/', $contenuto) ?: [$contenuto];
    $contenuto = '<p>' . implode('</p><p>', array_map(
        fn($p) => str_replace("\n", '<br>', trim($p)),
        array_filter($paragrafi, 'strlen')
    )) . '</p>';
}
$contenuto = sanitizza_html($contenuto);
$video_ok  = video_embed($video);
$video_db  = $video_ok ? $video : null;

if ($id > 0) {
    $stmt = db()->prepare('SELECT slug, stato FROM articoli WHERE id = ?');
    $stmt->execute([$id]);
    $esistente = $stmt->fetch();
    if (!$esistente) {
        header('Location: ' . admin_url('articoli'));
        exit;
    }
    $pubblicato_il = ($stato === 'pubblicato')
        ? 'COALESCE(pubblicato_il, NOW())'
        : 'NULL';
    db()->prepare(
        "UPDATE articoli
         SET titolo = ?, contenuto = ?, video_url = ?, stato = ?, visibilita = ?,
             pubblicato_il = IF(? = 'pubblicato', COALESCE(pubblicato_il, NOW()), NULL)
         WHERE id = ?"
    )->execute([$titolo, $contenuto, $video_db, $stato, $visibilita, $stato, $id]);
} else {
    $slug = crea_slug($titolo);
    db()->prepare(
        'INSERT INTO articoli (slug, titolo, contenuto, video_url, stato, visibilita, autore_id, pubblicato_il)
         VALUES (?, ?, ?, ?, ?, ?, ?, IF(? = "pubblicato", NOW(), NULL))'
    )->execute([$slug, $titolo, $contenuto, $video_db, $stato, $visibilita, $utente['id'], $stato]);
}

header('Location: ' . admin_url('articoli'));
exit;
