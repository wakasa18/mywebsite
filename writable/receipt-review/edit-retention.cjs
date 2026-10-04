const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const changed = new Set();
const read = p => fs.readFileSync(path.join(root, p), 'utf8').replace(/\r\n/g, '\n');
const save = (p, s) => { fs.writeFileSync(path.join(root, p), s); changed.add(p); };
function files(dir) { return fs.readdirSync(path.join(root, dir), {withFileTypes:true}).flatMap(e => e.isDirectory() ? files(dir+'/'+e.name) : e.name.endsWith('.php') ? [dir+'/'+e.name] : []); }
for (const name of ['ProductModel','BranchProductModel','CategoryModel','SupplierModel','DiscountModel']) {
    const p = 'app/Models/'+name+'.php';
    save(p, read(p).replace('use CodeIgniter\\Model;\n', '').replace('extends Model', 'extends RetainedRecordModel'));
}
for (const p of [...files('app/Controllers'), ...files('app/Libraries')]) {
    const s = read(p);
    const updated = s.replace(/->where\('((?:\w+\.)?)deleted_at', null\)/g, (m,a) => m+"->where('"+a+"is_deleted', 0)")
        .replace(/->where\('((?:\w+\.)?)deleted_at IS NULL', null, false\)/g, (m,a) => m+"->where('"+a+"is_deleted', 0)")
        .replace(/!empty\((\$\w+)\['deleted_at'\]\)/g, (m,v) => "(!empty("+v+"['deleted_at']) || !empty("+v+"['is_deleted']))");
    if (s !== updated) save(p, updated);
}
for (const [p,destination,model,table] of [
    ['app/Controllers/Products.php','products/trash','productModel','products'],
    ['app/Controllers/Categories.php','categories/trash','categoryModel','categories'],
    ['app/Controllers/Admin/Suppliers.php','admin/suppliers/trash','supplierModel','suppliers'],
    ['app/Controllers/Admin/Discounts.php','admin/discounts/trash','discountModel','discounts']
]) {
    let s = read(p);
    const start = s.indexOf('    public function forceDelete(');
    const end = start + 1 + s.slice(start+1).search(/\n    (?:private|public|protected) function /);
    assert(start > 0 && end > start);
    const stub = `    /** Retained for old links; permanent deletion is never performed. */
    public function forceDelete(int $id)
    {
        if (session('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Administrator access required.');
        }
        return redirect()->to(site_url('${destination}'))->with('error', 'Permanent deletion is disabled. Keep this record in Trash or restore it.');
    }
`;
    s = s.slice(0,start) + stub + s.slice(end);
    if (table !== 'products') {
        const old = `$this->db->table('${table}')->where('id', $id)->update(['deleted_at' => null])`;
        assert(s.includes(old));
        s = s.replace(old, `$this->${model}->restoreRecord($id)`);
    } else {
        s = s.replace("'deleted_at' => null,\n            'status' => 'active',", "'deleted_at' => null,\n            'is_deleted' => 0,\n            'status' => 'active',");
    }
    save(p,s);
}
for (const p of ['app/Views/categories/trash.php','app/Views/admin/suppliers/trash.php','app/Views/admin/discounts/trash.php']) {
    let removed=0;
    let s=read(p).replace(/\s*<form\b[^\n]*force-delete\/[^\n]*>[\s\S]*?<\/form>/g, () => {removed++;return '';});
    assert.equal(removed,1,p);
    s=s.replace('Soft-deleted categories — restore or permanently remove them.', 'Categories in Trash are kept for history. Restore them whenever needed.')
        .replace('Soft-deleted suppliers — restore or permanently remove them.', 'Suppliers in Trash are kept for history. Restore them whenever needed.')
        .replace('Restore discounts or permanently remove records that were never used in a sale.', 'Discounts in Trash are kept for history. Restore them whenever needed.')
        .replace('Discounts already used in completed sales cannot be permanently deleted because the system must keep the sales history accurate. They can safely remain in trash.', 'Moving a discount to Trash hides it from new sales and keeps its past usage intact. Restoring it also keeps its original dates and status.')
        .replace('Deleted At','Moved to Trash');
    save(p,s);
}
const test='tests/unit/BusinessLogicTest.php';
let s=read(test).replaceAll('deleted_at TEXT','deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0')
    .replace("end_date TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0'", "end_date TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT'")
    .replaceAll('CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT)', 'CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)')
    .replaceAll('CREATE TABLE suppliers (id INTEGER PRIMARY KEY, supplier_name TEXT)', 'CREATE TABLE suppliers (id INTEGER PRIMARY KEY, supplier_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
save(test,s);
console.log([...changed].sort().join('\n'));
