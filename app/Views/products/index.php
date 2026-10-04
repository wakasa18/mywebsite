<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<style>
.stock-update-form{display:grid;grid-template-columns:minmax(0,1fr) 85px;gap:8px;min-width:215px;max-width:300px;white-space:normal;text-align:left}
.stock-update-form .input{min-width:0;width:100%;min-height:44px}
.stock-update-form .stock-current,.stock-update-form .stock-reason,.stock-update-form .stock-preview,.stock-update-form .stock-submit{grid-column:1/-1}
.stock-update-form .stock-preview{font-size:12px;color:var(--muted);white-space:normal}
@media(max-width:700px){.stock-update-form{min-width:0;max-width:none;width:100%}.content .mobile-record-ready td:has(.stock-update-form){grid-column:1/-1}}
</style>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div>
        <h1>Products</h1>
        <p>Manage product records, stock, pricing and expiry by branch.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <?php if (session('role') === 'cashier'): ?>
            <a href="<?= site_url('products/catalog') ?>" class="btn btn-primary">+ Add from Existing Products</a>
        <?php endif; ?>
        <a href="<?= site_url('products/create') ?>" class="btn <?= session('role') === 'cashier' ? 'btn-secondary' : 'btn-primary' ?>">
            <?= session('role') === 'cashier' ? '+ Create New Product' : '+ Add Product' ?>
        </a>
        <?php if (session('role') === 'admin'): ?>
            <a href="<?= site_url('products/trash') ?>" class="btn btn-secondary"><?= view('partials/icon', ['name' => 'trash']) ?> Trash</a>
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert danger"><ul style="margin:0;padding-left:20px;"><?php foreach (session()->getFlashdata('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Product List</h2>
            <p>
                <?php if (session('role') === 'cashier'): ?>
                    Showing products for your assigned branch. Add or remove stock by entering the number of units and a reason.
                <?php else: ?>
                    Filter by branch, search and edit products. Move to Trash affects only the selected branch.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="catalogue-filters">
        <form method="get" action="<?= site_url('products') ?>" class="catalogue-filter-form">
            <div>
                <label for="filterBranch">Branch</label>
                <select id="filterBranch" name="branch_id" class="input" <?= session('role') === 'cashier' ? 'disabled' : '' ?>>
                    <?php if (session('role') !== 'cashier'): ?><option value="">All Branches</option><?php endif; ?>
                    <?php if (!empty($branches)): ?>
                        <?php foreach ($branches as $branch): ?>
                        <option value="<?= esc($branch['id']) ?>" <?= (($branchId ?? '') == $branch['id']) ? 'selected' : '' ?>>
                            <?= esc($branch['branch_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php if (session('role') === 'cashier'): ?>
                    <input type="hidden" name="branch_id" value="<?= esc($branchId) ?>">
                <?php endif; ?>
            </div>
            <div>
                <label for="filterCategory">Category</label>
                <select id="filterCategory" name="category_id" class="input">
                    <option value="">All Categories</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= esc($category['id']) ?>" <?= (($categoryId ?? '') == $category['id']) ? 'selected' : '' ?>>
                            <?= esc($category['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label for="filterStatus">Status</label>
                <select id="filterStatus" name="status" class="input">
                    <option value="">All Statuses</option>
                    <option value="active" <?= (($status ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= (($status ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label for="filterStock">Stock level</label>
                <select id="filterStock" name="stock_filter" class="input">
                    <option value="">All Stock Levels</option>
                    <option value="low" <?= (($stockFilter ?? '') === 'low') ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out" <?= (($stockFilter ?? '') === 'out') ? 'selected' : '' ?>>Out of Stock</option>
                    <option value="healthy" <?= (($stockFilter ?? '') === 'healthy') ? 'selected' : '' ?>>Above Reorder Level</option>
                </select>
            </div>
            <div class="filter-search">
                <label for="filterKeyword">Search products</label>
                <input type="search" id="filterKeyword" name="keyword" class="input" value="<?= esc($keyword ?? '') ?>" placeholder="Product name, SKU, category…">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-secondary">Search</button>
                <a href="<?= site_url('products') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table class="mobile-record-table catalogue-table" data-record-title="1" aria-label="Products">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Product</th>
                    <th>Branch</th>
                    <th>Category</th>
                    <th>Manufacturer</th>
                    <th>Supplier</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                    <?php
                        $expiry = $product['branch_expiration_date'] ?? $product['expiration_date'] ?? null;
                        $isExpired = $expiry && strtotime($expiry) < strtotime(date('Y-m-d'));
                        $isNearExpiry = $expiry && !$isExpired && strtotime($expiry) <= strtotime(date('Y-m-d', strtotime('+30 days')));
                        $statusValue = $product['branch_status'] ?? $product['status'] ?? 'inactive';
                        $costPrice = $product['branch_cost_price'] ?? $product['cost_price'] ?? 0;
                        $sellingPrice = $product['branch_price'] ?? $product['price'] ?? 0;
                        $stockValue = $product['branch_stock'] ?? $product['stock'] ?? 0;
                        $cashierBranchId = (int)(session('branch_id') ?? 0);
                        $rowBranchId = (int)($product['branch_id'] ?? 0);
                        $canCashierEdit = session('role') === 'cashier' && $cashierBranchId === $rowBranchId;
                    ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:12px;"><?= esc($product['id']) ?></td>
                        <td><strong style="font-size:13px;"><?= esc($product['product_name']) ?></strong></td>
                        <td style="font-size:12.5px;"><?= esc($product['branch_name'] ?? '—') ?></td>
                        <td style="font-size:12.5px;"><?= esc($product['category_name'] ?? '—') ?></td>
                        <td style="font-size:12.5px;"><?= esc($product['manufacturer'] ?: '—') ?></td>
                        <td style="font-size:12.5px;"><?= esc($product['supplier_name'] ?: '—') ?></td>
                        <td style="font-family:monospace;font-size:12px;"><?= esc($product['sku'] ?: '—') ?></td>
                        <td style="font-size:12.5px;"><?= esc($product['unit'] ?: '—') ?></td>
                        <td style="font-size:13px;">₱<?= number_format((float)$costPrice, 2) ?></td>
                        <td style="font-size:13px;font-weight:600;">₱<?= number_format((float)$sellingPrice, 2) ?></td>
                        <td>
                            <?php
                                $reorderLevel = (int) ($product['branch_reorder_level'] ?? $product['reorder_level'] ?? 0);
                                $isOutOfStock = (int) $stockValue <= 0;
                                $isLowStock = !$isOutOfStock && (int) $stockValue <= $reorderLevel;
                            ?>
                            <?php if ($canCashierEdit): ?>
                                <?php
                                    $stockToken = bin2hex(random_bytes(24));
                                    session()->set('stock_adjustment_' . (int) $product['id'] . '_' . (int) $product['branch_id'], $stockToken);
                                ?>
                                <form method="post" action="<?= site_url('products/update-stock') ?>" class="stock-update-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="stock_token" value="<?= esc($stockToken, 'attr') ?>">
                                    <input type="hidden" name="product_id" value="<?= esc($product['id']) ?>">
                                    <input type="hidden" name="branch_id" value="<?= esc($product['branch_id']) ?>">
                                    <input type="hidden" name="old_stock" value="<?= esc($stockValue) ?>">
                                    <strong class="stock-current">Current: <?= (int) $stockValue ?> <?= esc($product['unit'] ?? '') ?></strong>
                                    <select name="stock_mode" class="input" aria-label="Stock action for <?= esc($product['product_name'], 'attr') ?>">
                                        <option value="add">Add stock</option>
                                        <option value="remove">Remove stock</option>
                                    </select>
                                    <input type="number" name="stock" value="" class="input" aria-label="Units to add or remove for <?= esc($product['product_name'], 'attr') ?>" placeholder="Units" min="1" max="1000000" step="1" required>
                                    <input type="text" name="remarks" class="input stock-reason" aria-label="Stock change reason for <?= esc($product['product_name'], 'attr') ?>" placeholder="Reason / reference" minlength="3" maxlength="200" required>
                                    <span class="stock-preview" aria-live="polite">Enter units to see the new balance.</span>
                                    <button type="submit" class="btn btn-primary btn-sm stock-submit" data-busy-label="Saving…">Add stock</button>
                                </form>
                            <?php else: ?>
                                <span style="font-weight:600;"><?= esc($stockValue) ?></span>
                            <?php endif; ?>
                            <?php if ($isOutOfStock): ?>
                                <br><span class="badge badge-danger" style="margin-top:4px;">Out of Stock</span>
                            <?php elseif ($isLowStock): ?>
                                <br><span class="badge badge-warning" style="margin-top:4px;">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($expiry)): ?>
                                <span style="font-size:12px;"><?= esc(\App\Libraries\DisplayDate::date($expiry)) ?></span>
                                <?php if ($isExpired): ?>
                                    <br><span class="badge badge-danger" style="margin-top:3px;">Expired</span>
                                <?php elseif ($isNearExpiry): ?>
                                    <br><span class="badge badge-warning" style="margin-top:3px;">Near</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($statusValue === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (session('role') === 'admin'): ?>
                                <div class="action-group">
                                    <a href="<?= site_url('products/edit/' . $product['id'] . '?branch_id=' . urlencode($product['branch_id'] ?? ($branchId ?? ''))) ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <?php if ((int)($product['branch_id'] ?? 0) > 0): ?>
                                    <form method="post" action="<?= site_url('products/delete/' . $product['id']) ?>" class="inline-action-form"
                                        onsubmit="return confirm(this.dataset.confirm)" data-confirm="<?= esc('Move ' . $product['product_name'] . ' to Trash in ' . ($product['branch_name'] ?? 'this branch') . ' only? Its stock will be retained. Other branches will keep this product.', 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="branch_id" value="<?= (int)($product['branch_id'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" data-busy-label="Moving…">Move to Trash</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($canCashierEdit): ?>
                                <span class="badge badge-success">Your Branch</span>
                            <?php else: ?>
                                <span class="muted" style="font-size:12px;">View Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="14" class="muted" style="text-align:center;padding:24px;">No products found.</td></tr>
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

<script>
document.querySelectorAll('.stock-update-form').forEach(function(form){
    const mode = form.querySelector('[name="stock_mode"]');
    const input = form.querySelector('[name="stock"]');
    const current = Number(form.querySelector('[name="old_stock"]').value);
    const preview = form.querySelector('.stock-preview');
    const button = form.querySelector('button[type="submit"]');
    function updatePreview() {
        const removing = mode.value === 'remove';
        input.max = String(removing ? Math.min(current, 1000000) : 1000000);
        button.textContent = removing ? 'Remove stock' : 'Add stock';
        input.setCustomValidity('');
        const units = Number(input.value);
        if (!input.value || !Number.isInteger(units) || units < 1) {
            preview.textContent = 'Enter units to see the new balance.';
            return;
        }
        if (removing && units > current) {
            preview.textContent = 'Cannot remove more than the current stock.';
            input.setCustomValidity('Removal quantity exceeds current stock.');
            return;
        }
        preview.textContent = current + (removing ? ' − ' : ' + ') + units + ' = ' + (removing ? current - units : current + units) + ' units after saving';
    }
    mode.addEventListener('change', updatePreview);
    input.addEventListener('input', updatePreview);
    form.addEventListener('submit', function(event){
        const reason = form.querySelector('[name="remarks"]');
        reason.value = reason.value.trim();
        updatePreview();
        if (!form.reportValidity()) {
            event.preventDefault();
        }
    });
    updatePreview();
});
</script>

<?= $this->endSection() ?>
