<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBranchProductTrash extends Migration
{
    public function up(): void
    {
        $this->db->resetDataCache();
        if (!$this->db->fieldExists('deleted_at', 'branch_products')) {
            $this->forge->addColumn('branch_products', ['deleted_at'=>['type'=>'DATETIME', 'null'=>true, 'default'=>null]]);
            $this->db->resetDataCache();
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('deleted_at', 'branch_products')) {
            $this->forge->dropColumn('branch_products', 'deleted_at');
        }
    }
}
