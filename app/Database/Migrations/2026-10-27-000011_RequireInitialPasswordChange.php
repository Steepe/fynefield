<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RequireInitialPasswordChange extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'role'],
        ]);
        $users = $this->db->table('users')->select('id,password_hash')->get()->getResultArray();
        foreach ($users as $user) if (password_verify('ChangeMe!2026', $user['password_hash'])) $this->db->table('users')->where('id', $user['id'])->update(['must_change_password' => 1]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'must_change_password');
    }
}
