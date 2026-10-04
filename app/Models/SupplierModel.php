<?php

namespace App\Models;


class SupplierModel extends RetainedRecordModel
{
    protected $table      = 'suppliers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'supplier_name',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'status',
    ];

    // Timestamps
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Soft delete
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
}
