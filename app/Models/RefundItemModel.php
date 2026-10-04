<?php

namespace App\Models;

use CodeIgniter\Model;

class RefundItemModel extends Model
{
    protected $table      = 'refund_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'sale_id',
        'sale_item_id',
        'product_id',
        'product_name_snapshot',
        'quantity_refunded',
        'price_at_sale',
        'refund_subtotal',
        'refunded_by',
        'reason',
        'return_condition',
        'refund_method',
        'refund_event_id',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Returns the total quantity already refunded for a given sale_item_id
     * across all past refund events.
     */
    public function totalRefundedQty(int $saleItemId): int
    {
        $row = $this->selectSum('quantity_refunded', 'total')
            ->where('sale_item_id', $saleItemId)
            ->first();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Returns all refund events for a sale, joined with the user who processed them.
     */
    public function getRefundHistory(int $saleId): array
    {
        return $this
            ->select('refund_items.*, users.full_name AS refunded_by_name')
            ->join('users', 'users.id = refund_items.refunded_by', 'left')
            ->where('refund_items.sale_id', $saleId)
            ->orderBy('refund_items.id', 'ASC')
            ->findAll();
    }
}
