<?php
/** Form nuovo/modifica notizia */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errore = isset($_GET['err']) ? $_GET['err'] : '';
$nt = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM notizie WHERE id = ?');
    $stmt->execute([$id]);
    $nt = $stmt->fetch();
    if (!$nt) {
        echo '<div class="alert alert-err">Notizia non trovata.</div>';
        return;
    }
}
$titolo_pagina = $nt ? 'Modifica notizia' : 'Nuova notizia';
?>

<p><a href="<?= e(admin_url('articoli') . '&tipo=notizie') ?>">← Tutte le notizie</a></p>
<h1><?= e($titolo_pagina) ?></h1>

<?php if ($errore !== ''): ?>
    <div class="alert alert-err"><?= e($errore) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('salva-notizia')) ?>" class="form panel-form" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)($nt['id'] ?? 0) ?>">

    <label>Titolo *
        <input type="text" name="titolo" required maxlength="200"
               value="<?= e($nt['titolo'] ?? '') ?>"
               placeholder="Titolo della notizia">
    </label>

    <label>Testo *
        <textarea name="testo" rows="6" required
                  placeholder="Scrivi la notizia in stile giornalistico..."><?= e($nt['testo'] ?? '') ?></textarea>
    </label>

    <label>Fonte (link, facoltativo)
        <input type="url" name="fonte_url" maxlength="400"
               value="<?= e($nt['fonte_url'] ?? '') ?>"
               placeholder="https://www.example.com/notizia">
    </label>

    <p class="hint" style="margin-top:.6rem">Allegati (facoltativi)</p>
    <label>Immagine
        <input type="file" name="immagine" accept="image/jpeg,image/png,image/gif,image/webp">
        <?php if (!empty($nt['immagine'])): ?>
            <span class="hint">Attuale: <img src="<?= e(asodomi_media_url($nt['immagine'])) ?>" style="max-height:70px;vertical-align:middle" alt=""> &nbsp; (sostituisci scegliendo un nuovo file)</span>
        <?php endif; ?>
    </label>

    <label>Oppure link video (YouTube / Vimeo)
        <input type="url" name="video_url" maxlength="300"
               value="<?= e($nt['video_url'] ?? '') ?>"
               placeholder="https://www.youtube.com/watch?v=...">
    </label>

    <label>Stato
        <select name="stato">
            <option value="bozza" <?= ($nt['stato'] ?? '') === 'bozza' ? 'selected' : '' ?>>○ Bozza (non visibile sul sito)</option>
            <option value="pubblicato" <?= ($nt['stato'] ?? '') === 'pubblicato' ? 'selected' : '' ?>>● Pubblicato</option>
        </select>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salva notizia</button>
        <a class="btn btn-ghost" href="<?= e(admin_url('articoli') . '&tipo=notizie') ?>">Annulla</a>
    </div>
</form>
