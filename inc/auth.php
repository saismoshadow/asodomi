<?php
/**
 * Autenticación y seguridad: sesiones, login, roles, CSRF, rate limiting, password reset.
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
            unset($_SESSION['utente_id']);
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

/** Intenta iniciar sesión (admin/redattore); devuelve true/false */
function login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, email, nome, ruolo, password_hash FROM utenti WHERE email = ?');
    $stmt->execute([trim(strtolower($email))]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        if (password_needs_rehash($u['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            db()->prepare('UPDATE utenti SET password_hash = ? WHERE id = ?')->execute([$newHash, $u['id']]);
        }
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
        $stmt = db()->prepare('SELECT id, numero_socio, nome, email, telefono, indirizzo, comune, stato, creato_il FROM soci WHERE id = ?');
        $stmt->execute([$_SESSION['socio_id']]);
        $cache = $stmt->fetch() ?: null;
        if ($cache === null) {
            unset($_SESSION['socio_id']);
        }
    }
    return $cache;
}

/**
 * Verifica rate limiting per login soci
 * @return array ['allowed' => bool, 'retry_after' => string|null, 'attempts' => int]
 */
function socio_check_rate_limit(PDO $pdo, string $ip, ?string $email): array
{
    $ipBin = @inet_pton($ip);
    if ($ipBin === false) {
        $ipBin = str_repeat("\0", 16); // fallback
    }
    
    $stmt = $pdo->prepare('SELECT * FROM login_attempts WHERE ip = ? AND (email = ? OR email IS NULL)');
    $stmt->execute([$ipBin, $email]);
    $record = $stmt->fetch();
    
    $now = new DateTime();
    
    if ($record) {
        if ($record['bloccato_fino'] && new DateTime($record['bloccato_fino']) > $now) {
            return ['allowed' => false, 'retry_after' => $record['bloccato_fino'], 'attempts' => (int)$record['tentativi']];
        }
        if ((int)$record['tentativi'] >= 5) {
            // Blocca per 15 minuti
            $blockUntil = (clone $now)->add(new DateInterval('PT15M'))->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE login_attempts SET bloccato_fino = ? WHERE id = ?')
                ->execute([$blockUntil, $record['id']]);
            return ['allowed' => false, 'retry_after' => $blockUntil, 'attempts' => (int)$record['tentativi']];
        }
    }
    return ['allowed' => true, 'retry_after' => null, 'attempts' => $record ? (int)$record['tentativi'] : 0];
}

/**
 * Registra tentativo di login
 */
function socio_record_login_attempt(PDO $pdo, string $ip, ?string $email, bool $success): void
{
    $ipBin = @inet_pton($ip);
    if ($ipBin === false) {
        $ipBin = str_repeat("\0", 16);
    }
    
    if ($success) {
        $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? AND (email = ? OR email IS NULL)')
            ->execute([$ipBin, $email]);
    } else {
        $pdo->prepare('
            INSERT INTO login_attempts (ip, email, tentativi, primo_tentativo, ultimo_tentativo)
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                tentativi = tentativi + 1,
                ultimo_tentativo = NOW()
        ')->execute([$ipBin, $email]);
    }
}

/** Login socio con rate limiting, honeypot, Argon2ID */
function login_socio(string $email, string $password, ?string $honeypot = null): array
{
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $emailNorm = trim(strtolower($email));
    
    // Honeypot check (se compilato = bot)
    if ($honeypot !== null && $honeypot !== '') {
        error_log('ASODOMI honeypot triggered from IP: ' . $ip . ' for email: ' . $emailNorm);
        // Simula ritardo per non rivelare detection
        usleep(500000); // 0.5 sec
        socio_record_login_attempt($pdo, $ip, $emailNorm, false);
        return ['success' => false, 'error' => 'generic'];
    }
    
    // Rate limiting check
    $rateLimit = socio_check_rate_limit($pdo, $ip, $emailNorm);
    if (!$rateLimit['allowed']) {
        return ['success' => false, 'error' => 'rate_limit', 'retry_after' => $rateLimit['retry_after']];
    }
    
    $stmt = $pdo->prepare('SELECT id, password_hash, stato FROM soci WHERE email = ?');
    $stmt->execute([$emailNorm]);
    $s = $stmt->fetch();
    
    if (!$s || !$s['password_hash'] || $s['stato'] === 'dimesso') {
        socio_record_login_attempt($pdo, $ip, $emailNorm, false);
        return ['success' => false, 'error' => 'generic'];
    }
    
    if (!password_verify($password, $s['password_hash'])) {
        socio_record_login_attempt($pdo, $ip, $emailNorm, false);
        return ['success' => false, 'error' => 'generic'];
    }
    
    // Password corretta - aggiorna hash se necessario (migrazione ad Argon2ID)
    if (password_needs_rehash($s['password_hash'], PASSWORD_ARGON2ID)) {
        $newHash = password_hash($password, PASSWORD_ARGON2ID);
        $pdo->prepare('UPDATE soci SET password_hash = ? WHERE id = ?')->execute([$newHash, $s['id']]);
    }
    
    socio_record_login_attempt($pdo, $ip, $emailNorm, true);
    
    session_regenerate_id(true);
    $_SESSION['socio_id'] = (int)$s['id'];
    
    return ['success' => true];
}

/** Chiude solo la sessione del socio (l'admin resta connesso) */
function logout_socio(): void
{
    unset($_SESSION['socio_id']);
}

/* ── CSRF ──────────────────────────────────────────────────────────── */

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

function csrf_verifica(): void
{
    $ok = isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        exit('Token di sicurezza non valido. Torna indietro e riprova.');
    }
}

/* ── PASSWORD RESET ────────────────────────────────────────────────── */

/**
 * Genera token crittograficamente sicuro per password reset
 * @return array ['token' => string (originale per email), 'token_hash' => string (SHA256 per DB)]
 */
function generate_password_reset_token(): array
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    return ['token' => $token, 'token_hash' => $tokenHash];
}

