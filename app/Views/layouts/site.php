<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $siteTitle = $title ?? ($settings['site_name'] ?? 'Fynefield Petroleum'); $description = $metaDescription ?? ($settings['site_tagline'] ?? 'Fynefield Petroleum'); $canonical = $page['canonical_url'] ?? $post['canonical_url'] ?? current_url(); $noindex = ($page['noindex'] ?? 0) || ($post['noindex'] ?? 0) || ($isPreview ?? false); $socialImage = $page['cover_image'] ?? $post['cover_image'] ?? ($settings['logo'] ?? ''); ?>
    <meta name="description" content="<?= esc($description) ?>">
    <link rel="canonical" href="<?= esc($canonical) ?>">
    <?php if ($noindex): ?><meta name="robots" content="noindex,follow"><?php endif ?>
    <meta property="og:type" content="website"><meta property="og:site_name" content="<?= esc($settings['site_name'] ?? 'Fynefield Petroleum') ?>"><meta property="og:title" content="<?= esc($siteTitle) ?>"><meta property="og:description" content="<?= esc($description) ?>"><meta property="og:url" content="<?= esc($canonical) ?>">
    <?php if ($socialImage): ?><meta property="og:image" content="<?= esc($socialImage) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif ?>
    <title><?= esc($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap">
    <script>document.documentElement.classList.add('ff-js')</script>
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/contact.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/fynefield-ui.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/fynefield-ui.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/accessibility.css') ?>">
    <script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $settings['site_name'] ?? 'Fynefield Petroleum', 'url' => site_url('/'), 'logo' => $settings['logo'] ?? null, 'email' => $settings['contact_email'] ?? null, 'telephone' => $settings['contact_phone'] ?? null], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="fynefield-ui">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php
$currentPath = '/' . trim(service('uri')->getPath(), '/');
$phone = $settings['contact_phone'] ?? '';
$phoneHref = 'tel:' . preg_replace('/\s+/', '', $phone);
$email = $settings['contact_email'] ?? '';
?>
<div class="ff-wrap">
<header class="ff-header" data-site-header>
    <div class="ff-header-bar">
        <a class="ff-brand" href="<?= site_url('/') ?>"><img src="<?= esc($settings['logo'] ?? '') ?>" alt="<?= esc($settings['site_name'] ?? 'Fynefield Petroleum') ?>"></a>
        <nav class="ff-nav" aria-label="Main navigation"><?php foreach ($navigation as $item): ?><?php $itemPath = '/' . trim((string) $item['url'], '/'); $isActive = $itemPath === $currentPath || ($itemPath === '/services' && str_starts_with($currentPath, '/services/')) || ($itemPath === '/news' && str_starts_with($currentPath, '/news/')); ?><a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= esc($item['url']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= esc($item['label']) ?></a><?php endforeach ?></nav>
        <div class="ff-header-actions">
            <a class="ff-cta" href="<?= site_url('contact-us') ?>">Get in touch</a>
            <button class="ff-menu-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-menu">Menu</button>
        </div>
    </div>
    <nav class="ff-menu" id="site-menu" aria-label="Mobile navigation" hidden><?php foreach ($navigation as $item): ?><a href="<?= esc($item['url']) ?>"><?= esc($item['label']) ?></a><?php endforeach ?></nav>
</header>
<?php if (! ($inlineFlash ?? false)): ?>
<?php if (session('success')): ?><div class="ff-flash is-success" role="status"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="ff-flash is-error" role="alert"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('errors')): ?><div class="ff-flash is-error" role="alert"><?= implode('<br>', array_map('esc', session('errors'))) ?></div><?php endif ?>
<?php endif ?>
<main id="main-content" tabindex="-1"><?= $this->renderSection('content') ?></main>
<?php if (! ($hideFooterCta ?? false)): ?>
<section class="ff-band">
    <div class="ff-band-card">
        <div class="ff-band-amber">
            <h2>Let's talk supply.</h2>
            <p>Bulk supply, storage, haulage or a retail partnership — tell us what you need.</p>
        </div>
        <div class="ff-band-white is-cta">
            <a class="ff-pill ff-pill-dark is-md" href="<?= site_url('contact-us') ?>">Send an inquiry</a>
            <div class="ff-band-lines"><a href="<?= esc($phoneHref) ?>"><?= esc($phone) ?></a><a href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a></div>
        </div>
    </div>
</section>
<?php endif ?>
<footer class="ff-footer">
    <div class="ff-footer-wordmark" aria-hidden="true">FYNEFIELD</div>
    <div class="ff-footer-grid">
        <div><strong>Head office</strong><span class="ff-footer-address"><?= esc(rtrim($settings['address'] ?? '', '.')) ?></span></div>
        <div><strong>Company</strong><a href="<?= site_url('about-us') ?>">About us</a><a href="<?= site_url('management-team') ?>">Leadership</a><a href="<?= site_url('news') ?>">News</a></div>
        <div><strong>Services</strong><a href="<?= site_url('services/bulk-storage') ?>">Bulk storage</a><a href="<?= site_url('services/logistics') ?>">Logistics</a><a href="<?= site_url('services/retail-outlets') ?>">Retail outlets</a></div>
        <div><strong>Contact</strong><a href="<?= esc($phoneHref) ?>"><?= esc($phone) ?></a><a href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a><a href="<?= site_url('contact-us') ?>">Make an inquiry</a></div>
    </div>
    <div class="ff-footer-bottom"><span>© <?= date('Y') ?> Fynefield Petroleum Company Limited</span><div><a href="<?= site_url('privacy-policy') ?>">Privacy policy</a><a href="<?= site_url('sitemap.xml') ?>">Sitemap</a></div></div>
</footer>
</div>
<script src="<?= base_url('assets/js/site.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/site.js') ?>" defer></script>
</body>
</html>
