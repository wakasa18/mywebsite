<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchModel extends Model
{
    protected $table = 'branches';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'branch_name',
        'branch_code',
        'address',
        'contact_number',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;
}