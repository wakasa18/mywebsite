<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\ActivityLogModel;

class Branches extends BaseController
{
    protected BranchModel $branchModel;
    protected ActivityLogModel $activityLogModel;
    protected $db;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $filterStatus = trim((string) ($this->request->getGet('status') ?? ''));
        if (!in_array($filterStatus, ['', 'active', 'inactive'], true)) {
            $filterStatus = '';
        }

        $builder = $this->branchModel;
        if ($search !== '') {
            $builder->groupStart()
                ->like('branch_name', $search)
                ->orLike('branch_code', $search)
                ->orLike('address', $search)
                ->orLike('contact_number', $search)
                ->groupEnd();
        }
        if ($filterStatus !== '') {
            $builder->where('status', $filterStatus);
        }

        return view('admin/branches/index', [
            'branches' => $builder->orderBy('branch_name', 'ASC')->paginate(10),
            'pager' => $this->branchModel->pager,
            'search' => $search,
            'filterStatus' => $filterStatus,
            'totalBranches' => $this->branchModel->countAll(),
            'activeBranches' => $this->branchModel->where('status', 'active')->countAllResults(),
            'inactiveBranches' => $this->branchModel->where('status', 'inactive')->countAllResults(),
        ]);
    }

    public function create()
    {
        return view('admin/branches/create');
    }

    public function store()
    {
        $branchName = trim((string) $this->request->getPost('branch_name'));
        $branchCode = strtoupper(trim((string) $this->request->getPost('branch_code')));
        $contact = trim((string) $this->request->getPost('contact_number'));

        $data = $this->request->getPost();
        $data['branch_name'] = $branchName;
        $data['branch_code'] = $branchCode;
        $data['contact_number'] = $contact;

        if (!$this->validateData($data, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($this->branchNameExists($branchName)) {
            return redirect()->back()->withInput()->with('errors', ['branch_name' => 'A branch with this name already exists.']);
        }

        $inserted = $this->branchModel->insert([
            'branch_name' => $branchName,
            'branch_code' => $branchCode,
            'address' => trim((string) $this->request->getPost('address')) ?: null,
            'contact_number' => $contact ?: null,
            'status' => $this->request->getPost('status'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$inserted) {
            return redirect()->back()->withInput()->with('error', 'The branch could not be saved.');
        }

        $this->logActivity('Added branch: ' . $branchName . ' (' . $branchCode . ')');
        return redirect()->to(site_url('admin/branches'))->with('success', 'Branch added successfully.');
    }

    public function edit($id)
    {
        $branch = $this->branchModel->find((int) $id);
        if (!$branch) {
            return redirect()->to(site_url('admin/branches'))->with('error', 'Branch not found.');
        }

        return view('admin/branches/edit', ['branch' => $branch]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $branch = $this->branchModel->find($id);
        if (!$branch) {
            return redirect()->to(site_url('admin/branches'))->with('error', 'Branch not found.');
        }

        $branchName = trim((string) $this->request->getPost('branch_name'));
        $branchCode = strtoupper(trim((string) $this->request->getPost('branch_code')));
        $contact = trim((string) $this->request->getPost('contact_number'));
        $status = trim((string) $this->request->getPost('status'));

        $rules = $this->rules();
        if (strcasecmp($branchCode, (string) $branch['branch_code']) === 0) {
            $rules['branch_code'] = 'required|min_length[2]|max_length[20]|alpha_numeric_punct';
        }

        $data = $this->request->getPost();
        $data['branch_name'] = $branchName;
        $data['branch_code'] = $branchCode;
        $data['contact_number'] = $contact;

        if (!$this->validateData($data, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($this->branchNameExists($branchName, $id)) {
            return redirect()->back()->withInput()->with('errors', ['branch_name' => 'A branch with this name already exists.']);
        }

        if ($status === 'inactive' && $branch['status'] === 'active') {
            $activeCashiers = $this->db->table('users')
                ->where('branch_id', $id)
                ->where('role', 'cashier')
                ->where('status', 'active')
                ->countAllResults();

            if ($activeCashiers > 0) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'This branch still has ' . $activeCashiers . ' active cashier account(s). Reassign or deactivate them first.'
                );
            }
        }

        if (!$this->branchModel->update($id, [
            'branch_name' => $branchName,
            'branch_code' => $branchCode,
            'address' => trim((string) $this->request->getPost('address')) ?: null,
            'contact_number' => $contact ?: null,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            return redirect()->back()->withInput()->with('error', 'The branch could not be updated.');
        }

        $this->logActivity('Updated branch: ' . $branchName . ' (' . $branchCode . ')');
        return redirect()->to(site_url('admin/branches'))->with('success', 'Branch updated successfully.');
    }

    private function rules(): array
    {
        return [
            'branch_name' => 'required|min_length[2]|max_length[100]',
            'branch_code' => 'required|min_length[2]|max_length[20]|alpha_numeric_punct|is_unique[branches.branch_code]',
            'address' => 'permit_empty|max_length[500]',
            'contact_number' => 'permit_empty|max_length[30]|regex_match[/^[0-9+()\-\s]+$/]',
            'status' => 'required|in_list[active,inactive]',
        ];
    }

    private function branchNameExists(string $name, ?int $ignoreId = null): bool
    {
        $builder = $this->db->table('branches')->where('LOWER(branch_name) = ' . $this->db->escape(strtolower($name)), null, false);
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
