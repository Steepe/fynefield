<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenInquiries extends Migration
{
    public function up()
    {
        $this->forge->addColumn('inquiries', [
            'consented_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
            'internal_notes' => ['type' => 'TEXT', 'null' => true, 'after' => 'consented_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('inquiries', ['consented_at', 'internal_notes']);
    }
}
