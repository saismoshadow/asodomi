<?php
/** Contenuti: contenitore con linguette Blog / Notizie / Eventi */
$tipo = $_GET['tipo'] ?? 'blog';
if (!in_array($tipo, ['blog', 'notizie', 'eventi'], true)) {
    $tipo = 'blog';
}

$schede = [
    'blog'    => ['Blog',  admin_url('articoli') . '&tipo=blog'],
    'notizie' => ['Notizie', admin_url('articoli') . '&tipo=notizie'],
    'eventi'  => ['Eventi', admin_url('articoli') . '&tipo=eventi'],
];
?>
<div class="admin-tabs" role="tablist">
    <?php foreach ($schede as $s => [$etichetta, $link]): ?>
        <a class="admin-tab <?= $tipo === $s ? 'active' : '' ?>" role="tab" href="<?= e($link) ?>"><?= e($etichetta) ?></a>
    <?php endforeach; ?>
</div>

<?php
if ($tipo === 'notizie') {
    require __DIR__ . '/notizie.php';
} elseif ($tipo === 'eventi') {
    require __DIR__ . '/eventi.php';
} else {
    require __DIR__ . '/articoli-blog.php';
}
