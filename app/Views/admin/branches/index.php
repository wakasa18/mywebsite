<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
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
    <h1 style="margin:0;">Branch Management</h1>
    <p class="muted" style="margin:6px 0 0;">View and manage all branch records.</p>
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

<div class="cards compact-cards" style="margin-bottom:20px;">
    <div class="card mini">
        <h4>
            <?= esc($totalBranches) ?>
        </h4>
        <p class="muted">Total</p>
    </div>
    <div class="card mini">
        <h4>
            <?= esc($activeBranches) ?>
        </h4>
        <p class="muted">Active</p>
    </div>
    <div class="card mini">
        <h4>
            <?= esc($inactiveBranches) ?>
        </h4>
        <p class="muted">Inactive</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Branch List</h2>
            <p class="muted" style="margin:6px 0 0;">Manage branch name, code, address, contact number, and status.</p>
        </div>

        <div class="row" style="gap:10px;">
            <a class="btn btn-primary" href="<?= site_url('admin/branches/create') ?>">+ Add Branch</a>
        </div>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <form method="get" action="<?= site_url('admin/branches') ?>" class="row"
        style="align-items:end; gap:10px; margin-bottom:18px;">
        <div class="field" style="flex:1; margin-bottom:0; min-width:260px;">
            <label class="label">Search Branch</label>
            <input type="text" name="search" value="<?= esc($search ?? '') ?>" class="input"
                placeholder="Branch name, code, address, or contact">
        </div>

        <div class="field" style="min-width:150px; margin-bottom:0;">
            <label class="label">Status</label>
            <select name="status" class="input">
                <option value="">All Statuses</option>
                <option value="active" <?= (($filterStatus ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= (($filterStatus ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="row" style="gap:10px;">
            <button type="submit" class="btn secondary">Search</button>
            <a href="<?= site_url('admin/branches') ?>" class="btn secondary">Reset</a>
        </div>
    </form>
    </div>

    <div class="table-wrap">
        <table class="mobile-record-table" data-record-title="1">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Branch Name</th>
                    <th>Branch Code</th>
                    <th>Address</th>
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th style="min-width:160px;">Created At</th>
                    <th style="min-width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($branches)): ?>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td>
                                <?= esc($branch['id']) ?>
                            </td>
                            <td><strong>
                                    <?= esc($branch['branch_name']) ?>
                                </strong></td>
                            <td>
                                <?= esc($branch['branch_code']) ?>
                            </td>
                            <td>
                                <?= esc($branch['address'] ?: '-') ?>
                            </td>
                            <td>
                                <?= esc($branch['contact_number'] ?: '-') ?>
                            </td>
                            <td>
                                <?php if (($branch['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= esc(\App\Libraries\DisplayDate::dateTime($branch['created_at'] ?? '-')) ?>
                            </td>
                            <td>
                                <div class="row" style="gap:6px; flex-wrap:wrap;">
                                    <a href="<?= site_url('admin/branches/edit/' . $branch['id']) ?>"
                                        class="btn secondary btn-sm">Edit</a>
                                    <a href="<?= site_url('products') . '?branch_id=' . (int) $branch['id'] ?>"
                                        class="btn secondary btn-sm">View inventory</a>
                                    <a href="<?= site_url(($branch['status'] ?? 'active') === 'active' ? 'admin/reports' : 'cashier/sales/history') . '?branch_id=' . (int) $branch['id'] ?>"
                                        class="btn secondary btn-sm"
                                        title="<?= ($branch['status'] ?? 'active') === 'active' ? 'Open sales reports for this branch' : 'Open sales history for this inactive branch' ?>">View sales</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center; padding:20px;">
                            No branches found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="paginationWrap">
        <?= $pager->links() ?>
    </div>
</div>

<?= $this->endSection() ?>
