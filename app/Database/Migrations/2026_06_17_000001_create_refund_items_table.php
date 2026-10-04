<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the refund_items table for tracking partial refunds.
 *
 * A single sale can have multiple partial refunds over time.
 * Each row records one item's refunded quantity from one refund event.
 *
 * Run with: php spark migrate
 */
class CreateRefundItemsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // Which sale this refund belongs to
            'sale_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            // The original sale_items row being partially refunded
            'sale_item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'product_name_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            // How many units were refunded in this event
            'quantity_refunded' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'price_at_sale' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            // quantity_refunded * price_at_sale
            'refund_subtotal' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'refunded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'users.id of the staff member who processed this refund',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('sale_id');
        $this->forge->addKey('sale_item_id');
        $this->forge->createTable('refund_items', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('refund_items', true);
    }
}
