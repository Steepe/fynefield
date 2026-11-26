<?php

namespace App\Controllers;

use App\Libraries\Cms;
use CodeIgniter\Exceptions\PageNotFoundException;

class Site extends BaseController
{
    public function health()
    {
        try {
            db_connect()->query('SELECT 1');
            return $this->response->setJSON(['status' => 'ok', 'time' => gmdate('c')]);
        } catch (\Throwable $exception) {
            log_message('critical', 'Health check database failure: {message}', ['message' => $exception->getMessage()]);
            return $this->response->setStatusCode(503)->setJSON(['status' => 'unavailable']);
        }
    }

    public function notFound(string $message = ''): string
    {
        $title = Cms::pageTitle('Page not found');
        $metaDescription = 'The page you requested is unavailable. Explore Fynefield Petroleum services, news, or contact our team.';
        $noindex = true;
        return view('site/not_found', $this->base(compact('title', 'metaDescription', 'noindex')));
    }

    /** Everything the layout needs: site settings, navigation and footer content from the Studio. */
    private function base(array $data = []): array
    {
        return $data + [
            'general' => Cms::get('general'), 'contact' => Cms::get('contact'), 'seo' => Cms::get('seo'), 'integrations' => Cms::get('integrations'),
            'advanced' => Cms::get('advanced'), 'nav' => Cms::get('nav'), 'theme' => Cms::get('theme'), 'forms' => Cms::get('forms'), 'redesign' => Cms::get('redesign'),
        ];
    }

    private function visibleContent(string $table)
    {
        // Scheduled content becomes visible without needing a cron worker.
        // The initial status filter prevents any other editorial state leaking out.
        return db_connect()->table($table)->whereIn('status', ['published', 'scheduled'])->groupStart()->where('status', 'published')->orWhere('published_at <=', date('Y-m-d H:i:s'))->groupEnd();
    }

    /** Collection data a page's sections need, loaded only when one of them uses it. */
    private function sectionData(array $sections): array
    {
        $types = array_column($sections, 'type');
        $needs = static fn (string ...$wanted): bool => (bool) array_intersect($wanted, $types);
        $db = db_connect();
        $data = ['services' => [], 'team' => [], 'locations' => [], 'posts' => []];
        if ($needs('picker', 'serviceList')) $data['services'] = $db->table('services')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
        if ($needs('team')) $data['team'] = $db->table('team_members')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
        if ($needs('map')) $data['locations'] = $db->table('locations')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
        if ($needs('news')) $data['posts'] = $this->visibleContent('posts')->orderBy('published_at', 'DESC')->get()->getResultArray();
        return $data;
    }

