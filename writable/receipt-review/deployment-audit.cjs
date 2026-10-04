const fs=require('node:fs'),path=require('node:path'),{spawnSync}=require('node:child_process');
const root=process.cwd(),php='C:/UniServerZ/core/php83/php.exe';
const walk=dir=>fs.readdirSync(dir,{withFileTypes:true}).flatMap(e=>e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)]);
const exact=relative=>{let dir=root;for(const segment of relative.replaceAll('\\','/').split('/')){if(!fs.existsSync(dir)||!fs.readdirSync(dir).includes(segment))return false;dir=path.join(dir,segment);}return true;};
const files=[...walk('app'),...walk('public')],issues=[],counts={php:0,js:0,staticViews:0,staticAssets:0,appClasses:0};
for(const file of files){
 const ext=path.extname(file);if(!['.php','.js','.css'].includes(ext))continue;
 const content=fs.readFileSync(file,'utf8');
 if(ext==='.php'||ext==='.js'){
  const result=spawnSync(ext==='.php'?php:process.execPath,ext==='.php'?['-l',file]:['--check',file],{encoding:'utf8',windowsHide:true});
  counts[ext==='.php'?'php':'js']++;
  if(result.status!==0)issues.push({file,kind:'syntax',message:result.stdout+result.stderr});
 }
 if(ext==='.php'){
  for(const match of content.matchAll(/(?:\bview\s*\(|->extend\s*\()\s*['"]([^'"]+)['"]/g)){
   counts.staticViews++;const target='app/Views/'+match[1]+(match[1].endsWith('.php')?'':'.php');
   if(!exact(target))issues.push({file,kind:'view path missing or case mismatch',target});
  }
  for(const match of content.matchAll(/base_url\s*\(\s*['"](assets\/[^'"]+)['"]/g)){
   counts.staticAssets++;const target='public/'+match[1].split('?')[0];
   if(!exact(target))issues.push({file,kind:'asset path missing or case mismatch',target});
  }
  const ns=content.match(/^namespace\s+([^;]+);/m),cls=content.match(/^(?:(?:final|abstract|readonly)\s+)?class\s+(\w+)/m);
  if(ns&&cls&&ns[1].startsWith('App\\')&&!file.includes('Migrations')){
   counts.appClasses++;const target='app/'+ns[1].slice(4).replaceAll('\\','/')+'/'+cls[1]+'.php';
   if(!exact(target))issues.push({file,kind:'class filename case mismatch',target});
  }
 }
}
const env=Object.fromEntries(fs.readFileSync('.env','utf8').split(/\r?\n/).filter(l=>!l.trim().startsWith('#')&&l.includes('=')).map(l=>{const i=l.indexOf('=');return[l.slice(0,i).trim(),l.slice(i+1).trim().replace(/^(['"])(.*)\1$/,'$2')];}));
const environment={};
for(const key of ['CI_ENVIRONMENT','app.baseURL','app.forceGlobalSecureRequests','cookie.secure','database.default.DBDebug'])environment[key]=env[key]??'(using config default)';
environment.databaseHostIsLocal=['localhost','127.0.0.1'].includes(env['database.default.hostname']);
environment.databaseUserIsRoot=env['database.default.username']==='root';
environment.databasePasswordConfigured=Boolean(env['database.default.password']);
fs.writeFileSync('writable/receipt-review/deployment-audit.json',JSON.stringify({counts,issues,environment},null,2));
console.log(JSON.stringify({counts,issues,environment},null,2));
