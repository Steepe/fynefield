<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="article-hero"><div class="container"><p class="eyebrow">News & insights · <?= date('d M Y', strtotime($post['published_at'])) ?></p><h1><?= esc($post['title']) ?></h1><p><?= esc($post['excerpt']) ?></p></div></section><main class="container article prose"><img src="<?= esc($post['cover_image']) ?>" alt="<?= esc($post['title']) ?>"><div><?= $post['content'] ?></div></main>
<?= $this->endSection() ?>
