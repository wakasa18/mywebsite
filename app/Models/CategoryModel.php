<?php

namespace App\Models;


class CategoryModel extends RetainedRecordModel
{
    protected $table      = 'categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'category_name',
    ];

    // Timestamps
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Soft delete
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
}
