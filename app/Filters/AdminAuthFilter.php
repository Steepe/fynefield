<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');
        $isApi = str_starts_with($path, 'admin/api/');
        if (! session()->get('admin_id')) {
            if ($isApi) return service('response')->setStatusCode(401)->setJSON(['error' => 'Your session has ended. Sign in again.']);
            return redirect()->to('/admin/login')->with('error', 'Please sign in to access the CMS.');
        }
        $db = db_connect();
        $user = $db->table('users')->select('is_active, role, last_active_at')->where('id', session('admin_id'))->get()->getRowArray();
        if (! $user || ! $user['is_active']) {
            session()->destroy();
            if ($isApi) return service('response')->setStatusCode(401)->setJSON(['error' => 'Your CMS account is no longer active.']);
            return redirect()->to('/admin/login')->with('error', 'Your CMS account is no longer active.');
        }
        // Role changes made in Users & roles take effect on the next request, not the next sign-in.
        session()->set('admin_role', $user['role']);
        if (! $user['last_active_at'] || strtotime($user['last_active_at']) < time() - 300) $db->table('users')->where('id', session('admin_id'))->update(['last_active_at' => date('Y-m-d H:i:s')]);
        if (session('must_change_password') && ! in_array($path, ['admin/profile', 'admin/logout'], true)) {
            if ($isApi) return service('response')->setStatusCode(403)->setJSON(['error' => 'Set a new password before continuing.']);
            return redirect()->to('/admin/profile')->with('error', 'Set a new password before continuing to the CMS.');
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
