<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ActivityLogModel;
use App\Models\BranchModel;

class Users extends BaseController
{
    protected UserModel $userModel;
    protected ActivityLogModel $activityLogModel;
    protected BranchModel $branchModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->branchModel = new BranchModel();
    }

    public function index()
    {
        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $branchId = trim((string) ($this->request->getGet('branch_id') ?? ''));
        $filterRole = trim((string) ($this->request->getGet('role') ?? ''));
        $filterStatus = trim((string) ($this->request->getGet('status') ?? ''));

        if (!in_array($filterRole, ['', 'admin', 'cashier'], true)) {
            $filterRole = '';
        }
        if (!in_array($filterStatus, ['', 'active', 'inactive'], true)) {
            $filterStatus = '';
        }

        $builder = $this->userModel
            ->select('users.*, branches.branch_name')
            ->join('branches', 'branches.id = users.branch_id', 'left');

        if ($search !== '') {
            $builder->groupStart()
                ->like('users.full_name', $search)
                ->orLike('users.username', $search)
                ->orLike('branches.branch_name', $search)
                ->groupEnd();
        }
        if ($branchId !== '' && ctype_digit($branchId)) {
            $builder->where('users.branch_id', (int) $branchId);
        } else {
            $branchId = '';
        }
        if ($filterRole !== '') {
            $builder->where('users.role', $filterRole);
        }
        if ($filterStatus !== '') {
            $builder->where('users.status', $filterStatus);
        }

        return view('admin/users/index', [
            'title' => 'User Management',
            'users' => $builder->orderBy('users.full_name', 'ASC')->paginate(10),
            'pager' => $this->userModel->pager,
            'search' => $search,
            'branchId' => $branchId,
            'branches' => $this->branchModel->orderBy('branch_name', 'ASC')->findAll(),
            'filterRole' => $filterRole,
            'filterStatus' => $filterStatus,
            'totalUsers' => $this->userModel->countAll(),
            'activeUsers' => $this->userModel->where('status', 'active')->countAllResults(),
            'inactiveUsers' => $this->userModel->where('status', 'inactive')->countAllResults(),
            'adminUsers' => $this->userModel->where('role', 'admin')->countAllResults(),
            'cashierUsers' => $this->userModel->where('role', 'cashier')->countAllResults(),
        ]);
    }

    public function create()
    {
        return view('admin/users/create', [
            'title' => 'Add User',
            'branches' => $this->activeBranches(),
        ]);
    }

    public function store()
    {
        $role = trim((string) $this->request->getPost('role'));
        $branchId = (int) ($this->request->getPost('branch_id') ?? 0);
        $fullName = trim((string) $this->request->getPost('full_name'));
        $username = trim((string) $this->request->getPost('username'));

        $rules = [
            'full_name' => 'required|min_length[3]|max_length[100]',
            'username' => 'required|min_length[3]|max_length[50]|alpha_numeric_punct|is_unique[users.username]',
            'password' => 'required|min_length[8]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
            'role' => 'required|in_list[admin,cashier]',
        ];
        if ($role === 'cashier') {
            $rules['branch_id'] = 'required|integer|greater_than[0]';
        }

        $validationData = $this->request->getPost();
        $validationData['full_name'] = $fullName;
        $validationData['username'] = $username;

        if (!$this->validateData($validationData, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($role === 'cashier' && !$this->isActiveBranch($branchId)) {
            return redirect()->back()->withInput()->with('errors', [
                'branch_id' => 'Please select a valid active branch for the cashier account.',
            ]);
        }

        $inserted = $this->userModel->insert([
            'full_name' => $fullName,
            'username' => $username,
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $role,
            'branch_id' => $role === 'cashier' ? $branchId : null,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$inserted) {
            return redirect()->back()->withInput()->with('error', 'The user account could not be created.');
        }

        $this->logActivity('Added user: ' . $username);

        return redirect()->to(site_url('admin/users'))->with('success', 'User added successfully.');
    }

    public function edit($id)
    {
        $user = $this->userModel->find((int) $id);
        if (!$user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found.');
        }

        return view('admin/users/edit', [
            'title' => 'Edit User',
            'user' => $user,
            'branches' => $this->activeBranches(),
        ]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found.');
        }

        $role = trim((string) $this->request->getPost('role'));
        $status = trim((string) $this->request->getPost('status'));
        $branchId = (int) ($this->request->getPost('branch_id') ?? 0);
        $fullName = trim((string) $this->request->getPost('full_name'));
        $username = trim((string) $this->request->getPost('username'));

        $usernameRule = 'required|min_length[3]|max_length[50]|alpha_numeric_punct';
        if (strcasecmp($username, (string) $user['username']) !== 0) {
            $usernameRule .= '|is_unique[users.username]';
        }

        $rules = [
            'full_name' => 'required|min_length[3]|max_length[100]',
            'username' => $usernameRule,
            'role' => 'required|in_list[admin,cashier]',
            'status' => 'required|in_list[active,inactive]',
        ];
        if ($role === 'cashier') {
            $rules['branch_id'] = 'required|integer|greater_than[0]';
        }

        $validationData = $this->request->getPost();
        $validationData['full_name'] = $fullName;
        $validationData['username'] = $username;

        if (!$this->validateData($validationData, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($role === 'cashier' && !$this->isActiveBranch($branchId)) {
            return redirect()->back()->withInput()->with('errors', [
                'branch_id' => 'Please select a valid active branch for the cashier account.',
            ]);
        }

        $currentUserId = (int) session('user_id');
        if ($id === $currentUserId && ($role !== 'admin' || $status !== 'active')) {
            return redirect()->back()->withInput()->with('error', 'You cannot remove your own administrator access or deactivate your own account.');
        }

        if ($user['role'] === 'admin' && $user['status'] === 'active' && ($role !== 'admin' || $status !== 'active')) {
            if ($this->activeAdminCount() <= 1) {
                return redirect()->back()->withInput()->with('error', 'At least one active administrator account must remain.');
            }
        }

        if (!$this->userModel->update($id, [
            'full_name' => $fullName,
            'username' => $username,
            'role' => $role,
            'branch_id' => $role === 'cashier' ? $branchId : null,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            return redirect()->back()->withInput()->with('error', 'The user account could not be updated.');
        }

        $this->logActivity('Updated user: ' . $username);

        return redirect()->to(site_url('admin/users'))->with('success', 'User updated successfully.');
    }

    public function deactivate($id)
    {
        $id = (int) $id;
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found.');
        }
        if ($id === (int) session('user_id')) {
            return redirect()->to(site_url('admin/users'))->with('error', 'You cannot deactivate your own account.');
        }
        if ($user['status'] === 'inactive') {
            return redirect()->to(site_url('admin/users'))->with('success', 'The user is already inactive.');
        }
        if ($user['role'] === 'admin' && $this->activeAdminCount() <= 1) {
            return redirect()->to(site_url('admin/users'))->with('error', 'You cannot deactivate the last active administrator.');
        }

        if (!$this->userModel->update($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')])) {
            return redirect()->to(site_url('admin/users'))->with('error', 'The user could not be deactivated.');
        }

        $this->logActivity('Deactivated user: ' . $user['username']);
        return redirect()->to(site_url('admin/users'))->with('success', 'User deactivated successfully.');
    }

    public function activate($id)
    {
        $id = (int) $id;
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found.');
        }
        if ($user['status'] === 'active') {
            return redirect()->to(site_url('admin/users'))->with('success', 'The user is already active.');
        }
        if ($user['role'] === 'cashier' && !$this->isActiveBranch((int) ($user['branch_id'] ?? 0))) {
            return redirect()->to(site_url('admin/users'))->with('error', 'Assign this cashier to an active branch before activating the account.');
        }

        if (!$this->userModel->update($id, ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')])) {
            return redirect()->to(site_url('admin/users'))->with('error', 'The user could not be activated.');
        }

        $this->logActivity('Activated user: ' . $user['username']);
        return redirect()->to(site_url('admin/users'))->with('success', 'User activated successfully.');
    }

    public function password($id)
    {
        $user = $this->userModel->find((int) $id);
        if (!$user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found.');
        }

        return view('admin/users/password', [
            'title' => 'Change Password',
            'user' => $user,
        ]);
    }

    public function passwordUpdate($id)
    {
        $id = (int) $id;
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to(site_url('admin/users'))->with('error', 'User not found.');
        }

        if (!$this->validate([
            'password' => 'required|min_length[8]|max_length[255]',
            'confirm_password' => 'required|matches[password]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        if (!$this->userModel->update($id, [
            'password' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            return redirect()->back()->with('error', 'The password could not be updated.');
        }

        $this->logActivity('Changed password for user: ' . $user['username']);
        return redirect()->to(site_url('admin/users'))->with('success', 'Password updated successfully.');
    }

    public function activityLogs()
    {
        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $dateFrom = $this->validDate((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo = $this->validDate((string) ($this->request->getGet('date_to') ?? ''));

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            session()->setFlashdata('error', 'The activity-log dates were reversed, so the system corrected their order.');
        }

        $builder = $this->activityLogModel
            ->select('activity_logs.*, users.full_name, users.username')
            ->join('users', 'users.id = activity_logs.user_id', 'left');

        if ($search !== '') {
            $builder->groupStart()
                ->like('activity_logs.activity', $search)
                ->orLike('users.full_name', $search)
                ->orLike('users.username', $search)
                ->groupEnd();
        }
        if ($dateFrom !== '') {
            $builder->where('DATE(activity_logs.log_time) >=', $dateFrom);
        }
        if ($dateTo !== '') {
            $builder->where('DATE(activity_logs.log_time) <=', $dateTo);
        }

        return view('admin/users/activity_logs', [
            'title' => 'Activity Logs',
            'logs' => $builder->orderBy('activity_logs.id', 'DESC')->paginate(10),
            'pager' => $this->activityLogModel->pager,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function activeBranches(): array
    {
        return $this->branchModel->where('status', 'active')->orderBy('branch_name', 'ASC')->findAll();
    }

    private function isActiveBranch(int $branchId): bool
    {
        if ($branchId <= 0) {
            return false;
        }

        return $this->branchModel->where('id', $branchId)->where('status', 'active')->first() !== null;
    }

    private function activeAdminCount(): int
    {
        return $this->userModel->where('role', 'admin')->where('status', 'active')->countAllResults();
    }

    private function validDate(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
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
