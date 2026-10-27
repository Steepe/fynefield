<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="page-hero plain"><div class="container"><p class="eyebrow">News & insights</p><h1>What’s happening at Fynefield.</h1><p>Updates from our operations, people and industry engagement.</p></div></section><main class="container news-grid listing"><?php foreach ($posts as $post): ?><article><img src="<?= esc($post['cover_image']) ?>" alt="<?= esc($post['title']) ?>"><p class="date"><?= date('d M Y', strtotime($post['published_at'])) ?></p><h2><a href="<?= site_url('news/' . $post['slug']) ?>"><?= esc($post['title']) ?></a></h2><p><?= esc($post['excerpt']) ?></p><a class="text-link" href="<?= site_url('news/' . $post['slug']) ?>">Read story →</a></article><?php endforeach ?></main>
<?= $this->endSection() ?>
