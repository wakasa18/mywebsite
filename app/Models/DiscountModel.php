<?php

namespace App\Models;


class DiscountModel extends RetainedRecordModel
{
    protected $table      = 'discounts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'discount_name',
        'discount_type',
        'discount_value',
        'description',
        'applies_to',
        'category_id',
        'product_id',
        'start_date',
        'end_date',
        'minimum_purchase',
        'max_discount_amount',
        'status',
    ];

    // Timestamps (already exist in DB via DEFAULT CURRENT_TIMESTAMP)
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Soft delete
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
}
