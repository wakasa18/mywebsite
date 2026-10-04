<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SupplierModel;
use App\Models\ActivityLogModel;

class Suppliers extends BaseController
{
    protected SupplierModel $supplierModel;
    protected ActivityLogModel $activityLogModel;
    protected $db;

    public function __construct()
    {
        $this->supplierModel = new SupplierModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        if (!in_array($status, ['', 'active', 'inactive'], true)) {
            $status = '';
        }

        $builder = $this->supplierModel->orderBy('supplier_name', 'ASC');
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('supplier_name', $keyword)
                ->orLike('contact_person', $keyword)
                ->orLike('contact_number', $keyword)
                ->orLike('email', $keyword)
                ->orLike('address', $keyword)
                ->groupEnd();
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }

        $suppliers = $builder->paginate(15);
        foreach ($suppliers as &$supplier) {
            $supplier['product_count'] = $this->db->table('products')
                ->where('supplier_id', (int) $supplier['id'])
                ->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0)
                ->countAllResults();
        }
        unset($supplier);

        return view('admin/suppliers/index', [
            'suppliers' => $suppliers,
            'keyword' => $keyword,
            'status' => $status,
            'pager' => $this->supplierModel->pager,
        ]);
    }

    public function create()
    {
        return view('admin/suppliers/create', ['validation' => \Config\Services::validation()]);
    }

    public function store()
    {
        $input = $this->normalizedInput();
        if (!$this->validateData($input, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($this->nameExists($input['supplier_name'])) {
            return redirect()->back()->withInput()->with('errors', ['supplier_name' => 'A supplier with this name already exists.']);
        }

        if (!$this->supplierModel->insert($this->payload($input))) {
            return redirect()->back()->withInput()->with('error', 'The supplier could not be saved.');
        }

        $this->logActivity('Added supplier: ' . $input['supplier_name']);
        return redirect()->to(site_url('admin/suppliers'))->with('success', 'Supplier added successfully.');
    }

    public function edit(int $id)
    {
        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            return redirect()->to(site_url('admin/suppliers'))->with('error', 'Supplier not found.');
        }

        return view('admin/suppliers/edit', [
            'supplier' => $supplier,
            'validation' => \Config\Services::validation(),
        ]);
    }

    public function update(int $id)
    {
        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            return redirect()->to(site_url('admin/suppliers'))->with('error', 'Supplier not found.');
        }

        $input = $this->normalizedInput();
        if (!$this->validateData($input, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($this->nameExists($input['supplier_name'], $id)) {
            return redirect()->back()->withInput()->with('errors', ['supplier_name' => 'A supplier with this name already exists.']);
        }

        if (!$this->supplierModel->update($id, $this->payload($input))) {
            return redirect()->back()->withInput()->with('error', 'The supplier could not be updated.');
        }

        $this->logActivity('Updated supplier: ' . $input['supplier_name'] . ' (ID: ' . $id . ')');
        return redirect()->to(site_url('admin/suppliers'))->with('success', 'Supplier updated successfully.');
    }

    public function delete(int $id)
    {
        $supplier = $this->supplierModel->find($id);
        if (!$supplier) {
            return redirect()->to(site_url('admin/suppliers'))->with('error', 'Supplier not found.');
        }

        if (!$this->supplierModel->delete($id)) {
            return redirect()->to(site_url('admin/suppliers'))->with('error', 'The supplier could not be moved to trash.');
        }

        $this->logActivity('Moved supplier to trash: ' . $supplier['supplier_name'] . ' (ID: ' . $id . ')');
        return redirect()->to(site_url('admin/suppliers'))->with('success', 'Supplier moved to trash. Existing product records are preserved.');
    }

    public function trash()
    {
        $keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
        $builder = $this->supplierModel->onlyDeleted()->orderBy('deleted_at', 'DESC');
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('supplier_name', $keyword)
                ->orLike('contact_person', $keyword)
                ->orLike('contact_number', $keyword)
                ->orLike('email', $keyword)
                ->groupEnd();
        }

        return view('admin/suppliers/trash', [
            'suppliers' => $builder->paginate(15),
            'keyword' => $keyword,
            'pager' => $this->supplierModel->pager,
        ]);
    }

    public function restore(int $id)
    {
        $supplier = $this->supplierModel->onlyDeleted()->find($id);
        if (!$supplier) {
            return redirect()->to(site_url('admin/suppliers/trash'))->with('error', 'Supplier not found in trash.');
        }
        if ($this->nameExists((string) $supplier['supplier_name'], $id)) {
            return redirect()->to(site_url('admin/suppliers/trash'))->with('error', 'A current supplier already uses this name.');
        }

        if (!$this->supplierModel->restoreRecord($id)) {
            return redirect()->to(site_url('admin/suppliers/trash'))->with('error', 'The supplier could not be restored.');
        }

        $this->logActivity('Restored supplier: ' . $supplier['supplier_name'] . ' (ID: ' . $id . ')');
        return redirect()->to(site_url('admin/suppliers/trash'))->with('success', 'Supplier restored successfully.');
    }

    public function forceDelete(int $id)
    {
        if (session('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Administrator access required.');
        }
        try {
            $changed = (new \App\Libraries\RetainedRecordDeletion($this->db))->hide('suppliers', $id, (string)session('role'), (int)session('user_id'));
        } catch (\DomainException|\InvalidArgumentException $error) {
            return redirect()->to(site_url('admin/suppliers/trash'))->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            log_message('error', 'Permanent hiding failed: {message}', ['message'=>$error->getMessage()]);
            return redirect()->to(site_url('admin/suppliers/trash'))->with('error', 'The record could not be removed. No changes were saved.');
        }
        return redirect()->to(site_url('admin/suppliers/trash'))->with('success', $changed ? 'Supplier removed from the system and Trash. Its database record and history are retained.' : 'Supplier is already permanently hidden.');
    }

    private function normalizedInput(): array
    {
        return [
            'supplier_name' => trim((string) $this->request->getPost('supplier_name')),
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'contact_number' => trim((string) $this->request->getPost('contact_number')),
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'address' => trim((string) $this->request->getPost('address')),
            'status' => trim((string) $this->request->getPost('status')),
        ];
    }

    private function rules(): array
    {
        return [
            'supplier_name' => 'required|min_length[2]|max_length[150]',
            'contact_person' => 'permit_empty|max_length[100]',
            'contact_number' => 'permit_empty|max_length[30]|regex_match[/^[0-9+()\-\s]+$/]',
            'email' => 'permit_empty|valid_email|max_length[150]',
            'address' => 'permit_empty|max_length[500]',
            'status' => 'required|in_list[active,inactive]',
        ];
    }

    private function payload(array $input): array
    {
        return [
            'supplier_name' => $input['supplier_name'],
            'contact_person' => $input['contact_person'] ?: null,
            'contact_number' => $input['contact_number'] ?: null,
            'email' => $input['email'] ?: null,
            'address' => $input['address'] ?: null,
            'status' => $input['status'],
        ];
    }

    private function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $builder = $this->db->table('suppliers')->where('LOWER(supplier_name) = ' . $this->db->escape(strtolower($name)), null, false)->where('deleted_at IS NULL', null, false)->where('is_deleted', 0)->where('is_permanently_deleted', 0);
        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }
        return $builder->countAllResults() > 0;
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
