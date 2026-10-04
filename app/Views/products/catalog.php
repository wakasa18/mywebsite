<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>
<style>
.catalog-page{max-width:1180px;margin:auto;min-width:0}
.catalog-page-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}
.catalog-page-header>div{min-width:0}.catalog-page-header>.btn{flex-shrink:0}
.catalog-page .btn{min-height:44px;line-height:1.35}
.catalog-page :is(a,button):focus-visible{outline:3px solid var(--primary);outline-offset:3px}
.catalog-page .catalog-toolbar{margin:0;padding:20px;border-bottom:1px solid var(--line);background:var(--surface-2)}
.catalog-toolbar .label{margin-bottom:9px}
.catalog-search-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:stretch}
.catalog-search-row:has(.catalog-reset){grid-template-columns:minmax(0,1fr) auto auto}
.catalog-page .catalog-search-row>.input,.catalog-page .catalog-search-row>.btn{min-width:0;min-height:48px;margin:0}
.catalog-search-row>.input{background:var(--surface)}
.catalog-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;padding:20px}
.catalog-product{border:1px solid var(--border);border-radius:12px;padding:18px;display:flex;flex-direction:column;gap:10px;min-width:0;overflow-wrap:anywhere}
.catalog-product h3{font-size:16px;margin:0}.catalog-product p{margin:0;color:var(--muted);font-size:13px}
.catalog-product .btn{margin-top:auto;align-self:start;min-height:44px}
.catalog-details{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:0}
.catalog-details dt{font-size:12px;color:var(--muted);margin-bottom:4px}.catalog-details dd{margin:0;overflow-wrap:anywhere}
.catalog-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.catalog-fields .field{min-width:0;margin:0}.catalog-fields .input{width:100%;min-width:0;min-height:44px}
.catalog-note{color:var(--muted);font-size:13px;margin:0 0 20px}
.catalog-empty{padding:32px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:12px}
.catalog-empty-icon{width:52px;height:52px;display:grid;place-items:center;border:1px solid var(--line);border-radius:16px;background:var(--surface-2);color:var(--primary)}
.catalog-empty-icon svg{width:26px;height:26px}
.catalog-empty h3{font-family:var(--heading-font);font-size:19px;line-height:1.4;margin:0}
.catalog-empty p{max-width:520px;margin:0;color:var(--muted);font-size:14px;line-height:1.65;overflow-wrap:anywhere}
.catalog-empty-actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:6px}
.catalog-footer{padding:18px 20px;border-top:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px}
.catalog-footer p{color:var(--muted);font-size:13px;line-height:1.6;margin:0}.catalog-footer .btn{flex-shrink:0}
@media(max-width:900px){.catalog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.catalog-grid,.catalog-fields,.catalog-details{grid-template-columns:1fr}.catalog-grid{padding:14px}.catalog-page .card-body,.catalog-page .catalog-toolbar{padding:16px}.catalog-page .btn{white-space:normal}.catalog-page-header{align-items:start;flex-direction:column;gap:14px}.catalog-search-row{grid-template-columns:minmax(0,1fr)}.catalog-search-row:has(.catalog-reset){grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-search-row>.input{grid-column:1/-1}.catalog-empty{padding:28px 16px}.catalog-empty h3{font-size:18px}.catalog-empty-actions{width:100%}.catalog-empty-actions>.btn{flex:1;min-width:0}.catalog-footer{flex-direction:column;align-items:stretch;padding:16px;gap:12px}}
</style>
<div class="catalog-page">
    <div class="page-header catalog-page-header">
        <div><h1>Add from Existing Products</h1><p>Reuse a catalog product in <?= esc($branch['branch_name']) ?>.</p></div>
        <a class="btn btn-secondary" href="<?= site_url('products') ?>">Back to Products</a>
    </div>
    <?= view('partials/form_messages') ?>
    <?php if ($selected): ?>
        <div class="card" style="margin-bottom:20px">
            <div class="card-head"><div><h2><?= esc($selected['product_name']) ?></h2><p>Shared product details are kept as they are.</p></div>
                <a class="btn btn-secondary" href="<?= site_url('products/catalog?' . http_build_query(['keyword' => $keyword])) ?>">Choose another</a>
            </div>
            <div class="card-body"><dl class="catalog-details">
                <?php foreach (['sku'=>'SKU','category_name'=>'Category','unit'=>'Unit','manufacturer'=>'Manufacturer','supplier_name'=>'Supplier'] as $key=>$label): ?>
                    <div><dt><?= $label ?></dt><dd><?= esc($selected[$key] ?: '—') ?></dd></div>
                <?php endforeach; ?>
                <div><dt>Adding to</dt><dd><?= esc($branch['branch_name']) ?></dd></div>
            </dl></div>
        </div>
        <form class="card" method="post" action="<?= site_url('products/add-existing') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $selected['id'] ?>">
            <div class="card-head"><div><h2>Set up your branch stock</h2><p>Enter the stock physically received at <?= esc($branch['branch_name']) ?>.</p></div></div>
            <div class="card-body">
                <p class="catalog-note">Prices and reorder level start with catalog defaults. Review them for your branch. Stock starts at zero; no inventory or expiry is copied from another branch.</p>
                <div class="catalog-fields">
                    <?php foreach (['stock'=>['Initial stock',0,'1','1000000'],'reorder_level'=>['Reorder level',$selected['reorder_level'] ?? 0,'1','1000000'],'cost_price'=>['Cost per unit (PHP)',$selected['cost_price'] ?? 0,'0.01','99999999.99'],'price'=>['Selling price per unit (PHP)',$selected['price'] ?? 0,'0.01','99999999.99']] as $key=>[$label,$default,$step,$max]): ?>
                    <div class="field">
                        <label class="label" for="catalog-<?= $key ?>"><?= $label ?> <span class="required-mark">*</span></label>
                        <input class="input" id="catalog-<?= $key ?>" name="<?= $key ?>" type="number" min="0" max="<?= $max ?>" step="<?= $step ?>" value="<?= old($key, (string) $default) ?>" required>
                        <?php if ($key === 'stock'): ?><span class="form-help">Quantity in <?= esc($selected['unit']) ?>. Enter 0 to set up the product before delivery.</span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="field"><label class="label" for="catalog-expiry">Expiration date</label>
                        <input class="input" id="catalog-expiry" name="expiration_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= old('expiration_date', '') ?>">
                        <span class="form-help">Use the expiry on your branch's stock. Leave blank if it does not apply.</span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px">
                    <button type="submit" class="btn btn-primary">Add to My Branch</button>
                    <a class="btn btn-secondary" href="<?= site_url('products/catalog') ?>">Cancel</a>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="card">
            <div class="card-head"><div><h2>Choose a product</h2><p>Active products that have not yet been assigned to your branch.</p></div></div>
            <form method="get" action="<?= site_url('products/catalog') ?>" class="catalog-toolbar">
                <label class="label" for="catalog-search">Search the shared catalog</label>
                <div class="catalog-search-row">
                    <input class="input" id="catalog-search" name="keyword" type="search" value="<?= esc($keyword) ?>" placeholder="Product name, SKU, or category">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <?php if ($keyword !== ''): ?><a class="btn btn-secondary catalog-reset" href="<?= site_url('products/catalog') ?>">Reset</a><?php endif; ?>
                </div>
            </form>
            <?php if ($products): ?>
                <div class="catalog-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="catalog-product">
                            <p><?= esc($product['category_name'] ?: 'Uncategorized') ?></p>
                            <h3><?= esc($product['product_name']) ?></h3>
                            <p>SKU: <?= esc($product['sku'] ?: 'Not assigned') ?> · <?= esc($product['unit']) ?></p>
                            <p><?= esc($product['manufacturer'] ?: 'Manufacturer not recorded') ?></p>
                            <a class="btn btn-secondary" aria-label="<?= esc('Select ' . $product['product_name'], 'attr') ?>" href="<?= site_url('products/catalog?' . http_build_query(['product_id'=>(int)$product['id'],'keyword'=>$keyword])) ?>">Select product</a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($pager): ?><div class="card-body"><?= $pager->only(['keyword'])->links() ?></div><?php endif; ?>
            <?php else: ?>
                <div class="catalog-empty">
                    <span class="catalog-empty-icon"><?= view('partials/icon', ['name'=>'box']) ?></span>
                    <h3><?= $keyword !== '' ? 'No matching products' : 'No products left to add' ?></h3>
                    <?php if ($keyword !== ''): ?>
                        <p>No available products match “<?= esc($keyword) ?>”. Try another name, SKU, or category, or clear your search.</p>
                    <?php else: ?>
                        <p>There are no active catalog products available to add to <?= esc($branch['branch_name']) ?>. Create a product if it is new, or ask an administrator to review inactive products.</p>
                    <?php endif; ?>
                    <div class="catalog-empty-actions">
                        <?php if ($keyword !== ''): ?><a class="btn btn-primary" href="<?= site_url('products/catalog') ?>">Clear Search</a><?php endif; ?>
                        <a class="btn <?= $keyword !== '' ? 'btn-secondary' : 'btn-primary' ?>" href="<?= site_url('products/create') ?>"><?= view('partials/icon', ['name'=>'plus']) ?> Create New Product</a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($products): ?>
                <div class="catalog-footer"><p>Product missing from the catalog?<br>Create a record for a new item.</p><a class="btn btn-secondary" href="<?= site_url('products/create') ?>"><?= view('partials/icon', ['name'=>'plus']) ?> Create New Product</a></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
