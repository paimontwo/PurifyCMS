<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (isset($title) && $title !== '') ? htmlspecialchars($title) . ' | ' . htmlspecialchars($cfg_name ?? 'PurifyCMS') : htmlspecialchars($cfg_name ?? 'PurifyCMS') . ' | Premium Flat-File Engine' ?></title>
    <meta name="description" content="<?= htmlspecialchars($desc ?? 'A lightning-fast, database-free CMS for modern developers.') ?>">
    
    <!-- OpenGraph / Social Meta -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($cfg_mainurl) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title ?? $cfg_name ?? 'PurifyCMS') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($desc ?? 'Professional Business Solutions Built with PurifyCMS.') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($cfg_mainurl) ?>/themes/<?= $cfg_active_theme ?>/assets/images/og-image.jpg">
    <?php if (!empty($cfg_favicon)): ?>
    <link rel="icon" href="<?= $cfg_favicon ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title ?? $cfg_name ?? 'PurifyCMS') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($desc ?? 'Professional Business Solutions Built with PurifyCMS.') ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Line Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" integrity="sha512-vebUliqxrVkBy3gucMhClmyQP9On/HAWQdKDXRaAlb/FKuTbxkjPKUyqVOxAcGwFDka79eTF+YXwfke1h3/wfg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <link rel="stylesheet" href="<?= htmlspecialchars($cfg_mainurl) ?>/themes/<?= $cfg_active_theme ?>/assets/css/main.css?v=2.1.0">
    <link rel="stylesheet" href="<?= htmlspecialchars($cfg_mainurl) ?>/themes/<?= $cfg_active_theme ?>/assets/css/storefront.css?v=1.0.0">

    <style>
        .animate-in { opacity: 1 !important; transform: none !important; }
    </style>
</head>
<body>
