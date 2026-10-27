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
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
