<?php
/**
 * Invia campagna newsletter
 */
require_once __DIR__ . '/../../inc/auth.php';
admin_required();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM newsletter_campagne WHERE id = ?");
$stmt->execute([$id]);
$campagna = $stmt->fetch();

if (!$campagna) {
    header('Location: ' . url($lang, 'newsletter'));
    exit;
}

if ($campagna['stato'] !== 'bozza') {
    header('Location: ' . url($lang, 'newsletter?err=stato'));
    exit;
}

$t = asodomi_load_lang($lang);
$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifica();
    $conferma = isset($_POST['conferma']);

    if (!$conferma) {
        $errore = $t['newsletter']['conferma_invio'] ?? 'Conferma l\'invio';
    } else {
        // Get active subscribers
        $stmt = $pdo->query("SELECT email, nome, lingua, token_unsubscribe FROM newsletter_iscritti WHERE attivo = 1");
        $iscritti = $stmt->fetchAll();

        $inviate = 0;
        $fallite = 0;
        $baseUrl = (defined('SITE_URL') && SITE_URL) ? rtrim(SITE_URL, '/') : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(base_url(), '/');

        foreach ($iscritti as $iscritto) {
            $contenuto = $campagna['contenuto'];
            $contenuto = str_replace('{nome}', $iscritto['nome'] ?? '', $contenuto);
            $contenuto = str_replace('{unsubscribe_link}', $baseUrl . url($lang, 'newsletter_unsubscribe') . '?token=' . $iscritto['token_unsubscribe'], $contenuto);

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: " . CONTACT_EMAIL . "\r\n";
            $headers .= "Reply-To: " . CONTACT_EMAIL . "\r\n";

            if (mail($iscritto['email'], $campagna['oggetto'], $contenuto, $headers)) {
                $inviate++;
            } else {
                $fallite++;
            }
        }

        // Update campaign
        $stmt = $pdo->prepare("UPDATE newsletter_campagne SET stato='inviata', inviata_il=NOW() WHERE id=?");
        $stmt->execute([$id]);

        $messaggio = sprintf($t['newsletter']['invio_completato'] ?? 'Inviate: %d, Fallite: %d', $inviate, $fallite);
    }
}
?>
<div class="admin-header">
    <h1><?= e($t['newsletter']['invia_campagna'] ?? 'Invia campagna') ?>: <?= e($campagna['titolo']) ?></h1>
    <a class="btn btn-secondary" href="<?= e(url($lang, 'newsletter')) ?>"><?= e($t['newsletter']['indietro'] ?? 'Indietro') ?></a>
</div>

<?php if ($messaggio): ?>
<div class="alert alert-success"><?= e($messaggio) ?></div>
<?php endif; ?>
<?php if ($errore): ?>
<div class="alert alert-error"><?= e($errore) ?></div>
<?php endif; ?>

<div class="card">
    <h3><?= e($t['newsletter']['dettagli'] ?? 'Dettagli campagna') ?></h3>
    <p><strong><?= e($t['newsletter']['oggetto'] ?? 'Oggetto') ?>:</strong> <?= e($campagna['oggetto']) ?></p>
    <p><strong><?= e($t['newsletter']['stato'] ?? 'Stato') ?>:</strong> <span class="status status-<?= e($campagna['stato']) ?>"><?= e($t['newsletter'][$campagna['stato']] ?? $campagna['stato']) ?></span></p>
</div>

<form method="post" onsubmit="return confirm('<?= e($t['newsletter']['conferma_invio_prompt'] ?? 'Inviare davvero questa campagna a tutti gli iscritti?') ?>');">
    <?= csrf_campo() ?>
    <div class="form-group">
        <label>
            <input type="checkbox" name="conferma" required> <?= e($t['newsletter']['conferma_label'] ?? 'Confermo di voler inviare questa newsletter') ?>
        </label>
    </div>
    <button type="submit" class="btn btn-primary btn-lg"><?= e($t['newsletter']['invia_ora'] ?? 'Invia ora') ?></button>
</form>
