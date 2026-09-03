<?php
/** Elenco notizie (lista dentro la sezione "Contenuti") */
$notizie = db()->query(
    'SELECT * FROM notizie ORDER BY COALESCE(aggiornato_il, creato_il) DESC'
)->fetchAll();
?>

<div class="page-title-row">
    <h2>Notizie</h2>
    <a class="btn btn-primary" href="<?= e(admin_url('notizia')) ?>">+ Nuova notizia</a>
</div>

<p class="muted" style="margin-top:-.5rem">
    Notizie in stile giornalistico mostrate nella sezione &laquo;Notizie&raquo; del sito. Ogni notizia può avere una fonte (link) e un media facoltativo.
</p>

<?php if (!$notizie): ?>
    <div class="panel"><p class="muted">Nessuna notizia. Crea la prima con il pulsante qui sopra.</p></div>
<?php else: ?>
    <div class="panel">
        <table class="tabella">
            <tr>
                <th>Titolo</th><th>Fonte</th><th>Stato</th><th>Aggiornata</th><th></th>
            </tr>
            <?php foreach ($notizie as $nt): ?>
                <tr>
                    <td><strong><?= e($nt['titolo']) ?></strong></td>
                    <td>
                        <?php if (!empty($nt['fonte_url'])): ?>
                            <a href="<?= e($nt['fonte_url']) ?>" target="_blank" rel="noopener">↗ fonte</a>
                        <?php else: ?>
                            <span class="muted">–</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="stato stato-<?= e($nt['stato']) ?>"><?= $nt['stato'] === 'pubblicato' ? '● pubblicato' : '○ bozza' ?></span></td>
                    <td><?= e(date('d.m.Y', strtotime($nt['aggiornato_il'] ?? $nt['creato_il']))) ?></td>
                    <td class="azioni">
                        <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('notizia') . '&id=' . (int)$nt['id']) ?>">Modifica</a>
                        <form method="post" action="<?= e(admin_url('elimina-notizia')) ?>" style="display:inline" onsubmit="return confirm('Eliminare davvero «<?= e(addslashes($nt['titolo'])) ?>»?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$nt['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
