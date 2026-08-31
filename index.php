<?php
/**
 * ASODOMI – Enrutador principal.
 * Las URLs limpias las genera .htaccess: /es/servicios → index.php?lang=es&page=servicios
 */

// Pagine sempre aggiornate: il browser non deve servire copie vecchie
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/auth.php';

[$lang, $page] = asodomi_route();
$t = asodomi_load_lang($lang);

// ── Azioni dell'area soci (prima di qualsiasi output, per i redirect) ──
$login_errore = false;
if ($page === 'area-soci') {
    if (isset($_GET['esci'])) {
        logout_socio();
        header('Location: ' . url($lang, 'area-soci'));
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['socio_login'])) {
        csrf_verifica();
        if (login_socio((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            header('Location: ' . url($lang, 'area-soci'));
            exit;
        }
        $login_errore = true;
    }
}

require __DIR__ . '/inc/header.php';

// Mensaje de éxito/error tras enviar un formulario (enviar.php redirige aquí)
$form_ok   = isset($_GET['ok']);
$form_err  = isset($_GET['err']);

switch ($page):

case 'inicio': ?>

    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-text">
                <span class="badge"><?= e($t['home']['hero_badge']) ?></span>
                <h1><?= e($t['home']['hero_title']) ?></h1>
                <p class="lead"><?= e($t['home']['hero_text']) ?></p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="<?= e(url($lang, 'ayuda')) ?>"><?= e($t['home']['hero_cta1']) ?></a>
                    <a class="btn btn-ghost" href="<?= e(url($lang, 'servicios')) ?>"><?= e($t['home']['hero_cta2']) ?></a>
                </div>
            </div>
            <div class="hero-stats">
                <div class="stat"><strong><?= e($t['home']['stats_1_num']) ?></strong><span><?= e($t['home']['stats_1_txt']) ?></span></div>
                <div class="stat"><strong><?= e($t['home']['stats_2_num']) ?></strong><span><?= e($t['home']['stats_2_txt']) ?></span></div>
                <div class="stat"><strong><?= e($t['home']['stats_3_num']) ?></strong><span><?= e($t['home']['stats_3_txt']) ?></span></div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="section-title"><?= e($t['home']['services_title']) ?></h2>
            <p class="section-lead"><?= e($t['home']['services_text']) ?></p>
            <div class="cards">
                <?php foreach ($t['servicios']['items'] as $i => $s): ?>
                    <article class="card">
                        <div class="card-icon"><?= ['📝','🌍','🧭','🎉'][$i] ?? '⭐' ?></div>
                        <h3><?= e($s['t']) ?></h3>
                        <p><?= e($s['d']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <h2 class="section-title"><?= e($t['home']['how_title']) ?></h2>
            <ol class="steps">
                <li><h3><?= e($t['home']['how_1_t']) ?></h3><p><?= e($t['home']['how_1_d']) ?></p></li>
                <li><h3><?= e($t['home']['how_2_t']) ?></h3><p><?= e($t['home']['how_2_d']) ?></p></li>
                <li><h3><?= e($t['home']['how_3_t']) ?></h3><p><?= e($t['home']['how_3_d']) ?></p></li>
            </ol>
        </div>
    </section>

    <?php $eventos = asodomi_eventos(3); if ($eventos): ?>
    <section class="section">
        <div class="container">
            <h2 class="section-title"><?= e($t['home']['events_title']) ?></h2>
            <p class="section-lead"><?= e($t['home']['events_text']) ?></p>
            <div class="cards">
                <?php foreach ($eventos as $ev): ?>
                    <article class="card event-card">
                        <time class="event-date"><?= e(asodomi_fecha($ev['fecha'], $lang)) ?></time>
                        <h3><?= e($ev['titulo'][$lang] ?? $ev['titulo']['es']) ?></h3>
                        <p><?= e($ev['descripcion'][$lang] ?? $ev['descripcion']['es']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="center"><a class="btn btn-ghost" href="<?= e(url($lang, 'eventos')) ?>"><?= e($t['home']['events_more']) ?></a></p>
        </div>
    </section>
    <?php endif; ?>

    <section class="cta-band">
        <div class="container center">
            <h2><?= e($t['home']['free_title']) ?></h2>
            <p><?= e($t['home']['free_text']) ?></p>
            <a class="btn btn-light" href="<?= e(url($lang, 'cita')) ?>"><?= e($t['home']['free_cta']) ?></a>
        </div>
    </section>

<?php break;

case 'servicios': ?>

    <section class="page-head">
        <div class="container">
            <h1><?= e($t['servicios']['title']) ?></h1>
            <p class="lead"><?= e($t['servicios']['text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="service-list">
                <?php foreach ($t['servicios']['items'] as $i => $s): ?>
                    <article class="service-item">
                        <div class="card-icon big"><?= ['📝','🌍','🧭','🎉'][$i] ?? '⭐' ?></div>
                        <div>
                            <h2><?= e($s['t']) ?></h2>
                            <p><?= e($s['d']) ?></p>
                            <ul class="checklist">
                                <?php foreach ($s['l'] as $linea): ?><li><?= e($linea) ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="note">
                <strong>⚠️ <?= e($t['servicios']['note_title']) ?>:</strong> <?= e($t['servicios']['note']) ?>
            </aside>
        </div>
    </section>

<?php break;

case 'eventos': ?>

    <section class="page-head">
        <div class="container">
            <h1><?= e($t['eventos']['title']) ?></h1>
            <p class="lead"><?= e($t['eventos']['text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php $eventos = asodomi_eventos(); ?>
            <?php if (!$eventos): ?>
                <p class="empty-state"><?= e($t['eventos']['empty']) ?></p>
            <?php else: ?>
                <div class="cards">
                    <?php foreach ($eventos as $ev): ?>
                        <article class="card event-card">
                            <time class="event-date"><?= e(asodomi_fecha($ev['fecha'], $lang)) ?></time>
                            <h3><?= e($ev['titulo'][$lang] ?? $ev['titulo']['es']) ?></h3>
                            <p><?= e($ev['descripcion'][$lang] ?? $ev['descripcion']['es']) ?></p>
                            <?php if (!empty($ev['lugar'])): ?><p class="muted">📍 <?= e($ev['lugar']) ?></p><?php endif; ?>
                            <a class="btn btn-primary" href="<?= e(url($lang, 'cita')) ?>"><?= e($t['eventos']['participate']) ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php break;

case 'blog':
    // Dettaglio articolo: /it/blog/{slug}
    $slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]/', '', strtolower((string)$_GET['slug'])) : '';
    if ($slug !== '') {
        $stmt = db()->prepare(
            'SELECT a.*, u.nome AS autore FROM articoli a
             LEFT JOIN utenti u ON u.id = a.autore_id
             WHERE a.slug = ? AND a.stato = "pubblicato" LIMIT 1'
        );
        $stmt->execute([$slug]);
        $art = $stmt->fetch();
        // Articolo riservato: richiede l'accesso del socio
        if ($art && ($art['visibilita'] ?? 'pubblico') === 'riservato' && !socio_corrente()) {
            $art_bloccato = true;
        } else {
            $art_bloccato = false;
        }
    } else {
        $art = null;
        $art_bloccato = false;
    }
?>

<?php if ($art && !$art_bloccato): ?>
    <section class="page-head">
        <div class="container narrow">
            <p><a class="back-link" href="<?= e(url($lang, 'blog')) ?>"><?= e($t['blog']['torna']) ?></a></p>
            <h1><?= e($art['titolo']) ?></h1>
            <p class="muted small">
                <?php
                $data = $art['pubblicato_il'] ?: $art['creato_il'];
                echo e(date('d.m.Y', strtotime($data)));
                if (!empty($art['autore'])) {
                    echo ' · ' . e($t['blog']['di']) . ' ' . e($art['autore']);
                }
                ?>
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container narrow">
            <?php $video = video_embed((string)$art['video_url']); ?>
            <?php if ($video): ?>
                <div class="video-box">
                    <iframe src="<?= e($video['embed']) ?>" title="<?= e($art['titolo']) ?>"
                            frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen loading="lazy"></iframe>
                </div>
            <?php endif; ?>

            <article class="article-body">
                <?= sanitizza_html((string)$art['contenuto']) ?>
            </article>

            <p class="center" style="margin-top:2.5rem">
                <a class="btn btn-ghost" href="<?= e(url($lang, 'blog')) ?>"><?= e($t['blog']['torna']) ?></a>
            </p>
        </div>
    </section>
<?php elseif ($art_bloccato): ?>
    <section class="section">
        <div class="container narrow">
            <p><a class="back-link" href="<?= e(url($lang, 'blog')) ?>"><?= e($t['blog']['torna']) ?></a></p>
            <div class="riservato-box">
                <span class="lock">🔒</span>
                <h2><?= e($t['area_soci']['riservato_titolo']) ?></h2>
                <p><?= e($t['area_soci']['riservato_testo']) ?></p>
                <a class="btn btn-primary" href="<?= e(url($lang, 'area-soci')) ?>"><?= e($t['area_soci']['accedi']) ?></a>
                <a class="btn btn-ghost" href="<?= e(url($lang, 'iscrizione')) ?>" style="color:var(--azul)"><?= e($t['iscrizione']['title']) ?></a>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="page-head">
        <div class="container">
            <h1><?= e($t['blog']['title']) ?></h1>
            <p class="lead"><?= e($t['blog']['text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php
            $stmt = db()->prepare(
                'SELECT slug, titolo, contenuto, video_url, COALESCE(pubblicato_il, creato_il) AS data_pub
                 FROM articoli WHERE stato = "pubblicato" AND visibilita = "pubblico"
                 ORDER BY data_pub DESC LIMIT 50'
            );
            $stmt->execute();
            $articoli = $stmt->fetchAll();
            ?>
            <?php if (!$articoli): ?>
                <p class="empty-state"><?= e($t['blog']['empty']) ?></p>
            <?php else: ?>
                <div class="cards">
                    <?php foreach ($articoli as $a): ?>
                        <article class="card event-card">
                            <time class="event-date"><?= e(date('d.m.Y', strtotime($a['data_pub']))) ?></time>
                            <h3><?= e($a['titolo']) ?></h3>
                            <p><?= e(mb_substr(trim(strip_tags((string)$a['contenuto'])), 0, 140)) ?>…</p>
                            <a class="btn btn-primary" href="<?= e(url($lang, 'blog') . '/' . rawurlencode($a['slug'])) ?>">
                                <?= e($t['blog']['leggi']) ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php break;

case 'ayuda':
case 'cita':
    $es_cita = ($page === 'cita');
    $f = $es_cita ? $t['cita'] : $t['ayuda'];
    $accion_ok   = $form_ok ? $f['success'] : '';
    $accion_err  = $form_err ? $f['error'] : '';
?>

    <section class="page-head">
        <div class="container">
            <h1><?= e($f['title']) ?></h1>
            <p class="lead"><?= e($f['text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container narrow">
            <?php if ($accion_ok): ?><div class="alert alert-ok" role="status">✅ <?= e($accion_ok) ?></div><?php endif; ?>
            <?php if ($accion_err): ?><div class="alert alert-err" role="alert">⚠️ <?= e($accion_err) ?></div><?php endif; ?>

            <form class="form" method="post" action="<?= e(asset('/enviar.php')) ?>">
                <input type="hidden" name="form_type" value="<?= $es_cita ? 'cita' : 'ayuda' ?>">
                <input type="hidden" name="lang" value="<?= e($lang) ?>">
                <!-- Campo trampa anti-spam: los bots lo rellenan, las personas no lo ven -->
                <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                <label><?= e($f['form_name']) ?>
                    <input type="text" name="nombre" required maxlength="120">
                </label>

                <label><?= e($f['form_email']) ?>
                    <input type="email" name="email" required maxlength="160">
                </label>

                <label><?= e($f['form_phone']) ?>
                    <input type="tel" name="telefono" maxlength="40"
                           <?= $es_cita ? 'required' : '' ?>>
                </label>

                <?php if ($es_cita): ?>
                    <label><?= e($f['form_service']) ?>
                        <select name="servicio" required>
                            <?php foreach ($f['form_service_opt'] as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($f['form_date']) ?>
                        <input type="date" name="fecha" required min="<?= date('Y-m-d') ?>">
                    </label>
                    <label><?= e($f['form_time']) ?>
                        <select name="horario" required>
                            <?php foreach ($f['form_time_opt'] as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($f['form_mode']) ?>
                        <select name="modalidad" required>
                            <?php foreach ($f['form_mode_opt'] as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($f['form_msg']) ?>
                        <textarea name="mensaje" rows="4" maxlength="2000"></textarea>
                    </label>
                <?php else: ?>
                    <label><?= e($f['form_type']) ?>
                        <select name="tipo_ayuda" required>
                            <?php foreach ($f['form_type_opt'] as $opt): ?><option value="<?= e($opt) ?>"><?= e($opt) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label><?= e($f['form_msg']) ?>
                        <textarea name="mensaje" rows="6" required maxlength="3000"></textarea>
                    </label>
                <?php endif; ?>

                <p class="muted small"><?= e($f['form_privacy']) ?></p>
                <button type="submit" class="btn btn-primary btn-block" data-sending="<?= e($t['forms']['sending']) ?>"><?= e($f['submit']) ?></button>
            </form>
        </div>
    </section>

<?php break;

case 'contacto': ?>

    <section class="page-head">
        <div class="container">
            <h1><?= e($t['contacto']['title']) ?></h1>
            <p class="lead"><?= e($t['contacto']['text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container contact-grid">
            <div class="contact-card">
                <h3>📧 <?= e($t['contacto']['email_label']) ?></h3>
                <p><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></p>
            </div>
            <div class="contact-card">
                <h3>📞 <?= e($t['contacto']['phone_label']) ?></h3>
                <p><a href="tel:<?= e(preg_replace('/\s+/', '', CONTACT_PHONE)) ?>"><?= e(CONTACT_PHONE) ?></a></p>
            </div>
            <div class="contact-card">
                <h3>💬 <?= e($t['contacto']['whatsapp_label']) ?></h3>
                <p><a class="btn btn-whatsapp" href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>" target="_blank" rel="noopener"><?= e($t['contacto']['whatsapp_cta']) ?></a></p>
            </div>
            <div class="contact-card">
                <h3>📍 <?= e($t['contacto']['address_label']) ?></h3>
                <p><?= e(CONTACT_ADDRESS) ?></p>
            </div>
            <div class="contact-card">
                <h3>🕘 <?= e($t['contacto']['hours_label']) ?></h3>
                <p><?= e($t['contacto']['hours_value']) ?></p>
            </div>
            <div class="contact-card">
                <h3>🌐 <?= e($t['contacto']['social_label']) ?></h3>
                <p>
                    <a href="<?= e(FACEBOOK_URL) ?>" target="_blank" rel="noopener">Facebook</a> ·
                    <a href="<?= e(INSTAGRAM_URL) ?>" target="_blank" rel="noopener">Instagram</a>
                </p>
            </div>
        </div>
    </section>

<?php break;

case 'iscrizione':
    $fi = $t['iscrizione'];

    // ── Stato della pagina: id socio da confermare/modificare ────────
    $socio_conferma = null;
    $socio_modifica = 0;
    $socio_dati     = null;

    if (isset($_GET['ok']) && (int)($_GET['id'] ?? 0) > 0) {
        $stmt = db()->prepare('SELECT id, nome, email, telefono, indirizzo, comune FROM soci WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        $socio_conferma = $stmt->fetch() ?: null;
    }

    $socio_modifica = (int)($_GET['modifica'] ?? 0);
    if ($socio_modifica > 0) {
        $stmt = db()->prepare('SELECT id, nome, email, telefono, indirizzo, comune FROM soci WHERE id = ?');
        $stmt->execute([$socio_modifica]);
        $socio_dati = $stmt->fetch() ?: null;
        if (!$socio_dati) {
            $socio_modifica = 0;
        }
    }

    $dup_email = isset($_GET['dup']) ? trim((string)($_GET['email'] ?? '')) : '';
    $dup_mod   = isset($_GET['dup']) && $socio_modifica > 0;
?>

    <section class="page-head">
        <div class="container">
            <h1><?= e($socio_conferma ? $fi['conferma_title'] : ($socio_modifica ? $fi['modifica_title'] : $fi['title'])) ?></h1>
            <p class="lead"><?= e($socio_modifica ? $fi['modifica_intro'] : $fi['text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container narrow">

            <?php if ($socio_conferma): ?>

                <div class="alert alert-ok" role="status">✅ <?= e($fi['success']) ?></div>

                <div class="card" style="padding:1.5rem;border-radius:14px;border:1px solid var(--bordo,rgba(0,0,0,.08));background:#fff">
                    <h3>📋 <?= e($fi['i_tuoi_dati']) ?></h3>
                    <table class="socio-riepilogo" style="width:100%;border-collapse:collapse;margin:1rem 0">
                        <tr><th style="text-align:left;padding:.5rem 0;color:var(--azul)"><?= e($fi['form_nome']) ?></th><td style="padding:.5rem 0"><?= e($socio_conferma['nome']) ?></td></tr>
                        <tr><th style="text-align:left;padding:.5rem 0;color:var(--azul)"><?= e($fi['form_email']) ?></th><td style="padding:.5rem 0"><?= e($socio_conferma['email']) ?></td></tr>
                        <tr><th style="text-align:left;padding:.5rem 0;color:var(--azul)"><?= e($fi['form_telefono']) ?></th><td style="padding:.5rem 0"><?= e($socio_conferma['telefono']) ?></td></tr>
                        <?php if ($socio_conferma['indirizzo'] !== ''): ?>
                        <tr><th style="text-align:left;padding:.5rem 0;color:var(--azul)"><?= e($fi['form_indirizzo']) ?></th><td style="padding:.5rem 0"><?= e($socio_conferma['indirizzo']) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($socio_conferma['comune'] !== ''): ?>
                        <tr><th style="text-align:left;padding:.5rem 0;color:var(--azul)"><?= e($fi['form_comune']) ?></th><td style="padding:.5rem 0"><?= e($socio_conferma['comune']) ?></td></tr>
                        <?php endif; ?>
                    </table>
                    <p class="muted small"><?= e($fi['altri_dati']) ?></p>
                    <div style="display:flex;flex-wrap:wrap;gap:.8rem;margin-top:1rem">
                        <a class="btn btn-primary" href="<?= e(url($lang, 'area-soci')) ?>">🔒 <?= e($fi['accedi_area']) ?></a>
                        <a class="btn btn-ghost" href="<?= e(url($lang, 'iscrizione') . '?modifica=1&id=' . $socio_conferma['id']) ?>">✏️ <?= e($fi['correggi']) ?></a>
                    </div>
                </div>

            <?php else: ?>

                <?php if ($dup_email): ?>
                    <div class="alert alert-err" role="alert">⚠️ <?= e($fi['duplicato']) ?></div>
                <?php endif; ?>
                <?php if ($form_err || (isset($_GET['err']) && $socio_modifica)): ?>
                    <div class="alert alert-err" role="alert">⚠️ <?= e($fi['error']) ?></div>
                <?php endif; ?>

                <form class="form iscrizione-form" method="post" action="<?= e(asset('/enviar.php')) ?>">
                    <input type="hidden" name="form_type" value="socio">
                    <input type="hidden" name="lang" value="<?= e($lang) ?>">
                    <?php if ($socio_modifica): ?>
                        <input type="hidden" name="socio_id" value="<?= e($socio_modifica) ?>">
                    <?php endif; ?>
                    <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <label><?= e($fi['form_nome']) ?>
                        <input type="text" name="nombre" required maxlength="160" value="<?= e($socio_dati['nome'] ?? '') ?>">
                    </label>
                    <label><?= e($fi['form_email']) ?>
                        <input type="email" name="email" required maxlength="160" value="<?= e($socio_dati['email'] ?? '') ?>">
                    </label>
                    <label><?= e($fi['form_telefono']) ?>
                        <input type="tel" name="telefono" required maxlength="40" value="<?= e($socio_dati['telefono'] ?? '') ?>">
                    </label>
                    <label><?= e($fi['form_indirizzo']) ?>
                        <input type="text" name="indirizzo" maxlength="200" value="<?= e($socio_dati['indirizzo'] ?? '') ?>">
                    </label>
                    <label><?= e($fi['form_comune']) ?>
                        <input type="text" name="comune" required maxlength="120" value="<?= e($socio_dati['comune'] ?? '') ?>">
                    </label>
                    <label><?= e($socio_modifica ? $fi['password_opzionale'] : $fi['form_password']) ?>
                        <input type="password" name="password" <?= $socio_modifica ? '' : 'required minlength="8"' ?> autocomplete="new-password">
                    </label>

                    <label class="check-label">
                        <input type="checkbox" name="consenso" value="1" <?= $socio_modifica ? 'checked' : 'required' ?>>
                        <span><?= e($fi['form_consenso']) ?> <a href="<?= e(url($lang, 'privacy')) ?>" target="_blank">↗</a></span>
                    </label>

                    <?php if (!$socio_modifica): ?>
                        <p class="muted small" style="margin-top:.4rem">*</p>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-block js-privacy-submit" data-sending="<?= e($t['forms']['sending']) ?>"><?= e($socio_modifica ? $fi['salva_modifiche'] : $fi['submit']) ?></button>
                </form>

            <?php endif; ?>

        </div>
    </section>

<?php break;

case 'area-soci':
    $fa = $t['area_soci'];

    $socio = socio_corrente();
    if (!$socio):
?>
    <section class="page-head">
        <div class="container narrow">
            <h1>🔒 <?= e($fa['login_title']) ?></h1>
            <p class="lead"><?= e($fa['login_text']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <?php if ($login_errore): ?><div class="alert alert-err" role="alert">⚠️ <?= e($fa['errore_login']) ?></div><?php endif; ?>

            <form class="form soci-login" method="post" action="<?= e(url($lang, 'area-soci')) ?>">
                <?= csrf_campo() ?>
                <input type="hidden" name="socio_login" value="1">
                <label><?= e($fa['email']) ?>
                    <input type="email" name="email" required maxlength="160" autocomplete="username">
                </label>
                <label><?= e($fa['password']) ?>
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
                <button type="submit" class="btn btn-primary btn-block"><?= e($fa['accedi']) ?></button>
            </form>

            <p class="center muted" style="margin-top:1.5rem">
                <?= e($t['iscrizione']['title']) ?>:
                <a href="<?= e(url($lang, 'iscrizione')) ?>"><strong><?= e($t['iscrizione']['submit']) ?></strong></a>
            </p>
        </div>
    </section>

<?php else:
    $sezione = in_array($_GET['sezione'] ?? '', ['notizie', 'documenti'], true) ? $_GET['sezione'] : '';
    $stati = ['attivo' => $fa['stato_attivo'], 'in_attesa' => $fa['stato_in_attesa'], 'dimesso' => $fa['stato_dimesso']];
?>

    <section class="page-head">
        <div class="container">
            <h1>👤 <?= e($fa['benvenuto']) ?>, <?= e(explode(' ', trim($socio['nome']))[0]) ?>!</h1>
            <p class="lead"><?= e($fa['benvenuto_testo']) ?></p>
            <p class="area-tabs">
                <a href="<?= e(url($lang, 'area-soci')) ?>" class="<?= $sezione === '' ? 'active' : '' ?>">🏠 <?= e($fa['benvenuto']) ?></a>
                <a href="<?= e(url($lang, 'area-soci')) ?>?sezione=notizie" class="<?= $sezione === 'notizie' ? 'active' : '' ?>">📰 <?= e($fa['notizie_title']) ?></a>
                <a href="<?= e(url($lang, 'area-soci')) ?>?sezione=documenti" class="<?= $sezione === 'documenti' ? 'active' : '' ?>">📁 <?= e($fa['documenti_title']) ?></a>
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">

        <?php if ($sezione === ''): ?>
            <div class="soci-grid">
                <aside class="profile-card">
                    <h3><?= e($fa['profilo']) ?></h3>
                    <dl>
                        <dt><?= e($fa['stato']) ?>:</dt>
                        <dd><span class="stato-badge"><?= e($stati[$socio['stato']] ?? $socio['stato']) ?></span></dd>
                        <dt>Email:</dt><dd><?= e($socio['email']) ?></dd>
                        <dt>Tel:</dt><dd><?= e($socio['telefono'] ?: '–') ?></dd>
                        <dt>Comune:</dt><dd><?= e($socio['comune'] ?: '–') ?></dd>
                        <dt><?= e($fa['socio_dal']) ?>:</dt><dd><?= e(date('d.m.Y', strtotime($socio['creato_il']))) ?></dd>
                    </dl>
                    <p style="margin:1.2rem 0 0"><a href="<?= e(url($lang, 'area-soci')) ?>?esci=1">🚪 <?= e($fa['esci']) ?></a></p>
                </aside>

                <div>
                    <h2><?= e($fa['notizie_title']) ?></h2>
                    <?php
                    $stmt = db()->query(
                        'SELECT slug, titolo, visibilita, COALESCE(pubblicato_il, creato_il) AS data_pub
                         FROM articoli WHERE stato = "pubblicato"
                         ORDER BY data_pub DESC LIMIT 3'
                    );
                    $ultime = $stmt->fetchAll();
                    if ($ultime): ?>
                        <ul class="checklist" style="margin-bottom:1rem">
                            <?php foreach ($ultime as $u2): ?>
                                <li>
                                    <a href="<?= e(url($lang, 'blog') . '/' . rawurlencode($u2['slug'])) ?>">
                                        <?= e(date('d.m.Y', strtotime($u2['data_pub']))) ?> – <?= e($u2['titolo']) ?>
                                    </a>
                                    <?php if ($u2['visibilita'] === 'riservato'): ?><span class="badge-riservato"><?= e($fa['etichetta_riservato']) ?></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p><a href="<?= e(url($lang, 'area-soci')) ?>?sezione=notizie"><strong><?= e($t['blog']['leggi']) ?></strong></a></p>
                    <?php else: ?>
                        <p class="muted"><?= e($t['blog']['empty']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($sezione === 'notizie'): ?>
            <h2 class="section-title"><?= e($fa['notizie_title']) ?></h2>
            <p class="section-lead"><?= e($fa['notizie_text']) ?></p>
            <?php
            $stmt = db()->query(
                'SELECT slug, titolo, contenuto, visibilita, COALESCE(pubblicato_il, creato_il) AS data_pub
                 FROM articoli WHERE stato = "pubblicato"
                 ORDER BY data_pub DESC LIMIT 50'
            );
            $articoli_soci = $stmt->fetchAll();
            ?>
            <?php if (!$articoli_soci): ?>
                <p class="empty-state"><?= e($t['blog']['empty']) ?></p>
            <?php else: ?>
                <div class="cards">
                    <?php foreach ($articoli_soci as $a): ?>
                        <article class="card event-card">
                            <time class="event-date"><?= e(date('d.m.Y', strtotime($a['data_pub']))) ?></time>
                            <h3><?= e($a['titolo']) ?><?php if ($a['visibilita'] === 'riservato'): ?><span class="badge-riservato"><?= e($fa['etichetta_riservato']) ?></span><?php endif; ?></h3>
                            <p><?= e(mb_substr(trim(strip_tags((string)$a['contenuto'])), 0, 140)) ?>…</p>
                            <a class="btn btn-primary" href="<?= e(url($lang, 'blog') . '/' . rawurlencode($a['slug'])) ?>">
                                <?= e($t['blog']['leggi']) ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: // documenti ?>
            <h2 class="section-title">📁 <?= e($fa['documenti_title']) ?></h2>
            <p class="section-lead"><?= e($fa['documenti_text']) ?></p>
            <?php
            $docs = db()->query('SELECT * FROM documenti ORDER BY creato_il DESC')->fetchAll();
            ?>
            <?php if (!$docs): ?>
                <p class="empty-state"><?= e($fa['nessun_documento']) ?></p>
            <?php else: ?>
                <div class="doc-list narrow" style="width:min(760px,100%);margin-inline:auto">
                    <?php foreach ($docs as $d): ?>
                        <article class="doc-card">
                            <div>
                                <h3>📄 <?= e($d['titolo']) ?></h3>
                                <?php if (!empty($d['descrizione'])): ?><p><?= e($d['descrizione']) ?></p><?php endif; ?>
                            </div>
                            <a class="btn btn-primary btn-sm" href="<?= e($d['url']) ?>" target="_blank" rel="noopener"><?= e($fa['apri']) ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['upload'])): ?>
                <div class="doc-list narrow" style="width:min(760px,100%);margin-inline:auto;margin-top:.5rem">
                    <?php
                    $msg_up = match($_GET['upload']) {
                        'ok'   => '✅ ' . $fa['upload_ok'],
                        'type' => '⚠️ ' . $fa['upload_troppo'],
                        default => '⚠️ ' . $fa['upload_err'],
                    };
                    ?>
                    <div class="doc-card" style="<?= $_GET['upload'] === 'ok' ? 'border-color:#2e7d32;background:#e8f5e9' : '' ?>"><?= $msg_up ?></div>
                </div>
            <?php endif; ?>

            <div class="doc-list narrow" style="width:min(760px,100%);margin-inline:auto;margin-top:1.5rem">
                <div class="doc-card" style="border-style:dashed">
                    <h3>⬆️ <?= e($fa['upload_title']) ?></h3>
                    <p><?= e($fa['upload_text']) ?></p>
                    <form method="post" action="<?= e(asset('/upload-socio.php')) ?>" enctype="multipart/form-data" class="form">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="lang" value="<?= e($lang) ?>">
                        <input type="file" name="documento" required style="margin:.5rem 0">
                        <p class="muted" style="font-size:.85rem"><?= e($fa['upload_max']) ?></p>
                        <button type="submit" class="btn btn-primary"><?= e($fa['upload_btn']) ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        </div>
    </section>
<?php endif; ?>

<?php break;

case 'privacy': ?>

    <section class="page-head">
        <div class="container narrow">
            <h1><?= e($t['privacy']['title']) ?></h1>
            <p class="lead"><?= e($t['privacy']['intro']) ?></p>
        </div>
    </section>

    <section class="section">
        <div class="container narrow article-body">
            <?php foreach ($t['privacy']['sezioni'] as [$tit, $txt]): ?>
                <h2><?= e($tit) ?></h2>
                <p><?= e($txt) ?></p>
            <?php endforeach; ?>
            <p class="muted small"><?= e(date('d.m.Y')) ?></p>
        </div>
    </section>

<?php break;

endswitch;

require __DIR__ . '/inc/footer.php';
