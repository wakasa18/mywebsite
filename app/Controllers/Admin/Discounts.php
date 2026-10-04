<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;
use App\Models\CategoryModel;
use App\Models\DiscountModel;
use App\Models\ProductModel;

class Discounts extends BaseController
{
    protected DiscountModel $discountModel;
    protected CategoryModel $categoryModel;
    protected ProductModel $productModel;
    protected ActivityLogModel $activityLogModel;
    protected $db;

    public function __construct()
    {
        $this->discountModel = new DiscountModel();
        $this->categoryModel = new CategoryModel();
        $this->productModel = new ProductModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $type = trim((string) ($this->request->getGet('type') ?? ''));
        $scope = trim((string) ($this->request->getGet('scope') ?? ''));

        if (!in_array($status, ['', 'active', 'scheduled', 'expired', 'inactive'], true)) {
            $status = '';
        }
        if (!in_array($type, ['', 'percentage', 'fixed'], true)) {
            $type = '';
        }
        if (!in_array($scope, ['', 'all', 'category', 'product'], true)) {
            $scope = '';
        }

        $today = date('Y-m-d');

        $builder = $this->discountModel
            ->select("discounts.*, categories.category_name, target_products.product_name AS target_product_name,
                (SELECT COUNT(*) FROM sales WHERE sales.discount_id = discounts.id) AS usage_count", false)
            ->join('categories', 'categories.id = discounts.category_id', 'left')
            ->join('products AS target_products', 'target_products.id = discounts.product_id', 'left');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('discounts.discount_name', $keyword)
                ->orLike('discounts.description', $keyword)
                ->orLike('categories.category_name', $keyword)
                ->orLike('target_products.product_name', $keyword)
                ->groupEnd();
        }

        $this->applyLifecycleFilter($builder, $status, $today);

        if ($type !== '') {
            $builder->where('discounts.discount_type', $type);
        }
        if ($scope !== '') {
            $builder->where('discounts.applies_to', $scope);
        }

        $discounts = $builder
            ->orderBy("CASE
                WHEN discounts.status = 'inactive' THEN 4
                WHEN discounts.end_date IS NOT NULL AND discounts.end_date < " . $this->db->escape($today) . " THEN 3
                WHEN discounts.start_date IS NOT NULL AND discounts.start_date > " . $this->db->escape($today) . " THEN 2
                ELSE 1 END", '', false)
            ->orderBy('discounts.discount_name', 'ASC')
            ->paginate(10);

        foreach ($discounts as &$discount) {
            $discount['computed_status'] = $this->lifecycleStatus($discount, $today);
            $discount['scope_label'] = $this->scopeLabel($discount);
        }
        unset($discount);

        $stats = $this->discountStats($today);
        $pager = $this->discountModel->pager;
        $totalResults = method_exists($pager, 'getTotal') ? (int) $pager->getTotal() : count($discounts);

        return view('admin/discounts/index', [
            'discounts' => $discounts,
            'keyword' => $keyword,
            'status' => $status,
            'type' => $type,
            'scope' => $scope,
            'today' => $today,
            'stats' => $stats,
            'totalResults' => $totalResults,
            'pager' => $pager,
        ]);
    }

    public function create()
    {
        return view('admin/discounts/create', $this->formData());
    }

    public function store()
    {
        $input = $this->normalizedInput();
        if (!$this->validateData($input, $this->rules($input['applies_to']))) {
            return view('admin/discounts/create', $this->formData(null, $this->validator, $input));
        }
        if ($error = $this->businessRuleError($input)) {
            $this->validator->setError($error[0], $error[1]);
            return view('admin/discounts/create', $this->formData(null, $this->validator, $input));
        }
        if ($this->nameExists($input['discount_name'])) {
            $this->validator->setError('discount_name', 'A discount with this name already exists.');
            return view('admin/discounts/create', $this->formData(null, $this->validator, $input));
        }

        if (!$this->discountModel->insert($this->payload($input))) {
            return redirect()->back()->withInput()->with('error', 'The discount could not be saved. Please try again.');
        }

        $this->logActivity('Created discount: ' . $input['discount_name']);
        return redirect()->to(site_url('admin/discounts'))->with('success', 'Discount created successfully.');
    }

    public function edit(int $id)
    {
        $discount = $this->discountModel->find($id);
        if (!$discount) {
            return redirect()->to(site_url('admin/discounts'))->with('error', 'Discount not found.');
        }

        return view('admin/discounts/edit', $this->formData($discount));
    }

    public function update(int $id)
    {
        $discount = $this->discountModel->find($id);
        if (!$discount) {
            return redirect()->to(site_url('admin/discounts'))->with('error', 'Discount not found.');
        }

        $input = $this->normalizedInput();
        if (!$this->validateData($input, $this->rules($input['applies_to']))) {
            return view('admin/discounts/edit', $this->formData($discount, $this->validator, $input));
        }
        if ($error = $this->businessRuleError($input)) {
            $this->validator->setError($error[0], $error[1]);
            return view('admin/discounts/edit', $this->formData($discount, $this->validator, $input));
        }
        if ($this->nameExists($input['discount_name'], $id)) {
            $this->validator->setError('discount_name', 'A discount with this name already exists.');
            return view('admin/discounts/edit', $this->formData($discount, $this->validator, $input));
        }

        if (!$this->discountModel->update($id, $this->payload($input))) {
            return redirect()->back()->withInput()->with('error', 'The discount could not be updated. Please try again.');
        }

        $this->logActivity('Updated discount: ' . $input['discount_name']);
        return redirect()->to(site_url('admin/discounts'))->with('success', 'Discount updated successfully.');
    }

    public function toggleStatus(int $id)
    {
        $discount = $this->discountModel->find($id);
        if (!$discount) {
            return redirect()->to(site_url('admin/discounts'))->with('error', 'Discount not found.');
        }

        $newStatus = $discount['status'] === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'active') {
            if (!empty($discount['end_date']) && $discount['end_date'] < date('Y-m-d')) {
                return redirect()->to(site_url('admin/discounts/edit/' . $id))->with(
                    'error',
                    'This discount has already ended. Update the end date before activating it.'
                );
            }

            $activationError = $this->businessRuleError([
                'discount_type' => (string) $discount['discount_type'],
                'discount_value' => (string) $discount['discount_value'],
                'minimum_purchase' => (string) ($discount['minimum_purchase'] ?? ''),
                'max_discount_amount' => (string) ($discount['max_discount_amount'] ?? ''),
                'start_date' => (string) ($discount['start_date'] ?? ''),
                'end_date' => (string) ($discount['end_date'] ?? ''),
                'applies_to' => (string) ($discount['applies_to'] ?? 'all'),
                'category_id' => (string) ($discount['category_id'] ?? ''),
                'product_id' => (string) ($discount['product_id'] ?? ''),
            ]);
            if ($activationError) {
                return redirect()->to(site_url('admin/discounts/edit/' . $id))->with(
                    'error',
                    'This discount cannot be activated until its target and values are corrected.'
                );
            }
        }

        if (!$this->discountModel->update($id, ['status' => $newStatus])) {
            return redirect()->to(site_url('admin/discounts'))->with('error', 'The discount status could not be changed.');
        }

        $this->logActivity('Discount "' . $discount['discount_name'] . '" set to ' . $newStatus);
        $message = $newStatus === 'active'
            ? 'Discount activated successfully.'
            : 'Discount deactivated. Cashiers can no longer select it.';

        return redirect()->to(site_url('admin/discounts'))->with('success', $message);
    }

