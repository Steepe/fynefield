<?php
use App\Libraries\Cms;

$siteName = $general['siteName'];
$siteTitle = $title ?? $siteName;
$description = $metaDescription ?? $seo['defaultDesc'];
$canonical = current_url();
$noindex = ($noindex ?? false) || ! $seo['indexing'];
$socialImage = ($shareImage ?? '') ?: $seo['shareImage'];
$socialImage = $socialImage ? base_url(ltrim($socialImage, '/')) : '';
$currentPath = '/' . trim(service('uri')->getPath(), '/');
$headerLinks = array_filter($nav['header'], static fn (array $item): bool => ($item['show'] ?? 'Visible') !== 'Hidden');
$isActive = static function (string $href) use ($currentPath): bool {
    $path = '/' . trim((string) parse_url($href, PHP_URL_PATH), '/');
    return $path === $currentPath || ($path !== '/' && (str_starts_with($currentPath, $path . '/') || ($path === '/services' && str_starts_with($currentPath, '/services/'))));
};
?>
<!doctype html>
<html lang="<?= esc(Cms::htmlLang()) ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc($description) ?>"><link rel="canonical" href="<?= esc($canonical) ?>">
    <?php if ($noindex): ?><meta name="robots" content="noindex,follow"><?php endif ?>
    <meta property="og:type" content="website"><meta property="og:site_name" content="<?= esc($siteName) ?>"><meta property="og:title" content="<?= esc($siteTitle) ?>"><meta property="og:description" content="<?= esc($description) ?>"><meta property="og:url" content="<?= esc($canonical) ?>">
    <?php if ($socialImage): ?><meta property="og:image" content="<?= esc($socialImage) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif ?>
    <?php if ($general['favicon']): ?><link rel="icon" href="<?= esc($general['favicon']) ?>"><?php endif ?>
    <title><?= esc($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/fynefield-reference.css?v=<?= filemtime(FCPATH . 'assets/css/fynefield-reference.css') ?>">
    <link rel="stylesheet" href="/assets/css/accessibility.css">
    <?php if (trim((string) $advanced['customCss']) !== ''): ?><style><?= str_replace('</', '<\/', (string) $advanced['customCss']) ?></style><?php endif ?>
    <script type="application/ld+json"><?= json_encode(array_filter(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $general['legalName'] ?: $siteName, 'url' => site_url('/'), 'logo' => $general['logo'] ? base_url(ltrim($general['logo'], '/')) : null, 'email' => $contact['email'] ?: null, 'telephone' => $contact['phone'] ?: null]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="fynefield-reference"><a class="skip-link" href="#main-content">Skip to content</a>
<header class="rf-header" data-site-header><div class="rf-shell rf-header-inner">
    <a class="rf-brand" href="<?= site_url('/') ?>"><img src="<?= esc($general['logo']) ?>" alt="<?= esc($siteName) ?>"></a>
    <nav class="rf-nav" aria-label="Main navigation"><?php foreach ($headerLinks as $item): ?><?php $href = Cms::href($item['link'] ?? '', $item['linkUrl'] ?? ''); $active = $isActive($href); ?><a class="<?= $active ? 'is-active' : '' ?>" href="<?= esc($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= esc(Cms::linkText($item)) ?></a><?php endforeach ?></nav>
    <a class="rf-header-cta" href="<?= esc(Cms::href($nav['cta']['link'] ?? 'page:contact-us', $nav['cta']['linkUrl'] ?? '')) ?>"><?= esc($nav['cta']['label'] ?: 'Get in touch') ?></a>
    <button class="rf-menu-button" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-menu">Menu</button>
</div><nav class="rf-mobile-nav" id="site-menu" aria-label="Mobile navigation" hidden><?php foreach ($headerLinks as $item): ?><a href="<?= esc(Cms::href($item['link'] ?? '', $item['linkUrl'] ?? '')) ?>"><?= esc(Cms::linkText($item)) ?></a><?php endforeach ?><a href="<?= esc(Cms::href($nav['cta']['link'] ?? 'page:contact-us', $nav['cta']['linkUrl'] ?? '')) ?>"><?= esc($nav['cta']['label'] ?: 'Get in touch') ?></a></nav></header>
<?php if (session('error') && ! session('inquiry_form')): ?><div class="rf-flash is-error" role="alert"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('success') && ! session('inquiry_form')): ?><div class="rf-flash is-success" role="status"><?= esc(session('success')) ?></div><?php endif ?>
<main id="main-content" tabindex="-1"><?= $this->renderSection('content') ?></main>
<footer class="rf-footer"><div class="rf-shell"><div class="rf-footer-grid"><div><strong class="rf-footer-name"><?= esc($siteName) ?></strong><p>Integrated petroleum trading, bulk storage, logistics and retail across Nigeria since 2013.</p></div><div><span>Company</span><a href="<?= esc(Cms::href('page:about-us')) ?>">About us</a><a href="<?= esc(Cms::href('page:management-team')) ?>">Leadership</a></div><div><span>Newsroom</span><a href="<?= esc(Cms::href('page:news')) ?>">News & insights</a></div><div><span>Services</span><a href="<?= site_url('services/bulk-storage') ?>">Bulk storage</a><a href="<?= site_url('services/logistics') ?>">Logistics</a><a href="<?= site_url('services/retail-outlets') ?>">Retail outlets</a></div><div><span>Head office</span><p><?= esc(rtrim($contact['address'], '.')) ?></p><a href="tel:<?= esc(preg_replace('/\s+/', '', $contact['phone'])) ?>"><?= esc($contact['phone']) ?></a><a href="mailto:<?= esc($contact['email']) ?>"><?= esc($contact['email']) ?></a></div></div><div class="rf-footer-bottom"><span>© <?= date('Y') ?> <?= esc($general['legalName'] ?: $siteName) ?></span><div><a href="<?= esc(Cms::href('page:privacy-policy')) ?>">Privacy policy</a><a href="<?= site_url('sitemap.xml') ?>">Sitemap</a></div></div></div></footer>
<script src="/assets/js/reference-site.js?v=<?= filemtime(FCPATH . 'assets/js/reference-site.js') ?>" defer></script>
</body></html>
