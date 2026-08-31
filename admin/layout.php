<?php
/**
 * Layout del pannello admin.
 * Variabili: $utente, $route
 */
$nav_admin = [
    'dashboard' => ['📊', 'Bacheca'],
    'articoli'  => ['📰', 'Articoli blog'],
    'documenti' => ['📁', 'Documenti soci'],
    'soci'      => ['👥', 'Soci'],
    'utenti'    => ['🔑', 'Utenti'],
];
$gruppo_attivo = [
    'dashboard' => ['dashboard'],
    'articoli'  => ['articoli', 'articolo', 'salva-articolo', 'elimina-articolo'],
    'documenti' => ['documenti', 'salva-documento', 'elimina-documento'],
    'soci'      => ['soci', 'socio', 'salva-socio', 'elimina-socio', 'soci-export'],
    'utenti'    => ['utenti', 'salva-utente', 'elimina-utente'],
];
$ruolo_utente = $utente['ruolo'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ASODOMI – Gestione</title>
<link rel="icon" type="image/png" href="<?= e(asset_v('/assets/img/favicon.png')) ?>">
<link rel="stylesheet" href="<?= e(asset_v('/assets/css/styles.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_v('/admin/admin.css')) ?>">
</head>
<body class="admin-body">

<header class="admin-topbar">
    <div class="admin-topbar-inner">
        <a class="brand" href="<?= e(admin_url()) ?>"><span class="brand-name">ASO<span>DOMI</span></span><small>gestione</small></a>
        <nav class="admin-nav">
            <?php foreach ($nav_admin as $r => [$icona, $etichetta]):
                if ($r === 'utenti' && $ruolo_utente !== 'admin') continue;
                if ($r === 'soci' && $ruolo_utente !== 'admin') continue; ?>
                <a href="<?= e(admin_url($r)) ?>" class="<?= in_array($route, $gruppo_attivo[$r], true) ? 'active' : '' ?>">
                    <?= $icona ?> <?= $etichetta ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-user">
            <span>Ciao, <strong><?= e($utente['nome'] ?: $utente['email']) ?></strong></span>
            <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('logout')) ?>">Esci</a>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('it', 'inicio')) ?>" title="Vedi il sito pubblico">🌐</a>
        </div>
    </div>
</header>

<main class="admin-main">
<?php
$file = __DIR__ . '/pages/' . str_replace(['..', '\\'], '', $route) . '.php';
if (is_file($file)) {
    require $file;
} else {
    echo '<div class="alert alert-err">Pagina non trovata.</div>';
}
?>
</main>

<script src="<?= e(asset_v('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
