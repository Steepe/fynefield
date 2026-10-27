<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="ff-page-hero">
    <div class="ff-page-hero-inner is-overlapped">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span>404 · Page not found</span></div>
        <h1>That page is not available.</h1>
        <p>It may have moved, been replaced, or no longer be published.</p>
        <div class="ff-actions is-spaced">
            <a class="ff-pill ff-pill-amber is-lg" href="<?= site_url('/') ?>">Go to homepage</a>
            <a class="ff-pill ff-pill-ghost is-lg" href="<?= site_url('contact-us') ?>">Contact us</a>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
