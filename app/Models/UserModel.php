<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'full_name',
        'username',
        'password',
        'role',
        'branch_id',
        'status',
        'session_version',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    protected $beforeUpdate = ['invalidateSessions'];

    protected function invalidateSessions(array $event): array
    {
        if (array_intersect(['password', 'role', 'branch_id', 'status'], array_keys($event['data'] ?? []))) {
            $event['data']['session_version'] = bin2hex(random_bytes(16));
        }
        return $event;
    }
}
