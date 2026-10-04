<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--compact">

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Edit Category</h1>
    <p class="muted" style="margin:6px 0 0;">Update category details and save changes.</p>
</div>

<?= view('partials/form_messages') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Category Information</h2>
            <p class="muted" style="margin:6px 0 0;">Modify the selected category record.</p>
        </div>

        <a class="btn secondary" href="<?= site_url('categories') ?>">← Back</a>
    </div>

    <form method="post" action="<?= site_url('categories/update/' . $category['id']) ?>">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <div class="field">
            <label class="label" for="editor-field-1">Category name <span class="required-mark" aria-hidden="true">*</span></label>
            <input id="editor-field-1" type="text" name="category_name" class="input"
                value="<?= old('category_name', $category['category_name']) ?>" maxlength="100" autocomplete="off" required>
        </div>

        <div class="sp"></div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('categories'), 'submitLabel' => 'Save Changes']) ?>
    </form>
</div>

</div>

<?= $this->endSection() ?>
