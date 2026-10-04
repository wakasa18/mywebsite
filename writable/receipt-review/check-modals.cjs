const fs = require('fs');
const path = require('path');
const http = require('http');
const assert = require('assert/strict');
const {spawn} = require('child_process');
const root = process.cwd(), dir = __dirname;
const profile = path.join(dir, 'chrome-profile-' + Date.now());
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
  '--headless=new','--disable-gpu','--hide-scrollbars','--no-first-run','--no-default-browser-check','--disable-extensions',
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
 const checks=[];
 const errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.text+': '+m.params.exceptionDetails.exception?.description);});
 const key=async(key,modifiers=0)=>{await send('Input.dispatchKeyEvent',{type:'keyDown',key,modifiers});await send('Input.dispatchKeyEvent',{type:'keyUp',key,modifiers});};
 for(const [page,modal,trigger,body] of [
  ['held','lowStockModal','reviewLowStockTrigger','.low-stock-list'],
  ['held','heldModalOverlay','heldSalesTrigger','.held-modal-body'],
  ['forecast','forecastModalOverlay','forecastModalTrigger','.forecast-modal-body']
 ]) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/modals-${page}.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete' && !!window.PharxmacoModal"))break;await delay(100);}
  assert.equal(await evaluate('!!window.PharxmacoModal'),true);
  const inertBefore=await evaluate("document.querySelectorAll('[inert]').length");
  for(const [width,height] of [[320,740],[390,844],[768,1024],[1024,768],[1440,900],[844,390]]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.getElementById('${trigger}').click()`);
    await delay(120);
    const state=await evaluate(`(()=>{const m=document.getElementById('${modal}'),d=m.querySelector('[role=dialog]'),b=m.querySelector('${body}');const rect=d.getBoundingClientRect();return {visible:m.getAttribute('aria-hidden')==='false',inert:!!d.closest('[inert]'),focus:m.contains(document.activeElement),overflow:b.scrollWidth>b.clientWidth+2,bodyHeight:b.clientHeight,left:rect.left,right:rect.right,top:rect.top,bottom:rect.bottom,bg:getComputedStyle(d).backgroundColor,backgroundIsolated:document.getElementById('sidebar').inert||!!document.getElementById('sidebar').closest('[inert]'),buttonHeight:m.querySelector('button').getBoundingClientRect().height}})()`);
    assert.equal(state.visible,true,modal+' did not open');
    assert.equal(state.inert,false,modal+' inert dialog');assert.equal(state.focus,true);
    assert.equal(state.overflow,false,`${modal} horizontal overflow ${width}`);
    assert.ok(state.bodyHeight>70,`${modal} scroll area too small ${width}`);
    assert.ok(state.left>=0&&state.right<=width+1&&state.top>=0&&state.bottom<=height+1,`${modal} offscreen ${width}: ${JSON.stringify(state)}`);
    assert.equal(state.backgroundIsolated,true);assert.ok(state.buttonHeight>=44);
    if(modal==='forecastModalOverlay') {
     assert.equal(await evaluate("[...document.querySelectorAll('#forecastModalOverlay .table-wrap')].every(w=>w.getBoundingClientRect().bottom>=w.querySelector('table').getBoundingClientRect().bottom-2)"),true,'Forecast table overlaps following content');
    }
    await key('Tab',8);assert.equal(await evaluate(`document.getElementById('${modal}').contains(document.activeElement)`),true);
    await key('Tab');assert.equal(await evaluate(`document.activeElement===document.getElementById('${modal}').querySelector('button')`),true);
    if(modal==='lowStockModal') {
     await evaluate("document.getElementById('lowStockSearch').value='no match at all';document.getElementById('lowStockSearch').dispatchEvent(new Event('input'))");
     assert.equal(await evaluate("[...document.querySelectorAll('[data-low-stock-row]')].filter(x=>x.getClientRects().length).length"),0);
     assert.equal(await evaluate("document.getElementById('lowStockVisibleCount').textContent"),'0');
     await evaluate("document.getElementById('lowStockSearch').value='';document.getElementById('lowStockSearch').dispatchEvent(new Event('input'))");
     assert.equal(await evaluate("[...document.querySelectorAll('[data-low-stock-row]')].filter(x=>x.getClientRects().length).length"),24);
    }
    if([390,1440].includes(width)) {
     await evaluate(`document.querySelector('#${modal} ${body}').scrollTop=0`);
     const shot=await send('Page.captureScreenshot',{format:'png'});
     fs.writeFileSync(path.join(dir,`${modal}-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
    await key('Escape');
    assert.equal(await evaluate(`document.getElementById('${modal}').getAttribute('aria-hidden')`),'true');
    assert.equal(await evaluate(`document.activeElement.id`),trigger);
    assert.equal(await evaluate("document.querySelectorAll('[inert]').length"),inertBefore);
    assert.equal(await evaluate("document.body.style.overflow"),'');
    checks.push({modal,width,height,dark,...state});
   }
  }
  // Backdrop closes, and an existing scroll lock is preserved.
  await evaluate(`document.body.style.overflow='clip';document.getElementById('${trigger}').click();document.getElementById('${modal}').click()`);
  assert.equal(await evaluate("document.body.style.overflow"),'clip');
  await evaluate("document.body.style.overflow=''");
  if(modal==='lowStockModal') {
   await evaluate("document.getElementById('notificationButton').click();document.getElementById('reviewNotificationStock').click()");
   assert.equal(await evaluate("document.getElementById('notificationMenu').classList.contains('open')"),false);
   await evaluate("window.checkoutClicks=0;document.getElementById('completeSaleBtn').disabled=false;document.getElementById('completeSaleBtn').addEventListener('click',e=>{e.preventDefault();e.stopImmediatePropagation();window.checkoutClicks++},true)");
   await key('Enter',2);
   assert.equal(await evaluate('window.checkoutClicks'),0,'Checkout shortcut must be blocked behind a modal');
   await key('Escape');
   assert.equal(await evaluate('document.activeElement.id'),'notificationButton');
   assert.equal(await evaluate("document.getElementById('notificationButton').getAttribute('aria-expanded')"),'false');
  }
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'modal-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} modal layouts, focus trapping/restoration, Escape/backdrop, search, and scroll restoration.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
