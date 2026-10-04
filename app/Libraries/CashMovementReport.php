<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

final class CashMovementReport
{
    public function __construct(private BaseConnection $db)
    {
    }

    public function generate(string $from, string $to, string $branchId = ''): array
    {
        $start = $from . ' 00:00:00';
        $end = $to . ' 23:59:59';
        $sales = $this->db->table('sales s')
            ->select('DATE(s.sale_date) AS movement_day, s.payment_method AS method, SUM(s.final_total) AS amount', false)
            ->whereIn('s.status', ['completed', 'partially_refunded', 'refunded'])
            ->where('s.sale_date >=', $start)->where('s.sale_date <=', $end);
        $refunds = $this->db->table('refund_items r')
            ->select("DATE(r.created_at) AS movement_day, COALESCE(r.refund_method, 'unknown') AS method, SUM(r.refund_subtotal) AS amount", false)
            ->join('sales s', 's.id = r.sale_id')
            ->where('r.created_at >=', $start)->where('r.created_at <=', $end);
        $returns = $this->db->table('refund_items r')
            ->select('r.return_condition, SUM(r.quantity_refunded) AS quantity', false)
            ->join('sales s', 's.id = r.sale_id')
            ->where('r.created_at >=', $start)->where('r.created_at <=', $end);
        if ($branchId !== '') {
            foreach ([$sales, $refunds, $returns] as $builder) {
                $builder->where('s.branch_id', (int) $branchId);
            }
        }
        $rows = [];
        foreach ([
            'receipts' => $sales->groupBy('DATE(s.sale_date), s.payment_method', false)->get()->getResultArray(),
            'refunds' => $refunds->groupBy("DATE(r.created_at), COALESCE(r.refund_method, 'unknown')", false)->get()->getResultArray(),
        ] as $type => $events) {
            foreach ($events as $event) {
                $key = $event['movement_day'] . ':' . $event['method'];
                $rows[$key] ??= ['date' => $event['movement_day'], 'method' => $event['method'], 'receipts' => 0.0, 'refunds' => 0.0];
                $rows[$key][$type] += (float) $event['amount'];
            }
        }
        // A return credit offsets the replacement sale; it is not a cash receipt or payout.
        // Both exchange entries commit together with the same date, branch and method.
        $exchanges = $this->db->table('sales')->like('invoice_no', 'EXC-', 'after')
            ->where('sale_date >=', $start)->where('sale_date <=', $end)
            ->whereIn('status', ['completed', 'partially_refunded', 'refunded']);
        if ($branchId !== '') $exchanges->where('branch_id', (int) $branchId);
        foreach ($exchanges->get()->getResultArray() as $exchangeSale) {
            $exchange = ExchangeRecord::forSale($exchangeSale, $this->db);
            if (!$exchange) continue;
            $key = substr($exchangeSale['sale_date'], 0, 10) . ':' . $exchangeSale['payment_method'];
            if (isset($rows[$key])) {
                $rows[$key]['receipts'] -= $exchange['credit'];
                $rows[$key]['refunds'] -= $exchange['credit'];
            }
        }
        ksort($rows);
        $totals = ['receipts' => 0.0, 'refunds' => 0.0, 'net' => 0.0, 'cash_net' => 0.0, 'unknown_refunds' => 0.0];
        foreach ($rows as &$row) {
            $row['receipts'] = round($row['receipts'], 2);
            $row['refunds'] = round($row['refunds'], 2);
            $row['net'] = round($row['receipts'] - $row['refunds'], 2);
            foreach (['receipts', 'refunds', 'net'] as $key) {
                $totals[$key] += $row[$key];
            }
            if ($row['method'] === 'cash') {
                $totals['cash_net'] += $row['net'];
            }
            if ($row['method'] === 'unknown') {
                $totals['unknown_refunds'] += $row['refunds'];
            }
        }
        unset($row);
        return [
            'rows' => array_values($rows), 'totals' => array_map(static fn ($value) => round($value, 2), $totals),
            'returns' => $returns->groupBy('r.return_condition')->get()->getResultArray(),
        ];
    }
}
