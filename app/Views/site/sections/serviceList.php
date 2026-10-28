<?php
$facts = static function (array $service): array {
    $list = json_decode((string) $service['facts'], true);
    return is_array($list) ? array_slice($list, 0, 2) : [];
};
$grid = $s['layout'] === 'Grid';
?>
<section class="<?= $grid ? 'ff-service-grid' : 'ff-service-rows' ?><?= $cls ?>"<?= $idAttr ?>>
    <?php foreach ($data['services'] as $index => $service): ?>
    <?php $image = $service['card_image'] ?: $service['image']; ?>
    <article class="ff-service-row<?= ! $grid && $index % 2 ? ' is-reversed' : '' ?>">
        <?php if ($image): ?><img src="<?= esc($image) ?>" alt="<?= esc($service['title']) ?>"><?php else: ?><span class="ff-image-empty is-active"></span><?php endif ?>
        <div>
            <p class="ff-kicker"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?><?= $service['kicker'] ? ' · ' . esc($service['kicker']) : '' ?></p>
            <h2 class="ff-h2 is-small"><?= esc($service['title']) ?></h2>
            <p><?= esc($service['summary']) ?></p>
            <?php if ($s['showFacts'] && $facts($service)): ?><div class="ff-facts is-wide"><?php foreach ($facts($service) as $fact): ?><div><strong><?= esc($fact['v'] ?? '') ?></strong><span><?= esc($fact['l'] ?? '') ?></span></div><?php endforeach ?></div><?php endif ?>
            <?php if (trim((string) $s['buttonLabel']) !== ''): ?><a class="ff-pill ff-pill-dark" href="<?= site_url('services/' . $service['slug']) ?>"><?= esc($s['buttonLabel']) ?></a><?php endif ?>
        </div>
    </article>
    <?php endforeach ?>
</section>
