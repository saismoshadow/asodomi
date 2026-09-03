<?php
/**
 * ASODOMI – Upload di un documento da parte del socio.
 * Solo soci autenticati. I file finiscono in /uploads (PHP disattivato lì)
 * e sono accessibili SOLO dall'admin tramite download-documento.php.
 */
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('it', 'area-soci') . '?sezione=documenti');
    exit;
}

// Deve essere un socio autenticato
$socio = socio_corrente();
if (!$socio) {
    header('Location: ' . url(DEFAULT_LANG, 'area-soci'));
    exit;
}

// CSRF
csrf_verifica();

$lang = isset($_POST['lang']) && in_array($_POST['lang'], $GLOBALS['ASODOMI_LANGS'], true)
    ? $_POST['lang'] : DEFAULT_LANG;
$ritorno = url($lang, 'area-soci') . '?sezione=documenti';

// Definizioni limiti e costanti
$MAX_BYTES = 25 * 1024 * 1024; // 25 MB
$UPLOAD_DIR = __DIR__ . '/uploads/';

if (empty($_FILES['documento']) || $_FILES['documento']['error'] === UPLOAD_ERR_NO_FILE) {
    header('Location: ' . $ritorno . '&upload=err');
    exit;
}

$file = $_FILES['documento'];

// Errore di upload PHP
if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . $ritorno . '&upload=err');
    exit;
}

// Controllo dimensione (incluso post_max_size che produce file > limite)
if ($file['size'] > $MAX_BYTES || $file['size'] <= 0) {
    header('Location: ' . $ritorno . '&upload=type');
    exit;
}

// Nome originale senza problemi di percorso
$nome_originale = basename((string)$file['name']);
$nome_originale = mb_substr($nome_originale, 0, 200) ?: 'documento';
// Estensione sanificata (solo alfanumerico, max 10)
$est = strtolower(pathinfo($nome_originale, PATHINFO_EXTENSION));
$est = preg_replace('/[^a-z0-9]/', '', $est);
$est = mb_substr($est, 0, 10);

// Nome univoco casuale su disco: NNN_<random>.<est> — mai il nome utente
$nome_file = $socio['id'] . '_' . bin2hex(random_bytes(12)) . ($est !== '' ? '.' . $est : '');
$destinazione = $UPLOAD_DIR . $nome_file;

// Verifica / crea cartella
if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0755, true);
}

if (!is_uploaded_file($file['tmp_name'])) {
    header('Location: ' . $ritorno . '&upload=err');
    exit;
}

if (!move_uploaded_file($file['tmp_name'], $destinazione)) {
    header('Location: ' . $ritorno . '&upload=err');
    exit;
}

// Salva i metadati nel DB
try {
    db()->prepare(
        'INSERT INTO documenti_soci (socio_id, nome_originale, nome_file, tipo_mime, dimensione)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        (int)$socio['id'],
        $nome_originale,
        $nome_file,
        $file['type'] !== '' ? mb_substr($file['type'], 0, 120) : '',
        (int)$file['size'],
    ]);
} catch (PDOException $ex) {
    @unlink($destinazione); // rollback: niente record orfano
    error_log('ASODOMI upload insert: ' . $ex->getMessage());
    header('Location: ' . $ritorno . '&upload=err');
    exit;
}

header('Location: ' . $ritorno . '&upload=ok');
exit;
