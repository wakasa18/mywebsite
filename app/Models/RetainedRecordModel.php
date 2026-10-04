<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use LogicException;

/** Trash retains both the record and its references. deleted_at records when it happened. */
abstract class RetainedRecordModel extends Model
{
    private function filterActiveFlag(): void
    {
        if ($this->tempUseSoftDeletes) {
            $this->builder()->where($this->table . '.is_deleted', 0)
                ->where($this->table . '.is_permanently_deleted', 0);
        }
    }

    protected function doFind(bool $singleton, $id = null)
    {
        $this->filterActiveFlag();
        return parent::doFind($singleton, $id);
    }

    protected function doFindColumn(string $columnName)
    {
        $this->filterActiveFlag();
        return parent::doFindColumn($columnName);
    }

    protected function doFindAll(?int $limit = null, int $offset = 0)
    {
        $this->filterActiveFlag();
        return parent::doFindAll($limit, $offset);
    }

    protected function doFirst()
    {
        $this->filterActiveFlag();
        return parent::doFirst();
    }

    public function countAllResults(bool $reset = true, bool $test = false)
    {
        $this->filterActiveFlag();
        return parent::countAllResults($reset, $test);
    }

    protected function doOnlyDeleted()
    {
        // Also recognize records trashed before is_deleted was introduced.
        $this->builder()->where($this->table . '.is_permanently_deleted', 0)->groupStart()
            ->where($this->table . '.is_deleted', 1)
            ->orWhere($this->table . '.' . $this->deletedField . ' IS NOT NULL', null, false)
            ->groupEnd();
    }

    protected function doDelete($id = null, bool $purge = false)
    {
        if ($purge) {
            throw new LogicException('Physical deletion is disabled. Use retained permanent deletion from Trash.');
        }

        $builder = $this->builder();
        if (is_array($id) && $id !== []) {
            $builder->whereIn($this->primaryKey, $id);
        }
        if ($builder->getCompiledQBWhere() === []) {
            throw new DatabaseException('Moving records to Trash requires an ID or a where clause.');
        }

        $data = ['is_deleted' => 1, $this->deletedField => $this->setDate()];
        if ($this->useTimestamps && $this->updatedField !== '') {
            $data[$this->updatedField] = $this->setDate();
        }

        return $builder->where($this->table . '.is_deleted', 0)
            ->where($this->table . '.' . $this->deletedField, null)
            ->where($this->table . '.is_permanently_deleted', 0)->update($data);
    }

    protected function doPurgeDeleted()
    {
        throw new LogicException('Physical purging is disabled. Database records are retained.');
    }

    public function restoreRecord(int $id): bool
    {
        if ($id < 1) {
            throw new LogicException('Restoring a record requires a valid ID.');
        }
        $data = ['is_deleted' => 0, $this->deletedField => null];
        if ($this->useTimestamps && $this->updatedField !== '') {
            $data[$this->updatedField] = $this->setDate();
        }
        $restored = $this->db->table($this->table)->where($this->primaryKey, $id)
            ->where('is_permanently_deleted', 0)
            ->groupStart()->where('is_deleted', 1)
            ->orWhere($this->deletedField . ' IS NOT NULL', null, false)->groupEnd()->update($data);
        return $restored && $this->db->affectedRows() > 0;
    }
}
