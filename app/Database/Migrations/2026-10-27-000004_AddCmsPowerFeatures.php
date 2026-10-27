<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCmsPowerFeatures extends Migration
{
    public function up()
    {
        foreach (['pages', 'posts'] as $table) {
            $this->forge->addColumn($table, [
                'seo_title' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'title'],
                'meta_description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'excerpt'],
                'canonical_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'cover_image'],
                'noindex' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'status'],
                'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'noindex'],
            ]);
        }
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'label' => ['type' => 'VARCHAR', 'constraint' => 120],
            'url' => ['type' => 'VARCHAR', 'constraint' => 255],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('navigation_items');
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'entity_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'snapshot' => ['type' => 'LONGTEXT'],
            'editor_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('content_revisions');
    }

    public function down()
    {
        $this->forge->dropTable('content_revisions', true);
        $this->forge->dropTable('navigation_items', true);
        foreach (['pages', 'posts'] as $table) $this->forge->dropColumn($table, ['seo_title', 'meta_description', 'canonical_url', 'noindex', 'sort_order']);
    }
}
