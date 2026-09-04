<?php
/**
 * Crea/Modifica campagna newsletter
 */
require_once __DIR__ . '/../../inc/auth.php';
admin_required();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$campagna = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM newsletter_campagne WHERE id = ?");
    $stmt->execute([$id]);
    $campagna = $stmt->fetch();
    if (!$campagna) {
        header('Location: ' . url($lang, 'newsletter'));
        exit;
    }
}

$t = asodomi_load_lang($lang);
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifica();
    $titolo = trim((string)($_POST['titolo'] ?? ''));
    $oggetto = trim((string)($_POST['oggetto'] ?? ''));
    $contenuto = trim((string)($_POST['contenuto'] ?? ''));
    $stato = $_POST['stato'] ?? 'bozza';

    if ($titolo === '' || $oggetto === '' || $contenuto === '') {
        $errore = $t['newsletter']['campi_obbligatori'] ?? 'Tutti i campi sono obbligatori';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE newsletter_campagne SET titolo=?, oggetto=?, contenuto=?, stato=?, aggiornato_il=NOW() WHERE id=?");
            $stmt->execute([$titolo, $oggetto, $contenuto, $stato, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO newsletter_campagne (titolo, oggetto, contenuto, stato) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titolo, $oggetto, $contenuto, $stato]);
            $id = $pdo->lastInsertId();
        }
        header('Location: ' . url($lang, 'newsletter'));
        exit;
    }
}
?>
<div class="admin-header">
    <h1><?= $campagna ? ($t['newsletter']['modifica_campagna'] ?? 'Modifica campagna') : ($t['newsletter']['nuova_campagna'] ?? 'Nuova campagna') ?></h1>
    <a class="btn btn-secondary" href="<?= e(url($lang, 'newsletter')) ?>"><?= e($t['newsletter']['indietro'] ?? 'Indietro') ?></a>
</div>

<?php if ($errore): ?>
<div class="alert alert-error"><?= e($errore) ?></div>
<?php endif; ?>

<form method="post" class="admin-form">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">

    <div class="form-group">
        <label><?= e($t['newsletter']['titolo'] ?? 'Titolo interno') ?></label>
        <input type="text" name="titolo" value="<?= e($campagna['titolo'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label><?= e($t['newsletter']['oggetto'] ?? 'Oggetto email') ?></label>
        <input type="text" name="oggetto" value="<?= e($campagna['oggetto'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label><?= e($t['newsletter']['contenuto'] ?? 'Contenuto (HTML)') ?></label>
        <textarea name="contenuto" rows="15" required><?= e($campagna['contenuto'] ?? '') ?></textarea>
        <small><?= e($t['newsletter']['contenuto_aiuto'] ?? 'Usa HTML. Variabili: {nome}, {unsubscribe_link}') ?></small>
    </div>

    <div class="form-group">
        <label><?= e($t['newsletter']['stato'] ?? 'Stato') ?></label>
        <select name="stato">
            <option value="bozza" <?= ($campagna['stato'] ?? '') === 'bozza' ? 'selected' : '' ?>><?= e($t['newsletter']['bozza'] ?? 'Bozza') ?></option>
            <option value="programmata" <?= ($campagna['stato'] ?? '') === 'programmata' ? 'selected' : '' ?>><?= e($t['newsletter']['programmata'] ?? 'Programmata') ?></option>
        </select>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= e($t['newsletter']['salva'] ?? 'Salva') ?></button>
        <a class="btn btn-secondary" href="<?= e(url($lang, 'newsletter')) ?>"><?= e($t['newsletter']['annulla'] ?? 'Annulla') ?></a>
    </div>
</form>
