<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Cms;
use App\Libraries\Markdown;

$facts = json_decode((string) $service['facts'], true) ?: [];
$gallery = array_values(array_filter(json_decode((string) $service['gallery'], true) ?: []));
$contactHref = Cms::href('page:contact-us');
?>
<section class="ff-page-hero">
    <?php if ($service['image']): ?><img class="ff-page-hero-image is-faint" src="<?= esc($service['image']) ?>" alt=""><?php endif ?>
    <div class="ff-page-hero-shade is-service"></div>
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><a href="<?= esc(Cms::href('page:services')) ?>">Services</a><span>/</span><span><?= esc($service['title']) ?></span></div>
        <h1><?= esc($service['title']) ?></h1>
        <p><?= esc($service['summary']) ?></p>
    </div>
</section>

<nav class="ff-tabs" aria-label="Services">
    <div class="ff-tabs-inner"><?php foreach ($services as $item): ?><a class="<?= $item['slug'] === $service['slug'] ? 'is-active' : '' ?>" href="<?= site_url('services/' . $item['slug']) ?>"<?= $item['slug'] === $service['slug'] ? ' aria-current="page"' : '' ?>><?= esc($item['title']) ?></a><?php endforeach ?></div>
</nav>

<section class="ff-overview">
    <div>
        <p class="ff-kicker">Overview</p>
        <?php if ($service['lede']): ?><h2><?= esc($service['lede']) ?></h2><?php endif ?>
        <div class="ff-overview-body"><?= Markdown::render($service['body']) ?></div>
        <a class="ff-pill ff-pill-amber" href="<?= esc($contactHref) ?>"><?= esc($service['cta_label'] ?: 'Make an inquiry') ?></a>
    </div>
    <aside class="ff-glance" aria-label="At a glance">
        <?php foreach ($facts as $fact): ?><div><span><?= esc($fact['l'] ?? '') ?></span><strong><?= esc($fact['v'] ?? '') ?></strong></div><?php endforeach ?>
        <footer><span>Questions about this service?</span><a class="ff-accent-link" href="<?= esc($contactHref) ?>">Talk to our team →</a></footer>
    </aside>
</section>

<?php if ($gallery): ?>
<section class="ff-gallery">
    <?php foreach ($gallery as $src): ?>
    <button class="gallery-trigger" type="button" data-lightbox-src="<?= esc($src, 'attr') ?>" data-lightbox-alt="<?= esc($service['title'], 'attr') ?>"><img src="<?= esc($src) ?>" alt="<?= esc($service['title']) ?>"></button>
    <?php endforeach ?>
</section>
<?php endif ?>

<?php $others = array_filter($services, static fn (array $item): bool => $item['slug'] !== $service['slug']); ?>
<?php if ($others): ?>
<section class="ff-others">
    <div class="ff-others-inner">
        <h2>Other services</h2>
        <div class="ff-others-grid">
            <?php foreach ($others as $item): ?>
            <a href="<?= site_url('services/' . $item['slug']) ?>"><span><strong><?= esc($item['title']) ?></strong><small><?= esc($item['summary']) ?></small></span><span class="ff-round-arrow" aria-hidden="true">→</span></a>
            <?php endforeach ?>
        </div>
    </div>
</section>
<div class="ff-surface-spacer"></div>
<?php endif ?>
<?php if ($nav['showCta']): ?><?= view('site/sections/cta', ['s' => array_replace(Cms::sectionCommon(), Cms::sectionDefaults()['cta'], ['type' => 'cta']), 'cls' => '', 'idAttr' => '', 'contact' => $contact]) ?><?php endif ?>
<?= $this->endSection() ?>