    private function renderPage(array $page, array $document, bool $isPreview = false, ?array $article = null)
    {
        $sections = array_values(array_filter($document['sections'], static fn (array $section): bool => ! $section['hidden']));
        $slug = trim($document['slug'], '/') ?: 'home';
        if (! $isPreview && $document['visibility'] === 'Password protected' && ! empty($page['page_password']) && ! session('unlocked_page_' . $page['id'])) {
            return view('site/password', $this->base(['title' => Cms::pageTitle($document['title']), 'pageTitle' => $document['title'], 'slug' => $slug, 'noindex' => true]));
        }
        $title = $document['seoTitle'] ?: Cms::pageTitle($document['title']);
        $metaDescription = $document['seoDesc'] ?: Cms::setting('seo', 'defaultDesc');
        $shareImage = $document['shareImage'] ?: Cms::setting('seo', 'shareImage');
        $noindex = $isPreview || (int) ($page['noindex'] ?? 0) === 1 || $document['visibility'] === 'Hidden from search';
        if ($article !== null) {
            $title = $article['seo_title'] ?: Cms::pageTitle($article['title']);
            $metaDescription = $article['meta_description'] ?: $article['excerpt'];
            $shareImage = $article['cover_image'];
            $noindex = (int) $article['noindex'] === 1;
        }
        $headerSolid = $document['headerStyle'] === 'Solid' || ($sections[0]['type'] ?? '') !== 'hero';
        $footerCta = (bool) $document['footerCta'];
        $data = $this->sectionData($sections);
        // The public reference pages use the same CMS records, but have a fixed
        // editorial composition rather than an arbitrary block sequence.
        if (in_array($slug, ['home', 'about-us', 'services', 'management-team', 'news', 'contact-us'], true)) {
            $db = db_connect();
            $data['services'] = $db->table('services')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
            $data['team'] = $db->table('team_members')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
            $data['locations'] = $db->table('locations')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
            $data['posts'] = $this->visibleContent('posts')->orderBy('published_at', 'DESC')->get()->getResultArray();
            $contactPage = $this->visibleContent('pages')->where('slug', 'contact-us')->get()->getRowArray();
            $data['contactSections'] = $contactPage ? Cms::sections($contactPage['sections']) : [];
            // The design is an SPA: links to unpublished or protected pages
            // must still go through the server's visibility checks.
            $publishedPages = $this->visibleContent('pages')->select('slug,visibility')->get()->getResultArray();
            $data['pageVisibility'] = array_column($publishedPages, 'visibility', 'slug');
        }
        $pageTitle = $document['title'];
        // The supplied redesign is a complete authored interactive document.
        // Use it for the public experience; the Studio, CMS records, and all
        // protected editorial routes remain available through their own routes.
        $redesignPages = ['home', 'about-us', 'services', 'management-team', 'news', 'contact-us'];
        $builderPages = array_column(Cms::get('redesign')['builderPages'] ?? [], 'slug');
        if (! $isPreview && in_array($slug, $redesignPages, true) && ! in_array($slug, $builderPages, true)) {
            $screen = ['home' => 'Home', 'about-us' => 'About', 'services' => 'Services', 'management-team' => 'Leadership', 'news' => 'News', 'contact-us' => 'Contact'][$slug];
            return view('site/redesign', $this->base(compact('screen', 'data', 'sections', 'document', 'title', 'metaDescription', 'shareImage', 'noindex', 'article')));
        }
        $builderPage = true;
        return view('site/page', $this->base(compact('page', 'sections', 'slug', 'title', 'metaDescription', 'shareImage', 'noindex', 'headerSolid', 'footerCta', 'data', 'isPreview', 'pageTitle', 'builderPage')));
    }

    public function home()
    {
        $legacyPageId = $this->request->getGet('page_id');
        $legacyPostId = $this->request->getGet('p');
        if (is_string($legacyPageId) && ctype_digit($legacyPageId)) {
            $page = $this->visibleContent('pages')->where('legacy_source_id', (int) $legacyPageId)->get()->getRowArray();
            if ($page) return redirect()->to(Cms::href('page:' . $page['slug']), 301);
        }
        if (is_string($legacyPostId) && ctype_digit($legacyPostId)) {
            $post = $this->visibleContent('posts')->where('legacy_source_id', (int) $legacyPostId)->get()->getRowArray();
            if ($post) return redirect()->to(site_url('news/' . $post['slug']), 301);
        }
        return $this->page('home');
    }

    private function redirectByPath(string $path)
    {
        $path = '/' . trim($path, '/');
        $redirect = db_connect()->table('redirects')->where(['source_path' => $path, 'is_active' => 1])->get()->getRowArray();
        if (! $redirect) throw PageNotFoundException::forPageNotFound();
        return redirect()->to($redirect['target_url'], (int) $redirect['http_code']);
    }

    public function page(string $slug)
    {
        $page = $this->visibleContent('pages')->where('slug', $slug)->get()->getRowArray();
        if (! $page) return $this->redirectByPath($slug);
        $live = $page; unset($live['draft']);
        return $this->renderPage($page, Cms::pageDocument($live));
    }

    public function legacyRedirect(string $path)
    {
        return $this->redirectByPath($path);
    }

    public function unlockPage(string $slug)
    {
        $page = $this->visibleContent('pages')->where('slug', $slug)->get()->getRowArray();
        if (! $page || empty($page['page_password'])) throw PageNotFoundException::forPageNotFound();
        $throttler = service('throttler');
        if (! $throttler->check('page-unlock-' . hash('sha256', (string) $this->request->getIPAddress()), 10, 600)) return redirect()->back()->with('error', 'Too many attempts. Please wait a few minutes.');
        if (! password_verify((string) $this->request->getPost('password'), $page['page_password'])) return redirect()->back()->with('error', 'That password is not correct.');
        session()->set('unlocked_page_' . $page['id'], true);
        return redirect()->to(Cms::href('page:' . $page['slug']));
    }

