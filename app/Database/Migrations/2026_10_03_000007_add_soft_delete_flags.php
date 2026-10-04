<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeleteFlags extends Migration
{
    private const TABLES = ['products', 'branch_products', 'categories', 'suppliers', 'discounts'];

    public function up(): void
    {
        $this->db->resetDataCache();
        foreach (self::TABLES as $table) {
            if (!$this->db->fieldExists('deleted_at', $table)) {
                $this->forge->addColumn($table, ['deleted_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null]]);
                $this->db->resetDataCache();
            }
            if (!$this->db->fieldExists('is_deleted', $table)) {
                $this->forge->addColumn($table, ['is_deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0]]);
                $this->db->resetDataCache();
            }
            $this->db->table($table)->where('deleted_at IS NOT NULL', null, false)->update(['is_deleted' => 1]);
        }
    }

    public function down(): void
    {
        // Preserve deletion state in the older timestamp-based application before dropping flags.
        foreach (self::TABLES as $table) {
            if ($this->db->fieldExists('is_deleted', $table)) {
                $this->db->table($table)->where('is_deleted', 1)->where('deleted_at', null)
                    ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                $this->forge->dropColumn($table, 'is_deleted');
                $this->db->resetDataCache();
            }
        }
    }
}
