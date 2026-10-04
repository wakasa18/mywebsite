const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../..');
const files=dir=>fs.readdirSync(path.join(root,dir),{withFileTypes:true}).flatMap(e=>e.isDirectory()?files(dir+'/'+e.name):e.name.endsWith('.php')?[dir+'/'+e.name]:[]);
const changed=[];
for(const p of [...files('app/Controllers'),...files('app/Libraries'),...files('app/Models'), 'app/Views/admin/reports/expiry_action.php']) {
    const s=fs.readFileSync(path.join(root,p),'utf8').replace(/\r\n/g,'\n');
    const updated=s.replace(/->where\('((?:\w+\.)?)permanently_deleted_at', null\)/g,(m,a)=>"->where('"+a+"is_permanently_deleted', 0)")
        .replace(/\. '\.permanently_deleted_at', null\)/g,". '.is_permanently_deleted', 0)")
        .replace(/(empty\(\$\w+\[')permanently_deleted_at('\]\))/g,"$1is_permanently_deleted$2")
        .replace(/(empty\(\$\w+\[')branch_permanently_deleted_at('\]\))/g,"$1branch_is_permanently_deleted$2")
        .replaceAll('AND bp.permanently_deleted_at IS NULL','AND bp.is_permanently_deleted = 0')
        .replaceAll('AND p.permanently_deleted_at IS NULL','AND p.is_permanently_deleted = 0');
    if(s!==updated) {fs.writeFileSync(path.join(root,p),updated);changed.push(p);}
}
const p='tests/unit/BusinessLogicTest.php';
let s=fs.readFileSync(path.join(root,p),'utf8').replace(/\r\n/g,'\n');
s=s.replaceAll('permanently_deleted_at TEXT','permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0')
    .replaceAll("['permanently_deleted_at','updated_at']","['permanently_deleted_at','is_permanently_deleted','updated_at']")
    .replace("['is_deleted'=>1,'deleted_at'=>'2026-09-01','permanently_deleted_at'=>'2026-10-03']", "['is_deleted'=>1,'is_permanently_deleted'=>1,'deleted_at'=>'2026-09-01','permanently_deleted_at'=>'2026-10-03']");
fs.writeFileSync(path.join(root,p),s);
console.log(changed.join('\n'));