    public function previewPage(string $slug)
    {
        $token = (string) $this->request->getGet('token');
        $page = db_connect()->table('pages')->where('slug', $slug)->get()->getRowArray();
        if (! $page || strlen($token) !== 64 || empty($page['preview_token']) || empty($page['preview_token_expires']) || strtotime($page['preview_token_expires']) < time() || ! hash_equals($page['preview_token'], $token)) throw PageNotFoundException::forPageNotFound();
        // A preview shows the working draft, not the published version.
        return $this->renderPage($page, Cms::pageDocument($page), true);
    }

    public function previewPost(string $slug): string
    {
        $token = (string) $this->request->getGet('token');
        $post = db_connect()->table('posts')->where('slug', $slug)->get()->getRowArray();
        if (! $post || strlen($token) !== 64 || empty($post['preview_token']) || empty($post['preview_token_expires']) || strtotime($post['preview_token_expires']) < time() || ! hash_equals($post['preview_token'], $token)) throw PageNotFoundException::forPageNotFound();
        return $this->articleView($post, true);
    }

    public function sitemap()
    {
        if (! Cms::setting('seo', 'sitemap', true)) throw PageNotFoundException::forPageNotFound();
        $pages = $this->visibleContent('pages')->where('noindex', 0)->where('visibility', 'Public')->select('slug, updated_at')->orderBy('sort_order')->get()->getResultArray();
        $posts = $this->visibleContent('posts')->where('noindex', 0)->select('slug, updated_at')->get()->getResultArray();
        $services = db_connect()->table('services')->where('status', 'published')->select('slug, updated_at')->get()->getResultArray();
        $urls = [];
        foreach ($pages as $page) $urls[] = ['loc' => Cms::href('page:' . $page['slug']), 'lastmod' => $page['updated_at']];
        foreach ($services as $service) $urls[] = ['loc' => site_url('services/' . $service['slug']), 'lastmod' => $service['updated_at']];
        foreach ($posts as $post) $urls[] = ['loc' => site_url('news/' . $post['slug']), 'lastmod' => $post['updated_at']];

        // Do not render XML through the view service: development debug markers
        // are HTML comments and would make the XML declaration invalid.
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if ($url['lastmod']) $xml .= '<lastmod>' . htmlspecialchars(date('c', strtotime($url['lastmod'])), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>';
            $xml .= '</url>';
        }
        return $this->response->setContentType('application/xml')->setBody($xml . '</urlset>');
    }

    public function robots()
    {
        if (! Cms::setting('seo', 'indexing', true)) return $this->response->setContentType('text/plain')->setBody("User-agent: *\nDisallow: /\n");
        $sitemap = Cms::setting('seo', 'sitemap', true) ? "\nSitemap: " . site_url('sitemap.xml') . "\n" : '';
        return $this->response->setContentType('text/plain')->setBody("User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /preview/\n" . $sitemap);
    }

    public function service(string $slug)
    {
        $db = db_connect();
        $service = $db->table('services')->where(['slug' => $slug, 'status' => 'published'])->get()->getRowArray();
        if (! $service) throw PageNotFoundException::forPageNotFound();
        $builderPages = array_column(Cms::get('redesign')['builderPages'] ?? [], 'slug');
        if (! in_array('services', $builderPages, true) && in_array($slug, ['bulk-storage', 'logistics', 'retail-outlets'], true)) return redirect()->to(site_url('services') . '#' . ($slug === 'retail-outlets' ? 'retail' : $slug), 302);
        $services = $db->table('services')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray();
        $title = Cms::pageTitle($service['title']);
        $metaDescription = $service['summary'];
        $shareImage = $service['image'];
        return view('site/service', $this->base(compact('service', 'services', 'title', 'metaDescription', 'shareImage')));
    }

