<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--wide">

<?php
$isCashier = $isCashier ?? ((string) session('role') === 'cashier');
$assignedBranch = $assignedBranch ?? null;
$assignedBranchId = (int) ($assignedBranch['id'] ?? session('branch_id') ?? 0);
$assignedBranchName = (string) ($assignedBranch['branch_name'] ?? 'Assigned branch');
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1><?= $isCashier ? 'Add Product to Your Branch' : 'Add Product' ?></h1>
        <p>
            <?= $isCashier
                ? 'Create a product record for ' . esc($assignedBranchName) . '. The branch cannot be changed.'
                : 'Create a new product and assign it to a branch.' ?>
        </p>
    </div>
    <a href="<?= site_url('products') ?>" class="btn btn-secondary">← Back</a>
</div>

<?= view('partials/form_messages') ?>

<?php if ($isCashier): ?>
    <div class="product-role-note">
        <p>Already in the catalog? <a href="<?= site_url('products/catalog') ?>">Choose an existing product</a> to reuse its name, SKU, category, and unit.</p>
        <strong>Your branch:</strong>
        This product will be added only to <strong><?= esc($assignedBranchName) ?></strong>.
        When the SKU already belongs to an existing product, the system will add that product to your branch instead of creating a duplicate.
        Ask an administrator when shared product information needs to be corrected later.
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('products/store') ?>" id="product-create-form">
    <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

    <div class="product-create-layout">
        <div>
            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><div><h2>Product details</h2><p>Name, category, SKU, and selling unit.</p></div></div>
                <div class="card-body">
                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-1">Product Name <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-1" type="text" name="product_name" class="input" maxlength="150" autocomplete="off"
                                value="<?= old('product_name') ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-2">Category <span style="color:var(--danger);">*</span></label>
                            <select id="editor-field-2" name="category_id" class="input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc($cat['id']) ?>" <?= old('category_id') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= esc($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-3">SKU</label>
                            <input id="editor-field-3" type="text" name="sku" class="input" maxlength="50" autocomplete="off"
                                value="<?= old('sku') ?>" placeholder="Optional, but recommended">
                            <span class="form-help">Use the same SKU when the product already exists in another branch.</span>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-4">Unit <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-4" type="text" name="unit" class="input" maxlength="30" autocomplete="off"
                                value="<?= old('unit') ?>" placeholder="e.g. box, tablet, bottle" required>
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
                                value="<?= old('cost_price') ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-6">Selling price (₱) <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-6" type="number" step="0.01" min="0" name="price" class="input"
                                value="<?= old('price') ?>" required>
                        </div>
                    </div>

                    <div class="product-form-grid">
                        <div class="field">
                            <label class="label" for="editor-field-7">Initial Stock <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-7" type="number" min="0" name="stock" class="input"
                                value="<?= old('stock', 0) ?>" required>
                        </div>
                        <div class="field">
                            <label class="label" for="editor-field-8">Reorder Level <span style="color:var(--danger);">*</span></label>
                            <input id="editor-field-8" type="number" min="0" name="reorder_level" class="input"
                                value="<?= old('reorder_level', 0) ?>" required>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="editor-field-9">Expiration Date</label>
                        <input id="editor-field-9" type="date" name="expiration_date" class="input" value="<?= old('expiration_date') ?>">
                        <span class="form-help">Leave blank only when the item has no applicable expiry date.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Manufacturer & Supplier</h2></div>
                <div class="card-body">
                    <div class="field">
                        <label class="label" for="editor-field-10">Manufacturer</label>
                        <input id="editor-field-10" type="text" name="manufacturer" class="input" maxlength="150" autocomplete="organization"
                            value="<?= old('manufacturer') ?>" placeholder="e.g. Unilab, GSK, Pfizer">
                    </div>
                    <div class="field">
                        <label class="label" for="editor-field-11">Supplier</label>
                        <select id="editor-field-11" name="supplier_id" class="input">
                            <option value="">— No Supplier —</option>
                            <?php foreach ($suppliers as $sup): ?>
                                <option value="<?= esc($sup['id']) ?>" <?= old('supplier_id') == $sup['id'] ? 'selected' : '' ?>>
                                    <?= esc($sup['supplier_name']) ?>
                                    <?= !empty($sup['contact_person']) ? ' — ' . esc($sup['contact_person']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$isCashier): ?>
                            <small style="font-size:12px;color:var(--muted);">
                                <a href="<?= site_url('admin/suppliers/create') ?>" target="_blank" rel="noopener">+ Add new supplier</a>
                            </small>
                        <?php else: ?>
                            <span class="form-help">Ask an administrator when the supplier is not yet listed.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-create-side">
            <div class="card">
                <div class="card-head"><h2>Branch Assignment</h2></div>
                <div class="card-body">
                    <?php if ($isCashier): ?>
                        <input type="hidden" name="branch_id" value="<?= esc($assignedBranchId) ?>">
                        <div class="assigned-branch-box">
                            <strong><?= esc($assignedBranchName) ?></strong>
                            <span class="form-help">Locked to the branch assigned to your cashier account.</span>
                        </div>
                    <?php else: ?>
                        <div class="field">
                            <label class="label" for="editor-field-12">Assign to Branch <span class="required-mark">*</span></label>
                            <select id="editor-field-12" name="branch_id" class="input" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $br): ?>
                                    <option value="<?= esc($br['id']) ?>" <?= old('branch_id') == $br['id'] ? 'selected' : '' ?>>
                                        <?= esc($br['branch_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="form-help">Initial stock and expiry will be recorded for this branch.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


        </div>
    </div>
    <?= view('partials/editor_actions', ['cancelUrl' => site_url('products'), 'submitLabel' => ($isCashier ? 'Add to My Branch' : 'Save Product')]) ?>
</form>

</div>

<?= $this->endSection() ?>
