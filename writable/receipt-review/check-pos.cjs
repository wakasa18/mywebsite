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
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 for(const variant of ['full','empty']) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/pos-${variant}.html`});
  for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete'&&!!window.PharxmacoModal"))break;await delay(100);}
  for(const [width,height] of [[320,800],[390,844],[768,1024],[1024,768],[1180,820],[1440,900],[844,390]]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);
    for(const panel of width<=1100?['products','cart','payment']:['products']) {
     await evaluate(`document.querySelector('[data-pos-target=${panel}]').click()`);
     await delay(90);
     const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth+1,panels:[...document.querySelectorAll('[data-pos-panel]')].filter(x=>x.getClientRects().length).length,clipped:[...document.querySelectorAll('.prod-card-name')].filter(x=>x.getClientRects().length).some(x=>x.scrollHeight>x.clientHeight+1),innerOverflow:[...document.querySelectorAll('.prod-scroll,.cart-item-list,.pay-scroll')].filter(x=>x.getClientRects().length).some(x=>x.scrollWidth>x.clientWidth+1)})`);
     assert.equal(state.overflow,false,`${width} ${panel} page overflow`);
     assert.equal(state.innerOverflow,false,`${width} ${panel} panel overflow`);
     assert.equal(state.clipped,false,'Medicine name clipped');
     assert.equal(state.panels,width<=1100?1:3);
     checks.push({variant,width,height,dark,panel});
     if(variant==='full'&&[390,768,1440].includes(width)) {
      await evaluate("window.scrollTo(0,0)");
      const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
      fs.writeFileSync(path.join(dir,`pos-${width}-${panel}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
     }
    }
   }
  }
  await evaluate("document.querySelector('[data-pos-target=products]').click();document.getElementById('availableProductsOnly').click()");
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'6');
  await evaluate("document.getElementById('productSearch').value='paracetamol';document.getElementById('productSearch').dispatchEvent(new Event('input'))");await delay(200);
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'1');
  await evaluate("document.getElementById('clearProductSearch').click();document.querySelector('.cat-chip[data-cat=vitamins]').click()");
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'2');
  await evaluate("document.getElementById('productSearch').value='unmatched';document.getElementById('productSearch').dispatchEvent(new Event('input'))");await delay(200);
  assert.equal(await evaluate("document.getElementById('productFilterEmpty').hidden"),false);
  await evaluate("document.getElementById('resetProductFilters').click()");
  assert.equal(await evaluate("document.getElementById('prodCount').textContent"),'8');
  await evaluate("document.querySelector('.prod-card.disabled').click()");
  assert.equal(await evaluate("document.getElementById('quickAddBar').classList.contains('show')"),false);
  await evaluate("document.querySelector('.prod-card:not(.disabled)').click();document.querySelector('[data-quickadd-step=\"1\"]').click()");
  assert.equal(await evaluate("document.getElementById('quickAddQtyHidden').value"),'2');
  await evaluate("document.getElementById('quickAddQty').value=20;document.querySelector('[data-quickadd-step=\"1\"]').click()");
  assert.equal(await evaluate("document.getElementById('quickAddQtyHidden').value"),'20');
  await evaluate("document.getElementById('quickAddQty').value=1;document.querySelector('[data-quickadd-step=\"-1\"]').click()");
  assert.equal(await evaluate("document.getElementById('quickAddQtyHidden').value"),'1');
  await send('Emulation.setDeviceMetricsOverride',{width:320,height:800,deviceScaleFactor:1,mobile:false});
  assert.equal(await evaluate("document.documentElement.scrollWidth>innerWidth+1"),false,'Quick add phone overflow');
  assert.equal(await evaluate("document.getElementById('quickAddBar').scrollWidth>document.getElementById('quickAddBar').clientWidth+1"),false,'Quick add controls overflow');
  if(variant==='full') {await evaluate('window.scrollTo(0,0)');const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(path.join(dir,'pos-320-selected.png'),Buffer.from(shot.data,'base64'));}
  await evaluate("document.getElementById('quickAddCancel').click()");
  assert.equal(await evaluate("document.activeElement===document.querySelector('.prod-card:not(.disabled)')"),true,'Cancel restores product focus');
  await evaluate("document.getElementById('productSearch').focus();for(let i=0;i<10;i++)document.getElementById('productSearch').dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true}))");
  assert.equal(await evaluate("document.querySelector('.prod-card.selected').classList.contains('disabled')"),false,'Arrow navigation skips unavailable products');
  await evaluate("document.getElementById('productSearch').dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));document.getElementById('quickAddQty').dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}))");
  assert.equal(await evaluate("document.activeElement.classList.contains('prod-card')"),true,'Escape restores product focus');
  await evaluate("document.querySelector('[data-pos-target=payment]').click()");
  if(variant==='full') {
   await evaluate("document.getElementById('discountSelect').value='1';document.getElementById('discountSelect').dispatchEvent(new Event('change'));document.querySelector('[data-tender=exact]').click()");
   assert.equal(await evaluate("document.getElementById('amountPaidInput').value"),'191.70');
   await evaluate("document.querySelector('[data-method=gcash]').click()");
   assert.equal(await evaluate("document.getElementById('paymentMethodInput').value"),'gcash');
   assert.equal(await evaluate("document.getElementById('referenceNoInput').placeholder"),'Optional GCash reference');
   assert.equal(await evaluate("document.querySelector('[data-tender=\"100\"]').getClientRects().length"),0);
   await evaluate("document.querySelector('[data-method=cash]').click()");
   assert.equal(await evaluate("document.getElementById('referenceNoInput').required"),false);
  } else assert.equal(await evaluate("document.getElementById('completeSaleBtn').disabled"),true);
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'pos-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} POS layouts plus filtering, selection, quantity bounds, discounts and tender controls. No forms submitted.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
