<?php
// Buffer de salida antes de cualquier require: garantiza que ningún BOM,
// aviso o contenido emitido por los archivos incluidos rompa los header('Location').
if (!ob_get_level()) {
    ob_start();
}

/**
 * ASODOMI – Procesamiento de los formularios (ayuda y cita).
 * Recibe el POST, valida, envía el email y redirige de vuelta.
 */

require_once __DIR__ . '/inc/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url(DEFAULT_LANG, 'inicio'));
    exit;
}

// ── Anti-spam: campo trampa ──────────────────────────────────────────
if (!empty($_POST['website'])) {
    header('Location: ' . url(DEFAULT_LANG, 'inicio'));
    exit;
}

// ── Datos básicos ────────────────────────────────────────────────────
$lang = isset($_POST['lang']) && in_array($_POST['lang'], $GLOBALS['ASODOMI_LANGS'], true)
    ? $_POST['lang'] : DEFAULT_LANG;
$tipo = in_array($_POST['form_type'] ?? '', ['cita', 'ayuda', 'socio'], true)
    ? $_POST['form_type'] : 'ayuda';
$volver = url($lang, $tipo === 'socio' ? 'iscrizione' : $tipo);

/** Limpia un valor de texto */
function limpiar(string $campo, int $max = 300): string
{
    $v = trim((string)($_POST[$campo] ?? ''));
    $v = strip_tags($v);
    $v = str_replace(["\r", "\n"], ' ', $v); // evita inyección de cabeceras
    return mb_substr($v, 0, $max);
}

$nombre   = limpiar('nombre', 120);
$email    = limpiar('email', 160);
$telefono = limpiar('telefono', 40);
$mensaje  = trim(strip_tags((string)($_POST['mensaje'] ?? '')));
$mensaje  = mb_substr($mensaje, 0, 3000);

// ── Validación mínima ────────────────────────────────────────────────
if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: $volver?err=1");
    exit;
}

