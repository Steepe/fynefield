<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLegacySourceToPages extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pages', [
            'legacy_source_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);
        $this->db->query('ALTER TABLE pages ADD UNIQUE KEY pages_legacy_source_id_unique (legacy_source_id)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE pages DROP INDEX pages_legacy_source_id_unique');
        $this->forge->dropColumn('pages', 'legacy_source_id');
    }
}
