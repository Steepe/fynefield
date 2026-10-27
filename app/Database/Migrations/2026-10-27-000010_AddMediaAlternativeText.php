<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMediaAlternativeText extends Migration
{
    public function up()
    {
        $this->forge->addColumn('media', [
            'alt_text' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'mime_type'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('media', 'alt_text');
    }
}
