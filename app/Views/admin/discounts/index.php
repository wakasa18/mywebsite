<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    .discount-toolbar { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .discount-toolbar-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .discount-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .discount-stat { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 16px; text-decoration:none; color:inherit; transition:transform .15s,border-color .15s; }
    .discount-stat:hover { transform:translateY(-1px); border-color:var(--primary); }
    .discount-stat strong { display:block; font-size:22px; line-height:1; color:var(--text); }
    .discount-stat span { display:block; margin-top:5px; font-size:11.5px; color:var(--muted); }
    .stat-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .stat-dot.active { background:var(--success); }
    .stat-dot.scheduled { background:var(--primary); }
    .stat-dot.expired { background:#d97706; }
    .stat-dot.inactive { background:var(--muted); }
    .discount-filter-wrap { padding:15px 20px 17px; border-bottom:1px solid var(--line); }
    .discount-filters { display:grid; grid-template-columns:minmax(220px,1.5fr) repeat(3,minmax(145px,.65fr)) auto; gap:10px; align-items:end; }
    .discount-filters .field { margin:0; min-width:0; }
    .discount-filter-actions { display:flex; gap:8px; }
    .discount-value { font-weight:800; font-size:14px; color:var(--text); white-space:nowrap; }
    .discount-subtext { margin-top:3px; font-size:11.5px; line-height:1.4; color:var(--muted); }
    .discount-type-pill, .discount-scope-pill { display:inline-flex; align-items:center; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:750; white-space:nowrap; }
    .discount-type-pill.percentage { background:var(--primary-light); color:var(--primary); }
    .discount-type-pill.fixed { background:var(--success-bg); color:var(--success-text); }
    .discount-scope-pill { max-width:210px; border:1px solid var(--line); background:var(--surface-2); color:var(--text-2); border-radius:7px; white-space:normal; line-height:1.3; }
    .discount-status { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; white-space:nowrap; }
    .discount-status::before { content:''; width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .discount-status.active { color:var(--success-text); }
    .discount-status.active::before { background:var(--success); }
    .discount-status.scheduled { color:var(--primary); }
    .discount-status.scheduled::before { background:var(--primary); }
    .discount-status.expired { color:#b45309; }
    .discount-status.expired::before { background:#d97706; }
    .discount-status.inactive { color:var(--muted); }
    .discount-status.inactive::before { background:var(--muted); }
    .discount-table { min-width:1100px; }
    .discount-actions { display:flex; gap:6px; flex-wrap:wrap; }
    .discount-empty { padding:42px 20px !important; text-align:center; color:var(--muted); }
    .discount-empty strong { display:block; margin-bottom:5px; color:var(--text); }

    @media(max-width:1050px){
        .discount-filters { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .discount-filter-actions { grid-column:1/-1; }
    }
    @media(max-width:760px){
        .discount-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .discount-filters { grid-template-columns:1fr; }
        .discount-filter-actions { grid-column:auto; }
        .discount-filter-actions .btn { flex:1; justify-content:center; }
        .discount-toolbar-actions { width:100%; }
        .discount-toolbar-actions .btn { flex:1; justify-content:center; }
        .discount-filter-wrap { padding:14px 15px 16px; }
    }
    @media(max-width:420px){
        .discount-stats { gap:8px; }
        .discount-stat { padding:13px; }
        .discount-stat strong { font-size:19px; }
    }
</style>

<div class="page-header discount-toolbar">
    <div>
        <h1>Discounts</h1>
        <p>Create clear checkout discounts and control when they are available.</p>
    </div>
    <div class="discount-toolbar-actions">
        <a href="<?= site_url('admin/discounts/trash') ?>" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6m3 0V4h8v2"/></svg>
            Trash
        </a>
        <a href="<?= site_url('admin/discounts/create') ?>" class="btn btn-primary">+ New Discount</a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?><div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

<div class="discount-stats" aria-label="Discount status summary">
    <a class="card discount-stat" href="<?= site_url('admin/discounts?status=active') ?>"><div><strong><?= (int) ($stats['active'] ?? 0) ?></strong><span>Available now</span></div><i class="stat-dot active"></i></a>
    <a class="card discount-stat" href="<?= site_url('admin/discounts?status=scheduled') ?>"><div><strong><?= (int) ($stats['scheduled'] ?? 0) ?></strong><span>Scheduled</span></div><i class="stat-dot scheduled"></i></a>
    <a class="card discount-stat" href="<?= site_url('admin/discounts?status=expired') ?>"><div><strong><?= (int) ($stats['expired'] ?? 0) ?></strong><span>Ended</span></div><i class="stat-dot expired"></i></a>
    <a class="card discount-stat" href="<?= site_url('admin/discounts?status=inactive') ?>"><div><strong><?= (int) ($stats['inactive'] ?? 0) ?></strong><span>Turned off</span></div><i class="stat-dot inactive"></i></a>
</div>

<div class="card">
    <div class="card-head">
        <div><h2>Discount List</h2><p><?= (int) $totalResults ?> matching discount<?= (int) $totalResults === 1 ? '' : 's' ?></p></div>
    </div>

    <div class="discount-filter-wrap">
        <form method="get" action="<?= site_url('admin/discounts') ?>" class="discount-filters">
            <div class="field">
                <label class="label" for="discountKeyword">Search</label>
                <input type="search" id="discountKeyword" name="keyword" class="input" value="<?= esc($keyword) ?>" placeholder="Name, description, category, or product">
            </div>
            <div class="field">
                <label class="label" for="discountStatusFilter">Availability</label>
                <select id="discountStatusFilter" name="status" class="input">
                    <option value="">All</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Available now</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Ended</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Turned off</option>
                </select>
            </div>
            <div class="field">
                <label class="label" for="discountTypeFilter">Type</label>
                <select id="discountTypeFilter" name="type" class="input">
                    <option value="">All types</option>
                    <option value="percentage" <?= $type === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                    <option value="fixed" <?= $type === 'fixed' ? 'selected' : '' ?>>Fixed amount</option>
                </select>
            </div>
            <div class="field">
                <label class="label" for="discountScopeFilter">Products Included</label>
                <select id="discountScopeFilter" name="scope" class="input">
                    <option value="">All scopes</option>
                    <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>All products</option>
                    <option value="category" <?= $scope === 'category' ? 'selected' : '' ?>>One category</option>
                    <option value="product" <?= $scope === 'product' ? 'selected' : '' ?>>One product</option>
                </select>
            </div>
            <div class="discount-filter-actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="<?= site_url('admin/discounts') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table class="discount-table">
            <thead><tr><th>Discount</th><th>Amount</th><th>Products Included</th><th>Minimum</th><th>Available Dates</th><th>Used</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!empty($discounts)): ?>
                <?php foreach ($discounts as $discount): ?>
                <tr>
                    <td>
                        <strong><?= esc($discount['discount_name']) ?></strong>
                        <?php if (!empty($discount['description'])): ?><div class="discount-subtext"><?= esc($discount['description']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <span class="discount-type-pill <?= esc($discount['discount_type']) ?>"><?= $discount['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed' ?></span>
                        <div class="discount-value" style="margin-top:6px;"><?= $discount['discount_type'] === 'percentage' ? rtrim(rtrim(number_format((float) $discount['discount_value'],2),'0'),'.') . '%' : '₱' . number_format((float) $discount['discount_value'],2) ?></div>
                        <?php if ($discount['discount_type'] === 'percentage' && (float) ($discount['max_discount_amount'] ?? 0) > 0): ?><div class="discount-subtext">Up to ₱<?= number_format((float) $discount['max_discount_amount'],2) ?></div><?php endif; ?>
                    </td>
                    <td><span class="discount-scope-pill"><?= esc($discount['scope_label']) ?></span></td>
                    <td><?= (float) ($discount['minimum_purchase'] ?? 0) > 0 ? '₱' . number_format((float) $discount['minimum_purchase'],2) : '<span class="muted">None</span>' ?></td>
                    <td>
                        <?php if (empty($discount['start_date']) && empty($discount['end_date'])): ?>
                            <span class="muted">No date limit</span>
                        <?php else: ?>
                            <div><?= !empty($discount['start_date']) ? esc(\App\Libraries\DisplayDate::date($discount['start_date'])) : 'Immediately' ?></div>
                            <div class="discount-subtext">to <?= !empty($discount['end_date']) ? esc(\App\Libraries\DisplayDate::date($discount['end_date'])) : 'no end date' ?></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= (int) ($discount['usage_count'] ?? 0) ?></strong><div class="discount-subtext">sale<?= (int) ($discount['usage_count'] ?? 0) === 1 ? '' : 's' ?></div></td>
                    <td><span class="discount-status <?= esc($discount['computed_status']) ?>"><?= $discount['computed_status'] === 'active' ? 'Available' : ($discount['computed_status'] === 'scheduled' ? 'Scheduled' : ($discount['computed_status'] === 'expired' ? 'Ended' : 'Off')) ?></span></td>
                    <td>
                        <div class="discount-actions">
                            <a href="<?= site_url('admin/discounts/edit/' . (int) $discount['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="post" action="<?= site_url('admin/discounts/toggle/' . (int) $discount['id']) ?>" class="inline-action-form" onsubmit="return confirm('<?= $discount['status'] === 'active' ? 'Turn off this discount? Cashiers will no longer see it.' : 'Make this discount available when its dates allow?' ?>');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm <?= $discount['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>" data-busy-label="Saving…"><?= $discount['status'] === 'active' ? 'Turn Off' : 'Activate' ?></button>
                            </form>
                            <form method="post" action="<?= site_url('admin/discounts/delete/' . (int) $discount['id']) ?>" class="inline-action-form" onsubmit="return confirm('Move this discount to trash? Existing sales will not be changed.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-sm" data-busy-label="Moving…">Trash</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="discount-empty"><strong>No discounts match these filters.</strong>Try resetting the filters or create a new discount.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)): ?><div class="paginationWrap"><?= $pager->only(['keyword','status','type','scope'])->links() ?></div><?php endif; ?>
</div>

<?= $this->endSection() ?>
