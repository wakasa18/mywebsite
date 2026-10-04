<?php

namespace App\Models;


class ProductModel extends RetainedRecordModel
{
    protected $table      = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'product_name',
        'category_id',
        'sku',
        'unit',
        'cost_price',
        'price',
        'stock',
        'reorder_level',
        'status',
        'manufacturer',
        'supplier_id',
    ];

    // Timestamps
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Soft delete
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
}
