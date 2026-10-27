<?= $this->extend('layouts/admin') ?>
<?= $this->section('head') ?>
<?php if (in_array('richtext', $meta['fields'], true)): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.12/dist/trix.css">
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="list-head"><div><p>Content manager</p><h1><?= empty($row) ? 'New ' : 'Edit ' ?><?= esc(rtrim($meta['label'], 's')) ?></h1></div><div class="list-actions"><a href="<?= site_url('admin/' . $entity) ?>">← Back to <?= strtolower($meta['label']) ?></a><?php if (! empty($row)): ?><a class="history-link" href="<?= site_url('admin/' . $entity . '/' . $row['id'] . '/revisions') ?>">Version history</a><?php endif ?><?php if ($entity === 'pages' && ! empty($row)): ?><a class="history-link" href="<?= site_url('admin/pages/' . $row['id'] . '/sections') ?>">Manage sections</a><?php endif ?><?php if (in_array($entity, ['pages', 'posts'], true) && ! empty($row)): ?><?php $previewPath = $entity === 'pages' ? 'preview/' . $row['slug'] : 'preview/news/' . $row['slug']; ?><?php if (! empty($row['preview_token']) && ! empty($row['preview_token_expires']) && strtotime($row['preview_token_expires']) >= time()): ?><a class="history-link" target="_blank" rel="noopener" href="<?= site_url($previewPath) . '?token=' . rawurlencode($row['preview_token']) ?>">Private preview ↗</a><?php endif ?><form class="inline-form" method="post" action="<?= site_url('admin/' . $entity . '/' . $row['id'] . '/preview-link') ?>"><?= csrf_field() ?><button type="submit">Refresh preview</button></form><?php endif ?></div></section>
<form class="editor-form" method="post" action="<?= empty($row) ? site_url('admin/' . $entity) : site_url('admin/' . $entity . '/' . $row['id']) ?>">
<?= csrf_field() ?>
<?php foreach ($meta['fields'] as $name => $type): ?>
<label class="<?= in_array($type, ['textarea', 'richtext']) ? 'wide' : '' ?> <?= in_array($name, ['seo_title', 'meta_description', 'canonical_url', 'noindex']) ? 'seo-field' : '' ?>"><?= esc(ucwords(str_replace('_', ' ', $name))) ?>
<?php if ($type === 'richtext'): ?>
    <span class="field-help">Use the editor for formatted copy. Insert only approved images from the media library.</span>
    <input id="<?= esc($name) ?>" type="hidden" name="<?= esc($name) ?>" value="<?= esc(old($name, $row[$name] ?? '')) ?>">
    <trix-editor input="<?= esc($name) ?>" class="rich-text-editor"></trix-editor>
    <button class="media-picker-button" type="button" data-media-picker data-editor-input="<?= esc($name) ?>">Insert image from library</button>
<?php elseif ($type === 'readonly'): ?>
    <output><?= esc($row[$name] ?? '—') ?></output>
<?php elseif ($type === 'textarea'): ?>
    <textarea name="<?= esc($name) ?>" rows="4" <?= $name === 'meta_description' ? 'maxlength="255"' : '' ?>><?= esc(old($name, $row[$name] ?? '')) ?></textarea>
<?php elseif ($type === 'status' || $type === 'inquiry_status'): ?>
    <?php $statusOptions = $type === 'inquiry_status' ? ['new', 'in_progress', 'resolved'] : (($entity === 'pages' || $entity === 'posts') ? ($adminRole === 'admin' ? ['draft', 'in_review', 'scheduled', 'published'] : ['draft', 'in_review']) : ['draft', 'published']); ?>
    <?php if ($type === 'status'): ?><span class="field-help"><?= ($entity === 'pages' || $entity === 'posts') && $adminRole !== 'admin' ? 'Save as a draft or submit for administrator review.' : 'Drafts stay private. Send content for review, schedule it, or publish it immediately.' ?></span><?php endif ?>
    <select name="<?= esc($name) ?>"><?php foreach ($statusOptions as $option): ?><option value="<?= $option ?>" <?= old($name, $row[$name] ?? '') === $option ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $option)) ?></option><?php endforeach ?></select>
<?php elseif ($type === 'redirect_code'): ?>
    <span class="field-help">Use 301 for a permanent URL change; use 302 only for a temporary diversion.</span>
    <select name="<?= esc($name) ?>"><?php foreach ([301 => '301 — Permanent', 302 => '302 — Temporary'] as $option => $label): ?><option value="<?= $option ?>" <?= (int) old($name, $row[$name] ?? 301) === $option ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select>
<?php elseif ($type === 'template'): ?>
    <span class="field-help">Standard retains the conventional page intro; Landing makes the builder sections the main page experience.</span>
    <select name="<?= esc($name) ?>"><?php foreach (['standard' => 'Standard page', 'landing' => 'Landing page'] as $option => $label): ?><option value="<?= $option ?>" <?= old($name, $row[$name] ?? 'standard') === $option ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select>
<?php elseif ($type === 'checkbox'): ?>
    <span class="checkbox"><input type="checkbox" name="<?= esc($name) ?>" value="1" <?= old($name, $row[$name] ?? 0) ? 'checked' : '' ?>> Keep this page out of search engines</span>
<?php else: ?>
    <input type="<?= $type === 'datetime' ? 'datetime-local' : $type ?>" name="<?= esc($name) ?>" value="<?= esc($type === 'datetime' && ! empty($row[$name]) ? date('Y-m-d\\TH:i', strtotime($row[$name])) : old($name, $row[$name] ?? '')) ?>">
<?php endif ?></label>
<?php endforeach ?>
<div class="form-actions"><button class="primary" type="submit">Save <?= esc(rtrim($meta['label'], 's')) ?></button></div>
</form>

<?php if (in_array('richtext', $meta['fields'], true)): ?>
<dialog id="media-picker" class="media-picker-dialog" aria-labelledby="media-picker-title">
    <div class="media-picker-heading"><div><p>Media library</p><h2 id="media-picker-title">Insert an image</h2></div><button type="button" class="dialog-close" data-close-media-picker aria-label="Close media library">×</button></div>
    <p class="field-help">Choose an approved image. Descriptive alternative text is added from the filename and can be refined in the editor.</p>
    <div class="media-picker-grid">
        <?php foreach ($editorMedia as $item): ?>
        <button type="button" class="media-picker-item" data-insert-media data-url="<?= esc($item['path'], 'attr') ?>" data-alt="<?= esc($item['alt_text'] ?: pathinfo($item['filename'], PATHINFO_FILENAME), 'attr') ?>">
            <img src="<?= esc($item['path'], 'attr') ?>" alt=""><span><?= esc($item['filename']) ?></span>
        </button>
        <?php endforeach ?>
    </div>
    <?php if (! $editorMedia): ?><p>No images yet. <a href="<?= site_url('admin/media') ?>">Upload one in the media library</a>, then return here.</p><?php endif ?>
</dialog>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (in_array('richtext', $meta['fields'], true)): ?>
<script src="https://cdn.jsdelivr.net/npm/trix@2.1.12/dist/trix.umd.min.js"></script>
<script src="<?= base_url('assets/js/editor.js') ?>"></script>
<?php endif ?>
<?= $this->endSection() ?>
