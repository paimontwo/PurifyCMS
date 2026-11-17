<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (isset($title) && $title !== '') ? htmlspecialchars($title) . ' | ' . htmlspecialchars($cfg_name ?? 'PurifyCMS') : htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></title>
    <meta name="description" content="<?= htmlspecialchars($desc ?? 'Modern, high-performance digital platform powered by PurifyCMS.') ?>">
    
    <!-- OpenGraph / Social Meta -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($cfg_mainurl) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title ?? $cfg_name ?? 'PurifyCMS') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($desc ?? 'Modern, high-performance digital platform powered by PurifyCMS.') ?>">
    <?php if (!empty($cfg_favicon)): ?>
    <link rel="icon" href="<?= htmlspecialchars($cfg_favicon) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title ?? $cfg_name ?? 'PurifyCMS') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($desc ?? 'Modern, high-performance digital platform powered by PurifyCMS.') ?>">

    <!-- Open Source Fonts (Inter & Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Line Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" integrity="sha512-vebUliqxrVkBy3gucMhClmyQP9On/HAWQdKDXRaAlb/FKuTbxkjPKUyqVOxAcGwFDka79eTF+YXwfke1h3/wfg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <?php
    $main_css_ver = @filemtime(dirname(__DIR__) . '/assets/css/main.css') ?: '2.2.0';
    $sf_css_ver = @filemtime(dirname(__DIR__) . '/assets/css/storefront.css') ?: '2.2.0';
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim($cfg_mainurl, '/')) ?>/themes/<?= htmlspecialchars($cfg_active_theme) ?>/assets/css/main.css?v=<?= $main_css_ver ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim($cfg_mainurl, '/')) ?>/themes/<?= htmlspecialchars($cfg_active_theme) ?>/assets/css/storefront.css?v=<?= $sf_css_ver ?>">

    <?php if (!empty($cfg_googleanalytics)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($cfg_googleanalytics) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', <?= json_encode($cfg_googleanalytics) ?>);
    </script>
    <?php endif; ?>
</head>
<body>
