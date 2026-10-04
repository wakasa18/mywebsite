<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLogicSafeguards extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('session_version', 'users')) {
            $this->forge->addColumn('users', [
                'session_version' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            ]);
        }
        foreach ([
            'return_condition' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'resellable'],
            'refund_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'refund_event_id' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
        ] as $name => $definition) {
            if (!$this->db->fieldExists($name, 'refund_items')) {
                $this->forge->addColumn('refund_items', [$name => $definition]);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('refund_items', ['return_condition', 'refund_method', 'refund_event_id']);
        $this->forge->dropColumn('users', 'session_version');
    }
}