    public function article(string $slug): string
    {
        $post = $this->visibleContent('posts')->where('slug', $slug)->get()->getRowArray();
        if (! $post) throw PageNotFoundException::forPageNotFound();
        $newsPage = $this->visibleContent('pages')->where('slug', 'news')->get()->getRowArray();
        $builderPages = array_column(Cms::get('redesign')['builderPages'] ?? [], 'slug');
        if ($newsPage && $newsPage['visibility'] === 'Public' && ! in_array('news', $builderPages, true)) {
            unset($newsPage['draft']);
            return $this->renderPage($newsPage, Cms::pageDocument($newsPage), false, $post);
        }
        return $this->articleView($post, false);
    }

    private function articleView(array $post, bool $isPreview): string
    {
        $title = $post['seo_title'] ?: Cms::pageTitle($post['title']);
        $metaDescription = $post['meta_description'] ?: $post['excerpt'];
        $shareImage = $post['cover_image'];
        $noindex = $isPreview || (int) $post['noindex'] === 1;
        $nextPost = $isPreview ? null : $this->visibleContent('posts')->where('id !=', $post['id'])->orderBy('published_at', 'DESC')->get()->getRowArray();
        $newsHref = Cms::href('page:news');
        return view('site/article', $this->base(compact('post', 'title', 'metaDescription', 'shareImage', 'noindex', 'nextPost', 'isPreview', 'newsHref')));
    }

    /** Finds the inquiry form section a submission came from, so its own settings apply. */
    private function formSection(string $formId): array
    {
        [$slug, $sectionId] = array_pad(explode(':', $formId, 2), 2, '');
        $page = $this->visibleContent('pages')->where('slug', $slug)->get()->getRowArray();
        if (! $page) return [null, null];
        foreach (Cms::sections($page['sections']) as $section) if ($section['type'] === 'form' && $section['id'] === $sectionId) return [$page, $section];
        return [$page, null];
    }

    private function notifyInquiry(int $inquiryId, array $inquiry, string $now): void
    {
        $forms = Cms::get('forms');
        $contactEmail = (string) Cms::setting('contact', 'email');
        $route = $contactEmail;
        foreach ($forms['topics'] as $topic) if (($topic['label'] ?? '') === $inquiry['topic'] && filter_var($topic['route'] ?? '', FILTER_VALIDATE_EMAIL)) $route = $topic['route'];
        $db = db_connect();
        if (! Cms::emailConfigured() || ! filter_var($route, FILTER_VALIDATE_EMAIL)) {
            $db->table('inquiries')->where('id', $inquiryId)->update(['notification_status' => 'not_configured', 'updated_at' => $now]);
            return;
        }
        $body = "Reference: {$inquiry['reference']}\nTopic: {$inquiry['topic']}\nName: {$inquiry['name']}\nCompany: {$inquiry['company']}\nEmail: {$inquiry['email']}\nPhone: {$inquiry['phone']}\n\nMessage:\n{$inquiry['message']}";
        $sent = Cms::sendEmail($route, 'New website inquiry: ' . $inquiry['subject'], $body, $inquiry['email'], $inquiry['name']);
        $db->table('inquiries')->where('id', $inquiryId)->update(['notification_status' => $sent ? 'sent' : 'failed', 'notified_at' => $sent ? $now : null, 'updated_at' => $now]);
        if ($forms['autoReply']) {
            $text = strtr((string) $forms['autoReplyText'], ['{ref}' => $inquiry['reference'], '{topic}' => strtolower((string) $inquiry['topic']), '{name}' => $inquiry['name']]);
            Cms::sendEmail($inquiry['email'], 'We received your inquiry · ' . $inquiry['reference'], $text, $contactEmail);
        }
    }

