<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login(): string { return view('admin/login'); }
    public function attempt()
    {
        $email = strtolower((string) $this->request->getPost('email'));
        $user = db_connect()->table('users')->where('email', $email)->get()->getRowArray();
        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])) return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        session()->regenerate();
        session()->set(['admin_id' => $user['id'], 'admin_name' => $user['name'], 'admin_role' => $user['role']]);
        return redirect()->to('/admin');
    }
    public function logout() { session()->destroy(); return redirect()->to('/admin/login')->with('success', 'You have been signed out.'); }
}
