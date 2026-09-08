<?php
/**
 * ASODOMI – Modulo pubblico di iscrizione alla newsletter (double opt-in).
 * Include: Nome, Cognome, Email, checkbox consenso, bottone. Multilingua.
 * Necessita di: $t, $lang già in scope (come nell'area soci di index.php).
 */
$nwlF = $t['newsletter'] ?? [];
$nlErrore = $_GET['nl_err'] ?? '';
?>
<section class="newsletter-sub-card" style="margin-top:2rem;background:#fff;border:1px dashed var(--borde);border-radius:var(--radio);padding:1.6rem;">
    <h3>📧 <?= e($nwlF['titolo'] ?? 'Newsletter ASODOMI') ?></h3>
    <p class="muted"><?= e($nwlF['modulo_caratteristiche'] ?? 'Ricevi le comunicazioni e le novità di ASODOMI via email. Puoi disiscriverti in qualsiasi momento.') ?></p>

    <?php if ($nlErrore === 'rate'): ?>
        <div class="alert alert-err">⚠️ <?= e($nwlF['errore_rate'] ?? 'Troppe richieste. Riprova più tardi.') ?></div>
    <?php elseif ($nlErrore === 'campi'): ?>
        <div class="alert alert-err">⚠️ <?= e($nwlF['errore_campi'] ?? 'Compila tutti i campi e spunta il consenso.') ?></div>
    <?php elseif ($nlErrore === 'gia_iscritto'): ?>
        <div class="alert alert-info">ℹ️ <?= e($nwlF['gia_iscritto'] ?? 'Sei già iscritto alla newsletter.') ?></div>
    <?php elseif ($nlErrore !== ''): ?>
        <div class="alert alert-err">⚠️ <?= e($nwlF['errore'] ?? 'Iscrizione non riuscita. Riprova.') ?></div>
    <?php endif; ?>

    <form class="form newsletter-form" method="post" action="<?= e(asset('/newsletter_subscribe.php')) ?>">
        <input type="hidden" name="lang" value="<?= e($lang) ?>">
        <input type="hidden" name="canale" value="area_soci">
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
        <label><?= e($nwlF['nome'] ?? 'Nome *') ?>
            <input type="text" name="nome" required maxlength="120">
        </label>
        <label><?= e($nwlF['cognome'] ?? 'Cognome *') ?>
            <input type="text" name="cognome" required maxlength="120">
        </label>
        <label><?= e($nwlF['email'] ?? 'Email *') ?>
            <input type="email" name="email" required maxlength="160" autocomplete="email">
        </label>
        <label class="check-label">
            <input type="checkbox" name="consenso" value="1" required>
            <span><?= e($nwlF['consenso'] ?? 'Desidero ricevere le comunicazioni e le newsletter di ASODOMI. Posso annullare l\'iscrizione in qualsiasi momento.') ?></span>
        </label>
        <button type="submit" class="btn btn-primary"><?= e($nwlF['subscribe_btn'] ?? 'ISCRIVITI ALLA NEWSLETTER') ?></button>
        <p class="muted" style="font-size:.82rem;margin-top:.6rem"><?= e($nwlF['privacy_note'] ?? 'Iscrizione in doppia conferma: attiva solo dopo la verifica dell\'email. I tuoi dati non saranno condivisi.') ?></p>
    </form>
</section>