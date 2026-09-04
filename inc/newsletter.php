<?php
/**
 * ASODOMI – Newsletter: helper condivisi (pubblico e admin).
 * Token, rate-limit, invio email, log, rendering footer compatibile.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

// PHPMailer per invio SMTP (vendor/PHPMailer). File caricano solo se presenti.
$__pm = __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
if (is_file($__pm)) {
    require_once $__pm;
    require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';
    require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';
}

/* ── Costanti di configurazione (override possibili in config.php) ── */

if (!defined('NL_SENDER_NAME'))  define('NL_SENDER_NAME', SITE_NAME);
if (!defined('NL_SENDER_EMAIL')) define('NL_SENDER_EMAIL', CONTACT_EMAIL);
if (!defined('NL_REPLY_TO'))     define('NL_REPLY_TO', CONTACT_EMAIL);
if (!defined('NL_BATCH_SIZE'))   define('NL_BATCH_SIZE', 50);
if (!defined('NL_CONFIRM_TTL'))  define('NL_CONFIRM_TTL', '+72 hours');
if (!defined('NL_UNSUB_TTL'))    define('NL_UNSUB_TTL', '+2 years');

/**
 * Genera un URL assoluto verso un endpoint di primo livello (per email/redirect).
 * Es.: nl_url_pubblica('newsletter_conferma', ['token' => ...], $lang)
 */
function nl_url_pubblica(string $script, array $query = [], string $lang = ''): string
{
    $base = isset($_SERVER['HTTP_HOST'])
        ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(base_url(), '/')
        : (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '');

    if ($lang === '') {
        $lang = isset($GLOBALS['current_lang']) ? $GLOBALS['current_lang'] : DEFAULT_LANG;
    }
    $url = $base . '/' . rawurlencode($script);
    $query['lang'] = $lang;
    $qs = http_build_query($query, '', '&');
    return $url . '?' . $qs;
}

/** Recupera/o genera il percorso del log newsletter. */
function nl_log_file(): string
{
    $dir = dirname(__DIR__) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . '/newsletter.log';
}

/** Scrive una riga nel log (mai token completi né password). */
function nl_log(string $message, string $level = 'INFO'): void
{
    $line = date('Y-m-d H:i:s') . ' [' . $level . '] ' . $message . PHP_EOL;
    @file_put_contents(nl_log_file(), $line, FILE_APPEND | LOCK_EX);
}

/** Genera un token sicuro da usare negli URL (32 byte -> 64 hex). */
function nl_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Rate-limit sull'iscrizione: max N azioni per IP in una finestra.
 * True = consenti l'azione; false = superato il limite.
 */
function nl_rate_permesso(string $ip, int $max = 5, int $finestraMinuti = 60): bool
{
    $ip = (string)$ip;
    if ($ip === '' || $ip === '0.0.0.0') {
        return true; // non penalizzare chi non espone IP (test in VM)
    }
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE ip = INET6_ATON(?) AND ultimo_tentativo >= DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->execute([$ip, $finestraMinuti]);
    return ((int)$stmt->fetchColumn()) < $max;
}

/** Registra un tentativo per il rate-limit. */
function nl_rate_registra(string $ip): void
{
    $ip = (string)$ip;
    if ($ip === '' || $ip === '0.0.0.0') {
        return;
    }
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (ip, email, tentativi, primo_tentativo, ultimo_tentativo)
         VALUES (INET6_ATON(?), NULL, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE tentativi = tentativi + 1, ultimo_tentativo = NOW()'
    );
    $stmt->execute([$ip]);
}

/**
 * Valida e normalizza un indirizzo email.
 * Ritorna la versione minuscola oppure '' se non valida.
 */
