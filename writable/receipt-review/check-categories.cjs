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
 if(process.argv.includes('--pagination')) {
  const paginationResults=[],runtimeErrors=[];
  await send('Runtime.enable');
  ws.addEventListener('message',event=>{const message=JSON.parse(event.data);if(message.method==='Runtime.exceptionThrown')runtimeErrors.push(message.params.exceptionDetails.exception?.description);});
  for(const state of ['first','middle','last','single','empty','large'])for(const width of [320,390,768,1440])for(const dark of [false,true]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/pagination-${state}.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.app-pagination')"))break;await delay(100);}
   await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.querySelector('.app-pagination').scrollIntoView({block:'center',behavior:'instant'})`);
   assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,`Page overflow ${state} ${width}`);
   assert.equal(await evaluate("[...document.querySelectorAll('.app-pagination .pg-control')].filter(el=>el.getClientRects().length).every(el=>el.getBoundingClientRect().height>=44&&el.getBoundingClientRect().width>=44)"),true,'Touch target size');
   assert.equal(await evaluate("[...document.querySelectorAll('.app-pagination a')].every(el=>new URL(el.href).searchParams.get('keyword')==='Vitamin & Zinc')"),true,'Filters preserved');
   if(['first','middle','last','large'].includes(state)) {
    assert.equal(await evaluate("getComputedStyle(document.querySelector('.pg-pages')).display==='none'"),width<=700);
    assert.equal(await evaluate("document.querySelectorAll('.pg-current[aria-current=page]').length"),1);
    await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});
    await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});
    await evaluate("[...document.querySelectorAll('.app-pagination a')].find(el=>el.getClientRects().length).focus()");
    assert.equal(await evaluate("getComputedStyle(document.activeElement).outlineStyle"),'solid');
   }
   if(state==='middle'&&[390,1440].includes(width)) {
    await evaluate('document.activeElement.blur()');
    const shot=await send('Page.captureScreenshot',{format:'png'});fs.writeFileSync(path.join(dir,`pagination-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
   }
   await evaluate("document.documentElement.classList.add('large-controls','high-contrast');document.documentElement.dataset.textSize='extra-large';document.documentElement.dataset.density='spacious'");
   assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,`Large preference overflow ${state} ${width}`);
   assert.equal(await evaluate("[...document.querySelectorAll('.pg-control')].filter(el=>el.getClientRects().length).every(el=>el.scrollWidth<=el.clientWidth+1)"),true,`Button text overflow ${state} ${width}`);
   await evaluate("document.documentElement.classList.remove('large-controls','high-contrast');document.documentElement.dataset.textSize='normal';document.documentElement.dataset.density='comfortable'");
   paginationResults.push({state,width,dark});
  }
  assert.deepEqual(runtimeErrors,[]);
  fs.writeFileSync(path.join(dir,'pagination-checks.json'),JSON.stringify(paginationResults,null,2));
  console.log(`Passed ${paginationResults.length} pagination layouts: page boundaries, mobile, themes, filter URLs, focus and large controls.`);return;
 }
 const checks=[];
 for(const variant of ['categories','categories-empty']) {
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/${variant}.html`});
  for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete' && !!document.querySelector('.mobile-record-ready')"))break;await delay(100);}
  assert.equal(await evaluate("!!document.querySelector('.mobile-record-ready')"),true);
  for(const width of [320,375,390,600,700,768,1024,1440]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
   for(const dark of [false,true]) {
    await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);
    await delay(80);
    const state=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,cardMode:getComputedStyle(document.querySelector('.categories-table')).display==='block',tableOverflow:document.querySelector('.table-wrap').scrollWidth>document.querySelector('.table-wrap').clientWidth+2,hintVisible:!document.querySelector('.table-scroll-hint').hidden,buttonHeights:[...document.querySelectorAll('.category-search-actions .btn,.categories-table .record-actions .btn')].map(x=>x.getBoundingClientRect().height),clippedPills:[...document.querySelectorAll('.cat-pill')].some(x=>x.scrollWidth>x.clientWidth+2||x.getBoundingClientRect().right>x.closest('td').getBoundingClientRect().right+1),productWidths:[...document.querySelectorAll('.category-products')].map(x=>({width:x.getBoundingClientRect().width,row:x.closest('tr').getBoundingClientRect().width})),input:document.querySelector('#categorySearch').getBoundingClientRect().toJSON(),search:document.querySelector('.category-search-actions').getBoundingClientRect().toJSON(),title:document.querySelector('.record-title')?.getBoundingClientRect().toJSON()})`);
    assert.equal(state.overflow,false,`${variant} page overflow ${width}`);
    assert.equal(state.clippedPills,false,`${variant} clipped product ${width}`);
    if(width<=700) {
     assert.equal(state.cardMode,true);
     assert.equal(state.tableOverflow,false,`${variant} mobile card overflow ${width}`);
     assert.equal(state.hintVisible,false,`${variant} unexpected mobile scroll hint ${width}`);
     for(const product of state.productWidths)assert.ok(product.width>product.row*.75,'Products need a full-width row');
    } else assert.equal(state.cardMode,false);
    if(width<=600){assert.ok(state.search.top>=state.input.bottom,'Search actions must be below input');assert.ok(state.buttonHeights.every(h=>h>=44),'Touch targets too small');}
    checks.push({variant,width,dark,overflow:state.overflow,cardMode:state.cardMode});
    if(variant==='categories'&&[390,768,1440].includes(width)) {
     const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
     fs.writeFileSync(path.join(dir,`categories-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
    }
   }
  }
 }
 fs.writeFileSync(path.join(dir,'category-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} category layouts: mobile/tablet/desktop, light/dark, long products and empty state.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
