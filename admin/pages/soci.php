<?php
/** Elenco soci con filtro per stato */
$filtro = in_array($_GET['stato'] ?? '', ['attivo', 'in_attesa', 'dimesso'], true) ? $_GET['stato'] : '';
if ($filtro !== '') {
    $stmt = db()->prepare('SELECT * FROM soci WHERE stato = ? ORDER BY creato_il DESC');
    $stmt->execute([$filtro]);
} else {
    $stmt = db()->query('SELECT * FROM soci ORDER BY creato_il DESC');
}
$soci = $stmt->fetchAll();

$etichette_stato = ['attivo' => '● attivo', 'in_attesa' => '◐ in attesa', 'dimesso' => '○ dimesso'];
?>

<div class="page-title-row">
    <h1>Soci (<?= count($soci) ?>)</h1>
    <a class="btn btn-primary" href="<?= e(admin_url('soci-export') . ($filtro !== '' ? '&stato=' . $filtro : '')) ?>">⬇ Esporta CSV</a>
</div>

<p class="filtri">
    Stato:
    <a href="<?= e(admin_url('soci')) ?>" class="<?= $filtro === '' ? 'active' : '' ?>">tutti</a> ·
    <a href="<?= e(admin_url('soci')) ?>&stato=attivo" class="<?= $filtro === 'attivo' ? 'active' : '' ?>">attivi</a> ·
    <a href="<?= e(admin_url('soci')) ?>&stato=in_attesa" class="<?= $filtro === 'in_attesa' ? 'active' : '' ?>">in attesa</a> ·
    <a href="<?= e(admin_url('soci')) ?>&stato=dimesso" class="<?= $filtro === 'dimesso' ? 'active' : '' ?>">dimessi</a>
</p>

<?php if (!$soci): ?>
    <div class="panel"><p class="muted">Nessun socio con questo filtro.</p></div>
<?php else: ?>
    <div class="panel">
        <table class="tabella">
            <tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Comune</th><th>Stato</th><th>Iscritto il</th><th></th></tr>
            <?php foreach ($soci as $s): ?>
                <tr>
                    <td><strong><?= e($s['nome']) ?></strong></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['telefono'] ?: '–') ?></td>
                    <td><?= e($s['comune'] ?: '–') ?></td>
                    <td><span class="stato stato-<?= e($s['stato']) ?>"><?= $etichette_stato[$s['stato']] ?></span></td>
                    <td><?= e(date('d.m.Y', strtotime($s['creato_il']))) ?></td>
                    <td class="azioni">
                        <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('socio') . '&id=' . (int)$s['id']) ?>">Apri</a>
                        <form method="post" action="<?= e(admin_url('elimina-socio')) ?>" onsubmit="return confirm('Eliminare davvero «<?= e(addslashes($s['nome'])) ?>»?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
