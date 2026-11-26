<?php

/*
 * The supplied redesign is an authored Design Component document, not a flat
 * screenshot. Its generated document contains the interaction runtime, local
 * media, and locally bundled Inter font subsets. Returning it directly keeps
 * the public presentation faithful to the supplied source instead of making a
 * second, inevitably divergent, implementation from its markup.
 */

$reference = FCPATH . 'assets/redesign/fynefield-redesign.html';

if (! is_file($reference)) {
    throw new RuntimeException('The Fynefield redesign preview has not been generated. Run scripts/unpack-fynefield-reference.mjs first.');
}

$screen = $screen ?? 'Home';
$html = file_get_contents($reference);
// The reference is an SPA. Seed its initial state from the server route so a
// direct link to one of the CMS-managed public URLs lands on the corresponding
// authored screen, not on the home screen.
$encode = static fn ($value) => json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
$html = preg_replace_callback('~(<script type="__bundler/template">\s*)(.*?)(\s*</script>)~s', static function ($match) use ($screen, $data, $sections, $forms, $contact, $redesign, $title, $metaDescription, $shareImage, $noindex, $seo, $article, $encode) {
    $template = json_decode($match[2], true, 512, JSON_THROW_ON_ERROR);
    $template = preg_replace("/page: 'Home'/", "page: '" . $screen . "'", $template, 1);
    $template = str_replace("const all = [['about'", "const all = [['home','Home','Home.dc.html'],['about'", $template);
    $template = str_replace("allItems: [{ label: 'Home', href: 'Home.dc.html', cur: cur === 'home' ? 'page' : undefined }].concat(all)", 'allItems: all', $template);
    $screenSlugs = ['Home' => 'home', 'About' => 'about-us', 'Services' => 'services', 'Leadership' => 'management-team', 'News' => 'news', 'Contact' => 'contact-us'];
    $builderUrls = [];
    foreach ($screenSlugs as $pageName => $pageSlug) {
        if (in_array($pageSlug, array_column($redesign['builderPages'] ?? [], 'slug'), true) || ! isset($data['pageVisibility'][$pageSlug]) || in_array($data['pageVisibility'][$pageSlug], ['Password protected', 'Hidden from search'], true)) $builderUrls[$pageName] = site_url($pageSlug === 'home' ? '/' : $pageSlug);
    }
    $template = str_replace('const PAGES = ', 'const BUILDER_URLS = ' . $encode($builderUrls) . ";\nconst PAGES = ", $template);
    $template = str_replace("Object.values(this.subs).forEach(s => s.componentDidMount && s.componentDidMount());\n    try", "Object.values(this.subs).forEach(s => s.componentDidMount && s.componentDidMount());\n    if (window.location.hash && this.state.page === 'Services') this.go('Services', window.location.hash);\n    try", $template);
    if ($article !== null) $template = str_replace("try { document.title = TITLES[this.state.page]; }", 'try { document.title = ' . $encode($title) . '; }', $template);
    $meta = '<meta name="description" content="' . esc($metaDescription, 'attr') . '"><link rel="canonical" href="' . esc(current_url(), 'attr') . '">';
    if ($noindex || ! $seo['indexing']) $meta .= '<meta name="robots" content="noindex,follow">';
    if ($shareImage) $meta .= '<meta property="og:image" content="' . esc(base_url(ltrim($shareImage, '/')), 'attr') . '">';
    $meta .= '<meta property="og:title" content="' . esc($title, 'attr') . '"><meta property="og:description" content="' . esc($metaDescription, 'attr') . '">';
    $template = preg_replace('~<head>~', '<head>' . $meta, $template, 1);
    $template = str_replace("e.preventDefault();\n        this.go(m[1], m[2]);", "e.preventDefault();\n        if (BUILDER_URLS[m[1]]) { window.location.assign(BUILDER_URLS[m[1]] + (m[2] || '')); return; }\n        this.go(m[1], m[2]);", $template);
    $template = str_replace('<a href="#" style="color:#a9a6c8;text-decoration:none" style-hover="color:#ffffff">Privacy policy</a>', '<a href="' . esc(site_url('privacy-policy'), 'attr') . '" style="color:#a9a6c8;text-decoration:none" style-hover="color:#ffffff">Privacy policy</a>', $template);
    $template = str_replace('<a href="#" style="color:#a9a6c8;text-decoration:none" style-hover="color:#ffffff">Sitemap</a>', '<a href="' . esc(site_url('sitemap.xml'), 'attr') . '" style="color:#a9a6c8;text-decoration:none" style-hover="color:#ffffff">Sitemap</a>', $template);
    $designText = static fn ($text) => str_replace(['{', '}'], ['&#123;', '&#125;'], esc($text));
    foreach (['Energy that keeps Nigeria moving.' => 'homeHeading', 'Trading, storing, transporting and retailing petroleum products across Nigeria.' => 'homeIntro', 'Built for dependable supply.' => 'aboutHeading', 'Solutions that move energy forward.' => 'servicesHeading', 'Our leadership.' => 'leadershipHeading', 'News &amp; insights.' => 'newsHeading', 'Contact us.' => 'contactHeading'] as $original => $key) {
        if (trim((string) ($redesign[$key] ?? '')) !== '') $template = str_replace($original, $designText($redesign[$key]), $template);
    }
    foreach ($redesign['textOverrides'] as $override) {
        $original = trim((string) ($override['original'] ?? ''));
        if ($original !== '') $template = str_replace(esc($original), $designText((string) ($override['replacement'] ?? '')), $template);
    }
    $images = [];
    if (! empty($redesign['heroImage'])) $images['depotAerial'] = \App\Libraries\Cms::safeMediaPath($redesign['heroImage']);
    foreach ($redesign['imageOverrides'] as $override) {
        $key = (string) ($override['key'] ?? '');
        $path = \App\Libraries\Cms::safeMediaPath($override['image'] ?? '');
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $key) && $path !== '') $images[$key] = $path;
    }
    $template = str_replace('return m;\n}', 'return Object.assign(m, ' . $encode($images) . ');\n}', $template);
    // JavaScript source contains real newlines, not escaped newline characters.
    $template = str_replace("return m;\n}", 'return Object.assign(m, ' . $encode($images) . ");\n}", $template);

    $team = array_map(static fn ($p) => ['name' => $p['name'], 'role' => $p['position'], 'group' => $p['group_name'] ?: 'Management Team', 'photo' => $p['image'], 'bio' => $p['full_bio'] ?: $p['bio']], $data['team']);
    $template = preg_replace_callback('~const people = \[.*?\]\.map\(p =>~s', static fn () => 'const people = ' . $encode($team) . '.map(p =>', $template, 1);
    $posts = array_map(static function ($p) {
        $body = preg_split('/\n\s*\n/', trim(strip_tags((string) $p['content']))) ?: [];
        return ['id' => (string) $p['id'], 'title' => $p['title'], 'date' => $p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '', 'category' => $p['category'] ?: 'Company news', 'cover' => $p['cover_image'] ?: '', 'excerpt' => $p['excerpt'] ?: '', 'body' => $body, 'quote' => $p['quote'] ?: '', 'author' => $p['author'] ?: 'Fynefield Petroleum', 'gallery' => []];
    }, $data['posts']);
    $template = preg_replace_callback('~const list = \[.*?\];\s*const all = list\.map~s', static fn () => 'const list = ' . $encode($posts) . '; const all = list.map', $template, 1);
    if ($article !== null) $template = str_replace('state = { open: null, sub: false, cat: \'All\' };', 'state = { open: ' . $encode((string) $article['id']) . ', sub: false, cat: \'All\' };', $template);
    $template = str_replace("asset(ph + a.cover)", "a.cover", $template);
    $template = str_replace("['All', 'Company news', 'Operations'].map", "['All', ...new Set(all.map(a => a.category))].map", $template);
    $template = str_replace("['Bulk supply', 'Storage', 'Haulage', 'Retail partnership', 'Something else'].map", $encode(array_column($forms['topics'], 'label')) . '.map', $template);

    // Render the homepage newsroom from the same published collection as News.
    $cards = '';
    foreach (array_slice($data['posts'], 0, 3) as $post) {
        $date = $post['published_at'] ? date('d M Y', strtotime($post['published_at'])) : '';
        $image = \App\Libraries\Cms::safeMediaPath($post['cover_image'] ?? '');
        $cards .= '<a href="' . esc(site_url('news/' . $post['slug']), 'attr') . '" style="display:grid;gap:18px;align-content:start;color:#15132b;text-decoration:none">';
        if ($image !== '') $cards .= '<span class="ff-zoom" style="position:relative;display:block;aspect-ratio:16/10;border-top-right-radius:56px;background:#2f2a60"><img class="ff-zi" src="' . esc($image, 'attr') . '" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover"></span>';
        $cards .= '<span style="font-size:13px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#c8202a">' . $designText(($post['category'] ?: 'Company news') . ($date ? ' · ' . $date : '')) . '</span><span class="ff-h3">' . $designText($post['title']) . '</span><span style="color:#55536e">' . $designText($post['excerpt'] ?? '') . '</span><span class="ff-link">Read post →</span></a>';
    }
    $newsletter = '<form method="post" action="' . esc(site_url('newsletter'), 'attr') . '" style="display:flex;flex-wrap:wrap;gap:8px"><input type="hidden" name="' . esc(csrf_token(), 'attr') . '" value="' . esc(csrf_hash(), 'attr') . '"><input type="email" name="email" required aria-label="Email address" placeholder="Email address" style="flex:1;min-width:200px;font:inherit;padding:16px;border:1px solid #cfcde0"><button class="ff-btn ff-btn-i"><span class="ff-bt">Subscribe</span></button></form>';
    $newsSection = '<section data-screen-label="News" style="background:#fff"><div class="ff-wrap" style="padding-top:clamp(80px,10vw,140px);padding-bottom:clamp(80px,10vw,140px)"><div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:24px;margin-bottom:48px"><div style="display:grid;gap:18px"><span class="ff-eyebrow">Newsroom</span><h2 class="ff-h2">Latest from Fynefield</h2></div><a href="News.dc.html" class="ff-btn ff-btn-d"><span class="ff-bt">View all news</span></a></div><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:40px">' . $cards . '</div><div style="margin-top:40px;padding:32px;background:#f5f5f8;display:grid;gap:16px"><span class="ff-h3">Subscribe to our newsletter</span>' . $newsletter . '</div></div></section>';
    $template = preg_replace_callback('~<section data-screen-label="News".*?</section>(?=\s*<!-- FOOTPRINT)~s', static fn () => $newsSection, $template, 1);

    foreach ($data['services'] as $service) {
        $sectionId = $service['slug'] === 'retail-outlets' ? 'retail' : $service['slug'];
        if (! in_array($sectionId, ['bulk-storage', 'logistics', 'retail'], true)) continue;
        $template = preg_replace_callback('~<section id="' . preg_quote($sectionId, '~') . '".*?</section>~s', static function ($section) use ($service, $designText) {
            $markup = $section[0];
            $markup = preg_replace_callback('~(<h2 class="ff-h2">).*?(</h2>)~s', static fn ($m) => $m[1] . $designText($service['title']) . $m[2], $markup, 1);
            $markup = preg_replace_callback('~(<p class="ff-lede"[^>]*>).*?(</p>)~s', static fn ($m) => $m[1] . $designText($service['lede'] ?: $service['summary']) . $m[2], $markup, 1);
            $markup = preg_replace_callback('~(<p style="margin:0;color:#55536e;max-width:50ch">).*?(</p>)~s', static fn ($m) => $m[1] . $designText(strip_tags((string) $service['body'])) . $m[2], $markup, 1);
            $image = \App\Libraries\Cms::safeMediaPath($service['image'] ?? '');
            if ($image !== '') $markup = preg_replace_callback('~(<img class="ff-zi" src=")[^"]*(")~', static fn ($m) => $m[1] . esc($image, 'attr') . $m[2], $markup, 1);
            return $markup;
        }, $template, 1);
    }

    $formId = 'contact-us:contact';
    foreach ($data['contactSections'] as $section) if (($section['type'] ?? '') === 'form') { $formId = 'contact-us:' . $section['id']; break; }
    $firstTopic = $forms['topics'][0]['label'] ?? '';
    $template = str_replace("topic: 'Bulk supply', openQ", 'topic: ' . $encode($firstTopic) . ', openQ', $template);
    $hidden = '<input type="hidden" name="' . esc(csrf_token(), 'attr') . '" value="' . esc(csrf_hash(), 'attr') . '">';
    $template = str_replace('<form sc-camel-on-submit="{{ contact.submit }}"', '<form method="post" action="' . esc(site_url('contact-us'), 'attr') . '"', $template);
    $template = str_replace('<div style="grid-column:1/-1;display:grid;gap:16px;margin-bottom:8px"><span class="ff-eyebrow">Send an inquiry', $hidden . '<input type="hidden" name="form_id" value="' . esc($formId, 'attr') . '"><input type="hidden" name="subject" value="Website inquiry"><input type="hidden" name="topic" value="{{ contact.selectedTopic }}"><input type="text" name="company_website" tabindex="-1" autocomplete="off" style="position:absolute;left:-10000px"><div style="grid-column:1/-1;display:grid;gap:16px;margin-bottom:8px"><span class="ff-eyebrow">Send an inquiry', $template);
    $template = str_replace('<input required="" type="checkbox" style="margin-top:3px', '<input required="" type="checkbox" name="privacy_consent" value="1" style="margin-top:3px', $template);
    $template = str_replace("sent: this.state.sent, notSent: !this.state.sent,", "sent: this.state.sent, notSent: !this.state.sent, selectedTopic: this.state.topic,", $template);
    $sent = session('inquiry_form') && session('inquiry_reference');
    if ($sent && $screen === 'Contact') {
        $template = str_replace('state = { sent: false, topic: ' . $encode($firstTopic), 'state = { sent: true, topic: ' . $encode($firstTopic), $template);
        $template = str_replace("ref: 'FP-26-' + this.state.n", 'ref: ' . $encode(session('inquiry_reference')), $template);
        $template = str_replace('A confirmation has been sent to your email.', 'We will be in touch.', $template);
    }
    foreach (['home', 'news'] as $part) {
        $template = str_replace('<form sc-camel-on-submit="{{ ' . $part . '.subscribe }}"', '<form method="post" action="' . esc(site_url('newsletter'), 'attr') . '"', $template);
    }
    $template = str_replace('<input type="email" required="" aria-label="Email address"', $hidden . '<input type="email" name="email" required="" aria-label="Email address"', $template);
    $template = str_replace(['+234 807 119 1635', '+2348071191635', 'info@fynefield.com', 'Suite 3, 4th Floor, Sinoki House, Ahmadu Bello Way, CBD, Abuja'], [esc($contact['phone']), esc(preg_replace('/\s+/', '', $contact['phone'])), esc($contact['email']), esc($contact['address'])], $template);
    $message = session('error') ?: (session('errors') ? implode(' ', session('errors')) : session('success'));
    if ($message) $template = str_replace('<!-- ===== HEADER ===== -->', '<div role="status" style="position:relative;z-index:100;background:#fff4cb;color:#15132b;padding:16px;text-align:center">' . esc($message) . '</div><!-- ===== HEADER ===== -->', $template);
    return $match[1] . $encode($template) . $match[3];
}, $html, 1);

echo $html;
