<?php
use App\Libraries\Cms;

$overlay = $s['overlay'] ?? 'Strong';
$primary = trim((string) $s['primaryLabel']) !== '' ? [$s['primaryLabel'], Cms::href($s['primaryLink'], $s['primaryLinkUrl'] ?? '')] : null;
$secondary = trim((string) $s['secondaryLabel']) !== '' ? [$s['secondaryLabel'], Cms::href($s['secondaryLink'], $s['secondaryLinkUrl'] ?? '')] : null;
?>
<?php if ($s['height'] === 'Tall'): ?>
<section class="ff-hero ff-overlay-<?= strtolower($overlay) ?><?= $cls ?>"<?= $idAttr ?> data-hero>
    <?php if ($s['image']): ?><img class="ff-hero-image" src="<?= esc($s['image']) ?>" alt=""><?php endif ?>
    <div class="ff-hero-shade"></div>
    <div class="ff-hero-inner">
        <div class="ff-hero-copy">
            <?php if ($s['breadcrumb']): ?><div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span><?= esc($pageTitle) ?></span></div><?php endif ?>
            <?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-hero-eyebrow"><?= esc($s['eyebrow']) ?></p><?php endif ?>
            <h1><?= esc($s['heading']) ?></h1>
            <?php if (trim((string) $s['body']) !== ''): ?><p class="ff-hero-lede"><?= esc($s['body']) ?></p><?php endif ?>
            <?php if ($primary || $secondary): ?>
            <div class="ff-actions">
                <?php if ($primary): ?><a class="ff-pill ff-pill-amber is-lg" href="<?= esc($primary[1]) ?>"><?= esc($primary[0]) ?></a><?php endif ?>
                <?php if ($secondary): ?><a class="ff-pill ff-pill-ghost is-lg" href="<?= esc($secondary[1]) ?>"><?= esc($secondary[0]) ?></a><?php endif ?>
            </div>
            <?php endif ?>
        </div>
    </div>
</section>
<?php else: ?>
<section class="ff-page-hero ff-overlay-<?= strtolower($overlay) ?><?= $s['height'] === 'Standard' ? ' is-standard' : '' ?><?= $cls ?>"<?= $idAttr ?>>
    <?php if ($s['image']): ?><img class="ff-page-hero-image is-about" src="<?= esc($s['image']) ?>" alt=""><div class="ff-page-hero-shade"></div><?php endif ?>
    <div class="ff-page-hero-inner<?= $overlapped ? ' is-overlapped' : '' ?>">
        <?php if ($s['breadcrumb']): ?><div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span><?= esc($pageTitle) ?></span></div><?php endif ?>
        <?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-hero-eyebrow"><?= esc($s['eyebrow']) ?></p><?php endif ?>
        <h1 class="is-wide"><?= esc($s['heading']) ?></h1>
        <?php if (trim((string) $s['body']) !== ''): ?><p class="is-wide"><?= esc($s['body']) ?></p><?php endif ?>
        <?php if ($primary || $secondary): ?>
        <div class="ff-actions is-spaced">
            <?php if ($primary): ?><a class="ff-pill ff-pill-amber is-lg" href="<?= esc($primary[1]) ?>"><?= esc($primary[0]) ?></a><?php endif ?>
            <?php if ($secondary): ?><a class="ff-pill ff-pill-ghost is-lg" href="<?= esc($secondary[1]) ?>"><?= esc($secondary[0]) ?></a><?php endif ?>
        </div>
        <?php endif ?>
    </div>
</section>
<?php endif ?>
