<?php

namespace App\Controllers;

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
        $title = 'Page not found | Fynefield Petroleum';
        $metaDescription = 'The page you requested is unavailable. Explore Fynefield Petroleum services, news, or contact our team.';
        $isPreview = true;
        return view('site/not_found', $this->base(compact('title', 'metaDescription', 'isPreview')));
    }
    private function settings(): array
    {
        $rows = db_connect()->table('settings')->get()->getResultArray();
        return array_column($rows, 'setting_value', 'setting_key');
    }
    private function base(array $data = []): array
    {
        $navigation = db_connect()->table('navigation_items')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();
        return $data + ['settings' => $this->settings(), 'navigation' => $navigation];
    }
    private function visibleContent(string $table)
    {
        // Scheduled content becomes visible without needing a cron worker.
        // The initial status filter prevents any other editorial state leaking out.
        return db_connect()->table($table)->whereIn('status', ['published', 'scheduled'])->groupStart()->where('status', 'published')->orWhere('published_at <=', date('Y-m-d H:i:s'))->groupEnd();
    }
    private function pageView(array $page, bool $isPreview = false): string
    {
        $db = db_connect();
        $sections = $db->table('page_sections')->where('page_id', $page['id'])->orderBy('sort_order')->orderBy('id')->get()->getResultArray();
        $mediaAlt = array_column($db->table('media')->select('path, alt_text')->get()->getResultArray(), 'alt_text', 'path');
        foreach ($sections as &$section) {
            $section['image_alt'] = trim((string) ($mediaAlt[$section['image'] ?? ''] ?? '')) ?: (string) ($section['heading'] ?? '');
            $section['item_rows'] = array_values(array_filter(array_map(static function (string $line): array {
                $parts = array_map('trim', explode('|', $line, 2));
                return ['label' => $parts[0] ?? '', 'value' => $parts[1] ?? ''];
            }, preg_split('/\R/', (string) ($section['items'] ?? '')) ?: []), static fn (array $item): bool => $item['label'] !== ''));
        }
        unset($section);
        $slug = $page['slug'];
        $team = $slug === 'management-team' ? $db->table('team_members')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray() : [];
        $locations = $slug === 'locator' ? $db->table('locations')->where('status', 'published')->get()->getResultArray() : [];
        $galleryImages = $slug === 'gallery' ? ['/assets/media/fynefield-commissioning.jpeg', '/assets/media/frontpage-image1.jpg', '/assets/media/frontpage-image3.jpg', '/assets/media/frontpage-image4.jpg', '/assets/media/big-1.jpg', '/assets/media/oil-small.jpg'] : [];
        $title = $page['seo_title'] ?: ($page['title'] . ' | Fynefield Petroleum');
        $metaDescription = $page['meta_description'] ?: $page['excerpt'];
        return view('site/page', $this->base(compact('page', 'sections', 'isPreview', 'team', 'locations', 'galleryImages', 'title', 'metaDescription')));
    }
    public function home()
    {
        $db = db_connect();
        $legacyPageId = $this->request->getGet('page_id');
        $legacyPostId = $this->request->getGet('p');
        if (is_string($legacyPageId) && ctype_digit($legacyPageId)) {
            $page = $this->visibleContent('pages')->where('legacy_source_id', (int) $legacyPageId)->get()->getRowArray();
            if ($page) return redirect()->to(site_url($page['slug']), 301);
        }
        if (is_string($legacyPostId) && ctype_digit($legacyPostId)) {
            $post = $this->visibleContent('posts')->where('legacy_source_id', (int) $legacyPostId)->get()->getRowArray();
            if ($post) return redirect()->to(site_url('news/' . $post['slug']), 301);
        }
        // The home page carries its own inquiry form, so it shows that form's confirmation and errors inline.
        return view('site/home', $this->base(['isHome' => true, 'inlineFlash' => true, 'hideFooterCta' => true, 'services' => $db->table('services')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray(), 'posts' => $this->visibleContent('posts')->orderBy('published_at', 'DESC')->limit(2)->get()->getResultArray()]));
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
        return $this->pageView($page);
    }
    public function legacyRedirect(string $path)
    {
        return $this->redirectByPath($path);
    }
    public function previewPage(string $slug): string
    {
        $token = (string) $this->request->getGet('token');
        $page = db_connect()->table('pages')->where('slug', $slug)->get()->getRowArray();
        if (! $page || strlen($token) !== 64 || empty($page['preview_token']) || empty($page['preview_token_expires']) || strtotime($page['preview_token_expires']) < time() || ! hash_equals($page['preview_token'], $token)) throw PageNotFoundException::forPageNotFound();
        return $this->pageView($page, true);
    }
    public function previewPost(string $slug): string
    {
        $token = (string) $this->request->getGet('token');
        $post = db_connect()->table('posts')->where('slug', $slug)->get()->getRowArray();
        if (! $post || strlen($token) !== 64 || empty($post['preview_token']) || empty($post['preview_token_expires']) || strtotime($post['preview_token_expires']) < time() || ! hash_equals($post['preview_token'], $token)) throw PageNotFoundException::forPageNotFound();
        $title = $post['seo_title'] ?: ($post['title'] . ' | Fynefield Petroleum');
        $metaDescription = $post['meta_description'] ?: $post['excerpt'];
        $isPreview = true;
        $nextPost = null;
        return view('site/article', $this->base(compact('post', 'title', 'metaDescription', 'isPreview', 'nextPost')));
    }
    public function sitemap()
    {
        $pages = $this->visibleContent('pages')->where('noindex', 0)->select('slug, updated_at')->get()->getResultArray();
        $posts = $this->visibleContent('posts')->where('noindex', 0)->select('slug, updated_at')->get()->getResultArray();
        $urls = [
            ['loc' => site_url('/'), 'lastmod' => null],
            ['loc' => site_url('services'), 'lastmod' => null],
            ['loc' => site_url('news'), 'lastmod' => null],
            ['loc' => site_url('contact-us'), 'lastmod' => null],
        ];
        foreach ($pages as $page) $urls[] = ['loc' => site_url($page['slug']), 'lastmod' => $page['updated_at']];
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
        return $this->response->setContentType('text/plain')->setBody("User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /preview/\n\nSitemap: " . site_url('sitemap.xml') . "\n");
    }
    public function services(): string
    {
        return view('site/services', $this->base(['services' => db_connect()->table('services')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray()]));
    }
    public function service(string $slug): string
    {
        $service = db_connect()->table('services')->where(['slug' => $slug, 'status' => 'published'])->get()->getRowArray();
        if (! $service) throw PageNotFoundException::forPageNotFound();
        return view('site/service', $this->base(compact('service')));
    }
    public function news(): string
    {
        return view('site/news', $this->base(['posts' => $this->visibleContent('posts')->orderBy('published_at', 'DESC')->get()->getResultArray()]));
    }
    public function article(string $slug): string
    {
        $post = $this->visibleContent('posts')->where('slug', $slug)->get()->getRowArray();
        if (! $post) throw PageNotFoundException::forPageNotFound();
        $title = $post['seo_title'] ?: ($post['title'] . ' | Fynefield Petroleum');
        $metaDescription = $post['meta_description'] ?: $post['excerpt'];
        $nextPost = $this->visibleContent('posts')->where('id !=', $post['id'])->orderBy('published_at', 'DESC')->get()->getRowArray();
        return view('site/article', $this->base(compact('post', 'title', 'metaDescription', 'nextPost')));
    }
    public function contact(): string
    {
        // The contact view renders its own success/error states and the design omits the footer CTA band here.
        return view('site/contact', $this->base(['locations' => db_connect()->table('locations')->where('status', 'published')->get()->getResultArray(), 'inlineFlash' => true, 'hideFooterCta' => true]));
    }
    private function notifyInquiry(int $inquiryId, array $inquiry, string $now): void
    {
        $settings = $this->settings();
        $recipient = trim((string) ($settings['inquiry_recipient_email'] ?? ''));
        $sender = trim((string) ($settings['inquiry_sender_email'] ?? ''));
        $db = db_connect();
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $db->table('inquiries')->where('id', $inquiryId)->update(['notification_status' => 'not_configured', 'updated_at' => $now]);
            return;
        }

        try {
            $email = service('email');
            $email->clear();
            $email->setFrom($sender, $settings['site_name'] ?? 'Fynefield Petroleum');
            $email->setTo($recipient);
            $email->setReplyTo($inquiry['email'], $inquiry['name']);
            $email->setSubject('New website inquiry: ' . $inquiry['subject']);
            $email->setMessage("Name: {$inquiry['name']}\nEmail: {$inquiry['email']}\nPhone: {$inquiry['phone']}\n\nMessage:\n{$inquiry['message']}");
            $sent = $email->send(false);
            $db->table('inquiries')->where('id', $inquiryId)->update(['notification_status' => $sent ? 'sent' : 'failed', 'notified_at' => $sent ? $now : null, 'updated_at' => $now]);
            if (! $sent) log_message('error', 'Inquiry notification failed for inquiry {id}.', ['id' => $inquiryId]);
        } catch (\Throwable $exception) {
            $db->table('inquiries')->where('id', $inquiryId)->update(['notification_status' => 'failed', 'updated_at' => $now]);
            log_message('error', 'Inquiry notification exception for inquiry {id}: {message}', ['id' => $inquiryId, 'message' => $exception->getMessage()]);
        }
    }
    public function storeInquiry()
    {
        // This field is visually hidden from people but attractive to basic form bots.
        // Return the normal confirmation so a bot cannot use the response as an oracle.
        // The home page's quick form returns to its own confirmation; every other form returns to the contact page.
        $returnTo = $this->request->getPost('return_to') === 'home' ? site_url('/') . '#inquiry' : '/contact-us';
        if (trim((string) $this->request->getPost('company_website')) !== '') {
            return redirect()->to($returnTo)->with('success', 'Thank you. Your inquiry has been received.')->with('inquiry_reference', sprintf('FP-%s-%04d', date('y'), random_int(1000, 9999)));
        }

        $rules = ['name' => 'required|max_length[160]', 'company' => 'permit_empty|max_length[160]', 'email' => 'required|valid_email|max_length[190]', 'phone' => 'permit_empty|max_length[80]', 'subject' => 'required|max_length[190]', 'message' => 'required|max_length[5000]', 'privacy_consent' => 'required|in_list[1]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());

        $throttler = service('throttler');
        $key = 'contact-inquiry-' . hash('sha256', (string) $this->request->getIPAddress());
        if (! $throttler->check($key, 3, 600)) {
            return redirect()->back()->withInput()->with('error', 'Please wait a few minutes before sending another inquiry.');
        }

        $now = date('Y-m-d H:i:s');
        $fields = ['name', 'email', 'phone', 'subject', 'message'];
        $inquiry = $this->request->getPost($fields);
        // Inquiries have no company column, so the optional company is recorded at the top of the message.
        $company = trim((string) $this->request->getPost('company'));
        if ($company !== '') $inquiry['message'] = "Company: {$company}\n\n" . $inquiry['message'];
        $db = db_connect();
        $db->table('inquiries')->insert($inquiry + ['status' => 'new', 'consented_at' => $now, 'notification_status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
        $inquiryId = (int) $db->insertID();
        $this->notifyInquiry($inquiryId, $inquiry, $now);
        return redirect()->to($returnTo)->with('success', 'Thank you. Your inquiry has been received.')->with('inquiry_reference', sprintf('FP-%s-%04d', date('y'), $inquiryId));
    }
}
