<?php
/**
 * ASODOMI – Newsletter worker (CLI). Elabora invii programmati/pendenti in lotti.
 *
 * Uso (cron):  * * * * *  php /var/www/asodomi/cron/newsletter_worker.php
 *
 * Responsabilità:
 *  - elabora le campagne 'programmata' la cui scheduled_at <= NOW()
 *  - invia un BATCH di email agli iscritti eleggibili (confirmed+attivo)
 *  - registra ogni tentativo nel log (nessun duplicato: log 'ok' per la coppia campagna-iscritto)
 *  - quando il batch non trova più destinazioni residue per una campagna, la marca 'inviata'
 */
if (PHP_SAPI !== 'cli') {
    exit(1); // niente esecuzioni da HTTP
}

require_once dirname(__DIR__) . '/inc/config.php';
require_once dirname(__DIR__) . '/inc/functions.php';
require_once dirname(__DIR__) . '/inc/newsletter.php';

$batchSize = defined('NL_BATCH_SIZE') && NL_BATCH_SIZE > 0 ? (int)NL_BATCH_SIZE : 50;

$pdo = db();
$now = date('Y-m-d H:i:s');

// Campagne programmate e pronte (scheduled_at nullo o già scaduto)
$stmt = $pdo->query(
    "SELECT id, titolo, oggetto, mittente_nome, reply_to, contenuto FROM newsletter_campagne
     WHERE stato = 'programmata' AND (scheduled_at IS NULL OR scheduled_at <= NOW())
     ORDER BY scheduled_at ASC"
);
$campagne = $stmt->fetchAll();

if (!$campagne) {
    echo "[" . $now . "] Nessuna campagna da elaborare.\n";
    exit(0);
}

foreach ($campagne as $c) {
    $cid = (int)$c['id'];
    echo "[" . date('Y-m-d H:i:s') . "] Campagna #$cid: {$c['titolo']}" . PHP_EOL;

    // Subscribers eleggibili: confirmed+attivo, ancora senza log 'ok' per questa campagna
    $in = $pdo->prepare(
        "SELECT s.id, s.email, s.nome, s.cognome, s.lingua, s.token_unsubscribe
         FROM newsletter_iscritti s
         WHERE s.status = 'confirmed' AND s.attivo = 1
           AND NOT EXISTS (
                SELECT 1 FROM newsletter_campaign_logs l
                WHERE l.campaign_id = ? AND l.subscriber_id = s.id AND l.status = 'ok'
           )
         LIMIT {$batchSize}"
    );
    $in->execute([$cid]);
    $destinatari = $in->fetchAll();

    if (!$destinatari) {
        // Tutti gli eleggibili sono stati già elaborati (ok/errore definitivo)
        if (batch_esaurita($pdo, $cid)) {
            $pdo->prepare("UPDATE newsletter_campagne SET stato='inviata', inviata_il=NOW(), sent_at=NOW() WHERE id=?")->execute([$cid]);
            echo "  -> campagna marcata INVIATA\n";
        } else {
            echo "  -> batch vuoto, ma ci sono ancora pendenti: nessun cambiamento\n";
        }
        continue;
    }

    foreach ($destinatari as $s) {
        $email = $s['email'];
        $nomeCompleto = trim(($s['nome'] ?? '') . ' ' . ($s['cognome'] ?? ''));
        $linkUnsub = nl_url_pubblica('newsletter_optout', ['token' => $s['token_unsubscribe']], $s['lingua'] ?: DEFAULT_LANG);

        // Template: sostituisci variabili
        $html = (string)$c['contenuto'];
        $html = str_replace(['{nome}', '{nome_cognome}'], [$nomeCompleto, $nomeCompleto], $html);
        $html = str_replace('{unsubscribe_link}', $linkUnsub, $html);

        $oggetto = str_replace(['{nome}', '{nome_cognome}'], [$nomeCompleto, $nomeCompleto], (string)$c['oggetto']);

        $body = nl_body_html($html, $linkUnsub, $s['lingua'] ?: DEFAULT_LANG);

        // Invio con soggetto già codificato in nl_invia
        $ok = nl_invia(
            $email,
            $oggetto,
            $body,
            (string)($c['mittente_nome'] ?: NL_SENDER_NAME),
            NL_SENDER_EMAIL,
            (string)($c['reply_to'] ?: NL_REPLY_TO)
        );

        if ($ok) {
            nl_log_invio($cid, (int)$s['id'], $email, 'invio', 'ok');
            echo "  OK  $email\n";
        } else {
            nl_log_invio($cid, (int)$s['id'], $email, 'invio', 'errore', 'mail() fallita');
            echo "  ERR $email\n";
        }
    }
}

/** true se nessuna riga eleggibile rimane in sospeso per la campagna. */
function batch_esaurita(PDO $pdo, int $cid): bool
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM newsletter_iscritti s
         WHERE s.status='confirmed' AND s.attivo=1
           AND NOT EXISTS (
                SELECT 1 FROM newsletter_campaign_logs l
                WHERE l.campaign_id = ? AND l.subscriber_id = s.id AND l.status='ok'
           )"
    );
    $st->execute([$cid]);
    return ((int)$st->fetchColumn()) === 0;
}

exit(0);