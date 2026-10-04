const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const root=path.resolve(__dirname,'../..');
const read=p=>fs.readFileSync(path.join(root,p),'utf8').replace(/\r\n/g,'\n');
const save=(p,s)=>fs.writeFileSync(path.join(root,p),s);
const files=dir=>fs.readdirSync(path.join(root,dir),{withFileTypes:true}).flatMap(e=>e.isDirectory()?files(dir+'/'+e.name):e.name.endsWith('.php')?[dir+'/'+e.name]:[]);
for(const p of [...files('app/Controllers'),...files('app/Libraries')]) {
    const s=read(p),updated=s.replace(/->where\('((?:\w+\.)?)is_deleted', 0\)/g,(m,a)=>m+"->where('"+a+"permanently_deleted_at', null)");
    if(s!==updated)save(p,updated);
}
for(const [p,table,label,destination] of [
    ['app/Controllers/Categories.php','categories','Category','categories/trash'],
    ['app/Controllers/Admin/Suppliers.php','suppliers','Supplier','admin/suppliers/trash'],
    ['app/Controllers/Admin/Discounts.php','discounts','Discount','admin/discounts/trash']
]) {
    let s=read(p);
    const start=s.indexOf('    /** Retained for old links;'),end=start+1+s.slice(start+1).search(/\n    private function /);
    assert(start>0&&end>start);
    const method=`    public function forceDelete(int $id)
    {
        if (session('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Administrator access required.');
        }
        try {
            $changed = (new \\App\\Libraries\\RetainedRecordDeletion($this->db))->hide('${table}', $id, (string)session('role'), (int)session('user_id'));
        } catch (\\DomainException|\\InvalidArgumentException $error) {
            return redirect()->to(site_url('${destination}'))->with('error', $error->getMessage());
        } catch (\\Throwable $error) {
            log_message('error', 'Permanent hiding failed: {message}', ['message'=>$error->getMessage()]);
            return redirect()->to(site_url('${destination}'))->with('error', 'The record could not be removed. No changes were saved.');
        }
        return redirect()->to(site_url('${destination}'))->with('success', $changed ? '${label} removed from the system and Trash. Its database record and history are retained.' : '${label} is already permanently hidden.');
    }
`;
    save(p,s.slice(0,start)+method+s.slice(end));
}
let p='tests/unit/BusinessLogicTest.php',s=read(p);
s=s.replaceAll('is_deleted INTEGER NOT NULL DEFAULT 0','is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT');
s=s.replace('CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT)', 'CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, updated_at TEXT)');
s=s.replace('testOldPermanentDeleteEndpointsCannotRemoveAnyRecord','testPermanentDeleteEndpointsRequireTrashAndAdministrator')
    .replace("$this->assertStringContainsString('disabled',session()->getFlashdata('error'));", "$this->assertNotEmpty(session()->getFlashdata('error'));");
// A separate test exercises successful branch hiding; this test continues to check Restore.
s=s.replace("        $this->controller(\\App\\Controllers\\Products::class,['branch_id'=>1])->forceDelete(1);\n        $this->assertNotNull($this->row('products'));", "        $this->controller(\\App\\Controllers\\Products::class,[])->forceDelete(1);\n        $this->assertNotNull($this->row('products'));");
save(p,s);
console.log('Updated active queries, retained deletion endpoints and in-memory schemas.');
