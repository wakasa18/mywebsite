<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    .import-page { display:grid; gap:18px; }
    .import-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:start; gap:20px; }
    .import-hero h1 { margin:0; }
    .import-actions { display:grid; grid-template-columns:auto minmax(220px,1fr) minmax(220px,1fr); gap:10px; align-items:stretch; }
    .import-action-btn {
        display:flex;
        align-items:center;
        gap:10px;
        min-height:50px;
        padding:9px 13px;
        border:1px solid var(--line);
        border-radius:12px;
        background:var(--card);
        color:var(--text);
        text-decoration:none;
        font-weight:750;
        line-height:1.15;
        box-shadow:0 1px 2px rgba(15,23,42,.04);
        transition:border-color .18s ease, background .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .import-action-btn:hover {
        border-color:color-mix(in srgb, var(--primary) 55%, var(--line));
        background:color-mix(in srgb, var(--primary) 6%, var(--card));
        box-shadow:0 5px 14px rgba(15,23,42,.08);
        transform:translateY(-1px);
    }
    .import-action-btn:focus-visible { outline:3px solid color-mix(in srgb, var(--primary) 28%, transparent); outline-offset:2px; }
    .import-action-btn--back { justify-content:center; min-width:136px; }
    .import-action-btn--primary { border-color:color-mix(in srgb, var(--primary) 42%, var(--line)); background:color-mix(in srgb, var(--primary) 9%, var(--card)); }
    .import-action-icon {
        width:30px;
        height:30px;
        border-radius:9px;
        display:grid;
        place-items:center;
        flex:0 0 auto;
        background:var(--surface-soft, rgba(148,163,184,.12));
        color:var(--primary);
        font-size:.9rem;
        font-weight:850;
    }
    .import-action-btn--primary .import-action-icon { background:var(--primary); color:#fff; }
    .import-action-copy { display:grid; gap:3px; min-width:0; }
    .import-action-copy strong { font-size:.86rem; }
    .import-action-copy small { color:var(--muted); font-size:.72rem; font-weight:600; white-space:normal; }
    .import-grid { display:grid; grid-template-columns:minmax(0, 1.35fr) minmax(280px, .65fr); gap:18px; align-items:start; }
    .import-step { display:flex; gap:12px; align-items:flex-start; }
    .step-number { width:30px; height:30px; border-radius:50%; display:grid; place-items:center; flex:0 0 auto; background:var(--primary); color:#fff; font-weight:800; }
    .upload-drop { border:2px dashed var(--line); border-radius:16px; padding:24px; background:var(--surface-soft, rgba(148,163,184,.08)); text-align:center; transition:.2s ease; }
    .upload-drop:focus-within, .upload-drop:hover { border-color:var(--primary); background:color-mix(in srgb, var(--primary) 7%, transparent); }
    .upload-drop input[type=file] { width:100%; max-width:520px; margin:14px auto 0; }
    .format-list { margin:12px 0 0; padding-left:18px; }
    .format-list li { margin:7px 0; }
    .code-chip-wrap { display:flex; gap:7px; flex-wrap:wrap; margin-top:9px; }
    .code-chip { display:inline-flex; align-items:center; min-height:30px; padding:4px 9px; border:1px solid var(--line); border-radius:999px; font-size:.82rem; background:var(--card); }
    .summary-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; }
    .summary-box { border:1px solid var(--line); border-radius:14px; padding:14px; background:var(--card); }
    .summary-box .number { display:block; font-size:1.55rem; font-weight:850; line-height:1.05; margin-bottom:5px; }
    .summary-box .label { color:var(--muted); font-size:.82rem; }
    .preview-note { display:flex; gap:10px; align-items:flex-start; padding:13px 14px; border-radius:12px; background:rgba(59,130,246,.09); border:1px solid rgba(59,130,246,.24); }
    .preview-table td, .preview-table th { vertical-align:top; }
    .error-list { max-height:360px; overflow:auto; border:1px solid var(--line); border-radius:12px; }
    .error-list table { margin:0; }
    .import-confirm { display:flex; align-items:flex-start; gap:10px; padding:14px; border:1px solid var(--line); border-radius:12px; background:var(--card); }
    .import-confirm input { margin-top:4px; width:18px; height:18px; flex:0 0 auto; }
    .preview-actions { display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
    .mini-table-wrap { max-height:260px; overflow:auto; border:1px solid var(--line); border-radius:12px; }
    .mini-table-wrap table { margin:0; }
    .required-columns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 14px; margin-top:12px; }
    .required-columns code { font-size:.78rem; }
    @media (max-width:1100px) {
        .import-hero { grid-template-columns:1fr; }
        .import-actions { grid-template-columns:auto minmax(0,1fr) minmax(0,1fr); }
    }
    @media (max-width:900px) {
        .import-grid { grid-template-columns:1fr; }
        .summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:700px) {
        .import-actions { grid-template-columns:1fr 1fr; }
        .import-action-btn--back { grid-column:1 / -1; }
    }
    @media (max-width:600px) {
        .import-page { gap:14px; }
        .import-actions, .preview-actions, .preview-actions .btn { width:100%; }
        .import-actions { grid-template-columns:1fr; }
        .import-action-btn, .import-action-btn--back { width:100%; min-width:0; justify-content:flex-start; }
        .import-action-btn--back { grid-column:auto; }
        .summary-grid { grid-template-columns:1fr 1fr; gap:9px; }
        .summary-box { padding:12px; }
        .summary-box .number { font-size:1.3rem; }
        .required-columns { grid-template-columns:1fr; }
        .upload-drop { padding:18px 13px; }
        .card-head { align-items:flex-start; }
        .preview-table thead { display:none; }
        .preview-table, .preview-table tbody, .preview-table tr, .preview-table td { display:block; width:100%; }
        .preview-table tr { padding:13px; border-bottom:1px solid var(--line); }
        .preview-table td { border:0 !important; padding:4px 0 !important; }
        .preview-table td::before { content:attr(data-label); display:block; color:var(--muted); font-size:.72rem; font-weight:750; text-transform:uppercase; letter-spacing:.04em; margin-bottom:2px; }
    }
</style>

<div class="import-page">
    <div class="import-hero">
        <div>
            <h1>Import Past Sales</h1>
            <p class="muted" style="margin:7px 0 0;max-width:760px;">Add itemized business sales from a CSV file for reports and forecasting. Imported historical sales do not reduce the current stock.</p>
        </div>
        <nav class="import-actions" aria-label="Historical sales import actions">
            <a class="import-action-btn import-action-btn--back" href="<?= site_url('cashier/sales/history') ?>">
                <span class="import-action-icon" aria-hidden="true">←</span>
                <span class="import-action-copy"><strong>Sales History</strong><small>Return to transactions</small></span>
            </a>
            <a class="import-action-btn import-action-btn--primary" href="<?= site_url('admin/sales-import/template') ?>">
                <span class="import-action-icon" aria-hidden="true">1</span>
                <span class="import-action-copy"><strong>Download Sales CSV Template</strong><small>Fill in and upload this file</small></span>
            </a>
            <a class="import-action-btn" href="<?= site_url('admin/sales-import/reference') ?>" title="Lookup file only — do not upload this file">
                <span class="import-action-icon" aria-hidden="true">2</span>
                <span class="import-action-copy"><strong>Download Product/SKU Lookup</strong><small>Reference only — do not upload</small></span>
            </a>
        </nav>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (!empty($pageError)): ?>
        <div class="alert danger"><?= esc($pageError) ?></div>
    <?php endif; ?>

    <?php if (empty($preview)): ?>
        <div class="import-grid">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h2 style="margin:0;">Upload historical sales</h2>
                        <p class="muted" style="margin:6px 0 0;">Use one row for every product sold. Repeat the invoice information when one sale contains several products.</p>
                    </div>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('admin/sales-import/preview') ?>" method="post" enctype="multipart/form-data" id="historicalImportUploadForm">
                        <?= csrf_field() ?>
                        <div class="upload-drop">
                            <div style="font-size:2rem;line-height:1;">📄</div>
                            <strong style="display:block;margin-top:10px;">Choose a CSV file</strong>
                            <span class="muted" style="display:block;margin-top:5px;">Maximum size: 5 MB or 20,000 item rows</span>
                            <input class="input" type="file" name="sales_file" id="salesFile" accept=".csv,text/csv" required>
                        </div>
                        <div class="preview-note" style="margin-top:16px;">
                            <span aria-hidden="true">ℹ️</span>
                            <div>
                                <strong>Upload the completed Sales CSV Template—not the Product/SKU Lookup file.</strong>
                                <div class="muted" style="margin-top:3px;">Use the lookup file only to copy valid branch codes, product SKUs, names, and prices into the sales template. Excel files must be saved as CSV UTF-8 before upload.</div>
                            </div>
                        </div>
                        <button class="btn" type="submit" data-busy-label="Checking CSV…" style="margin-top:16px;min-height:44px;">Preview and Validate</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2 style="margin:0;">Before importing</h2></div>
                <div class="card-body">
                    <div class="import-step"><span class="step-number">1</span><div><strong>Download the Sales CSV Template</strong><div class="muted">This is the only file format that should be completed and uploaded.</div></div></div>
                    <div class="import-step" style="margin-top:15px;"><span class="step-number">2</span><div><strong>Use the Product/SKU Lookup as a guide</strong><div class="muted">Copy valid branch codes and product SKUs from it. Do not upload the lookup file itself.</div></div></div>
                    <div class="import-step" style="margin-top:15px;"><span class="step-number">3</span><div><strong>Complete and check the template</strong><div class="muted">Enter invoice, date, cashier, quantity, price, payment, and compare totals with the original record.</div></div></div>
                    <div class="import-step" style="margin-top:15px;"><span class="step-number">4</span><div><strong>Back up, preview, then import</strong><div class="muted">Current stock stays unchanged; past sales are added only to Sales History, reports, and forecasting.</div></div></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 style="margin:0;">CSV columns</h2>
                    <p class="muted" style="margin:6px 0 0;">Keep the template header row. Optional fields may be left blank.</p>
                </div>
            </div>
            <div class="card-body">
                <strong>Required</strong>
                <div class="required-columns">
                    <div><code>invoice_no</code> — unique sale number</div>
                    <div><code>sale_date</code> — date and time</div>
                    <div><code>branch_code</code> — existing branch code</div>
                    <div><code>cashier_username</code> — existing user</div>
                    <div><code>product_sku</code> — existing product SKU</div>
                    <div><code>quantity</code> — whole number</div>
                    <div><code>unit_price</code> — price during the sale</div>
                    <div><code>payment_method</code> — cash, gcash, or card</div>
                </div>
                <hr style="border:0;border-top:1px solid var(--line);margin:18px 0;">
                <strong>Optional</strong>
                <div class="required-columns">
                    <div><code>product_name</code> — used only for checking</div>
                    <div><code>cost_price</code> — otherwise current branch cost is used</div>
                    <div><code>line_discount</code> — discount for that item row</div>
                    <div><code>reference_no</code> — optional GCash/card reference</div>
                    <div><code>amount_paid</code> — defaults to final total</div>
                    <div><code>notes</code> — sale remarks</div>
                </div>
            </div>
        </div>

        <div class="import-grid">
            <div class="card">
                <div class="card-head"><h2 style="margin:0;">Available branch codes</h2></div>
                <div class="card-body">
                    <div class="code-chip-wrap">
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <span class="code-chip"><strong><?= esc($branch['branch_code']) ?></strong>&nbsp;—&nbsp;<?= esc($branch['branch_name']) ?><?= ($branch['status'] ?? '') !== 'active' ? ' (Inactive)' : '' ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h2 style="margin:0;">Accepted usernames</h2></div>
                <div class="card-body">
                    <div class="code-chip-wrap">
                        <?php foreach (($importUsers ?? []) as $user): ?>
                            <span class="code-chip"><strong><?= esc($user['username']) ?></strong>&nbsp;—&nbsp;<?= esc($user['full_name']) ?><?= ($user['status'] ?? '') !== 'active' ? ' (Inactive)' : '' ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 style="margin:0;">Import preview</h2>
                    <p class="muted" style="margin:6px 0 0;">File: <strong><?= esc($uploadedFileName ?? 'uploaded.csv') ?></strong></p>
                </div>
                <a class="btn secondary" href="<?= site_url('admin/sales-import') ?>">Upload Another File</a>
            </div>
            <div class="card-body">
                <div class="summary-grid">
                    <div class="summary-box"><span class="number"><?= number_format((int) $preview['total_rows']) ?></span><span class="label">CSV item rows</span></div>
                    <div class="summary-box"><span class="number"><?= number_format((int) $preview['valid_invoice_count']) ?></span><span class="label">Valid invoices</span></div>
                    <div class="summary-box"><span class="number"><?= number_format((int) $preview['valid_item_count']) ?></span><span class="label">Valid sale items</span></div>
                    <div class="summary-box"><span class="number"><?= number_format((int) $preview['invalid_invoice_count']) ?></span><span class="label">Invalid invoices/rows</span></div>
                </div>
                <div class="preview-note" style="margin-top:15px;">
                    <span aria-hidden="true">🛡️</span>
                    <div><strong>Current inventory will not be changed.</strong><div class="muted" style="margin-top:3px;">The import creates only Sales and Sale Item records. It does not deduct branch stock or add stock logs.</div></div>
                </div>
            </div>
        </div>

        <?php if (!empty($preview['valid_invoices'])): ?>
            <div class="card">
                <div class="card-head">
                    <div><h2 style="margin:0;">Valid sales ready to import</h2><p class="muted" style="margin:6px 0 0;">Showing up to the first 50 valid invoices.</p></div>
                </div>
                <div class="table-wrap">
                    <table class="preview-table">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Branch</th><th>Cashier</th><th>Items</th><th>Payment</th><th>Gross</th><th>Discount</th><th>Final Total</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($preview['valid_invoices'], 0, 50) as $invoice): ?>
                            <tr>
                                <td data-label="Invoice"><strong><?= esc($invoice['invoice_no']) ?></strong></td>
                                <td data-label="Date"><?= esc($invoice['sale_date']) ?></td>
                                <td data-label="Branch"><?= esc($invoice['branch_name']) ?> <span class="muted">(<?= esc($invoice['branch_code']) ?>)</span></td>
                                <td data-label="Cashier"><?= esc($invoice['cashier_name']) ?> <span class="muted">@<?= esc($invoice['cashier_username']) ?></span></td>
                                <td data-label="Items"><?= number_format(count($invoice['items'])) ?></td>
                                <td data-label="Payment"><?= esc(ucfirst($invoice['payment_method'])) ?></td>
                                <td data-label="Gross">₱<?= number_format((float) $invoice['gross_total'], 2) ?></td>
                                <td data-label="Discount">₱<?= number_format((float) $invoice['discount_total'], 2) ?></td>
                                <td data-label="Final Total"><strong>₱<?= number_format((float) $invoice['final_total'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($preview['errors'])): ?>
            <div class="card">
                <div class="card-head"><div><h2 style="margin:0;">Rows that will not be imported</h2><p class="muted" style="margin:6px 0 0;">Correct these entries in the CSV and upload it again when needed.</p></div><span class="badge badge-danger"><?= number_format(count($preview['errors'])) ?> error<?= count($preview['errors']) === 1 ? '' : 's' ?></span></div>
                <div class="card-body">
                    <div class="error-list">
                        <table><thead><tr><th>CSV Row</th><th>Invoice</th><th>Problem</th></tr></thead><tbody>
                            <?php foreach (array_slice($preview['errors'], 0, 200) as $error): ?>
                                <tr><td><?= esc((string) $error['row']) ?></td><td><?= esc($error['invoice']) ?></td><td><?= esc($error['message']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table>
                    </div>
                    <?php if (count($preview['errors']) > 200): ?><p class="muted" style="margin-bottom:0;">Only the first 200 errors are shown.</p><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($preview['warnings'])): ?>
            <div class="card">
                <div class="card-head"><div><h2 style="margin:0;">Warnings</h2><p class="muted" style="margin:6px 0 0;">These items can still be imported, but review them first.</p></div><span class="badge badge-warning"><?= number_format(count($preview['warnings'])) ?></span></div>
                <div class="card-body">
                    <div class="error-list">
                        <table><thead><tr><th>CSV Row</th><th>Invoice</th><th>Warning</th></tr></thead><tbody>
                            <?php foreach (array_slice($preview['warnings'], 0, 100) as $warning): ?>
                                <tr><td><?= esc((string) $warning['row']) ?></td><td><?= esc($warning['invoice']) ?></td><td><?= esc($warning['message']) ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-head"><h2 style="margin:0;">Confirm import</h2></div>
            <div class="card-body">
                <?php if (!empty($preview['valid_invoices'])): ?>
                    <form action="<?= site_url('admin/sales-import/confirm') ?>" method="post" id="historicalImportConfirmForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="import_token" value="<?= esc($importToken) ?>">
                        <label class="import-confirm">
                            <input type="checkbox" name="confirm_import" value="1" required>
                            <span><strong>I checked the preview and understand that current stock will not change.</strong><span class="muted" style="display:block;margin-top:4px;">Valid invoices are imported in one database transaction. If saving fails, none of them are kept.</span></span>
                        </label>
                        <div class="preview-actions" style="margin-top:15px;">
                            <button class="btn" type="submit" data-busy-label="Importing sales…">Import <?= number_format((int) $preview['valid_invoice_count']) ?> Valid Sale<?= (int) $preview['valid_invoice_count'] === 1 ? '' : 's' ?></button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert danger">No valid invoices are available. Correct the CSV file and upload it again.</div>
                <?php endif; ?>
                <form action="<?= site_url('admin/sales-import/cancel') ?>" method="post" style="margin-top:10px;text-align:right;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="import_token" value="<?= esc($importToken) ?>">
                    <button class="btn secondary" type="submit" data-busy-label="Cancelling…">Cancel and Delete Uploaded File</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>


<?= $this->endSection() ?>
