<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\App;
use Config\Email;
use Config\Encryption;

class CmsPreflight extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:preflight';
    protected $description = 'Checks non-secret production readiness settings for the Fynefield CMS.';

    public function run(array $params)
    {
        $issues = [];
        $app = config(App::class);
        $email = config(Email::class);
        $encryption = config(Encryption::class);
        $settings = array_column(db_connect()->table('settings')->get()->getResultArray(), 'setting_value', 'setting_key');

        if (ENVIRONMENT !== 'production') $issues[] = 'CI_ENVIRONMENT is not production.';
        if (! str_starts_with($app->baseURL, 'https://') || str_contains($app->baseURL, 'fynefield.test')) $issues[] = 'app.baseURL is not a production HTTPS domain.';
        if (strlen($encryption->key) < 32) $issues[] = 'A production encryption key is missing or too short.';
        if ($email->protocol !== 'smtp' || ! $email->SMTPHost || ! $email->SMTPUser || ! $email->SMTPPass) $issues[] = 'SMTP is not fully configured in the environment.';
        if (! filter_var($settings['inquiry_recipient_email'] ?? '', FILTER_VALIDATE_EMAIL) || ! filter_var($settings['inquiry_sender_email'] ?? '', FILTER_VALIDATE_EMAIL)) $issues[] = 'CMS inquiry sender and recipient addresses are not both configured.';

        $users = db_connect()->table('users')->select('password_hash')->get()->getResultArray();
        foreach ($users as $user) {
            if (password_verify('ChangeMe!2026', $user['password_hash'])) {
                $issues[] = 'An account still uses the initial administrator password.';
                break;
            }
        }
        if (db_connect()->table('migrations')->where('namespace', 'App')->countAllResults() < 13) $issues[] = 'Not all application migrations have been applied.';

        if ($issues) {
            CLI::error('Preflight found ' . count($issues) . ' item(s) requiring attention:');
            foreach ($issues as $issue) CLI::write('- ' . $issue, 'yellow');
            return EXIT_ERROR;
        }

        CLI::write('CMS production preflight passed.', 'green');
        return EXIT_SUCCESS;
    }
}
