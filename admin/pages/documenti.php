<?php
/** Gestione documenti utili per l'area soci */
$documenti = db()->query('SELECT * FROM documenti ORDER BY creato_il DESC')->fetchAll();
?>

<div class="page-title-row">
    <h1>Documenti per i soci</h1>
</div>
<p class="muted">Questi documenti compaiono solo nell'<strong>area soci</strong> del sito.
   Carica il file su Google Drive/Dropbox (o simili) e incolla qui il link di condivisione.</p>

<div class="panel">
    <?php if (!$documenti): ?>
        <p class="muted">Nessun documento. Aggiungine uno con il modulo qui sotto.</p>
    <?php else: ?>
        <table class="tabella">
            <tr><th>Titolo</th><th>Link</th><th>Data</th><th></th></tr>
            <?php foreach ($documenti as $d): ?>
                <tr>
                    <td><strong><?= e($d['titolo']) ?></strong><?php if (!empty($d['descrizione'])): ?><br><small class="muted"><?= e($d['descrizione']) ?></small><?php endif; ?></td>
                    <td><a href="<?= e($d['url']) ?>" target="_blank" rel="noopener" style="word-break:break-all"><?= e(mb_substr($d['url'], 0, 40)) ?>…</a></td>
                    <td><?= e(date('d.m.Y', strtotime($d['creato_il']))) ?></td>
                    <td class="azioni">
                        <form method="post" action="<?= e(admin_url('elimina-documento')) ?>" onsubmit="return confirm('Eliminare «<?= e(addslashes($d['titolo'])) ?>»?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<h2>Nuovo documento</h2>
<form method="post" action="<?= e(admin_url('salva-documento')) ?>" class="form panel-form">
    <?= csrf_campo() ?>
    <label>Titolo *
        <input type="text" name="titolo" required maxlength="200" placeholder="Es. Guida al rinnovo del permesso B">
    </label>
    <label>Descrizione breve
        <input type="text" name="descrizione" maxlength="400" placeholder="Es. PDF con tutti i passaggi, aggiornata 2026">
    </label>
    <label>Link *
        <input type="url" name="url" required maxlength="400" placeholder="https://drive.google.com/...">
    </label>
    <button type="submit" class="btn btn-primary">Aggiungi documento</button>
</form>
