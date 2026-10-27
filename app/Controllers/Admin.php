<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Admin extends BaseController
{
    private array $entities = [
        'pages' => ['label' => 'Pages', 'fields' => ['title' => 'text', 'slug' => 'text', 'excerpt' => 'textarea', 'content' => 'richtext', 'cover_image' => 'text', 'status' => 'status', 'published_at' => 'datetime']],
        'posts' => ['label' => 'News', 'fields' => ['title' => 'text', 'slug' => 'text', 'excerpt' => 'textarea', 'content' => 'richtext', 'cover_image' => 'text', 'status' => 'status', 'published_at' => 'datetime']],
        'services' => ['label' => 'Services', 'fields' => ['title' => 'text', 'slug' => 'text', 'summary' => 'textarea', 'content' => 'richtext', 'image' => 'text', 'sort_order' => 'number', 'status' => 'status']],
        'team_members' => ['label' => 'Team', 'fields' => ['name' => 'text', 'position' => 'text', 'bio' => 'textarea', 'image' => 'text', 'sort_order' => 'number', 'status' => 'status']],
        'locations' => ['label' => 'Locations', 'fields' => ['name' => 'text', 'address' => 'textarea', 'city' => 'text', 'state' => 'text', 'phone' => 'text', 'latitude' => 'text', 'longitude' => 'text', 'status' => 'status']],
        'inquiries' => ['label' => 'Inquiries', 'fields' => ['name' => 'text', 'email' => 'text', 'phone' => 'text', 'subject' => 'text', 'message' => 'textarea', 'status' => 'inquiry_status']],
    ];
    private function entity(string $entity): array { if (! isset($this->entities[$entity])) throw PageNotFoundException::forPageNotFound(); return $this->entities[$entity]; }
    private function data(array $data = []): array { return $data + ['entities' => $this->entities, 'adminName' => session('admin_name')]; }
    private function cleanHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><h2><h3><h4><a><blockquote>');
        $html = preg_replace_callback('/<a\\b[^>]*>/i', static function (array $match): string {
            preg_match('/href\\s*=\\s*["\\\']([^"\\\']+)["\\\']/i', $match[0], $href);
            $url = $href[1] ?? '';
            return preg_match('#^(https?://|/|mailto:)#i', $url) ? '<a href="' . esc($url, 'attr') . '">' : '<a>';
        }, $html) ?? '';
        return preg_replace('/<(?!a\\b)([a-z0-9]+)\\b[^>]*>/i', '<$1>', $html) ?? '';
    }
    public function dashboard(): string
    {
        $db = db_connect(); $counts = [];
        foreach (['pages', 'posts', 'services', 'team_members', 'locations', 'inquiries'] as $table) $counts[$table] = $db->table($table)->countAllResults();
        return view('admin/dashboard', $this->data(compact('counts')));
    }
    public function index(string $entity): string
    {
        $meta = $this->entity($entity); $rows = db_connect()->table($entity)->orderBy('updated_at', 'DESC')->get()->getResultArray();
        return view('admin/index', $this->data(compact('entity', 'meta', 'rows')));
    }
    public function create(string $entity): string { $meta = $this->entity($entity); $row = []; return view('admin/form', $this->data(compact('entity', 'meta', 'row'))); }
    public function edit(string $entity, int $id): string
    {
        $meta = $this->entity($entity); $row = db_connect()->table($entity)->where('id', $id)->get()->getRowArray();
        if (! $row) throw PageNotFoundException::forPageNotFound(); return view('admin/form', $this->data(compact('entity', 'meta', 'row')));
    }
    public function save(string $entity, ?int $id = null)
    {
        $meta = $this->entity($entity); $db = db_connect(); $fields = array_keys($meta['fields']); $data = $this->request->getPost($fields) ?? [];
        foreach (['content'] as $html) if (isset($data[$html])) $data[$html] = $this->cleanHtml((string) $data[$html]);
        foreach ($data as $key => $value) if (is_string($value)) $data[$key] = trim($value);
        if (in_array($entity, ['pages', 'posts', 'services'], true) && empty($data['slug'])) $data['slug'] = url_title((string) ($data['title'] ?? ''), '-', true);
        if (isset($data['slug']) && $data['slug'] !== '') {
            $duplicate = $db->table($entity)->where('slug', $data['slug']); if ($id) $duplicate->where('id !=', $id);
            if ($duplicate->countAllResults() > 0) return redirect()->back()->withInput()->with('error', 'That URL slug is already in use.');
        }
        if (empty($data['status']) && $entity !== 'inquiries') $data['status'] = 'draft';
        if (in_array($entity, ['pages', 'posts'], true) && ($data['status'] ?? '') === 'published' && empty($data['published_at'])) $data['published_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($id) $db->table($entity)->where('id', $id)->update($data); else { $data['created_at'] = date('Y-m-d H:i:s'); $db->table($entity)->insert($data); }
        return redirect()->to('/admin/' . $entity)->with('success', $meta['label'] . ' saved.');
    }
    public function delete(string $entity, int $id)
    {
        $meta = $this->entity($entity); db_connect()->table($entity)->where('id', $id)->delete();
        return redirect()->to('/admin/' . $entity)->with('success', $meta['label'] . ' deleted.');
    }
    public function settings(): string
    {
        $rows = db_connect()->table('settings')->get()->getResultArray(); $settings = array_column($rows, 'setting_value', 'setting_key');
        return view('admin/settings', $this->data(compact('settings')));
    }
    public function saveSettings()
    {
        $db = db_connect(); foreach ($this->request->getPost(['site_name', 'site_tagline', 'contact_email', 'contact_phone', 'address', 'footer_text', 'logo']) as $key => $value) $db->table('settings')->replace(['setting_key' => $key, 'setting_value' => trim((string) $value), 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/admin/settings')->with('success', 'Site settings saved.');
    }
    public function media(): string { $media = db_connect()->table('media')->orderBy('id', 'DESC')->get()->getResultArray(); return view('admin/media', $this->data(compact('media'))); }
    public function upload()
    {
        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid() || $file->hasMoved() || ! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) return redirect()->back()->with('error', 'Upload a valid JPG, PNG, or WebP image.');
        $name = $file->getRandomName(); $file->move(FCPATH . 'uploads', $name);
        db_connect()->table('media')->insert(['filename' => $file->getClientName(), 'path' => '/uploads/' . $name, 'mime_type' => $file->getMimeType(), 'created_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/admin/media')->with('success', 'Image uploaded.');
    }
}
