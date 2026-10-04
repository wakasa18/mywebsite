<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Sales History</h1>
    <p class="muted" style="margin:6px 0 0;">View sales, reprint receipts, and process refunds or product exchanges.
    </p>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
    <div class="sp"></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger">
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
    <div class="sp"></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Transaction Records</h2>
            <p class="muted" style="margin:6px 0 0;">Search, filter, view, reprint, and refund completed sales.</p>
        </div>

        <div class="row" style="gap:10px;">
            <a class="btn secondary" href="<?= site_url('cashier/sales') ?>">← Back to POS</a>
            <?php if (session('role') === 'admin'): ?>
                <a class="btn secondary" href="<?= site_url('admin/reports') ?>">Reports</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <form method="get" action="<?= site_url('cashier/sales/history') ?>" class="row history-filters"
        style="gap:10px; align-items:end; flex-wrap:wrap;">

        <div class="field" style="flex:1; min-width:220px; margin-bottom:0;">
            <label class="label">Search</label>
            <input type="text" name="q" class="input" value="<?= esc($keyword ?? '') ?>"
                placeholder="Invoice no, cashier, or payment method">
        </div>

        <?php if (session('role') === 'admin'): ?>
            <div class="field" style="min-width:220px; margin-bottom:0;">
                <label class="label">Branch</label>
                <select name="branch_id" class="input">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= esc($b['id']) ?>" <?= ($filterBranch == $b['id']) ? 'selected' : '' ?>>
                            <?= esc($b['branch_name']) ?><?= ($b['status'] ?? 'active') === 'inactive' ? ' (Inactive)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="field" style="min-width:160px; margin-bottom:0;">
            <label class="label">Payment</label>
            <select name="payment_method" class="input">
                <option value="">All Methods</option>
                <?php $paymentLabels = ['cash' => 'Cash', 'gcash' => 'GCash', 'card' => 'Card']; ?>
                <?php foreach ($paymentLabels as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= (($filterPaymentMethod ?? '') === $value) ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="min-width:170px; margin-bottom:0;">
            <label class="label">Status</label>
            <select name="status" class="input">
                <option value="">All Statuses</option>
                <?php $statusLabels = ['completed' => 'Completed', 'void' => 'Void', 'refunded' => 'Refunded', 'partially_refunded' => 'Partially Refunded']; ?>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= (($filterStatus ?? '') === $value) ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="min-width:180px; margin-bottom:0;">
            <label class="label">Date From</label>
            <input type="date" name="date_from" class="input" value="<?= esc($dateFrom ?? '') ?>">
        </div>

        <div class="field" style="min-width:180px; margin-bottom:0;">
            <label class="label">Date To</label>
            <input type="date" name="date_to" class="input" value="<?= esc($dateTo ?? '') ?>">
        </div>

        <div class="row" style="gap:10px;">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= site_url('cashier/sales/history') ?>" class="btn secondary">Reset</a>
        </div>
    </form>
    </div>

    <div class="table-wrap">
        <table class="mobile-record-table" data-record-title="0" aria-label="Sales history">
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>Branch</th>
                    <th>Cashier</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Sale Date</th>
                    <th style="min-width:220px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sales)): ?>
                    <?php foreach ($sales as $s): ?>
                        <tr>
                            <td><strong><?= esc($s['invoice_no']) ?></strong></td>
                            <td><?= esc($s['branch_name'] ?? '-') ?></td>
                            <td><?= esc($s['full_name'] ?? '-') ?></td>
                            <td>₱<?= number_format((float) $s['final_total'], 2) ?></td>
                            <td><?= esc(ucfirst($s['payment_method'])) ?></td>
                            <td>
                                <?php if (($s['status'] ?? '') === 'completed'): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php elseif (($s['status'] ?? '') === 'refunded'): ?>
                                    <span class="badge badge-danger">Refunded</span>
                                <?php elseif (($s['status'] ?? '') === 'partially_refunded'): ?>
                                    <span class="badge badge-warning">Partial Refund</span>
                                <?php elseif (($s['status'] ?? '') === 'void'): ?>
                                    <span class="badge badge-danger">Void</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= esc(ucfirst($s['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc(\App\Libraries\DisplayDate::dateTime($s['sale_date'])) ?></td>
                            <td>
                                <div class="row" style="gap:8px; flex-wrap:wrap;">
                                    <a href="<?= site_url('cashier/sales/view/' . $s['id']) ?>" class="btn btn-secondary">View Details</a>
                                    <?php if (session('role') === 'admin' && ($s['status'] ?? '') === 'completed' && !\App\Libraries\ExchangeRecord::identity($s)): ?>
                                    <a href="<?= site_url('admin/sale-correction/' . $s['id']) ?>"
                                        class="btn btn-secondary btn-sm"
                                        style="color:var(--primary);border-color:var(--primary);"
                                        title="Correct this sale record">Correct</a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('cashier/sales/reprint/' . $s['id']) ?>"
                                        class="btn secondary">Reprint</a>

                                    <?php if (in_array(($s['status'] ?? ''), ['completed', 'partially_refunded'])): ?>
                                        <a href="<?= site_url('cashier/sales/exchange/' . (int) $s['id']) ?>" class="btn btn-secondary">Exchange Product</a>
                                        <a href="<?= site_url('cashier/sales/refund-form/' . $s['id']) ?>"
                                           class="btn danger btn-sm">
                                            Refund
                                        </a>
                                    <?php else: ?>
                                        <span class="muted">No refund</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center; padding:20px;">
                            No sales records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pager)): ?>
        <div class="paginationWrap">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
