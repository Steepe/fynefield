<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<section class="page-hero" style="background-image:linear-gradient(rgba(5,21,36,.78),rgba(5,21,36,.78)),url('<?= esc($service['image']) ?>')"><div class="container"><p class="eyebrow">Our services</p><h1><?= esc($service['title']) ?></h1><p><?= esc($service['summary']) ?></p></div></section><main class="container prose content-page"><?= $service['content'] ?><p><a class="button" href="<?= site_url('contact-us') ?>">Make an inquiry</a></p></main>
<?= $this->endSection() ?>
