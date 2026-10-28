<?php

namespace App\Controllers;

/**
 * The signed-in user's own account page. Everything else in the back office is
 * the Studio; this page stays separate because a forced password change must be
 * reachable before the Studio opens.
 */
class Admin extends BaseController
{
    public function profile(): string
    {
        $user = db_connect()->table('users')->where('id', session('admin_id'))->get()->getRowArray();
        return view('admin/profile', ['user' => $user, 'mustChangePassword' => (bool) session('must_change_password')]);
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
        return redirect()->to($user['must_change_password'] ? '/admin' : '/admin/profile')->with('success', 'Profile updated.');
    }
}
