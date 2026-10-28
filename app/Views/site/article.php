<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Cms;
use App\Libraries\Markdown;

$readingMinutes = max(1, (int) ceil(str_word_count(Markdown::plain($post['content'])) / 200));
?>
<?php if ($isPreview): ?><div class="preview-notice ff-preview-notice">Private preview — this story may be unpublished or awaiting review.</div><?php endif ?>
<div class="ff-reading-progress" aria-hidden="true"><i data-reading-progress></i></div>
<section class="ff-page-hero ff-article-hero">
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= esc($newsHref) ?>">← News</a><span>·</span><span><?= Cms::longDate($post['published_at']) ?></span><span>·</span><span><?= $readingMinutes ?> min read</span></div>
        <h1><?= esc($post['title']) ?></h1>
        <?php if ($post['excerpt']): ?><p><?= esc($post['excerpt']) ?></p><?php endif ?>
    </div>
</section>
<div class="ff-article-cover"><?php if ($post['cover_image']): ?><img src="<?= esc($post['cover_image']) ?>" alt="<?= esc($post['title']) ?>"><?php else: ?><span class="ff-image-empty is-active"></span><?php endif ?></div>
<section class="ff-article" data-reading-body>
    <article class="ff-article-copy">
        <?= Markdown::render($post['content']) ?>
        <?php if (trim((string) $post['quote']) !== ''): ?><blockquote>"<?= esc(trim($post['quote'], "\" \n")) ?>"</blockquote><?php endif ?>
        <p>To discuss supply, storage or logistics with our team, <a href="<?= esc(Cms::href('page:contact-us')) ?>">send an inquiry</a>.</p>
    </article>
    <aside class="ff-share">
        <span>Share</span>
        <button type="button" data-copy-link>Copy link</button>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode(current_url()) ?>" target="_blank" rel="noopener">LinkedIn</a>
        <a href="mailto:?subject=<?= rawurlencode($post['title']) ?>&body=<?= rawurlencode(current_url()) ?>">Email</a>
    </aside>
</section>
<?php if ($nextPost): ?>
<section class="ff-next">
    <div class="ff-next-inner">
        <a href="<?= site_url('news/' . $nextPost['slug']) ?>">
            <?php if ($nextPost['cover_image']): ?><img src="<?= esc($nextPost['cover_image']) ?>" alt=""><?php else: ?><span class="ff-image-empty is-active"></span><?php endif ?>
            <div><small>Next story · <?= Cms::longDate($nextPost['published_at']) ?></small><h2><?= esc($nextPost['title']) ?></h2><span class="ff-accent-link">Read article →</span></div>
        </a>
    </div>
</section>
<div class="ff-surface-spacer"></div>
<?php endif ?>
<?php if ($nav['showCta']): ?><?= view('site/sections/cta', ['s' => array_replace(Cms::sectionCommon(), Cms::sectionDefaults()['cta'], ['type' => 'cta']), 'cls' => '', 'idAttr' => '', 'contact' => $contact]) ?><?php endif ?>
<?= $this->endSection() ?>