// ── Contenido según tipo de formulario ───────────────────────────────
if ($tipo === 'socio') {
    $telefono = limpiar('telefono', 40);
    if ($telefono === '' || empty($_POST['consenso'])) {
        header("Location: $volver?err=1");
        exit;
    }

    require_once __DIR__ . '/inc/db.php';

    $nome      = limpiar('nombre', 160);
    $email     = strtolower(limpiar('email', 160));
    $indirizzo = limpiar('indirizzo', 200);
    $comune    = limpiar('comune', 120);

    $modifica  = (int)($_POST['socio_id'] ?? 0);
    $password  = (string)($_POST['password'] ?? '');

    // ── Modalità "modifica dati": UPDATE invece di INSERT ─────────────
    if ($modifica > 0) {
        if ($password !== '' && strlen($password) < 8) {
            header("Location: $volver?modifica=1&id=$modifica&err=1");
            exit;
        }
        try {
            $stmt = db()->prepare('SELECT id FROM soci WHERE email = ? AND id != ?');
            $stmt->execute([$email, $modifica]);
            if ($stmt->fetch()) {
                header("Location: $volver?modifica=1&id=$modifica&dup=1");
                exit;
            }
            if ($password !== '') {
                db()->prepare(
                    'UPDATE soci SET nome = ?, email = ?, telefono = ?, indirizzo = ?, comune = ?, password_hash = ? WHERE id = ?'
                )->execute([$nome, $email, $telefono, $indirizzo, $comune, password_hash($password, PASSWORD_DEFAULT), $modifica]);
            } else {
                db()->prepare(
                    'UPDATE soci SET nome = ?, email = ?, telefono = ?, indirizzo = ?, comune = ? WHERE id = ?'
                )->execute([$nome, $email, $telefono, $indirizzo, $comune, $modifica]);
            }
            header("Location: $volver?ok=1&id=$modifica");
            exit;
        } catch (PDOException $ex) {
            error_log('ASODOMI socio update: ' . $ex->getMessage());
            header("Location: $volver?modifica=1&id=$modifica&err=1");
            exit;
        }
    }

    // ── Controllo duplicati: il socio con questa email esiste già ─────
    try {
        $stmt = db()->prepare('SELECT id FROM soci WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: $volver?dup=1&email=" . rawurlencode($email));
            exit;
        }
    } catch (PDOException $ex) {
        error_log('ASODOMI socio dup check: ' . $ex->getMessage());
    }

    if (strlen($password) < 8) {
        header("Location: $volver?err=1");
        exit;
    }

    try {
        db()->prepare(
            'INSERT INTO soci (nome, email, telefono, indirizzo, comune, password_hash, stato, consenso_privacy)
             VALUES (?, ?, ?, ?, ?, ?, "attivo", 1)'
        )->execute([$nome, $email, $telefono, $indirizzo, $comune, password_hash($password, PASSWORD_DEFAULT)]);
        $socio_id = (int)db()->lastInsertId();
    } catch (PDOException $ex) {
        error_log('ASODOMI socio insert: ' . $ex->getMessage());
        header("Location: $volver?err=1");
        exit;
    }
    $asunto = MAIL_SUBJECT_SOCIO;
    $lineas = [
        'Nuova iscrizione socio da asodomi.com',
        str_repeat('-', 46),
        'Nome:     ' . limpiar('nombre', 160),
        'Email:    ' . strtolower(limpiar('email', 160)),
        'Telefono: ' . $telefono,
        'Indirizzo: ' . (limpiar('indirizzo', 200) !== '' ? limpiar('indirizzo', 200) : '(non indicato)'),
        'Comune:   ' . limpiar('comune', 120),
        str_repeat('-', 46),
        'Gestisci i soci nel pannello: /admin',
    ];
} elseif ($tipo === 'cita') {
    if ($telefono === '') {
        header("Location: $volver?err=1");
        exit;
    }
    $asunto = MAIL_SUBJECT_CITA;
    $lineas = [
        'Nueva reserva de cita desde asodomi.com',
        str_repeat('-', 46),
        'Nombre:     ' . $nombre,
        'Email:      ' . $email,
        'Teléfono:   ' . $telefono,
        'Servicio:   ' . limpiar('servicio', 120),
        'Fecha pref.: ' . limpiar('fecha', 20),
        'Horario:    ' . limpiar('horario', 60),
        'Modalidad:  ' . limpiar('modalidad', 40),
        str_repeat('-', 46),
        'Mensaje:',
        wordwrap($mensaje !== '' ? $mensaje : '(sin mensaje adicional)', 70),
    ];
} else {
    $asunto = MAIL_SUBJECT_AYUDA;
    $lineas = [
        'Nueva solicitud de ayuda desde asodomi.com',
        str_repeat('-', 46),
        'Nombre:     ' . $nombre,
        'Email:      ' . $email,
        'Teléfono:   ' . ($telefono !== '' ? $telefono : '(no indicado)'),
        'Tipo ayuda: ' . limpiar('tipo_ayuda', 120),
        str_repeat('-', 46),
        'Descripción:',
        wordwrap($mensaje !== '' ? $mensaje : '(sin descripción)', 70),
    ];
}

$cuerpo = implode("\r\n", $lineas) . "\r\n";

// ── Envío del email ──────────────────────────────────────────────────
$cabeceras = implode("\r\n", [
    'From: ASODOMI Web <' . CONTACT_EMAIL . '>',
    'Reply-To: ' . $nombre . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8',
]);

$enviado = @mail(CONTACT_EMAIL, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo, $cabeceras);

// Per l'iscrizione socio il dato è già salvato nel database: va bene anche se l'email di notifica fallisse
$successo = ($tipo === 'socio') ? true : $enviado;

if ($tipo === 'socio') {
    header('Location: ' . $volver . '?ok=1&id=' . $socio_id);
} else {
    header('Location: ' . $volver . ($successo ? '?ok=1' : '?err=1'));
}
exit;
