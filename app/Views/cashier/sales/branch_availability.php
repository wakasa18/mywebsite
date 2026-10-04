<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<style>
.branch-lookup { max-width:1100px; margin:0 auto; }
.branch-lookup-head { display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
.branch-lookup h1 { margin:0 0 8px; font-size:24px; }
.branch-lookup p { color:var(--muted); line-height:1.6; }
.branch-lookup-form { padding:20px; margin-bottom:20px; }
.branch-lookup-fields { display:flex; align-items:end; gap:12px; flex-wrap:wrap; }
.branch-lookup-field { flex:1 1 240px; min-width:0; }
.branch-lookup-field label { display:block; margin-bottom:8px; font-weight:600; }
.branch-lookup-field input { width:100%; box-sizing:border-box; }
.branch-lookup-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:16px; }
.branch-lookup-result { padding:20px; min-width:0; overflow-wrap:anywhere; }
.branch-lookup-result h2 { font-size:17px; margin:0 0 6px; }
.branch-lookup-result h3 { font-size:15px; margin:16px 0 6px; }
.branch-lookup-result p { margin:6px 0; }
.branch-lookup-stock { display:inline-block; padding:7px 10px; background:var(--success-bg); color:var(--success-text); border-radius:8px; font-weight:700; margin-top:10px; }
.branch-lookup-empty { padding:28px; }
@media(max-width:480px) { .branch-lookup-fields .btn { width:100%; min-height:44px; } }
</style>
<div class="branch-lookup">
    <div class="branch-lookup-head">
        <div><h1>Find stock at another branch</h1><p>Help your customer find an available product. Your cart and selling branch stay the same.</p></div>
        <a class="btn btn-secondary" href="<?= site_url('cashier/sales') ?>">Back to POS</a>
    </div>
    <form class="card branch-lookup-form" method="get" data-auto-filter="off" action="<?= site_url('cashier/sales/branch-availability') ?>">
        <div class="branch-lookup-fields">
            <div class="branch-lookup-field"><label for="branchProductSearch">Product name or SKU</label>
                <input class="input" id="branchProductSearch" name="q" type="search" value="<?= esc($keyword) ?>" maxlength="80" placeholder="For example, Paracetamol" required>
            </div>
            <button class="btn btn-primary" type="submit">Find availability</button>
        </div>
        <p>Shows active branches with stock available to sell, excluding your current branch. Confirm availability with the branch before referring a customer. Results are alphabetical, not ordered by distance.</p>
    </form>
    <?php if ($keyword === ''): ?>
        <div class="card branch-lookup-empty">Search for a product to see other branches and their contact details.</div>
    <?php elseif (!$availability): ?>
        <div class="card branch-lookup-empty" role="status">No available stock at other branches matches “<?= esc($keyword) ?>”. Try the SKU or a shorter product name.</div>
    <?php else: ?>
        <p role="status"><?= number_format($total) ?> matching stock listing<?= $total === 1 ? '' : 's' ?></p>
        <div class="branch-lookup-grid">
            <?php foreach ($availability as $row): ?>
                <article class="card branch-lookup-result">
                    <h2><?= esc($row['product_name']) ?></h2><p>SKU: <?= esc($row['sku'] ?: 'Not recorded') ?></p>
                    <span class="branch-lookup-stock"><?= number_format((int) $row['stock']) ?> <?= esc($row['unit'] ?: 'units') ?> available</span>
                    <h3><?= esc($row['branch_name']) ?></h3>
                    <p><?= esc($row['address'] ?: 'Address not recorded') ?></p>
                    <p>Contact: <?= esc($row['contact_number'] ?: 'Not recorded') ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <?= service('pager')->makeLinks($page, 20, $total) ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
