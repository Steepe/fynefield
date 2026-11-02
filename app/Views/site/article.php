<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php use App\Libraries\Cms; use App\Libraries\Markdown; ?>
<article class="rf-news-article rf-shell"><div><a class="rf-back" href="<?= esc($newsHref) ?>">← All news</a><span class="rf-meta"><b><?= esc($post['category'] ?: 'Company news') ?></b> · <?= Cms::longDate($post['published_at']) ?></span><h1><?= esc($post['title']) ?></h1><p class="rf-lede"><?= esc($post['excerpt']) ?></p></div><?php if ($post['cover_image']): ?><img class="rf-article-image" src="<?= esc($post['cover_image']) ?>" alt="<?= esc($post['title']) ?>"><?php endif ?><div class="rf-article-body"><?= Markdown::render($post['content']) ?><?php if (trim((string) $post['quote']) !== ''): ?><blockquote><?= esc(trim($post['quote'], "\" \n")) ?></blockquote><?php endif ?></div></article>
<section class="rf-newsletter"><div class="rf-shell"><div style="display:grid;gap:12px"><h2>Stay informed</h2><p>Receive operational updates and news from Fynefield.</p></div><a class="rf-button-dark" href="<?= site_url('news') ?>">All news →</a></div></section>
<?= $this->endSection() ?>
