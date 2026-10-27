<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Admin extends BaseController
{
    private array $entities = [
        'pages' => ['label' => 'Pages', 'fields' => ['title' => 'text', 'slug' => 'text', 'template' => 'template', 'excerpt' => 'textarea', 'content' => 'richtext', 'cover_image' => 'text', 'sort_order' => 'number', 'status' => 'status', 'published_at' => 'datetime', 'review_notes' => 'textarea', 'seo_title' => 'text', 'meta_description' => 'textarea', 'canonical_url' => 'url', 'noindex' => 'checkbox']],
        'posts' => ['label' => 'News', 'fields' => ['title' => 'text', 'slug' => 'text', 'excerpt' => 'textarea', 'content' => 'richtext', 'cover_image' => 'text', 'sort_order' => 'number', 'status' => 'status', 'published_at' => 'datetime', 'review_notes' => 'textarea', 'seo_title' => 'text', 'meta_description' => 'textarea', 'canonical_url' => 'url', 'noindex' => 'checkbox']],
        'services' => ['label' => 'Services', 'fields' => ['title' => 'text', 'slug' => 'text', 'summary' => 'textarea', 'content' => 'richtext', 'image' => 'text', 'sort_order' => 'number', 'status' => 'status']],
        'team_members' => ['label' => 'Team', 'fields' => ['name' => 'text', 'position' => 'text', 'bio' => 'textarea', 'image' => 'text', 'sort_order' => 'number', 'status' => 'status']],
        'locations' => ['label' => 'Locations', 'fields' => ['name' => 'text', 'address' => 'textarea', 'city' => 'text', 'state' => 'text', 'phone' => 'text', 'latitude' => 'text', 'longitude' => 'text', 'status' => 'status']],
        'inquiries' => ['label' => 'Inquiries', 'fields' => ['name' => 'text', 'email' => 'text', 'phone' => 'text', 'subject' => 'text', 'message' => 'textarea', 'status' => 'inquiry_status', 'notification_status' => 'readonly', 'notified_at' => 'readonly', 'internal_notes' => 'textarea']],
        'redirects' => ['label' => 'Redirects', 'admin_only' => true, 'fields' => ['source_path' => 'text', 'target_url' => 'url', 'http_code' => 'redirect_code', 'is_active' => 'checkbox']],
    ];
    private function entity(string $entity): array { if (! isset($this->entities[$entity]) || (($this->entities[$entity]['admin_only'] ?? false) && session('admin_role') !== 'admin')) throw PageNotFoundException::forPageNotFound(); return $this->entities[$entity]; }
    private function data(array $data = []): array { return $data + ['entities' => $this->entities, 'adminName' => session('admin_name'), 'adminRole' => session('admin_role'), 'mustChangePassword' => (bool) session('must_change_password')]; }
    private function requireAdministrator()
    {
        if (session('admin_role') !== 'admin') return redirect()->to('/admin')->with('error', 'Administrator access is required.');
        return null;
    }
    private function cleanHtml(string $html): string
    {
        // Rich text comes from the editor, but it is still untrusted input. Keep a
        // deliberate, small HTML vocabulary and reconstruct every allowed opening tag.
        $html = preg_replace('#<\\s*(script|style|iframe|object|embed)\\b[^>]*>.*?</\\s*\\1\\s*>#is', '', $html) ?? '';
        $html = strip_tags($html, '<p><div><br><strong><em><s><del><ul><ol><li><h1><h2><h3><h4><h5><h6><a><blockquote><pre><code><figure><figcaption><img>');

        return preg_replace_callback('/<([a-z][a-z0-9]*)\\b([^>]*)>/i', static function (array $match): string {
            $tag = strtolower($match[1]);
            $attributes = $match[2];

            if ($tag === 'a') {
                preg_match('/href\\s*=\\s*(["\\\'])(.*?)\\1/i', $attributes, $href);
                $url = trim($href[2] ?? '');
                return preg_match('#^(https?://|/|mailto:)#i', $url) ? '<a href="' . esc($url, 'attr') . '">' : '<a>';
            }

            if ($tag === 'img') {
                preg_match('/src\\s*=\\s*(["\\\'])(.*?)\\1/i', $attributes, $src);
                preg_match('/alt\\s*=\\s*(["\\\'])(.*?)\\1/i', $attributes, $alt);
                $url = trim($src[2] ?? '');
                // Editorial images may come from the managed media areas or HTTPS.
                if (! preg_match('#^(?:/uploads/|/assets/media/|https://)#i', $url)) return '';
                return '<img src="' . esc($url, 'attr') . '" alt="' . esc(trim($alt[2] ?? ''), 'attr') . '">';
            }

            return '<' . $tag . '>';
        }, $html) ?? '';
    }
    private function editorMedia(): array
    {
        $media = db_connect()->table('media')->orderBy('id', 'DESC')->get()->getResultArray();
        $knownPaths = array_column($media, 'path');
        $legacyFiles = glob(FCPATH . 'assets/media/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        foreach ($legacyFiles as $file) {
            $path = '/assets/media/' . basename($file);
            if (! in_array($path, $knownPaths, true)) $media[] = ['filename' => basename($file), 'path' => $path, 'mime_type' => mime_content_type($file) ?: 'image/*', 'alt_text' => ''];
        }
        return $media;
    }
    private function recordActivity(string $action, ?string $entity = null, ?int $id = null, ?string $details = null): void
    {
        db_connect()->table('activity_log')->insert(['user_id' => session('admin_id'), 'action' => $action, 'entity_type' => $entity, 'entity_id' => $id, 'details' => $details, 'created_at' => date('Y-m-d H:i:s')]);
    }
    private function pageOrFail(int $pageId): array
    {
        $page = db_connect()->table('pages')->where('id', $pageId)->get()->getRowArray();
        if (! $page || (session('admin_role') !== 'admin' && ! in_array($page['status'], ['draft', 'in_review'], true))) throw PageNotFoundException::forPageNotFound();
        return $page;
    }
    private function safeMediaPath(string $path): string
    {
        $path = trim($path);
        return preg_match('#^(?:/uploads/|/assets/media/|https://)#i', $path) ? $path : '';
    }
    private function safeCsvValue(mixed $value): string
    {
        $value = (string) $value;
        // Spreadsheet applications may treat cells starting with a formula
        // character as executable content. Preserve the visible value as text.
        return preg_match('/^\s*[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
    public function dashboard(): string
    {
        $db = db_connect(); $counts = [];
        foreach (['pages', 'posts', 'services', 'team_members', 'locations', 'inquiries'] as $table) $counts[$table] = $db->table($table)->countAllResults();
        $recentActivity = $db->table('activity_log')->select('activity_log.*, users.name AS user_name')->join('users', 'users.id = activity_log.user_id', 'left')->orderBy('activity_log.id', 'DESC')->limit(8)->get()->getResultArray();
        return view('admin/dashboard', $this->data(compact('counts', 'recentActivity')));
    }
    public function index(string $entity): string
    {
        $meta = $this->entity($entity); $builder = db_connect()->table($entity);
        $search = trim((string) $this->request->getGet('q')); $status = trim((string) $this->request->getGet('status'));
        if ($search !== '') { $column = $entity === 'inquiries' ? 'name' : ($entity === 'team_members' || $entity === 'locations' ? 'name' : 'title'); $builder->like($column, $search); }
        if ($status !== '' && $entity !== 'inquiries') $builder->where('status', $status);
        $rows = $builder->orderBy($entity === 'pages' || $entity === 'services' || $entity === 'team_members' ? 'sort_order' : 'updated_at', $entity === 'pages' || $entity === 'services' || $entity === 'team_members' ? 'ASC' : 'DESC')->get()->getResultArray();
        return view('admin/index', $this->data(compact('entity', 'meta', 'rows', 'search', 'status')));
    }
    public function create(string $entity): string { $meta = $this->entity($entity); $row = []; $editorMedia = $this->editorMedia(); return view('admin/form', $this->data(compact('entity', 'meta', 'row', 'editorMedia'))); }
    public function edit(string $entity, int $id)
    {
        $meta = $this->entity($entity); $row = db_connect()->table($entity)->where('id', $id)->get()->getRowArray();
        if (! $row) throw PageNotFoundException::forPageNotFound();
        if (session('admin_role') !== 'admin' && in_array($entity, ['pages', 'posts'], true) && ! in_array($row['status'], ['draft', 'in_review'], true)) return redirect()->to('/admin/' . $entity)->with('error', 'Live and scheduled content is available to administrators for approval and publishing.');
        $editorMedia = $this->editorMedia(); return view('admin/form', $this->data(compact('entity', 'meta', 'row', 'editorMedia')));
    }
    public function save(string $entity, ?int $id = null)
    {
        $isNew = $id === null; $meta = $this->entity($entity); $db = db_connect(); $current = $id ? $db->table($entity)->where('id', $id)->get()->getRowArray() : null;
        if ($id && ! $current) throw PageNotFoundException::forPageNotFound();
        $fields = array_keys($meta['fields']); $data = $this->request->getPost($fields) ?? [];
        foreach (['content'] as $html) if (isset($data[$html])) $data[$html] = $this->cleanHtml((string) $data[$html]);
        foreach ($data as $key => $value) if (is_string($value)) $data[$key] = trim($value);
        foreach ($meta['fields'] as $field => $type) if ($type === 'checkbox') $data[$field] = (int) ($this->request->getPost($field) === '1');
        if ($entity === 'redirects') {
            $data['source_path'] = '/' . trim((string) ($data['source_path'] ?? ''), '/');
            $data['target_url'] = trim((string) ($data['target_url'] ?? ''));
            $validSource = preg_match('~^/(?!/)[^\s?#]*$~', $data['source_path']) === 1;
            $validTarget = preg_match('#^(?:/(?!/)|https?://)#i', $data['target_url']) === 1;
            if (! $validSource || ! $validTarget || ! in_array((int) ($data['http_code'] ?? 301), [301, 302], true)) return redirect()->back()->withInput()->with('error', 'Use a site-relative source path, a site-relative or HTTPS target, and a 301 or 302 status.');
            $duplicate = $db->table('redirects')->where('source_path', $data['source_path']); if ($id) $duplicate->where('id !=', $id);
            if ($duplicate->countAllResults() > 0) return redirect()->back()->withInput()->with('error', 'A redirect already exists for that source path.');
        }
        if (in_array($entity, ['pages', 'posts', 'services'], true) && empty($data['slug'])) $data['slug'] = url_title((string) ($data['title'] ?? ''), '-', true);
        if (isset($data['slug']) && $data['slug'] !== '') {
            $duplicate = $db->table($entity)->where('slug', $data['slug']); if ($id) $duplicate->where('id !=', $id);
            if ($duplicate->countAllResults() > 0) return redirect()->back()->withInput()->with('error', 'That URL slug is already in use.');
        }
        if (in_array($entity, ['pages', 'posts'], true)) {
            if (session('admin_role') !== 'admin' && (($current && ! in_array($current['status'], ['draft', 'in_review'], true)) || ! in_array($data['status'] ?? '', ['draft', 'in_review'], true))) return redirect()->to('/admin/' . $entity)->with('error', 'Editors can save drafts and submit content for review; an administrator publishes or schedules live content.');
            $data['noindex'] = (int) ($this->request->getPost('noindex') === '1');
            $data['template'] = $entity === 'pages' && in_array($data['template'] ?? '', ['standard', 'landing'], true) ? $data['template'] : ($entity === 'pages' ? 'standard' : null);
            if ($entity !== 'pages') unset($data['template']);
            if (! in_array($data['status'] ?? '', ['draft', 'in_review', 'scheduled', 'published'], true)) $data['status'] = 'draft';
            if (! empty($data['published_at'])) $data['published_at'] = str_replace('T', ' ', $data['published_at']);
            if (($data['status'] ?? '') === 'scheduled') {
                if (empty($data['published_at']) || strtotime((string) $data['published_at']) <= time()) return redirect()->back()->withInput()->with('error', 'Scheduled content needs a future publish date and time.');
            }
            if (($data['status'] ?? '') === 'in_review') $data['submitted_for_review_at'] = date('Y-m-d H:i:s');
            if (($data['status'] ?? '') === 'published') {
                $data['published_at'] = $data['published_at'] ?: date('Y-m-d H:i:s');
                $data['approved_at'] = date('Y-m-d H:i:s');
                $data['approved_by'] = session('admin_id');
            }
            if (in_array($entity, ['pages', 'posts'], true) && ! $id) {
                $data['preview_token'] = bin2hex(random_bytes(32));
                $data['preview_token_expires'] = date('Y-m-d H:i:s', strtotime('+7 days'));
            }
        }
        if (empty($data['status']) && ! in_array($entity, ['inquiries', 'redirects'], true)) $data['status'] = 'draft';
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id) {
            if ($current && $entity !== 'inquiries') $db->table('content_revisions')->insert(['entity_type' => $entity, 'entity_id' => $id, 'snapshot' => json_encode($current, JSON_THROW_ON_ERROR), 'editor_id' => session('admin_id'), 'created_at' => date('Y-m-d H:i:s')]);
            $db->table($entity)->where('id', $id)->update($data);
        } else { $data['created_at'] = date('Y-m-d H:i:s'); $db->table($entity)->insert($data); $id = (int) $db->insertID(); }
        $this->recordActivity($isNew ? 'created' : 'saved', $entity, $id, $data['status'] ?? null);
        if ($isNew && $entity === 'pages') return redirect()->to('/admin/pages/' . $id . '/sections')->with('success', 'Page created. Add and arrange its sections.');
        return redirect()->to('/admin/' . $entity)->with('success', $meta['label'] . ' saved.');
    }
    public function revisions(string $entity, int $id): string
    {
        $meta = $this->entity($entity); $row = db_connect()->table($entity)->where('id', $id)->get()->getRowArray();
        if (! $row) throw PageNotFoundException::forPageNotFound();
        $revisions = db_connect()->table('content_revisions')->where(['entity_type' => $entity, 'entity_id' => $id])->orderBy('id', 'DESC')->get()->getResultArray();
        return view('admin/revisions', $this->data(compact('entity', 'meta', 'row', 'revisions')));
    }
    public function restoreRevision(string $entity, int $id, int $revisionId)
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $this->entity($entity); $db = db_connect();
        $revision = $db->table('content_revisions')->where(['id' => $revisionId, 'entity_type' => $entity, 'entity_id' => $id])->get()->getRowArray();
        $current = $db->table($entity)->where('id', $id)->get()->getRowArray();
        if (! $revision || ! $current) throw PageNotFoundException::forPageNotFound();
        $db->table('content_revisions')->insert(['entity_type' => $entity, 'entity_id' => $id, 'snapshot' => json_encode($current, JSON_THROW_ON_ERROR), 'editor_id' => session('admin_id'), 'created_at' => date('Y-m-d H:i:s')]);
        $snapshot = json_decode($revision['snapshot'], true, 512, JSON_THROW_ON_ERROR); unset($snapshot['id'], $snapshot['created_at'], $snapshot['updated_at']); $snapshot['updated_at'] = date('Y-m-d H:i:s');
        $db->table($entity)->where('id', $id)->update($snapshot);
        return redirect()->to('/admin/' . $entity . '/' . $id . '/edit')->with('success', 'Revision restored.');
    }
    public function delete(string $entity, int $id)
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $meta = $this->entity($entity); db_connect()->table($entity)->where('id', $id)->delete(); $this->recordActivity('deleted', $entity, $id);
        return redirect()->to('/admin/' . $entity)->with('success', $meta['label'] . ' deleted.');
    }
    public function pageSections(int $pageId): string
    {
        $page = $this->pageOrFail($pageId);
        $sections = db_connect()->table('page_sections')->where('page_id', $pageId)->orderBy('sort_order')->orderBy('id')->get()->getResultArray();
        return view('admin/page_sections', $this->data(compact('page', 'sections')));
    }
    public function sectionForm(int $pageId, ?int $sectionId = null): string
    {
        $page = $this->pageOrFail($pageId); $section = [];
        if ($sectionId) {
            $section = db_connect()->table('page_sections')->where(['id' => $sectionId, 'page_id' => $pageId])->get()->getRowArray();
            if (! $section) throw PageNotFoundException::forPageNotFound();
        }
        $editorMedia = $this->editorMedia();
        $defaultSectionType = (string) $this->request->getGet('type');
        if (! in_array($defaultSectionType, ['hero', 'rich_text', 'image_text', 'call_to_action', 'stat_grid', 'gallery', 'faq', 'trust_strip', 'testimonial_carousel'], true)) $defaultSectionType = 'rich_text';
        return view('admin/section_form', $this->data(compact('page', 'section', 'editorMedia', 'defaultSectionType')));
    }
    public function saveSection(int $pageId, ?int $sectionId = null)
    {
        $this->pageOrFail($pageId); $db = db_connect();
        $data = $this->request->getPost(['section_type', 'eyebrow', 'heading', 'content', 'image', 'button_label', 'button_url', 'items', 'sort_order']) ?? [];
        if (! in_array($data['section_type'] ?? '', ['hero', 'rich_text', 'image_text', 'call_to_action', 'stat_grid', 'gallery', 'faq', 'trust_strip', 'testimonial_carousel'], true)) return redirect()->back()->withInput()->with('error', 'Choose a valid section type.');
        foreach ($data as $key => $value) if (is_string($value)) $data[$key] = trim($value);
        $data['content'] = $this->cleanHtml((string) ($data['content'] ?? ''));
        $data['image'] = $this->safeMediaPath((string) ($data['image'] ?? ''));
        $data['button_url'] = trim((string) ($data['button_url'] ?? ''));
        if ($data['button_url'] !== '' && ! preg_match('#^(?:https?://|/(?!/))#i', $data['button_url'])) return redirect()->back()->withInput()->with('error', 'Buttons must link to an absolute HTTPS/HTTP URL or a site-relative path.');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0); $data['page_id'] = $pageId; $data['updated_at'] = date('Y-m-d H:i:s');
        if ($sectionId) {
            $exists = $db->table('page_sections')->where(['id' => $sectionId, 'page_id' => $pageId])->countAllResults();
            if (! $exists) throw PageNotFoundException::forPageNotFound();
            $db->table('page_sections')->where('id', $sectionId)->update($data);
        } else { $data['created_at'] = date('Y-m-d H:i:s'); $db->table('page_sections')->insert($data); $sectionId = (int) $db->insertID(); }
        $this->recordActivity('saved section', 'pages', $pageId, ($data['section_type'] ?? 'section') . ':' . $sectionId);
        return redirect()->to('/admin/pages/' . $pageId . '/sections')->with('success', 'Section saved.');
    }
    public function deleteSection(int $pageId, int $sectionId)
    {
        $this->pageOrFail($pageId); db_connect()->table('page_sections')->where(['id' => $sectionId, 'page_id' => $pageId])->delete();
        $this->recordActivity('deleted section', 'pages', $pageId, (string) $sectionId);
        return redirect()->to('/admin/pages/' . $pageId . '/sections')->with('success', 'Section removed.');
    }
    public function duplicateSection(int $pageId, int $sectionId)
    {
        $this->pageOrFail($pageId); $db = db_connect();
        $section = $db->table('page_sections')->where(['id' => $sectionId, 'page_id' => $pageId])->get()->getRowArray();
        if (! $section) throw PageNotFoundException::forPageNotFound();
        unset($section['id'], $section['created_at'], $section['updated_at']);
        $section['sort_order'] = ((int) ($db->table('page_sections')->selectMax('sort_order')->where('page_id', $pageId)->get()->getRow()->sort_order ?? 0)) + 10;
        $section['created_at'] = date('Y-m-d H:i:s'); $section['updated_at'] = date('Y-m-d H:i:s');
        $db->table('page_sections')->insert($section);
        $this->recordActivity('duplicated section', 'pages', $pageId, (string) $sectionId);
        return redirect()->to('/admin/pages/' . $pageId . '/sections')->with('success', 'Section duplicated. Drag it into position.');
    }
    public function reorderSections(int $pageId)
    {
        $this->pageOrFail($pageId); $order = json_decode((string) $this->request->getPost('order'), true);
        if (! is_array($order) || ! $order) return $this->response->setStatusCode(422)->setJSON(['error' => 'A section order is required.']);
        $order = array_values(array_unique(array_map('intval', $order)));
        $sections = db_connect()->table('page_sections')->select('id')->where('page_id', $pageId)->get()->getResultArray();
        $known = array_map(static fn (array $section): int => (int) $section['id'], $sections);
        sort($known); $submitted = $order; sort($submitted);
        if ($known !== $submitted) return $this->response->setStatusCode(422)->setJSON(['error' => 'The submitted order does not match this page.']);
        $db = db_connect(); $db->transStart();
        foreach ($order as $position => $sectionId) $db->table('page_sections')->where(['id' => $sectionId, 'page_id' => $pageId])->update(['sort_order' => ($position + 1) * 10, 'updated_at' => date('Y-m-d H:i:s')]);
        $db->transComplete();
        $this->recordActivity('reordered sections', 'pages', $pageId);
        return $this->response->setJSON(['ok' => true, 'csrfHash' => csrf_hash()]);
    }
    public function refreshPreview(int $pageId)
    {
        $page = $this->pageOrFail($pageId); $token = bin2hex(random_bytes(32));
        db_connect()->table('pages')->where('id', $pageId)->update(['preview_token' => $token, 'preview_token_expires' => date('Y-m-d H:i:s', strtotime('+7 days')), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->recordActivity('refreshed preview', 'pages', $pageId);
        return redirect()->to('/admin/pages/' . $pageId . '/edit')->with('success', 'A fresh private preview link is ready.');
    }
    public function refreshPostPreview(int $postId)
    {
        $post = db_connect()->table('posts')->where('id', $postId)->get()->getRowArray();
        if (! $post || (session('admin_role') !== 'admin' && ! in_array($post['status'], ['draft', 'in_review'], true))) throw PageNotFoundException::forPageNotFound();
        $token = bin2hex(random_bytes(32));
        db_connect()->table('posts')->where('id', $postId)->update(['preview_token' => $token, 'preview_token_expires' => date('Y-m-d H:i:s', strtotime('+7 days')), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->recordActivity('refreshed preview', 'posts', $postId);
        return redirect()->to('/admin/posts/' . $postId . '/edit')->with('success', 'A fresh private preview link is ready.');
    }
    public function settings()
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $rows = db_connect()->table('settings')->get()->getResultArray(); $settings = array_column($rows, 'setting_value', 'setting_key');
        return view('admin/settings', $this->data(compact('settings')));
    }
    public function saveSettings()
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $keys = ['site_name', 'site_tagline', 'contact_email', 'contact_phone', 'address', 'footer_text', 'logo', 'inquiry_recipient_email', 'inquiry_sender_email'];
        $values = $this->request->getPost($keys);
        foreach (['inquiry_recipient_email', 'inquiry_sender_email'] as $emailKey) if (! empty($values[$emailKey]) && ! filter_var($values[$emailKey], FILTER_VALIDATE_EMAIL)) return redirect()->back()->withInput()->with('error', 'Notification sender and recipient addresses must be valid email addresses.');
        $db = db_connect(); foreach ($values as $key => $value) $db->table('settings')->replace(['setting_key' => $key, 'setting_value' => trim((string) $value), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->recordActivity('updated site settings');
        return redirect()->to('/admin/settings')->with('success', 'Site settings saved.');
    }
    public function media(): string { $media = db_connect()->table('media')->orderBy('id', 'DESC')->get()->getResultArray(); return view('admin/media', $this->data(compact('media'))); }
    public function upload()
    {
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid() || $file->hasMoved() || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) return redirect()->back()->with('error', 'Upload a valid JPG, PNG, or WebP image.');
        if ($file->getSize() > 10 * 1024 * 1024) return redirect()->back()->with('error', 'Images must be 10 MB or smaller.');
        $dimensions = @getimagesize($file->getTempName());
        if (! $dimensions || $dimensions[0] < 1 || $dimensions[1] < 1 || ((int) $dimensions[0] * (int) $dimensions[1]) > 40_000_000) return redirect()->back()->with('error', 'Images must be valid and no larger than 40 megapixels.');
        $altText = trim((string) $this->request->getPost('alt_text'));
        if (mb_strlen($altText) > 255) return redirect()->back()->withInput()->with('error', 'Alternative text cannot exceed 255 characters.');
        $name = $file->getRandomName(); $file->move(FCPATH . 'uploads', $name);
        db_connect()->table('media')->insert(['filename' => $file->getClientName(), 'path' => '/uploads/' . $name, 'mime_type' => $file->getMimeType(), 'alt_text' => $altText ?: null, 'created_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/admin/media')->with('success', 'Image uploaded.');
    }
    public function saveMediaAlt(int $id)
    {
        $altText = trim((string) $this->request->getPost('alt_text'));
        if (mb_strlen($altText) > 255) return redirect()->back()->withInput()->with('error', 'Alternative text cannot exceed 255 characters.');
        $media = db_connect()->table('media')->where('id', $id)->get()->getRowArray();
        if (! $media) throw PageNotFoundException::forPageNotFound();
        db_connect()->table('media')->where('id', $id)->update(['alt_text' => $altText ?: null]);
        $this->recordActivity('updated image alternative text', 'media', $id);
        return redirect()->to('/admin/media')->with('success', 'Image alternative text saved.');
    }
    public function navigation()
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $items = db_connect()->table('navigation_items')->orderBy('sort_order')->get()->getResultArray();
        return view('admin/navigation', $this->data(compact('items')));
    }
    public function saveNavigation(?int $id = null)
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $data = $this->request->getPost(['label', 'url', 'sort_order', 'is_active']) ?? [];
        $data['label'] = trim((string) ($data['label'] ?? '')); $data['url'] = trim((string) ($data['url'] ?? ''));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0); $data['is_active'] = (int) ($this->request->getPost('is_active') === '1');
        if ($data['label'] === '' || ! preg_match('#^(?:https?://|/(?!/))#', $data['url'])) return redirect()->back()->withInput()->with('error', 'Provide a label and a valid absolute or site-relative URL.');
        $data['updated_at'] = date('Y-m-d H:i:s'); $db = db_connect();
        if ($id) $db->table('navigation_items')->where('id', $id)->update($data); else $db->table('navigation_items')->insert($data + ['created_at' => date('Y-m-d H:i:s')]);
        $this->recordActivity($id ? 'updated navigation' : 'created navigation', 'navigation_items', $id);
        return redirect()->to('/admin/navigation')->with('success', 'Navigation saved.');
    }
    public function deleteNavigation(int $id)
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        db_connect()->table('navigation_items')->where('id', $id)->delete();
        $this->recordActivity('deleted navigation', 'navigation_items', $id);
        return redirect()->to('/admin/navigation')->with('success', 'Navigation item removed.');
    }
    public function exportInquiries()
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $rows = db_connect()->table('inquiries')->select('name,email,phone,subject,message,status,notification_status,notified_at,consented_at,created_at,updated_at,internal_notes')->orderBy('id', 'DESC')->get()->getResultArray();
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Name', 'Email', 'Phone', 'Subject', 'Message', 'Lead status', 'Notification status', 'Notified at', 'Consented at', 'Received at', 'Updated at', 'Internal notes']);
        foreach ($rows as $row) fputcsv($stream, array_map(fn ($value): string => $this->safeCsvValue($value), array_values($row)));
        rewind($stream); $csv = stream_get_contents($stream); fclose($stream);
        $this->recordActivity('exported inquiries');
        return $this->response->setContentType('text/csv', 'UTF-8')->setHeader('Content-Disposition', 'attachment; filename="fynefield-inquiries-' . date('Y-m-d') . '.csv"')->setBody($csv);
    }
    public function users()
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $users = db_connect()->table('users')->select('id,name,email,role,is_active,created_at,updated_at')->orderBy('name')->get()->getResultArray();
        return view('admin/users', $this->data(compact('users')));
    }
    public function saveUser(?int $id = null)
    {
        if ($redirect = $this->requireAdministrator()) return $redirect;
        $db = db_connect(); $current = $id ? $db->table('users')->where('id', $id)->get()->getRowArray() : null;
        if ($id && ! $current) throw PageNotFoundException::forPageNotFound();
        $data = $this->request->getPost(['name', 'email', 'role']) ?? [];
        $data['name'] = trim((string) ($data['name'] ?? '')); $data['email'] = strtolower(trim((string) ($data['email'] ?? ''))); $data['role'] = in_array($data['role'] ?? '', ['admin', 'editor'], true) ? $data['role'] : 'editor';
        $data['is_active'] = (int) ($this->request->getPost('is_active') === '1');
        if ($data['name'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return redirect()->back()->withInput()->with('error', 'Provide a name and valid email address.');
        $duplicate = $db->table('users')->where('email', $data['email']); if ($id) $duplicate->where('id !=', $id); if ($duplicate->countAllResults()) return redirect()->back()->withInput()->with('error', 'That email is already in use.');
        if ($id === (int) session('admin_id') && ($data['role'] !== 'admin' || ! $data['is_active'])) return redirect()->back()->withInput()->with('error', 'You cannot remove your own administrator access or deactivate your current account.');
        if ($current && $current['role'] === 'admin' && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            $otherAdministrators = $db->table('users')->where('role', 'admin')->where('is_active', 1)->where('id !=', $id)->countAllResults();
            if ($otherAdministrators === 0) return redirect()->back()->withInput()->with('error', 'Keep at least one active administrator account.');
        }
        $password = (string) $this->request->getPost('password'); if (! $id && strlen($password) < 12) return redirect()->back()->withInput()->with('error', 'New accounts require a password of at least 12 characters.');
        if ($password !== '') { if (strlen($password) < 12) return redirect()->back()->withInput()->with('error', 'Passwords must have at least 12 characters.'); $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT); $data['must_change_password'] = 1; }
        $data['updated_at'] = date('Y-m-d H:i:s'); if ($id) $db->table('users')->where('id', $id)->update($data); else $db->table('users')->insert($data + ['created_at' => date('Y-m-d H:i:s')]);
        $this->recordActivity($id ? 'updated user' : 'created user', 'users', $id ?: (int) $db->insertID(), $data['role'] . ($data['is_active'] ? ':active' : ':inactive'));
        return redirect()->to('/admin/users')->with('success', 'User saved.');
    }
    public function profile(): string
    {
        $user = db_connect()->table('users')->where('id', session('admin_id'))->get()->getRowArray();
        return view('admin/profile', $this->data(compact('user')));
    }
    public function saveProfile()
    {
        $db = db_connect(); $user = $db->table('users')->where('id', session('admin_id'))->get()->getRowArray();
        $name = trim((string) $this->request->getPost('name')); $password = (string) $this->request->getPost('password');
        if ($name === '') return redirect()->back()->withInput()->with('error', 'Your name is required.');
        if ($user['must_change_password'] && $password === '') return redirect()->back()->withInput()->with('error', 'You must set a new password before continuing.');
        $data = ['name' => $name, 'updated_at' => date('Y-m-d H:i:s')];
        if ($password !== '') { if (strlen($password) < 12) return redirect()->back()->withInput()->with('error', 'Passwords must have at least 12 characters.'); $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT); $data['must_change_password'] = 0; }
        $db->table('users')->where('id', $user['id'])->update($data); session()->set(['admin_name' => $name, 'must_change_password' => false]);
        return redirect()->to('/admin/profile')->with('success', 'Profile updated.');
    }
}
