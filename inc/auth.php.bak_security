<?php
/**
 * Autenticación y seguridad: sesiones, login, roles, CSRF.
 */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

/** Usuario conectado (array) o null */
function utente_corrente(): ?array
{
    if (empty($_SESSION['utente_id'])) {
        return null;
    }
    static $cache = false;
    if ($cache === false) {
        $stmt = db()->prepare('SELECT id, email, nome, ruolo FROM utenti WHERE id = ?');
        $stmt->execute([$_SESSION['utente_id']]);
        $cache = $stmt->fetch() ?: null;
        if ($cache === null) {
            unset($_SESSION['utente_id']); // usuario borrado mientras estaba conectado
        }
    }
    return $cache;
}

/** Exige una sesión con rol concreto; si no, redirige al login */
function richiedi_ruolo(array $ruoli): array
{
    $u = utente_corrente();
    if (!$u || !in_array($u['ruolo'], $ruoli, true)) {
        header('Location: ' . admin_url('login'));
        exit;
    }
    return $u;
}

/** Intenta iniciar sesión; devuelve true/false */
function login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, email, nome, ruolo, password_hash FROM utenti WHERE email = ?');
    $stmt->execute([trim(strtolower($email))]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['utente_id'] = (int)$u['id'];
        db()->prepare('UPDATE utenti SET ultimo_accesso = NOW() WHERE id = ?')->execute([$u['id']]);
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ── Accesso SOCI (area riservata) ─────────────────────────────────── */

/** Socio connesso (array) o null */
function socio_corrente(): ?array
{
    if (empty($_SESSION['socio_id'])) {
        return null;
    }
    static $cache = false;
    if ($cache === false) {
        $stmt = db()->prepare('SELECT id, nome, email, telefono, indirizzo, comune, stato, creato_il FROM soci WHERE id = ?');
        $stmt->execute([$_SESSION['socio_id']]);
        $cache = $stmt->fetch() ?: null;
        if ($cache === null) {
            unset($_SESSION['socio_id']);
        }
    }
    return $cache;
}

/** Login socio: true se le credenziali sono valide e lo stato lo consente */
function login_socio(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash, stato FROM soci WHERE email = ?');
    $stmt->execute([trim(strtolower($email))]);
    $s = $stmt->fetch();
    if (!$s || !$s['password_hash'] || $s['stato'] === 'dimesso') {
        return false;
    }
    if (!password_verify($password, $s['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['socio_id'] = (int)$s['id'];
    return true;
}

/** Chiude solo la sessione del socio (l'admin resta connesso) */
function logout_socio(): void
{
    unset($_SESSION['socio_id']);
}

/** Token CSRF para formularios */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verifica el token CSRF de un POST; corta la ejecución si no es válido */
function csrf_verifica(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        exit('Token di sicurezza non valido. Torna indietro e riprova.');
    }
}

/* ── Sanitización de contenido para artículos ─────────────────────── */

/** Limpia HTML permitido para el cuerpo de los artículos */
function sanitizza_html(string $html): string
{
    // quitar bloques peligrosos completos
    $html = preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#is', '', $html);
    // quitar etiquetas no permitidas
    $permitidas = '<p><br><b><strong><i><em><u><h2><h3><h4><ul><ol><li><a><blockquote><figure><figcaption><img><hr><table><tr><td><th><tbody>';
    $html = strip_tags($html, $permitidas);
    // quitar atributos on* y javascript:
    $html = preg_replace_callback('#<([a-z]+)([^>]*)>#i', function ($m) {
        $attrs = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $m[2]);
        $attrs = preg_replace('#(href|src)\s*=\s*("|\'?)\s*(javascript|data)(:[^"\'>\s]*)?#i', '$2', $attrs);
        return '<' . strtolower($m[1]) . $attrs . '>';
    }, $html);
    return trim($html);
}

/** Convierte un enlace YouTube/Vimeo en URL de embed; null si no es válido */
function video_embed(string $url): ?array
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    // YouTube: watch?v=, youtu.be/, shorts/, embed/
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~', $url, $m)) {
        return ['tipo' => 'youtube', 'id' => $m[1], 'embed' => 'https://www.youtube-nocookie.com/embed/' . $m[1]];
    }
    // Vimeo: vimeo.com/123456789
    if (preg_match('~vimeo\.com/(\d{6,12})~', $url, $m)) {
        return ['tipo' => 'vimeo', 'id' => $m[1], 'embed' => 'https://player.vimeo.com/video/' . $m[1]];
    }
    return null;
}

/** Slug amigable a partir de un título */
function crea_slug(string $titolo, int $ignoreId = 0): string
{
    $slug = strtolower(trim($titolo));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'articolo';
    }
    $base = $slug;
    $n = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM articoli WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $ignoreId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . (++$n);
    }
}
