<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

class TopbarNotificationService
{
    private const FORECAST_HISTORY_DAYS = 90;
    private const FORECAST_LEVEL_WEIGHT = 0.30;
    private const FORECAST_TREND_WEIGHT = 0.20;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Build lightweight inventory alerts for the shared topbar.
     * Cashiers only see alerts from their assigned branch; administrators
     * see the combined count for all active branches.
     */
    public function getSummary(string $role, ?int $branchId = null): array
    {
        $empty = [
            'total' => 0,
            'badge' => '',
            'items' => [],
            'scope_label' => $role === 'cashier' ? 'Your branch' : 'All branches',
            'low_stock_total' => 0,
            'low_stock_products' => [],
            'low_stock_url' => '',
        ];

        if (!in_array($role, ['admin', 'cashier'], true)) {
            return $empty;
        }

        if ($role === 'cashier' && (!$branchId || $branchId <= 0)) {
            return $empty;
        }

        $lowStockEnabled = true;
        $expiredEnabled = true;
        $nearExpiryEnabled = true;
        $nearExpiryDays = 30;

        $today = date('Y-m-d');
        $nearDate = date('Y-m-d', strtotime('+' . $nearExpiryDays . ' days'));

        $lowStock = $lowStockEnabled
            ? $this->countMatching($role, $branchId, static function ($builder): void {
                $builder->where('branch_products.stock <= branch_products.reorder_level', null, false);
            })
            : 0;
        $lowStockProducts = $lowStock > 0
            ? $this->getLowStockProducts($role, $branchId)
            : [];

        if (!empty($lowStockProducts)) {
            $lowStockProducts = $this->attachReorderForecasts($lowStockProducts, $role, $branchId);
        }

        $expired = $expiredEnabled
            ? $this->countMatching($role, $branchId, static function ($builder) use ($today): void {
                $builder
                    ->where('branch_products.stock >', 0)
                    ->where('branch_products.expiration_date IS NOT NULL', null, false)
                    ->where('branch_products.expiration_date <', $today);
            })
            : 0;

        $nearExpiry = $nearExpiryEnabled
            ? $this->countMatching($role, $branchId, static function ($builder) use ($today, $nearDate): void {
                $builder
                    ->where('branch_products.stock >', 0)
                    ->where('branch_products.expiration_date >=', $today)
                    ->where('branch_products.expiration_date <=', $nearDate);
            })
            : 0;

        $items = [];
        if ($expired > 0) {
            $items[] = [
                'type' => 'danger',
                'title' => 'Expired stock needs attention',
                'message' => $expired . ' stocked item' . ($expired === 1 ? ' has' : 's have') . ' passed the expiry date.',
                'url' => site_url('admin/expiry-report?status=expired'),
                'count' => $expired,
            ];
        }
        if ($lowStock > 0) {
            // The separate Branch Inventory page was removed. Stock management
            // is now handled from the shared Products page. Keep the cashier
            // link limited to the cashier's assigned branch.
            $lowUrl = $role === 'admin'
                ? site_url('products')
                : site_url('products') . '?branch_id=' . rawurlencode((string) (int) $branchId);
            $items[] = [
                'type' => 'warning',
                'title' => 'Low-stock products',
                'message' => $lowStock . ' item' . ($lowStock === 1 ? ' is' : 's are') . ' at or below the reorder level. Click to view the products.',
                'url' => $lowUrl,
                'action' => 'show_low_stock',
                'count' => $lowStock,
            ];
        }
        if ($nearExpiry > 0) {
            $items[] = [
                'type' => 'info',
                'title' => 'Products nearing expiry',
                'message' => $nearExpiry . ' stocked item' . ($nearExpiry === 1 ? ' expires' : 's expire') . ' within ' . $nearExpiryDays . ' day' . ($nearExpiryDays === 1 ? '' : 's') . '.',
                'url' => site_url('admin/expiry-report?status=near'),
                'count' => $nearExpiry,
            ];
        }

        $total = $expired + $lowStock + $nearExpiry;

        return [
            'total' => $total,
            'badge' => $total > 99 ? '99+' : (string) $total,
            'items' => $items,
            'scope_label' => $role === 'cashier' ? 'Your branch' : 'All branches',
            'low_stock_total' => $lowStock,
            'low_stock_products' => $lowStockProducts,
            'low_stock_url' => $lowStock > 0 ? $lowUrl : '',
        ];
    }

