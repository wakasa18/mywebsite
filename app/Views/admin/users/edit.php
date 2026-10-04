<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--standard">

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Edit User</h1>
    <p class="muted" style="margin:6px 0 0;">Update system user details and account settings.</p>
</div>

<?= view('partials/form_messages') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">User Information</h2>
            <p class="muted">Modify the selected user record.</p>
        </div>

        <a class="btn secondary" href="<?= site_url('admin/users') ?>">← Back</a>
    </div>

    <form action="<?= site_url('admin/users/update/' . $user['id']) ?>" method="post">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <!-- NAME + USERNAME -->
        <h3 class="editor-subheading">Account details</h3>
        <div class="row" style="gap:20px; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-1">Full Name <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-1" type="text" name="full_name" value="<?= old('full_name', $user['full_name']) ?>" class="input" maxlength="100" autocomplete="name" required>
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-2">Username <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="editor-field-2" type="text" name="username" value="<?= old('username', $user['username']) ?>" class="input" maxlength="50" autocomplete="username" required>
            </div>
        </div>

        <!-- ROLE + STATUS -->
        <h3 class="editor-subheading">Access and status</h3>
        <div class="row" style="gap:20px; flex-wrap:wrap; margin-top:4px;">
            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="role">Role <span class="required-mark" aria-hidden="true">*</span></label>
                <select name="role" id="role" class="input" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin" <?= old('role', $user['role']) == 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="cashier" <?= old('role', $user['role']) == 'cashier' ? 'selected' : '' ?>>Cashier
                    </option>
                </select>
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="editor-field-3">Status <span class="required-mark" aria-hidden="true">*</span></label>
                <select id="editor-field-3" name="status" class="input" required>
                    <option value="active" <?= old('status', $user['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>
                        Active</option>
                    <option value="inactive" <?= old('status', $user['status'] ?? 'active') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <!-- ✅ BRANCH SELECT -->
        <div class="row" style="gap:20px; flex-wrap:wrap; margin-top:4px;" id="branchWrapper">
            <div class="field" style="flex:1; min-width:280px;">
                <label class="label" for="branch_id">Branch</label>
                <select name="branch_id" id="branch_id" class="input">
                    <option value="">-- Select Branch --</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= esc($branch['id']) ?>" <?= old('branch_id', $user['branch_id'] ?? '') == $branch['id'] ? 'selected' : '' ?>>
                            <?= esc($branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="muted">Only required for Cashier</small>
            </div>
        </div>

        <div class="sp"></div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/users'), 'submitLabel' => 'Save Changes']) ?>
    </form>
</div>

<!-- ✅ SCRIPT: AUTO SHOW/HIDE BRANCH -->
<script>
    const roleSelect = document.getElementById('role');
    const branchWrapper = document.getElementById('branchWrapper');
    const branchSelect = document.getElementById('branch_id');

    function toggleBranch() {
        const isCashier = roleSelect.value === 'cashier';
        branchWrapper.style.display = isCashier ? 'flex' : 'none';
        branchSelect.disabled = !isCashier;
        branchSelect.required = isCashier;
        if (!isCashier) branchSelect.value = '';
    }

    roleSelect.addEventListener('change', toggleBranch);
    toggleBranch();
</script>

</div>

<?= $this->endSection() ?>
