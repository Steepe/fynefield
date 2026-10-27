<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
$commissioning = base_url('assets/media/fynefield-commissioning.jpeg');
$serviceImages = array_column($services, 'image', 'slug');
$tabs = [
    ['title' => 'Trading', 'body' => 'Petroleum product trading that secures dependable supply for depots, stations and commercial customers.', 'url' => 'services', 'image' => base_url('assets/media/frontpage-image6.jpg'), 'facts' => [['PMS · AGO', 'Core products']]],
    ['title' => 'Bulk storage', 'body' => 'Our multi-product depot in the Calabar Free Trade Zone keeps product available when markets tighten.', 'url' => 'services/bulk-storage', 'image' => $serviceImages['bulk-storage'] ?? $commissioning, 'facts' => [['50M L', 'Capacity'], ['Calabar FTZ', 'Location']]],
    ['title' => 'Logistics', 'body' => 'A dedicated fleet delivers product safely to our stations and customers in Calabar, Delta and Abuja.', 'url' => 'services/logistics', 'image' => $serviceImages['logistics'] ?? $commissioning, 'facts' => [['13+', 'Tank trucks'], ['3', 'Core corridors']]],
    ['title' => 'Retail', 'body' => 'A growing network of outlets offering dependable PMS to motorists and businesses.', 'url' => 'services/retail-outlets', 'image' => $serviceImages['retail-outlets'] ?? $commissioning, 'facts' => [['12+', 'Outlets'], ['6', 'States & FCT']]],
];
$topics = [['Bulk supply', 'Product, volume and delivery location'], ['Storage', 'Product and storage duration'], ['Haulage', 'Pickup, destination and volume'], ['Retail partnership', 'Location and proposal'], ['Other', 'How can we help?']];
$phone = $settings['contact_phone'] ?? '';
$email = $settings['contact_email'] ?? '';
$errors = session('errors') ?: [];
?>
<section class="ff-hero" data-hero>
    <img class="ff-hero-image" src="<?= $commissioning ?>" alt="">
    <div class="ff-hero-shade"></div>
    <div class="ff-hero-inner">
        <div class="ff-hero-copy">
            <p class="ff-hero-eyebrow">Fynefield Petroleum Company Limited</p>
            <h1>Powering Nigeria's downstream energy, responsibly.</h1>
            <p class="ff-hero-lede">Integrated trading, storage, logistics and retail — built for dependable supply since 2013.</p>
            <div class="ff-actions">
                <a class="ff-pill ff-pill-amber is-lg" href="#services">Explore our services</a>
                <a class="ff-pill ff-pill-ghost is-lg" href="<?= site_url('about-us') ?>">About us</a>
            </div>
        </div>
    </div>
</section>

<section class="ff-stats" data-stats>
    <div class="ff-stats-card">
        <div><strong>2013</strong><span>Year incorporated</span></div>
        <div><strong data-count-to="50" data-count-suffix="M">50M</strong><span>Litres of storage</span></div>
        <div><strong data-count-to="13" data-count-suffix="+">13+</strong><span>Tank trucks</span></div>
        <div><strong data-count-to="12" data-count-suffix="+">12+</strong><span>Retail outlets</span></div>
    </div>
</section>

<section id="services" class="ff-what" data-service-tabs>
    <div class="ff-section-head is-tight">
        <div><p class="ff-kicker">What we do</p><h2 class="ff-h2">Every link in the downstream value chain.</h2></div>
        <a class="ff-text-link" href="<?= site_url('services') ?>">All services →</a>
    </div>
    <div class="ff-tabset" role="tablist" aria-label="What we do">
        <?php foreach ($tabs as $index => $tab): ?>
        <button type="button" class="<?= $index === 0 ? 'is-active' : '' ?>" role="tab" id="service-tab-<?= $index ?>" aria-controls="service-panel-<?= $index ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>" data-service-tab><span>0<?= $index + 1 ?></span><span><?= esc($tab['title']) ?></span><span class="ff-tab-track"><i data-service-tab-fill></i></span></button>
        <?php endforeach ?>
    </div>
    <div class="ff-tab-panel">
        <div class="ff-tab-images">
            <?php foreach ($tabs as $index => $tab): ?><img class="<?= $index === 0 ? 'is-active' : '' ?>" src="<?= esc($tab['image']) ?>" alt="<?= esc($tab['title']) ?>" data-service-tab-image><?php endforeach ?>
        </div>
        <?php foreach ($tabs as $index => $tab): ?>
        <div class="ff-tab-copy" id="service-panel-<?= $index ?>" role="tabpanel" aria-labelledby="service-tab-<?= $index ?>" data-service-tab-panel<?= $index ? ' hidden' : '' ?>>
            <h3><?= esc($tab['title']) ?></h3>
            <p><?= esc($tab['body']) ?></p>
            <div class="ff-facts"><?php foreach ($tab['facts'] as [$value, $label]): ?><div><strong><?= esc($value) ?></strong><span><?= esc($label) ?></span></div><?php endforeach ?></div>
            <a class="ff-pill ff-pill-dark" href="<?= site_url($tab['url']) ?>">Learn more</a>
        </div>
        <?php endforeach ?>
    </div>
