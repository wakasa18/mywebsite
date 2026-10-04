<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\BranchProductModel;
use App\Models\SalesModel;

class Dashboard extends BaseController
{
    private const REVENUE_STATUSES = ['completed', 'partially_refunded'];

    protected $branchModel;
    protected $userModel;
    protected $productModel;
    protected $branchProductModel;
    protected $salesModel;
    protected $db;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        $this->branchProductModel = new BranchProductModel();
        $this->salesModel = new SalesModel();
        $this->db = \Config\Database::connect();
    }

    private function refundedAmountJoinSql(): string
    {
        return '(SELECT sale_id, SUM(refund_subtotal) AS refunded_amount '
            . 'FROM refund_items GROUP BY sale_id) ri';
    }

    private function refundedQuantityJoinSql(): string
    {
        return '(SELECT sale_item_id, SUM(quantity_refunded) AS qty_refunded, '
            . 'SUM(refund_subtotal) AS refunded_subtotal '
            . 'FROM refund_items GROUP BY sale_item_id) rq';
    }

    private function salesSummary(?string $from = null, ?string $to = null): array
    {
        $builder = $this->db->table('sales s')
            ->select(
                'COUNT(s.id) AS total_transactions, '
                . 'COALESCE(SUM(s.final_total - COALESCE(ri.refunded_amount, 0)), 0) AS net_sales',
                false
            )
            ->join($this->refundedAmountJoinSql(), 'ri.sale_id = s.id', 'left', false)
            ->whereIn('s.status', self::REVENUE_STATUSES);

        if ($from !== null) {
            $builder->where('s.sale_date >=', $from);
        }
        if ($to !== null) {
            $builder->where('s.sale_date <=', $to);
        }

        return $builder->get()->getRowArray() ?? [];
    }

    private function activeInventoryBuilder()
    {
        return $this->db->table('branch_products bp')
            ->join('products p', 'p.id = bp.product_id')
            ->join('branches b', 'b.id = bp.branch_id')
            ->where('bp.status', 'active')
            ->where('bp.deleted_at', null)->where('bp.is_deleted', 0)->where('bp.is_permanently_deleted', 0)
            ->where('p.status', 'active')
            ->where('p.deleted_at IS NULL', null, false)->where('p.is_deleted', 0)->where('p.is_permanently_deleted', 0)
            ->where('b.status', 'active');
    }

    public function index()
    {
        $today = date('Y-m-d');
        $todayStart = $today . ' 00:00:00';
        $todayEnd = $today . ' 23:59:59';
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayStart = $yesterday . ' 00:00:00';
        $yesterdayEnd = $yesterday . ' 23:59:59';
        $nearExpiryDate = date('Y-m-d', strtotime('+30 days'));
        $last30Start = date('Y-m-d', strtotime('-29 days'));
        $last90Start = date('Y-m-d', strtotime('-89 days'));

        // Active records only. The previous dashboard labels said "active"
        // while counting inactive branches and users as well.
        $totalBranches = $this->db->table('branches')
            ->where('status', 'active')
            ->countAllResults();

        $totalUsers = $this->db->table('users')
            ->where('status', 'active')
            ->countAllResults();

        $totalProducts = $this->db->table('products')
            ->where('status', 'active')
            ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0)
            ->countAllResults();

        $totalBranchInventory = $this->activeInventoryBuilder()->countAllResults();

        $stockRow = $this->activeInventoryBuilder()
            ->select('COALESCE(SUM(bp.stock), 0) AS total_stock', false)
            ->get()
            ->getRowArray();
        $totalStock = (int) ($stockRow['total_stock'] ?? 0);

        $todaySummary = $this->salesSummary($todayStart, $todayEnd);
        $yesterdaySummary = $this->salesSummary($yesterdayStart, $yesterdayEnd);
        $allTimeSummary = $this->salesSummary();

        $todayTransactions = (int) ($todaySummary['total_transactions'] ?? 0);
        $todayRevenue = (float) ($todaySummary['net_sales'] ?? 0);
        $yesterdayRevenue = (float) ($yesterdaySummary['net_sales'] ?? 0);
        $totalSales = (int) ($allTimeSummary['total_transactions'] ?? 0);
        $totalSalesAmount = (float) ($allTimeSummary['net_sales'] ?? 0);

        // Exact counts are calculated separately from the limited alert list.
        // Previously the KPI was capped at 10 because it used count(findAll(10)).
        $lowStockCount = $this->activeInventoryBuilder()
            ->where('bp.stock <= bp.reorder_level', null, false)
            ->countAllResults();

        $outOfStockCount = $this->activeInventoryBuilder()
            ->where('bp.stock <=', 0)
            ->countAllResults();

        $lowStockItems = $this->activeInventoryBuilder()
            ->select('bp.id, bp.branch_id, bp.product_id, bp.stock, bp.reorder_level, '
                . 'p.product_name, p.sku, b.branch_name')
            ->where('bp.stock <= bp.reorder_level', null, false)
            ->orderBy('bp.stock', 'ASC')
            ->orderBy('p.product_name', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $nearExpiryCount = $this->activeInventoryBuilder()
            ->where('bp.stock >', 0)
            ->where('bp.expiration_date IS NOT NULL', null, false)
            ->where('bp.expiration_date >=', $today)
            ->where('bp.expiration_date <=', $nearExpiryDate)
            ->countAllResults();

        $expiredCount = $this->activeInventoryBuilder()
            ->where('bp.stock >', 0)
            ->where('bp.expiration_date IS NOT NULL', null, false)
            ->where('bp.expiration_date <', $today)
            ->countAllResults();

        $nearExpiryItems = $this->activeInventoryBuilder()
            ->select('bp.stock, bp.reorder_level, bp.expiration_date, '
                . 'p.product_name, p.sku, b.branch_name')
            ->where('bp.stock >', 0)
            ->where('bp.expiration_date IS NOT NULL', null, false)
            ->where('bp.expiration_date >=', $today)
            ->where('bp.expiration_date <=', $nearExpiryDate)
            ->orderBy('bp.expiration_date', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $expiredItems = $this->activeInventoryBuilder()
            ->select('bp.stock, bp.reorder_level, bp.expiration_date, '
                . 'p.product_name, p.sku, b.branch_name')
            ->where('bp.stock >', 0)
            ->where('bp.expiration_date IS NOT NULL', null, false)
            ->where('bp.expiration_date <', $today)
            ->orderBy('bp.expiration_date', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // Last 30 days by active branch, including branches with no sales.
        $statusList = "'" . implode("','", self::REVENUE_STATUSES) . "'";
        $salesByBranch = $this->db->query(
            "SELECT
                b.id AS branch_id,
                b.branch_name,
                COUNT(s.id) AS total_transactions,
                COALESCE(SUM(s.final_total - COALESCE(ri.refunded_amount, 0)), 0) AS total_amount
            FROM branches b
            LEFT JOIN sales s
                ON s.branch_id = b.id
                AND s.status IN ({$statusList})
                AND s.sale_date >= ?
            LEFT JOIN {$this->refundedAmountJoinSql()} ON ri.sale_id = s.id
            WHERE b.status = 'active'
            GROUP BY b.id, b.branch_name
            ORDER BY total_amount DESC, b.branch_name ASC",
            [$last30Start . ' 00:00:00']
        )->getResultArray();

        $inventoryByBranch = $this->db->query(
            "SELECT
                b.id AS branch_id,
                b.branch_name,
                COUNT(p.id) AS total_products,
                COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN bp.stock ELSE 0 END), 0) AS total_stock
            FROM branches b
            LEFT JOIN branch_products bp
                ON bp.branch_id = b.id
                AND bp.status = 'active'
                AND bp.deleted_at IS NULL
                AND bp.is_deleted = 0
                AND bp.is_permanently_deleted = 0
            LEFT JOIN products p
                ON p.id = bp.product_id
                AND p.status = 'active'
                AND p.deleted_at IS NULL
                AND p.is_deleted = 0
                AND p.is_permanently_deleted = 0
            WHERE b.status = 'active'
            GROUP BY b.id, b.branch_name
            ORDER BY b.branch_name ASC"
        )->getResultArray();

        $recentSales = $this->db->table('sales s')
            ->select('s.*, u.full_name, b.branch_name, '
                . 'COALESCE(ri.refunded_amount, 0) AS refunded_amount, '
                . '(s.final_total - COALESCE(ri.refunded_amount, 0)) AS remaining_total', false)
            ->join('users u', 'u.id = s.user_id', 'left')
            ->join('branches b', 'b.id = s.branch_id', 'left')
            ->join($this->refundedAmountJoinSql(), 'ri.sale_id = s.id', 'left', false)
            ->whereIn('s.status', self::REVENUE_STATUSES)
            ->orderBy('s.sale_date', 'DESC')
            ->orderBy('s.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $recentLogs = $this->db->table('activity_logs')
            ->select('activity_logs.*, users.full_name, users.username')
            ->join('users', 'users.id = activity_logs.user_id', 'left')
            ->orderBy('activity_logs.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // Daily net sales trend — last 30 days, after refunds.
        $dailySalesRaw = $this->db->table('sales s')
            ->select('DATE(s.sale_date) AS sale_day, '
                . 'SUM(s.final_total - COALESCE(ri.refunded_amount, 0)) AS revenue, '
                . 'COUNT(s.id) AS txns', false)
            ->join($this->refundedAmountJoinSql(), 'ri.sale_id = s.id', 'left', false)
            ->whereIn('s.status', self::REVENUE_STATUSES)
            ->where('s.sale_date >=', $last30Start . ' 00:00:00')
            ->groupBy('DATE(s.sale_date)')
            ->orderBy('sale_day', 'ASC')
            ->get()
            ->getResultArray();

        $dailyMap = [];
        foreach ($dailySalesRaw as $row) {
            $dailyMap[$row['sale_day']] = $row;
        }

        $dailyLabels = [];
        $dailyRevenue = [];
        $dailyTxns = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dailyLabels[] = \App\Libraries\DisplayDate::date($date);
            $dailyRevenue[] = isset($dailyMap[$date]) ? (float) $dailyMap[$date]['revenue'] : 0.0;
            $dailyTxns[] = isset($dailyMap[$date]) ? (int) $dailyMap[$date]['txns'] : 0;
        }

        // Monthly net sales trend — last 12 months, after refunds.
        $last12Start = date('Y-m-01', strtotime('-11 months'));
        $monthlySalesRaw = $this->db->table('sales s')
            ->select("DATE_FORMAT(s.sale_date, '%Y-%m') AS sale_month, "
                . 'SUM(s.final_total - COALESCE(ri.refunded_amount, 0)) AS revenue, '
                . 'COUNT(s.id) AS txns', false)
            ->join($this->refundedAmountJoinSql(), 'ri.sale_id = s.id', 'left', false)
            ->whereIn('s.status', self::REVENUE_STATUSES)
            ->where('s.sale_date >=', $last12Start . ' 00:00:00')
            ->groupBy("DATE_FORMAT(s.sale_date, '%Y-%m')")
            ->orderBy('sale_month', 'ASC')
            ->get()
            ->getResultArray();

        $monthlyMap = [];
        foreach ($monthlySalesRaw as $row) {
            $monthlyMap[$row['sale_month']] = $row;
        }

        $monthlyLabels = [];
        $monthlyRevenue = [];
        $monthlyTxns = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthlyLabels[] = \App\Libraries\DisplayDate::period($month);
            $monthlyRevenue[] = isset($monthlyMap[$month]) ? (float) $monthlyMap[$month]['revenue'] : 0.0;
            $monthlyTxns[] = isset($monthlyMap[$month]) ? (int) $monthlyMap[$month]['txns'] : 0;
        }

        // Top products in the last 90 days, after item discounts and refunds.
        $topProducts = $this->db->table('sale_items si')
            ->select('si.product_id, MAX(si.product_name_snapshot) AS product_name, '
                . 'SUM(si.quantity - COALESCE(rq.qty_refunded, 0)) AS units_sold, '
                . 'SUM((si.subtotal - COALESCE(si.discount_applied, 0)) '
                . '- COALESCE(rq.refunded_subtotal, 0)) AS revenue', false)
            ->join('sales s', 's.id = si.sale_id')
            ->join($this->refundedQuantityJoinSql(), 'rq.sale_item_id = si.id', 'left', false)
            ->whereIn('s.status', self::REVENUE_STATUSES)
            ->where('s.sale_date >=', $last90Start . ' 00:00:00')
            ->groupBy('si.product_id')
            ->having('SUM(si.quantity - COALESCE(rq.qty_refunded, 0)) > 0', null, false)
            ->orderBy('revenue', 'DESC')
            ->orderBy('units_sold', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        $topProductLabels = array_column($topProducts, 'product_name');
        $topProductRevenue = array_map(static fn(array $row): float => (float) $row['revenue'], $topProducts);
        $topProductUnits = array_map(static fn(array $row): int => (int) $row['units_sold'], $topProducts);

        $branchChartData = array_map(static fn(array $row): float => (float) $row['total_amount'], $salesByBranch);
        $branchChartLabels = array_map(
            static fn(array $row): string => (string) ($row['branch_name'] ?? 'Unknown'),
            $salesByBranch
        );

        return view('dashboard/index', [
            'title' => 'Dashboard',
            'totalBranches' => $totalBranches,
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalBranchInventory' => $totalBranchInventory,
            'totalStock' => $totalStock,
            'todayTransactions' => $todayTransactions,
            'todayRevenue' => $todayRevenue,
            'yesterdayRevenue' => $yesterdayRevenue,
            'totalSales' => $totalSales,
            'totalSalesAmount' => $totalSalesAmount,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'lowStockItems' => $lowStockItems,
            'salesByBranch' => $salesByBranch,
            'inventoryByBranch' => $inventoryByBranch,
            'recentSales' => $recentSales,
            'nearExpiryItems' => $nearExpiryItems,
            'expiredItems' => $expiredItems,
            'nearExpiryCount' => $nearExpiryCount,
            'expiredCount' => $expiredCount,
            'today' => $today,
            'recentLogs' => $recentLogs,
            'dailyLabels' => $dailyLabels,
            'dailyRevenue' => $dailyRevenue,
            'dailyTxns' => $dailyTxns,
            'monthlyLabels' => $monthlyLabels,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyTxns' => $monthlyTxns,
            'topProductLabels' => $topProductLabels,
            'topProductRevenue' => $topProductRevenue,
            'topProductUnits' => $topProductUnits,
            'branchChartLabels' => $branchChartLabels,
            'branchChartData' => $branchChartData,
        ]);
    }
}