    public function delete(int $id)
    {
        $discount = $this->discountModel->find($id);
        if (!$discount) {
            return redirect()->to(site_url('admin/discounts'))->with('error', 'Discount not found.');
        }

        if (!$this->discountModel->delete($id)) {
            return redirect()->to(site_url('admin/discounts'))->with('error', 'The discount could not be moved to trash.');
        }

        $this->logActivity('Moved discount to trash: ' . $discount['discount_name']);
        return redirect()->to(site_url('admin/discounts'))->with('success', 'Discount moved to trash. Existing sales records are unchanged.');
    }

    public function trash()
    {
        $keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
        $builder = $this->discountModel
            ->onlyDeleted()
            ->select("discounts.*, (SELECT COUNT(*) FROM sales WHERE sales.discount_id = discounts.id) AS usage_count", false)
            ->orderBy('discounts.deleted_at', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('discounts.discount_name', $keyword)
                ->orLike('discounts.description', $keyword)
                ->groupEnd();
        }

        return view('admin/discounts/trash', [
            'discounts' => $builder->paginate(15),
            'keyword' => $keyword,
            'pager' => $this->discountModel->pager,
        ]);
    }

    public function restore(int $id)
    {
        $discount = $this->discountModel->onlyDeleted()->find($id);
        if (!$discount) {
            return redirect()->to(site_url('admin/discounts/trash'))->with('error', 'Discount not found in trash.');
        }
        if ($this->nameExists((string) $discount['discount_name'], $id)) {
            return redirect()->to(site_url('admin/discounts/trash'))->with('error', 'A current discount already uses this name. Rename the current discount before restoring this record.');
        }

        if (!$this->discountModel->restoreRecord($id)) {
            return redirect()->to(site_url('admin/discounts/trash'))->with('error', 'The discount could not be restored.');
        }

        $this->logActivity('Restored discount: ' . $discount['discount_name']);
        return redirect()->to(site_url('admin/discounts/trash'))->with('success', 'Discount restored successfully. Check its dates and status before using it.');
    }

