<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageBuilderAndWorkflow extends Migration
{
    public function up()
    {
        foreach (['pages', 'posts'] as $table) {
            $this->forge->addColumn($table, [
                'review_notes' => ['type' => 'TEXT', 'null' => true, 'after' => 'status'],
                'submitted_for_review_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'review_notes'],
                'approved_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'submitted_for_review_at'],
                'approved_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'approved_at'],
                'preview_token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'approved_by'],
                'preview_token_expires' => ['type' => 'DATETIME', 'null' => true, 'after' => 'preview_token'],
            ]);
        }
        $this->forge->addColumn('pages', [
            'template' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'standard', 'after' => 'cover_image'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'page_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'section_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'eyebrow' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'heading' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'content' => ['type' => 'LONGTEXT', 'null' => true],
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'button_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'button_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'items' => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['page_id', 'sort_order']);
        $this->forge->createTable('page_sections');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 120],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'entity_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'details' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('activity_log');
    }

    public function down()
    {
        $this->forge->dropTable('activity_log', true);
        $this->forge->dropTable('page_sections', true);
        $this->forge->dropColumn('pages', 'template');
        foreach (['pages', 'posts'] as $table) $this->forge->dropColumn($table, ['review_notes', 'submitted_for_review_at', 'approved_at', 'approved_by', 'preview_token', 'preview_token_expires']);
    }
}
