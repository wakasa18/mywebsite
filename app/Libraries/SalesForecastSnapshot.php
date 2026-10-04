<?php

namespace App\Libraries;

/** Revenue forecast points use nullable product/quantity fields in the existing table. */
class SalesForecastSnapshot
{
    public static function basis(string $from, string $to, string $branch, string $interval): string
    {
        return 'Sales Holt b=' . ($branch === '' ? 'all' : $branch)
            . " {$from}..{$to} {$interval} a=0.3 t=0.2";
    }

    /** Caller owns the transaction, including any product snapshots. */
    public function save($db, array $points, string $from, string $to, string $branch, string $interval): int
    {
        $method = self::basis($from, $to, $branch, $interval);
        if (strlen($method) > 100 || !in_array($interval, ['daily', 'weekly', 'monthly'], true)) {
            throw new \InvalidArgumentException('Invalid sales forecast basis.');
        }
        // Serialize matching saves even before any nullable-product forecast rows exist.
        if ($db->DBDriver === 'MySQLi') {
            $sql = 'SELECT id FROM branches' . ($branch === '' ? '' : ' WHERE id = ?') . ' ORDER BY id FOR UPDATE';
            if ($db->query($sql, $branch === '' ? [] : [(int) $branch]) === false) {
                throw new \RuntimeException('Unable to lock forecast scope.');
            }
        }
        $now = date('Y-m-d H:i:s');
        foreach ($points as $point) {
            $label = (string) $point['forecast_date'];
            $target = match ($interval) {
                'weekly' => (new \DateTimeImmutable())->setISODate((int) substr($label, 0, 4), (int) substr($label, 6, 2))->format('Y-m-d'),
                'monthly' => $label . '-01',
                default => $label,
            };
            $existing = $db->table('forecasting_data')->where('product_id', null)
                ->where('method_used', $method)->where('forecast_month', $target)
                ->where('generated_at >=', date('Y-m-d') . ' 00:00:00')
                ->where('generated_at <', date('Y-m-d', strtotime('+1 day')) . ' 00:00:00')
                ->orderBy('id', 'DESC')->get()->getRowArray();
            $row = ['product_id' => null, 'forecast_month' => $target, 'predicted_quantity' => null,
                'predicted_revenue' => $point['forecast_value'], 'method_used' => $method, 'generated_at' => $now];
            $builder = $db->table('forecasting_data');
            $ok = $existing ? $builder->where('id', $existing['id'])->update($row) : $builder->insert($row);
            if (!$ok) throw new \RuntimeException('Unable to save sales forecast.');
        }
        return count($points);
    }
}
