<?php
/** Bacheca: riepilogo rapido */
$conteggi = [
    'articoli_pub'  => (int)db()->query('SELECT COUNT(*) FROM articoli WHERE stato = "pubblicato"')->fetchColumn(),
    'articoli_bozze'=> (int)db()->query('SELECT COUNT(*) FROM articoli WHERE stato = "bozza"')->fetchColumn(),
    'soci_attivi'   => (int)db()->query('SELECT COUNT(*) FROM soci WHERE stato = "attivo"')->fetchColumn(),
    'soci_nuovi'    => (int)db()->query('SELECT COUNT(*) FROM soci WHERE creato_il >= DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn(),
];
$ultimi_soci = db()->query('SELECT id, nome, email, comune, creato_il FROM soci ORDER BY creato_il DESC LIMIT 5')->fetchAll();
$ultimi_articoli = db()->query('SELECT id, titolo, stato, aggiornato_il FROM articoli ORDER BY aggiornato_il DESC LIMIT 5')->fetchAll();
?>

<h1>Bacheca</h1>
<p class="muted">Benvenuta nella gestione di ASODOMI. Qui vedi a colpo d'occhio come sta il sito.</p>

<div class="stat-grid">
    <a class="stat-card" href="<?= e(admin_url('articoli')) ?>">
        <strong><?= $conteggi['articoli_pub'] ?></strong><span>Articoli pubblicati</span>
    </a>
    <a class="stat-card" href="<?= e(admin_url('articoli')) ?>">
        <strong><?= $conteggi['articoli_bozze'] ?></strong><span>Bozze in attesa</span>
    </a>
    <a class="stat-card" href="<?= e(admin_url('soci')) ?>">
        <strong><?= $conteggi['soci_attivi'] ?></strong><span>Soci attivi</span>
    </a>
    <a class="stat-card" href="<?= e(admin_url('soci')) ?>">
        <strong><?= $conteggi['soci_nuovi'] ?></strong><span>Nuovi iscritti (30 giorni)</span>
    </a>
</div>

<div class="two-col">
    <section class="panel">
        <h2>Ultimi soci iscritti</h2>
        <?php if (!$ultimi_soci): ?>
            <p class="muted">Nessun socio ancora iscritto.</p>
        <?php else: ?>
            <table class="tabella">
                <tr><th>Nome</th><th>Comune</th><th>Data</th></tr>
                <?php foreach ($ultimi_soci as $s): ?>
                    <tr>
                        <td><a href="<?= e(admin_url('socio') . '&id=' . (int)$s['id']) ?>"><?= e($s['nome']) ?></a></td>
                        <td><?= e($s['comune'] ?: '–') ?></td>
                        <td><?= e(date('d.m.Y', strtotime($s['creato_il']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Articoli recenti</h2>
        <?php if (!$ultimi_articoli): ?>
            <p class="muted">Nessun articolo ancora. <a href="<?= e(admin_url('articolo')) ?>">Scrivi il primo →</a></p>
        <?php else: ?>
            <table class="tabella">
                <tr><th>Titolo</th><th>Stato</th></tr>
                <?php foreach ($ultimi_articoli as $a): ?>
                    <tr>
                        <td><a href="<?= e(admin_url('articolo') . '&id=' . (int)$a['id']) ?>"><?= e($a['titolo']) ?></a></td>
                        <td><span class="stato stato-<?= e($a['stato']) ?>"><?= $a['stato'] === 'pubblicato' ? '● pubblicato' : '○ bozza' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <p><a class="btn btn-primary btn-sm" href="<?= e(admin_url('articolo')) ?>">+ Nuovo articolo</a></p>
    </section>
</div>
