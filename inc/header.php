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

    <?php
    [$title_page, $desc_page] = asodomi_meta_pagina($t, $page);
    // Slug articolo (pagina blog in dettaglio) per canonical/hreflang dedicati
    $blog_slug = (isset($page) && $page === 'blog' && isset($_GET['slug']))
        ? preg_replace('/[^a-z0-9-]/', '', strtolower((string)$_GET['slug'])) : '';
    // Home it → raíz sin prefijo; demás lenguas → prefijo
    $canon = $blog_slug !== ''
        ? asodomi_url_canonica($lang, 'blog') . $blog_slug . '/'
        : asodomi_url_canonica($lang, $page);
    ?>

    <title><?= e($title_page) ?></title>
    <meta name="description" content="<?= e($desc_page) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($canon) ?>">

    <!-- hreflang: versiones por idioma -->
    <?php
    $hreflang_pagina = ($page === 'inicio') ? '' : $page;
    foreach ($GLOBALS['ASODOMI_LANGS'] as $l):
        $href = $blog_slug !== ''
            ? asodomi_site_url() . '/' . $l . '/blog/' . $blog_slug . '/'
            : asodomi_site_url() . '/' . $l . ($hreflang_pagina !== '' ? '/' . $hreflang_pagina : '');
        // La versión it de la home corresponde a la raíz
        if ($l === DEFAULT_LANG && $hreflang_pagina === '' && $blog_slug === '') {
            $href = asodomi_site_url() . '/';
        }
    ?>
        <link rel="alternate" hreflang="<?= e($l) ?>" href="<?= e($href) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e(asodomi_site_url() . '/') ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= $page === 'inicio' ? 'website' : 'website' ?>">
    <meta property="og:locale" content="<?= e(['it' => 'it_IT', 'es' => 'es_ES', 'fr' => 'fr_CH', 'de' => 'de_CH'][$t['meta']['lang']] ?? 'it_IT') ?>">
    <meta property="og:site_name" content="<?= e($t['meta']['title_site']) ?>">
    <meta property="og:title" content="<?= e($title_page) ?>">
    <meta property="og:description" content="<?= e($desc_page) ?>">
    <meta property="og:url" content="<?= e($canon) ?>">
    <meta property="og:image" content="<?= e(asodomi_site_url() . asset('/assets/img/logo.png')) ?>">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= e($title_page) ?>">
    <meta name="twitter:description" content="<?= e($desc_page) ?>">
    <meta name="twitter:image" content="<?= e(asodomi_site_url() . asset('/assets/img/logo.png')) ?>">

    <link rel="icon" type="image/png" href="<?= e(asset_v('/assets/img/favicon.png')) ?>">
    <link rel="manifest" href="<?= e(asset('/manifest.webmanifest')) ?>">
    <meta name="theme-color" content="#0b3d91">
    <link rel="apple-touch-icon" href="<?= e(asset_v('/assets/img/apple-touch-icon.png')) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ASODOMI">
    <link rel="stylesheet" href="<?= e(asset_v('/assets/css/styles.css')) ?>">

    <!-- Schema.org: Organización (asociación sin ánimo de lucro) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "ASODOMI",
        "alternateName": <?= json_encode($t['meta']['title_site']) ?>,
        "description": <?= json_encode($desc_page) ?>,
        "url": <?= json_encode(asodomi_site_url() . '/') ?>,
        "logo": <?= json_encode(asodomi_site_url() . asset('/assets/img/logo.png')) ?>,
        "email": <?= json_encode(CONTACT_EMAIL) ?>,
        "telephone": <?= json_encode(CONTACT_PHONE) ?>,
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Via Scazziga 8",
            "postalCode": "6600",
            "addressLocality": "Muralto",
            "addressCountry": "CH"
        },
        "sameAs": [<?= json_encode(FACEBOOK_URL) ?>, <?= json_encode(INSTAGRAM_URL) ?>],
        "knowsAbout": ["associazione dominicana", "aiuto immigrati", "integrazione", "servizi amministrativi"],
        "makesOffer": {
            "@type": "Offer",
            "description": <?= json_encode($t['meta']['description']) ?>,
            "priceCurrency": "CHF",
            "price": "0"
        }
    }
    </script>
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
