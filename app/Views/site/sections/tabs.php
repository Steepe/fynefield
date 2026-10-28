<?php
use App\Libraries\Cms;

$items = array_values($s['items']);
$interval = ((int) $s['interval'] ?: 7) * 1000;
$key = substr(preg_replace('/\W/', '', $s['id']), -6);
?>
<section class="ff-what<?= $cls ?>"<?= $idAttr ?> data-service-tabs data-interval="<?= $interval ?>" data-autoplay="<?= $s['autoplay'] ? '1' : '0' ?>">
    <div class="ff-section-head is-tight">
        <div><?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker"><?= esc($s['eyebrow']) ?></p><?php endif ?><h2 class="ff-h2"><?= esc($s['heading']) ?></h2></div>
        <?php if (trim((string) $s['linkLabel']) !== ''): ?><a class="ff-text-link" href="<?= esc(Cms::href($s['linkTo'], $s['linkToUrl'] ?? '')) ?>"><?= esc($s['linkLabel']) ?> →</a><?php endif ?>
    </div>
    <div class="ff-tabset" role="tablist" aria-label="<?= esc($s['heading'], 'attr') ?>" style="--ff-tabs:<?= max(1, count($items)) ?>">
        <?php foreach ($items as $index => $tab): ?>
        <button type="button" class="<?= $index === 0 ? 'is-active' : '' ?>" role="tab" id="tab-<?= $key ?>-<?= $index ?>" aria-controls="panel-<?= $key ?>-<?= $index ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>" data-service-tab><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span><?= esc($tab['title'] ?? '') ?></span><span class="ff-tab-track"><i data-service-tab-fill></i></span></button>
        <?php endforeach ?>
    </div>
    <div class="ff-tab-panel">
        <div class="ff-tab-images">
            <?php foreach ($items as $index => $tab): ?><?php if (! empty($tab['image'])): ?><img class="<?= $index === 0 ? 'is-active' : '' ?>" src="<?= esc($tab['image']) ?>" alt="<?= esc($tab['title'] ?? '') ?>" data-service-tab-image><?php else: ?><span class="ff-image-empty<?= $index === 0 ? ' is-active' : '' ?>" data-service-tab-image></span><?php endif ?><?php endforeach ?>
        </div>
        <?php foreach ($items as $index => $tab): ?>
        <div class="ff-tab-copy" id="panel-<?= $key ?>-<?= $index ?>" role="tabpanel" aria-labelledby="tab-<?= $key ?>-<?= $index ?>" data-service-tab-panel<?= $index ? ' hidden' : '' ?>>
            <h3><?= esc($tab['title'] ?? '') ?></h3>
            <p><?= esc($tab['body'] ?? '') ?></p>
            <?php $facts = Cms::factLines($tab['facts'] ?? ''); if ($facts): ?><div class="ff-facts"><?php foreach ($facts as [$value, $label]): ?><div><strong><?= esc($value) ?></strong><span><?= esc($label) ?></span></div><?php endforeach ?></div><?php endif ?>
            <a class="ff-pill ff-pill-dark" href="<?= esc(Cms::href($tab['link'] ?? '', $tab['linkUrl'] ?? '')) ?>">Learn more</a>
        </div>
        <?php endforeach ?>
    </div>
</section>