    public function storeInquiry()
    {
        [$page, $section] = $this->formSection((string) $this->request->getPost('form_id'));
        $section ??= Cms::sectionDefaults()['form'];
        $anchor = trim((string) ($section['anchor'] ?? ''));
        $returnTo = $page ? Cms::href('page:' . $page['slug']) . ($anchor !== '' ? '#' . $anchor : '') : site_url('/');
        $formId = (string) $this->request->getPost('form_id');
        $forms = Cms::get('forms');
        $success = fn (string $reference) => redirect()->to($returnTo)->with('success', 'Thank you. Your inquiry has been received.')->with('inquiry_form', $formId)->with('inquiry_reference', $reference);

        // This field is visually hidden from people but attractive to basic form bots.
        // Return the normal confirmation so a bot cannot use the response as an oracle.
        if ($forms['spam'] && trim((string) $this->request->getPost('company_website')) !== '') return $success($forms['refPrefix'] . random_int(1000, 9999));

        $short = ($section['fields'] ?? '') === 'Name, email, message';
        $rules = ['name' => 'required|max_length[160]', 'company' => 'permit_empty|max_length[160]', 'email' => 'required|valid_email|max_length[190]', 'phone' => 'permit_empty|max_length[80]', 'subject' => ($short ? 'permit_empty' : 'required') . '|max_length[190]', 'message' => 'required|max_length[5000]', 'topic' => 'permit_empty|max_length[80]'];
        if ($section['consent'] ?? true) $rules['privacy_consent'] = 'required|in_list[1]';
        if (! $this->validate($rules, ['privacy_consent' => ['required' => 'Please confirm your consent so we can respond.']])) return redirect()->to($returnTo)->withInput()->with('errors', $this->validator->getErrors())->with('inquiry_form', $formId);

        if ($forms['spam']) {
            $throttler = service('throttler');
            $key = 'contact-inquiry-' . hash('sha256', (string) $this->request->getIPAddress());
            if (! $throttler->check($key, 3, 600)) return redirect()->to($returnTo)->withInput()->with('error', 'Please wait a few minutes before sending another inquiry.')->with('inquiry_form', $formId);
        }

        $topics = array_column($forms['topics'], 'label');
        $topic = in_array($this->request->getPost('topic'), $topics, true) ? (string) $this->request->getPost('topic') : ($topics[0] ?? '');
        $now = date('Y-m-d H:i:s');
        $inquiry = array_map(static fn ($value) => trim((string) $value), $this->request->getPost(['name', 'company', 'email', 'phone', 'subject', 'message']));
        if ($inquiry['subject'] === '') $inquiry['subject'] = $topic . ' inquiry';
        $inquiry['topic'] = $topic;
        $db = db_connect();
        $db->table('inquiries')->insert($inquiry + ['status' => 'new', 'consented_at' => ($section['consent'] ?? true) ? $now : null, 'notification_status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
        $inquiryId = (int) $db->insertID();
        $inquiry['reference'] = $forms['refPrefix'] . str_pad((string) $inquiryId, 4, '0', STR_PAD_LEFT);
        $db->table('inquiries')->where('id', $inquiryId)->update(['reference' => $inquiry['reference']]);
        $db->table('activity_log')->insert(['user_id' => null, 'action' => 'received inquiry', 'entity_type' => 'inquiries', 'entity_id' => $inquiryId, 'details' => $inquiry['reference'], 'created_at' => $now]);
        $this->notifyInquiry($inquiryId, $inquiry, $now);
        return $success($inquiry['reference']);
    }

    public function subscribe()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $list = trim((string) $this->request->getPost('list')) ?: 'Operational updates';
        $ajax = $this->request->isAJAX();
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 || mb_strlen($list) > 80) {
            return $ajax ? $this->response->setStatusCode(422)->setJSON(['error' => 'Enter a valid email address.']) : redirect()->back()->with('error', 'Enter a valid email address.');
        }
        $throttler = service('throttler');
        if (! $throttler->check('newsletter-' . hash('sha256', (string) $this->request->getIPAddress()), 5, 600)) {
            return $ajax ? $this->response->setStatusCode(429)->setJSON(['error' => 'Please try again in a few minutes.']) : redirect()->back()->with('error', 'Please try again in a few minutes.');
        }
        $db = db_connect();
        if (! $db->table('newsletter_subscribers')->where(['email' => $email, 'list_name' => $list])->countAllResults()) {
            $db->table('newsletter_subscribers')->insert(['email' => $email, 'list_name' => $list, 'created_at' => date('Y-m-d H:i:s')]);
        }
        return $ajax ? $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]) : redirect()->back()->with('success', "You're subscribed. Thank you.");
    }

}
