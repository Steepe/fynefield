<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php if ($isPreview): ?><div class="preview-notice ff-preview-notice">Private preview — this page may be unpublished or awaiting review.</div><?php endif ?>

<?php if ($team): ?>
<section class="ff-page-hero">
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><a href="<?= site_url('about-us') ?>">About</a><span>/</span><span>Leadership</span></div>
        <h1>Our leadership</h1>
        <p>Practical energy-sector experience, combined with a commitment to safe, reliable service.</p>
    </div>
</section>
<section class="ff-leaders" data-profiles>
    <div class="ff-leader-grid">
        <?php foreach ($team as $index => $member): ?>
        <article class="ff-leader">
            <button class="ff-leader-image" type="button" data-profile-open="<?= $index ?>" aria-label="View profile of <?= esc($member['name'], 'attr') ?>"><img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>"></button>
            <div class="ff-leader-body">
                <span><?= esc($member['position']) ?></span>
                <h2><?= esc($member['name']) ?></h2>
                <p><?= esc($member['bio']) ?></p>
                <button class="ff-leader-more" type="button" data-profile-open="<?= $index ?>">View profile →</button>
            </div>
        </article>
        <?php endforeach ?>
    </div>
    <div class="ff-drawer-backdrop" data-profile-close hidden></div>
    <?php foreach ($team as $index => $member): ?>
    <aside class="ff-drawer" role="dialog" aria-modal="true" aria-label="<?= esc($member['name'], 'attr') ?>" data-profile="<?= $index ?>" tabindex="-1" hidden>
        <div class="ff-drawer-bar">
            <span>Profile <?= $index + 1 ?> of <?= count($team) ?></span>
            <div>
                <button type="button" data-profile-step="-1" aria-label="Previous profile">←</button>
                <button type="button" data-profile-step="1" aria-label="Next profile">→</button>
                <button type="button" class="ff-drawer-close" data-profile-close>Close</button>
            </div>
        </div>
        <div class="ff-drawer-intro">
            <img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>">
            <div><p><?= esc($member['position']) ?></p><h2><?= esc($member['name']) ?></h2></div>
        </div>
        <div class="ff-drawer-body">
            <p class="ff-drawer-lede"><?= esc($member['bio']) ?></p>
            <div class="ff-drawer-paras"><p>Fynefield’s leadership team combines practical sector experience with disciplined attention to dependable supply, safe operations and customer service.</p></div>
            <div class="ff-drawer-facts"><div><span>Role</span><strong><?= esc($member['position']) ?></strong></div><div><span>Company</span><strong>Fynefield Petroleum</strong></div></div>
        </div>
    </aside>
    <?php endforeach ?>
</section>

<?php elseif ($page['slug'] === 'about-us'): ?>
<section class="ff-page-hero">
    <img class="ff-page-hero-image is-about" src="<?= base_url('assets/media/fynefield-commissioning.jpeg') ?>" alt="">
    <div class="ff-page-hero-shade"></div>
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span>About us</span></div>
        <h1>Built for dependable supply.</h1>
        <p><?= esc($page['excerpt']) ?></p>
    </div>
</section>
<section class="ff-split">
    <div>
        <p class="ff-kicker">Our story</p>
        <div class="ff-about-copy"><?= $page['content'] ?></div>
    </div>
    <figure><img src="<?= base_url('assets/media/fynefield-commissioning.jpeg') ?>" alt="Commissioning of the Fynefield depot"></figure>
</section>
<section class="ff-stats is-flat">
    <div class="ff-stats-card">
        <?php foreach ([['2013', 'Year incorporated'], ['50M', 'Litres depot capacity'], ['13+', 'Tank trucks'], ['12+', 'Retail outlets, expanding']] as [$value, $label]): ?>
        <div><strong><?= esc($value) ?></strong><span><?= esc($label) ?></span></div>
        <?php endforeach ?>
    </div>
</section>
<section class="ff-pillars">
    <div class="ff-section-head">
        <div><p class="ff-kicker">What we do</p><h2 class="ff-h2 is-small">Every link in the value chain.</h2></div>
        <a class="ff-text-link" href="<?= site_url('services') ?>">All services →</a>
    </div>
    <div class="ff-pillar-grid">
        <?php foreach ([['Trading', 'Petroleum product trading that secures dependable supply.', 'services'], ['Bulk storage', 'A 50-million-litre multi-product depot in the Calabar Free Trade Zone.', 'services/bulk-storage'], ['Logistics', 'Product haulage to stations and customers in Calabar, Delta and Abuja.', 'services/logistics'], ['Retail', 'Outlets across Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT.', 'services/retail-outlets']] as $index => [$pillarTitle, $pillarBody, $pillarUrl]): ?>
        <a href="<?= site_url($pillarUrl) ?>"><span>0<?= $index + 1 ?></span><strong><?= esc($pillarTitle) ?></strong><p><?= esc($pillarBody) ?></p><b>Learn more →</b></a>
        <?php endforeach ?>
    </div>
</section>

<?php elseif (($page['template'] ?? 'standard') !== 'landing'): ?>
<section class="ff-page-hero">
    <?php if ($page['cover_image']): ?><img class="ff-page-hero-image" src="<?= esc($page['cover_image']) ?>" alt=""><div class="ff-page-hero-shade"></div><?php endif ?>
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span><?= esc($page['title']) ?></span></div>
        <h1><?= esc($page['title']) ?></h1>
        <?php if ($page['excerpt']): ?><p><?= esc($page['excerpt']) ?></p><?php endif ?>
    </div>
</section>
<section class="ff-content"><div class="ff-prose"><?= $page['content'] ?></div></section>
<?php endif ?>

<?php if ($locations): ?>
<section class="ff-locations">
    <div class="ff-section-head"><div><p class="ff-kicker">Our network</p><h2 class="ff-h2 is-small">Where we operate.</h2></div></div>
    <div class="trust-items"><?php foreach ($locations as $location): ?><article><strong><?= esc($location['name']) ?></strong><span><?= esc($location['address']) ?></span></article><?php endforeach ?></div>
</section>
<?php endif ?>
<?php if ($galleryImages): ?>
<section class="ff-gallery is-page"><?php foreach ($galleryImages as $image): ?><button class="gallery-trigger" type="button" data-lightbox-src="<?= esc($image, 'attr') ?>" data-lightbox-alt="Fynefield operations"><img src="<?= esc($image) ?>" alt="Fynefield operations"></button><?php endforeach ?></section>
<?php endif ?>
<?= view('site/partials/page_sections', ['sections' => $sections]) ?>
<?= $this->endSection() ?>
