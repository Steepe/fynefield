<section class="ff-content<?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-prose<?= $s['width'] === 'Wide' ? ' is-wide' : '' ?>">
        <?php if (trim((string) $s['heading']) !== ''): ?><h2><?= esc($s['heading']) ?></h2><?php endif ?>
        <?= \App\Libraries\Markdown::render($s['body']) ?>
    </div>
</section>
