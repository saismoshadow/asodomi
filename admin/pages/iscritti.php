<?php
/**
 * Lista iscritti newsletter
 */
require_once __DIR__ . '/../../inc/auth.php';
admin_required();

$pdo = db();

$stato = $_GET['stato'] ?? '';
$where = '';
$params = [];
if ($stato === 'attivo') {
    $where = 'WHERE attivo = 1';
} elseif ($stato === 'inattivo') {
    $where = 'WHERE attivo = 0';
}

$stmt = $pdo->prepare("SELECT * FROM newsletter_iscritti $where ORDER BY creato_il DESC");
$stmt->execute($params);
$iscritti = $stmt->fetchAll();

$totale = $pdo->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE attivo = 1")->fetchColumn();

$t = asodomi_load_lang($lang);
?>
<div class="admin-header">
    <h1><?= e($t['newsletter']['iscritti'] ?? 'Iscritti newsletter') ?></h1>
    <span class="badge"><?= e($totale) ?> <?= e($t['newsletter']['iscritti_attivi'] ?? 'iscritti attivi') ?></span>
</div>

<div class="admin-filters">
    <form method="get">
        <select name="stato">
            <option value=""><?= e($t['newsletter']['tutti'] ?? 'Tutti') ?></option>
            <option value="attivo" <?= $stato === 'attivo' ? 'selected' : '' ?>><?= e($t['newsletter']['attivi'] ?? 'Attivi') ?></option>
            <option value="inattivo" <?= $stato === 'inattivo' ? 'selected' : '' ?>><?= e($t['newsletter']['inattivi'] ?? 'Inattivi') ?></option>
        </select>
        <button type="submit" class="btn btn-secondary"><?= e($t['newsletter']['filtra'] ?? 'Filtra') ?></button>
    </form>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th><?= e($t['newsletter']['email'] ?? 'Email') ?></th>
            <th><?= e($t['newsletter']['nome'] ?? 'Nome') ?></th>
            <th><?= e($t['newsletter']['lingua'] ?? 'Lingua') ?></th>
            <th><?= e($t['newsletter']['stato'] ?? 'Stato') ?></th>
            <th><?= e($t['newsletter']['creato_il'] ?? 'Iscritto il') ?></th>
            <th><?= e($t['newsletter']['azioni'] ?? 'Azioni') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($iscritti as $i): ?>
        <tr>
            <td><?= e($i['email']) ?></td>
            <td><?= e($i['nome'] ?? '—') ?></td>
            <td><?= e(strtoupper($i['lingua'] ?? 'it')) ?></td>
            <td><span class="status status-<?= $i['attivo'] ? 'attivo' : 'inattivo' ?>"><?= $i['attivo'] ? ($t['newsletter']['attivo'] ?? 'Attivo') : ($t['newsletter']['inattivo'] ?? 'Inattivo') ?></span></td>
            <td><?= e(date('d/m/Y', strtotime($i['creato_il']))) ?></td>
            <td>
                <form method="post" action="<?= e(url($lang, 'toggle-iscritto')) ?>" style="display:inline">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= e($i['id']) ?>">
                    <input type="hidden" name="attivo" value="<?= $i['attivo'] ? '0' : '1' ?>">
                    <button type="submit" class="btn btn-sm <?= $i['attivo'] ? 'btn-secondary' : 'btn-primary' ?>">
                        <?= $i['attivo'] ? ($t['newsletter']['disattiva'] ?? 'Disattiva') : ($t['newsletter']['attiva'] ?? 'Attiva') ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($iscritti)): ?>
        <tr><td colspan="6" class="center"><?= e($t['newsletter']['nessun_iscritto'] ?? 'Nessun iscritto') ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>
