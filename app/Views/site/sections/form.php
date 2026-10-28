<?php
use App\Libraries\Cms;

$formId = $slug . ':' . $s['id'];
$mine = session('inquiry_form') === $formId;
$sent = $mine && session('success');
$errors = $mine ? (session('errors') ?: []) : [];
$error = $mine ? session('error') : null;
$topics = array_values(array_filter($forms['topics'], static fn (array $topic): bool => trim((string) ($topic['label'] ?? '')) !== ''));
$first = $topics[0] ?? ['label' => '', 'hint' => '', 'message' => 'Message'];
$full = $s['fields'] !== 'Name, email, message';
$reference = (string) session('inquiry_reference');
$phone = $contact['phone'] ?? '';
$email = $contact['email'] ?? '';
$hasAside = (bool) $aside;
$old = static fn (string $field) => $mine ? old($field) : '';
?>
<?php ob_start() ?>
<?php if ($sent): ?>
<div class="ff-sent" role="status">
    <div class="ff-sent-label">✓ Received<?= $reference !== '' ? ' · ' . ($hasAside ? 'Reference ' : '') . esc($reference) : '' ?></div>
    <?php if ($hasAside): ?>
    <h2 class="ff-sent-title"><?= esc($s['success']) ?></h2>
    <p>Our team will respond within <?= esc(strtolower((string) $forms['responseTime'])) ?>.</p>
    <?php else: ?>
    <p class="ff-sent-title"><?= esc($s['success']) ?></p>
    <?php endif ?>
    <a class="ff-sent-again" href="<?= esc(Cms::href('page:' . $slug)) ?><?= $s['anchor'] ? '#' . esc($s['anchor'], 'attr') : '' ?>"><?= $hasAside ? 'Send another request →' : 'Send another →' ?></a>
</div>
<?php else: ?>
<form class="ff-form" method="post" action="<?= site_url('contact-us') ?>" data-contact-form>
    <?= csrf_field() ?>
    <input type="hidden" name="form_id" value="<?= esc($formId, 'attr') ?>">
    <input type="hidden" name="topic" value="<?= esc($first['label'], 'attr') ?>" data-contact-topic-value>
    <?php if (! $full): ?><input type="hidden" name="subject" value="<?= esc($first['label'] . ' inquiry', 'attr') ?>" data-contact-subject-value><?php endif ?>
    <?php if ($error || $errors): ?><div class="ff-form-errors" role="alert"><?= $error ? esc($error) : implode('<br>', array_map('esc', $errors)) ?></div><?php endif ?>
    <?php if ($hasAside && trim((string) $s['heading']) !== ''): ?><h2 class="ff-form-title"><?= esc($s['heading']) ?></h2><?php endif ?>
    <?php if ($topics): ?>
    <div>
        <?php if ($full): ?><div class="ff-form-heading">I'm interested in</div><?php endif ?>
        <div class="ff-chips" role="group" aria-label="I'm interested in">
            <?php foreach ($topics as $i => $topic): ?>
            <button type="button" class="ff-chip<?= $i === 0 ? ' is-selected' : '' ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" data-contact-topic data-hint="<?= esc($topic['hint'] ?? '', 'attr') ?>" data-message="<?= esc(($topic['message'] ?? '') ?: 'Message', 'attr') ?>"><?= esc($topic['label']) ?></button>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
    <?php if ($full): ?>
    <div class="ff-field-row">
        <label class="ff-label">Full name<input class="ff-input" name="name" value="<?= $old('name') ?>" autocomplete="name" required></label>
        <label class="ff-label">Company<input class="ff-input" name="company" value="<?= $old('company') ?>" autocomplete="organization"></label>
        <label class="ff-label">Email<input class="ff-input" type="email" name="email" value="<?= $old('email') ?>" autocomplete="email" required></label>
        <label class="ff-label">Phone<input class="ff-input" name="phone" value="<?= $old('phone') ?>" autocomplete="tel"></label>
    </div>
    <label class="ff-label">Subject<input class="ff-input" name="subject" value="<?= $old('subject') ?>" placeholder="<?= esc($first['hint'] ?? '', 'attr') ?>" required data-contact-subject></label>
    <label class="ff-label"><span data-contact-message-label><?= esc(($first['message'] ?? '') ?: 'Message') ?></span><textarea class="ff-input" name="message" rows="4" required><?= $old('message') ?></textarea></label>
    <?php else: ?>
    <div class="ff-field-row is-compact">
        <input class="ff-input" name="name" value="<?= $old('name') ?>" placeholder="Full name" aria-label="Full name" autocomplete="name" required>
        <input class="ff-input" type="email" name="email" value="<?= $old('email') ?>" placeholder="Email address" aria-label="Email address" autocomplete="email" required>
    </div>
    <textarea class="ff-input" name="message" rows="3" placeholder="<?= esc(($first['message'] ?? '') ?: 'Message', 'attr') ?>" aria-label="Message" required data-contact-message><?= $old('message') ?></textarea>
    <?php endif ?>
    <?php if ($s['consent']): ?><label class="ff-consent"><input type="checkbox" name="privacy_consent" value="1" required><?= esc($forms['consent']) ?></label><?php endif ?>
    <?php if ($forms['spam']): ?><input class="honeypot-field" name="company_website" tabindex="-1" autocomplete="off" aria-hidden="true"><?php endif ?>
    <button class="ff-pill ff-pill-dark" type="submit"><?= esc($s['button']) ?></button>
</form>
<?php endif ?>
<?php $formHtml = ob_get_clean() ?>

<?php if ($hasAside): ?>
<section class="ff-contact<?= $afterHero ? '' : ' is-flat' ?><?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-contact-card"><?= $formHtml ?></div>
    <div class="ff-contact-aside">
        <?php foreach ($aside as $part): ?>
        <?= view('site/sections/' . $part['type'], ['s' => $part, 'contact' => $contact, 'inAside' => true, 'cls' => '', 'idAttr' => '']) ?>
        <?php endforeach ?>
    </div>
</section>
<?php else: ?>
<section class="ff-band ff-home-inquiry<?= $cls ?>"<?= $idAttr ?>>
    <div class="ff-band-card">
        <div class="ff-band-amber">
            <h2><?= esc($s['heading']) ?></h2>
            <?php if (trim((string) $s['body']) !== ''): ?><p><?= esc($s['body']) ?></p><?php endif ?>
            <div class="ff-band-lines"><a href="<?= esc(Cms::href('tel')) ?>"><?= esc($phone) ?></a><a href="mailto:<?= esc($email) ?>"><?= esc($email) ?></a></div>
        </div>
        <div class="ff-band-white"><?= $formHtml ?></div>
    </div>
</section>
<?php endif ?>
