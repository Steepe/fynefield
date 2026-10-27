<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<section class="list-head"><div><p>Version history</p><h1><?= esc($row['title'] ?? $row['name']) ?></h1></div><a href="<?= site_url('admin/' . $entity . '/' . $row['id'] . '/edit') ?>">← Back to editor</a></section>
<div class="revision-list"><?php foreach ($revisions as $revision): ?><article><div><strong>Saved <?= esc($revision['created_at']) ?></strong><small>Revision #<?= esc($revision['id']) ?></small></div><form method="post" action="<?= site_url('admin/' . $entity . '/' . $row['id'] . '/revisions/' . $revision['id'] . '/restore') ?>" onsubmit="return confirm('Restore this version? Your current work will be saved as a revision first.')"><?= csrf_field() ?><button class="primary">Restore</button></form></article><?php endforeach ?><?php if (! $revisions): ?><p>No earlier versions exist yet. A revision is saved whenever this item is edited.</p><?php endif ?></div>
<?= $this->endSection() ?>
