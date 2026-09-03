<?php
/**
 * Cabecera común del sitio.
 * Variables esperadas: $t (textos del idioma), $lang, $page
 */
$socio_header = socio_corrente();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['meta']['title_site']) ?> – <?= e($t['nav'][$page] ?? '') ?></title>
    <meta name="description" content="<?= e($t['meta']['description']) ?>">
    <link rel="icon" type="image/png" href="<?= e(asset_v('/assets/img/favicon.png')) ?>">
    <link rel="manifest" href="<?= e(asset('/manifest.webmanifest')) ?>">
    <meta name="theme-color" content="#0b3d91">
    <link rel="apple-touch-icon" href="<?= e(asset_v('/assets/img/apple-touch-icon.png')) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ASODOMI">
    <link rel="stylesheet" href="<?= e(asset_v('/assets/css/styles.css')) ?>">
</head>
<body>
<a class="skip-link" href="#contenido">↧</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url($lang, 'inicio')) ?>">
            <img src="<?= e(asset_v('/assets/img/logo.png')) ?>" alt="ASODOMI" class="brand-logo"
                 onerror="this.style.display='none'">
        </a>

        <nav class="main-nav" id="mainNav" aria-label="Principal">
            <ul>
                <?php foreach ($t['nav'] as $key => $label): ?>
                    <li>
                        <a href="<?= e(url($lang, $key)) ?>"
                           class="<?= $page === $key ? 'active' : '' ?> <?= $key === 'iscrizione' ? 'nav-cta' : '' ?>">
                            <?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="header-right">
            <div class="lang-switch" aria-label="Sprache / Idioma">
                <?php foreach ($GLOBALS['ASODOMI_LANGS'] as $l): ?>
                    <a href="<?= e(url($l, $page === 'inicio' ? '' : $page)) ?>"
                       class="<?= $l === $lang ? 'active' : '' ?>" hreflang="<?= e($l) ?>">
                        <?= e(strtoupper($l)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($socio_header): ?>
                <a class="member-chip" href="<?= e(url($lang, 'area-soci')) ?>" title="<?= e($t['area_soci']['link']) ?>">
                    👤 <span><?= e($t['area_soci']['ciao']) ?>, <?= e(explode(' ', trim($socio_header['nome']))[0]) ?></span>
                </a>
            <?php else: ?>
                <a class="member-link" href="<?= e(url($lang, 'area-soci')) ?>">🔒 <?= e($t['area_soci']['link']) ?></a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Menü">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main id="contenido">
