<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Transfer #
                <?= esc($transfer['id']) ?>
            </h2>
            <p>
                <?= esc($transfer['from_branch_name']) ?> →
                <?= esc($transfer['to_branch_name']) ?>
            </p>
        </div>

        <a href="<?= site_url('admin/stock-transfer') ?>" class="btn secondary">Back</a>
    </div>

    <div class="row" style="gap:16px;">
        <div class="card" style="flex:1; min-width:220px;">
            <h3>
                <?= esc($transfer['from_branch_name']) ?>
            </h3>
            <p class="muted">From Branch</p>
        </div>

        <div class="card" style="flex:1; min-width:220px;">
            <h3>
                <?= esc($transfer['to_branch_name']) ?>
            </h3>
            <p class="muted">To Branch</p>
        </div>

        <div class="card" style="flex:1; min-width:220px;">
            <h3>
                <?= esc($transfer['status']) ?>
            </h3>
            <p class="muted">Status</p>
        </div>
    </div>

    <div class="sp"></div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= esc($item['product_name']) ?>
                            </td>
                            <td>
                                <?= esc($item['sku'] ?: '-') ?>
                            </td>
                            <td>
                                <?= esc($item['unit'] ?: '-') ?>
                            </td>
                            <td>
                                <?= esc($item['quantity']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="muted" style="text-align:center;">No transfer items found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="sp"></div>

    <div class="field">
        <label class="label">Notes</label>
        <textarea class="input" rows="3" readonly><?= esc($transfer['notes'] ?? '') ?></textarea>
    </div>
</div>

<?= $this->endSection() ?>