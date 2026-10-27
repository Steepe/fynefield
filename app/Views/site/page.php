<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="page-hero" style="background-image:linear-gradient(rgba(5,21,36,.8),rgba(5,21,36,.8)),url('<?= esc($page['cover_image']) ?>')"><div class="container"><p class="eyebrow">Fynefield Petroleum</p><h1><?= esc($page['title']) ?></h1><p><?= esc($page['excerpt']) ?></p></div></section>
<main class="container prose content-page"><?= $page['content'] ?>
<?php if ($team): ?><div class="team-grid"><?php foreach ($team as $member): ?><article><img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>"><h3><?= esc($member['name']) ?></h3><p class="position"><?= esc($member['position']) ?></p><p><?= esc($member['bio']) ?></p></article><?php endforeach ?></div><?php endif ?></main>
<?= $this->endSection() ?>
