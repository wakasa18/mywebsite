<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--standard">

<div class="page-header">
    <h1>Edit branch inventory</h1>
    <p>Update stock, pricing, and availability for <?= esc($item['branch_name']) ?>.</p>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Inventory details</h2>
            <p>
                <?= esc($item['product_name']) ?> —
                <?= esc($item['branch_name']) ?>
            </p>
        </div>

        <a href="<?= site_url('admin/branch-inventory?branch_id=' . $item['branch_id']) ?>"
            class="btn secondary">Back</a>
    </div>

    <?= view('partials/form_messages') ?>

    <form method="post" action="<?= site_url('admin/branch-inventory/update/' . $item['id']) ?>">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <div class="row" style="gap:16px;">
            <div style="flex:1; min-width:250px;">
                <label class="label" for="editor-field-1">Product</label>
                <input id="editor-field-1" type="text" class="input" value="<?= esc($item['product_name']) ?>" readonly>
            </div>

            <div style="flex:1; min-width:250px;">
                <label class="label" for="editor-field-2">SKU</label>
                <input id="editor-field-2" type="text" class="input" value="<?= esc($item['sku'] ?: '-') ?>" readonly>
            </div>
        </div>

        <div class="sp"></div>

        <div class="row" style="gap:16px;">
            <div style="flex:1; min-width:220px;">
                <label class="label" for="editor-field-3">Stock <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-3" type="number" name="stock" class="input" min="0" value="<?= old('stock', $item['stock']) ?>"
                    required>
            </div>

            <div style="flex:1; min-width:220px;">
                <label class="label" for="editor-field-4">Reorder Level <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-4" type="number" name="reorder_level" class="input" min="0"
                    value="<?= old('reorder_level', $item['reorder_level']) ?>" required>
            </div>
        </div>

        <div class="sp"></div>

        <div class="row" style="gap:16px;">
            <div style="flex:1; min-width:220px;">
                <label class="label" for="editor-field-5">Price <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-5" type="number" step="0.01" min="0" name="price" class="input"
                    value="<?= old('price', $item['price']) ?>" required>
            </div>

            <div style="flex:1; min-width:220px;">
                <label class="label" for="editor-field-6">Cost Price <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-6" type="number" step="0.01" min="0" name="cost_price" class="input"
                    value="<?= old('cost_price', $item['cost_price']) ?>" required>
            </div>
        </div>

        <div class="sp"></div>

        <div class="row" style="gap:16px; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:220px;">
                <label class="label" for="editor-field-7">Expiration Date</label>
                <input id="editor-field-7" type="date" name="expiration_date" class="input"
                    value="<?= old('expiration_date', $item['expiration_date'] ?? '') ?>">
            </div>
            <div class="field" style="flex:2; min-width:280px;">
                <label class="label" for="editor-field-8">Reason for Stock Change</label>
                <input id="editor-field-8" type="text" name="correction_reason" class="input" maxlength="255"
                    value="<?= old('correction_reason') ?>" placeholder="Required only when changing stock">
                <span class="form-help">Examples: physical count, damaged medicine, or received delivery.</span>
            </div>
        </div>

        <div class="sp"></div>

        <div style="max-width:320px;">
            <label class="label" for="editor-field-9">Status <span class="required-mark" aria-hidden="true">*</span></label>
            <select id="editor-field-9" name="status" class="input" required>
                <option value="active" <?= old('status', $item['status']) === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= old('status', $item['status']) === 'inactive' ? 'selected' : '' ?>>Inactive
                </option>
            </select>
        </div>

        <div class="sp"></div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/branch-inventory?branch_id=' . $item['branch_id']), 'submitLabel' => 'Save Changes']) ?>
    </form>
</div>

</div>

<?= $this->endSection() ?>
