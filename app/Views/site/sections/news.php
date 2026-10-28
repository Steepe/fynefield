<?php
use App\Libraries\Cms;

$posts = array_values(array_filter($data['posts'], static fn (array $post): bool => $s['category'] === 'All categories' || $post['category'] === $s['category']));
$layout = $s['layout'];
$featured = null;
if ($layout === 'Featured + grid' && $posts) {
    foreach ($posts as $post) if ($post['featured']) { $featured = $post; break; }
    $featured ??= $posts[0];
}
if ($s['count'] !== 'All') $posts = array_slice($posts, 0, (int) $s['count']);
$years = array_values(array_unique(array_map(static fn ($post) => date('Y', strtotime($post['published_at'])), $posts)));
$cover = static fn (array $post): string => $post['cover_image'] ? '<img src="' . esc($post['cover_image'], 'attr') . '" alt="">' : '<span class="ff-image-empty is-active"></span>';
$newsHref = Cms::href('page:news');
?>
<?php if ($featured): ?>
<section class="ff-overlap<?= $afterHero ? '' : ' is-flat' ?>">
    <a class="ff-card ff-feature" href="<?= site_url('news/' . $featured['slug']) ?>">
        <div class="ff-feature-image"><?= $cover($featured) ?></div>
        <div class="ff-feature-copy">
            <div class="ff-feature-meta"><span class="ff-badge">Featured</span><span><?= Cms::longDate($featured['published_at']) ?></span></div>
            <h2><?= esc($featured['title']) ?></h2>
            <p><?= esc($featured['excerpt']) ?></p>
            <b>Read article →</b>
        </div>
    </a>
</section>
<?php endif ?>

<?php if ($layout === 'Compact list'): ?>
<section class="ff-latest<?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-section-head">
        <div><?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker"><?= esc($s['eyebrow']) ?></p><?php endif ?><h2 class="ff-h2"><?= esc($s['heading']) ?></h2></div>
        <a class="ff-text-link" href="<?= esc($newsHref) ?>">View all →</a>
    </div>
    <div class="ff-latest-grid">
        <?php foreach ($posts as $post): ?>
        <a href="<?= site_url('news/' . $post['slug']) ?>"><?= $cover($post) ?><div><small><?= Cms::longDate($post['published_at']) ?></small><h3><?= esc($post['title']) ?></h3><span class="ff-accent-link">Read article →</span></div></a>
        <?php endforeach ?>
    </div>
</section>
<?php else: ?>
<section class="ff-stories<?= $cls ?>"<?= $idAttr ?><?= $s['yearFilter'] ? ' data-news-filter' : '' ?>>
    <div class="ff-stories-head">
        <div><?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker"><?= esc($s['eyebrow']) ?></p><?php endif ?><h2><?= esc($s['heading']) ?></h2></div>
        <?php if ($s['yearFilter']): ?>
        <div class="ff-chips" role="group" aria-label="Filter stories by year">
            <button type="button" class="ff-chip is-selected" aria-pressed="true" data-year="All">All</button>
            <?php foreach ($years as $year): ?><button type="button" class="ff-chip" aria-pressed="false" data-year="<?= esc($year, 'attr') ?>"><?= esc($year) ?></button><?php endforeach ?>
        </div>
        <?php elseif ($s['count'] !== 'All'): ?>
        <a class="ff-text-link" href="<?= esc($newsHref) ?>">View all →</a>
        <?php endif ?>
    </div>
    <div class="ff-story-grid">
        <?php foreach ($posts as $post): ?>
        <a class="ff-story" data-news-year="<?= date('Y', strtotime($post['published_at'])) ?>" href="<?= site_url('news/' . $post['slug']) ?>">
            <?= $cover($post) ?>
            <div><small><?= Cms::longDate($post['published_at']) ?></small><h3><?= esc($post['title']) ?></h3><p><?= esc($post['excerpt']) ?></p></div>
        </a>
        <?php endforeach ?>
    </div>
    <p class="ff-news-empty" data-news-empty<?= $posts ? ' hidden' : '' ?>>No stories published<span data-news-empty-year></span> yet.</p>
</section>
<?php endif ?>
