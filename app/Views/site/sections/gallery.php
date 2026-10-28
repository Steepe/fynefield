<?php $images = array_values(array_filter([$s['image1'], $s['image2'], $s['image3']])); ?>
<?php if ($images): ?>
<section class="ff-gallery-section<?= $cls ?>"<?= $idAttr ?>>
    <?php if (trim((string) $s['heading']) !== ''): ?><h2 class="ff-h2 is-small"><?= esc($s['heading']) ?></h2><?php endif ?>
    <div class="ff-gallery is-bare">
        <?php foreach ($images as $src): ?>
        <button class="gallery-trigger" type="button" data-lightbox-src="<?= esc($src, 'attr') ?>" data-lightbox-alt="<?= esc($s['heading'] ?: 'Gallery image', 'attr') ?>"><img src="<?= esc($src) ?>" alt=""></button>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>
