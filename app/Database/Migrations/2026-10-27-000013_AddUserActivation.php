<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserActivation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'must_change_password'],
        ]);
        $this->db->table('users')->update(['is_active' => 1]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'is_active');
    }
}
