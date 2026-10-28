<?php
$inAside ??= false;
$key = substr(preg_replace('/\W/', '', $s['id']), -6);
?>
<?php if (! $inAside): ?><section class="ff-faq-band<?= $cls ?>"<?= $idAttr ?>><?php endif ?>
<div class="ff-faq" data-faq>
    <?php if (trim((string) $s['heading']) !== ''): ?><h2><?= esc($s['heading']) ?></h2><?php endif ?>
    <?php foreach ($s['items'] as $i => $item): ?>
    <?php $open = $i === 0 && $s['openFirst']; ?>
    <div>
        <button type="button" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="faq-<?= $key ?>-<?= $i ?>" data-faq-toggle><?= esc($item['q'] ?? '') ?><span><?= $open ? '−' : '+' ?></span></button>
        <p id="faq-<?= $key ?>-<?= $i ?>"<?= $open ? '' : ' hidden' ?>><?= esc($item['a'] ?? '') ?></p>
    </div>
    <?php endforeach ?>
</div>
<?php if (! $inAside): ?></section><?php endif ?>
