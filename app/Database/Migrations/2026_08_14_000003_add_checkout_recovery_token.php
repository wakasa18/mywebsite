<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a non-reversible hash of the checkout request token to each sale.
 * This lets the POS safely confirm whether a sale completed when the
 * browser loses its connection before receiving the success page.
 */
class AddCheckoutRecoveryToken extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('checkout_token_hash', 'sales')) {
            $this->forge->addColumn('sales', [
                'checkout_token_hash' => [
                    'type'       => 'CHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'invoice_no',
                    'comment'    => 'SHA-256 hash used for safe checkout recovery',
                ],
            ]);
        }

        $indexes = $this->db->getIndexData('sales');
        if (!isset($indexes['uq_sales_checkout_token_hash'])) {
            $this->db->query(
                'ALTER TABLE `sales` ADD UNIQUE KEY `uq_sales_checkout_token_hash` (`checkout_token_hash`)'
            );
        }
    }

    public function down(): void
    {
        $indexes = $this->db->getIndexData('sales');
        if (isset($indexes['uq_sales_checkout_token_hash'])) {
            $this->db->query('ALTER TABLE `sales` DROP INDEX `uq_sales_checkout_token_hash`');
        }

        if ($this->db->fieldExists('checkout_token_hash', 'sales')) {
            $this->forge->dropColumn('sales', 'checkout_token_hash');
        }
    }
}
