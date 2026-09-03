<?php
/**
 * ASODOMI – Crea / modifica / anteprima / email di prova / programmazione invio newsletter.
 */
$pdo = db();
$id  = (int)($_GET['id'] ?? 0);
$campagna = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM newsletter_campagne WHERE id = ?");
    $stmt->execute([$id]);
    $campagna = $stmt->fetch() ?: null;
    if (!$campagna) { $id = 0; }
}

$errore = $messaggio = '';
$anteprima = null;

// ── POST: salva / invio prova / programma ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifica();
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'programma_invio') {
        // Programma l'invio: il worker batch la processerà (senza ciclo HTTP)
        if ($id) {
            $pdo->prepare("UPDATE newsletter_campagne SET stato='programmata', scheduled_at = COALESCE(scheduled_at, NOW()) WHERE id=?")->execute([$id]);
            $messaggio = 'Invio programmato: il batch lo elaborerà automaticamente (cron).';
        } else {
            $errore = 'Salva prima la newsletter, poi programma l\'invio.';
        }
    } elseif ($azione === 'prova_email') {
        // Invia email di prova a un indirizzo (non registrata come inviata agli iscritti)
        $destinatario = nl_email_valida((string)($_POST['email_prova'] ?? ''));
        if (!$destinatario) {
            $errore = 'Indirizzo email di prova non valido.';
        } else {
            $oggetto = trim((string)($_POST['oggetto'] ?? ''));
            $html    = trim((string)($_POST['contenuto'] ?? ''));
            if ($oggetto === '' || $html === '') {
                $errore = 'Compila oggetto e contenuto prima dell\'anteprima/prova.';
            } else {
                $fromN = (string)($_POST['mittente_nome'] ?? NL_SENDER_NAME);
                $fromE = (string)($_POST['fr_from'] ?? NL_SENDER_EMAIL);
                $reply = (string)($_POST['reply_to'] ?? NL_REPLY_TO);
                $linkUnsub = nl_url_pubblica('newsletter_optout', ['token' => str_repeat('0',64)], DEFAULT_LANG);
                $body = nl_body_html($html, $linkUnsub, DEFAULT_LANG);
                if (nl_invia($destinatario, $oggetto, $body, $fromN, $fromE, $reply)) {
                    nl_log_invio(0, null, $destinatario, 'prova', 'ok');
                    $messaggio = 'Email di prova inviata a ' . $destinatario . '.';
                } else {
                    $errore = 'Invio email di prova fallito (controlla configurazione mail del server).';
                }
            }
        }
    } else {
        // Salva campagna
        $titolo   = trim(mb_substr((string)($_POST['titolo'] ?? ''), 0, 200));
        $oggetto  = trim(mb_substr((string)($_POST['oggetto'] ?? ''), 0, 200));
        $contenuto= trim((string)($_POST['contenuto'] ?? ''));
        $contentText = trim((string)($_POST['content_text'] ?? ''));
        $mitt   = trim(mb_substr((string)($_POST['mittente_nome'] ?? ''), 0, 120));
        $reply  = trim(mb_substr((string)($_POST['reply_to'] ?? ''), 0, 200));
        $stato  = ($_POST['stato'] ?? 'bozza') === 'programmata' ? 'programmata' : 'bozza';
        $programmata = trim((string)($_POST['scheduled_at'] ?? ''));

        if ($titolo === '' || $oggetto === '' || $contenuto === '') {
            $errore = 'Titolo, oggetto e contenuto sono obbligatori.';
        } else {
            if ($stato === 'programmata' && $programmata !== '') {
                // valida formato datetime
                if (strtotime($programmata) === false) { $programmata = ''; }
            }
            $sched = ($stato === 'programmata' && $programmata !== '') ? date('Y-m-d H:i:s', strtotime($programmata)) : null;

            if ($id) {
                $pdo->prepare("UPDATE newsletter_campagne SET titolo=?, oggetto=?, contenuto=?, content_text=?, mittente_nome=?, reply_to=?, stato=?, scheduled_at=?, aggiornato_il=NOW() WHERE id=?")
                    ->execute([$titolo, $oggetto, $contenuto, $contentText !== '' ? $contentText : nl_plain_from_html($contenuto), $mitt, $reply, $stato, $sched, $id]);
            } else {
                $pdo->prepare("INSERT INTO newsletter_campagne (titolo, oggetto, contenuto, content_text, mittente_nome, reply_to, stato, scheduled_at) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$titolo, $oggetto, $contenuto, $contentText !== '' ? $contentText : nl_plain_from_html($contenuto), $mitt, $reply, $stato, $sched]);
                $id = (int)$pdo->lastInsertId();
                $campagna = $pdo->query("SELECT * FROM newsletter_campagne WHERE id=$id")->fetch();
            }
            $messaggio = 'Campagna salvata.';
        }
    }
    if ($errore !== '') { /* mostra errore */ }
    // ricarica campagna dopo salvataggio
    if ($id && $campagna === null) {
        $campagna = $pdo->query("SELECT * FROM newsletter_campagne WHERE id=$id")->fetch() ?: null;
    }
}

// ── Anteprima (GET ?anteprima=1) ──────────────────────────────────────
if (isset($_GET['anteprima']) && $campagna) {
    $linkUnsub = nl_url_pubblica('newsletter_optout', ['token' => str_repeat('0',64)], DEFAULT_LANG);
    $anteprima = nl_body_html($campagna['contenuto'] ?? '', $linkUnsub, DEFAULT_LANG);
}