function nl_email_valida(?string $email): string
{
    $email = trim(strtolower((string)$email));
    if ($email === '' || strlen($email) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    return $email;
}

/**
 * Crea o aggiorna un iscritto (doppio opt-in).
 * - Crea la riga con status='pending' e token di conferma.
 * - Se l'email esiste già e NON è stato ancora confermato, rigenera il token.
 * - Se è già confermato/attivo, non rimanda (salvo $forza).
 * Ritorna: array ['ok'=>bool, 'status'=>string, 'messaggio'=>string, 'id'=>int]
 */
function nl_iscrivi(string $email, string $nome = '', string $cognome = '', string $lingua = '', string $canale = 'modulo', string $ip = '', bool $forza = false): array
{
    $email = nl_email_valida($email);
    if ($email === '') {
        return ['ok' => false, 'status' => 'invalida', 'messaggio' => 'email_invalida', 'id' => 0];
    }
    $pdo = db();
    if ($lingua === '' || !in_array($lingua, $GLOBALS['ASODOMI_LANGS'], true)) {
        $lingua = DEFAULT_LANG;
    }

    $stmt = $pdo->prepare('SELECT id, status, token_unsubscribe FROM newsletter_iscritti WHERE email = ?');
    $stmt->execute([$email]);
    $esistente = $stmt->fetch();

    if ($esistente) {
        if ($esistente['status'] === 'confirmed' && !$forza) {
            return ['ok' => false, 'status' => 'gia_attivo', 'messaggio' => 'gia_iscritto', 'id' => (int)$esistente['id']];
        }
        // pending / unsubscribed / cleaned -> riscrivi come pending con nuovo token
        $confirmToken = nl_token();
        $pdo->prepare(
            'UPDATE newsletter_iscritti
             SET nome = ?, cognome = ?, status = "pending", attivo = 0,
                 confirmation_token = ?, confirmation_expires = DATE_ADD(NOW(), INTERVAL ' . (int)substr(NL_CONFIRM_TTL, 1, 8) . ' HOUR),
                 confirmed_at = NULL, unsubscribed_at = NULL, consenso_data = NOW(), lingua = ?
             WHERE id = ?'
        )->execute([trim($nome), trim($cognome), $confirmToken, $lingua, (int)$esistente['id']]);
        $id = (int)$esistente['id'];
    } else {
        $confirmToken = nl_token();
        $unsubToken  = nl_token();
        $pdo->prepare(
            'INSERT INTO newsletter_iscritti
                (email, nome, cognome, lingua, status, attivo, confirmation_token, confirmation_expires, token_unsubscribe, consenso_data)
             VALUES (?, ?, ?, ?, "pending", 0, ?, DATE_ADD(NOW(), INTERVAL ' . (int)substr(NL_CONFIRM_TTL, 1, 8) . ' HOUR), ?, NOW())'
        )->execute([$email, trim($nome), trim($cognome), $lingua, $confirmToken, $unsubToken]);
        $id = (int)$pdo->lastInsertId();
    }

    // Log consenso (GDPR)
    $pdo->prepare(
        'INSERT INTO newsletter_consensi (subscriber_id, email, ip, consenso, canale) VALUES (?, ?, ?, 1, ?)'
    )->execute([$id, $email, $ip, $canale]);

    return ['ok' => true, 'status' => 'pending', 'messaggio' => 'da_confermare', 'id' => $id, 'token' => $confirmToken];
}

/** Conferma l'iscrizione tramite token. Ritorna true/false. */
function nl_conferma(string $token): bool
{
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        return false;
    }
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id FROM newsletter_iscritti
         WHERE confirmation_token = ? AND status = "pending" AND attivo = 0
           AND (confirmation_expires IS NULL OR confirmation_expires > NOW())'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    $pdo->prepare(
        'UPDATE newsletter_iscritti
         SET status = "confirmed", attivo = 1, confirmed_at = NOW(),
             confirmation_token = NULL, confirmation_expires = NULL
         WHERE id = ?'
    )->execute([(int)$row['id']]);
    return true;
}

/** Invalida manualmente un token di conferma (doppio uso). */
function nl_invalida_token(string $token): void
{
    db()->prepare('UPDATE newsletter_iscritti SET confirmation_token = NULL, confirmation_expires = NULL WHERE confirmation_token = ?')
        ->execute([$token]);
}

/**
 * Disiscrizione tramite token (senza login). Aggiorna stato e attivo.
 */
function nl_disiscrivi(string $token, string $ip = ''): bool
{
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        return false;
    }
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM newsletter_iscritti WHERE token_unsubscribe = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    $pdo->prepare(
        'UPDATE newsletter_iscritti
         SET status = "unsubscribed", attivo = 0, unsubscribed_at = NOW()
         WHERE id = ?'
    )->execute([(int)$row['id']]);
    return true;
}

/**
 * Costruisce una email HTML compatibile (Gmail/Outlook/Apple/Thunderbird),
 * senza JS, con CSS inline, footer con associazione + indirizzo + unsub.
 */
