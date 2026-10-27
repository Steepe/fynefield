<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
$serviceMeta = [
    'bulk-storage' => ['need' => 'Supply or bulk storage', 'short' => 'bulk storage', 'kicker' => 'Infrastructure', 'summary' => 'Multi-product depot capacity in the Calabar Free Trade Zone, supporting reliable petroleum product availability.', 'facts' => [['50M L', 'Capacity'], ['Calabar FTZ', 'Location']]],
    'logistics' => ['need' => 'Delivery and logistics', 'short' => 'logistics', 'kicker' => 'Haulage', 'summary' => 'Essential product haulage to our stations and customers, through a dedicated fleet serving Calabar, Delta and Abuja.', 'facts' => [['13+', 'Tank trucks'], ['3', 'Core corridors']]],
    'retail-outlets' => ['need' => 'Find a retail outlet', 'short' => 'retail outlets', 'kicker' => 'Stations', 'summary' => 'Convenient access to dependable PMS through a network expanding to more than 12 strategically positioned outlets.', 'facts' => [['12+', 'Outlets'], ['6', 'States & FCT']]],
];
$meta = static fn (array $service): array => $serviceMeta[$service['slug']] ?? ['need' => $service['title'], 'short' => strtolower($service['title']), 'kicker' => '', 'summary' => $service['summary'], 'facts' => []];
$first = $services[0] ?? null;
?>
<section class="ff-page-hero">
    <div class="ff-page-hero-inner is-overlapped">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span>Services</span></div>
        <h1 class="is-wide">Solutions that move energy forward.</h1>
        <p class="is-wide">Integrated downstream expertise — product trading, storage, logistics and retail — for customers and communities across Nigeria.</p>
    </div>
</section>

<?php if ($first): ?>
<section class="ff-overlap">
    <div class="ff-card ff-finder" data-service-finder>
        <div class="ff-finder-choices">
            <p class="ff-kicker">Find the right solution</p>
            <h2>What can we help you move?</h2>
            <div class="ff-finder-list">
                <?php foreach ($services as $index => $service): ?>
                <?php $info = $meta($service); ?>
                <button type="button" class="<?= $index === 0 ? 'is-selected' : '' ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>" data-service-choice data-title="<?= esc($service['title'], 'attr') ?>" data-summary="<?= esc($info['summary'], 'attr') ?>" data-short="<?= esc($info['short'], 'attr') ?>" data-href="<?= esc(site_url('services/' . $service['slug']), 'attr') ?>"><span><?= esc($info['need']) ?></span><span data-choice-mark><?= $index === 0 ? '●' : '→' ?></span></button>
                <?php endforeach ?>
            </div>
        </div>
        <div class="ff-finder-result" aria-live="polite">
            <div>
                <small>Recommended service</small>
                <strong data-service-result-title><?= esc($first['title']) ?></strong>
                <p data-service-result-copy><?= esc($meta($first)['summary']) ?></p>
            </div>
            <div class="ff-actions">
                <a class="ff-pill ff-pill-dark is-sm" href="<?= site_url('services/' . $first['slug']) ?>" data-service-result-link>Explore&nbsp;<span data-service-result-short><?= esc($meta($first)['short']) ?></span></a>
                <a class="ff-pill ff-pill-line is-sm" href="<?= site_url('contact-us') ?>">Ask our team</a>
            </div>
        </div>
    </div>
</section>
<?php endif ?>

<section class="ff-service-rows">
    <?php foreach ($services as $index => $service): ?>
    <?php $info = $meta($service); ?>
    <article class="ff-service-row<?= $index % 2 ? ' is-reversed' : '' ?>">
        <img src="<?= esc($service['image']) ?>" alt="<?= esc($service['title']) ?>">
        <div>
            <p class="ff-kicker">0<?= $index + 1 ?> · <?= esc($info['kicker']) ?></p>
            <h2 class="ff-h2 is-small"><?= esc($service['title']) ?></h2>
            <p><?= esc($info['summary']) ?></p>
            <?php if ($info['facts']): ?><div class="ff-facts is-wide"><?php foreach ($info['facts'] as [$value, $label]): ?><div><strong><?= esc($value) ?></strong><span><?= esc($label) ?></span></div><?php endforeach ?></div><?php endif ?>
            <a class="ff-pill ff-pill-dark" href="<?= site_url('services/' . $service['slug']) ?>">Learn more</a>
        </div>
    </article>
    <?php endforeach ?>
</section>
<?= $this->endSection() ?>
