<?php
$phone = $contact['phone'] ?? '';
$email = $contact['email'] ?? '';
$address = $contact['address'] ?? '';
$directions = ($contact['mapsLink'] ?? '') ?: 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address);
$inAside ??= false;
?>
<?php if (! $inAside): ?><section class="ff-contact-band<?= $cls ?>"<?= $idAttr ?>><?php endif ?>
<?php if ($s['showPhone'] || $s['showEmail']): ?>
<div class="ff-contact-lines">
    <?php if ($s['showPhone']): ?><a href="<?= esc(\App\Libraries\Cms::href('tel')) ?>"><span>Call</span><strong><?= esc($phone) ?></strong></a><?php endif ?>
    <?php if ($s['showEmail']): ?><a href="mailto:<?= esc($email) ?>"><span>Email</span><strong><?= esc($email) ?></strong></a><?php endif ?>
</div>
<?php endif ?>
<?php if ($s['showOffice']): ?>
<div class="ff-office">
    <span><?= esc($s['officeLabel'] ?: 'Office') ?></span>
    <p><?= esc($address) ?></p>
    <?php if (trim((string) $s['directions']) !== ''): ?><a class="ff-accent-link" href="<?= esc($directions) ?>" target="_blank" rel="noopener"><?= esc($s['directions']) ?> ↗</a><?php endif ?>
</div>
<?php endif ?>
<?php if (! $inAside): ?></section><?php endif ?>
