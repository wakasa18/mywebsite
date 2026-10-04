<?php

namespace App\Libraries;

use App\Models\ForecastingDataModel;
use Config\Database;

/**
 * Generates per-product reorder forecasts: which low-stock products are
 * projected to run out soonest, and how much to order.
 *
 * Used from two places that need the exact same logic:
 *  - Admin\ReportsController, when someone loads the Reports page
 *  - Commands\GenerateReorderForecast, the `php spark forecast:reorder`
 *    CLI command — for reliable daily generation that doesn't depend on
 *    anyone actually opening the Reports page that day (see that command
 *    for how to schedule it via cron).
 *
 * The Reports page always calculates a fresh result from its selected date
 * range and branch. Explicit Update Forecast submissions save a manual
 * snapshot with that basis; the CLI keeps a separate daily store-wide snapshot.
 */
class ReorderForecastService
{
    /**
     * Sale statuses that represent real, counted revenue — kept identical
     * to ReportsController::REVENUE_STATUSES so the two forecasts agree on
     * what counts as a sale.
     */
    private const REVENUE_STATUSES = ['completed', 'partially_refunded'];

    /**
     * How many days of forecasted demand a suggested reorder quantity
     * should cover. There's no per-supplier lead-time field in the schema
     * to base this on, so it's a flat, clearly-labeled assumption rather
     * than a precise reorder-point calculation.
     */
    public const REORDER_COVERAGE_DAYS = 30;

