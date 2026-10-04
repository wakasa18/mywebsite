<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;
use Throwable;

/**
 * Creates and restores Pharxmaco data backups without shell commands.
 *
 * The custom newline-delimited format is intentionally data-only. A restore
 * is accepted only when the current table structure matches the structure
 * recorded in the backup. This keeps the restore transactional and avoids
 * partially applying DROP/CREATE statements on shared hosting.
 */
class DatabaseBackupService
{
    private const FORMAT = 'pharxmaco-database-backup';
    private const VERSION = 1;
    private const MAX_BACKUP_BYTES = 268435456; // 256 MB
    private const BATCH_SIZE = 200;

    private BaseConnection $db;
    private string $backupDirectory;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
        $this->backupDirectory = rtrim(WRITEPATH, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR;
        $this->ensureBackupDirectory();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBackups(): array
    {
        $files = glob($this->backupDirectory . '*.pmbak') ?: [];
        rsort($files, SORT_STRING);
        $backups = [];

        foreach (array_slice($files, 0, 50) as $path) {
            try {
                $header = $this->readHeader($path);
                $backups[] = [
                    'filename' => basename($path),
                    'created_at' => (string) ($header['created_at'] ?? date(DATE_ATOM, filemtime($path) ?: time())),
                    'label' => (string) ($header['label'] ?? 'manual'),
                    'table_count' => count($header['tables'] ?? []),
                    'row_count' => (int) ($header['row_count'] ?? 0),
                    'size' => (int) (filesize($path) ?: 0),
                ];
            } catch (Throwable $e) {
                log_message('warning', 'Skipped unreadable backup {file}: {message}', [
                    'file' => basename($path),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $backups;
    }

    /**
     * @return array<string, mixed>
     */
    public function createBackup(string $label = 'manual'): array
    {
        $label = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($label))) ?: 'manual';
        $transactionStarted = false;
        $handle = null;
        $temporaryPath = null;
        $path = null;

        try {
            // REPEATABLE READ gives every table query the same database
            // snapshot while allowing the pharmacy to continue operating.
            $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            if (!$this->db->transBegin()) {
                throw new RuntimeException('A consistent backup snapshot could not be started.');
            }
            $transactionStarted = true;

            $metadata = $this->collectTableMetadata(true);
            foreach ($metadata as $table) {
                if (strcasecmp((string) ($table['engine'] ?? ''), 'InnoDB') !== 0) {
                    throw new RuntimeException(
                        'Backup was stopped because table "' . $table['name']
                        . '" is not using InnoDB. A consistent snapshot cannot be guaranteed.'
                    );
                }
            }
            $rowCount = array_sum(array_column($metadata, 'row_count'));
            $filename = sprintf(
                'pharxmaco-%s-%s-%s.pmbak',
                $label,
                date('Ymd-His'),
                bin2hex(random_bytes(3))
            );
            $path = $this->backupDirectory . $filename;
            $temporaryPath = $path . '.part';

            $handle = fopen($temporaryPath, 'wb');
            if ($handle === false) {
                throw new RuntimeException('The backup file could not be created. Check the writable/backups folder permissions.');
            }
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('The backup file could not be locked for writing.');
            }

            $header = [
                'format' => self::FORMAT,
                'version' => self::VERSION,
                'created_at' => date(DATE_ATOM),
                'label' => $label,
                'database' => $this->databaseName(),
                'row_count' => $rowCount,
                'tables' => $metadata,
            ];

            $this->writeJsonLine($handle, $header);

            foreach ($metadata as $table) {
                $tableName = (string) $table['name'];
                $query = $this->db->query('SELECT * FROM ' . $this->quoteIdentifier($tableName));

                while ($row = $query->getUnbufferedRow('array')) {
                    $encodedRow = [];
                    foreach ($row as $column => $value) {
                        $encodedRow[$column] = $this->encodeValue($value);
                    }
                    $this->writeJsonLine($handle, [
                        'table' => $tableName,
                        'row' => $encodedRow,
                    ]);
                }

                $query->freeResult();
            }

            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);
            $handle = null;

            if (!$this->db->transCommit()) {
                throw new RuntimeException('The consistent backup snapshot could not be completed.');
            }
            $transactionStarted = false;

            if (!rename($temporaryPath, $path)) {
                throw new RuntimeException('The completed backup could not be saved.');
            }

            return [
                'filename' => $filename,
                'path' => $path,
                'created_at' => $header['created_at'],
                'label' => $label,
                'table_count' => count($metadata),
                'row_count' => $rowCount,
                'size' => (int) (filesize($path) ?: 0),
                'checksum' => hash_file('sha256', $path) ?: '',
            ];
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->db->transRollback();
            }
            if (is_resource($handle)) {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
            if ($temporaryPath !== null) {
                @unlink($temporaryPath);
            }
            throw $e;
        }
    }

    public function resolveBackupPath(string $filename): string
    {
        $filename = basename($filename);
        if (!preg_match('/^pharxmaco-[a-z0-9_-]+-\d{8}-\d{6}-[a-f0-9]{6}\.pmbak$/i', $filename)) {
            throw new RuntimeException('Invalid backup filename.');
        }

        $path = $this->backupDirectory . $filename;
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('The selected backup file was not found.');
        }

        return $path;
    }

