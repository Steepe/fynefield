<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc($settings['site_tagline'] ?? 'Fynefield Petroleum') ?>">
    <title><?= esc($title ?? ($settings['site_name'] ?? 'Fynefield Petroleum')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
</head>
<body>
<div class="topbar"><div class="container"><span><?= esc($settings['contact_phone'] ?? '') ?></span><a href="mailto:<?= esc($settings['contact_email'] ?? '') ?>"><?= esc($settings['contact_email'] ?? '') ?></a></div></div>
<header class="site-header"><div class="container nav-wrap">
    <a class="brand" href="<?= site_url('/') ?>"><img src="<?= esc($settings['logo'] ?? '') ?>" alt="<?= esc($settings['site_name'] ?? 'Fynefield') ?>"></a>
    <nav aria-label="Main navigation"><a href="<?= site_url('/') ?>">Home</a><a href="<?= site_url('about-us') ?>">About</a><a href="<?= site_url('services') ?>">Services</a><a href="<?= site_url('news') ?>">News</a><a href="<?= site_url('management-team') ?>">Leadership</a><a class="nav-cta" href="<?= site_url('contact-us') ?>">Contact us</a></nav>
</div></header>
<?php if (session('success')): ?><div class="flash success container"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('errors')): ?><div class="flash error container"><?= implode('<br>', array_map('esc', session('errors'))) ?></div><?php endif ?>
<?= $this->renderSection('content') ?>
<footer><div class="container footer-grid"><div><img class="footer-logo" src="<?= esc($settings['logo'] ?? '') ?>" alt=""><p><?= esc($settings['footer_text'] ?? '') ?></p></div><div><h3>Visit or call</h3><p><?= nl2br(esc($settings['address'] ?? '')) ?></p><p><a href="tel:<?= esc(preg_replace('/\s+/', '', $settings['contact_phone'] ?? '')) ?>"><?= esc($settings['contact_phone'] ?? '') ?></a></p></div><div><h3>Explore</h3><a href="<?= site_url('services') ?>">Our services</a><a href="<?= site_url('news') ?>">News & insights</a><a href="<?= site_url('contact-us') ?>">Make an inquiry</a></div></div><div class="container copyright">© <?= date('Y') ?> <?= esc($settings['site_name'] ?? 'Fynefield Petroleum') ?>. All rights reserved.</div></footer>
</body>
</html>
