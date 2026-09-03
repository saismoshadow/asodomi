<?php
/**
 * ASODOMI – Gestione iscritti newsletter (ricerca, filtro, stato, export CSV).
 */
$pdo = db();

$q     = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 120));
$stato = in_array($_GET['stato'] ?? '', ['attivo', 'pending', 'unsubscribed'], true) ? $_GET['stato'] : '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(email LIKE ? OR nome LIKE ? OR cognome LIKE ? OR CONCAT(nome, " ", cognome) LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($stato !== '') {
    $where[] = 'status = ?';
    $params[] = $stato;
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT id, email, nome, cognome, lingua, status, creato_il, confirmed_at, unsubscribed_at
                       FROM newsletter_iscritti $sqlWhere ORDER BY creato_il DESC");
$stmt->execute($params);
$iscritti = $stmt->fetchAll();

$totAttivi = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE status='confirmed' AND attivo=1")->fetchColumn();
?>
<div class="admin-header">
    <h1>👥 Iscritti newsletter</h1>
    <a class="btn btn-primary" href="<?= e(admin_url('newsletter_export') . '&stato=' . rawurlencode($stato) . '&q=' . rawurlencode($q)) ?>">⬇️ Esporta CSV</a>
</div>

<div class="admin-tabs">
    <a class="admin-tab" href="<?= e(admin_url('newsletter')) ?>">📊 Dashboard</a>
    <a class="admin-tab active" href="<?= e(admin_url('newsletter_iscritti')) ?>">👥 Iscritti</a>
    <a class="admin-tab" href="<?= e(admin_url('newsletter_nuova')) ?>">✉️ Nuova</a>
</div>

<p class="muted">Sono attivi <strong><?= (int)$totAttivi ?></strong> iscritti (confermati).</p>

<div class="admin-filters">
    <form method="get">
        <input type="hidden" name="route" value="newsletter_iscritti">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cerca email o nome…" style="min-width:220px">
        <select name="stato">
            <option value="">Tutti gli stati</option>
            <option value="attivo" <?= $stato==='attivo'?'selected':'' ?>>Attivi (confermati)</option>
            <option value="pending" <?= $stato==='pending'?'selected':'' ?>>Da confermare</option>
            <option value="unsubscribed" <?= $stato==='unsubscribed'?'selected':'' ?>>Disiscritti</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filtra</button>
        <?php if ($q !== '' || $stato !== ''): ?>
        <a class="btn btn-ghost" href="<?= e(admin_url('newsletter_iscritti')) ?>">Azzerra</a>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <?php if (!$iscritti): ?>
        <p class="muted">Nessun iscritto trovato.</p>
    <?php else: ?>
    <table class="tabella">
        <tr>
            <th>Email</th><th>Nome</th><th>Lingua</th><th>Stato</th>
            <th>Iscritto</th><th>Confermato</th><th>Azioni</th>
        </tr>
        <?php foreach ($iscritti as $i): ?>
        <tr>
            <td><?= e($i['email']) ?></td>
            <td><?= e(trim(($i['nome'] ?? '') . ' ' . ($i['cognome'] ?? ''))) ?></td>
            <td><?= e(strtoupper($i['lingua'] ?? 'it')) ?></td>
            <td>
                <span class="stato stato-<?= e($i['status']) ?>">
                    <?= $i['status']==='confirmed' ? '● Attivo' : ($i['status']==='unsubscribed' ? '○ Disiscritto' : '◐ Da confermare') ?>
                </span>
            </td>
            <td><?= e(date('d/m/Y', strtotime($i['creato_il']))) ?></td>
            <td><?= $i['confirmed_at'] ? e(date('d/m/Y', strtotime($i['confirmed_at']))) : '—' ?></td>
            <td class="azioni">
                <?php if ($i['status'] === 'unsubscribed'): ?>
                    <form method="post" action="<?= e(admin_url('newsletter_iscritto_delete')) ?>" style="display:inline" onsubmit="return confirm('Eliminare definitivamente questo iscritto?');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Elimina</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e(admin_url('newsletter_iscritto_delete')) ?>" style="display:inline" onsubmit="return confirm('Eliminare definitivamente questo iscritto?');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Elimina</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>