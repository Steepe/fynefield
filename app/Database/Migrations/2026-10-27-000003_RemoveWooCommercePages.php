<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveWooCommercePages extends Migration
{
    public function up()
    {
        $this->db->table('pages')->whereIn('legacy_source_id', [9, 10, 11, 12, 13])->delete();
    }

    public function down()
    {
        // The page seed deliberately omits these obsolete WooCommerce pages.
    }
}
