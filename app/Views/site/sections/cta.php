<?php
use App\Libraries\Cms;

$phone = $contact['phone'] ?? '';
$email = $contact['email'] ?? '';
?>
<section class="ff-band<?= $s['style'] === 'Navy band' ? ' is-navy' : '' ?><?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-band-card">
        <div class="ff-band-amber">
            <h2><?= esc($s['heading']) ?></h2>
            <?php if (trim((string) $s['body']) !== ''): ?><p><?= esc($s['body']) ?></p><?php endif ?>
        </div>
        <div class="ff-band-white is-cta">
            <a class="ff-pill ff-pill-dark is-md" href="<?= esc(Cms::href($s['link'], $s['linkUrl'] ?? '')) ?>"><?= esc($s['button']) ?></a>
            <?php if ($s['showContact']): ?><div class="ff-band-lines"><a href="<?= esc(Cms::href('tel')) ?>"><?= esc($phone) ?></a><a href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a></div><?php endif ?>
        </div>
    </div>
</section>
