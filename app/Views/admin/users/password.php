<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--standard">

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Change Password</h1>
    <p class="muted" style="margin:6px 0 0;">Update the password for <?= esc($user['full_name']) ?>.</p>
</div>

<?= view('partials/form_messages') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Password Information</h2>
            <p class="muted" style="margin:6px 0 0;">Enter and confirm the new password for this user.</p>
        </div>

        <a class="btn secondary" href="<?= site_url('admin/users') ?>">← Back</a>
    </div>

    <form action="<?= site_url('admin/users/password-update/' . $user['id']) ?>" method="post">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <div class="row" style="gap:20px; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:280px;">
                <label for="password" class="label">New Password <span class="required-mark" aria-hidden="true">*</span></label>
                <input type="password" name="password" id="password" class="input" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>

            <div class="field" style="flex:1; min-width:280px;">
                <label for="confirm_password" class="label">Confirm Password <span class="required-mark" aria-hidden="true">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" class="input" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>
        </div>
        <span class="form-help">Use at least 8 characters and enter the same password twice.</span>

        <div class="sp"></div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/users'), 'submitLabel' => 'Update Password']) ?>
    </form>
</div>

</div>

<?= $this->endSection() ?>