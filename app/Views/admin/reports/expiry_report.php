<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<style>
.expiry-row-actions{display:flex;flex-wrap:wrap;gap:8px;min-width:210px}
.expiry-row-actions .btn{white-space:normal;text-align:left}
@media(max-width:700px){.expiry-row-actions{min-width:0}.expiry-row-actions .btn{flex:1 1 100%}}
</style>

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Expiry Report</h1>
    <p class="muted" style="margin:6px 0 0;">Monitor expired and near-expiry products by branch.</p>
</div>

<?php if ($expiryError = session()->getFlashdata('error')): ?><div class="alert danger" role="alert"><?= esc($expiryError) ?></div><?php endif; ?>

<?php if (!empty($filterNotices)): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border:1px solid rgba(217,119,6,.28); border-radius:10px; background:var(--warning-bg); font-size:12.5px;">
        <?php foreach ($filterNotices as $notice): ?>
            <div><?= esc($notice) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Expiry Records</h2>
            <p class="muted" style="margin:6px 0 0;">Filter and export expiry status of products across branches.</p>
        </div>
        <a class="btn btn-secondary" href="<?= site_url('stock-logs?search=' . urlencode('Expiry resolution:')) ?>">Resolution history</a>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <form method="get" action="<?= site_url('admin/expiry-report') ?>" class="row"
        style="gap:10px; margin-bottom:18px; align-items:end; flex-wrap:wrap;">

        <div class="field" style="min-width:220px; margin-bottom:0;">
            <label class="label">Branch</label>
            <?php if (!empty($isCashier)): ?>
                <input type="hidden" name="branch_id" value="<?= esc($filterBranch ?? '') ?>">
                <input type="text" class="input" value="<?= esc($branches[0]['branch_name'] ?? 'No assigned branch') ?>" readonly>
            <?php else: ?>
                <select name="branch_id" class="input">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= esc($b['id']) ?>" <?= ((string) ($filterBranch ?? '') === (string) $b['id']) ? 'selected' : '' ?>>
                            <?= esc($b['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="field" style="min-width:200px; margin-bottom:0;">
            <label class="label">Expiry Status</label>
            <select name="status" class="input">
                <option value="all" <?= ($filterStatus ?? 'all') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="expired" <?= ($filterStatus ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                <option value="near" <?= ($filterStatus ?? '') === 'near' ? 'selected' : '' ?>>Near Expiry (<?= esc($nearExpiryDays ?? 30) ?> days)</option>
                <option value="good" <?= ($filterStatus ?? '') === 'good' ? 'selected' : '' ?>>Good</option>
                <option value="missing" <?= ($filterStatus ?? '') === 'missing' ? 'selected' : '' ?>>No Expiry Date</option>
            </select>
        </div>

        <div class="row" style="gap:10px;">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a href="<?= site_url('admin/expiry-report') ?>" class="btn btn-secondary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M3 12a9 9 0 1 0 2.64-6.36"/><polyline points="3 3 3 9 9 9"/>
                </svg>
                Reset
            </a>
            <a href="<?= site_url('admin/expiry-report/export-excel?branch_id=' . urlencode($filterBranch ?? '') . '&status=' . urlencode($filterStatus ?? 'all')) ?>"
                class="btn btn-secondary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Excel CSV
            </a>
            <a href="<?= site_url('admin/expiry-report/export-pdf?branch_id=' . urlencode($filterBranch ?? '') . '&status=' . urlencode($filterStatus ?? 'all')) ?>"
                class="btn btn-secondary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                PDF
            </a>
        </div>
    </form>
    </div>

    <div class="table-wrap">
        <table class="mobile-record-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Manufacturer</th>
                    <th>Supplier</th>
                    <th>Branch</th>
                    <th>Stock</th>
                    <th>Expiration Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $expiry = $p['expiration_date'] ?? null;
                        $isExpired = $expiry && $expiry < $today;
                        $isNear = $expiry && !$isExpired && $expiry <= $nearDate;
                        ?>
                        <tr>
                            <td><strong><?= esc($p['product_name'] ?? '-') ?></strong></td>
                            <td><?= esc($p['sku'] ?? '-') ?></td>
                            <td><?= esc($p['manufacturer'] ?: '—') ?></td>
                            <td><?= esc($p['supplier_name'] ?: '—') ?></td>
                            <td><?= esc($p['branch_name'] ?? '-') ?></td>
                            <td><?= esc($p['stock'] ?? 0) ?></td>
                            <td><?= esc(\App\Libraries\DisplayDate::date($expiry ?? '-')) ?></td>
                            <td>
                                <?php if (empty($expiry)): ?>
                                    <span class="badge badge-neutral">No Expiry Date</span>
                                <?php elseif ($isExpired): ?>
                                    <span class="badge badge-danger">Expired</span>
                                <?php elseif ($isNear): ?>
                                    <span class="badge badge-warning">Near Expiry</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Good</span>
                                <?php endif; ?>
                            </td>
                            <td><div class="expiry-row-actions">
                                <a class="btn btn-secondary" href="<?= site_url('admin/expiry-report/item/' . (int) $p['id'] . '?action=replace') ?>">Replace stock</a>
                                <?php if ((int) $p['stock'] > 0): ?><a class="btn btn-secondary" href="<?= site_url('admin/expiry-report/item/' . (int) $p['id'] . '?action=remove') ?>">Dispose / Return</a><?php endif; ?>
                                <?php if (session('role') === 'admin'): ?><a class="btn danger" href="<?= site_url('admin/expiry-report/item/' . (int) $p['id'] . '?action=deactivate') ?>">Deactivate in branch</a><?php endif; ?>
                                <a class="btn btn-secondary" href="<?= site_url('admin/expiry-report/item/' . (int) $p['id']) ?>">View history</a>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="muted" style="text-align:center; padding:20px;">
                            No expiry records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)): ?>
        <div class="paginationWrap">
            <?= $pager->only(['branch_id', 'status'])->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
