<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
$details = [
    'bulk-storage' => ['We operate a 50-million-litre capacity multi-product depot in the Calabar Free Trade Zone, Cross River State.', 'Our storage infrastructure supports dependable supply for customers and for our own growing network — keeping product available when markets tighten.', 'Enquire about storage', [['Capacity', '50,000,000 litres'], ['Location', 'Calabar Free Trade Zone'], ['State', 'Cross River'], ['Products', 'Multi-product']]],
    'logistics' => ['Our logistics business provides essential product haulage to our stations and customers in Calabar, Delta and Abuja.', 'The operation runs on a dedicated fleet of more than 13 tank trucks, connecting our depot to the stations and commercial customers who rely on steady supply.', 'Arrange a delivery', [['Fleet', '13+ tank trucks'], ['Corridors', 'Calabar · Delta · Abuja'], ['Serving', 'Stations & customers']]],
    'retail-outlets' => ['Our retail network is expanding to more than 12 strategically positioned outlets.', 'Fynefield stations serve motorists and businesses across Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT with dependable PMS.', 'Discuss a retail partnership', [['Outlets', '12+, expanding'], ['Coverage', '5 states + FCT'], ['Product', 'PMS']]],
];
$detail = $details[$service['slug']] ?? [$service['summary'], strip_tags($service['content']), 'Make an inquiry', []];
$tabs = [
    'bulk-storage' => ['Bulk Storage', 'Strategic multi-product petroleum storage.'],
    'logistics' => ['Logistics', 'Dependable product haulage and delivery.'],
    'retail-outlets' => ['Retail Outlets', 'Convenient access to quality petroleum products.'],
];
$gallery = [[$service['image'], $service['title']], [base_url('assets/media/fynefield-commissioning.jpeg'), 'Fynefield operations'], [base_url('assets/media/frontpage-image4.jpg'), 'Fynefield operations']];
?>
<section class="ff-page-hero">
    <img class="ff-page-hero-image is-faint" src="<?= esc($service['image']) ?>" alt="">
    <div class="ff-page-hero-shade is-service"></div>
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><a href="<?= site_url('services') ?>">Services</a><span>/</span><span><?= esc($service['title']) ?></span></div>
        <h1><?= esc($service['title']) ?></h1>
        <p><?= esc($service['summary']) ?></p>
    </div>
</section>

<nav class="ff-tabs" aria-label="Services">
    <div class="ff-tabs-inner"><?php foreach ($tabs as $slug => [$label]): ?><a class="<?= $service['slug'] === $slug ? 'is-active' : '' ?>" href="<?= site_url('services/' . $slug) ?>"<?= $service['slug'] === $slug ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a><?php endforeach ?></div>
</nav>

<section class="ff-overview">
    <div>
        <p class="ff-kicker">Overview</p>
        <h2><?= esc($detail[0]) ?></h2>
        <p class="ff-overview-body"><?= esc($detail[1]) ?></p>
        <a class="ff-pill ff-pill-amber" href="<?= site_url('contact-us') ?>"><?= esc($detail[2]) ?></a>
    </div>
    <aside class="ff-glance" aria-label="At a glance">
        <?php foreach ($detail[3] as [$label, $value]): ?><div><span><?= esc($label) ?></span><strong><?= esc($value) ?></strong></div><?php endforeach ?>
        <footer><span>Questions about this service?</span><a class="ff-accent-link" href="<?= site_url('contact-us') ?>">Talk to our team →</a></footer>
    </aside>
</section>

<section class="ff-gallery">
    <?php foreach ($gallery as [$src, $alt]): ?>
    <button class="gallery-trigger" type="button" data-lightbox-src="<?= esc($src, 'attr') ?>" data-lightbox-alt="<?= esc($alt, 'attr') ?>"><img src="<?= esc($src) ?>" alt="<?= esc($alt) ?>"></button>
    <?php endforeach ?>
</section>

<section class="ff-others">
    <div class="ff-others-inner">
        <h2>Other services</h2>
        <div class="ff-others-grid">
            <?php foreach ($tabs as $slug => [$label, $summary]): ?><?php if ($slug !== $service['slug']): ?>
            <a href="<?= site_url('services/' . $slug) ?>"><span><strong><?= esc($label) ?></strong><small><?= esc($summary) ?></small></span><span class="ff-round-arrow" aria-hidden="true">→</span></a>
            <?php endif ?><?php endforeach ?>
        </div>
    </div>
</section>
<div class="ff-surface-spacer"></div>
<?= $this->endSection() ?>
