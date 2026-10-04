<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--standard">

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Edit Branch</h1>
    <p class="muted" style="margin:6px 0 0;">Update branch details and settings.</p>
</div>

<?= view('partials/form_messages') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Branch Information</h2>
            <p class="muted">Modify the selected branch record.</p>
        </div>

        <a class="btn secondary" href="<?= site_url('admin/branches') ?>">← Back</a>
    </div>

    <form action="<?= site_url('admin/branches/update/' . $branch['id']) ?>" method="post">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <div class="row" style="gap:20px; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-1">Branch Name <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-1" type="text" name="branch_name" maxlength="100" autocomplete="organization" value="<?= old('branch_name', $branch['branch_name']) ?>"
                    class="input" required>
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-2">Branch Code <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-2" type="text" name="branch_code" maxlength="20" autocomplete="off" oninput="this.value=this.value.toUpperCase()" value="<?= old('branch_code', $branch['branch_code']) ?>"
                    class="input" required>
                <span class="form-help">Use a short unique code, such as PATAG or MAIN.</span>
            </div>
        </div>

        <div class="row" style="gap:20px; flex-wrap:wrap; margin-top:4px;">
            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-3">Contact Number</label>
                <input id="editor-field-3" type="text" name="contact_number" maxlength="30" inputmode="tel" autocomplete="tel" value="<?= old('contact_number', $branch['contact_number']) ?>"
                    class="input">
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-4">Status <span class="required-mark" aria-hidden="true">*</span></label>
                <select id="editor-field-4" name="status" class="input" required>
                    <option value="active" <?= old('status', $branch['status']) === 'active' ? 'selected' : '' ?>>Active
                    </option>
                    <option value="inactive" <?= old('status', $branch['status']) === 'inactive' ? 'selected' : '' ?>
                        >Inactive</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label class="label" for="editor-field-5">Address</label>
            <textarea id="editor-field-5" name="address" class="input" rows="4" maxlength="500" autocomplete="street-address"><?= old('address', $branch['address']) ?></textarea>
        </div>

        <div class="sp"></div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/branches'), 'submitLabel' => 'Save Changes']) ?>
    </form>
</div>

</div>

<?= $this->endSection() ?>