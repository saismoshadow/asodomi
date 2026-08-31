<?php
/** Form nuovo/modifica evento */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ev = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM eventi WHERE id = ?');
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if (!$ev) {
        echo '<div class="alert alert-err">Evento non trovato.</div>';
        return;
    }
}
$titolo_pagina = $ev ? 'Modifica evento' : 'Nuovo evento';
?>

<p><a href="<?= e(admin_url('articoli') . '&tipo=eventi') ?>">← Tutti gli eventi</a></p>
<h1><?= e($titolo_pagina) ?></h1>

<form method="post" action="<?= e(admin_url('salva-evento')) ?>" class="form panel-form" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)($ev['id'] ?? 0) ?>">

    <label>Titolo *
        <input type="text" name="titolo" required maxlength="200"
               value="<?= e($ev['titolo'] ?? '') ?>"
               placeholder="Es. Incontro mensile della comunità">
    </label>

    <label>Descrizione
        <textarea name="descrizione" rows="5"
                  placeholder="Descrivi l'evento: programma, orari, come partecipare..."><?= e($ev['descrizione'] ?? '') ?></textarea>
    </label>

    <label>Data *
        <input type="date" name="data" required value="<?= e($ev['data'] ?? '') ?>">
    </label>

    <label>Luogo
        <input type="text" name="luogo" maxlength="200"
               value="<?= e($ev['luogo'] ?? '') ?>"
               placeholder="Es. Via Scazziga 8, 6600 Muralto">
    </label>

    <label>Modalità
        <select name="modalita">
            <option value="presenza" <?= ($ev['modalita'] ?? '') === 'presenza' ? 'selected' : '' ?>>📍 In presenza</option>
            <option value="online" <?= ($ev['modalita'] ?? '') === 'online' ? 'selected' : '' ?>>💻 Online</option>
            <option value="mista" <?= ($ev['modalita'] ?? '') === 'mista' ? 'selected' : '' ?>>🔄 Mista (online e in presenza)</option>
        </select>
    </label>

    <p class="hint" style="margin-top:.6rem">Allegati (facoltativi)</p>
    <label>Immagine
        <input type="file" name="immagine" accept="image/jpeg,image/png,image/gif,image/webp">
        <?php if (!empty($ev['immagine'])): ?>
            <span class="hint">Attuale: <img src="<?= e(asodomi_media_url($ev['immagine'])) ?>" style="max-height:70px;vertical-align:middle" alt=""> &nbsp; (sostituisci scegliendo un nuovo file)</span>
        <?php endif; ?>
    </label>

    <label>Video (file, facoltativo)
        <input type="file" name="videofile" accept="video/mp4,video/webm">
        <?php if (!empty($ev['videofile']) && is_string($ev['videofile']) && strpos($ev['videofile'], '.') !== false): ?>
            <span class="hint">Attuale: <?= e(basename((string)$ev['videofile'])) ?>. Sostituisci scegliendo un nuovo file per rimuoverlo con un nuovo upload.</span>
        <?php endif; ?>
    </label>

    <label>Oppure link video (YouTube / Vimeo)
        <input type="url" name="video_url" maxlength="300"
               value="<?= e($ev['video_url'] ?? '') ?>"
               placeholder="https://www.youtube.com/watch?v=...">
    </label>

    <label>Stato
        <select name="stato">
            <option value="bozza" <?= ($ev['stato'] ?? '') === 'bozza' ? 'selected' : '' ?>>○ Bozza (non visibile sul sito)</option>
            <option value="pubblicato" <?= ($ev['stato'] ?? '') === 'pubblicato' ? 'selected' : '' ?>>● Pubblicato</option>
        </select>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salva evento</button>
        <a class="btn btn-ghost" href="<?= e(admin_url('articoli') . '&tipo=eventi') ?>">Annulla</a>
    </div>
</form>
