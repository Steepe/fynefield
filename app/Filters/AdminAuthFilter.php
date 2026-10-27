<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('admin_id')) return redirect()->to('/admin/login')->with('error', 'Please sign in to access the CMS.');
        $user = db_connect()->table('users')->select('is_active')->where('id', session('admin_id'))->get()->getRowArray();
        if (! $user || ! $user['is_active']) {
            session()->destroy();
            return redirect()->to('/admin/login')->with('error', 'Your CMS account is no longer active.');
        }
        $path = trim($request->getUri()->getPath(), '/');
        if (session('must_change_password') && ! in_array($path, ['admin/profile', 'admin/logout'], true)) return redirect()->to('/admin/profile')->with('error', 'Set a new password before continuing to the CMS.');
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
