<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

final class ExpiryStockResolution
{
    public const ACTIONS = ['replace' => 'Replace with new stock', 'remove' => 'Dispose / Return to supplier', 'deactivate' => 'Deactivate in this branch'];

    public function __construct(private BaseConnection $db) {}

    public static function authorize(string $role, string $action): void
    {
        if (!in_array($role, ['admin', 'cashier'], true) || ($action === 'deactivate' && $role !== 'admin')) {
            throw new \DomainException('Only an administrator can deactivate a product in a branch.');
        }
        if (!isset(self::ACTIONS[$action])) throw new InvalidArgumentException('Select a valid expiry-stock action.');
    }

    public function inventory(int $id, string $role, int $branchId, bool $lock = false): array
    {
        $row = $this->db->query('SELECT * FROM branch_products WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$id])->getRowArray();
        if (!$row || !in_array($role, ['admin', 'cashier'], true) || ($role === 'cashier' && ($branchId <= 0 || (int) $row['branch_id'] !== $branchId))) {
            throw new \DomainException('This inventory record is not available for your branch.');
        }
        $product = $this->db->query('SELECT * FROM products WHERE id = ?' . ($lock ? ' FOR UPDATE' : ''), [$row['product_id']])->getRowArray();
        $branch = $this->db->table('branches')->where('id', $row['branch_id'])->get()->getRowArray();
        if (!$product || !$branch) throw new InvalidArgumentException('The product or branch record is missing.');
        $row['branch_deleted_at'] = $row['deleted_at'] ?? null;
        $row['branch_is_deleted'] = $row['is_deleted'] ?? 0;
        $row['branch_permanently_deleted_at'] = $row['permanently_deleted_at'] ?? null;
        $row['branch_is_permanently_deleted'] = $row['is_permanently_deleted'] ?? 0;
        unset($row['deleted_at'], $row['is_deleted'], $row['permanently_deleted_at'], $row['is_permanently_deleted']);
        return $row + ['product_name' => $product['product_name'], 'sku' => $product['sku'], 'unit' => $product['unit'],
            'product_status' => $product['status'], 'deleted_at' => $product['deleted_at'], 'is_deleted' => $product['is_deleted'], 'permanently_deleted_at' => $product['permanently_deleted_at'], 'is_permanently_deleted' => $product['is_permanently_deleted'],
            'branch_name' => $branch['branch_name'], 'branch_status' => $branch['status']];
    }

    public static function revision(array $row): string
    {
        $values = [];
        foreach (['id','product_id','branch_id','stock','expiration_date','status','updated_at','product_status','deleted_at','is_deleted','permanently_deleted_at','is_permanently_deleted','branch_deleted_at','branch_is_deleted','branch_permanently_deleted_at','branch_is_permanently_deleted','branch_status'] as $key) $values[] = $row[$key] ?? null;
        return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
    }

    private function quantity($value): int
    {
        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0 || (int) $value > 1000000) {
            throw new InvalidArgumentException('Enter a whole-number quantity from 1 to 1,000,000.');
        }
        return (int) $value;
    }

