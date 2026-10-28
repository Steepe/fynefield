<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="ff-page-hero">
    <div class="ff-page-hero-inner">
        <div class="ff-breadcrumb"><a href="<?= site_url('/') ?>">Home</a><span>/</span><span><?= esc($pageTitle) ?></span></div>
        <h1>This page is password protected.</h1>
        <p>Enter the password you were given to continue.</p>
        <form class="ff-password-form" method="post" action="<?= site_url('unlock/' . $slug) ?>">
            <?= csrf_field() ?>
            <input class="ff-input" type="password" name="password" required aria-label="Page password" autocomplete="current-password">
            <button class="ff-pill ff-pill-amber" type="submit">Continue</button>
        </form>
        <?php if (session('error')): ?><p class="ff-password-error" role="alert"><?= esc(session('error')) ?></p><?php endif ?>
    </div>
</section>
<?= $this->endSection() ?>
