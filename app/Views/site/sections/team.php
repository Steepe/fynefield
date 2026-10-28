<?php
use App\Libraries\Markdown;

$team = $data['team'];
$profiles = (bool) $s['profiles'];
?>
<section class="ff-leaders<?= $cls ?>"<?= $idAttr ?><?= $profiles ? ' data-profiles' : '' ?>>
    <?php if (trim((string) $s['heading']) !== ''): ?><div class="ff-section-head"><div><h2 class="ff-h2 is-small"><?= esc($s['heading']) ?></h2></div></div><?php endif ?>
    <div class="ff-leader-grid" style="--ff-cols:<?= (int) $s['columns'] ?: 3 ?>">
        <?php foreach ($team as $index => $member): ?>
        <article class="ff-leader">
            <?php if ($profiles): ?><button class="ff-leader-image" type="button" data-profile-open="<?= $index ?>" aria-label="View profile of <?= esc($member['name'], 'attr') ?>"><img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>"></button><?php else: ?><div class="ff-leader-image"><img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>"></div><?php endif ?>
            <div class="ff-leader-body">
                <span><?= esc($member['position']) ?></span>
                <h2><?= esc($member['name']) ?></h2>
                <?php if ($s['showBio'] && $member['bio']): ?><p><?= esc($member['bio']) ?></p><?php endif ?>
                <?php if ($profiles): ?><button class="ff-leader-more" type="button" data-profile-open="<?= $index ?>">View profile →</button><?php endif ?>
            </div>
        </article>
        <?php endforeach ?>
    </div>
    <?php if ($profiles): ?>
    <div class="ff-drawer-backdrop" data-profile-close hidden></div>
    <?php foreach ($team as $index => $member): ?>
    <?php $facts = json_decode((string) $member['facts'], true) ?: []; ?>
    <aside class="ff-drawer" role="dialog" aria-modal="true" aria-label="<?= esc($member['name'], 'attr') ?>" data-profile="<?= $index ?>" tabindex="-1" hidden>
        <div class="ff-drawer-bar">
            <span>Profile <?= $index + 1 ?> of <?= count($team) ?></span>
            <div>
                <button type="button" data-profile-step="-1" aria-label="Previous profile">←</button>
                <button type="button" data-profile-step="1" aria-label="Next profile">→</button>
                <button type="button" class="ff-drawer-close" data-profile-close>Close</button>
            </div>
        </div>
        <div class="ff-drawer-intro">
            <img src="<?= esc($member['image']) ?>" alt="<?= esc($member['name']) ?>">
            <div><p><?= esc($member['position']) ?></p><h2><?= esc($member['name']) ?></h2></div>
        </div>
        <div class="ff-drawer-body">
            <?php if ($member['bio']): ?><p class="ff-drawer-lede"><?= esc($member['bio']) ?></p><?php endif ?>
            <?php if (trim((string) $member['full_bio']) !== ''): ?><div class="ff-drawer-paras"><?= Markdown::render($member['full_bio']) ?></div><?php endif ?>
            <?php if ($facts): ?><div class="ff-drawer-facts"><?php foreach ($facts as $fact): ?><div><span><?= esc($fact['l'] ?? '') ?></span><strong><?= esc($fact['v'] ?? '') ?></strong></div><?php endforeach ?></div><?php endif ?>
            <?php if ($member['linkedin']): ?><a class="ff-accent-link" href="<?= esc($member['linkedin']) ?>" target="_blank" rel="noopener">LinkedIn profile ↗</a><?php endif ?>
        </div>
    </aside>
    <?php endforeach ?>
    <?php endif ?>
</section>
