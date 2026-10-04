<?php

namespace App\Models;

use CodeIgniter\Model;

class SaleItemModel extends Model
{
    protected $table = 'sale_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'sale_id',
        'product_id',
        'product_name_snapshot',
        'cost_price_at_sale',
        'quantity',
        'price',
        'subtotal',
        'discount_applied',
        'profit'
    ];
}