/**
 * Crea richiesta password reset
 * @return array ['success' => bool, 'error' => string|null, 'generic_message' => string]
 */
function create_password_reset_request(string $email, string $lang): array
{
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $emailNorm = trim(strtolower($email));
    
    // Rate limiting anche per password reset (max 3 richieste/ora per email)
    $ipBin = @inet_pton($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') ?: str_repeat("\0", 16);
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM password_resets 
        WHERE socio_id = (SELECT id FROM soci WHERE email = ?) 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ');
    $stmt->execute([$emailNorm]);
    if ((int)$stmt->fetchColumn() >= 3) {
        return ['success' => false, 'error' => 'rate_limit_reset', 'generic_message' => true];
    }
    
    // Verifica se socio esiste (solo attivo o in_attesa, non dimesso)
    $stmt = $pdo->prepare('SELECT id FROM soci WHERE email = ? AND stato != "dimesso"');
    $stmt->execute([$emailNorm]);
    $socio = $stmt->fetch();
    
    // SEMPRE messaggio generico per evitare account enumeration
    $genericMsg = 'Se l\'indirizzo email è associato a un account, riceverai a breve le istruzioni per reimpostare la password.';
    
    if (!$socio) {
        // Log per audit ma non rivelare
        error_log('ASODOMI password reset request for non-existent email: ' . $emailNorm . ' from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return ['success' => true, 'error' => null, 'generic_message' => $genericMsg];
    }
    
    // Invalida token precedenti non usati per questo socio
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE socio_id = ? AND used_at IS NULL')
        ->execute([$socio['id']]);
    
    // Genera nuovo token
    $tokenData = generate_password_reset_token();
    $expiresAt = (new DateTime())->add(new DateInterval('PT60M'))->format('Y-m-d H:i:s');
    $ipBin = @inet_pton($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') ?: str_repeat("\0", 16);
    
    $stmt = $pdo->prepare('
        INSERT INTO password_resets (socio_id, token_hash, expires_at, request_ip)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$socio['id'], $tokenData['token_hash'], $expiresAt, $ipBin]);
    
    // Invia email (la funzione di invio è separata per separazione responsabilità)
    return [
        'success' => true, 
        'error' => null, 
        'generic_message' => $genericMsg,
        'token' => $tokenData['token'], // solo per invio email
        'socio_id' => $socio['id'],
        'email' => $emailNorm
    ];
}

/**
 * Valida token password reset
 * @return array ['valid' => bool, 'socio_id' => int|null, 'error' => string|null]
 */
function validate_password_reset_token(string $token): array
{
    $pdo = db();
    $tokenHash = hash('sha256', $token);
    
    $stmt = $pdo->prepare('
        SELECT socio_id, expires_at, used_at 
        FROM password_resets 
        WHERE token_hash = ?
    ');
    $stmt->execute([$tokenHash]);
    $record = $stmt->fetch();
    
    if (!$record) {
        return ['valid' => false, 'socio_id' => null, 'error' => 'token_invalido'];
    }
    
    if ($record['used_at'] !== null) {
        return ['valid' => false, 'socio_id' => null, 'error' => 'token_gia_usato'];
    }
    
    if (new DateTime($record['expires_at']) < new DateTime()) {
        return ['valid' => false, 'socio_id' => null, 'error' => 'token_scaduto'];
    }
    
    return ['valid' => true, 'socio_id' => (int)$record['socio_id'], 'error' => null];
}

/**
 * Completa password reset
 * @return array ['success' => bool, 'error' => string|null]
 */
function complete_password_reset(string $token, string $newPassword): array
{
    $pdo = db();
    $validation = validate_password_reset_token($token);
    
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'password_corta'];
    }
    
    // Hash con Argon2ID (PHP 8.5+)
    $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
    
    $pdo->beginTransaction();
    try {
        // Aggiorna password socio
        $pdo->prepare('UPDATE soci SET password_hash = ? WHERE id = ?')
            ->execute([$hash, $validation['socio_id']]);
        
        // Marca token come usato
        $tokenHash = hash('sha256', $token);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?')
            ->execute([$tokenHash]);
        
        // Invalida eventuali altri token attivi per questo socio
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE socio_id = ? AND used_at IS NULL')
            ->execute([$validation['socio_id']]);
        
        // Invalida sessioni esistenti per questo socio (forza nuovo login)
        $pdo->prepare('DELETE FROM login_attempts WHERE email = (SELECT email FROM soci WHERE id = ?)')
            ->execute([$validation['socio_id']]);
        
        $pdo->commit();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('ASODOMI password reset error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'db_error'];
    }
}

/* ── Sanitización de contenido para artículos ─────────────────────── */

function sanitizza_html(string $html): string
{
    $html = preg_replace('#<(script|style|iframe|object|embed|form)[^>]*>.*?</\1>#is', '', $html);
    $permitidas = '<p><br><b><strong><i><em><u><h2><h3><h4><ul><ol><li><a><blockquote><figure><figcaption><img><hr><table><tr><td><th><tbody>';
    $html = strip_tags($html, $permitidas);
    $html = preg_replace_callback('#<([a-z]+)([^>]*)>#i', function ($m) {
        $attrs = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $m[2]);
        $attrs = preg_replace('#(href|src)\s*=\s*("|\'?)\s*(javascript|data)(:[^"\'>\s]*)?#i', '$2', $attrs);
        return '<' . strtolower($m[1]) . $attrs . '>';
    }, $html);
    return trim($html);
}

function video_embed(string $url): ?array
{
    $url = trim($url);
    if ($url === '') return null;
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~', $url, $m)) {
        return ['tipo' => 'youtube', 'id' => $m[1], 'embed' => 'https://www.youtube-nocookie.com/embed/' . $m[1]];
    }
    if (preg_match('~vimeo\.com/(\d{6,12})~', $url, $m)) {
        return ['tipo' => 'vimeo', 'id' => $m[1], 'embed' => 'https://player.vimeo.com/video/' . $m[1]];
    }
    return null;
}

function crea_slug(string $titolo, int $ignoreId = 0): string
{
    $slug = strtolower(trim($titolo));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') $slug = 'articolo';
    $base = $slug;
    $n = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM articoli WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $ignoreId]);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . (++$n);
    }
}