    public function forceDelete(int $id)
    {
        if (session('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Administrator access required.');
        }
        try {
            $changed = (new \App\Libraries\RetainedRecordDeletion($this->db))->hide('discounts', $id, (string)session('role'), (int)session('user_id'));
        } catch (\DomainException|\InvalidArgumentException $error) {
            return redirect()->to(site_url('admin/discounts/trash'))->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            log_message('error', 'Permanent hiding failed: {message}', ['message'=>$error->getMessage()]);
            return redirect()->to(site_url('admin/discounts/trash'))->with('error', 'The record could not be removed. No changes were saved.');
        }
        return redirect()->to(site_url('admin/discounts/trash'))->with('success', $changed ? 'Discount removed from the system and Trash. Its database record and history are retained.' : 'Discount is already permanently hidden.');
    }

    private function normalizedInput(): array
    {
        return [
            'discount_name' => trim((string) $this->request->getPost('discount_name')),
            'discount_type' => trim((string) $this->request->getPost('discount_type')),
            'discount_value' => trim((string) $this->request->getPost('discount_value')),
            'description' => trim((string) $this->request->getPost('description')),
            'applies_to' => trim((string) $this->request->getPost('applies_to')),
            'category_id' => trim((string) $this->request->getPost('category_id')),
            'product_id' => trim((string) $this->request->getPost('product_id')),
            'start_date' => trim((string) $this->request->getPost('start_date')),
            'end_date' => trim((string) $this->request->getPost('end_date')),
            'minimum_purchase' => trim((string) $this->request->getPost('minimum_purchase')),
            'max_discount_amount' => trim((string) $this->request->getPost('max_discount_amount')),
            'status' => trim((string) $this->request->getPost('status')),
        ];
    }

    private function rules(string $appliesTo): array
    {
        $rules = [
            'discount_name' => 'required|min_length[2]|max_length[100]',
            'discount_type' => 'required|in_list[percentage,fixed]',
            'discount_value' => 'required|numeric|greater_than[0]',
            'description' => 'permit_empty|max_length[500]',
            'applies_to' => 'required|in_list[all,category,product]',
            'status' => 'required|in_list[active,inactive]',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'minimum_purchase' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'max_discount_amount' => 'permit_empty|numeric|greater_than[0]',
        ];
        if ($appliesTo === 'category') {
            $rules['category_id'] = 'required|integer|greater_than[0]';
        } elseif ($appliesTo === 'product') {
            $rules['product_id'] = 'required|integer|greater_than[0]';
        }
        return $rules;
    }

