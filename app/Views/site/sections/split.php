<?php
use App\Libraries\Cms;
use App\Libraries\Markdown;
?>
<section class="ff-split<?= $s['imageSide'] === 'Left' ? ' is-image-left' : '' ?><?= $cls ?>"<?= $idAttr ?>>
    <div>
        <?php if (trim((string) $s['eyebrow']) !== ''): ?><p class="ff-kicker"><?= esc($s['eyebrow']) ?></p><?php endif ?>
        <div class="ff-about-copy">
            <?php if (trim((string) $s['heading']) !== ''): ?><h2><?= esc($s['heading']) ?></h2><?php endif ?>
            <?= Markdown::render($s['body']) ?>
        </div>
        <?php if (trim((string) $s['buttonLabel']) !== ''): ?><a class="ff-pill ff-pill-dark ff-split-button" href="<?= esc(Cms::href($s['buttonLink'], $s['buttonLinkUrl'] ?? '')) ?>"><?= esc($s['buttonLabel']) ?></a><?php endif ?>
    </div>
    <figure><?php if ($s['image']): ?><img src="<?= esc($s['image']) ?>" alt="<?= esc($s['heading']) ?>"><?php else: ?><span class="ff-image-empty is-active"></span><?php endif ?></figure>
</section>
