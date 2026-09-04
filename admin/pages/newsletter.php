<?php
/**
 * Lista campagne newsletter
 */
require_once __DIR__ . '/../../inc/auth.php';
admin_required();

$pdo = db();

$stato = $_GET['stato'] ?? '';
$where = '';
$params = [];
if ($stato && in_array($stato, ['bozza','inviata','programmata'])) {
    $where = 'WHERE stato = ?';
    $params[] = $stato;
}

$stmt = $pdo->prepare("SELECT * FROM newsletter_campagne $where ORDER BY creato_il DESC");
$stmt->execute($params);
$campagne = $stmt->fetchAll();

$t = asodomi_load_lang($lang);
?>
<div class="admin-header">
    <h1><?= e($t['newsletter']['title'] ?? 'Newsletter') ?></h1>
    <a class="btn btn-primary" href="<?= e(url($lang, 'newsletter_campagna')) ?>"><?= e($t['newsletter']['nuova_campagna'] ?? 'Nuova campagna') ?></a>
</div>

<div class="admin-filters">
    <form method="get">
        <select name="stato">
            <option value=""><?= e($t['newsletter']['tutti_stati'] ?? 'Tutti') ?></option>
            <option value="bozza" <?= $stato === 'bozza' ? 'selected' : '' ?>><?= e($t['newsletter']['bozza'] ?? 'Bozza') ?></option>
            <option value="inviata" <?= $stato === 'inviata' ? 'selected' : '' ?>><?= e($t['newsletter']['inviata'] ?? 'Inviata') ?></option>
            <option value="programmata" <?= $stato === 'programmata' ? 'selected' : '' ?>><?= e($t['newsletter']['programmata'] ?? 'Programmata') ?></option>
        </select>
        <button type="submit" class="btn btn-secondary"><?= e($t['newsletter']['filtra'] ?? 'Filtra') ?></button>
    </form>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th><?= e($t['newsletter']['titolo'] ?? 'Titolo') ?></th>
            <th><?= e($t['newsletter']['oggetto'] ?? 'Oggetto') ?></th>
            <th><?= e($t['newsletter']['stato'] ?? 'Stato') ?></th>
            <th><?= e($t['newsletter']['creato_il'] ?? 'Creato il') ?></th>
            <th><?= e($t['newsletter']['inviata_il'] ?? 'Inviata il') ?></th>
            <th><?= e($t['newsletter']['azioni'] ?? 'Azioni') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($campagne as $c): ?>
        <tr>
            <td><?= e($c['titolo']) ?></td>
            <td><?= e($c['oggetto']) ?></td>
            <td><span class="status status-<?= e($c['stato']) ?>"><?= e($t['newsletter'][$c['stato']] ?? $c['stato']) ?></span></td>
            <td><?= e(date('d/m/Y H:i', strtotime($c['creato_il']))) ?></td>
            <td><?= $c['inviata_il'] ? e(date('d/m/Y H:i', strtotime($c['inviata_il']))) : '—' ?></td>
            <td>
                <a class="btn btn-sm" href="<?= e(url($lang, 'newsletter_campagna?id=' . $c['id'])) ?>"><?= e($t['newsletter']['modifica'] ?? 'Modifica') ?></a>
                <?php if ($c['stato'] === 'bozza'): ?>
                    <a class="btn btn-sm btn-primary" href="<?= e(url($lang, 'newsletter_invia?id=' . $c['id'])) ?>"><?= e($t['newsletter']['invia'] ?? 'Invia') ?></a>
                <?php endif; ?>
                <form method="post" action="<?= e(url($lang, 'elimina-newsletter')) ?>" style="display:inline" onsubmit="return confirm('<?= e($t['newsletter']['conferma_elimina'] ?? 'Eliminare questa campagna?') ?>');">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><?= e($t['newsletter']['elimina'] ?? 'Elimina') ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($campagne)): ?>
        <tr><td colspan="6" class="center"><?= e($t['newsletter']['nessuna_campagna'] ?? 'Nessuna campagna') ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>
