<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1>🗑 Product Trash</h1>
        <p>Restore a branch product or delete it permanently from this system. Database records and history are retained.</p>
    </div>
    <a href="<?= site_url('products') ?>" class="btn btn-secondary">← Back to Products</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<!-- Search bar -->
<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <select class="input" name="branch_id" aria-label="Filter trash by branch" style="max-width:240px">
        <option value="">All Branches</option>
        <?php foreach (($branches ?? []) as $branch): ?><option value="<?= (int)$branch['id'] ?>" <?= (int)($branchId ?? 0)===(int)$branch['id']?'selected':'' ?>><?= esc($branch['branch_name']) ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="keyword" class="input" style="max-width:300px;"
        placeholder="Search by name or SKU…" value="<?= esc($keyword) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <a href="<?= site_url('products/trash') ?>" class="btn btn-secondary">Reset</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="mobile-record-table" data-record-title="1" aria-label="Deleted products">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Branch</th>
                    <th>Stock retained</th>
                    <th>Deleted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $i => $p): ?>
                    <tr>
                        <td class="muted" style="font-size:13px;"><?= $i + 1 ?></td>
                        <td style="font-weight:700;font-size:15px;"><?= esc($p['product_name']) ?></td>
                        <td style="font-family:'DM Mono',monospace;font-size:13px;"><?= esc($p['sku'] ?: '—') ?></td>
                        <td><?= esc($p['category_name'] ?? '—') ?></td>
                        <td><?= esc($p['supplier_name'] ?? '—') ?></td>
                        <td><?= esc($p['branch_name'] ?? '—') ?></td>
                        <td><?= (int)($p['branch_stock'] ?? 0) ?> <?= esc($p['unit'] ?? '') ?></td>
                        <td style="font-size:13px;color:var(--muted);"><?= esc(\App\Libraries\DisplayDate::dateTime($p['deleted_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <form method="post" action="<?= site_url('products/restore/' . $p['id']) ?>" class="inline-action-form" onsubmit="return confirm(this.dataset.confirm)" data-confirm="<?= esc('Restore this product in ' . ($p['branch_name'] ?? 'this branch') . ' only?', 'attr') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="branch_id" value="<?= (int)($p['branch_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" data-busy-label="Restoring…">Restore</button>
                                </form>
                                <form method="post" action="<?= site_url('products/force-delete/' . $p['id']) ?>" class="inline-action-form" onsubmit="return confirm(this.dataset.confirm)" data-confirm="<?= esc('Delete this product permanently from ' . ($p['branch_name'] ?? 'this branch') . ' and remove it from Trash? Other branches are unaffected. Its database record and stock history are retained. You cannot restore it here.', 'attr') ?>">
                                    <?= csrf_field() ?><input type="hidden" name="branch_id" value="<?= (int)($p['branch_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" data-busy-label="Removing…">Delete permanently</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">No branch products in Trash for these filters.</td></tr>
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

<?php if (!empty($legacyProducts)): ?>
<div class="card" style="margin-top:20px">
    <div class="card-head"><div><h2>Older shared product deletions</h2><p>These products were deleted before branch-specific Trash. Restore here restores the shared catalog record for all of its branches. Review branch status afterward.</p></div></div>
    <div class="table-wrap"><table class="mobile-record-table" data-record-title="0" aria-label="Older shared product deletions">
        <thead><tr><th>Product</th><th>SKU</th><th>Deleted at</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($legacyProducts as $legacy): ?><tr>
            <td><?= esc($legacy['product_name']) ?></td><td><?= esc($legacy['sku'] ?: '—') ?></td>
            <td><?= esc(\App\Libraries\DisplayDate::dateTime($legacy['deleted_at'])) ?></td>
            <td><div style="display:flex;gap:6px;flex-wrap:wrap;"><form method="post" action="<?= site_url('products/restore/' . $legacy['id']) ?>" class="inline-action-form" onsubmit="return confirm('Restore this older shared product for all of its branches?');">
                <?= csrf_field() ?><input type="hidden" name="legacy_restore" value="1"><button type="submit" class="btn btn-secondary btn-sm">Restore Shared Product</button>
            </form><form method="post" action="<?= site_url('products/force-delete/' . $legacy['id']) ?>" class="inline-action-form" onsubmit="return confirm('Permanently hide this older shared deletion from Trash? Its existing branch records and transaction history are retained. You cannot restore the shared product here afterward.');">
                <?= csrf_field() ?><input type="hidden" name="legacy_delete" value="1"><button type="submit" class="btn btn-danger btn-sm" data-busy-label="Removing…">Delete permanently</button>
            </form></div></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