    /**
     * Return the low-stock rows shown in the notification popup.
     * The full count is kept separately so the popup can explain when a
     * very large result set has been limited for page performance.
     */
    private function getLowStockProducts(string $role, ?int $branchId, int $limit = 250): array
    {
        $builder = $this->db->table('branch_products')
            ->select([
                'branch_products.id AS branch_product_id',
                'branch_products.product_id',
                'branch_products.branch_id',
                'branch_products.stock',
                'branch_products.reorder_level',
                'products.product_name',
                'products.sku',
                'products.unit',
                'branches.branch_name',
            ])
            ->join('products', 'products.id = branch_products.product_id', 'inner')
            ->join('branches', 'branches.id = branch_products.branch_id', 'inner')
            ->where('branch_products.status', 'active')->where('branch_products.deleted_at', null)->where('branch_products.is_deleted', 0)->where('branch_products.is_permanently_deleted', 0)
            ->where('products.status', 'active')
            ->where('products.deleted_at IS NULL', null, false)->where('products.is_deleted', 0)->where('products.is_permanently_deleted', 0)
            ->where('branches.status', 'active')
            ->where('branch_products.stock <= branch_products.reorder_level', null, false);

        if ($role === 'cashier') {
            $builder->where('branch_products.branch_id', (int) $branchId);
        }

        return $builder
            ->orderBy('(branch_products.stock / GREATEST(branch_products.reorder_level, 1))', 'ASC', false)
            ->orderBy('branches.branch_name', 'ASC')
            ->orderBy('products.product_name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }


    /**
     * Add the same 30-day suggested reorder quantity used by the Reports page.
     * The topbar uses the most recent 90 days of sales so the figure is useful
     * without requiring the owner to first choose a forecasting date range.
     */
    private function attachReorderForecasts(array $products, string $role, ?int $branchId): array
    {
        foreach ($products as &$product) {
            $product['reorder_forecast'] = null;
            $product['forecast_has_demand'] = false;
        }
        unset($product);

        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime('-' . (self::FORECAST_HISTORY_DAYS - 1) . ' days'));
        $branchFilter = $role === 'cashier' ? (string) (int) $branchId : '';

        try {
            $forecastRows = (new ReorderForecastService())->generate(
                $dateFrom,
                $dateTo,
                $branchFilter,
                self::FORECAST_LEVEL_WEIGHT,
                self::FORECAST_TREND_WEIGHT,
                false,
                count($products)
            );
        } catch (\Throwable $e) {
            log_message('error', 'Low-stock reorder forecasts could not be loaded: {message}', [
                'message' => $e->getMessage(),
            ]);
            return $products;
        }

        $forecastByBranchProduct = [];
        foreach ($forecastRows as $row) {
            $key = (int) ($row['branch_id'] ?? 0) . ':' . (int) ($row['product_id'] ?? 0);
            $forecastByBranchProduct[$key] = $row;
        }

        foreach ($products as &$product) {
            $key = (int) ($product['branch_id'] ?? 0) . ':' . (int) ($product['product_id'] ?? 0);
            if (!isset($forecastByBranchProduct[$key])) {
                continue;
            }

            $forecast = $forecastByBranchProduct[$key];
            $product['reorder_forecast'] = max(0, (int) ($forecast['suggested_qty'] ?? 0));
            $product['forecast_has_demand'] = (float) ($forecast['avg_daily_demand'] ?? 0) >= 0.01;
        }
        unset($product);

        return $products;
    }

    private function countMatching(string $role, ?int $branchId, callable $condition): int
    {
        $builder = $this->db->table('branch_products')
            ->join('products', 'products.id = branch_products.product_id', 'inner')
            ->join('branches', 'branches.id = branch_products.branch_id', 'inner')
            ->where('branch_products.status', 'active')->where('branch_products.deleted_at', null)->where('branch_products.is_deleted', 0)->where('branch_products.is_permanently_deleted', 0)
            ->where('products.status', 'active')
            ->where('products.deleted_at IS NULL', null, false)->where('products.is_deleted', 0)->where('products.is_permanently_deleted', 0)
            ->where('branches.status', 'active');

        if ($role === 'cashier') {
            $builder->where('branch_products.branch_id', (int) $branchId);
        }

        $condition($builder);

        return (int) $builder->countAllResults();
    }
}
