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
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/contact.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/accessibility.css') ?>">
    <script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $settings['site_name'] ?? 'Fynefield Petroleum', 'url' => site_url('/'), 'logo' => $settings['logo'] ?? null, 'email' => $settings['contact_email'] ?? null, 'telephone' => $settings['contact_phone'] ?? null], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="topbar"><div class="container"><span><?= esc($settings['contact_phone'] ?? '') ?></span><a href="mailto:<?= esc($settings['contact_email'] ?? '') ?>"><?= esc($settings['contact_email'] ?? '') ?></a></div></div>
<header class="site-header"><div class="container nav-wrap">
    <a class="brand" href="<?= site_url('/') ?>"><img src="<?= esc($settings['logo'] ?? '') ?>" alt="<?= esc($settings['site_name'] ?? 'Fynefield') ?>"></a>
    <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-navigation"><span aria-hidden="true">☰</span><span class="sr-only">Open navigation</span></button>
    <nav id="site-navigation" aria-label="Main navigation"><?php foreach ($navigation as $item): ?><a class="<?= $item['url'] === '/contact-us' ? 'nav-cta' : '' ?>" href="<?= esc($item['url']) ?>"><?= esc($item['label']) ?></a><?php endforeach ?></nav>
</div></header>
<?php if (session('success')): ?><div class="flash success container"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="flash error container"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('errors')): ?><div class="flash error container"><?= implode('<br>', array_map('esc', session('errors'))) ?></div><?php endif ?>
<div id="main-content" tabindex="-1"><?= $this->renderSection('content') ?></div>
<footer><div class="container footer-grid"><div><img class="footer-logo" src="<?= esc($settings['logo'] ?? '') ?>" alt=""><p><?= esc($settings['footer_text'] ?? '') ?></p></div><div><h3>Visit or call</h3><p><?= nl2br(esc($settings['address'] ?? '')) ?></p><p><a href="tel:<?= esc(preg_replace('/\s+/', '', $settings['contact_phone'] ?? '')) ?>"><?= esc($settings['contact_phone'] ?? '') ?></a></p></div><div><h3>Explore</h3><a href="<?= site_url('services') ?>">Our services</a><a href="<?= site_url('news') ?>">News & insights</a><a href="<?= site_url('contact-us') ?>">Make an inquiry</a></div></div><div class="container copyright">© <?= date('Y') ?> <?= esc($settings['site_name'] ?? 'Fynefield Petroleum') ?>. All rights reserved.</div></footer>
<aside class="mobile-contact-bar" aria-label="Quick contact"><a href="tel:<?= esc(preg_replace('/\s+/', '', $settings['contact_phone'] ?? '')) ?>">Call us</a><a href="<?= site_url('contact-us') ?>">Send an inquiry</a></aside>
<script src="<?= base_url('assets/js/site.js') ?>" defer></script>
</body>
</html>
