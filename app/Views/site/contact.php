<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
$topics = [
    ['Bulk supply', 'e.g. Weekly PMS supply to Warri', 'Product, volume and delivery location'],
    ['Storage', 'e.g. AGO storage for 60 days', 'Product and storage duration'],
    ['Haulage', 'e.g. Haulage Calabar → Abuja', 'Pickup, destination and volume'],
    ['Retail partnership', 'e.g. Operating a site in Makurdi', 'Location and proposal'],
    ['Something else', 'How can we help?', 'Message'],
];
$faqs = [
    ['How can I make an enquiry?', 'Use this form to send a supply, logistics, or service request to our team. You can also call or email us directly.'],
    ['Where does Fynefield operate?', 'Our operations and retail network serve key locations across Nigeria, including Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT.'],
];
$phone = $settings['contact_phone'] ?? '';
$email = $settings['contact_email'] ?? '';
$address = $settings['address'] ?? '';
$errors = session('errors') ?: [];
?>
<section class="ff-page-hero">
    <div class="ff-page-hero-inner is-overlapped">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span>Contact</span></div>
        <h1>Contact us</h1>
        <p>Tell us about your energy, supply or service needs. Every request is routed straight to the right team.</p>
    </div>
</section>

<section class="ff-contact">
    <div class="ff-contact-card">
        <?php if (session('success')): ?>
        <div class="ff-sent" role="status">
            <div class="ff-sent-label">✓ Received<?= session('inquiry_reference') ? ' · Reference ' . esc(session('inquiry_reference')) : '' ?></div>
            <h2 class="ff-sent-title">Thank you — we're on it.</h2>
            <p>Our team will respond within one business day.</p>
            <a class="ff-sent-again" href="<?= site_url('contact-us') ?>">Send another request →</a>
        </div>
        <?php else: ?>
        <form class="ff-form" method="post" action="<?= site_url('contact-us') ?>" data-contact-form>
            <?= csrf_field() ?>
            <?php if (session('error') || $errors): ?><div class="ff-form-errors" role="alert"><?= session('error') ? esc(session('error')) : implode('<br>', array_map('esc', $errors)) ?></div><?php endif ?>
            <div>
                <div class="ff-form-heading">I'm interested in</div>
                <div class="ff-chips" role="group" aria-label="I'm interested in">
                    <?php foreach ($topics as $i => [$topic, $hint, $messageLabel]): ?>
                    <button type="button" class="ff-chip<?= $i === 0 ? ' is-selected' : '' ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" data-contact-topic data-hint="<?= esc($hint, 'attr') ?>" data-message="<?= esc($messageLabel, 'attr') ?>"><?= esc($topic) ?></button>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="ff-field-row">
                <label class="ff-label">Full name<input class="ff-input" name="name" value="<?= old('name') ?>" autocomplete="name" required></label>
                <label class="ff-label">Company<input class="ff-input" name="company" value="<?= old('company') ?>" autocomplete="organization"></label>
                <label class="ff-label">Email<input class="ff-input" type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required></label>
                <label class="ff-label">Phone<input class="ff-input" name="phone" value="<?= old('phone') ?>" autocomplete="tel"></label>
            </div>
            <label class="ff-label">Subject<input class="ff-input" name="subject" value="<?= old('subject') ?>" placeholder="<?= esc($topics[0][1], 'attr') ?>" required data-contact-subject></label>
            <label class="ff-label"><span data-contact-message-label><?= esc($topics[0][2]) ?></span><textarea class="ff-input" name="message" rows="4" required><?= old('message') ?></textarea></label>
            <label class="ff-consent"><input type="checkbox" name="privacy_consent" value="1" required>I consent to Fynefield using these details to respond to this inquiry. Please do not send payment or card information.</label>
            <input class="honeypot-field" name="company_website" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button class="ff-pill ff-pill-dark" type="submit">Send inquiry</button>
        </form>
        <?php endif ?>
    </div>

    <div class="ff-contact-aside">
        <div class="ff-contact-lines">
            <a href="tel:<?= esc(preg_replace('/\s+/', '', $phone)) ?>"><span>Call</span><strong><?= esc($phone) ?></strong></a>
            <a href="mailto:<?= esc($email) ?>"><span>Email</span><strong><?= esc($email) ?></strong></a>
        </div>
        <div class="ff-office">
            <span>Abuja office</span>
            <p><?= esc($address) ?></p>
            <a class="ff-accent-link" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode($address) ?>" target="_blank" rel="noopener">Get directions ↗</a>
        </div>
        <div class="ff-faq" data-faq>
            <h2>Common questions</h2>
            <?php foreach ($faqs as $i => [$question, $answer]): ?>
            <div>
                <button type="button" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq-<?= $i ?>" data-faq-toggle><?= esc($question) ?><span><?= $i === 0 ? '−' : '+' ?></span></button>
                <p id="faq-<?= $i ?>"<?= $i === 0 ? '' : ' hidden' ?>><?= esc($answer) ?></p>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
