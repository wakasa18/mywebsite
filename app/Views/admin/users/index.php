<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    /* ===== CARDS ===== */
    .compact-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
    }

    .card.mini {
        padding: 14px 12px;
        border-radius: 14px;
    }

    .card.mini h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .card.mini p {
        margin: 4px 0 0;
        font-size: 12px;
    }

</style>

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">User Management</h1>
    <p class="muted" style="margin:6px 0 0;">View and manage all system users and their branch assignments.</p>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
    <div class="sp"></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger">
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
    <div class="sp"></div>
<?php endif; ?>

<!-- CARDS -->
<div class="cards compact-cards" style="margin-bottom:20px;">
    <div class="card mini">
        <h4><?= esc($totalUsers) ?></h4>
        <p class="muted">Total</p>
    </div>

    <div class="card mini">
        <h4><?= esc($activeUsers) ?></h4>
        <p class="muted">Active</p>
    </div>

    <div class="card mini">
        <h4><?= esc($inactiveUsers) ?></h4>
        <p class="muted">Inactive</p>
    </div>

    <div class="card mini">
        <h4><?= esc($adminUsers) ?></h4>
        <p class="muted">Admins</p>
    </div>

    <div class="card mini">
        <h4><?= esc($cashierUsers) ?></h4>
        <p class="muted">Cashiers</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">User List</h2>
            <p class="muted" style="margin:6px 0 0;">Manage accounts, roles, status, and branch assignment.</p>
        </div>

        <div class="row" style="gap:10px;">
            <a class="btn secondary" href="<?= site_url('admin/activity-logs') ?>">Activity Logs</a>
            <a class="btn btn-primary" href="<?= site_url('admin/users/create') ?>">+ Add User</a>
        </div>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <!-- SEARCH / FILTER -->
    <form method="get" action="<?= site_url('admin/users') ?>" class="row"
        style="align-items:end; gap:10px; margin-bottom:18px; flex-wrap:wrap;">

        <div class="field" style="flex:1; margin-bottom:0; min-width:240px;">
            <label class="label" for="userSearch">Search users</label>
            <input type="search" id="userSearch" name="search" value="<?= esc($search ?? '') ?>" class="input"
                placeholder="Full name, username, or role">
        </div>

        <?php if (!empty($branches)): ?>
            <div class="field" style="min-width:220px; margin-bottom:0;">
                <label class="label" for="userBranch">Branch</label>
                <select id="userBranch" name="branch_id" class="input">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= esc($branch['id']) ?>" <?= (($branchId ?? '') == $branch['id']) ? 'selected' : '' ?>>
                            <?= esc($branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="field" style="min-width:160px; margin-bottom:0;">
            <label class="label" for="userRole">Role</label>
            <select id="userRole" name="role" class="input">
                <option value="">All Roles</option>
                <option value="admin" <?= (($filterRole ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="cashier" <?= (($filterRole ?? '') === 'cashier') ? 'selected' : '' ?>>Cashier</option>
            </select>
        </div>

        <div class="field" style="min-width:150px; margin-bottom:0;">
            <label class="label" for="userStatus">Status</label>
            <select id="userStatus" name="status" class="input">
                <option value="">All Statuses</option>
                <option value="active" <?= (($filterStatus ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= (($filterStatus ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="row" style="gap:10px;">
            <button type="submit" class="btn secondary">Search</button>
            <a href="<?= site_url('admin/users') ?>" class="btn secondary">Reset</a>
        </div>
    </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <table class="mobile-record-table" data-record-title="1" aria-label="Users">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th style="min-width:160px;">Created At</th>
                    <th style="min-width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $userBranch = $user['branch_name'] ?? null;

                        if (($user['role'] ?? '') === 'admin') {
                            $userBranchDisplay = 'All Branches';
                        } else {
                            $userBranchDisplay = !empty($userBranch) ? $userBranch : 'Unassigned';
                        }
                        ?>
                        <tr>
                            <td><?= esc($user['id']) ?></td>
                            <td><strong><?= esc($user['full_name']) ?></strong></td>
                            <td><?= esc($user['username']) ?></td>
                            <td><?= esc(ucfirst($user['role'])) ?></td>
                            <td><?= esc($userBranchDisplay) ?></td>

                            <td>
                                <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td><?= !empty($user['created_at']) ? esc(\App\Libraries\DisplayDate::dateTime($user['created_at'])) : '-' ?></td>

                            <td>
                                <div class="row" style="gap:6px; flex-wrap:wrap;">
                                    <a href="<?= site_url('admin/users/edit/' . $user['id']) ?>"
                                        class="btn secondary btn-sm">Edit</a>

                                    <a href="<?= site_url('admin/users/password/' . $user['id']) ?>"
                                        class="btn btn-sm">Password</a>

                                    <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                        <form method="post" action="<?= site_url('admin/users/deactivate/' . $user['id']) ?>" class="inline-action-form"
                                            onsubmit="return confirm('Deactivate this user account?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn danger btn-sm" data-busy-label="Deactivating…">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= site_url('admin/users/activate/' . $user['id']) ?>" class="inline-action-form"
                                            onsubmit="return confirm('Activate this user account?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success btn-sm" data-busy-label="Activating…">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center; padding:20px;">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="paginationWrap">
        <?= $pager->links() ?>
    </div>
</div>

<?= $this->endSection() ?>
