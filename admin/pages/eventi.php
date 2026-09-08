<?php
/**
 * Elenco eventi (lista dentro la sezione "Contenuti").
 * Include la logica di applicazione automatica dell'archivio per gli eventi scaduti.
 */

// Gli eventi passati vanno automaticamente in archivio (senza eliminarli)
db()->exec(
    'UPDATE eventi SET archiviato = 1
     WHERE stato = "pubblicato" AND archiviato = 0 AND data IS NOT NULL AND data < CURDATE()'
);

$filtro = ($_GET['ev'] ?? '') === 'archivio' ? 'archivio' : 'attivi';
if ($filtro === 'archivio') {
    $eventi = db()->query(
        'SELECT * FROM eventi WHERE archiviato = 1 ORDER BY data DESC'
    )->fetchAll();
} else {
    $eventi = db()->query(
        'SELECT * FROM eventi WHERE archiviato = 0 ORDER BY data ASC'
    )->fetchAll();
}
?>

<div class="page-title-row">
    <h2>Eventi</h2>
    <a class="btn btn-primary" href="<?= e(admin_url('evento')) ?>">+ Nuovo evento</a>
</div>

<p class="muted" style="margin-top:-.5rem">
    Mostrati nel sito nella sezione &laquo;Eventi&raquo;. Appena passa la data, l'evento va automaticamente in archivio (senza essere eliminato) e può essere riattivato.
</p>

<div style="margin:.75rem 0"><a class="btn btn-ghost btn-sm" href="<?= e(admin_url('articoli') . '&tipo=eventi') ?>">◉ Attivi</a>
<a class="btn btn-ghost btn-sm" href="<?= e(admin_url('articoli') . '&tipo=eventi&ev=archivio') ?>">🗄 Archivio</a></div>

<?php if (!$eventi): ?>
    <div class="panel"><p class="muted">Nessun evento <?= $filtro === 'archivio' ? 'in archivio' : 'attivo' ?>. Crea il primo con il pulsante qui sopra.</p></div>
<?php else: ?>
    <div class="panel">
        <table class="tabella">
            <tr>
                <th>Titolo</th><th>Data</th><th>Modalità</th><th>Stato</th><th>Archivio</th><th></th>
            </tr>
            <?php foreach ($eventi as $ev): ?>
                <tr>
                    <td><strong><?= e($ev['titolo']) ?></strong>
                        <?php if ($ev['luogo'] !== null && $ev['luogo'] !== ''): ?><br><small class="muted">📍 <?= e($ev['luogo']) ?></small><?php endif; ?>
                    </td>
                    <td><?= $ev['data'] ? e(date('d.m.Y', strtotime($ev['data']))) : '–' ?></td>
                    <td><?= e(asodomi_modalita_icona($ev['modalita'])) ?> <?= e(asodomi_modalita($ev['modalita'])) ?></td>
                    <td><span class="stato stato-<?= e($ev['stato']) ?>"><?= $ev['stato'] === 'pubblicato' ? '● pubblicato' : '○ bozza' ?></span></td>
                    <td><span class="stato"><?= $ev['archiviato'] ? '🗄 in archivio' : 'attivo' ?></span></td>
                    <td class="azioni">
                        <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('evento') . '&id=' . (int)$ev['id']) ?>">Modifica</a>
                        <form method="post" action="<?= e(admin_url('stato-evento')) ?>" style="display:inline">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                            <input type="hidden" name="archiviato" value="<?= $ev['archiviato'] ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"><?= $ev['archiviato'] ? 'Riattiva' : 'Archivia' ?></button>
                        </form>
                        <form method="post" action="<?= e(admin_url('elimina-evento')) ?>" style="display:inline" onsubmit="return confirm('Eliminare davvero «<?= e(addslashes($ev['titolo'])) ?>»?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
