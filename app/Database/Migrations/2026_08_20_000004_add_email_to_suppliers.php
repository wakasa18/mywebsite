<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds an optional email address to supplier records.
 *
 * Run with: php spark migrate
 */
class AddEmailToSuppliers extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('suppliers') || $this->db->fieldExists('email', 'suppliers')) {
            return;
        }

        $this->forge->addColumn('suppliers', [
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'after'      => 'contact_number',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->tableExists('suppliers') && $this->db->fieldExists('email', 'suppliers')) {
            $this->forge->dropColumn('suppliers', 'email');
        }
    }
}
