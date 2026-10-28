<?php use App\Libraries\Cms; ?>
<section class="ff-pillars<?= $cls ?>"<?= $idAttr ?>>
    <?php if (trim($s['eyebrow'] . $s['heading'] . $s['linkLabel']) !== ''): ?>
    <div class="ff-section-head">
        <div><?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker"><?= esc($s['eyebrow']) ?></p><?php endif ?><?php if (trim((string) $s['heading']) !== ''): ?><h2 class="ff-h2 is-small"><?= esc($s['heading']) ?></h2><?php endif ?></div>
        <?php if (trim((string) $s['linkLabel']) !== ''): ?><a class="ff-text-link" href="<?= esc(Cms::href($s['linkTo'], $s['linkToUrl'] ?? '')) ?>"><?= esc($s['linkLabel']) ?> →</a><?php endif ?>
    </div>
    <?php endif ?>
    <div class="ff-pillar-grid" style="--ff-cols:<?= (int) $s['columns'] ?: 3 ?>">
        <?php foreach ($s['items'] as $index => $card): ?>
        <a href="<?= esc(Cms::href($card['link'] ?? '', $card['linkUrl'] ?? '')) ?>"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><strong><?= esc($card['title'] ?? '') ?></strong><p><?= esc($card['body'] ?? '') ?></p><b>Learn more →</b></a>
        <?php endforeach ?>
    </div>
</section>
