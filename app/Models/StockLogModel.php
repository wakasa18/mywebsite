<?php

namespace App\Models;

use CodeIgniter\Model;

class StockLogModel extends Model
{
    protected $table = 'stock_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'product_id',
        'branch_id',
        'user_id',
        'action_type',
        'quantity',
        'previous_stock',
        'new_stock',
        'remarks',
        'supplier_id',
        'manufacturer',
        'created_at',
    ];

    protected $useTimestamps = false;
}