</section>

<section class="ff-network">
    <div class="ff-network-inner">
        <div class="ff-network-map"><iframe src="<?= base_url('assets/network-map.html') ?>" title="Fynefield network map" loading="lazy"></iframe></div>
        <div>
            <p class="ff-kicker is-light">Our footprint</p>
            <h2 class="ff-h2">One depot. A growing national network.</h2>
            <p class="ff-network-lede">Product flows from our Calabar Free Trade Zone depot to outlets and customers across five states and the FCT.</p>
            <div class="ff-states"><?php foreach (['Cross River', 'FCT Abuja', 'Delta', 'Akwa Ibom', 'Benue', 'Borno'] as $state): ?><span tabindex="0" data-network-state="<?= esc($state, 'attr') ?>"><?= esc($state) ?></span><?php endforeach ?></div>
        </div>
    </div>
</section>

<?php if ($posts): ?>
<section class="ff-latest">
    <div class="ff-section-head">
        <div><p class="ff-kicker">Newsroom</p><h2 class="ff-h2">Latest news</h2></div>
        <a class="ff-text-link" href="<?= site_url('news') ?>">View all →</a>
    </div>
    <div class="ff-latest-grid">
        <?php foreach ($posts as $post): ?>
        <a href="<?= site_url('news/' . $post['slug']) ?>">
            <img src="<?= esc($post['cover_image']) ?>" alt="">
            <div><small><?= date('j F Y', strtotime($post['published_at'])) ?></small><h3><?= esc($post['title']) ?></h3><span class="ff-accent-link">Read article →</span></div>
        </a>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>

<section id="inquiry" class="ff-band ff-home-inquiry">
    <div class="ff-band-card">
        <div class="ff-band-amber">
            <h2>Let's talk supply.</h2>
            <p>Bulk supply, storage, haulage or a retail partnership — tell us what you need.</p>
            <div class="ff-band-lines"><a href="tel:<?= esc(preg_replace('/\s+/', '', $phone)) ?>"><?= esc($phone) ?></a><a href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a></div>
        </div>
        <div class="ff-band-white">
            <?php if (session('success')): ?>
            <div class="ff-sent" role="status">
                <div class="ff-sent-label">✓ Received<?= session('inquiry_reference') ? ' · ' . esc(session('inquiry_reference')) : '' ?></div>
                <p class="ff-sent-title">Thank you — we'll reply within one business day.</p>
                <a class="ff-sent-again" href="<?= site_url('/') ?>#inquiry">Send another →</a>
            </div>
            <?php else: ?>
            <form class="ff-form" method="post" action="<?= site_url('contact-us') ?>" data-contact-form>
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="home">
                <input type="hidden" name="subject" value="<?= esc($topics[0][0] . ' inquiry', 'attr') ?>" data-contact-subject-value>
                <?php if (session('error') || $errors): ?><div class="ff-form-errors" role="alert"><?= session('error') ? esc(session('error')) : implode('<br>', array_map('esc', $errors)) ?></div><?php endif ?>
                <div class="ff-chips" role="group" aria-label="I'm interested in">
                    <?php foreach ($topics as $i => [$topic, $messageLabel]): ?>
                    <button type="button" class="ff-chip<?= $i === 0 ? ' is-selected' : '' ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" data-contact-topic data-message="<?= esc($messageLabel, 'attr') ?>"><?= esc($topic) ?></button>
                    <?php endforeach ?>
                </div>
                <div class="ff-field-row is-compact">
                    <input class="ff-input" name="name" value="<?= old('name') ?>" placeholder="Full name" aria-label="Full name" autocomplete="name" required>
                    <input class="ff-input" type="email" name="email" value="<?= old('email') ?>" placeholder="Email address" aria-label="Email address" autocomplete="email" required>
                </div>
                <textarea class="ff-input" name="message" rows="3" placeholder="<?= esc($topics[0][1], 'attr') ?>" aria-label="Message" required data-contact-message><?= old('message') ?></textarea>
                <label class="ff-consent"><input type="checkbox" name="privacy_consent" value="1" required>I consent to Fynefield using these details to respond to this inquiry. Please do not send payment or card information.</label>
                <input class="honeypot-field" name="company_website" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="ff-pill ff-pill-dark" type="submit">Send inquiry</button>
            </form>
            <?php endif ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
