<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PurgeExpiredPasswordResets extends BaseCommand
{
    protected $group = 'CMS';
    protected $name = 'cms:purge-expired-password-resets';
    protected $description = 'Permanently removes expired CMS password-reset tokens.';

    public function run(array $params)
    {
        $deleted = db_connect()->table('password_resets')->where('expires_at <', date('Y-m-d H:i:s'))->delete();
        CLI::write("Removed {$deleted} expired password-reset token(s).", 'green');
        return EXIT_SUCCESS;
    }
}
