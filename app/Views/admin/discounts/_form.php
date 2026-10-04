<style>
    .discount-form-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 300px;
        gap: 18px;
        align-items: start;
    }
    .discount-form-card { min-width: 0; }
    .discount-form-body { padding: 4px 20px 22px; }
    .discount-section { padding: 20px 0; border-bottom: 1px solid var(--line); }
    .discount-section:first-child { padding-top: 12px; }
    .discount-section:last-of-type { border-bottom: 0; }
    .discount-section-head { margin-bottom: 14px; }
    .discount-section-head h3 { margin: 0; font-size: 14px; color: var(--text); }
    .discount-section-head p { margin: 4px 0 0; font-size: 12.5px; line-height: 1.5; color: var(--muted); }
    .discount-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 16px; }
    .discount-grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .discount-grid .field { margin: 0; min-width: 0; }
    .discount-grid .span-2 { grid-column: 1 / -1; }
    .conditional-field { display: none; }
    .conditional-field.visible { display: block; }
    .field-help { margin-top: 5px; font-size: 11.5px; line-height: 1.45; color: var(--muted); }
    .required-mark { color: var(--danger); }
    .discount-form-actions { display: flex; gap: 10px; flex-wrap: wrap; padding-top: 18px; }
    .discount-preview { position: sticky; top: calc(var(--topbar-h) + 18px); overflow: hidden; }
    .discount-preview-body { padding: 18px; }
    .preview-label { font-size: 10px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase; color: var(--muted); }
    .preview-value { margin-top: 5px; font-size: 25px; font-weight: 800; letter-spacing: -.5px; color: var(--primary); }
    .preview-name { margin-top: 9px; font-size: 15px; font-weight: 800; color: var(--text); overflow-wrap: anywhere; }
    .preview-list { display: grid; gap: 10px; margin-top: 16px; }
    .preview-row { padding: 10px 11px; border-radius: 9px; border: 1px solid var(--line); background: var(--surface-2); }
    .preview-row strong { display: block; font-size: 11.5px; color: var(--text); }
    .preview-row span { display: block; margin-top: 3px; font-size: 11.5px; line-height: 1.45; color: var(--muted); overflow-wrap: anywhere; }
    .preview-note { margin-top: 14px; padding: 11px 12px; border-radius: 9px; background: var(--primary-light); color: var(--text-2); font-size: 11.5px; line-height: 1.5; }
    .form-error-list { margin: 8px 0 0; padding-left: 20px; }
    textarea.input { min-height: 82px; resize: vertical; }

    @media (max-width: 980px) {
        .discount-form-shell { grid-template-columns: 1fr; }
        .discount-preview { position: static; order: -1; }
        .discount-preview-body { display: grid; grid-template-columns: 1fr 1.5fr; gap: 16px; }
        .preview-list { margin-top: 0; }
        .preview-note { grid-column: 1 / -1; margin-top: 0; }
    }
    @media (max-width: 680px) {
        .discount-form-body { padding: 2px 15px 18px; }
        .discount-grid, .discount-grid.three { grid-template-columns: 1fr; gap: 13px; }
        .discount-grid .span-2 { grid-column: auto; }
        .discount-preview-body { grid-template-columns: 1fr; }
        .preview-note { grid-column: auto; }
        .discount-form-actions .btn { flex: 1 1 150px; justify-content: center; }
    }
