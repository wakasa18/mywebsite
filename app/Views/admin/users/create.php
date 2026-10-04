<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--standard">

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Add User</h1>
    <p class="muted" style="margin:6px 0 0;">Create a new system user account.</p>
</div>

<?= view('partials/form_messages') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">User Information</h2>
            <p class="muted" style="margin:6px 0 0;">Enter details for the new user account.</p>
        </div>

        <a class="btn secondary" href="<?= site_url('admin/users') ?>">← Back</a>
    </div>

    <form action="<?= site_url('admin/users/store') ?>" method="post">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <h3 class="editor-subheading">Account details</h3>
        <div class="row" style="gap:20px; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:280px;">
                <label for="full_name" class="label">Full Name <span class="required-mark" aria-hidden="true">*</span></label>
                <input type="text" name="full_name" id="full_name" value="<?= old('full_name') ?>" class="input" maxlength="100" autocomplete="name" required>
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label for="username" class="label">Username <span class="required-mark" aria-hidden="true">*</span></label>
                <input type="text" name="username" id="username" value="<?= old('username') ?>" class="input" maxlength="50" autocomplete="username" required>
            </div>
        </div>

        <h3 class="editor-subheading">Password</h3>
        <div class="row" style="gap:20px; flex-wrap:wrap; margin-top:4px;">
            <div class="field" style="flex:1; min-width:280px;">
                <label for="password" class="label">Password <span class="required-mark" aria-hidden="true">*</span></label>
                <input type="password" name="password" id="password" class="input" minlength="8" maxlength="255" autocomplete="new-password" required>
                <span class="form-help">Use at least 8 characters.</span>
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label for="confirm_password" class="label">Confirm Password <span class="required-mark" aria-hidden="true">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" class="input" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>
        </div>

        <h3 class="editor-subheading">Access and branch</h3>
        <div class="row" style="gap:20px; flex-wrap:wrap; margin-top:4px;">
            <div class="field" style="flex:1; min-width:280px;">
                <label for="role" class="label">Account role <span class="required-mark" aria-hidden="true">*</span></label>
                <select name="role" id="role" class="input" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="cashier" <?= old('role') === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                </select>
            </div>
        </div>

        <div class="row" style="gap:20px; flex-wrap:wrap; margin-top:4px; display:none;" id="branchWrapper">
            <div class="field" style="flex:1; min-width:280px; max-width:calc(50% - 10px);">
                <label for="branch_id" class="label">Branch</label>
                <select name="branch_id" id="branch_id" class="input" disabled>
                    <option value="">-- Select Branch --</option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                        <option value="<?= esc($branch['id']) ?>" <?= (string) old('branch_id') === (string) $branch['id'] ? 'selected' : '' ?>>
                            <?= esc($branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="muted">Required when the selected role is Cashier.</small>
            </div>
        </div>

        <div class="sp"></div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/users'), 'submitLabel' => 'Save User']) ?>
    </form>
</div>

<script>
(function () {
    const roleSelect = document.getElementById('role');
    const branchWrapper = document.getElementById('branchWrapper');
    const branchSelect = document.getElementById('branch_id');

    function toggleBranch() {
        const isCashier = roleSelect.value === 'cashier';
        branchWrapper.style.display = isCashier ? 'flex' : 'none';
        branchSelect.disabled = !isCashier;
        branchSelect.required = isCashier;

        if (!isCashier) {
            branchSelect.value = '';
        }
    }

    roleSelect.addEventListener('change', toggleBranch);
    toggleBranch();
})();
</script>

</div>

<?= $this->endSection() ?>
