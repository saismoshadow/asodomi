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

/** Devuelve los eventos futuros (y los últimos pasados) desde content/eventos.php */
function asodomi_eventos(int $limit = 0): array
{
    static $eventos = null;
    if ($eventos === null) {
        $file = dirname(__DIR__) . '/content/eventos.php';
        $eventos = is_file($file) ? require $file : [];
        usort($eventos, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
    }
    $hoy = date('Y-m-d');
    $futuros = array_values(array_filter($eventos, fn($ev) => $ev['fecha'] >= $hoy));
    if ($limit > 0) {
        $futuros = array_slice($futuros, 0, $limit);
    }
    return $futuros;
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
