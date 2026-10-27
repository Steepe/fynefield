<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="page-hero plain"><div class="container"><p class="eyebrow">404 · Page not found</p><h1>That page is not available.</h1><p>It may have moved, been replaced, or no longer be published.</p><p class="actions"><a class="button" href="<?= site_url('/') ?>">Go to homepage</a><a class="button ghost dark-ghost" href="<?= site_url('contact-us') ?>">Contact us</a></p></div></section>
<?= $this->endSection() ?>
