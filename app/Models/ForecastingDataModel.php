<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stores product snapshots from scheduled forecasts and explicit manual
 * updates, plus revenue points with NULL product/quantity. The date field
 * holds the target period start for revenue points. Manual rows include
 * the selected basis in method_used; ordinary
 * report reads do not create or change saved records.
 */
class ForecastingDataModel extends Model
{
    protected $table = 'forecasting_data';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'product_id',
        'forecast_month',
        'predicted_quantity',
        'predicted_revenue',
        'method_used',
        'generated_at',
    ];

    protected $useTimestamps = false;
}
