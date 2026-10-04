<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPermanentDeleteMarkers extends Migration
{
    private const TABLES = ['products', 'branch_products', 'categories', 'suppliers', 'discounts'];

    public function up(): void
    {
        $this->db->resetDataCache();
        foreach (self::TABLES as $table) {
            if (!$this->db->fieldExists('permanently_deleted_at', $table)) {
                $this->forge->addColumn($table, ['permanently_deleted_at'=>['type'=>'DATETIME', 'null'=>true, 'default'=>null]]);
                $this->db->resetDataCache();
            }
        }
    }

    public function down(): void
    {
        // Dropping this marker would make permanently hidden records restorable in older code.
        throw new \LogicException('This retention migration cannot be rolled back automatically. Permanently hidden records must stay hidden.');
    }
}
