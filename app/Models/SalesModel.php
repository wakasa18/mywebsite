<?php

namespace App\Models;

use CodeIgniter\Model;

class SalesModel extends Model
{
    protected $table = 'sales';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'invoice_no',
        'checkout_token_hash',
        'user_id',
        'branch_id',
        'discount_id',
        'total_amount',
        'discount_amount',
        'final_total',
        'payment_method',
        'reference_no',
        'amount_paid',
        'change_amount',
        'status',
        'notes',
        'sale_date',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;
}
