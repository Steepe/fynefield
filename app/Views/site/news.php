<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
$featured = $posts[0] ?? null;
$years = array_values(array_unique(array_map(static fn ($post) => date('Y', strtotime($post['published_at'])), $posts)));
?>
<section class="ff-page-hero">
    <div class="ff-page-hero-inner<?= $featured ? ' is-overlapped' : '' ?>">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span>News</span></div>
        <h1>News &amp; insights</h1>
        <p>Updates from our operations, people and industry engagement.</p>
    </div>
</section>

<?php if ($featured): ?>
<section class="ff-overlap">
    <a class="ff-card ff-feature" href="<?= site_url('news/' . $featured['slug']) ?>">
        <div class="ff-feature-image"><img src="<?= esc($featured['cover_image']) ?>" alt=""></div>
        <div class="ff-feature-copy">
            <div class="ff-feature-meta"><span class="ff-badge">Featured</span><span><?= date('j F Y', strtotime($featured['published_at'])) ?></span></div>
            <h2><?= esc($featured['title']) ?></h2>
            <p><?= esc($featured['excerpt']) ?></p>
            <b>Read article →</b>
        </div>
    </a>
</section>
<?php endif ?>

<section class="ff-stories" data-news-filter>
    <div class="ff-stories-head">
        <h2>All stories</h2>
        <div class="ff-chips" role="group" aria-label="Filter stories by year">
            <button type="button" class="ff-chip is-selected" aria-pressed="true" data-year="All">All</button>
            <?php foreach ($years as $year): ?><button type="button" class="ff-chip" aria-pressed="false" data-year="<?= esc($year, 'attr') ?>"><?= esc($year) ?></button><?php endforeach ?>
        </div>
    </div>
    <div class="ff-story-grid">
        <?php foreach ($posts as $post): ?>
        <a class="ff-story" data-news-year="<?= date('Y', strtotime($post['published_at'])) ?>" href="<?= site_url('news/' . $post['slug']) ?>">
            <img src="<?= esc($post['cover_image']) ?>" alt="">
            <div><small><?= date('j F Y', strtotime($post['published_at'])) ?></small><h3><?= esc($post['title']) ?></h3><p><?= esc($post['excerpt']) ?></p></div>
        </a>
        <?php endforeach ?>
    </div>
    <p class="ff-news-empty" data-news-empty<?= $posts ? ' hidden' : '' ?>>No stories published<span data-news-empty-year></span> yet.</p>
</section>

<section class="ff-newsletter">
    <div class="ff-newsletter-inner">
        <div><h2>Stay informed</h2><p>Receive operational updates and news from Fynefield.</p></div>
        <form data-newsletter>
            <input type="email" required placeholder="Your work email" aria-label="Your work email">
            <button class="ff-pill ff-pill-dark" type="submit">Subscribe</button>
        </form>
    </div>
</section>
<div class="ff-surface-spacer"></div>
<?= $this->endSection() ?>