    private $db;
    private $forecastingDataModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->forecastingDataModel = new ForecastingDataModel();
    }

    private function refundedQtyJoinSql(): string
    {
        return '(SELECT sale_item_id, SUM(quantity_refunded) AS qty_refunded, '
            . 'SUM(refund_subtotal) AS refunded_subtotal '
            . 'FROM refund_items GROUP BY sale_item_id) rq';
    }

    /**
     * The Holt's Linear Trend math. Intentionally duplicated from
     * ReportsController::holtSmooth() (same algorithm, same output shape)
     * rather than sharing a dependency between the controller and this
     * service — it's a small, pure function, and this keeps the revenue
     * forecast's already-working code path completely untouched.
     */
    private function holtSmooth(array $values, float $alpha, float $beta): array
    {
        $fitted = [];
        $level = 0.0;
        $trend = 0.0;

        if (!empty($values)) {
            $level = $values[0];
            $trend = count($values) > 1 ? ($values[1] - $values[0]) : 0;

            $fitted[0] = $values[0];

            for ($i = 1; $i < count($values); $i++) {
                $fitted[$i] = $level + $trend;

                $previousLevel = $level;
                $level = ($alpha * $values[$i]) + ((1 - $alpha) * ($level + $trend));
                $trend = ($beta * ($level - $previousLevel)) + ((1 - $beta) * $trend);
            }
        }

        return ['fitted' => $fitted, 'level' => $level, 'trend' => $trend];
    }

    /**
     * Low-stock products (stock <= reorder_level, same definition the
     * Dashboard's low-stock widget uses) ranked by how soon each is
     * projected to run out, with a suggested reorder quantity.
     *
     * $dateFrom/$dateTo control the historical window used to fit each
     * product's demand trend — the CLI command defaults this to a fixed
     * trailing window since it runs with no page-supplied date range.
     * $limit lets small UI surfaces request forecasts for exactly the rows
     * they display while keeping the normal Reports-page cap unchanged.
     */
    public function generate(
        string $dateFrom,
        string $dateTo,
        string $branchId,
        float $alpha,
        float $beta,
        bool $persistSnapshot = false,
        ?int $limit = 60
    ): array {
        $lowStockBuilder = $this->db->table('branch_products bp')
            ->select('
                bp.product_id, bp.branch_id, bp.stock, bp.reorder_level, bp.price,
                p.product_name, p.sku, b.branch_name
            ')
            ->join('products p', 'p.id = bp.product_id')
            ->join('branches b', 'b.id = bp.branch_id')
            ->where('p.status', 'active')
            ->where('p.deleted_at IS NULL', null, false)->where('p.is_deleted', 0)->where('p.is_permanently_deleted', 0)
            ->where('bp.status', 'active')
            ->where('bp.deleted_at', null)->where('bp.is_deleted', 0)->where('bp.is_permanently_deleted', 0)
            ->where('b.status', 'active')
            ->where('bp.stock <= bp.reorder_level', null, false);

        if ($branchId !== '') {
            $lowStockBuilder->where('bp.branch_id', (int) $branchId);
        }

        $lowStockRows = $lowStockBuilder
            ->orderBy('(bp.stock / GREATEST(bp.reorder_level, 1))', 'ASC', false)
            ->get()
            ->getResultArray();

        if (empty($lowStockRows)) {
            return [];
        }

        $productIds = array_values(array_unique(array_map(
            static fn ($row) => (int) $row['product_id'],
            $lowStockRows
        )));

        $start = $dateFrom . ' 00:00:00';
        $end = $dateTo . ' 23:59:59';
        $statusList = "'" . implode("','", self::REVENUE_STATUSES) . "'";
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        // Demand is grouped by BOTH product and branch. The previous query
        // grouped only by product, which caused a branch report to borrow
        // demand from sales made at other branches.
        $branchClause = '';
        $binds = array_merge([$start, $end], $productIds);
        if ($branchId !== '') {
            $branchClause = 'AND s.branch_id = ?';
            $binds[] = (int) $branchId;
        }

        $sql = "
            SELECT
                si.product_id,
                s.branch_id,
                DATE(s.sale_date) AS sale_day,
                SUM(si.quantity - COALESCE(rq.qty_refunded, 0)) AS qty_sold
            FROM sale_items si
            JOIN sales s ON s.id = si.sale_id
            LEFT JOIN {$this->refundedQtyJoinSql()} ON rq.sale_item_id = si.id
            WHERE s.sale_date >= ? AND s.sale_date <= ?
                AND s.status IN ({$statusList})
                AND si.product_id IN ({$placeholders})
                {$branchClause}
            GROUP BY si.product_id, s.branch_id, DATE(s.sale_date)
        ";

        $qtyRows = $this->db->query($sql, $binds)->getResultArray();
        $qtyByBranchProduct = [];
        foreach ($qtyRows as $row) {
            $key = (int) $row['branch_id'] . ':' . (int) $row['product_id'];
            $qtyByBranchProduct[$key][$row['sale_day']] = max(0.0, (float) $row['qty_sold']);
        }

        $dayKeys = [];
        $cursor = new \DateTime($dateFrom);
        $endDate = new \DateTime($dateTo);
        while ($cursor <= $endDate) {
            $dayKeys[] = $cursor->format('Y-m-d');
            $cursor->modify('+1 day');
        }

        $daysInMonth = (int) date('t');
        $results = [];
        $snapshotByProduct = [];

        foreach ($lowStockRows as $row) {
            $productId = (int) $row['product_id'];
            $rowBranchId = (int) $row['branch_id'];
            $stock = max(0.0, (float) $row['stock']);
            $seriesKey = $rowBranchId . ':' . $productId;
            $series = [];

            foreach ($dayKeys as $day) {
                $series[] = $qtyByBranchProduct[$seriesKey][$day] ?? 0.0;
            }

            // Always fit the submitted series. Reusing a same-day cache here
            // made changes to the branch/date filters appear to do nothing.
            $smoothed = $this->holtSmooth($series, $alpha, $beta);
            $level = (float) $smoothed['level'];
            $trend = (float) $smoothed['trend'];

            $sum = 0.0;
            for ($m = 1; $m <= self::REORDER_COVERAGE_DAYS; $m++) {
                $sum += max(0.0, $level + ($m * $trend));
            }
            $avgDailyDemand = $sum / self::REORDER_COVERAGE_DAYS;

            $hasDemand = $avgDailyDemand >= 0.01;
            $daysLeft = $hasDemand ? $stock / $avgDailyDemand : null;
            $suggestedQty = $hasDemand
                ? max(0, (int) ceil($avgDailyDemand * self::REORDER_COVERAGE_DAYS) - (int) $stock)
                : 0;

            if ($daysLeft === null) {
                $urgency = 'unknown';
            } elseif ($daysLeft <= 7) {
                $urgency = 'critical';
            } elseif ($daysLeft <= 14) {
                $urgency = 'warning';
            } else {
                $urgency = 'watch';
            }

            $results[] = [
                'product_id' => $productId,
                'branch_id' => $rowBranchId,
                'product_name' => $row['product_name'],
                'sku' => $row['sku'],
                'branch_name' => $row['branch_name'],
                'stock' => (int) $stock,
                'reorder_level' => (int) $row['reorder_level'],
                'avg_daily_demand' => round($avgDailyDemand, 2),
                'monthly_quantity' => max(0, (int) round($avgDailyDemand * $daysInMonth)),
                'price' => (float) $row['price'],
                'days_left' => $daysLeft !== null ? max(0, (int) floor($daysLeft)) : null,
                'suggested_qty' => $suggestedQty,
                'urgency' => $urgency,
            ];

            // forecasting_data has one row per product rather than per branch.
            // For the scheduled store-wide snapshot, aggregate every branch's
            // monthly demand into one product-level value before saving it.
            if ($persistSnapshot) {
                $predictedQty = max(0, (int) round($avgDailyDemand * $daysInMonth));
                if (!isset($snapshotByProduct[$productId])) {
                    $snapshotByProduct[$productId] = [
                        'predicted_quantity' => 0,
                        'predicted_revenue' => 0.0,
                    ];
                }
                $snapshotByProduct[$productId]['predicted_quantity'] += $predictedQty;
                $snapshotByProduct[$productId]['predicted_revenue'] += $predictedQty * (float) $row['price'];
            }
        }

        if ($persistSnapshot && !empty($snapshotByProduct)) {
            $this->persistDailySnapshots($snapshotByProduct, $alpha, $beta);
        }

        usort($results, static function (array $a, array $b): int {
            if ($a['days_left'] === null && $b['days_left'] === null) {
                return [$a['product_name'], $a['branch_name']] <=> [$b['product_name'], $b['branch_name']];
            }
            if ($a['days_left'] === null) {
                return 1;
            }
            if ($b['days_left'] === null) {
                return -1;
            }

            $daysComparison = $a['days_left'] <=> $b['days_left'];
            return $daysComparison !== 0
                ? $daysComparison
                : [$a['product_name'], $a['branch_name']] <=> [$b['product_name'], $b['branch_name']];
        });

        return $limit === null ? $results : array_slice($results, 0, max(1, min($limit, 250)));
    }

    /** Save the selected window without changing scheduled daily history. */
    public function saveManualSnapshot(
        string $dateFrom, string $dateTo, string $branchId,
        float $alpha, float $beta, string $forecastType
    ): int {
        $forecasts = $this->generate($dateFrom, $dateTo, $branchId, $alpha, $beta, false, null);
        $snapshots = [];
        foreach ($forecasts as $forecast) {
            $productId = (int) $forecast['product_id'];
            $quantity = (int) $forecast['monthly_quantity'];
            $snapshots[$productId] ??= ['predicted_quantity' => 0, 'predicted_revenue' => 0.0];
            $snapshots[$productId]['predicted_quantity'] += $quantity;
            $snapshots[$productId]['predicted_revenue'] += $quantity * (float) $forecast['price'];
        }
        if ($snapshots === []) return 0;

        // The existing schema has no branch/window columns. Keep the basis in
        // method_used, separate from scheduled rows, without a schema change.
        $method = 'Manual Holt b=' . ($branchId === '' ? 'all' : $branchId)
            . " {$dateFrom}..{$dateTo} {$forecastType} a={$alpha} t={$beta}";
        if (strlen($method) > 100) throw new \InvalidArgumentException('Forecast basis is too long.');
        $now = date('Y-m-d H:i:s');
        $month = date('Y-m-01');
        $start = date('Y-m-d') . ' 00:00:00';
        $end = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
        ksort($snapshots);
        if (!$this->db->transBegin()) throw new \RuntimeException('Unable to start forecast transaction.');
        try {
            // Lock existing product rows in a stable order. This serializes
            // competing manual saves even when no snapshot exists yet.
            if ($this->db->DBDriver === 'MySQLi') {
                $ids = array_keys($snapshots);
                $marks = implode(',', array_fill(0, count($ids), '?'));
                if ($this->db->query("SELECT id FROM products WHERE id IN ({$marks}) ORDER BY id FOR UPDATE", $ids) === false) {
                    throw new \RuntimeException('Unable to lock forecast products.');
                }
            }
            foreach ($snapshots as $productId => $snapshot) {
                $existing = $this->db->table('forecasting_data')
                    ->where('product_id', $productId)->where('forecast_month', $month)
                    ->where('method_used', $method)->where('generated_at >=', $start)
                    ->where('generated_at <', $end)->orderBy('id', 'DESC')->get()->getRowArray();
                $row = [
                    'product_id' => $productId, 'forecast_month' => $month,
                    'predicted_quantity' => $snapshot['predicted_quantity'],
                    'predicted_revenue' => round($snapshot['predicted_revenue'], 2),
                    'method_used' => $method, 'generated_at' => $now,
                ];
                $builder = $this->db->table('forecasting_data');
                $ok = $existing ? $builder->where('id', $existing['id'])->update($row) : $builder->insert($row);
                if (!$ok) throw new \RuntimeException('Unable to write forecast record.');
            }
            if (!$this->db->transStatus() || !$this->db->transCommit()) {
                throw new \RuntimeException('Unable to commit forecast records.');
            }
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
        return count($snapshots);
    }

    /**
     * Saves at most one product-level row per day. This is deliberately
     * separated from the live Reports-page calculation so page filters do not
     * create inconsistent or duplicate history snapshots.
     */
    private function persistDailySnapshots(array $snapshotByProduct, float $alpha, float $beta): void
    {
        $productIds = array_map('intval', array_keys($snapshotByProduct));
        if (empty($productIds)) {
            return;
        }

        $forecastMonth = date('Y-m-01');
        $todayStart = date('Y-m-d') . ' 00:00:00';
        $existingRows = $this->forecastingDataModel
            ->select('product_id')
            ->whereIn('product_id', $productIds)
            ->where('forecast_month', $forecastMonth)
            ->where('generated_at >=', $todayStart)
            ->groupStart()->notLike('method_used', 'Manual Holt ', 'after')->orWhere('method_used', null)->groupEnd()
            ->findAll();

        $existing = [];
        foreach ($existingRows as $row) {
            $existing[(int) $row['product_id']] = true;
        }

        $rows = [];
        foreach ($snapshotByProduct as $productId => $snapshot) {
            if (isset($existing[(int) $productId])) {
                continue;
            }

            $rows[] = [
                'product_id' => (int) $productId,
                'forecast_month' => $forecastMonth,
                'predicted_quantity' => (int) $snapshot['predicted_quantity'],
                'predicted_revenue' => round((float) $snapshot['predicted_revenue'], 2),
                'method_used' => "Holt Linear (branch-aware, a={$alpha}, b={$beta})",
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($rows)) {
            $this->forecastingDataModel->insertBatch($rows);
        }
    }

}