</style>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if ($validation->getErrors()): ?>
    <div class="alert danger" role="alert">
        <strong>Please correct the highlighted fields.</strong>
        <ul class="form-error-list">
            <?php foreach ($validation->getErrors() as $message): ?>
                <li><?= esc($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="discount-form-shell">
    <div class="card discount-form-card">
        <div class="card-head">
            <div>
                <h2><?= esc($formTitle) ?></h2>
                <p><?= esc($formSubtitle) ?></p>
            </div>
            <a href="<?= site_url('admin/discounts') ?>" class="btn btn-secondary">← Back</a>
        </div>

        <form action="<?= esc($formAction) ?>" method="post" class="discount-form-body" id="discountForm">
            <?= csrf_field() ?>

            <section class="discount-section">
                <div class="discount-section-head">
                    <h3>1. Name and availability</h3>
                    <p>Give the discount a clear name and choose whether cashiers can use it.</p>
                </div>
                <div class="discount-grid">
                    <div class="field">
                        <label class="label" for="discountName">Discount Name <span class="required-mark">*</span></label>
                        <input type="text" id="discountName" name="discount_name" maxlength="100" autocomplete="off" required
                            class="input <?= $validation->hasError('discount_name') ? 'is-invalid' : '' ?>"
                            value="<?= esc($form['discount_name'] ?? '') ?>" placeholder="Example: Senior Citizen Discount">
                        <?php if ($validation->hasError('discount_name')): ?><div class="field-error"><?= esc($validation->getError('discount_name')) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="discountStatus">Cashier Availability <span class="required-mark">*</span></label>
                        <select name="status" id="discountStatus" class="input <?= $validation->hasError('status') ? 'is-invalid' : '' ?>" required>
                            <option value="active" <?= ($form['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Available when dates allow</option>
                            <option value="inactive" <?= ($form['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Not available</option>
                        </select>
                        <div class="field-help">Inactive discounts stay saved but cannot be selected at checkout.</div>
                        <?php if ($validation->hasError('status')): ?><div class="field-error"><?= esc($validation->getError('status')) ?></div><?php endif; ?>
                    </div>
                    <div class="field span-2">
                        <label class="label" for="discountDescription">Description</label>
                        <textarea id="discountDescription" name="description" maxlength="500" class="input <?= $validation->hasError('description') ? 'is-invalid' : '' ?>" placeholder="Optional note about who may use this discount or why it was created."><?= esc($form['description'] ?? '') ?></textarea>
                        <div class="field-help">This note is for the owner and staff. It is not printed on the receipt.</div>
                        <?php if ($validation->hasError('description')): ?><div class="field-error"><?= esc($validation->getError('description')) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="discount-section">
                <div class="discount-section-head">
                    <h3>2. Discount amount</h3>
                    <p>Choose a percentage or a fixed peso amount.</p>
                </div>
                <div class="discount-grid three">
                    <div class="field">
                        <label class="label" for="discountType">Discount Type <span class="required-mark">*</span></label>
                        <select name="discount_type" id="discountType" class="input <?= $validation->hasError('discount_type') ? 'is-invalid' : '' ?>" required>
                            <option value="percentage" <?= ($form['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                            <option value="fixed" <?= ($form['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount (₱)</option>
                        </select>
                        <?php if ($validation->hasError('discount_type')): ?><div class="field-error"><?= esc($validation->getError('discount_type')) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="discountValue">Value <span class="required-mark">*</span></label>
                        <input type="number" id="discountValue" name="discount_value" step="0.01" min="0.01" required
                            class="input <?= $validation->hasError('discount_value') ? 'is-invalid' : '' ?>"
                            value="<?= esc($form['discount_value'] ?? '') ?>" placeholder="0.00">
                        <?php if ($validation->hasError('discount_value')): ?><div class="field-error"><?= esc($validation->getError('discount_value')) ?></div><?php endif; ?>
                    </div>
                    <div class="field" id="maxDiscountWrap">
                        <label class="label" for="maxDiscount">Maximum Peso Discount</label>
                        <input type="number" id="maxDiscount" name="max_discount_amount" step="0.01" min="0.01"
                            class="input <?= $validation->hasError('max_discount_amount') ? 'is-invalid' : '' ?>"
                            value="<?= esc($form['max_discount_amount'] ?? '') ?>" placeholder="Optional">
                        <div class="field-help">For percentage discounts only. Example: 20% off, up to ₱500.</div>
                        <?php if ($validation->hasError('max_discount_amount')): ?><div class="field-error"><?= esc($validation->getError('max_discount_amount')) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="discount-section">
                <div class="discount-section-head">
                    <h3>3. Products included</h3>
                    <p>Choose whether the discount covers the whole cart, one category, or one product.</p>
                </div>
                <div class="discount-grid">
                    <div class="field">
                        <label class="label" for="appliesTo">Apply Discount To <span class="required-mark">*</span></label>
                        <select name="applies_to" id="appliesTo" class="input <?= $validation->hasError('applies_to') ? 'is-invalid' : '' ?>" required>
                            <option value="all" <?= ($form['applies_to'] ?? 'all') === 'all' ? 'selected' : '' ?>>All products in the cart</option>
                            <option value="category" <?= ($form['applies_to'] ?? '') === 'category' ? 'selected' : '' ?>>One product category</option>
                            <option value="product" <?= ($form['applies_to'] ?? '') === 'product' ? 'selected' : '' ?>>One specific product</option>
                        </select>
                        <?php if ($validation->hasError('applies_to')): ?><div class="field-error"><?= esc($validation->getError('applies_to')) ?></div><?php endif; ?>
                    </div>

                    <div class="field conditional-field <?= ($form['applies_to'] ?? '') === 'category' ? 'visible' : '' ?>" id="categoryField">
                        <label class="label" for="categorySelect">Category <span class="required-mark">*</span></label>
                        <select name="category_id" id="categorySelect" class="input <?= $validation->hasError('category_id') ? 'is-invalid' : '' ?>">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (string) ($form['category_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($validation->hasError('category_id')): ?><div class="field-error"><?= esc($validation->getError('category_id')) ?></div><?php endif; ?>
                    </div>

                    <div class="field conditional-field <?= ($form['applies_to'] ?? '') === 'product' ? 'visible' : '' ?>" id="productField">
                        <label class="label" for="productSelect">Product <span class="required-mark">*</span></label>
                        <select name="product_id" id="productSelect" class="input <?= $validation->hasError('product_id') ? 'is-invalid' : '' ?>">
                            <option value="">Select a product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= (int) $product['id'] ?>" <?= (string) ($form['product_id'] ?? '') === (string) $product['id'] ? 'selected' : '' ?>>
                                    <?= esc($product['product_name']) ?><?= !empty($product['sku']) ? ' (' . esc($product['sku']) . ')' : '' ?><?= !empty($product['unavailable']) ? ' — currently unavailable' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($validation->hasError('product_id')): ?><div class="field-error"><?= esc($validation->getError('product_id')) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="discount-section">
                <div class="discount-section-head">
                    <h3>4. Dates and minimum purchase</h3>
                    <p>Dates are optional. The minimum is checked only against products included in this discount.</p>
                </div>
                <div class="discount-grid three">
                    <div class="field">
                        <label class="label" for="startDate">Start Date</label>
                        <input type="date" id="startDate" name="start_date" class="input <?= $validation->hasError('start_date') ? 'is-invalid' : '' ?>" value="<?= esc($form['start_date'] ?? '') ?>">
                        <div class="field-help">Leave blank to allow it immediately.</div>
                        <?php if ($validation->hasError('start_date')): ?><div class="field-error"><?= esc($validation->getError('start_date')) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="endDate">End Date</label>
                        <input type="date" id="endDate" name="end_date" class="input <?= $validation->hasError('end_date') ? 'is-invalid' : '' ?>" value="<?= esc($form['end_date'] ?? '') ?>">
                        <div class="field-help">Leave blank if there is no end date.</div>
                        <?php if ($validation->hasError('end_date')): ?><div class="field-error"><?= esc($validation->getError('end_date')) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="minimumPurchase">Minimum Matching Purchase</label>
                        <input type="number" id="minimumPurchase" name="minimum_purchase" step="0.01" min="0"
                            class="input <?= $validation->hasError('minimum_purchase') ? 'is-invalid' : '' ?>"
                            value="<?= esc($form['minimum_purchase'] ?? '') ?>" placeholder="0.00">
                        <div class="field-help">Use 0 or leave blank for no minimum.</div>
                        <?php if ($validation->hasError('minimum_purchase')): ?><div class="field-error"><?= esc($validation->getError('minimum_purchase')) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <?= view('partials/editor_actions', ['cancelUrl' => site_url('admin/discounts'), 'submitLabel' => $submitLabel]) ?>
        </form>
    </div>

    <aside class="card discount-preview" aria-live="polite">
        <div class="card-head"><div><h2>Checkout Preview</h2><p>What this discount means in simple terms.</p></div></div>
        <div class="discount-preview-body">
            <div>
                <div class="preview-label">Discount</div>
                <div class="preview-value" id="previewValue">—</div>
                <div class="preview-name" id="previewName">Unnamed discount</div>
            </div>
            <div class="preview-list">
                <div class="preview-row"><strong>Products included</strong><span id="previewScope">All products in the cart</span></div>
                <div class="preview-row"><strong>Purchase requirement</strong><span id="previewMinimum">No minimum purchase</span></div>
                <div class="preview-row"><strong>Available period</strong><span id="previewDates">No date limit</span></div>
                <div class="preview-row"><strong>Cashier availability</strong><span id="previewStatus">Available when dates allow</span></div>
            </div>
            <div class="preview-note">At checkout, the system checks the selected products, dates, minimum purchase, and maximum discount again before completing the sale.</div>
        </div>
    </aside>
</div>

<script>
(function(){
    const appliesTo = document.getElementById('appliesTo');
    const categoryField = document.getElementById('categoryField');
    const productField = document.getElementById('productField');
    const categorySelect = document.getElementById('categorySelect');
    const productSelect = document.getElementById('productSelect');
    const discountType = document.getElementById('discountType');
    const discountValue = document.getElementById('discountValue');
    const maxWrap = document.getElementById('maxDiscountWrap');
    const maxDiscount = document.getElementById('maxDiscount');
    const minimumPurchase = document.getElementById('minimumPurchase');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const discountName = document.getElementById('discountName');
    const discountStatus = document.getElementById('discountStatus');

    function money(value){
        const number = Number(value || 0);
        return '₱' + number.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function selectedText(select){
        return select && select.selectedIndex >= 0 ? select.options[select.selectedIndex].text.replace(/— currently unavailable$/, '').trim() : '';
    }

    function updateScope(){
        const category = appliesTo.value === 'category';
        const product = appliesTo.value === 'product';
        categoryField.classList.toggle('visible', category);
        productField.classList.toggle('visible', product);
        categorySelect.disabled = !category;
        categorySelect.required = category;
        productSelect.disabled = !product;
        productSelect.required = product;
    }

    function updateType(){
        const percentage = discountType.value === 'percentage';
        maxWrap.style.display = percentage ? '' : 'none';
        maxDiscount.disabled = !percentage;
        discountValue.max = percentage ? '100' : '';
    }

    function updateDateLimit(){
        endDate.min = startDate.value || '';
        if (startDate.value && endDate.value && endDate.value < startDate.value) endDate.value = startDate.value;
    }

    function updatePreview(){
        const value = Number(discountValue.value || 0);
        const isPercentage = discountType.value === 'percentage';
        let valueText = value > 0 ? (isPercentage ? value.toLocaleString('en-PH', {maximumFractionDigits:2}) + '% off' : money(value) + ' off') : '—';
        if (isPercentage && Number(maxDiscount.value || 0) > 0 && value > 0) valueText += ' (up to ' + money(maxDiscount.value) + ')';
        document.getElementById('previewValue').textContent = valueText;
        document.getElementById('previewName').textContent = discountName.value.trim() || 'Unnamed discount';

        let scopeText = 'All products in the cart';
        if (appliesTo.value === 'category') scopeText = categorySelect.value ? 'Category: ' + selectedText(categorySelect) : 'Select a category';
        if (appliesTo.value === 'product') scopeText = productSelect.value ? 'Product: ' + selectedText(productSelect) : 'Select a product';
        document.getElementById('previewScope').textContent = scopeText;

        const minimum = Number(minimumPurchase.value || 0);
        document.getElementById('previewMinimum').textContent = minimum > 0 ? 'At least ' + money(minimum) + ' of matching products' : 'No minimum purchase';

        let dateText = 'No date limit';
        if (startDate.value && endDate.value) dateText = startDate.value + ' to ' + endDate.value;
        else if (startDate.value) dateText = 'Starts ' + startDate.value + ', no end date';
        else if (endDate.value) dateText = 'Available until ' + endDate.value;
        document.getElementById('previewDates').textContent = dateText;
        document.getElementById('previewStatus').textContent = discountStatus.value === 'active' ? 'Available when dates allow' : 'Not available to cashiers';
    }

    [appliesTo, categorySelect, productSelect, discountType, discountValue, maxDiscount, minimumPurchase, startDate, endDate, discountName, discountStatus].forEach(function(element){
        if (!element) return;
        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    });
    appliesTo.addEventListener('change', updateScope);
    discountType.addEventListener('change', updateType);
    startDate.addEventListener('change', updateDateLimit);

    updateScope();
    updateType();
    updateDateLimit();
    updatePreview();
})();
</script>
