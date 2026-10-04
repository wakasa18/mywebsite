<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermanentDeleteFlags extends Migration
{
    private const TABLES = ['products', 'branch_products', 'categories', 'suppliers', 'discounts'];

    public function up(): void
    {
        $this->db->resetDataCache();
        foreach (self::TABLES as $table) {
            if (!$this->db->fieldExists('is_permanently_deleted', $table)) {
                $this->forge->addColumn($table, ['is_permanently_deleted'=>['type'=>'TINYINT', 'constraint'=>1, 'null'=>false, 'default'=>0]]);
                $this->db->resetDataCache();
                // Backfill once. Later manual flag resets must survive migration reruns.
                $this->db->table($table)->where('permanently_deleted_at IS NOT NULL', null, false)
                    ->update(['is_permanently_deleted'=>1]);
            }
        }
    }

    public function down(): void
    {
        throw new \LogicException('Keep the permanent-deletion flag when rolling back: older timestamp rules would hide recovered records again.');
    }
}
