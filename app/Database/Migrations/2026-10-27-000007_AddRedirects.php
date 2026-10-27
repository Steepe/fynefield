<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRedirects extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'source_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'target_url' => ['type' => 'VARCHAR', 'constraint' => 500],
            'http_code' => ['type' => 'SMALLINT', 'constraint' => 3, 'default' => 301],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('source_path');
        $this->forge->createTable('redirects');
    }

    public function down()
    {
        $this->forge->dropTable('redirects');
    }
}
