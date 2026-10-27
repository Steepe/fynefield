<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="page-hero plain"><div class="container"><p class="eyebrow">What we do</p><h1>Solutions that move energy forward.</h1><p>Integrated downstream expertise for customers and communities across Nigeria.</p></div></section>
<main class="container service-list"><?php foreach ($services as $i => $service): ?><article class="service-feature <?= $i % 2 ? 'reverse' : '' ?>"><img src="<?= esc($service['image']) ?>" alt=""><div><p class="eyebrow dark">0<?= $i + 1 ?></p><h2><?= esc($service['title']) ?></h2><p><?= esc($service['summary']) ?></p><a class="text-link" href="<?= site_url('services/' . $service['slug']) ?>">Learn more <span>→</span></a></div></article><?php endforeach ?></main>
<?= $this->endSection() ?>
