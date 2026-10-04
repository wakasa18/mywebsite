<?php

namespace App\Models;


class BranchProductModel extends RetainedRecordModel
{
    protected $table = 'branch_products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'branch_id',
        'product_id',
        'stock',
        'reorder_level',
        'price',
        'cost_price',
        'expiration_date',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = false;
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
}
