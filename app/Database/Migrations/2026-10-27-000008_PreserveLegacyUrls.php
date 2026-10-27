<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PreserveLegacyUrls extends Migration
{
    public function up()
    {
        $this->forge->addColumn('posts', [
            'legacy_source_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);
        $this->db->query('ALTER TABLE posts ADD UNIQUE KEY posts_legacy_source_id_unique (legacy_source_id)');

        $now = date('Y-m-d H:i:s');
        $this->db->table('posts')->where('slug', 'fynefield-petroleum-recognized-at-otl')->update(['legacy_source_id' => 1184]);
        $this->db->table('posts')->where('slug', 'state-of-the-art-petroleum-depot')->update(['legacy_source_id' => 1241]);
        $this->db->table('redirects')->insertBatch([
            ['source_path' => '/fynefield-petroleum-company-recognized-for-sponsorship-and-customer-engagement-at-oil-trading-and-logistics-expo-otl', 'target_url' => '/news/fynefield-petroleum-recognized-at-otl', 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['source_path' => '/fynefield-petroleum-unveils-state-of-the-art-petroleum-depot', 'target_url' => '/news/state-of-the-art-petroleum-depot', 'http_code' => 301, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down()
    {
        $this->db->table('redirects')->whereIn('source_path', [
            '/fynefield-petroleum-company-recognized-for-sponsorship-and-customer-engagement-at-oil-trading-and-logistics-expo-otl',
            '/fynefield-petroleum-unveils-state-of-the-art-petroleum-depot',
        ])->delete();
        $this->db->query('ALTER TABLE posts DROP INDEX posts_legacy_source_id_unique');
        $this->forge->dropColumn('posts', 'legacy_source_id');
    }
}
