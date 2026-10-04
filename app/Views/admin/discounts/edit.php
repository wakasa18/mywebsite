<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--wide">

<div class="page-header">
    <h1>Edit Discount</h1>
    <p>Update the discount rules used at checkout.</p>
</div>

<?= view('admin/discounts/_form', [
    'formTitle' => 'Edit ' . ($form['discount_name'] ?: 'Discount'),
    'formSubtitle' => 'Changes take effect the next time a cashier opens or refreshes the POS page.',
    'formAction' => site_url('admin/discounts/update/' . (int) $discount['id']),
    'submitLabel' => 'Save Changes',
    'form' => $form,
    'validation' => $validation,
    'categories' => $categories,
    'products' => $products,
]) ?>

</div>

<?= $this->endSection() ?>
