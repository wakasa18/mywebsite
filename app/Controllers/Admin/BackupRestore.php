<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DatabaseBackupService;
use App\Models\ActivityLogModel;
use RuntimeException;
use Throwable;

class BackupRestore extends BaseController
{
    private DatabaseBackupService $backupService;
    private ActivityLogModel $activityLogModel;
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->backupService = new DatabaseBackupService($this->db);
        $this->activityLogModel = new ActivityLogModel();
    }

    public function index()
    {
        try {
            $backups = $this->backupService->listBackups();
            $storageReady = true;
            $storageError = null;
        } catch (Throwable $e) {
            $backups = [];
            $storageReady = false;
            $storageError = $e->getMessage();
        }

        return view('admin/backup_restore/index', [
            'backups' => $backups,
            'storageReady' => $storageReady,
            'storageError' => $storageError,
            'uploadLimitMb' => min($this->phpUploadLimitMb(), 256),
        ]);
    }

    public function create()
    {
        try {
            $backup = $this->backupService->createBackup('backup');
            $this->logActivity(
                'Created database backup: ' . $backup['filename']
                . ' (' . $backup['row_count'] . ' rows)'
            );

            return redirect()->to(site_url('admin/backup-restore'))
                ->with('success', 'Backup created successfully. Download and keep a copy in a secure location.');
        } catch (Throwable $e) {
            log_message('error', 'Database backup failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('admin/backup-restore'))
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download(string $filename)
    {
        try {
            $path = $this->backupService->resolveBackupPath($filename);
            return $this->response->download($path, null)->setFileName(basename($path));
        } catch (Throwable $e) {
            return redirect()->to(site_url('admin/backup-restore'))
                ->with('error', $e->getMessage());
        }
    }

    public function delete(string $filename)
    {
        try {
            $this->backupService->deleteBackup($filename);
            $this->logActivity('Deleted stored database backup: ' . basename($filename));
            return redirect()->to(site_url('admin/backup-restore'))
                ->with('success', 'Stored backup deleted.');
        } catch (Throwable $e) {
            return redirect()->to(site_url('admin/backup-restore'))
                ->with('error', 'Could not delete backup: ' . $e->getMessage());
        }
    }

    public function restore()
    {
        $confirmation = strtoupper(trim((string) $this->request->getPost('confirmation')));
        $password = (string) $this->request->getPost('admin_password');

        if ($confirmation !== 'RESTORE') {
            return redirect()->back()->withInput()
                ->with('error', 'Type RESTORE exactly to confirm replacing the current database data.');
        }

        if (!$this->verifyCurrentAdminPassword($password)) {
            return redirect()->back()->withInput()
                ->with('error', 'The administrator password is incorrect.');
        }

        $temporaryUpload = null;

        try {
            $sourceType = (string) $this->request->getPost('restore_source');
            if ($sourceType === 'stored') {
                $selected = trim((string) $this->request->getPost('stored_backup'));
                if ($selected === '') {
                    throw new RuntimeException('Choose a stored backup to restore.');
                }
                $path = $this->backupService->resolveBackupPath($selected);
            } else {
                $file = $this->request->getFile('backup_file');
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    throw new RuntimeException($file?->getErrorString() ?: 'Choose a valid .pmbak backup file.');
                }
                if (strtolower((string) $file->getClientExtension()) !== 'pmbak') {
                    throw new RuntimeException('Only Pharxmaco .pmbak files can be restored.');
                }
                if ($file->getSize() <= 0 || $file->getSize() > 268435456) {
                    throw new RuntimeException('The backup file is empty or exceeds the 256 MB restore limit.');
                }

                $uploadDirectory = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
                    throw new RuntimeException('The temporary upload folder could not be created.');
                }
                $temporaryName = 'restore-' . bin2hex(random_bytes(8)) . '.pmbak';
                $file->move($uploadDirectory, $temporaryName, true);
                $temporaryUpload = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $temporaryName;
                $path = $temporaryUpload;
            }

            $currentUserId = (int) session('user_id');
            $result = $this->backupService->restoreBackup($path);

            $restoredUser = $this->db->table('users')
                ->where('id', $currentUserId)
                ->where('role', 'admin')
                ->where('status', 'active')
                ->get()
                ->getRowArray();

            if ($restoredUser) {
                session()->set([
                    'account_fingerprint' => \App\Libraries\AccountSession::fingerprint($restoredUser),
                    'user_id' => (int) $restoredUser['id'],
                    'full_name' => (string) $restoredUser['full_name'],
                    'username' => (string) $restoredUser['username'],
                    'role' => (string) $restoredUser['role'],
                    'branch_id' => $restoredUser['branch_id'] !== null ? (int) $restoredUser['branch_id'] : null,
                    'isLoggedIn' => true,
                ]);

                $this->logActivity(
                    'Restored database backup. Restored ' . $result['row_count']
                    . ' rows; safety backup: ' . $result['pre_restore_backup']
                );

                return redirect()->to(site_url('admin/backup-restore'))
                    ->with(
                        'success',
                        'Restore completed successfully. A safety copy of the previous data was saved as '
                        . $result['pre_restore_backup'] . '.'
                    );
            }

            session()->destroy();
            return redirect()->to(site_url('login'))
                ->with('success', 'Restore completed. Please sign in using an administrator account from the restored backup.');
        } catch (Throwable $e) {
            log_message('error', 'Database restore failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('admin/backup-restore'))->withInput()
                ->with('error', $e->getMessage());
        } finally {
            if ($temporaryUpload && is_file($temporaryUpload)) {
                @unlink($temporaryUpload);
            }
        }
    }

    private function verifyCurrentAdminPassword(string $password): bool
    {
        if ($password === '') {
            return false;
        }

        $user = $this->db->table('users')
            ->select('password')
            ->where('id', (int) session('user_id'))
            ->where('role', 'admin')
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        return $user && password_verify($password, (string) $user['password']);
    }

    private function logActivity(string $activity): void
    {
        try {
            $this->activityLogModel->insert([
                'user_id' => (int) session('user_id'),
                'activity' => $activity,
                'log_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('warning', 'Backup activity log failed: {message}', ['message' => $e->getMessage()]);
        }
    }

    private function phpUploadLimitMb(): int
    {
        $toBytes = static function (string $value): int {
            $value = trim($value);
            if ($value === '') {
                return PHP_INT_MAX;
            }
            $unit = strtolower(substr($value, -1));
            $number = (float) $value;
            return match ($unit) {
                'g' => (int) ($number * 1024 * 1024 * 1024),
                'm' => (int) ($number * 1024 * 1024),
                'k' => (int) ($number * 1024),
                default => (int) $number,
            };
        };

        $limit = min($toBytes((string) ini_get('upload_max_filesize')), $toBytes((string) ini_get('post_max_size')));
        return max(1, (int) floor($limit / 1024 / 1024));
    }
}
