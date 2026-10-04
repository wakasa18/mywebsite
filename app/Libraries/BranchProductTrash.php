<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

final class BranchProductTrash
{
    public function __construct(private BaseConnection $db) {}

    public function permanentlyDelete(int $productId, int $branchId, string $role, int $userId): bool
    {
        return $this->change($productId, $branchId, true, $role, $userId, true);
    }

    public function change(int $productId, int $branchId, bool $trash, string $role, int $userId, bool $permanent = false): bool
    {
        if ($role !== 'admin') {
            throw new \DomainException('Only administrators can move or restore branch products.');
        }
        if ($productId <= 0 || $branchId <= 0 || $userId <= 0) {
            throw new \InvalidArgumentException('Select a specific branch product before changing its trash status.');
        }
        $this->db->transBegin();
        try {
            $row = $this->db->query('SELECT * FROM branch_products WHERE product_id = ? AND branch_id = ? FOR UPDATE', [$productId, $branchId])->getRowArray();
            $product = $this->db->query('SELECT * FROM products WHERE id = ? FOR UPDATE', [$productId])->getRowArray();
            $branch = $this->db->table('branches')->where('id', $branchId)->get()->getRowArray();
            if (!$row || !$product || !$branch) {
                throw new \InvalidArgumentException('This product does not have an inventory record in the selected branch.');
            }
            if (!empty($product['deleted_at']) || !empty($product['is_deleted']) || !empty($product['is_permanently_deleted'])) {
                throw new \InvalidArgumentException('This is an older shared product deletion. Use the legacy restore action in Product Trash first.');
            }
            if (!empty($row['is_permanently_deleted'])) {
                if (!$permanent) throw new \InvalidArgumentException('This branch product was permanently removed from the system and cannot be restored here.');
                $this->db->transCommit();
                return false;
            }
            $inTrash = !empty($row['deleted_at']) || !empty($row['is_deleted']);
            if ($permanent && !$inTrash) throw new \InvalidArgumentException('Move this branch product to Trash before deleting it permanently.');
            if (!$permanent && $trash === $inTrash) {
                $this->db->transCommit();
                return false;
            }
            $now = date('Y-m-d H:i:s');
            $changes = [
                'is_deleted' => $trash ? 1 : 0,
                'deleted_at' => $trash ? ($row['deleted_at'] ?: $now) : null, 'updated_at' => $now,
            ];
            if ($permanent) {
                $changes['is_permanently_deleted'] = 1;
                $changes['permanently_deleted_at'] = $now;
            }
            $this->write($this->db->table('branch_products')->where('id', $row['id'])->update($changes));
            $action = $permanent ? 'Permanently hidden; database record retained' : ($trash ? 'Moved to Trash' : 'Restored from Trash');
            $this->write($this->db->table('stock_logs')->insert([
                'product_id'=>$productId, 'branch_id'=>$branchId, 'user_id'=>$userId,
                'action_type'=>'adjustment', 'quantity'=>0,
                'previous_stock'=>(int)$row['stock'], 'new_stock'=>(int)$row['stock'],
                'remarks'=>'Branch product: ' . $action . '; stock and prices retained.', 'created_at'=>$now,
            ]));
            $this->write($this->db->table('activity_logs')->insert([
                'user_id'=>$userId, 'activity'=>$action . ': product #' . $productId . ' in branch #' . $branchId,
                'log_time'=>$now,
            ]));
            if (!$this->db->transStatus()) throw new \RuntimeException('The branch product change could not be saved.');
            $this->db->transCommit();
            return true;
        } catch (\Throwable $error) {
            $this->db->transRollback();
            throw $error;
        }
    }

    private function write(bool $ok): void
    {
        if (!$ok) throw new \RuntimeException('The branch product change could not be saved.');
    }
}
