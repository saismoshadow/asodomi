<?php
/** Elenco articoli del blog */
$stmt = db()->query(
    'SELECT a.id, a.titolo, a.slug, a.stato, a.visibilita, COALESCE(a.pubblicato_il, a.creato_il) AS data_pub, u.nome AS autore
     FROM articoli a LEFT JOIN utenti u ON u.id = a.autore_id
     ORDER BY a.aggiornato_il DESC'
);
$articoli = $stmt->fetchAll();
?>

<div class="page-title-row">
    <h1>Articoli del blog</h1>
    <a class="btn btn-primary" href="<?= e(admin_url('articolo')) ?>">+ Nuovo articolo</a>
</div>

<?php if (!$articoli): ?>
    <div class="panel"><p class="muted">Nessun articolo. Crea il primo con il pulsante qui sopra.</p></div>
<?php else: ?>
    <div class="panel">
        <table class="tabella">
            <tr>
                <th>Titolo</th><th>Stato</th><th>Data</th><th>Autore</th><th></th>
            </tr>
            <?php foreach ($articoli as $a): ?>
                <tr>
                    <td><strong><?= e($a['titolo']) ?></strong><?php if ($a['visibilita'] === 'riservato'): ?> <span class="stato stato-bozza">🔒 soci</span><?php endif; ?><br><small class="muted">/it/blog/<?= e($a['slug']) ?></small></td>
                    <td><span class="stato stato-<?= e($a['stato']) ?>"><?= $a['stato'] === 'pubblicato' ? '● pubblicato' : '○ bozza' ?></span></td>
                    <td><?= e(date('d.m.Y', strtotime($a['data_pub']))) ?></td>
                    <td><?= e($a['autore'] ?: '–') ?></td>
                    <td class="azioni">
                        <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('articolo') . '&id=' . (int)$a['id']) ?>">Modifica</a>
                        <form method="post" action="<?= e(admin_url('elimina-articolo')) ?>" onsubmit="return confirm('Eliminare davvero «<?= e(addslashes($a['titolo'])) ?>»?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
