<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInquiryNotifications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('inquiries', [
            'notification_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'not_configured', 'after' => 'internal_notes'],
            'notified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'notification_status'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('inquiries', ['notification_status', 'notified_at']);
    }
}
