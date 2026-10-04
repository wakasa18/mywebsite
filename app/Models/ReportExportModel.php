<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportExportModel extends Model
{
    protected $table = 'report_exports';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'branch_id',
        'export_type',
        'date_from',
        'date_to',
        'gross_sales',
        'net_sales',
        'total_discount',
        'cogs',
        'gross_profit',
        'total_transactions',
        'created_at',
    ];

    protected $useTimestamps = false;
}