    private function businessRuleError(array $input): ?array
    {
        foreach (['discount_value', 'minimum_purchase', 'max_discount_amount'] as $field) {
            $value = (string) ($input[$field] ?? '');
            if ($value === '') continue;
            if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value) || (float) $value > 99999999.99) {
                return [$field, 'Enter an amount with no more than two decimal places, up to 99,999,999.99.'];
            }
            if ($field !== 'minimum_purchase' && (float) $value < 0.01) {
                return [$field, 'The value must be at least 0.01.'];
            }
        }
        if (($input['discount_type'] ?? '') === 'percentage' && (float) ($input['discount_value'] ?? 0) > 100) {
            return ['discount_value', 'Percentage discount cannot be more than 100%.'];
        }
        if (($input['start_date'] ?? '') !== '' && ($input['end_date'] ?? '') !== '' && $input['start_date'] > $input['end_date']) {
            return ['end_date', 'End date must be the same as or later than the start date.'];
        }
        if (($input['applies_to'] ?? '') === 'category' && !$this->categoryModel->find((int) ($input['category_id'] ?? 0))) {
            return ['category_id', 'Please select a valid category.'];
        }
        if (($input['applies_to'] ?? '') === 'product') {
            $product = $this->productModel
                ->where('id', (int) ($input['product_id'] ?? 0))
                ->where('status', 'active')
                ->first();
            if (!$product) {
                return ['product_id', 'Please select a valid active product.'];
            }
        }
        return null;
    }

    private function payload(array $input): array
    {
        return [
            'discount_name' => $input['discount_name'],
            'discount_type' => $input['discount_type'],
            'discount_value' => round((float) $input['discount_value'], 2),
            'description' => $input['description'] ?: null,
            'applies_to' => $input['applies_to'],
            'category_id' => $input['applies_to'] === 'category' ? (int) $input['category_id'] : null,
            'product_id' => $input['applies_to'] === 'product' ? (int) $input['product_id'] : null,
            'start_date' => $input['start_date'] ?: null,
            'end_date' => $input['end_date'] ?: null,
            'minimum_purchase' => $input['minimum_purchase'] !== '' ? round((float) $input['minimum_purchase'], 2) : 0,
            'max_discount_amount' => $input['discount_type'] === 'percentage' && $input['max_discount_amount'] !== ''
                ? round((float) $input['max_discount_amount'], 2)
                : null,
            'status' => $input['status'],
        ];
    }

    private function formData(?array $discount = null, $validation = null, ?array $submitted = null): array
    {
        $products = $this->productModel
            ->where('status', 'active')
            ->orderBy('product_name', 'ASC')
            ->findAll();

        if ($discount && !empty($discount['product_id']) && !in_array((int) $discount['product_id'], array_map(static fn ($p) => (int) $p['id'], $products), true)) {
            $selected = $this->productModel->withDeleted()->find((int) $discount['product_id']);
            if ($selected) {
                $selected['unavailable'] = true;
                $products[] = $selected;
            }
        }

        $defaults = [
            'discount_name' => '',
            'discount_type' => 'percentage',
            'discount_value' => '',
            'description' => '',
            'applies_to' => 'all',
            'category_id' => '',
            'product_id' => '',
            'start_date' => '',
            'end_date' => '',
            'minimum_purchase' => '',
            'max_discount_amount' => '',
            'status' => 'active',
        ];

        $form = array_merge($defaults, $discount ?? [], $submitted ?? []);

        $data = [
            'validation' => $validation ?? \Config\Services::validation(),
            'categories' => $this->categoryModel->orderBy('category_name', 'ASC')->findAll(),
            'products' => $products,
            'form' => $form,
        ];
        if ($discount !== null) {
            $data['discount'] = $discount;
        }
        return $data;
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $builder = $this->db->table('discounts')
            ->where('LOWER(discount_name) = ' . $this->db->escape(mb_strtolower($name)), null, false)
            ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0);
        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }
        return $builder->countAllResults() > 0;
    }

    private function applyLifecycleFilter($builder, string $status, string $today): void
    {
        if ($status === 'inactive') {
            $builder->where('discounts.status', 'inactive');
            return;
        }
        if ($status === 'scheduled') {
            $builder->where('discounts.status', 'active')->where('discounts.start_date >', $today);
            return;
        }
        if ($status === 'expired') {
            $builder->where('discounts.status', 'active')->where('discounts.end_date <', $today);
            return;
        }
        if ($status === 'active') {
            $builder
                ->where('discounts.status', 'active')
                ->groupStart()
                    ->where('discounts.start_date IS NULL', null, false)
                    ->orWhere('discounts.start_date <=', $today)
                ->groupEnd()
                ->groupStart()
                    ->where('discounts.end_date IS NULL', null, false)
                    ->orWhere('discounts.end_date >=', $today)
                ->groupEnd();
        }
    }

    private function lifecycleStatus(array $discount, string $today): string
    {
        if (($discount['status'] ?? 'inactive') === 'inactive') {
            return 'inactive';
        }
        if (!empty($discount['end_date']) && $discount['end_date'] < $today) {
            return 'expired';
        }
        if (!empty($discount['start_date']) && $discount['start_date'] > $today) {
            return 'scheduled';
        }
        return 'active';
    }

    private function scopeLabel(array $discount): string
    {
        if (($discount['applies_to'] ?? 'all') === 'category') {
            return !empty($discount['category_name'])
                ? 'Category: ' . $discount['category_name']
                : 'Category unavailable';
        }
        if (($discount['applies_to'] ?? 'all') === 'product') {
            return !empty($discount['target_product_name'])
                ? 'Product: ' . $discount['target_product_name']
                : 'Product unavailable';
        }
        return 'All products';
    }

    private function discountStats(string $today): array
    {
        $row = $this->db->table('discounts')
            ->select("SUM(CASE WHEN status = 'active'
                    AND (start_date IS NULL OR start_date <= " . $this->db->escape($today) . ")
                    AND (end_date IS NULL OR end_date >= " . $this->db->escape($today) . ") THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN status = 'active'
                    AND start_date IS NOT NULL AND start_date > " . $this->db->escape($today) . "
                    AND (end_date IS NULL OR end_date >= " . $this->db->escape($today) . ") THEN 1 ELSE 0 END) AS scheduled_count,
                SUM(CASE WHEN status = 'active' AND end_date IS NOT NULL AND end_date < " . $this->db->escape($today) . " THEN 1 ELSE 0 END) AS expired_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_count", false)
            ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0)
            ->get()
            ->getRowArray();

        return [
            'active' => (int) ($row['active_count'] ?? 0),
            'scheduled' => (int) ($row['scheduled_count'] ?? 0),
            'expired' => (int) ($row['expired_count'] ?? 0),
            'inactive' => (int) ($row['inactive_count'] ?? 0),
        ];
    }

    private function logActivity(string $activity): void
    {
        $this->activityLogModel->insert([
            'user_id' => session('user_id'),
            'activity' => $activity,
            'log_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
