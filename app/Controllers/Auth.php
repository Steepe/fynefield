<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login(): string { return view('admin/login'); }

    public function forgotPassword(): string { return view('admin/forgot_password'); }

    private function resetRequestResponse()
    {
        return redirect()->to('/admin/login')->with('success', 'If that account can receive email, a password reset link has been sent.');
    }

    private function canSendResetEmail(): bool
    {
        $email = config('Email');
        if (! filter_var($email->fromEmail, FILTER_VALIDATE_EMAIL)) return false;
        if ($email->protocol !== 'smtp') return true;
        return $email->SMTPHost !== '' && $email->SMTPUser !== '' && $email->SMTPPass !== '';
    }

    public function sendPasswordReset()
    {
        $emailAddress = strtolower(trim((string) $this->request->getPost('email')));
        $throttler = service('throttler');
        $key = 'admin-password-reset-' . hash('sha256', (string) $this->request->getIPAddress());
        if (! $throttler->check($key, 3, 900) || ! filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) return $this->resetRequestResponse();

        $db = db_connect();
        $user = $db->table('users')->where('email', $emailAddress)->where('is_active', 1)->get()->getRowArray();
        if (! $user || ! $this->canSendResetEmail()) return $this->resetRequestResponse();

        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        $tokenHash = hash('sha256', $token);
        $db->transStart();
        $db->table('password_resets')->where(['user_id' => $user['id'], 'used_at' => null])->update(['used_at' => $now]);
        $db->table('password_resets')->insert([
            'user_id' => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'requested_ip' => (string) $this->request->getIPAddress(),
            'created_at' => $now,
        ]);
        $db->transComplete();

        $sent = false;
        try {
            $resetUrl = site_url('admin/reset-password') . '?token=' . rawurlencode($token);
            $mailer = service('email');
            $mailer->clear();
            $mailer->setFrom(config('Email')->fromEmail, config('Email')->fromName ?: 'Fynefield CMS');
            $mailer->setTo($user['email']);
            $mailer->setSubject('Reset your Fynefield CMS password');
            $mailer->setMessage("A password reset was requested for your Fynefield CMS account.\n\nUse this one-time link within one hour:\n{$resetUrl}\n\nIf you did not request this, you can ignore this email.");
            $sent = $mailer->send(false);
        } catch (\Throwable $exception) {
            log_message('error', 'Password reset email exception for user {id}: {message}', ['id' => $user['id'], 'message' => $exception->getMessage()]);
        }

        if (! $sent) {
            $db->table('password_resets')->where('token_hash', $tokenHash)->update(['used_at' => date('Y-m-d H:i:s')]);
            log_message('error', 'Password reset email failed for user {id}.', ['id' => $user['id']]);
            return $this->resetRequestResponse();
        }

        $db->table('activity_log')->insert(['user_id' => $user['id'], 'action' => 'requested password reset', 'created_at' => $now]);
        return $this->resetRequestResponse();
    }

    public function resetPassword()
    {
        $token = (string) $this->request->getGet('token');
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) return redirect()->to('/admin/login')->with('error', 'That password reset link is invalid or has expired.');
        return view('admin/reset_password', ['token' => $token]);
    }

    public function completePasswordReset()
    {
        $token = (string) $this->request->getPost('token');
        $password = (string) $this->request->getPost('password');
        $confirmation = (string) $this->request->getPost('password_confirmation');
        $returnToForm = static fn (string $message) => redirect()->to('/admin/reset-password?token=' . rawurlencode($token))->with('error', $message);
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) return redirect()->to('/admin/login')->with('error', 'That password reset link is invalid or has expired.');
        if (strlen($password) < 12) return $returnToForm('Passwords must have at least 12 characters.');
        if (! hash_equals($password, $confirmation)) return $returnToForm('The password confirmation does not match.');

        $throttler = service('throttler');
        $key = 'admin-password-reset-complete-' . hash('sha256', (string) $this->request->getIPAddress());
        if (! $throttler->check($key, 10, 900)) return $returnToForm('Too many reset attempts. Please wait 15 minutes and try again.');

        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $tokenHash = hash('sha256', $token);
        $db->transStart();
        $reset = $db->table('password_resets')->where('token_hash', $tokenHash)->where('used_at', null)->where('expires_at >', $now)->get()->getRowArray();
        if ($reset) {
            $db->table('password_resets')->where(['id' => $reset['id'], 'used_at' => null])->update(['used_at' => $now]);
            if ($db->affectedRows() === 1) {
                $db->table('users')->where('id', $reset['user_id'])->where('is_active', 1)->update(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'must_change_password' => 0, 'updated_at' => $now]);
                if ($db->affectedRows() === 1) $db->table('activity_log')->insert(['user_id' => $reset['user_id'], 'action' => 'completed password reset', 'created_at' => $now]); else $reset = null;
            } else $reset = null;
        }
        $db->transComplete();
        if (! $reset) return redirect()->to('/admin/login')->with('error', 'That password reset link is invalid or has expired.');

        session()->destroy();
        return redirect()->to('/admin/login')->with('success', 'Your password has been reset. You can now sign in.');
    }

    public function attempt()
    {
        $email = strtolower((string) $this->request->getPost('email'));
        $throttler = service('throttler');
        $key = 'admin-login-' . hash('sha256', (string) $this->request->getIPAddress() . '|' . $email);
        if (! $throttler->check($key, 5, 900)) return redirect()->back()->withInput()->with('error', 'Too many sign-in attempts. Please wait 15 minutes and try again.');
        $user = db_connect()->table('users')->where('email', $email)->where('is_active', 1)->get()->getRowArray();
        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])) return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        session()->regenerate();
        session()->set(['admin_id' => $user['id'], 'admin_name' => $user['name'], 'admin_role' => $user['role'], 'must_change_password' => (bool) ($user['must_change_password'] ?? false)]);
        db_connect()->table('activity_log')->insert(['user_id' => $user['id'], 'action' => 'signed in', 'created_at' => date('Y-m-d H:i:s')]);
        return redirect()->to(session('must_change_password') ? '/admin/profile' : '/admin');
    }
    public function logout() { session()->destroy(); return redirect()->to('/admin/login')->with('success', 'You have been signed out.'); }
}
