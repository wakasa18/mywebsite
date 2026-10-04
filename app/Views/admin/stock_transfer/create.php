<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="editor-page editor-page--standard">

<div class="page-header">
    <h1>Create stock transfer</h1>
    <p>Move product inventory between branches.</p>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Transfer details</h2>
            <p>Choose the source and destination, then add products and quantities.</p>
        </div>

        <a href="<?= site_url('admin/stock-transfer') ?>" class="btn secondary">Back</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
        <div class="sp"></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('admin/stock-transfer/store') ?>">
        <?= csrf_field() ?>
        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>

        <div class="row" style="gap:16px;">
            <div style="flex:1; min-width:260px;">
                <label class="label" for="editor-field-1">From Branch <span class="required-mark" aria-hidden="true">*</span></label>
                <select id="editor-field-1" name="from_branch_id" class="input" required>
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= esc($branch['id']) ?>">
                            <?= esc($branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1; min-width:260px;">
                <label class="label" for="editor-field-2">To Branch <span class="required-mark" aria-hidden="true">*</span></label>
                <select id="editor-field-2" name="to_branch_id" class="input" required>
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= esc($branch['id']) ?>">
                            <?= esc($branch['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="sp"></div>

        <h3 class="editor-subheading">Transfer items</h3>

        <div id="transferItems">
            <div class="row transfer-row" style="gap:12px; margin-bottom:12px;">
                <div style="flex:1; min-width:280px;">
                    <select name="items[0][product_id]" class="input" aria-label="Product to transfer" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= esc($product['id']) ?>">
                                <?= esc($product['product_name']) ?> (
                                <?= esc($product['sku'] ?: '-') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="width:160px;">
                    <input type="number" name="items[0][quantity]" class="input" min="1" placeholder="Quantity" aria-label="Transfer quantity"
                        required>
                </div>

                <div>
                    <button type="button" class="btn danger remove-item">Remove</button>
                </div>
            </div>
        </div>

        <button type="button" id="addItemBtn" class="btn secondary">+ Add Item</button>

        <div class="sp"></div>

        <div class="field">
            <label class="label" for="editor-field-3">Notes</label>
            <textarea id="editor-field-3" name="notes" class="input" rows="3"></textarea>
        </div>

        <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/stock-transfer'), 'submitLabel' => 'Submit Transfer']) ?>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('transferItems');
        const addBtn = document.getElementById('addItemBtn');
        let index = 1;

        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row transfer-row';
            row.style = 'gap:12px; margin-bottom:12px;';
            row.innerHTML = `
            <div style="flex:1; min-width:280px;">
                <select name="items[${index}][product_id]" class="input" aria-label="Product to transfer" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= esc($product['id']) ?>"><?= esc($product['product_name']) ?> (<?= esc($product['sku'] ?: '-') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="width:160px;">
                <input type="number" name="items[${index}][quantity]" class="input" min="1" placeholder="Quantity" aria-label="Transfer quantity" required>
            </div>
            <div>
                <button type="button" class="btn danger remove-item">Remove</button>
            </div>
        `;
            container.appendChild(row);
            index++;
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-item')) {
                const rows = document.querySelectorAll('.transfer-row');
                if (rows.length > 1) {
                    e.target.closest('.transfer-row').remove();
                }
            }
        });
    });
</script>

</div>

<?= $this->endSection() ?>
