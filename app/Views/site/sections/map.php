<?php
$sites = array_map(static fn (array $location): array => [
    'k' => $location['state'], 'city' => $location['city'], 'c' => [(float) $location['longitude'], (float) $location['latitude']],
    'type' => $location['pin_type'], 'route' => (bool) $location['show_route'],
], array_values(array_filter($data['locations'], static fn (array $l): bool => $l['latitude'] !== null && $l['longitude'] !== null)));
?>
<section class="ff-network<?= $s['mapSide'] === 'Right' ? ' is-map-right' : '' ?><?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-network-inner">
        <div class="ff-network-map">
            <script type="application/json" data-map-sites><?= json_encode($sites, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
            <iframe src="<?= base_url('assets/network-map.html') ?>" title="Network map" loading="lazy"></iframe>
        </div>
        <div>
            <?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker is-light"><?= esc($s['eyebrow']) ?></p><?php endif ?>
            <h2 class="ff-h2"><?= esc($s['heading']) ?></h2>
            <?php if (trim((string) $s['body']) !== ''): ?><p class="ff-network-lede"><?= esc($s['body']) ?></p><?php endif ?>
            <?php if ($s['showChips']): ?><div class="ff-states"><?php foreach ($data['locations'] as $location): ?><span tabindex="0" data-network-state="<?= esc($location['state'], 'attr') ?>"><?= esc($location['state']) ?></span><?php endforeach ?></div><?php endif ?>
        </div>
    </div>
</section>
