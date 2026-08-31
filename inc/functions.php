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

/**
 * Convierte URL nudos (http/https) que no estén ya dentro de un <a ...>...</a>
 * en enlaces clicables con target="_blank" rel="noopener noreferrer".
 * Escapa el texto de entrada (una sola vez) y deja intactas las etiquetas HTML
 * y los enlaces ya existentes. Usar sobre contenido/HTML en crudo (no escapado).
 */
function asodomi_linkify(?string $html): string
{
    $html = (string)$html;
    if ($html === '') {
        return $html;
    }
    $out = '';
    // Divide el HTML en etiquetas <...> y texto plano
    $partes = preg_split('~(<[^>]+>)~', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $dentro_a = false;
    foreach ($partes as $parte) {
        if ($parte === '') {
            continue;
        }
        if (preg_match('~^<[^>]+>$~', $parte)) {
            // Es una etiqueta: se deja tal cual
            $out .= $parte;
            if (preg_match('~^<a\b~i', $parte)) {
                $dentro_a = true;
            } elseif (preg_match('~^</a~i', $parte)) {
                $dentro_a = false;
            }
            continue;
        }
        // Texto plano: escapar y convertir URLs nudos (solo si no estamos dentro de <a>)
        if ($dentro_a) {
            $out .= htmlspecialchars($parte, ENT_QUOTES, 'UTF-8');
            continue;
        }
        // Dividimos el texto por URLs: escapamos lo que no es URL y creamos el link en la URL
        $trozos = preg_split(
            '~((?:https?://)[^\s<>\'\"\[\]]+[^\s<>\'\".),:;!?\[\]])~i',
            $parte,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        foreach ($trozos as $trozo) {
            if ($trozo === '') {
                continue;
            }
            if (preg_match('~^https?://~i', $trozo)) {
                $esc = htmlspecialchars($trozo, ENT_QUOTES, 'UTF-8');
                $out .= '<a href="' . $esc . '" target="_blank" rel="noopener noreferrer">' . $esc . '</a>';
            } else {
                $out .= htmlspecialchars($trozo, ENT_QUOTES, 'UTF-8');
            }
        }
    }
    return $out;
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
