const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../..');
const files=dir=>fs.readdirSync(path.join(root,dir),{withFileTypes:true}).flatMap(e=>e.isDirectory()?files(dir+'/'+e.name):e.name.endsWith('.php')?[dir+'/'+e.name]:[]);
for(const p of [...files('app/Controllers'),...files('app/Libraries'),'app/Views/admin/reports/expiry_action.php']) {
    const s=fs.readFileSync(path.join(root,p),'utf8');
    const updated=s.replace(/(empty\(\$\w+\[')permanently_deleted_at('\]\))/g,'$1is_permanently_deleted$2')
        .replace(/(empty\(\$\w+\[')branch_permanently_deleted_at('\]\))/g,'$1branch_is_permanently_deleted$2');
    if(s!==updated)fs.writeFileSync(path.join(root,p),updated);
}
