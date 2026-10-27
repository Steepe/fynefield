<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php if ($isPreview): ?><div class="preview-notice">Private preview — this page may be unpublished or awaiting review.</div><?php endif ?>
<?php if (($page['template'] ?? 'standard') !== 'landing'): ?><section class="page-hero" style="background-image:linear-gradient(rgba(5,21,36,.8),rgba(5,21,36,.8)),url('<?= esc($page['cover_image']) ?>')"><div class="container"><p class="eyebrow">Fynefield Petroleum</p><h1><?= esc($page['title']) ?></h1><p><?= esc($page['excerpt']) ?></p></div></section>
<main class="container prose content-page"><?= $page['content'] ?>
<?php if ($team): ?><div class="team-grid"><?php foreach ($team as $member): ?><article><img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>"><h3><?= esc($member['name']) ?></h3><p class="position"><?= esc($member['position']) ?></p><p><?= esc($member['bio']) ?></p></article><?php endforeach ?></div><?php endif ?></main>
<?php if ($locations): ?><section class="container location-list"><?php foreach ($locations as $location): ?><article><h3><?= esc($location['name']) ?></h3><p><?= esc($location['address']) ?></p><a href="tel:<?= esc(preg_replace('/\s+/', '', $location['phone'] ?? '')) ?>"><?= esc($location['phone']) ?></a></article><?php endforeach ?></section><?php endif ?>
<?php if ($galleryImages): ?><section class="container content-gallery"><?php foreach ($galleryImages as $image): ?><img src="<?= esc($image) ?>" alt="Fynefield operations"><?php endforeach ?></section><?php endif ?>
<?php endif ?>
<?= view('site/partials/page_sections', ['sections' => $sections]) ?>
<?= $this->endSection() ?>
