<?php
/** Form nuovo/modifica articolo */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$art = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM articoli WHERE id = ?');
    $stmt->execute([$id]);
    $art = $stmt->fetch();
    if (!$art) {
        echo '<div class="alert alert-err">Articolo non trovato.</div>';
        return;
    }
}
$titolo_pagina = $art ? 'Modifica articolo' : 'Nuovo articolo';
?>

<p><a href="<?= e(admin_url('articoli')) ?>">← Tutti gli articoli</a></p>
<h1><?= e($titolo_pagina) ?></h1>

<form method="post" action="<?= e(admin_url('salva-articolo')) ?>" class="form panel-form">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)($art['id'] ?? 0) ?>">

    <label>Titolo *
        <input type="text" name="titolo" required maxlength="200"
               value="<?= e($art['titolo'] ?? '') ?>"
               placeholder="Es. Festa dominicana a Ginevra: tutte le foto">
    </label>

    <label>Contenuto
        <textarea name="contenuto" rows="14"
                  placeholder="Scrivi l'articolo. Puoi usare il testo semplice (i paragrafi vengono creati automaticamente) oppure un po' di HTML: &lt;h2&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;a href=...&gt;."><?= e($art['contenuto'] ?? '') ?></textarea>
    </label>
    <p class="hint">Suggerimento: lascia una riga vuota tra i paragrafi. Per un video, incolla sotto il link YouTube o Vimeo.</p>

    <label>Link video (YouTube o Vimeo)
        <input type="url" name="video_url" maxlength="300"
               value="<?= e($art['video_url'] ?? '') ?>"
               placeholder="https://www.youtube.com/watch?v=...">
    </label>

    <label>Stato
        <select name="stato">
            <option value="bozza" <?= ($art['stato'] ?? '') === 'bozza' ? 'selected' : '' ?>>○ Bozza (non visibile sul sito)</option>
            <option value="pubblicato" <?= ($art['stato'] ?? '') === 'pubblicato' ? 'selected' : '' ?>>● Pubblicato</option>
        </select>
    </label>

    <label>Visibilità
        <select name="visibilita">
            <option value="pubblico" <?= ($art['visibilita'] ?? 'pubblico') === 'pubblico' ? 'selected' : '' ?>>🌐 Pubblico (tutti)</option>
            <option value="riservato" <?= ($art['visibilita'] ?? '') === 'riservato' ? 'selected' : '' ?>>🔒 Riservato ai soci</option>
        </select>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salva articolo</button>
        <?php if ($art): ?>
            <a class="btn btn-ghost" href="<?= e(url('it', 'blog') . '/' . rawurlencode($art['slug'])) ?>" target="_blank">Vedi sul sito ↗</a>
        <?php endif; ?>
    </div>
</form>
