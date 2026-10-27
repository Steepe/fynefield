<?php foreach ($sections as $section): ?>
<?php $type = $section['section_type']; $hasImage = ! empty($section['image']); ?>
<?php if ($type === 'hero'): ?>
<section class="builder-hero"<?= $hasImage ? ' style="background-image:linear-gradient(rgba(5,21,36,.78),rgba(5,21,36,.78)),url(\'' . esc($section['image'], 'attr') . '\')"' : '' ?>><div class="container"><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2><div class="builder-copy"><?= $section['content'] ?></div><?php if ($section['button_label'] && $section['button_url']): ?><a class="button" href="<?= esc($section['button_url']) ?>"><?= esc($section['button_label']) ?></a><?php endif ?></div></section>
<?php elseif ($type === 'image_text'): ?>
<section class="container builder-split"><div class="builder-copy"><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2><?= $section['content'] ?><?php if ($section['button_label'] && $section['button_url']): ?><a class="button" href="<?= esc($section['button_url']) ?>"><?= esc($section['button_label']) ?></a><?php endif ?></div><?php if ($hasImage): ?><img src="<?= esc($section['image']) ?>" alt="<?= esc($section['heading']) ?>"><?php endif ?></section>
<?php elseif ($type === 'call_to_action'): ?>
<section class="builder-cta"><div class="container"><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2><div class="builder-copy"><?= $section['content'] ?></div><?php if ($section['button_label'] && $section['button_url']): ?><a class="button" href="<?= esc($section['button_url']) ?>"><?= esc($section['button_label']) ?></a><?php endif ?></div></section>
<?php elseif ($type === 'stat_grid'): ?>
<section class="container builder-stats"><div><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2></div><div class="stat-grid"><?php foreach ($section['item_rows'] as $item): ?><article><strong><?= esc($item['value']) ?></strong><span><?= esc($item['label']) ?></span></article><?php endforeach ?></div></section>
<?php elseif ($type === 'gallery'): ?>
<section class="container builder-gallery"><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2><?php if ($section['content']): ?><div class="builder-copy"><?= $section['content'] ?></div><?php endif ?><div><?php foreach ($section['item_rows'] as $item): ?><?php if (preg_match('#^(?:/uploads/|/assets/media/|https://)#i', $item['label'])): ?><img src="<?= esc($item['label']) ?>" alt="<?= esc($item['value']) ?>"><?php endif ?><?php endforeach ?></div></section>
<?php elseif ($type === 'faq'): ?>
<section class="container builder-faq"><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2><?php foreach ($section['item_rows'] as $item): ?><details><summary><?= esc($item['label']) ?></summary><p><?= esc($item['value']) ?></p></details><?php endforeach ?></section>
<?php else: ?>
<section class="container builder-rich"><p class="eyebrow"><?= esc($section['eyebrow']) ?></p><h2><?= esc($section['heading']) ?></h2><div class="builder-copy prose"><?= $section['content'] ?></div></section>
<?php endif ?>
<?php endforeach ?>
