<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--wide">

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1>Edit Product</h1>
        <p>Update product information and branch details.</p>
    </div>
    <a href="<?= site_url('products') ?>" class="btn btn-secondary">← Back</a>
</div>

<?= view('partials/form_messages') ?>

<form method="post" action="<?= site_url('products/update/' . $product['id']) ?>">
    <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>
    <input type="hidden" name="branch_id" value="<?= esc($branchId) ?>">
    <input type="hidden" name="old_stock" value="<?= esc(old('old_stock', $branchProduct['stock'] ?? 0)) ?>">

    <div class="product-create-layout">

        <!-- LEFT -->
        <div>
            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><div><h2>Product details</h2><p>Name, category, SKU, and selling unit.</p></div></div>
                <div class="card-body">
                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-1">Product Name <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-1" type="text" name="product_name" class="input" maxlength="150" autocomplete="off"
                                value="<?= old('product_name', $product['product_name']) ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-2">Category <span style="color:var(--danger);">*</span></label>
                            <select id="editor-field-2" name="category_id" class="input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc($cat['id']) ?>"
                                        <?= old('category_id', $product['category_id']) == $cat['id'] ? 'selected' : '' ?>>
                                        <?= esc($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-3">SKU</label>
                            <input id="editor-field-3" type="text" name="sku" class="input" maxlength="100" autocomplete="off"
                                value="<?= old('sku', $product['sku']) ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-4">Unit <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-4" type="text" name="unit" class="input" maxlength="50" autocomplete="off"
                                value="<?= old('unit', $product['unit']) ?>" required>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><div><h2>Pricing and stock</h2><p>Set prices in pesos and manage stock for this branch.</p></div></div>
                <div class="card-body">
                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-5">Cost price (₱) <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-5" type="number" step="0.01" min="0" name="cost_price" class="input"
                                value="<?= old('cost_price', $product['cost_price']) ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-6">Selling price (₱) <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-6" type="number" step="0.01" min="0" name="price" class="input"
                                value="<?= old('price', $product['price']) ?>" required>
                        </div>
                    </div>

                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-7">Stock <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-7" type="number" min="0" name="stock" class="input"
                                value="<?= old('stock', $branchProduct['stock'] ?? $product['stock']) ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-8">Reorder Level <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-8" type="number" min="0" name="reorder_level" class="input"
                                value="<?= old('reorder_level', $branchProduct['reorder_level'] ?? $product['reorder_level']) ?>" required>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="editor-field-9">Reason for Stock Change</label>
                        <input id="editor-field-9" type="text" name="stock_reason" class="input" maxlength="255"
                            value="<?= old('stock_reason') ?>" placeholder="Required only when changing the stock quantity">
                        <span class="form-help">Examples: physical count, damaged items, or received delivery.</span>
                    </div>

                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-10">Expiration Date</label>
                            <input id="editor-field-10" type="date" name="expiration_date" class="input"
                                value="<?= old('expiration_date', $branchProduct['expiration_date'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-11">Status <span style="color:var(--danger);">*</span></label>
                            <select id="editor-field-11" name="status" class="input" required>
                                <option value="active"   <?= old('status', $branchProduct['status'] ?? $product['status']) === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= old('status', $branchProduct['status'] ?? $product['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manufacturer & Supplier -->
            <div class="card">
                <div class="card-head"><h2>Manufacturer & Supplier</h2></div>
                <div class="card-body">
                    <div class="field">
                        <label class="label" for="editor-field-12">Manufacturer</label>
                        <input id="editor-field-12" type="text" name="manufacturer" class="input" maxlength="150" autocomplete="organization"
                            value="<?= old('manufacturer', $product['manufacturer'] ?? '') ?>"
                            placeholder="e.g. Unilab, GSK, Pfizer">
                    </div>
                    <div class="field">
                        <label class="label" for="editor-field-13">Supplier</label>
                        <select id="editor-field-13" name="supplier_id" class="input">
                            <option value="">— No Supplier —</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= esc($sup['id']) ?>"
                                    <?= old('supplier_id', $product['supplier_id'] ?? '') == $sup['id'] ? 'selected' : '' ?>>
                                    <?= esc($sup['supplier_name']) ?>
                                    <?= $sup['contact_person'] ? ' — ' . esc($sup['contact_person']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="font-size:12px;color:var(--muted);">
                            <a href="<?= site_url('admin/suppliers/create') ?>" target="_blank">+ Add new supplier</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="product-create-side">
            <div class="card" style="margin-bottom:14px;">
                <div class="card-head"><h2>Branch</h2></div>
                <div class="card-body">
                    <p style="font-size:14px;color:var(--muted);">Editing branch:</p>
                    <p style="font-weight:700;font-size:15px;margin-top:4px;">
                        <?php
                        $currentBranch = array_filter($branches, fn($b) => $b['id'] == $branchId);
                        echo esc(reset($currentBranch)['branch_name'] ?? 'All Branches');
                        ?>
                    </p>
                </div>
            </div>


        </div>

    </div>
    <?= view('partials/editor_actions', ['cancelUrl' => site_url('products'), 'submitLabel' => 'Save Changes']) ?>
</form>

</div>

<?= $this->endSection() ?>