    public function deleteBackup(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);
        if (!unlink($path)) {
            throw new RuntimeException('The backup could not be deleted.');
        }
    }

    /**
     * Restores all application table data inside one transaction.
     *
     * @return array<string, mixed>
     */
    public function restoreBackup(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('The backup file cannot be read.');
        }

        $size = (int) (filesize($path) ?: 0);
        if ($size <= 0 || $size > self::MAX_BACKUP_BYTES) {
            throw new RuntimeException('The backup file is empty or larger than the supported 256 MB limit.');
        }

        $header = $this->readHeader($path);
        $this->validateHeader($header);
        $currentMetadata = $this->collectTableMetadata(false);
        $this->assertCompatibleSchema($header['tables'], $currentMetadata);

        foreach ($currentMetadata as $table) {
            if (strcasecmp((string) ($table['engine'] ?? ''), 'InnoDB') !== 0) {
                throw new RuntimeException(
                    'Restore was stopped because table "' . $table['name'] . '" is not using InnoDB. '
                    . 'Transactional rollback cannot be guaranteed.'
                );
            }
        }

        // Always preserve the current database before replacing any rows.
        $preRestoreBackup = $this->createBackup('pre-restore');

        $expectedCounts = [];
        $validTables = [];
        foreach ($header['tables'] as $table) {
            $name = (string) $table['name'];
            $expectedCounts[$name] = (int) ($table['row_count'] ?? 0);
            $validTables[$name] = true;
        }

        $insertedCounts = array_fill_keys(array_keys($validTables), 0);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The backup file could not be opened for restore.');
        }

        // Skip the header line.
        fgets($handle);
        $currentTable = null;
        $batch = [];
        $lineNumber = 1;

        try {
            if (!$this->db->transBegin()) {
                throw new RuntimeException('The database restore transaction could not be started.');
            }

            $this->db->query('SET FOREIGN_KEY_CHECKS=0');

            // DELETE is transactional for InnoDB; unlike TRUNCATE, it can be
            // rolled back if any row fails to restore.
            foreach (array_reverse(array_keys($validTables)) as $tableName) {
                $result = $this->db->query('DELETE FROM ' . $this->quoteIdentifier($tableName));
                if ($result === false) {
                    throw new RuntimeException('Could not clear table "' . $tableName . '".');
                }
            }

            while (($line = fgets($handle, 8388608)) !== false) {
                $lineNumber++;
                if (!str_ends_with($line, "\n") && !feof($handle)) {
                    throw new RuntimeException('A backup record is larger than the supported 8 MB line limit.');
                }
                if (trim($line) === '') {
                    continue;
                }

                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $tableName = (string) ($record['table'] ?? '');
                $row = $record['row'] ?? null;

                if (!isset($validTables[$tableName]) || !is_array($row)) {
                    throw new RuntimeException('Invalid data record at backup line ' . $lineNumber . '.');
                }

                if ($currentTable !== null && $tableName !== $currentTable) {
                    $this->flushBatch($currentTable, $batch, $insertedCounts);
                    $batch = [];
                }
                $currentTable = $tableName;

                $decodedRow = [];
                foreach ($row as $column => $value) {
                    $decodedRow[$column] = $this->decodeValue($value);
                }
                $batch[] = $decodedRow;

                if (count($batch) >= self::BATCH_SIZE) {
                    $this->flushBatch($currentTable, $batch, $insertedCounts);
                    $batch = [];
                }
            }

            if ($currentTable !== null && $batch !== []) {
                $this->flushBatch($currentTable, $batch, $insertedCounts);
            }

            foreach ($expectedCounts as $tableName => $expected) {
                if (($insertedCounts[$tableName] ?? 0) !== $expected) {
                    throw new RuntimeException(
                        'Restore verification failed for "' . $tableName . '": expected '
                        . $expected . ' rows but restored ' . ($insertedCounts[$tableName] ?? 0) . '.'
                    );
                }
            }

            if (isset($validTables['users'])) {
                $activeAdmins = $this->db->table('users')
                    ->where('role', 'admin')
                    ->where('status', 'active')
                    ->countAllResults();
                if ($activeAdmins < 1) {
                    throw new RuntimeException('Restore was stopped because the backup has no active administrator account.');
                }
            }

            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
            if (!$this->db->transCommit()) {
                throw new RuntimeException('The restored data could not be committed.');
            }
        } catch (Throwable $e) {
            $this->db->transRollback();
            try {
                $this->db->query('SET FOREIGN_KEY_CHECKS=1');
            } catch (Throwable $ignored) {
            }
            fclose($handle);
            throw new RuntimeException(
                'Restore failed and the current database was kept unchanged. ' . $e->getMessage(),
                0,
                $e
            );
        }

        fclose($handle);

        return [
            'table_count' => count($validTables),
            'row_count' => array_sum($insertedCounts),
            'pre_restore_backup' => $preRestoreBackup['filename'],
            'source_created_at' => (string) ($header['created_at'] ?? ''),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectTableMetadata(bool $includeCounts): array
    {
        $rows = $this->db->query('SHOW TABLE STATUS')->getResultArray();
        $tables = [];

        foreach ($rows as $row) {
            $name = (string) ($row['Name'] ?? '');
            $engine = (string) ($row['Engine'] ?? '');
            // SHOW TABLE STATUS can include views on some MySQL versions.
            // Backups intentionally contain base tables only.
            if ($name === '' || $engine === '') {
                continue;
            }

            $createRow = $this->db->query('SHOW CREATE TABLE ' . $this->quoteIdentifier($name))->getRowArray();
            $createSql = '';
            if ($createRow) {
                $values = array_values($createRow);
                $createSql = (string) ($values[1] ?? '');
            }

            $tables[] = [
                'name' => $name,
                'engine' => $engine,
                'schema_hash' => hash('sha256', $this->normalizeCreateSql($createSql)),
                'row_count' => $includeCounts
                    ? (int) (($this->db->query(
                        'SELECT COUNT(*) AS total FROM ' . $this->quoteIdentifier($name)
                    )->getRowArray()['total'] ?? 0))
                    : 0,
            ];
        }

        usort($tables, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
        return $tables;
    }

    /**
     * @param array<int, array<string, mixed>> $backupTables
     * @param array<int, array<string, mixed>> $currentTables
     */
    private function assertCompatibleSchema(array $backupTables, array $currentTables): void
    {
        $currentByName = [];
        foreach ($currentTables as $table) {
            $currentByName[(string) $table['name']] = $table;
        }

        if (count($backupTables) !== count($currentTables)) {
            throw new RuntimeException('The backup belongs to a different database structure. Run the same migrations before restoring it.');
        }

        foreach ($backupTables as $table) {
            $name = (string) ($table['name'] ?? '');
            if (!isset($currentByName[$name])) {
                throw new RuntimeException('The current database is missing table "' . $name . '" required by this backup.');
            }
            if (!hash_equals(
                (string) ($table['schema_hash'] ?? ''),
                (string) ($currentByName[$name]['schema_hash'] ?? '')
            )) {
                throw new RuntimeException(
                    'Table "' . $name . '" does not match the backup structure. '
                    . 'Run the correct migrations or choose a compatible backup.'
                );
            }
        }
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $data
     */
    private function writeJsonLine($handle, array $data): void
    {
        $line = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        if (fwrite($handle, $line) === false) {
            throw new RuntimeException('The backup file could not be written completely.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readHeader(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The backup file could not be opened.');
        }
        $line = fgets($handle, 2097152);
        fclose($handle);

        if ($line === false || trim($line) === '') {
            throw new RuntimeException('The backup header is missing.');
        }

        $header = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($header)) {
            throw new RuntimeException('The backup header is invalid.');
        }
        $this->validateHeader($header);
        return $header;
    }

    /**
     * @param array<string, mixed> $header
     */
    private function validateHeader(array $header): void
    {
        if (($header['format'] ?? null) !== self::FORMAT || (int) ($header['version'] ?? 0) !== self::VERSION) {
            throw new RuntimeException('This is not a supported Pharxmaco backup file.');
        }
        if (!isset($header['tables']) || !is_array($header['tables']) || $header['tables'] === []) {
            throw new RuntimeException('The backup does not contain a table list.');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     * @param array<string, int> $insertedCounts
     */
    private function flushBatch(string $tableName, array $batch, array &$insertedCounts): void
    {
        if ($batch === []) {
            return;
        }

        $inserted = $this->db->table($tableName)->insertBatch($batch, null, self::BATCH_SIZE);
        if ($inserted === false || (int) $inserted !== count($batch)) {
            throw new RuntimeException('Could not restore all rows for table "' . $tableName . '".');
        }
        $insertedCounts[$tableName] += (int) $inserted;
    }

    /**
     * @return mixed
     */
    private function encodeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('//u', $value) === 1) {
            return $value;
        }

        return ['__pmbak_base64__' => base64_encode($value)];
    }

    /**
     * @return mixed
     */
    private function decodeValue($value)
    {
        if (is_array($value) && array_key_exists('__pmbak_base64__', $value)) {
            $decoded = base64_decode((string) $value['__pmbak_base64__'], true);
            if ($decoded === false) {
                throw new RuntimeException('A binary value in the backup is invalid.');
            }
            return $decoded;
        }
        return $value;
    }

    private function normalizeCreateSql(string $sql): string
    {
        $sql = preg_replace('/AUTO_INCREMENT=\d+\s*/i', '', $sql) ?? $sql;
        $sql = preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql);
        return $sql;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function databaseName(): string
    {
        if (method_exists($this->db, 'getDatabase')) {
            return (string) $this->db->getDatabase();
        }
        return (string) ($this->db->database ?? '');
    }

    private function ensureBackupDirectory(): void
    {
        if (!is_dir($this->backupDirectory) && !mkdir($this->backupDirectory, 0775, true) && !is_dir($this->backupDirectory)) {
            throw new RuntimeException('Could not create writable/backups. Check folder permissions.');
        }
        if (!is_writable($this->backupDirectory)) {
            throw new RuntimeException('The writable/backups folder is not writable.');
        }
    }
}
