const fs = require('fs');
const path = require('path');
const http = require('http');
const assert = require('assert/strict');
const {spawn} = require('child_process');
const root = process.cwd(), dir = __dirname;
const profile = path.join(dir, 'chrome-profile-' + process.pid + '-' + Date.now());
const delay = ms => new Promise(resolve => setTimeout(resolve, ms));
let chrome, ws, server;
(async () => {
 server = http.createServer((req,res) => {
  const url = new URL(req.url, 'http://localhost');
  const base = url.pathname.startsWith('/assets/') ? path.join(root,'public') : dir;
  const file = path.resolve(base,'.' + decodeURIComponent(url.pathname));
  if (!file.startsWith(base + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {res.writeHead(404);res.end();return;}
  res.setHeader('Content-Type', file.endsWith('.css')?'text/css':file.endsWith('.js')?'text/javascript':'text/html; charset=utf-8');
  res.end(fs.readFileSync(file));
 });
 await new Promise(resolve => server.listen(0,'127.0.0.1',resolve));
 const port=server.address().port;
 fs.mkdirSync(profile,{recursive:true});
 chrome=spawn('C:/Program Files/Google/Chrome/Application/chrome.exe',[
  '--headless=new','--disable-gpu',...(process.argv.includes('--cash-scroll')?[]:['--hide-scrollbars']),'--no-first-run','--no-default-browser-check','--disable-extensions',
  '--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'
 ],{stdio:'ignore',windowsHide:true});
 let debugPort;
 for(let i=0;i<100;i++) {
  const portFile=path.join(profile,'DevToolsActivePort');
  if(fs.existsSync(portFile)){debugPort=Number(fs.readFileSync(portFile,'utf8').split('\n')[0]);break;}
  await delay(100);
 }
 if(!debugPort)throw new Error('Headless Chrome did not start');
 const tabs=await (await fetch('http://127.0.0.1:'+debugPort+'/json', {signal:AbortSignal.timeout(5000)})).json();
 ws=new WebSocket(tabs.find(tab=>tab.type==='page').webSocketDebuggerUrl);
 await new Promise((resolve,reject)=>{const timer=setTimeout(()=>reject(Error('Browser connection timed out')),10000);ws.addEventListener('open',()=>{clearTimeout(timer);resolve();},{once:true});ws.addEventListener('error',()=>{clearTimeout(timer);reject(Error('Browser connection failed'));},{once:true});});
 let serial=0; const pending=new Map();
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(pending.has(m.id)){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(Error(m.error.message)):p.resolve(m.result);}});
 const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++serial;const timer=setTimeout(()=>{pending.delete(id);reject(Error('Browser timed out: '+method));},15000);pending.set(id,{resolve:r=>{clearTimeout(timer);resolve(r);},reject:e=>{clearTimeout(timer);reject(e);}});ws.send(JSON.stringify({id,method,params}));});
 const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
 await send('Page.enable');
 if(process.argv.includes('--branch-trash')) {
  let cases=0;
  for(const view of ['list','trash','empty','legacy']) {
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/branch-trash-${view}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('.mobile-record-ready')"))break;await delay(80);}
   if(view!=='empty') {
    const rows=await evaluate("[...document.querySelectorAll('.content form[method=post]')].filter(f=>f.querySelector('[name=branch_id]')).map(f=>({branch:f.querySelector('[name=branch_id]').value,confirm:f.dataset.confirm,csrf:!!f.querySelector('[name=csrf_token]')}))");
    assert.deepEqual(rows.map(r=>r.branch),view==='list'?['1','2']:['1','1','2','2']);assert.equal(rows.every(r=>r.csrf),true);
    assert.equal(rows.every(r=>r.confirm.includes(r.branch==='1'?'Main Branch':'Patag Branch')),true);
    if(view!=='list')assert.equal(await evaluate("!!document.querySelector('form[action*=\"force-delete\"] [name=branch_id]')"),true);
    await evaluate("window.confirmMessage='';window.confirm=message=>{window.confirmMessage=message;return false};const f=[...document.querySelectorAll('.content form[method=post]')].find(f=>f.querySelector('[name=branch_id]'));f.addEventListener('submit',e=>{window.trashCancelled=e.defaultPrevented;e.preventDefault()});f.requestSubmit()");
    assert.equal(await evaluate('window.trashCancelled'),true);assert.match(await evaluate('window.confirmMessage'),/Main Branch/);
   }
   if(view==='legacy'){
    assert.equal(await evaluate("!!document.querySelector('[name=legacy_restore]')"),true);
    assert.equal(await evaluate("!!document.querySelector('form[action*=\"force-delete\"] [name=legacy_delete]')"),true);
   }
   for(const width of [320,390,768,1440])for(const dark of [false,true]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(80);
    assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,`${view} ${width} overflow`);
    assert.equal(await evaluate("[...document.querySelectorAll('.content table .btn')].some(e=>e.scrollWidth>e.clientWidth+1)"),false,`${view} ${width} clipped buttons`);
    if([390,1440].includes(width)&&view==='trash'){const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(path.join(dir,`branch-trash-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
    cases++;
   }
  }
  console.log(`Passed ${cases} branch Trash layouts, branch IDs, scoped confirmation, restore forms, legacy controls and cancellation. No actions submitted.`);return;
 }
 if(process.argv.includes('--catalog')) {
  let cases=0;
  for(const view of ['list','selected','empty','search-empty']) {
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/catalog-${view}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('.catalog-page')"))break;await delay(80);}
   if(view==='selected') {
    assert.equal(await evaluate("document.querySelector('[name=stock]').value"),'0');
    assert.equal(await evaluate("document.querySelector('[name=expiration_date]').value"),'');
    assert.equal(await evaluate("!!document.querySelector('form[method=post] [name=csrf_token]')"),true);
    assert.equal(await evaluate("!!document.querySelector('form[method=post] [name=branch_id]')"),false);
   }
   if(view==='list')assert.equal(await evaluate("document.querySelectorAll('.catalog-product').length"),12);
   for(const width of [320,390,768,1440])for(const dark of [false,true]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(100);
    assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,`${view} ${width} overflow`);
    assert.equal(await evaluate("[...document.querySelectorAll('.catalog-product,.catalog-fields .field')].some(e=>e.scrollWidth>e.clientWidth+1)"),false,`${view} ${width} clipped content`);
    if(view!=='selected' && width>600)assert.equal(await evaluate("(()=>{const input=document.querySelector('#catalog-search').getBoundingClientRect();const button=document.querySelector('.catalog-search-row button').getBoundingClientRect();return Math.abs(input.top-button.top)<1 && Math.abs(input.height-button.height)<1})()"),true,`${view} ${width} search alignment`);
    assert.equal(await evaluate("document.querySelector('.catalog-page-header>.btn').getBoundingClientRect().height<52"),true,`${view} ${width} stretched header action`);
    if(view.includes('empty'))assert.equal(await evaluate("!!document.querySelector('.catalog-empty-actions a[href$=\"products/create\"]')"),true,`${view} create action`);
    if([390,1440].includes(width)){const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(path.join(dir,`catalog-${view}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
    cases++;
   }
   if(view==='empty' || view==='search-empty') {
    for(const width of [390,1440])for(const density of ['compact','spacious'])for(const dark of [false,true]) {
     await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
     await evaluate(`document.documentElement.dataset.density='${density}';document.documentElement.dataset.textSize='extra-large';document.documentElement.dataset.accent='purple';document.documentElement.classList.toggle('dark',${dark})`);await delay(80);
     assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,`${view} ${width} ${density} preferences overflow`);
     if(width>600)assert.equal(await evaluate("(()=>{const input=document.querySelector('#catalog-search').getBoundingClientRect();const button=document.querySelector('.catalog-search-row button').getBoundingClientRect();return Math.abs(input.top-button.top)<1 && Math.abs(input.height-button.height)<1})()"),true,`${view} ${density} preferences search alignment`);
     cases++;
    }
    await evaluate("delete document.documentElement.dataset.density;delete document.documentElement.dataset.textSize;delete document.documentElement.dataset.accent");
   }
  }
  console.log(`Passed ${cases} catalog layouts and preference cases, aligned search controls, header actions, empty states, branch form defaults and CSRF checks. No actions submitted.`);return;
 }
 if(process.argv.includes('--cashier-stock')) {
  const errors=[];await send('Runtime.enable');
  ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/table-actions-cashier-stock.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('.stock-update-form')"))break;await delay(80);}
  assert.deepEqual(await evaluate("[...document.querySelector('.stock-update-form select').options].map(o=>o.textContent)"),['Add stock','Remove stock']);
  assert.equal(await evaluate("[...document.querySelectorAll('.stock-update-form')].every(f=>f.querySelector('[name=csrf_token]')&&f.querySelector('[name=stock_token]')&&f.querySelector('[name=remarks]').required)"),true);
  await evaluate("(()=>{const f=document.querySelector('.stock-update-form');const input=f.querySelector('[name=stock]');input.value=5;input.dispatchEvent(new Event('input'));})()");
  assert.match(await evaluate("document.querySelector('.stock-preview').textContent"),/8 \+ 5 = 13/);
  await evaluate("(()=>{const mode=document.querySelector('.stock-update-form select');mode.value='remove';mode.dispatchEvent(new Event('change'));})()");
  assert.match(await evaluate("document.querySelector('.stock-preview').textContent"),/8 − 5 = 3/);
  await evaluate("(()=>{const f=document.querySelector('.stock-update-form');f.querySelector('[name=remarks]').value='Inventory check';const input=f.querySelector('[name=stock]');input.value=9;input.dispatchEvent(new Event('input'));})()");
  assert.equal(await evaluate("document.querySelector('.stock-update-form').checkValidity()"),false);
  await evaluate("(()=>{const input=document.querySelector('.stock-update-form [name=stock]');input.value=8;input.dispatchEvent(new Event('input'));})()");
  assert.equal(await evaluate("document.querySelector('.stock-update-form').checkValidity()"),true);
  for(const width of [320,390,768,1440])for(const dark of [false,true]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
   await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.querySelector('.stock-update-form').scrollIntoView({block:'center'})`);await delay(100);
   assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,`${width} page overflow`);
   assert.equal(await evaluate("[...document.querySelectorAll('.stock-update-form')].some(f=>f.scrollWidth>f.clientWidth+1)"),false,`${width} clipped form`);
   if([390,1440].includes(width)){const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`cashier-stock-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
  }
  assert.deepEqual(errors,[]);
  console.log('Passed 8 cashier stock layouts plus add/remove previews, zero-balance, over-removal, CSRF and reason checks. No actions submitted.');return;
 }
 if(process.argv.includes('--expiry-actions')) {
  let cases=0;const errors=[];
  await send('Runtime.enable');
  ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
  for(const view of ['replace','remove','deactivate','history','cashier-history','inactive-history','admin-list','cashier-list']) {
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/expiry-action-${view}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('.mobile-record-ready')"))break;await delay(80);}
   if(view.startsWith('cashier') || view==='inactive-history')assert.equal(await evaluate("!!document.querySelector('a[href*=\"action=deactivate\"]')"),false,'Cashier/inactive deactivation link');
   if(['history','admin-list'].includes(view))assert.equal(await evaluate("!!document.querySelector('a[href*=\"action=deactivate\"]')"),true,'Admin action link');
   if(['replace','remove','deactivate'].includes(view)){
    assert.equal(await evaluate("document.querySelector('form[method=post]').checkValidity()"),false,'Confirmation and reason required');
    assert.equal(await evaluate("!!document.querySelector('input[name=csrf_token]') && !!document.querySelector('input[name=resolution_token]')"),true);
   }
   for(const width of [320,390,768,1440])for(const dark of [false,true]){
    await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(80);
    assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,`${view} ${width} page overflow`);
    assert.equal(await evaluate("[...document.querySelectorAll('.expiry-action-page input:not([type=hidden]),.expiry-action-page select,.expiry-action-page textarea')].filter(e=>e.getBoundingClientRect().width).some(e=>e.getBoundingClientRect().right>innerWidth+1)"),false,`${view} ${width} clipped form`);
    if(width===390 || width===1440){const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`expiry-action-${view}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
    cases++;
   }
  }
  assert.deepEqual(errors,[]);
  console.log(`Passed ${cases} expiry action layouts, role visibility and confirmation/CSRF checks. No stock actions submitted.`);return;
 }
 if(process.argv.includes('--sale-correction')) {
  let cases=0;const errors=[];
  await send('Runtime.enable');
  ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
  for(const view of ['percentage','fixed','missing','plain']) {
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/sale-correction-${view}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!window.PharxmacoDiscount && !!document.getElementById('correctionForm')"))break;await delay(80);}
   for(const width of [320,390,768,1440])for(const dark of [false,true]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(60);
    if(view!=='missing')await evaluate("document.querySelector('.qty-input').value='2';document.querySelector('.qty-input').dispatchEvent(new Event('input'))");
    assert.equal(await evaluate('document.documentElement.scrollWidth>innerWidth+1'),false,`${view} ${width} overflow`);
    if(view!=='missing')assert.equal(await evaluate("document.getElementById('settlementPanel').getBoundingClientRect().right<=innerWidth+1"),true,`${view} ${width} settlement clipped`);
    if(view!=='missing')assert.equal(await evaluate("getComputedStyle(document.getElementById('settlementConfirmationLabel')).color"),'rgb(146, 64, 14)',`${view} ${width} readable settlement label`);
    if(view==='percentage' && [390,1440].includes(width)) {
     const height=await evaluate('document.documentElement.scrollHeight');
     const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true,clip:{x:0,y:0,width,height,scale:1}});
     fs.writeFileSync(path.join(dir,`sale-correction-live-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    cases++;
   }
   assert.equal(await evaluate("!!document.querySelector('input[name=csrf_token]')"),true);
   assert.equal(await evaluate("!!document.querySelector('[name=discount_revision]')"),true);
   if(view==='missing') {
    assert.equal(await evaluate("[...document.querySelectorAll('.qty-input')].every(e=>e.readOnly)"),true);continue;
   }
   assert.equal(await evaluate("document.querySelector('.qty-input').readOnly"),false);
   await evaluate("document.querySelector('.qty-input').value='2';document.querySelector('.qty-input').dispatchEvent(new Event('input'))");
   const preview=await evaluate("({total:document.getElementById('newTotalCell').textContent,discount:document.getElementById('discountTotalCell')?.textContent,final:document.getElementById('finalTotalCell').textContent,diff:document.getElementById('settlementDifference').value,disabled:document.querySelector('.save-btn').disabled,panel:document.getElementById('settlementPanel').hidden})");
   assert.equal(preview.total,'₱320.00');
   assert.equal(preview.final,view==='percentage'?'₱256.00':view==='fixed'?'₱276.00':'₱320.00');
   assert.equal(preview.diff,view==='percentage'?'8000':'10000');
   assert.equal(preview.disabled,true);assert.equal(preview.panel,false);
   await evaluate("document.getElementById('settlementConfirmed').click()");
   assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),false);
   await evaluate("document.querySelector('.qty-input').value='0';document.querySelector('.qty-input').dispatchEvent(new Event('input'))");
   assert.equal(await evaluate("document.getElementById('settlementConfirmed').checked"),false);
   assert.equal(await evaluate("document.getElementById('finalTotalCell').textContent"),'₱120.00');
   if(view!=='plain')assert.equal(await evaluate("document.getElementById('correctionPreviewNotice').textContent.includes('minimum')"),true);
   assert.equal(await evaluate("document.getElementById('settlementAction').textContent.startsWith('Pay back')"),true);
   await evaluate("document.getElementById('paymentMethod').value='gcash';document.getElementById('paymentMethod').dispatchEvent(new Event('change'))");
   assert.equal(await evaluate("document.getElementById('referenceNo').required"),true);
   await evaluate("document.querySelectorAll('.qty-input').forEach(e=>{e.value='0';e.dispatchEvent(new Event('input'))})");
   assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),true);
   await evaluate("document.querySelectorAll('.qty-input').forEach(e=>{e.value=e.dataset.original;e.dispatchEvent(new Event('input'))});document.getElementById('paymentMethod').value='cash';document.getElementById('paymentMethod').dispatchEvent(new Event('change'))");
   assert.equal(await evaluate("document.getElementById('settlementPanel').hidden"),true);
   assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),false);
   if(view==='percentage') {
    const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,'sale-correction-percentage-1440-dark.png'),Buffer.from(shot.data,'base64'));
   }
  }
  assert.deepEqual(errors,[]);
  console.log(`Passed ${cases} correction layouts, discount previews, settlement confirmation, minimum checks, reference requirements and CSRF checks. No corrections submitted.`);return;
 }
 if(process.argv.includes('--exchange')) {
  let cases=0;
  const exchangeErrors=[];
  await send('Runtime.enable');
  ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')exchangeErrors.push(m.params.exceptionDetails.exception?.description);});
  for(const view of ['form','review','even','payout','receipt']) {
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/exchange-${view}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('.exchange-page,.receipt-paper')"))break;await delay(80);}
   for(const width of [320,390,768,1440]) for(const dark of [false,true]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(70);
    const result=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth+1,clipped:[...document.querySelectorAll('.exchange-page input:not([type=hidden]),.exchange-page select')].filter(e=>e.getBoundingClientRect().width).some(e=>e.getBoundingClientRect().right>innerWidth+1),title:document.querySelector('h1')?.textContent})`);
    assert.equal(result.overflow,false,`${view} ${width} page overflow`);
    assert.equal(result.clipped,false,`${view} ${width} controls clipped`);
    if(width===390 || width===1440){const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`exchange-${view}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
    cases++;
   }
   if(view==='form') {
    assert.equal(await evaluate("document.querySelector('form').getAttribute('method')"),'post');
    assert.equal(await evaluate("!!document.querySelector('input[name=csrf_token]')"),true);
    await evaluate("document.querySelector('[name=\"replacements[2]\"]').value='1';const search=document.getElementById('exchangeSearch');search.value='Paracetamol';search.dispatchEvent(new Event('input'))");
    assert.equal(await evaluate("[...document.querySelectorAll('[data-exchange-product]')].filter(r=>getComputedStyle(r).display!=='none').length"),2);
    await evaluate("(()=>{const search=document.getElementById('exchangeSearch');search.value='no matching product';search.dispatchEvent(new Event('input'));})()");
    assert.equal(await evaluate("[...document.querySelectorAll('[data-exchange-product]')].filter(r=>getComputedStyle(r).display!=='none').length"),1);
    await evaluate("document.querySelector('[name=\"replacements[2]\"]').value='0';document.getElementById('exchangeSearch').dispatchEvent(new Event('input'))");
    assert.equal(await evaluate("[...document.querySelectorAll('[data-exchange-product]')].filter(r=>getComputedStyle(r).display!=='none').length"),0);
    assert.equal(await evaluate("getComputedStyle(document.getElementById('exchangeNoMatches')).display!=='none'"),true);
    assert.equal(await evaluate("document.getElementById('exchangeSearchStatus').textContent"),'0 products shown');
    await evaluate("document.getElementById('exchangeSearch').value='';document.getElementById('exchangeSearch').dispatchEvent(new Event('input'))");
    assert.equal(await evaluate("document.getElementById('exchangeNoMatches').hidden"),true);
   }
   if(view==='receipt') {
    assert.equal(await evaluate("document.querySelector('.receipt-paper').textContent.includes('Credit applied')"),true);
    await send('Emulation.setEmulatedMedia',{media:'print'});
    for(const size of ['58','80']) {
     await evaluate(`document.body.dataset.paperWidth='${size}'`);
     assert.equal(await evaluate("document.querySelector('.receipt-paper').scrollWidth<=document.querySelector('.receipt-paper').clientWidth+1"),true);
    }
    await send('Emulation.setEmulatedMedia',{media:''});
   }
  }
  assert.deepEqual(exchangeErrors,[],'Exchange JavaScript errors');
  console.log(`Passed ${cases} exchange layouts, search, CSRF form and receipt print checks.`);return;
 }
 if(process.argv.includes('--table-actions')||process.argv.includes('--branch-actions')||process.argv.includes('--trash-tables')) {
  const errors=[];let cases=0;
  await send('Runtime.enable');await send('Emulation.setFocusEmulationEnabled',{enabled:true});
  ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
  for(const view of (process.argv.includes('--branch-actions')?['branches']:process.argv.includes('--trash-tables')?['trash','category-trash','supplier-trash','discount-trash']:['users','history','products','trash','categories','suppliers','branches'])) {
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/table-actions-${view}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.table-action-icon')"))break;await delay(100);}
   assert.equal(await evaluate("[...document.querySelectorAll('.content table td .btn')].every(b=>b.textContent.trim().length>0)"),true,'Visible labels retained');
   assert.equal(await evaluate("[...document.querySelectorAll('.table-action-icon')].every(i=>i.getAttribute('aria-hidden')==='true'&&i.getAttribute('focusable')==='false')"),true);
   assert.equal(await evaluate("[...document.querySelectorAll('.content table form')].every(f=>f.method==='post'&&!!f.querySelector('input[type=hidden]'))"),true,'POST and CSRF preserved');
   if(process.argv.includes('--trash-tables')) {
    assert.equal(await evaluate("!!document.querySelector('.content form[action*=\"force-delete\"]')"),true,'Permanent hiding offered');
    assert.equal(await evaluate("[...document.querySelectorAll('.content table form')].every(f=>f.action.includes('/restore/')||f.action.includes('/force-delete/'))"),true,'Trash offers Restore and permanent hiding');
   }
   if(view==='branches') {
    const links=await evaluate("[...document.querySelectorAll('.content table tbody tr')].map(r=>[...r.querySelectorAll('a.btn')].map(a=>({label:a.textContent.trim(),path:new URL(a.href).pathname,branch:new URL(a.href).searchParams.get('branch_id')})))");
    assert.equal(links.length,2);
    links.forEach((row,i)=>{assert.deepEqual(row.map(a=>a.label),['Edit','View inventory','View sales']);assert.ok(row[0].path.endsWith('/admin/branches/edit/'+(i+1)));assert.ok(row[1].path.endsWith('/products'));assert.equal(row[1].branch,String(i+1));assert.equal(row[2].branch,String(i+1));});
    assert.ok(links[0][2].path.endsWith('/admin/reports'));
    assert.ok(links[1][2].path.endsWith('/cashier/sales/history'));
   }
   for(const [width,height] of [[320,780],[390,844],[768,900],[1440,1000]])for(const dark of [false,true]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(70);
    const result=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth+1,small:[...document.querySelectorAll('.content table td .btn')].some(b=>b.getBoundingClientRect().height<${width<=700?44:36}-.5),clipped:[...document.querySelectorAll('.table-action-group,.content table td .btn')].some(b=>b.scrollWidth>b.clientWidth+1)})`);
    assert.equal(result.overflow,false,`${view} ${width} page overflow`);assert.equal(result.small,false,`${view} ${width} small targets`);assert.equal(result.clipped,false,`${view} ${width} clipped controls`);
    if([390,1440].includes(width)&&['users','history','trash','branches'].includes(view)) {
     await evaluate("document.querySelector('.content table').scrollIntoView({block:'start'})");
     const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`table-actions-${view}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    cases++;
   }
   await send('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:false});
   await evaluate("document.documentElement.dataset.textSize='extra-large';document.documentElement.classList.add('large-controls','reduce-motion','high-contrast')");
   assert.equal(await evaluate("[...document.querySelectorAll('.content table td .btn')].every(b=>b.clientWidth>=b.scrollWidth-1&&b.getBoundingClientRect().height>=44)"),true,'Accessible control settings');
   await evaluate("document.querySelector('.content table td .btn').focus()");
   await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Tab'});await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Tab'});
   assert.equal(await evaluate("document.activeElement.tagName!=='svg'"),true);
   if(await evaluate("!!document.querySelector('.content table form[onsubmit]')")) {
    await evaluate("window.confirms=0;window.confirm=()=>{window.confirms++;return false};const f=document.querySelector('.content table form[onsubmit]');f.addEventListener('submit',e=>{window.wasCancelled=e.defaultPrevented;e.preventDefault()});f.requestSubmit()");await delay(100);
    assert.equal(await evaluate('window.confirms'),1);assert.equal(await evaluate('window.wasCancelled'),true,'Confirmation cancellation retained');
   }
   if(process.argv.includes('--trash-tables')) {
    await evaluate("(()=>{window.permanentConfirms=0;window.confirm=()=>{window.permanentConfirms++;return false};const permanentForm=document.querySelector('.content table form[action*=\"force-delete\"]');permanentForm.addEventListener('submit',e=>{window.permanentCancelled=e.defaultPrevented;e.preventDefault()});permanentForm.requestSubmit()})()");
    assert.equal(await evaluate('window.permanentConfirms'),1);
    assert.equal(await evaluate('window.permanentCancelled'),true,'Permanent hiding confirmation retained');
   }
  }
  assert.deepEqual(errors,[]);console.log(`Passed ${cases} table action layouts, labels/icons, CSRF forms, cancellation, keyboard and accessibility settings. No actions submitted.`);return;
 }
 if(process.argv.includes('--alignment')) {
  await send('Emulation.setFocusEmulationEnabled',{enabled:true});
  let cases=0;
  for(const [width,height] of [[320,780],[390,844],[768,900],[1440,1000]])for(const dark of [false,true]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/branch-availability.html`});
   for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.branch-lookup')"))break;await delay(100);}
   await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);
   assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,'Branch page overflow');
   assert.equal(await evaluate("document.querySelectorAll('.branch-lookup-result').length"),2);
   assert.equal(await evaluate("document.querySelector('.branch-lookup-form').classList.contains('auto-filter-enabled')"),false,'Search stays manual');
   if(width===390){const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(path.join(dir,`branch-lookup-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-full.html`});
   for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete'&&document.getElementById('restockControls')?.hidden===false"))break;await delay(100);}
   await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.getElementById('savedSalesForecastTrigger').click()`);
   assert.equal(await evaluate("document.getElementById('forecastModalOverlay').getAttribute('aria-hidden')"),'false');
   assert.equal(await evaluate("document.getElementById('restockSavedTab').getAttribute('aria-selected')"),'true');
   assert.equal(await evaluate("document.querySelectorAll('#restockSaved tr[data-restock-row]:not([hidden])').length"),7);
   assert.equal(await evaluate("document.querySelector('.forecast-modal-body').scrollWidth<=document.querySelector('.forecast-modal-body').clientWidth+1"),true,'Saved sales overflow');
   assert.equal(await evaluate("document.getElementById('restockSaved').textContent.includes('Sep 24, 2026')"),true);
   if(width===390){const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`saved-sales-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));}
   await evaluate("document.getElementById('restockDone').click()");
   assert.equal(await evaluate('document.activeElement.id'),'savedSalesForecastTrigger');
   cases++;
  }
  console.log(`Passed ${cases} branch lookup and saved-revenue UI scenarios, including mobile, dark mode and focus return.`);return;
 }
 if(process.argv.includes('--restock')) {
  const checks=[],errors=[];
  await send('Runtime.enable');
  await send('Emulation.setFocusEmulationEnabled',{enabled:true});
  ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
  const key=async key=>{await send('Input.dispatchKeyEvent',{type:'keyDown',key});await send('Input.dispatchKeyEvent',{type:'keyUp',key});};
  for(const state of ['restock','empty'])for(const [width,height] of [[320,780],[390,844],[768,900],[1024,900],[1440,1000],[844,390]])for(const dark of [false,true]){
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-${state}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&document.getElementById('restockControls')?.hidden===false"))break;await delay(100);}
   await evaluate(`document.documentElement.classList.toggle('dark',${dark});window.submissions=0;document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',e=>{e.preventDefault();window.submissions++}));document.getElementById('forecastModalTrigger').click()`);
   assert.equal(await evaluate("document.getElementById('forecastModalOverlay').getAttribute('aria-hidden')"),'false');
   assert.equal(await evaluate("document.activeElement.id"),'forecastModalClose');
   assert.equal(await evaluate("document.querySelector('.forecast-modal').scrollWidth<=document.querySelector('.forecast-modal').clientWidth+1"),true,'Dialog overflow');
   assert.equal(await evaluate("document.querySelector('.forecast-modal-body').clientHeight>55"),true,'Usable body height');
   assert.equal(await evaluate("document.querySelector('.restock-footer').getBoundingClientRect().bottom<=innerHeight"),true,'Footer visible');
   assert.equal(await evaluate("document.querySelector('.forecast-modal-body').scrollWidth<=document.querySelector('.forecast-modal-body').clientWidth+1"),true,'Body overflow');
   for(const tab of ['restockCurrentTab','restockSavedTab']){
    await evaluate(`document.getElementById('${tab}').click()`);
    assert.equal(await evaluate("document.getElementById('restockPriorityLabel').hidden"),tab==='restockSavedTab');
    if(state==='restock'){
     assert.equal(await evaluate("document.querySelector('.forecast-modal-body').scrollHeight>document.querySelector('.forecast-modal-body').clientHeight"),true,'List scrolls');
     if([390,1440].includes(width)){
      const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`restock-${tab}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
     }
     await evaluate("document.getElementById('restockSearch').value='not-a-real-product';document.getElementById('restockSearch').dispatchEvent(new Event('input',{bubbles:true}))");
     assert.equal(await evaluate("document.getElementById('restockEmpty').hidden"),false);
     await evaluate("document.getElementById('restockEmptyReset').click()");
     assert.equal(await evaluate("document.getElementById('restockEmpty').hidden"),true);
     await evaluate("document.getElementById('restockSearch').value='RESTOCK-24';document.getElementById('restockSearch').dispatchEvent(new Event('input',{bubbles:true}))");
     assert.equal(await evaluate("document.getElementById('restockResultCount').textContent.startsWith('1 of 24')"),true,'SKU search');
     await evaluate("document.getElementById('restockReset').click()");
    }else assert.equal(await evaluate("document.getElementById('restockResultCount').textContent.startsWith('0 of 0')"),true);
   }
   await evaluate("document.getElementById('restockCurrentTab').focus()");await key('ArrowRight');
   assert.equal(await evaluate("document.activeElement.id"),'restockSavedTab');
   await key('Home');assert.equal(await evaluate("document.activeElement.id"),'restockCurrentTab');
   if(state==='restock'){
    await evaluate("document.getElementById('restockPriority').value='unknown';document.getElementById('restockPriority').dispatchEvent(new Event('change',{bubbles:true}))");
    assert.equal(await evaluate("document.getElementById('restockResultCount').textContent.startsWith('6 of 24')"),true,'Priority filtering');
    assert.equal(await evaluate("[...document.querySelectorAll('#restockCurrent tr[data-restock-row]:not([hidden]) .restock-order')].every(x=>x.textContent.includes('Review manually'))"),true);
   }
   await evaluate("document.getElementById('restockEditFilters').focus()");await key('Tab');
   assert.equal(await evaluate("document.activeElement.id"),'forecastModalClose','Focus trap');
   await key('Escape');assert.equal(await evaluate("document.activeElement.id"),'forecastModalTrigger');
   await evaluate("document.getElementById('forecastModalTrigger').click();document.getElementById('restockEditFilters').click()");
   assert.equal(await evaluate("document.activeElement.id"),'forecastGrouping');
   assert.equal(await evaluate('window.submissions'),0,'Modal actions never save');
   checks.push({state,width,height,dark});
  }
  assert.deepEqual(errors,[]);
  fs.writeFileSync(path.join(dir,'restock-checks.json'),JSON.stringify(checks,null,2));
  console.log(`Passed ${checks.length} restock dialog scenarios: tabs, search, priority, empty states, scrolling, themes, keyboard and filter navigation.`);return;
 }
 const checks=[],errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 if(process.argv.includes('--cash-scroll')) {
  for(const width of [390,1440])for(const dark of [false,true]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-full.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.mobile-record-ready')"))break;await delay(100);}
   await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.documentElement.classList.add('sticky-table-headers');const body=document.querySelector('.rp-cash-scroll tbody');const template=body.firstElementChild;for(let i=0;i<40;i++)body.appendChild(template.cloneNode(true));document.querySelector('.rp-cash-scroll').scrollIntoView({block:'center',behavior:'instant'});document.querySelector('.rp-cash-scroll').focus({preventScroll:true})`);
   const before=await evaluate("({height:document.querySelector('.rp-cash-scroll').clientHeight,content:document.querySelector('.rp-cash-scroll').scrollHeight,page:window.scrollY,head:document.querySelector('.rp-cash-scroll th').getBoundingClientRect().top})");
   assert.ok(before.content>before.height);
   assert.ok(before.height<=480);
   await send('Input.dispatchKeyEvent',{type:'keyDown',key:'ArrowDown',code:'ArrowDown',windowsVirtualKeyCode:40});
   await send('Input.dispatchKeyEvent',{type:'keyUp',key:'ArrowDown',code:'ArrowDown',windowsVirtualKeyCode:40});await delay(200);
   assert.ok(await evaluate("document.querySelector('.rp-cash-scroll').scrollTop>0"),'Keyboard scrolls the table');
   assert.equal(await evaluate('window.scrollY'),before.page,'Page stays in place');
   if(width===1440)assert.ok(Math.abs(await evaluate("document.querySelector('.rp-cash-scroll th').getBoundingClientRect().top")-before.head)<2,'Headings stay visible');
   assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true);
   if(width===1440&&!dark){const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,'cash-scroll-desktop.png'),Buffer.from(shot.data,'base64'));}
   await send('Emulation.setEmulatedMedia',{media:'print'});
   assert.equal(await evaluate("getComputedStyle(document.querySelector('.rp-cash-scroll')).maxHeight"),'none');
   await send('Emulation.setEmulatedMedia',{media:''});
  }
  assert.deepEqual(errors,[]);
  console.log('Passed cash table scrolling on mobile/desktop and both themes, with sticky headers, keyboard scrolling and print expansion.');return;
 }
 for(const variant of ['full','empty','mixed']) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-${variant}.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.mobile-record-ready')"))break;await delay(100);}
  assert.equal(await evaluate("typeof Chart!=='undefined' && Object.keys(Chart.instances).length===3"),true,'Charts must render');
  assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),true);
  for(const width of [320,390,600,768,1024,1440]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);await delay(120);
    const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth+1,cardOverflow:[...document.querySelectorAll('.reports-workspace .card,.reports-workspace .kpi')].filter(x=>x.getClientRects().length).some(x=>x.getBoundingClientRect().right>innerWidth+1),smallButtons:[...document.querySelectorAll('.rp-date-presets button,.rp-section-nav a,.rp-actions-row a')].some(x=>x.getBoundingClientRect().height<44),fieldOverflow:[...document.querySelectorAll('#salesReportFilters .input')].some(x=>x.getBoundingClientRect().right>x.closest('.card').getBoundingClientRect().right),emptyCharts:document.querySelectorAll('.rp-chart-empty').length})`);
    assert.equal(state.overflow,false,`${width} report overflow`);assert.equal(state.cardOverflow,false,`${width} card overflow`);assert.equal(state.fieldOverflow,false);assert.equal(state.smallButtons,false);
    assert.equal(state.emptyCharts,variant==='empty'?3:variant==='mixed'?1:0);
    assert.equal(await evaluate("[...document.querySelectorAll('canvas[hidden]')].every(c=>c.getClientRects().length===0)"),true,'Empty chart canvas still takes up space');
    assert.equal(await evaluate("[...document.querySelectorAll('.rp-chart-empty')].every(e=>{const r=e.getBoundingClientRect(),p=e.parentElement.getBoundingClientRect();return r.top>=p.top-1&&r.bottom<=p.bottom+1})"),true,'Empty chart message spills outside its chart');
    if(width<=700)assert.equal(await evaluate("[...document.querySelectorAll('.reports-workspace .mobile-record-ready')].every(t=>t.scrollWidth<=t.clientWidth+2)"),true,'Mobile report table overflow');
    checks.push({variant,width,dark,...state});
    if(variant==='mixed'&&[390,1440].includes(width)) {
     await evaluate("document.getElementById('reportTrends').scrollIntoView({block:'start'})");
     const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`reports-empty-chart-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    if(variant==='full'&&[390,1440].includes(width)) {
     for(const section of ['reportFilters','reportSummary','reportTrends','reportExportHistory']) {
      await evaluate(`document.getElementById('${section}').scrollIntoView({block:'start'})`);await delay(60);
      const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`reports-${section}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
     }
    }
   }
  }
  const href=await evaluate("document.querySelector('[data-report-export]').href");
  for(const [range,days] of [['today',0],['7',6],['30',29],['month',null]]) {
   await evaluate(`document.querySelector('[data-report-range="${range}"]').click()`);
   const dates=await evaluate("({from:document.getElementById('reportDateFrom').value,to:document.getElementById('reportDateTo').value,today:document.querySelector('[data-today]').dataset.today,valid:document.getElementById('salesReportFilters').checkValidity()})");
   assert.equal(dates.to,dates.today);assert.equal(dates.valid,true);
   if(days!==null)assert.equal((Date.parse(dates.to)-Date.parse(dates.from))/86400000,days);else assert.ok(dates.from.endsWith('-01'));
   assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),dates.from==='2026-09-01'&&dates.to==='2026-09-23');
   assert.equal(await evaluate("document.querySelector('[data-report-export]').href"),href,'Export dates remain applied values');
  }
  await evaluate("document.getElementById('salesReportFilters').reset();document.getElementById('reportDateFrom').dispatchEvent(new Event('change',{bubbles:true}))");
  assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),true);
  await evaluate("document.getElementById('forecastGrouping').value='weekly';document.getElementById('forecastGrouping').dispatchEvent(new Event('change',{bubbles:true}))");
  assert.equal(await evaluate("document.getElementById('reportPendingFilters').hidden"),false);
  await evaluate("document.getElementById('forecastModalTrigger').click()");
  assert.equal(await evaluate("document.getElementById('forecastModalOverlay').getAttribute('aria-hidden')"),'false');
  await evaluate("document.getElementById('forecastModalClose').click()");
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'reports-ui-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} report layouts, charts, empty states, date presets, pending-filter notice, export URLs and restock modal. No exports or forms submitted.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
