<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Site extends BaseController
{
    private function settings(): array
    {
        $rows = db_connect()->table('settings')->get()->getResultArray();
        return array_column($rows, 'setting_value', 'setting_key');
    }
    private function base(array $data = []): array { return $data + ['settings' => $this->settings()]; }
    public function home(): string
    {
        $db = db_connect();
        return view('site/home', $this->base(['services' => $db->table('services')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray(), 'posts' => $db->table('posts')->where('status', 'published')->orderBy('published_at', 'DESC')->limit(2)->get()->getResultArray()]));
    }
    public function page(string $slug): string
    {
        $page = db_connect()->table('pages')->where(['slug' => $slug, 'status' => 'published'])->get()->getRowArray();
        if (! $page) throw PageNotFoundException::forPageNotFound();
        $team = $slug === 'management-team' ? db_connect()->table('team_members')->where('status', 'published')->orderBy('sort_order')->get()->getResultArray() : [];
        return view('site/page', $this->base(compact('page', 'team')));
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
        return view('site/news', $this->base(['posts' => db_connect()->table('posts')->where('status', 'published')->orderBy('published_at', 'DESC')->get()->getResultArray()]));
    }
    public function article(string $slug): string
    {
        $post = db_connect()->table('posts')->where(['slug' => $slug, 'status' => 'published'])->get()->getRowArray();
        if (! $post) throw PageNotFoundException::forPageNotFound();
        return view('site/article', $this->base(compact('post')));
    }
    public function contact(): string
    {
        return view('site/contact', $this->base(['locations' => db_connect()->table('locations')->where('status', 'published')->get()->getResultArray()]));
    }
    public function storeInquiry()
    {
        $rules = ['name' => 'required|max_length[160]', 'email' => 'required|valid_email|max_length[190]', 'phone' => 'permit_empty|max_length[80]', 'subject' => 'required|max_length[190]', 'message' => 'required|max_length[5000]'];
        if (! $this->validate($rules)) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        db_connect()->table('inquiries')->insert($this->request->getPost(array_keys($rules)) + ['status' => 'new', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/contact-us')->with('success', 'Thank you. Your inquiry has been received.');
    }
}
