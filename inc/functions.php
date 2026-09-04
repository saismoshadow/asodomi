<?php
/**
 * Funciones auxiliares de ASODOMI
 */

require_once __DIR__ . '/config.php';

// Fallback si el hosting no tiene la extensión mbstring
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $length = null): string
    {
        return $length === null ? substr($s, $start) : substr($s, $start, $length);
    }
}

/** Carga el archivo de idioma y devuelve el array de textos */
function asodomi_load_lang(string $lang): array
{
    $lang = in_array($lang, $GLOBALS['ASODOMI_LANGS'], true) ? $lang : DEFAULT_LANG;
    $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
    if (!is_file($file)) {
        $lang = DEFAULT_LANG;
        $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
    }
    /** @var array $texts */
    $texts = require $file;
    return $texts;
}

/** Escapa texto para HTML */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Base de la instalación: '' en la raíz del dominio, '/carpeta' si está en un subdirectorio */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        // public_html = dos niveles arriba desde inc/
        $dir   = str_replace('\\', '/', dirname(__DIR__, 2));
        $root  = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
        $base  = '';
        if ($root !== '' && stripos($dir, $root) === 0) {
            $base = substr($dir, strlen($root));
        }
    }
    return $base;
}

/** Construye una URL limpia: url('es','servicios') => /es/servicios */
function url(string $lang, string $page = ''): string
{
    return base_url() . '/' . $lang . ($page !== '' ? '/' . $page : '');
}

/** Ruta a un recurso estático: asset('/enviar.php') */
function asset(string $path): string
{
    return base_url() . $path;
}

/** Igual que asset() pero añade ?v=fecha de modificación para romper la cache */
function asset_v(string $path): string
{
    $file = dirname(__DIR__) . $path;
    $v = is_file($file) ? filemtime($file) : 0;
    return base_url() . $path . '?v=' . $v;
}

/** Obtiene el idioma y la página actuales desde la URL (con validación) */
function asodomi_route(): array
{
    $lang = isset($_GET['lang']) ? preg_replace('/[^a-z]/', '', strtolower($_GET['lang'])) : '';
    $page = isset($_GET['page']) ? preg_replace('/[^a-z-]/', '', strtolower($_GET['page'])) : '';

    if (!in_array($lang, $GLOBALS['ASODOMI_LANGS'], true)) {
        $lang = DEFAULT_LANG;
    }
    if (!in_array($page, $GLOBALS['ASODOMI_PAGES'], true)) {
        $page = 'inicio';
    }
    return [$lang, $page];
}

/** Eventi prossimi (pubblicati, non in archivio, con data >= oggi) dalla tabella `eventi` */
function asodomi_eventos(int $limit = 0): array
{
    $sql = 'SELECT * FROM eventi
            WHERE stato = "pubblicato" AND archiviato = 0 AND data >= CURDATE()
            ORDER BY data ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int)$limit;
    }
    return db()->query($sql)->fetchAll();
}

/** Notizie pubblicate (stato = pubblicato) dalla tabella `notizie` */
function asodomi_notizie(int $limit = 0): array
{
    $sql = 'SELECT * FROM notizie
            WHERE stato = "pubblicato"
            ORDER BY COALESCE(aggiornato_il, creato_il) DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int)$limit;
    }
    return db()->query($sql)->fetchAll();
}

/** Percorso relativo di un file media in /uploads (sanificado) */
function asodomi_media_url(?string $nome_file): string
{
    $nome_file = trim((string)$nome_file);
    if ($nome_file === '') {
        return '';
    }
    return asset('/uploads/' . basename((string)$nome_file));
}

/** Etichetta leggibile della modalità di un evento */
function asodomi_modalita(string $modalita): string
{
    $map = [
        'presenza' => 'In presenza',
        'online'   => 'Online',
        'mista'    => 'Mista (online e in presenza)',
    ];
    return $map[$modalita] ?? 'In presenza';
}

/** Icona/emoji per la modalità di un evento */
function asodomi_modalita_icona(string $modalita): string
{
    $map = [
        'presenza' => '📍',
        'online'   => '💻',
        'mista'    => '🔄',
    ];
    return $map[$modalita] ?? '📍';
}

/** Formatea una fecha YYYY-MM-DD según el idioma */
function asodomi_fecha(string $ymd, string $lang): string
{
    $ts = strtotime($ymd);
    if ($ts === false) return $ymd;
    $map = ['es' => 'd/m/Y', 'de' => 'd.m.Y', 'fr' => 'd.m.Y', 'it' => 'd/m/Y'];
    $fmt = $map[$lang] ?? 'd/m/Y';
    return date($fmt, $ts);
}


/**
 * Genera URL per l'area admin.
 */
function admin_url(string $route = ''): string
{
    return asset('/admin/') . ($route !== '' ? '?route=' . rawurlencode($route) : '');
}
