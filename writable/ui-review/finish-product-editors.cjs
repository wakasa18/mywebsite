const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
for (const operation of ['create', 'edit']) {
  const file = path.join(root, 'app/Views/products', operation + '.php');
  let source = fs.readFileSync(file, 'utf8');
  if (operation === 'edit') {
    source = source.replaceAll('<div style="display:grid;grid-template-columns:var(--grid-2col);gap:14px;">', '<div class="product-form-grid">');
    source = source.replace('<div style="display:grid;grid-template-columns:var(--grid-sidebar-340);gap:18px;align-items:start;">', '<div class="product-create-layout">');
    source = source.replace('<div style="position:sticky;top:80px;">', '<div class="product-create-side">');
  }
  const costLabel = source.indexOf('>Cost Price ');
  const gridStart = source.lastIndexOf('                    <div class="product-form-grid">', costLabel);
  if (costLabel < 0 || gridStart < 0) throw Error('Pricing boundary not found');
  source = source.slice(0, gridStart) + `                </div>
            </div>

            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><div><h2>Pricing and stock</h2><p>Set prices in pesos and manage stock for this branch.</p></div></div>
                <div class="card-body">
` + source.slice(gridStart);
  source = source.replace('<div class="card-head"><h2>Basic Information</h2></div>', '<div class="card-head"><div><h2>Product details</h2><p>Name, category, SKU, and selling unit.</p></div></div>');
  source = source.replaceAll('>Cost Price <', '>Cost price (₱) <').replaceAll('>Selling Price <', '>Selling price (₱) <');
  const actionPattern = operation === 'create'
    ? /            <div class="product-create-actions"[^>]*>[\s\S]*?<\/div>/
    : /            <div style="display:flex;flex-direction:column;gap:8px;">[\s\S]*?<\/div>/;
  if (!actionPattern.test(source)) throw Error('Missing product actions');
  source = source.replace(actionPattern, '');
  const submit = operation === 'create' ? "($isCashier ? 'Add to My Branch' : 'Save Product')" : "'Save Changes'";
  source = source.replace('</form>', "    <?= view('partials/editor_actions', ['cancelUrl' => site_url('products'), 'submitLabel' => " + submit + "]) ?>\n</form>");
  source = source.replace('<strong>Cashier branch protection:</strong>', '<strong>Your branch:</strong>');
  fs.writeFileSync(file, source);
  console.log('Grouped product ' + operation + ' fields and moved actions');
}
