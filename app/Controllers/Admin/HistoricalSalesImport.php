<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;
use DateTime;
use RuntimeException;
use Throwable;

class HistoricalSalesImport extends BaseController
{
    private const MAX_FILE_BYTES = 5242880; // 5 MB
    private const MAX_DATA_ROWS = 20000;
    private const STAGE_TTL_SECONDS = 86400;

    protected $db;
    protected $activityLogModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->activityLogModel = new ActivityLogModel();
    }

    public function index()
    {
        $this->cleanExpiredStageFiles();

        return view('admin/sales_import/index', $this->pageData());
    }

    public function preview()
    {
        $this->cleanExpiredStageFiles();

        $file = $this->request->getFile('sales_file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'Choose a valid CSV file before continuing.');
        }

        if ((int) $file->getSize() <= 0 || (int) $file->getSize() > self::MAX_FILE_BYTES) {
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'The CSV file must be smaller than 5 MB.');
        }

        $extension = strtolower((string) $file->getClientExtension());
        $mime = strtolower((string) $file->getClientMimeType());
        $allowedMimes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ];

        if ($extension !== 'csv' || ($mime !== '' && !in_array($mime, $allowedMimes, true))) {
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'Only CSV files are supported. In Excel, use Save As → CSV UTF-8.');
        }

        $token = bin2hex(random_bytes(32));
        $stageDir = $this->stageDirectory();
        $stagedName = $token . '.csv';

        try {
            $file->move($stageDir, $stagedName, true);
        } catch (Throwable $e) {
            log_message('error', 'Historical sales upload failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'The uploaded file could not be saved. Check the writable folder permissions.');
        }

        $path = $stageDir . DIRECTORY_SEPARATOR . $stagedName;
        $originalName = basename((string) $file->getClientName());

        try {
            $preview = $this->parseCsv($path);
        } catch (Throwable $e) {
            @unlink($path);
            log_message('error', 'Historical sales preview failed: {message}', ['message' => $e->getMessage()]);
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', $e->getMessage());
        }

        session()->set($this->sessionKey($token), [
            'path' => $path,
            'original_name' => $originalName,
            'sha256' => hash_file('sha256', $path),
            'created_at' => time(),
        ]);

        return view('admin/sales_import/index', $this->pageData([
            'preview' => $preview,
            'importToken' => $token,
            'uploadedFileName' => $originalName,
        ]));
    }

    public function confirm()
    {
        $token = strtolower(trim((string) $this->request->getPost('import_token')));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'The import session is invalid. Upload the CSV file again.');
        }

        if ((string) $this->request->getPost('confirm_import') !== '1') {
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'Confirm that historical sales must not change the current stock.');
        }

        $stage = session()->get($this->sessionKey($token));
        if (!is_array($stage) || empty($stage['path']) || !is_file($stage['path'])) {
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'The import preview expired. Upload the CSV file again.');
        }

        if ((int) ($stage['created_at'] ?? 0) < time() - self::STAGE_TTL_SECONDS) {
            $this->removeStage($token, $stage);
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'The import preview expired after 24 hours. Upload the CSV file again.');
        }

        if (!hash_equals((string) ($stage['sha256'] ?? ''), (string) hash_file('sha256', $stage['path']))) {
            $this->removeStage($token, $stage);
            return redirect()->to(site_url('admin/sales-import'))
                ->with('error', 'The staged CSV file changed unexpectedly. Upload it again.');
        }

        try {
            $preview = $this->parseCsv($stage['path']);
        } catch (Throwable $e) {
            return view('admin/sales_import/index', $this->pageData([
                'pageError' => $e->getMessage(),
            ]));
        }

        if (empty($preview['valid_invoices'])) {
            return view('admin/sales_import/index', $this->pageData([
                'preview' => $preview,
                'importToken' => $token,
                'uploadedFileName' => (string) ($stage['original_name'] ?? 'uploaded.csv'),
                'pageError' => 'There are no valid invoices to import. Correct the CSV errors and upload it again.',
            ]));
        }

        $this->db->transBegin();
        $importedSales = 0;
        $importedItems = 0;

        try {
            foreach ($preview['valid_invoices'] as $invoice) {
                $saleDate = $invoice['sale_date'];
                $saleData = [
                    'invoice_no' => $invoice['invoice_no'],
                    'checkout_token_hash' => null,
                    'user_id' => $invoice['user_id'],
                    'branch_id' => $invoice['branch_id'],
                    'discount_id' => null,
                    'total_amount' => $invoice['gross_total'],
                    'discount_amount' => $invoice['discount_total'],
                    'final_total' => $invoice['final_total'],
                    'payment_method' => $invoice['payment_method'],
                    'reference_no' => $invoice['reference_no'] !== '' ? $invoice['reference_no'] : null,
                    'amount_paid' => $invoice['amount_paid'],
                    'change_amount' => $invoice['change_amount'],
                    'status' => 'completed',
                    'notes' => $invoice['notes'] !== '' ? $invoice['notes'] : 'Imported historical sale',
                    'sale_date' => $saleDate,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ];

                if (!$this->db->table('sales')->insert($saleData)) {
                    throw new RuntimeException('Failed to save invoice ' . $invoice['invoice_no'] . '.');
                }

                $saleId = (int) $this->db->insertID();
                if ($saleId <= 0) {
                    throw new RuntimeException('The new sale ID was not created for ' . $invoice['invoice_no'] . '.');
                }

                foreach ($invoice['items'] as $item) {
                    $itemData = [
                        'sale_id' => $saleId,
                        'product_id' => $item['product_id'],
                        'product_name_snapshot' => $item['product_name'],
                        'cost_price_at_sale' => $item['cost_price'],
                        'quantity' => $item['quantity'],
                        'price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'discount_applied' => $item['line_discount'],
                        'profit' => $item['profit'],
                        'created_at' => $saleDate,
                    ];

                    if (!$this->db->table('sale_items')->insert($itemData)) {
                        throw new RuntimeException('Failed to save an item for invoice ' . $invoice['invoice_no'] . '.');
                    }
                    $importedItems++;
                }

                $importedSales++;
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('The database rejected one or more imported records.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Historical sales import failed: {message}', ['message' => $e->getMessage()]);

            return view('admin/sales_import/index', $this->pageData([
                'preview' => $preview,
                'importToken' => $token,
                'uploadedFileName' => (string) ($stage['original_name'] ?? 'uploaded.csv'),
                'pageError' => 'Nothing was imported. ' . $e->getMessage(),
            ]));
        }

        $this->activityLogModel->insert([
            'user_id' => (int) session('user_id'),
            'activity' => sprintf(
                'Imported %d historical sales with %d items from %s without changing current stock',
                $importedSales,
                $importedItems,
                basename((string) ($stage['original_name'] ?? 'CSV file'))
            ),
            'log_time' => date('Y-m-d H:i:s'),
        ]);

        $this->removeStage($token, $stage);

        $skipped = (int) ($preview['invalid_invoice_count'] ?? 0);
        $message = sprintf(
            'Imported %d historical sale%s with %d item%s. Current stock was not changed.',
            $importedSales,
            $importedSales === 1 ? '' : 's',
            $importedItems,
            $importedItems === 1 ? '' : 's'
        );
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' invalid invoice' . ($skipped === 1 ? ' was' : 's were') . ' skipped.';
        }

        return redirect()->to(site_url('cashier/sales/history'))->with('success', $message);
    }

    public function cancel()
    {
        $token = strtolower(trim((string) $this->request->getPost('import_token')));
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            $stage = session()->get($this->sessionKey($token));
            if (is_array($stage)) {
                $this->removeStage($token, $stage);
            }
        }

        return redirect()->to(site_url('admin/sales-import'))
            ->with('success', 'The pending import was cancelled.');
    }

    public function template()
    {
        $sample = $this->db->table('branch_products bp')
            ->select('b.branch_code, p.sku, p.product_name, bp.price, bp.cost_price')
            ->join('branches b', 'b.id = bp.branch_id')
            ->join('products p', 'p.id = bp.product_id')
            ->where('p.sku !=', '')
            ->orderBy('b.id', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get(2)
            ->getResultArray();

        $user = $this->db->table('users')
            ->select('username')
            ->whereIn('role', ['admin', 'cashier'])
            ->orderBy('role', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        $username = (string) ($user['username'] ?? 'admin');
        $first = $sample[0] ?? [
            'branch_code' => 'MAIN',
            'sku' => 'PRODUCT-SKU',
            'product_name' => 'Sample Product',
            'price' => '10.00',
            'cost_price' => '7.00',
        ];
        $second = $sample[1] ?? $first;
        $date = date('Y-m-d', strtotime('-30 days')) . ' 10:30:00';

        $rows = [
            ['invoice_no', 'sale_date', 'branch_code', 'cashier_username', 'product_sku', 'product_name', 'quantity', 'unit_price', 'cost_price', 'line_discount', 'payment_method', 'reference_no', 'amount_paid', 'notes'],
            ['OLD-000001', $date, $first['branch_code'], $username, $first['sku'], $first['product_name'], '2', number_format((float) $first['price'], 2, '.', ''), number_format((float) $first['cost_price'], 2, '.', ''), '0.00', 'cash', '', number_format((float) $first['price'] * 2 + (float) $second['price'], 2, '.', ''), 'Imported historical sale'],
            ['OLD-000001', $date, $first['branch_code'], $username, $second['sku'], $second['product_name'], '1', number_format((float) $second['price'], 2, '.', ''), number_format((float) $second['cost_price'], 2, '.', ''), '0.00', 'cash', '', number_format((float) $first['price'] * 2 + (float) $second['price'], 2, '.', ''), 'Imported historical sale'],
        ];

        return $this->csvResponse('historical-sales-import-template.csv', $rows);
    }

    public function reference()
    {
        $records = $this->db->table('branch_products bp')
            ->select('b.branch_code, b.branch_name, p.sku, p.product_name, bp.price, bp.cost_price, bp.status AS branch_product_status, p.status AS product_status')
            ->join('branches b', 'b.id = bp.branch_id')
            ->join('products p', 'p.id = bp.product_id')
            ->where('p.deleted_at', null)->where('p.is_deleted', 0)->where('p.is_permanently_deleted', 0)
            ->orderBy('b.branch_name', 'ASC')
            ->orderBy('p.product_name', 'ASC')
            ->get()
            ->getResultArray();

        $rows = [[
            'branch_code',
            'branch_name',
            'product_sku',
            'product_name',
            'default_unit_price',
            'default_cost_price',
            'product_status',
            'branch_product_status',
        ]];

        foreach ($records as $record) {
            $rows[] = [
                $record['branch_code'],
                $record['branch_name'],
                $record['sku'],
                $record['product_name'],
                number_format((float) $record['price'], 2, '.', ''),
                number_format((float) $record['cost_price'], 2, '.', ''),
                $record['product_status'],
                $record['branch_product_status'],
            ];
        }

        return $this->csvResponse('product-sku-lookup-reference-only-do-not-upload.csv', $rows);
    }

    private function pageData(array $extra = []): array
    {
        $branches = $this->db->table('branches')
            ->select('branch_code, branch_name, status')
            ->orderBy('branch_name', 'ASC')
            ->get()
            ->getResultArray();

        $users = $this->db->table('users')
            ->select('username, full_name, role, status')
            ->whereIn('role', ['admin', 'cashier'])
            ->orderBy('full_name', 'ASC')
            ->get()
            ->getResultArray();

        return array_merge([
            'branches' => $branches,
            'importUsers' => $users,
            'preview' => null,
            'importToken' => null,
            'uploadedFileName' => null,
            'pageError' => null,
        ], $extra);
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The uploaded CSV file could not be opened.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new RuntimeException('The CSV file is empty.');
            }
            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $rawHeader = fgetcsv($handle, 0, $delimiter, '"', '\\');
            if ($rawHeader === false) {
                throw new RuntimeException('The CSV header could not be read.');
            }

            $headerMap = $this->buildHeaderMap($rawHeader);

            // The downloadable product/SKU reference is a lookup file, not a sales import file.
            // Detect it early so the user receives a useful instruction instead of a generic
            // missing-columns message.
            $referenceHeaders = [
                'branch_code',
                'branch_name',
                'product_sku',
                'product_name',
                'default_unit_price',
                'default_cost_price',
            ];
            $looksLikeReference = count(array_filter(
                $referenceHeaders,
                static fn ($field) => isset($headerMap[$field])
            )) >= 5;

            if ($looksLikeReference && !isset($headerMap['invoice_no'])) {
                throw new RuntimeException(
                    'You uploaded the Product/SKU Lookup file. That file is only for checking valid branch codes and product SKUs; it cannot be imported as sales. Download the Sales CSV Template, copy the needed codes from the lookup file, complete the invoice, date, cashier, quantity, price, and payment columns, then upload the completed template.'
                );
            }

            $required = [
                'invoice_no',
                'sale_date',
                'branch_code',
                'cashier_username',
                'product_sku',
                'quantity',
                'unit_price',
                'payment_method',
            ];
            $missing = array_values(array_filter($required, static fn ($field) => !isset($headerMap[$field])));
            if ($missing !== []) {
                throw new RuntimeException('Missing required column' . (count($missing) > 1 ? 's' : '') . ': ' . implode(', ', $missing) . '. Download the template and keep its header row.');
            }

            $rawGroups = [];
            $errors = [];
            $rowCount = 0;
            $lineNumber = 1;

            while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
                $lineNumber++;
                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                $rowCount++;
                if ($rowCount > self::MAX_DATA_ROWS) {
                    throw new RuntimeException('The CSV contains more than ' . number_format(self::MAX_DATA_ROWS) . ' data rows. Split it into smaller files.');
                }

                $data = $this->canonicalRow($row, $headerMap);
                $invoiceNo = trim((string) ($data['invoice_no'] ?? ''));
                if ($invoiceNo === '') {
                    $errors[] = ['row' => $lineNumber, 'invoice' => '-', 'message' => 'Invoice number is required.'];
                    continue;
                }

                $groupKey = mb_strtolower($invoiceNo);
                if (!isset($rawGroups[$groupKey])) {
                    $rawGroups[$groupKey] = [
                        'invoice_no' => $invoiceNo,
                        'rows' => [],
                    ];
                }
                $rawGroups[$groupKey]['rows'][] = [
                    'row_number' => $lineNumber,
                    'data' => $data,
                ];
            }
        } finally {
            fclose($handle);
        }

        if ($rowCount === 0) {
            throw new RuntimeException('The CSV file has a header but no sales rows.');
        }

        $lookup = $this->buildLookupData(array_column($rawGroups, 'invoice_no'));
        $validInvoices = [];
        $warnings = [];
        $invalidInvoices = [];

        foreach ($rawGroups as $groupKey => $group) {
            $invoiceNo = (string) $group['invoice_no'];
            $rows = $group['rows'];
            $invoiceErrors = [];
            $invoiceWarnings = [];
            $invoice = $this->validateInvoiceGroup($invoiceNo, $rows, $lookup, $invoiceErrors, $invoiceWarnings);

            if ($invoiceErrors !== []) {
                $invalidInvoices[$invoiceNo] = true;
                foreach ($invoiceErrors as $error) {
                    $errors[] = $error;
                }
                continue;
            }

            foreach ($invoiceWarnings as $warning) {
                $warnings[] = $warning;
            }
            $validInvoices[] = $invoice;
        }

        usort($validInvoices, static fn (array $a, array $b) => strcmp($a['sale_date'], $b['sale_date']));

        return [
            'total_rows' => $rowCount,
            'total_invoice_count' => count($rawGroups),
            'valid_invoice_count' => count($validInvoices),
            'valid_item_count' => array_sum(array_map(static fn (array $invoice) => count($invoice['items']), $validInvoices)),
            'invalid_invoice_count' => count($invalidInvoices) + count(array_unique(array_column(array_filter($errors, static fn ($e) => ($e['invoice'] ?? '-') === '-'), 'row'))),
            'valid_invoices' => $validInvoices,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function validateInvoiceGroup(string $invoiceNo, array $rows, array $lookup, array &$errors, array &$warnings): array
    {
        $firstRowNumber = (int) ($rows[0]['row_number'] ?? 0);
        if (mb_strlen($invoiceNo) > 50) {
            $errors[] = ['row' => $firstRowNumber, 'invoice' => $invoiceNo, 'message' => 'Invoice number must not exceed 50 characters.'];
        }

        if (isset($lookup['existing_invoices'][mb_strtolower($invoiceNo)])) {
            $errors[] = ['row' => $firstRowNumber, 'invoice' => $invoiceNo, 'message' => 'This invoice already exists in Sales History.'];
        }

        $items = [];
        $metaValues = [
            'sale_date' => [],
            'branch_code' => [],
            'cashier_username' => [],
            'payment_method' => [],
            'reference_no' => [],
            'amount_paid' => [],
            'notes' => [],
        ];

        foreach ($rows as $entry) {
            $rowNumber = (int) $entry['row_number'];
            $data = $entry['data'];

            $date = $this->parseDate((string) ($data['sale_date'] ?? ''));
            if ($date === null) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Sale date is invalid. Use YYYY-MM-DD HH:MM:SS or a supported Excel date format.'];
            } elseif (strtotime($date) > time() + 300) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Sale date cannot be in the future.'];
            } else {
                $metaValues['sale_date'][] = $date;
            }

            $branchCode = strtoupper(trim((string) ($data['branch_code'] ?? '')));
            $metaValues['branch_code'][] = $branchCode;
            $branch = $lookup['branches'][$branchCode] ?? null;
            if (!$branch) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Branch code "' . $branchCode . '" was not found.'];
            }

            $usernameKey = mb_strtolower(trim((string) ($data['cashier_username'] ?? '')));
            $metaValues['cashier_username'][] = $usernameKey;
            $user = $lookup['users'][$usernameKey] ?? null;
            if (!$user) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Cashier username "' . trim((string) ($data['cashier_username'] ?? '')) . '" was not found.'];
            }

            $paymentMethod = $this->normalizePaymentMethod((string) ($data['payment_method'] ?? ''));
            $metaValues['payment_method'][] = $paymentMethod;
            if (!in_array($paymentMethod, ['cash', 'gcash', 'card'], true)) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Payment method must be cash, gcash, or card.'];
            }

            $referenceNo = trim((string) ($data['reference_no'] ?? ''));
            if ($referenceNo !== '') {
                $metaValues['reference_no'][] = $referenceNo;
            }
            $amountPaidRaw = trim((string) ($data['amount_paid'] ?? ''));
            if ($amountPaidRaw !== '') {
                $metaValues['amount_paid'][] = $amountPaidRaw;
            }
            $notes = trim((string) ($data['notes'] ?? ''));
            if ($notes !== '') {
                $metaValues['notes'][] = $notes;
            }

            $sku = strtoupper(trim((string) ($data['product_sku'] ?? '')));
            $product = $lookup['products'][$sku] ?? null;
            if (isset($lookup['ambiguous_products'][$sku])) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Product SKU "' . $sku . '" is used by more than one product. Make the SKU unique before importing.'];
                $product = null;
            } elseif (!$product) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Product SKU "' . $sku . '" was not found.'];
            }

            $quantityRaw = trim((string) ($data['quantity'] ?? ''));
            $quantity = filter_var($quantityRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            if ($quantity === false) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Quantity must be a whole number greater than zero.'];
                $quantity = 0;
            }

            $unitPrice = $this->parseMoney((string) ($data['unit_price'] ?? ''));
            if ($unitPrice === null || $unitPrice <= 0) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Unit price must be greater than zero.'];
                $unitPrice = 0.0;
            }

            $lineDiscountRaw = trim((string) ($data['line_discount'] ?? ''));
            $lineDiscount = $lineDiscountRaw === '' ? 0.0 : $this->parseMoney($lineDiscountRaw);
            if ($lineDiscount === null || $lineDiscount < 0) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Line discount must be zero or a positive amount.'];
                $lineDiscount = 0.0;
            }

            $branchProduct = null;
            if ($branch && $product) {
                $branchProduct = $lookup['branch_products'][$branch['id'] . ':' . $product['id']] ?? null;
                if (!$branchProduct) {
                    $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'SKU "' . $sku . '" is not assigned to branch ' . $branchCode . '.'];
                }
            }

            $costRaw = trim((string) ($data['cost_price'] ?? ''));
            $costPrice = $costRaw === '' ? null : $this->parseMoney($costRaw);
            if ($costPrice !== null && $costPrice < 0) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Cost price cannot be negative.'];
                $costPrice = 0.0;
            }
            if ($costPrice === null) {
                $costPrice = (float) ($branchProduct['cost_price'] ?? $product['cost_price'] ?? 0);
            }
            if ($product && $costPrice == 0.0) {
                $warnings[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Cost price for SKU ' . $sku . ' is zero. Gross profit reports may be less accurate for this row.'];
            }

            $subtotal = round($unitPrice * (int) $quantity, 2);
            if ($lineDiscount > $subtotal) {
                $errors[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Line discount cannot be greater than the item subtotal.'];
            }

            if ($branch && $user && $user['role'] === 'cashier' && !empty($user['branch_id']) && (int) $user['branch_id'] !== (int) $branch['id']) {
                $warnings[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Cashier ' . $user['username'] . ' is currently assigned to another branch. The historical sale will still use ' . $branchCode . '.'];
            }
            if ($product && (string) ($product['status'] ?? '') !== 'active') {
                $warnings[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'SKU ' . $sku . ' is currently inactive, but it can still be used for a historical sale.'];
            }
            if ($branchProduct && (string) ($branchProduct['status'] ?? '') !== 'active') {
                $warnings[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'SKU ' . $sku . ' is currently inactive in branch ' . $branchCode . '.'];
            }

            $providedName = trim((string) ($data['product_name'] ?? ''));
            if ($providedName !== '' && $product && mb_strtolower($providedName) !== mb_strtolower((string) $product['product_name'])) {
                $warnings[] = ['row' => $rowNumber, 'invoice' => $invoiceNo, 'message' => 'Product name in the CSV does not match SKU ' . $sku . '. The system name will be used.'];
            }

            if ($product && $branchProduct && $quantity > 0 && $unitPrice > 0 && $lineDiscount <= $subtotal) {
                $profit = round((($unitPrice - (float) $costPrice) * (int) $quantity) - (float) $lineDiscount, 2);
                $items[] = [
                    'row_number' => $rowNumber,
                    'product_id' => (int) $product['id'],
                    'product_sku' => $sku,
                    'product_name' => (string) $product['product_name'],
                    'quantity' => (int) $quantity,
                    'unit_price' => round($unitPrice, 2),
                    'cost_price' => round((float) $costPrice, 2),
                    'subtotal' => $subtotal,
                    'line_discount' => round((float) $lineDiscount, 2),
                    'profit' => $profit,
                ];
            }
        }

        foreach (['sale_date', 'branch_code', 'cashier_username', 'payment_method'] as $field) {
            $unique = array_values(array_unique(array_filter($metaValues[$field], static fn ($value) => $value !== '')));
            if (count($unique) > 1) {
                $errors[] = ['row' => $firstRowNumber, 'invoice' => $invoiceNo, 'message' => 'All rows for one invoice must use the same ' . str_replace('_', ' ', $field) . '.'];
            }
        }

        foreach (['reference_no', 'amount_paid', 'notes'] as $field) {
            $unique = array_values(array_unique($metaValues[$field]));
            if (count($unique) > 1) {
                $errors[] = ['row' => $firstRowNumber, 'invoice' => $invoiceNo, 'message' => 'All non-empty ' . str_replace('_', ' ', $field) . ' values for one invoice must match.'];
            }
        }

        $saleDate = $metaValues['sale_date'][0] ?? '';
        $branchCode = $metaValues['branch_code'][0] ?? '';
        $usernameKey = $metaValues['cashier_username'][0] ?? '';
        $paymentMethod = $metaValues['payment_method'][0] ?? '';
        $branch = $lookup['branches'][$branchCode] ?? null;
        $user = $lookup['users'][$usernameKey] ?? null;

        $grossTotal = round(array_sum(array_column($items, 'subtotal')), 2);
        $discountTotal = round(array_sum(array_column($items, 'line_discount')), 2);
        $finalTotal = round($grossTotal - $discountTotal, 2);

        $amountPaidRaw = $metaValues['amount_paid'][0] ?? '';
        $amountPaid = $amountPaidRaw === '' ? $finalTotal : $this->parseMoney($amountPaidRaw);
        if ($amountPaid === null || $amountPaid < $finalTotal) {
            $errors[] = ['row' => $firstRowNumber, 'invoice' => $invoiceNo, 'message' => 'Amount paid must be at least the final total of ₱' . number_format($finalTotal, 2) . '.'];
            $amountPaid = $finalTotal;
        }

        if ($items === []) {
            $errors[] = ['row' => $firstRowNumber, 'invoice' => $invoiceNo, 'message' => 'The invoice has no valid product items.'];
        }

        return [
            'invoice_no' => $invoiceNo,
            'sale_date' => $saleDate,
            'branch_id' => (int) ($branch['id'] ?? 0),
            'branch_code' => $branchCode,
            'branch_name' => (string) ($branch['branch_name'] ?? ''),
            'user_id' => (int) ($user['id'] ?? 0),
            'cashier_username' => (string) ($user['username'] ?? ''),
            'cashier_name' => (string) ($user['full_name'] ?? ''),
            'payment_method' => $paymentMethod,
            'reference_no' => (string) ($metaValues['reference_no'][0] ?? ''),
            'amount_paid' => round((float) $amountPaid, 2),
            'change_amount' => $paymentMethod === 'cash' ? round(max(0, (float) $amountPaid - $finalTotal), 2) : 0.0,
            'notes' => (string) ($metaValues['notes'][0] ?? ''),
            'gross_total' => $grossTotal,
            'discount_total' => $discountTotal,
            'final_total' => $finalTotal,
            'items' => $items,
        ];
    }

    private function buildLookupData(array $invoiceNumbers): array
    {
        $branches = [];
        foreach ($this->db->table('branches')->get()->getResultArray() as $branch) {
            $branches[strtoupper(trim((string) $branch['branch_code']))] = $branch;
        }

        $users = [];
        foreach ($this->db->table('users')->whereIn('role', ['admin', 'cashier'])->get()->getResultArray() as $user) {
            $users[mb_strtolower(trim((string) $user['username']))] = $user;
        }

        $products = [];
        $ambiguousProducts = [];
        foreach ($this->db->table('products')->where('deleted_at', null)->where('is_deleted', 0)->where('is_permanently_deleted', 0)->get()->getResultArray() as $product) {
            $sku = strtoupper(trim((string) $product['sku']));
            if ($sku === '') {
                continue;
            }
            if (isset($products[$sku])) {
                $ambiguousProducts[$sku] = true;
                continue;
            }
            $products[$sku] = $product;
        }

        $branchProducts = [];
        foreach ($this->db->table('branch_products')->get()->getResultArray() as $branchProduct) {
            $branchProducts[$branchProduct['branch_id'] . ':' . $branchProduct['product_id']] = $branchProduct;
        }

        $existingInvoices = [];
        foreach (array_chunk(array_values(array_unique($invoiceNumbers)), 500) as $chunk) {
            if ($chunk === []) {
                continue;
            }
            $records = $this->db->table('sales')
                ->select('invoice_no')
                ->whereIn('invoice_no', $chunk)
                ->get()
                ->getResultArray();
            foreach ($records as $record) {
                $existingInvoices[mb_strtolower((string) $record['invoice_no'])] = true;
            }
        }

        return [
            'branches' => $branches,
            'users' => $users,
            'products' => $products,
            'ambiguous_products' => $ambiguousProducts,
            'branch_products' => $branchProducts,
            'existing_invoices' => $existingInvoices,
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($candidates);
        $delimiter = (string) array_key_first($candidates);
        return ($candidates[$delimiter] ?? 0) > 0 ? $delimiter : ',';
    }

    private function buildHeaderMap(array $headers): array
    {
        $aliases = [
            'invoice_no' => ['invoice_no', 'invoice', 'invoice_number', 'invoice_num'],
            'sale_date' => ['sale_date', 'date', 'transaction_date', 'sales_date'],
            'branch_code' => ['branch_code', 'branch'],
            'cashier_username' => ['cashier_username', 'cashier', 'username', 'user'],
            'product_sku' => ['product_sku', 'sku', 'product_code', 'item_code'],
            'product_name' => ['product_name', 'item_name', 'name'],
            'quantity' => ['quantity', 'qty'],
            'unit_price' => ['unit_price', 'price', 'selling_price'],
            'cost_price' => ['cost_price', 'cost', 'unit_cost'],
            'line_discount' => ['line_discount', 'discount_amount', 'item_discount', 'discount'],
            'payment_method' => ['payment_method', 'payment', 'method'],
            'reference_no' => ['reference_no', 'reference', 'reference_number'],
            'amount_paid' => ['amount_paid', 'paid', 'cash_received'],
            'notes' => ['notes', 'remarks', 'note'],
        ];

        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized !== '') {
                $normalizedHeaders[$normalized] = (int) $index;
            }
        }

        $map = [];
        foreach ($aliases as $canonical => $names) {
            foreach ($names as $name) {
                if (array_key_exists($name, $normalizedHeaders)) {
                    $map[$canonical] = $normalizedHeaders[$name];
                    break;
                }
            }
        }

        return $map;
    }

    private function canonicalRow(array $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $field => $index) {
            $value = $row[$index] ?? '';
            $data[$field] = $this->cleanCsvValue((string) $value);
        }
        return $data;
    }

    private function normalizeHeader(string $header): string
    {
        $header = $this->cleanCsvValue($header);
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/u', '_', $header) ?? '';
        return trim($header, '_');
    }

    private function cleanCsvValue(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1');
        }
        return trim($value);
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm/d/Y',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            $errors = DateTime::getLastErrors();
            $valid = $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
            if (!$valid) {
                continue;
            }

            if (!str_contains($format, 'H')) {
                $date->setTime(0, 0, 0);
            } elseif (!str_contains($format, 's')) {
                $date->setTime((int) $date->format('H'), (int) $date->format('i'), 0);
            }
            return $date->format('Y-m-d H:i:s');
        }

        return null;
    }

    private function parseMoney(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $value = str_replace(['₱', '$', ',', ' '], '', $value);
        if (!is_numeric($value)) {
            return null;
        }
        return round((float) $value, 2);
    }

    private function normalizePaymentMethod(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace([' ', '-', '_'], '', $value);
        return match ($value) {
            'cash' => 'cash',
            'gcash', 'g-cash' => 'gcash',
            'card', 'creditcard', 'debitcard', 'credit', 'debit' => 'card',
            default => $value,
        };
    }

    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function stageDirectory(): string
    {
        $path = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'historical_sales';
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('The historical sales upload folder could not be created.');
        }
        return $path;
    }

    private function cleanExpiredStageFiles(): void
    {
        try {
            $dir = $this->stageDirectory();
        } catch (Throwable $e) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.csv') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - self::STAGE_TTL_SECONDS) {
                @unlink($file);
            }
        }
    }

    private function removeStage(string $token, array $stage): void
    {
        if (!empty($stage['path']) && is_file($stage['path'])) {
            @unlink($stage['path']);
        }
        session()->remove($this->sessionKey($token));
    }

    private function sessionKey(string $token): string
    {
        return 'historical_sales_import_' . $token;
    }

    private function csvResponse(string $filename, array $rows)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setBody($content);
    }
}