function nl_body_html(string $html, string $unsubscribeUrl, string $lang = ''): string
{
    $lang = ($lang !== '' && in_array($lang, $GLOBALS['ASODOMI_LANGS'], true)) ? $lang : DEFAULT_LANG;
    $t = asodomi_load_lang($lang);
    $nwl = $t['newsletter'] ?? [];

    $unsubText  = $nwl['unsub_testo'] ?? 'Disiscrivimi dalla newsletter';
    $assoc      = SITE_FULL_NAME;
    $indirizzo  = CONTACT_ADDRESS;

    $html = (string)$html;
    // Aggiungi prefisso/eredità: avvolgi nel layout email se non è già completo
    if (stripos($html, '<html') === false) {
        $html =
            '<!DOCTYPE html><html><body style="margin:0;padding:0;background-color:#f2f4f8;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f8;padding:24px 0;"><tr><td>'
            . '<table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e2e6ee;">'
            . '<tr><td style="padding:24px 28px;border-bottom:4px solid #c8102e;">'
            . '<strong style="color:#0b3d91;font-size:22px;">ASO<span style="color:#c8102e;">DOMI</span></strong>'
            . '<span style="color:#6a7180;font-size:12px;display:block;margin-top:2px;">' . $assoc . '</span>'
            . '</td></tr>'
            . '<tr><td style="padding:28px;">' . $html . '</td></tr>'
            . '<tr><td style="padding:20px 28px;background:#f7f8fa;border-top:1px solid #e2e6ee;color:#6a7180;font-size:12px;line-height:1.6;">'
            . $indirizzo . '<br>'
            . '<a href="' . nl_e_attr($unsubscribeUrl) . '" style="color:#c8102e;">' . nl_e_attr($unsubText) . '</a>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
    return $html;
}

/** Escape per attributi HTML in un contesto di stringa (doppio escape di &). */
function nl_e_attr(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Invia UNA email. Ritorna true su successo, false altrimenti.
 * Non lancia eccezioni per non rompere il worker.
 */
function nl_invia(string $to, string $oggetto, string $htmlBody, string $fromName = '', string $fromEmail = '', string $replyTo = ''): bool
{
    $fromName  = $fromName  ?: NL_SENDER_NAME;
    $fromEmail = $fromEmail ?: NL_SENDER_EMAIL;
    $replyTo   = $replyTo   ?: NL_REPLY_TO;

    // Header injection guard: niente CR/LF nei campi
    $to       = str_replace(["\r", "\n"], '', $to);
    $oggetto  = str_replace(["\r", "\n"], '', $oggetto);
    $fromName = str_replace(["\r", "\n"], '', $fromName);
    $fromEmail= str_replace(["\r", "\n"], '', $fromEmail);
    $replyTo  = str_replace(["\r", "\n"], '', $replyTo);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        nl_log('Invio abortito: destinatario o mittente non validi.', 'ERRORE');
        return false;
    }

    // ── SMTP con PHPMailer (se disponibile e configurato) ──────────────────
    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && defined('SMTP_HOST')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->Port       = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;
            $mail->SMTPAuth   = (bool)(defined('SMTP_AUTH') ? SMTP_AUTH : true);
            $mail->Username   = defined('SMTP_USER') ? SMTP_USER : '';
            $mail->Password   = defined('SMTP_PASS') ? SMTP_PASS : '';
            $secure = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : 'starttls';
            if ($secure === 'ssl' || $secure === 'tls') {
                $mail->SMTPSecure = $secure === 'ssl' ? 'ssl' : 'tls';
            } // 'starttls'/altro -> lasciato PHPMailer default 'tls'
            $mail->Timeout = 20;
            $mail->CharSet = 'UTF-8';
            if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
                $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addReplyTo($replyTo, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $oggetto;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = nl_plain_from_html($htmlBody);

            $sent = $mail->send();
            return $sent;
                } catch (\Throwable $e) {
            nl_log("SMTP fallito verso " . $to . ": " . $e->getMessage(), "ERRORE");
            // Fallback to mail() if SMTP fails
        }
    }

    // ── Fallback: mail() di sistema (solo se PHPMailer non disponibile) ─────
    $encodedSubject = '=?UTF-8?B?' . base64_encode($oggetto) . '?=';
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'To: ' . $to,
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'X-Mailer: ASODOMI-Newsletter',
    ]);

    $sent = @mail($to, $encodedSubject, base64_encode($htmlBody), $headers);
    if (!$sent) {
        nl_log('mail() fallita verso ' . $to, 'ERRORE');
    }
    return $sent;
}

/**
 * Registra un esito nel log invii (no token completi; email ok).
 */
function nl_log_invio(int $campaignId, ?int $subscriberId, string $email, string $tipo, string $status, ?string $errore = null): void
{
    $errore = $errore !== null ? mb_substr($errore, 0, 490) : null;
    try {
        db()->prepare(
            'INSERT INTO newsletter_campaign_logs (campaign_id, subscriber_id, email, tipo, status, errore)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), errore = VALUES(errore), sent_at = NOW()'
        )->execute([$campaignId, $subscriberId, $email, $tipo, $status, $errore]);
    } catch (Exception $ex) {
        nl_log('Errore scrivendo log invio: ' . $ex->getMessage(), 'ERRORE');
    }
}

/** Numero di iscritti eleggibili (confirmed/attivi). */
function nl_conta_attivi(): int
{
    return (int)db()->query("SELECT COUNT(*) FROM newsletter_iscritti WHERE status = 'confirmed' AND attivo = 1")->fetchColumn();
}

/**
 * Bozza di contenuto testuale (plain) partendo dall'HTML.
 * Strip dei tag e decodifica entità.
 */
function nl_plain_from_html(string $html): string
{
    $plain = strip_tags($html);
    $plain = html_entity_decode($plain, ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/[ \t]+/', ' ', $plain);
    $plain = preg_replace('/\n\s*\n+/', "\n\n", $plain);
    return trim($plain);
}
