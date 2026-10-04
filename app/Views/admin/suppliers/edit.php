<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--details">

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1>Edit Supplier</h1>
        <p>Update supplier information.</p>
    </div>
    <a href="<?= site_url('admin/suppliers') ?>" class="btn btn-secondary">← Back</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if ($validation->getErrors()): ?>
    <div class="alert danger"><strong>Please check the following:</strong><ul style="margin:8px 0 0;padding-left:20px;">
        <?php foreach ($validation->getErrors() as $err): ?><li><?= esc($err) ?></li><?php endforeach; ?>
    </ul></div>
<?php endif; ?>

<div class="card">
    <div class="card-head"><h2>Supplier Information</h2></div>
    <div class="card-body">
        <form method="post" action="<?= site_url('admin/suppliers/update/' . $supplier['id']) ?>">
            <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

            <div class="field">
                <label class="label" for="editor-field-1">Supplier Name <span style="color:var(--danger);">*</span></label>
                <input id="editor-field-1" type="text" name="supplier_name" maxlength="150" autocomplete="organization" class="input"
                    value="<?= old('supplier_name', $supplier['supplier_name']) ?>" required>
            </div>

            <div style="display:grid;grid-template-columns:var(--grid-2col);gap:14px;">
                <div class="field">
                    <label class="label" for="editor-field-2">Contact Person</label>
                    <input id="editor-field-2" type="text" name="contact_person" maxlength="100" autocomplete="name" class="input"
                        value="<?= old('contact_person', $supplier['contact_person'] ?? '') ?>">
                </div>
                <div class="field">
                    <label class="label" for="editor-field-3">Contact Number</label>
                    <input id="editor-field-3" type="text" name="contact_number" maxlength="30" inputmode="tel" autocomplete="tel" class="input"
                        value="<?= old('contact_number', $supplier['contact_number'] ?? '') ?>">
                </div>
            </div>

            <div class="field">
                <label class="label" for="supplier-email">Email Address</label>
                <input type="email" id="supplier-email" name="email" maxlength="150" autocomplete="email" class="input"
                    value="<?= old('email', $supplier['email'] ?? '') ?>" placeholder="e.g. orders@supplier.com">
                <small class="muted" style="display:block;margin-top:6px;">Optional. Use the supplier's active email for orders and inquiries.</small>
            </div>

            <div class="field">
                <label class="label" for="editor-field-4">Address</label>
                <textarea id="editor-field-4" name="address" class="input" rows="3" maxlength="500" autocomplete="street-address"><?= old('address', $supplier['address'] ?? '') ?></textarea>
            </div>

            <div class="field" style="max-width:200px;">
                <label class="label" for="editor-field-5">Status <span class="required-mark" aria-hidden="true">*</span></label>
                <select id="editor-field-5" name="status" class="input" required>
                    <option value="active"   <?= old('status', $supplier['status']) === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= old('status', $supplier['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/suppliers'), 'submitLabel' => 'Save Changes']) ?>
        </form>
    </div>
</div>

</div>

<?= $this->endSection() ?>
