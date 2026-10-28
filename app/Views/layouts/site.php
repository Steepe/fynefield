<?php
use App\Libraries\Cms;

$siteName = $general['siteName'];
$siteTitle = $title ?? $siteName;
$description = $metaDescription ?? $seo['defaultDesc'];
$canonical = current_url();
$noindex = ($noindex ?? false) || ! $seo['indexing'];
$socialImage = ($shareImage ?? '') ?: $seo['shareImage'];
$socialImage = $socialImage ? base_url(ltrim($socialImage, '/')) : '';
$headerSolid = ($headerSolid ?? false) || ! $nav['transparent'];
$breakpoint = (int) $nav['mobileBreakpoint'] ?: 960;
$motion = match ($theme['motion'] ?? 'Some') { 'None' => 'none', 'Subtle fades only' => 'subtle', default => 'some' };
$currentPath = '/' . trim(service('uri')->getPath(), '/');
$isActive = static function (string $href) use ($currentPath): bool {
    $path = '/' . trim((string) parse_url($href, PHP_URL_PATH), '/');
    if ($path === $currentPath) return true;
    return $path !== '/' && (str_starts_with($currentPath, $path . '/') || ($path === '/services' && str_starts_with($currentPath, '/services/')));
};
$sameAs = array_values(array_filter([$seo['linkedin'], $seo['x'], $seo['facebook'], $seo['instagram']], static fn ($url) => Cms::safeUrl((string) $url) !== ''));
$tracking = $integrations['analytics'] !== '' || $integrations['tagManager'] !== '';
$headerLinks = array_filter($nav['header'], static fn (array $item): bool => ($item['show'] ?? 'Visible') !== 'Hidden');
?>
<!doctype html>
<html lang="<?= esc(Cms::htmlLang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc($description) ?>">
    <link rel="canonical" href="<?= esc($canonical) ?>">
    <?php if ($noindex): ?><meta name="robots" content="noindex,follow"><?php endif ?>
    <meta property="og:type" content="website"><meta property="og:site_name" content="<?= esc($siteName) ?>"><meta property="og:title" content="<?= esc($siteTitle) ?>"><meta property="og:description" content="<?= esc($description) ?>"><meta property="og:url" content="<?= esc($canonical) ?>">
    <?php if ($socialImage): ?><meta property="og:image" content="<?= esc($socialImage) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif ?>
    <?php if ($general['favicon']): ?><link rel="icon" href="<?= esc($general['favicon']) ?>"><?php endif ?>
    <title><?= esc($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= esc(Cms::fontUrl($theme)) ?>">
    <script>document.documentElement.classList.add('ff-js')</script>
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/contact.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/fynefield-ui.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/fynefield-ui.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/accessibility.css') ?>">
    <style><?= Cms::themeCss($theme) ?>
@media (max-width:<?= $breakpoint - 1 ?>px){.ff-nav{display:none}.ff-menu-toggle{display:block}}
@media (min-width:<?= $breakpoint ?>px){.ff-menu{display:none!important}}</style>
    <?php if (trim((string) $advanced['customCss']) !== ''): ?><style><?= str_replace('</', '<\/', (string) $advanced['customCss']) ?></style><?php endif ?>
    <script type="application/ld+json"><?= json_encode(array_filter(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $general['legalName'] ?: $siteName, 'url' => site_url('/'), 'logo' => $general['logo'] ? base_url(ltrim($general['logo'], '/')) : null, 'email' => $contact['email'] ?: null, 'telephone' => $contact['phone'] ?: null, 'sameAs' => $sameAs ?: null]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
    <?php if ($tracking): ?><script type="application/json" id="ff-tracking"><?= json_encode(['ga' => preg_replace('/[^\w-]/', '', $integrations['analytics']), 'gtm' => preg_replace('/[^\w-]/', '', $integrations['tagManager']), 'consent' => (bool) $integrations['cookieBanner']], JSON_HEX_TAG) ?></script><?php endif ?>
</head>
<body class="fynefield-ui" data-motion="<?= $motion ?>" data-breakpoint="<?= $breakpoint ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="ff-wrap">
<header class="ff-header<?= $headerSolid ? ' is-always-solid is-solid' : '' ?><?= $nav['sticky'] ? '' : ' is-static' ?>" data-site-header>
    <div class="ff-header-bar">
        <a class="ff-brand" href="<?= site_url('/') ?>"><img src="<?= esc($general['logo']) ?>" alt="<?= esc($siteName) ?>"></a>
        <nav class="ff-nav" aria-label="Main navigation"><?php foreach ($headerLinks as $item): ?><?php $href = Cms::href($item['link'] ?? '', $item['linkUrl'] ?? ''); $active = $isActive($href); ?><a class="<?= $active ? 'is-active' : '' ?>" href="<?= esc($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= esc(Cms::linkText($item)) ?></a><?php endforeach ?></nav>
        <div class="ff-header-actions">
            <?php if (($nav['cta']['show'] ?? 'Visible') !== 'Hidden'): ?><a class="ff-cta" href="<?= esc(Cms::href($nav['cta']['link'] ?? '', $nav['cta']['linkUrl'] ?? '')) ?>"><?= esc($nav['cta']['label']) ?></a><?php endif ?>
            <button class="ff-menu-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-menu">Menu</button>
        </div>
    </div>
    <nav class="ff-menu" id="site-menu" aria-label="Mobile navigation" hidden><?php foreach ($headerLinks as $item): ?><a href="<?= esc(Cms::href($item['link'] ?? '', $item['linkUrl'] ?? '')) ?>"><?= esc(Cms::linkText($item)) ?></a><?php endforeach ?></nav>
</header>
<?php if (! ($inlineFlash ?? false)): ?>
<?php if (session('error') && ! session('inquiry_form')): ?><div class="ff-flash is-error" role="alert"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('success') && ! session('inquiry_form')): ?><div class="ff-flash is-success" role="status"><?= esc(session('success')) ?></div><?php endif ?>
<?php endif ?>
<main id="main-content" tabindex="-1"><?= $this->renderSection('content') ?></main>
<?php if (($footerCta ?? false) && $nav['showCta']): ?>
<?= view('site/sections/cta', ['s' => array_replace(Cms::sectionCommon(), Cms::sectionDefaults()['cta'], ['type' => 'cta']), 'cls' => '', 'idAttr' => '', 'contact' => $contact]) ?>
<?php endif ?>
<footer class="ff-footer">
    <?php if ($nav['showWordmark'] && trim((string) $nav['wordmark']) !== ''): ?><div class="ff-footer-wordmark" aria-hidden="true"><?= esc($nav['wordmark']) ?></div><?php endif ?>
    <div class="ff-footer-grid">
        <?php if ($nav['showAddress']): ?><div><strong>Head office</strong><span class="ff-footer-address"><?= esc(rtrim($contact['address'], '.')) ?></span></div><?php endif ?>
        <?php foreach ($nav['columns'] as $column): ?>
        <div><strong><?= esc($column['heading'] ?? '') ?></strong><?php foreach ($column['links'] ?? [] as $link): ?><a href="<?= esc(Cms::href($link['link'] ?? '', $link['linkUrl'] ?? '')) ?>"><?= esc(Cms::linkText($link)) ?></a><?php endforeach ?></div>
        <?php endforeach ?>
    </div>
    <div class="ff-footer-bottom"><span><?= esc(str_replace('{year}', date('Y'), (string) $nav['copyright'])) ?></span><div><?php foreach ($nav['legal'] as $link): ?><a href="<?= esc(Cms::href($link['link'] ?? '', $link['linkUrl'] ?? '')) ?>"><?= esc(Cms::linkText($link)) ?></a><?php endforeach ?></div></div>
</footer>
</div>
<?php if ($tracking && $integrations['cookieBanner']): ?>
<div class="ff-cookie" role="dialog" aria-label="Cookie preferences" data-cookie-banner hidden>
    <p>We use analytics cookies to understand how our site is used.</p>
    <div><button type="button" class="ff-pill ff-pill-line is-sm" data-cookie-choice="declined">Decline</button><button type="button" class="ff-pill ff-pill-dark is-sm" data-cookie-choice="accepted">Accept</button></div>
</div>
<?php endif ?>
<script src="<?= base_url('assets/js/site.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/site.js') ?>" defer></script>
</body>
</html>
