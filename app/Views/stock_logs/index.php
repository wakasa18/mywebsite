<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Stock Logs</h1>
    <p class="muted" style="margin:6px 0 0;">Track stock movements per branch.</p>
</div>


<?php if (!empty($filterNotices)): ?>
    <div class="alert warning" style="margin-bottom:16px;">
        <strong>Filter notice:</strong> <?= esc(implode(' ', $filterNotices)) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
    <div class="sp"></div>
<?php endif; ?>

<?php
$flashError = session()->getFlashdata('error');
$isAdminOnlyMsg = str_contains((string)$flashError, 'Admin only');
if ($flashError && !(session('role') !== 'admin' && $isAdminOnlyMsg)):
?>
    <div class="alert danger">
        <?= esc($flashError) ?>
    </div>
    <div class="sp"></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Stock Log Records</h2>
            <p class="muted" style="margin:6px 0 0;">Search, filter, and export stock movement records.</p>
        </div>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <form method="get" action="<?= site_url('stock-logs') ?>" class="row"
        style="gap:10px; margin-bottom:18px; flex-wrap:wrap; align-items:end;">

        <div class="field" style="flex:1; min-width:240px; margin-bottom:0;">
            <label class="label">Search</label>
            <input type="text" name="search" value="<?= esc($search ?? '') ?>" class="input"
                placeholder="Product, user, action, remarks">
        </div>

        <div class="field" style="min-width:200px; margin-bottom:0;">
            <label class="label">Branch</label>
            <?php if (!empty($isCashier)): ?>
                <input type="hidden" name="branch_id" value="<?= esc($filterBranch ?? '') ?>">
                <input type="text" class="input" value="<?= esc($branches[0]['branch_name'] ?? 'No assigned branch') ?>" readonly>
            <?php else: ?>
                <select name="branch_id" class="input">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ((string) ($filterBranch ?? '') === (string) $b['id']) ? 'selected' : '' ?>>
                            <?= esc($b['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="field" style="min-width:170px; margin-bottom:0;">
            <label class="label">Type</label>
            <?php
                $actionTypeLabels = [
                    'stock_in' => 'Stock In',
                    'stock_out' => 'Stock Out',
                    'adjustment' => 'Adjustment',
                    'expired' => 'Expired',
                    'returned' => 'Returned',
                ];
            ?>
            <select name="action_type" class="input">
                <option value="">All Types</option>
                <?php foreach ($actionTypeLabels as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= (($filterActionType ?? '') === $value) ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="min-width:170px; margin-bottom:0;">
            <label class="label">Date From</label>
            <input type="date" name="date_from" value="<?= esc($dateFrom ?? '') ?>" class="input">
        </div>

        <div class="field" style="min-width:170px; margin-bottom:0;">
            <label class="label">Date To</label>
            <input type="date" name="date_to" value="<?= esc($dateTo ?? '') ?>" class="input">
        </div>

        <div class="row" style="gap:10px;">
            <button class="btn btn-secondary" type="submit">Filter</button>
            <a href="<?= site_url('stock-logs') ?>" class="btn secondary">Reset</a>
            <a href="<?= site_url('stock-logs/export-excel?search=' . urlencode($search ?? '') . '&branch_id=' . urlencode($filterBranch ?? '') . '&action_type=' . urlencode($filterActionType ?? '') . '&date_from=' . urlencode($dateFrom ?? '') . '&date_to=' . urlencode($dateTo ?? '')) ?>"
                class="btn secondary">
                Excel CSV
            </a>
            <a href="<?= site_url('stock-logs/export-pdf?search=' . urlencode($search ?? '') . '&branch_id=' . urlencode($filterBranch ?? '') . '&action_type=' . urlencode($filterActionType ?? '') . '&date_from=' . urlencode($dateFrom ?? '') . '&date_to=' . urlencode($dateTo ?? '')) ?>"
                class="btn btn-secondary">
                Export PDF
            </a>
        </div>
    </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Product</th>
                    <th>Branch</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Prev</th>
                    <th>New</th>
                    <th>Remarks</th>
                    <th style="min-width:170px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= esc($log['id']) ?></td>
                            <td><strong><?= esc($log['product_name'] ?? '-') ?></strong></td>
                            <td><?= esc($log['branch_name'] ?? '-') ?></td>
                            <td><?= esc($log['full_name'] ?? '-') ?></td>
                            <td>
                                <?php if ($log['action_type'] === 'stock_in'): ?>
                                    <span class="badge badge-success">Stock In</span>
                                <?php elseif ($log['action_type'] === 'stock_out'): ?>
                                    <span class="badge badge-danger">Stock Out</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <?= esc(ucwords(str_replace('_', ' ', $log['action_type']))) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($log['quantity']) ?></td>
                            <td><?= esc($log['previous_stock']) ?></td>
                            <td><?= esc($log['new_stock']) ?></td>
                            <td><?= esc($log['remarks'] ?? '-') ?></td>
                            <td><?= esc(\App\Libraries\DisplayDate::dateTime($log['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="muted" style="text-align:center; padding:20px;">
                            No stock logs found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)): ?>
        <div class="paginationWrap">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>