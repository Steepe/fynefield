<?php
$floating = $s['style'] === 'Floating card';
$modifier = ! $floating ? ' is-plain' : ($afterHero ? ($prevTall ? '' : ' is-overlap') : ' is-flat');
?>
<section class="ff-stats<?= $modifier ?><?= $cls ?>"<?= $idAttr ?><?= $s['animate'] ? ' data-stats' : '' ?>>
    <div class="ff-stats-card">
        <?php foreach ($s['items'] as $item): ?>
        <?php $value = (string) ($item['v'] ?? ''); $count = $s['animate'] && preg_match('/^(\d+)(\D+)$/', $value, $m); ?>
        <div><strong<?= $count ? ' data-count-to="' . $m[1] . '" data-count-suffix="' . esc($m[2], 'attr') . '"' : '' ?>><?= esc($value) ?></strong><span><?= esc($item['l'] ?? '') ?></span></div>
        <?php endforeach ?>
    </div>
</section>
