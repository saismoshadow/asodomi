<?php
/**
 * ASODOMI – Gestione upload di media (immagini/video) per eventi e notizie.
 * Include il backend di upload riusabile. I file finiscono in /uploads
 * (PHP disattivato lì, accessibile in sola lettura dal sito).
 */

const ASODOMI_EXT_IMMAGINE = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const ASODOMI_EXT_VIDEO    = ['mp4', 'webm'];

const ASODOMI_UPLOAD_MAX   = 50 * 1024 * 1024; // 50 MB
const ASODOMI_UPLOAD_DIR   = __DIR__ . '/../uploads/';

/**
 * Gestisce un campo <input type="file"> per un media (immagine o video).
 *
 * @param string $campo nome del campo file in $_FILES
 * @param string &$errore  messaggio d'errore (vuoto se ok)
 * @return string|null  nome file salvato in uploads, o null se non c'è file o errore
 */
function asodomi_handle_media_upload(string $campo, string &$errore): ?string
{
    $errore = '';

    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // nessun file inviato (facoltativo)
    }

    $file = $_FILES[$campo];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errore = 'Errore durante il caricamento del file.';
        return null;
    }
    if ($file['size'] <= 0 || $file['size'] > ASODOMI_UPLOAD_MAX) {
        $errore = 'File troppo grande (massimo 50 MB).';
        return null;
    }

    // Estensione sanificata
    $nome_originale = mb_substr((string)basename((string)$file['name']), 0, 200) ?: 'media';
    $est = strtolower(pathinfo($nome_originale, PATHINFO_EXTENSION));
    $est = preg_replace('/[^a-z0-9]/', '', $est);
    $est = mb_substr($est, 0, 10);

    if (!in_array($est, array_merge(ASODOMI_EXT_IMMAGINE, ASODOMI_EXT_VIDEO), true)) {
        $errore = 'Formato non supportato (usa jpg, png, gif, webp per le immagini; mp4, webm per i video).';
        return null;
    }

    $nome_file = 'media_' . bin2hex(random_bytes(12)) . ($est !== '' ? '.' . $est : '');
    $dest = ASODOMI_UPLOAD_DIR . $nome_file;

    if (!is_dir(ASODOMI_UPLOAD_DIR)) {
        @mkdir(ASODOMI_UPLOAD_DIR, 0755, true);
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        $errore = 'File non valido.';
        return null;
    }
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $errore = 'Impossibile salvare il file.';
        return null;
    }

    return $nome_file;
}