    public function resolve(int $id, string $action, array $input, string $role, int $branchId, int $userId, string $token, string $revision): bool
    {
        self::authorize($role, $action);
        if ($userId <= 0 || !preg_match('/^[a-f0-9]{48}$/D', $token)) throw new InvalidArgumentException('This action has expired. Reopen the expiry record.');
        $reason = is_string($input['reason'] ?? null) ? trim($input['reason']) : '';
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 100) throw new InvalidArgumentException('Enter a reason between 3 and 100 characters.');
        if (($input['confirmed'] ?? '') !== '1') throw new InvalidArgumentException('Confirm the stock action before saving.');
        $marker = '[ER:' . substr(hash('sha256', $id . ':' . $userId . ':' . $action . ':' . $token), 0, 32) . ']';
        $this->db->transBegin();
        try {
            $row = $this->inventory($id, $role, $branchId, true);
            // The row lock and durable log marker serialize repeated submissions.
            if ($this->db->table('stock_logs')->where('product_id', $row['product_id'])->where('branch_id', $row['branch_id'])->like('remarks', $marker)->countAllResults()) {
                $this->db->transCommit();
                return false;
            }
            if (!hash_equals(self::revision($row), $revision)) throw new InvalidArgumentException('Stock, expiry or status changed while this form was open. Reopen the record and review the latest values.');
            if ($row['status'] !== 'active' || $row['product_status'] !== 'active' || $row['branch_status'] !== 'active' || $row['deleted_at'] !== null || $row['branch_deleted_at'] !== null || !empty($row['is_deleted']) || !empty($row['branch_is_deleted']) || !empty($row['is_permanently_deleted']) || !empty($row['branch_is_permanently_deleted'])) {
                throw new InvalidArgumentException('Only active inventory, products and branches can be changed here.');
            }
            $oldStock = (int) $row['stock'];
            if ($oldStock < 0) throw new InvalidArgumentException('The current stock count needs administrator review.');
            $expiry = $row['expiration_date'] ?: 'not recorded';
            $now = date('Y-m-d H:i:s');
            $changes = ['updated_at' => $now];
            $newStock = $oldStock;
            $logs = [];
            if ($action === 'deactivate') {
                $changes['status'] = 'inactive';
                $logs[] = ['adjustment', 0, $oldStock, $oldStock, 'Deactivated in branch; expiry ' . $expiry . '; stock retained'];
            } else {
                $outcome = $input['outcome'] ?? '';
                if (!in_array($outcome, ['disposed', 'supplier_return'], true)) throw new InvalidArgumentException('Select Disposed or Returned to supplier for the removed stock.');
                $outcomeLabel = $outcome === 'disposed' ? 'Disposed' : 'Returned to supplier';
                if ($action === 'remove') {
                    $quantity = $this->quantity($input['quantity'] ?? null);
                    if ($quantity > $oldStock) throw new InvalidArgumentException('The removal quantity exceeds current stock.');
                    $newStock = $oldStock - $quantity;
                    $changes['stock'] = $newStock;
                    $logs[] = ['stock_out', $quantity, $oldStock, $newStock, $outcomeLabel . '; expiry ' . $expiry];
                } else {
                    // There is one expiry per branch-product: replacement removes the entire old stock first.
                    $newStock = $this->quantity($input['new_quantity'] ?? null);
                    $newExpiry = is_string($input['new_expiry'] ?? null) ? $input['new_expiry'] : '';
                    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $newExpiry)) throw new InvalidArgumentException('Enter a valid future expiration date for the new stock.');
                    $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $newExpiry);
                    if (!$parsed || $parsed->format('Y-m-d') !== $newExpiry || $newExpiry <= date('Y-m-d')) throw new InvalidArgumentException('Enter a valid future expiration date for the new stock.');
                    $changes += ['stock' => $newStock, 'expiration_date' => $newExpiry];
                    if ($oldStock > 0) $logs[] = ['stock_out', $oldStock, $oldStock, 0, 'Replaced old stock: ' . $outcomeLabel . '; expiry ' . $expiry];
                    $logs[] = ['stock_in', $newStock, 0, $newStock, 'Replacement received; expiry ' . $expiry . ' -> ' . $newExpiry];
                }
            }
            $this->write($this->db->table('branch_products')->where('id', $id)->update($changes));
            foreach ($logs as [$type, $quantity, $previous, $next, $description]) {
                $this->write($this->db->table('stock_logs')->insert(['product_id' => $row['product_id'], 'branch_id' => $row['branch_id'], 'user_id' => $userId,
                    'action_type' => $type, 'quantity' => $quantity, 'previous_stock' => $previous, 'new_stock' => $next,
                    'remarks' => 'Expiry resolution: ' . $description . '. ' . $reason . ' ' . $marker, 'created_at' => $now]));
            }
            if ($action !== 'deactivate') {
                $sum = $this->db->table('branch_products')->selectSum('stock', 'total')->where('product_id', $row['product_id'])->get()->getRowArray();
                $this->write($this->db->table('products')->where('id', $row['product_id'])->update(['stock' => (int) $sum['total'], 'updated_at' => $now]));
            }
            $this->write($this->db->table('activity_logs')->insert(['user_id' => $userId,
                'activity' => 'Expiry resolution: ' . self::ACTIONS[$action] . ' for product #' . $row['product_id'] . ' in branch #' . $row['branch_id']
                    . '. Stock ' . $oldStock . ' -> ' . $newStock . '; expiry ' . $expiry . ' -> ' . ($changes['expiration_date'] ?? $expiry) . '. ' . $reason . ' ' . $marker,
                'log_time' => $now]));
            if (!$this->db->transStatus()) throw new \RuntimeException('Expiry resolution could not be saved.');
            $this->db->transCommit();
            return true;
        } catch (\Throwable $error) {
            $this->db->transRollback();
            throw $error;
        }
    }

    private function write(bool $ok): void
    {
        if (!$ok) throw new \RuntimeException('Expiry resolution could not be saved.');
    }

    public function history(array $row): array
    {
        return $this->db->table('stock_logs l')->select('l.*, u.full_name')->join('users u', 'u.id = l.user_id', 'left')
            ->where('l.product_id', $row['product_id'])->where('l.branch_id', $row['branch_id'])
            ->like('l.remarks', 'Expiry resolution:', 'after')->orderBy('l.id', 'DESC')->get(50)->getResultArray();
    }
}
