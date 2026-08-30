<?php
/** Dettaglio e modifica socio */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = db()->prepare('SELECT * FROM soci WHERE id = ?');
$stmt->execute([$id]);
$socio = $stmt->fetch();
if (!$socio) {
    echo '<div class="alert alert-err">Socio non trovato.</div>';
    return;
}
?>

<p><a href="<?= e(admin_url('soci')) ?>">← Tutti i soci</a></p>
<h1><?= e($socio['nome']) ?></h1>
<p class="muted">Iscritto il <?= e(date('d.m.Y', strtotime($socio['creato_il']))) ?></p>

<form method="post" action="<?= e(admin_url('salva-socio')) ?>" class="form panel-form">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$socio['id'] ?>">

    <label>Nome *
        <input type="text" name="nome" required maxlength="160" value="<?= e($socio['nome']) ?>">
    </label>
    <label>Email *
        <input type="email" name="email" required maxlength="160" value="<?= e($socio['email']) ?>">
    </label>
    <label>Telefono
        <input type="tel" name="telefono" maxlength="40" value="<?= e($socio['telefono'] ?? '') ?>">
    </label>
    <label>Indirizzo
        <input type="text" name="indirizzo" maxlength="200" value="<?= e($socio['indirizzo'] ?? '') ?>">
    </label>
    <label>Comune
        <input type="text" name="comune" maxlength="120" value="<?= e($socio['comune'] ?? '') ?>">
    </label>

    <label>Stato
        <select name="stato">
            <?php foreach (['attivo' => '● Attivo', 'in_attesa' => '◐ In attesa', 'dimesso' => '○ Dimesso'] as $val => $et): ?>
                <option value="<?= $val ?>" <?= $socio['stato'] === $val ? 'selected' : '' ?>><?= $et ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Note interne (non visibili sul sito)
        <textarea name="note" rows="5" placeholder="Es. richieste di aiuto, servizi usati, preferenze di contatto…"><?= e($socio['note'] ?? '') ?></textarea>
    </label>

    <label>Nuova password per l'area soci
        <input type="password" name="password" minlength="8" autocomplete="new-password"
               placeholder="<?= $socio['password_hash'] ? 'lascia vuoto per non cambiare' : 'il socio non ha ancora una password' ?>">
    </label>

    <p class="hint">Consenso privacy: <?= $socio['consenso_privacy'] ? '✅ prestato il ' . e(date('d.m.Y', strtotime($socio['creato_il']))) : '—' ?> ·
       Area soci: <?= $socio['password_hash'] ? '✅ password impostata' : '✗ nessuna password' ?></p>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salva modifiche</button>
        <a class="btn btn-ghost" href="mailto:<?= e($socio['email']) ?>">Scrivi email</a>
    </div>
</form>

<h2 style="margin-top:2rem">📄 Documenti caricati da questo socio</h2>
<?php
$stmt = db()->prepare('SELECT * FROM documenti_soci WHERE socio_id = ? ORDER BY creato_il DESC');
$stmt->execute([$id]);
$doc_socio = $stmt->fetchAll();
?>
<?php if (isset($_GET['docdel'])): ?><div class="alert alert-ok">Documento eliminato.</div><?php endif; ?>
<?php if (!$doc_socio): ?>
    <p class="muted">Questo socio non ha ancora caricato documenti.</p>
<?php else: ?>
    <table class="tabella">
        <tr><th>File</th><th>Dimensione</th><th>Caricato il</th><th></th></tr>
        <?php foreach ($doc_socio as $d):
            $dim = $d['dimensione'] >= 1048576
                ? round($d['dimensione'] / 1048576, 2) . ' MB'
                : round($d['dimensione'] / 1024, 1) . ' KB';
        ?>
            <tr>
                <td>
                    <a href="<?= e(asset('/download-documento.php?id=' . (int)$d['id'])) ?>"><?= e($d['nome_originale']) ?></a>
                    <br><small class="muted"><?= e($d['tipo_mime'] ?: '—') ?></small>
                </td>
                <td><?= $dim ?></td>
                <td><?= e(date('d.m.Y H:i', strtotime($d['creato_il']))) ?></td>
                <td class="azioni">
                    <a class="btn btn-sm" href="<?= e(asset('/download-documento.php?id=' . (int)$d['id'])) ?>">Scarica</a>
                    <form method="post" action="<?= e(admin_url('elimina-documento-socio')) ?>" style="display:inline" onsubmit="return confirm('Eliminare il documento «<?= e(addslashes($d['nome_originale'])) ?>»?');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
