<?php
/**
 * ASODOMI – Dashboard Newsletter (statistiche + azioni).
 */
$pdo = db();

$stats = [
    'totale'       => (int)$pdo->query("SELECT COUNT(*) FROM newsletter_iscritti")->fetchColumn(),
    'attivi'       => (int)$pdo->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE status='confirmed' AND attivo=1")->fetchColumn(),
    'pending'      => (int)$pdo->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE status='pending' AND attivo=0")->fetchColumn(),
    'unsubscribed' => (int)$pdo->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE status='unsubscribed'")->fetchColumn(),
];
$campagneInviate     = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_campagne WHERE stato='inviata'")->fetchColumn();
$campagneProgrammate = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_campagne WHERE stato='programmata'")->fetchColumn();
$ultima = $pdo->query("SELECT titolo, oggetto, inviata_il FROM newsletter_campagne WHERE stato='inviata' ORDER BY inviata_il DESC LIMIT 1")->fetch();
?>
<div class="admin-header">
    <h1>📧 Newsletter</h1>
    <a class="btn btn-primary" href="<?= e(admin_url('newsletter_nuova')) ?>">➕ Nuova newsletter</a>
</div>

<div class="admin-tabs">
    <a class="admin-tab active" href="<?= e(admin_url('newsletter')) ?>">📊 Dashboard</a>
    <a class="admin-tab" href="<?= e(admin_url('newsletter_iscritti')) ?>">👥 Iscritti</a>
    <a class="admin-tab" href="<?= e(admin_url('newsletter_nuova')) ?>">✉️ Nuova</a>
</div>

<div class="stat-grid">
    <div class="stat-card"><strong><?= $stats['totale'] ?></strong><span>Iscritti totali</span></div>
    <div class="stat-card"><strong><?= $stats['attivi'] ?></strong><span>Iscritti attivi</span></div>
    <div class="stat-card"><strong style="color:#b07d00"><?= $stats['pending'] ?></strong><span>Da confermare</span></div>
    <div class="stat-card"><strong style="color:#6a7180"><?= $stats['unsubscribed'] ?></strong><span>Disiscritti</span></div>
    <div class="stat-card"><strong><?= $campagneInviate ?></strong><span>Newsletter inviate</span></div>
    <div class="stat-card"><strong style="color:#0b6e0b"><?= $campagneProgrammate ?></strong><span>Programmate</span></div>
</div>

<section class="panel">
    <h2>Ultima newsletter inviata</h2>
    <?php if ($ultima): ?>
        <p><strong><?= e($ultima['titolo']) ?></strong> — <?= e($ultima['oggetto']) ?></p>
        <p class="muted">Inviata il <?= e(date('d/m/Y H:i', strtotime($ultima['inviata_il']))) ?></p>
    <?php else: ?>
        <p class="muted">Nessuna newsletter inviata finora. <a href="<?= e(admin_url('newsletter_nuova')) ?>">Crea la prima →</a></p>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Consenso e privacy</h2>
    <p class="muted">Le iscrizioni usano il doppio opt-in (conferma via email). Ogni newsletter include un link di disiscrizione. I dati personali sono trattati nel rispetto della Legge svizzera sulla protezione dei dati (nLPD). Gli indirizzi email non sono mai mostrati pubblicamente.</p>
</section>