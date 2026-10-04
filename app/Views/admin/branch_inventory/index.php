<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    /* CARDS SMALLER */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .cards .card {
        padding: 14px;
    }

</style>

<div class="page-header">
    <h1>Branch Inventory</h1>
    <p class="muted">Manage stock, pricing, and reorder levels per branch.</p>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
    <div class="sp"></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <div class="sp"></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Inventory List</h2>
            <p class="muted">View and manage branch product inventory.</p>
        </div>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <!-- FILTER -->
    <form method="get" action="<?= site_url('admin/branch-inventory') ?>" class="row"
        style="gap:10px; align-items:end; margin-bottom:18px; flex-wrap:wrap;">

        <div class="field" style="min-width:220px; flex:1; margin-bottom:0;">
            <label class="label">Branch</label>
            <select name="branch_id" class="input" onchange="this.form.submit()">
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= esc($branch['id']) ?>" <?= (int) $branchId === (int) $branch['id'] ? 'selected' : '' ?>>
                        <?= esc($branch['branch_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="min-width:220px; flex:1; margin-bottom:0;">
            <label class="label">Search Product</label>
            <input type="text" name="q" class="input" value="<?= esc($keyword) ?>" placeholder="Search name or SKU">
        </div>

        <div class="row" style="gap:10px;">
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="<?= site_url('admin/branch-inventory?branch_id=' . $branchId) ?>" class="btn secondary">Reset</a>
        </div>
    </form>

    <!-- SUMMARY CARDS -->
    <?php if ($selectedBranch): ?>
        <div class="cards">
            <div class="card">
                <h3><?= esc($selectedBranch['branch_name']) ?></h3>
                <p class="muted">Selected Branch</p>
            </div>

            <div class="card">
                <h3><?= esc($summary['total_products'] ?? 0) ?></h3>
                <p class="muted">Products</p>
            </div>

            <div class="card">
                <h3><?= esc($summary['low_stock'] ?? 0) ?></h3>
                <p class="muted">Low Stock</p>
            </div>

            <div class="card">
                <h3><?= esc($summary['total_stock'] ?? 0) ?></h3>
                <p class="muted">Total Units</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Reorder</th>
                    <th>Price</th>
                    <th>Cost</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th width="120">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inventory)): ?>
                    <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><strong><?= esc($item['product_name']) ?></strong></td>
                            <td><?= esc($item['sku'] ?: '-') ?></td>
                            <td><?= esc($item['unit'] ?: '-') ?></td>

                            <td>
                                <?= esc($item['stock']) ?>
                                <?php if ((int) $item['stock'] <= (int) $item['reorder_level']): ?>
                                    <div style="margin-top:4px;">
                                        <span class="badge badge-warning">Low</span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td><?= esc($item['reorder_level']) ?></td>
                            <td>₱<?= number_format((float) $item['price'], 2) ?></td>
                            <td>₱<?= number_format((float) $item['cost_price'], 2) ?></td>
                            <td><?= !empty($item['expiration_date']) ? esc(\App\Libraries\DisplayDate::date($item['expiration_date'])) : '—' ?></td>

                            <td>
                                <?php if ($item['status'] === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?= site_url('admin/branch-inventory/edit/' . $item['id']) ?>"
                                    class="btn secondary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="muted" style="text-align:center; padding:20px;">
                            No inventory found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if (!empty($pager)): ?>
        <div class="paginationWrap">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>