$val = function(?array $c, string $k, string $default='') use (&$campagna): string {
    return isset($campagna[$k]) ? (string)$campagna[$k] : $default;
};
$schedVal = '';
if ($campagna && isset($campagna['scheduled_at']) && $campagna['scheduled_at']) {
    $schedVal = date('Y-m-d H:i', strtotime($campagna['scheduled_at']));
}
$attivi_count = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE status='confirmed' AND attivo=1")->fetchColumn();
?>
<div class="admin-header">
    <h1><?= $id ? '✏️ Modifica newsletter' : '✉️ Nuova newsletter' ?></h1>
    <a class="btn btn-secondary" href="<?= e(admin_url('newsletter')) ?>">← Dashboard</a>
</div>

<div class="admin-tabs">
    <a class="admin-tab" href="<?= e(admin_url('newsletter')) ?>">📊 Dashboard</a>
    <a class="admin-tab" href="<?= e(admin_url('newsletter_iscritti')) ?>">👥 Iscritti</a>
    <a class="admin-tab active" href="<?= e(admin_url('newsletter_nuova') . ($id ? '&id='.$id : '')) ?>">✉️ Editor</a>
</div>

<?php if ($errore): ?><div class="alert alert-err">⚠️ <?= e($errore) ?></div><?php endif; ?>
<?php if ($messaggio): ?><div class="alert alert-success">✅ <?= e($messaggio) ?></div><?php endif; ?>

<?php if ($anteprima): ?>
<div class="panel" style="margin-bottom:1.5rem">
    <div class="admin-header"><h2>👁 Anteprima</h2><a class="btn btn-secondary" href="<?= e(admin_url('newsletter_nuova') . '&id=' . $id) ?>">← Chiudi anteprima</a></div>
    <iframe srcdoc="<?= e($anteprima) ?>" style="width:100%;height:480px;border:1px solid var(--borde);border-radius:8px;background:#fff"></iframe>
</div>
<?php endif; ?>

<form method="post" class="admin-form panel-form">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <?php if ($id): ?>
    <p class="muted">Iscritti attivi al momento: <strong><?= (int)$attivi_count ?></strong>. L'invio avvia un batch elaborato dal cron: ogni singolo messaggio viene registrato nel log.</p>
    <?php endif; ?>

    <div class="form-group">
        <label>Titolo interno *</label>
        <input type="text" name="titolo" value="<?= e($val($campagna,'titolo')) ?>" required maxlength="200">
    </div>
    <div class="form-group">
        <label>Oggetto email *</label>
        <input type="text" name="oggetto" value="<?= e($val($campagna,'oggetto')) ?>" required maxlength="200">
    </div>
    <div class="form-group">
        <label>Mittente (nome visualizzato)</label>
        <input type="text" name="mittente_nome" value="<?= e($val($campagna,'mittente_nome', NL_SENDER_NAME)) ?>" maxlength="120">
    </div>
    <div class="form-group">
        <label>Rispondi a (email)</label>
        <input type="email" name="reply_to" value="<?= e($val($campagna,'reply_to', NL_REPLY_TO)) ?>" maxlength="200">
    </div>
    <div class="form-group">
        <label>Contenuto (HTML) *</label>
        <textarea name="contenuto" rows="18" required><?= e($val($campagna,'contenuto')) ?></textarea>
        <small class="hint">Usa HTML email compatibile. Variabili: <code>{nome}</code> (nome+cognome iscritto), <code>{unsubscribe_link}</code> (disiscrizione). Il footer con associazione, indirizzo e link di disiscrizione viene aggiunto automaticamente.</small>
    </div>
    <div class="form-group">
        <label>Versione testuale (facoltativa)</label>
        <textarea name="content_text" rows="6"><?= e($val($campagna,'content_text')) ?></textarea>
        <small class="hint">Lasciato vuoto, viene generato in automatico dall'HTML.</small>
    </div>
    <div class="form-group">
        <label>Stato</label>
        <select name="stato">
            <option value="bozza" <?= ($val($campagna,'stato') ?: 'bozza') === 'bozza' ? 'selected' : '' ?>>Bozza</option>
            <option value="programmata" <?= $val($campagna,'stato') === 'programmata' ? 'selected' : '' ?>>Programmata</option>
        </select>
    </div>
    <div class="form-group">
        <label>Invia il (data/ora, se programmata)</label>
        <input type="datetime-local" name="scheduled_at" value="<?= e($schedVal) ?>">
    </div>

    <div class="form-actions">
        <button type="submit" name="azione" value="salva" class="btn btn-primary">💾 Salva</button>
        <?php if ($id): ?>
            <a class="btn btn-secondary" href="<?= e(admin_url('newsletter_nuova') . '&id=' . $id . '&anteprima=1') ?>">👁 Anteprima</a>
            <button type="submit" name="azione" value="programma_invio" class="btn btn-success">🚀 Programma invio batch</button>
        <?php endif; ?>
    </div>

    <?php if ($id): ?>
    <div class="panel" style="margin-top:1.5rem">
        <h3>📨 Email di prova</h3>
        <p class="muted">Invia una copia di prova (oggetto + contenuto sopra) a un indirizzo, senza registrarla come inviata agli iscritti.</p>
        <div class="form-group">
            <label>Indirizzo email di prova *</label>
            <input type="email" name="email_prova" required maxlength="160" placeholder="tuo@email.ch">
        </div>
        <button type="submit" name="azione" value="prova_email" class="btn btn-secondary">📤 Invia email di prova</button>
    </div>
    <?php endif; ?>
</form>