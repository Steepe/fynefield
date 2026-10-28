<section class="ff-newsletter<?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-newsletter-inner">
        <div><h2><?= esc($s['heading']) ?></h2><?php if (trim((string) $s['body']) !== ''): ?><p><?= esc($s['body']) ?></p><?php endif ?></div>
        <form method="post" action="<?= site_url('newsletter') ?>" data-newsletter>
            <?= csrf_field() ?>
            <input type="hidden" name="list" value="<?= esc($s['list'], 'attr') ?>">
            <input type="email" name="email" required placeholder="Your work email" aria-label="Your work email">
            <button class="ff-pill ff-pill-dark" type="submit"><?= esc($s['button']) ?></button>
        </form>
    </div>
</section>
<div class="ff-surface-spacer"></div>
