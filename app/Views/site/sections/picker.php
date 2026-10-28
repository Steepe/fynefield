<?php
$services = array_column($data['services'], null, 'slug');
$answers = array_values(array_filter($s['items'], static fn (array $item): bool => isset($services[$item['service'] ?? ''])));
$first = $answers ? $services[$answers[0]['service']] : null;
?>
<?php if ($first): ?>
<section class="ff-overlap<?= $afterHero ? '' : ' is-flat' ?><?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-card ff-finder" data-service-finder>
        <div class="ff-finder-choices">
            <?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker"><?= esc($s['eyebrow']) ?></p><?php endif ?>
            <h2><?= esc($s['heading']) ?></h2>
            <div class="ff-finder-list">
                <?php foreach ($answers as $index => $answer): ?>
                <?php $service = $services[$answer['service']]; ?>
                <button type="button" class="<?= $index === 0 ? 'is-selected' : '' ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>" data-service-choice data-title="<?= esc($service['title'], 'attr') ?>" data-summary="<?= esc($service['summary'], 'attr') ?>" data-short="<?= esc(strtolower($service['title']), 'attr') ?>" data-href="<?= esc(site_url('services/' . $service['slug']), 'attr') ?>"><span><?= esc($answer['label'] ?? '') ?></span><span data-choice-mark><?= $index === 0 ? '●' : '→' ?></span></button>
                <?php endforeach ?>
            </div>
        </div>
        <div class="ff-finder-result" aria-live="polite">
            <div>
                <small>Recommended service</small>
                <strong data-service-result-title><?= esc($first['title']) ?></strong>
                <p data-service-result-copy><?= esc($first['summary']) ?></p>
            </div>
            <div class="ff-actions">
                <a class="ff-pill ff-pill-dark is-sm" href="<?= site_url('services/' . $first['slug']) ?>" data-service-result-link>Explore&nbsp;<span data-service-result-short><?= esc(strtolower($first['title'])) ?></span></a>
                <a class="ff-pill ff-pill-line is-sm" href="<?= esc(\App\Libraries\Cms::href('page:contact-us')) ?>">Ask our team</a>
            </div>
        </div>
    </div>
</section>
<?php endif ?>
