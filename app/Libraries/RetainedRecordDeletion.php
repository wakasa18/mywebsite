<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/** Removes a trashed record from the workspace while retaining its database history. */
final class RetainedRecordDeletion
{
    private const NAMES = ['products'=>'product_name', 'categories'=>'category_name', 'suppliers'=>'supplier_name', 'discounts'=>'discount_name'];

    public function __construct(private BaseConnection $db) {}

    public function hide(string $table, int $id, string $role, int $userId): bool
    {
        if ($role !== 'admin' || $userId <= 0) throw new \DomainException('Administrator access required.');
        if (!isset(self::NAMES[$table]) || $id <= 0) throw new \InvalidArgumentException('Select a valid record in Trash.');
        $this->db->transBegin();
        try {
            $row = $this->db->query('SELECT * FROM ' . $table . ' WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if (!$row) throw new \InvalidArgumentException('Record not found in Trash.');
            if (!empty($row['is_permanently_deleted'])) {
                $this->db->transCommit();
                return false;
            }
            if (empty($row['is_deleted']) && empty($row['deleted_at'])) throw new \InvalidArgumentException('Move this record to Trash before deleting it permanently.');
            $now = date('Y-m-d H:i:s');
            $this->write($this->db->table($table)->where('id', $id)->update([
                'is_deleted'=>1, 'is_permanently_deleted'=>1, 'deleted_at'=>$row['deleted_at'] ?: $now,
                'permanently_deleted_at'=>$now, 'updated_at'=>$now,
            ]));
            $this->write($this->db->table('activity_logs')->insert([
                'user_id'=>$userId, 'activity'=>'Permanently hidden ' . $table . ' #' . $id . ': ' . $row[self::NAMES[$table]] . '; database record retained.',
                'log_time'=>$now,
            ]));
            if (!$this->db->transStatus()) throw new \RuntimeException('The record could not be hidden.');
            $this->db->transCommit();
            return true;
        } catch (\Throwable $error) {
            $this->db->transRollback();
            throw $error;
        }
    }

    private function write(bool $ok): void
    {
        if (!$ok) throw new \RuntimeException('The record could not be hidden.');
    }
}
