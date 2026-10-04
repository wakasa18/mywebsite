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
 for(const variant of ['sale','reprint','refund','full-refund','legacy-refund']) {
  console.log('Checking '+variant);
  for(const width of [320,390,768,1440]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/${variant}.html`});
   for(let i=0;i<60;i++){if(await evaluate("document.readyState === 'complete' && !!document.querySelector('.receipt-paper')"))break;await delay(50);}
   const state=await evaluate(`({overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth, peso:document.querySelector('.receipt-grand').textContent.includes('₱'), buttons:[...document.querySelectorAll('.receipt-button')].every(b=>b.getBoundingClientRect().height>=44), paper:document.querySelector('.receipt-paper').getBoundingClientRect().width})`);
   assert.equal(state.overflow,false,variant+' overflow '+width);assert.equal(state.peso,true);assert.equal(state.buttons,true);
   checks.push({variant,width,...state});
   for (const theme of ['dark', 'light']) {
    await evaluate(`localStorage.setItem('pharxmaco-ui-settings',JSON.stringify({theme:'${theme}',accent:'blue'}));window.dispatchEvent(new StorageEvent('storage',{key:'pharxmaco-ui-settings'}));`);
    const colors = await evaluate(`({paper:getComputedStyle(document.querySelector('.receipt-paper')).backgroundColor,text:getComputedStyle(document.querySelector('.receipt-items td')).color,button:getComputedStyle(document.querySelector('.receipt-button--primary')).backgroundColor,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth})`);
    assert.equal(colors.paper,theme==='dark'?'rgb(22, 30, 46)':'rgb(255, 255, 255)');
    assert.equal(colors.text,theme==='dark'?'rgb(232, 237, 245)':'rgb(19, 43, 59)');
    assert.equal(colors.button,theme==='dark'?'rgb(77, 159, 255)':'rgb(33, 102, 209)');
    assert.equal(colors.overflow,false);
    checks.push({variant,width,theme,...colors});
    if(theme==='dark'&&variant==='refund'&&[390,1440].includes(width)) {
     const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
     fs.writeFileSync(path.join(dir,`refund-dark-${width}.png`),Buffer.from(shot.data,'base64'));
    }
   }
   if((variant==='refund'&&[390,1440].includes(width))||(variant==='sale'&&width===1440)) {
    const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
    fs.writeFileSync(path.join(dir,`${variant}-${width}.png`),Buffer.from(shot.data,'base64'));
   }
  }
 }
 await evaluate("window.printCount=0;window.print=()=>window.printCount++;document.querySelector('[data-print-receipt]').click()");
 assert.equal(await evaluate('window.printCount'),1);
 // Real storage events update another open receipt without reloading it.
 await evaluate(`new Promise(resolve=>{const frame=document.createElement('iframe');frame.id='theme-peer';frame.src='/sale.html';frame.onload=resolve;document.body.append(frame);})`);
 await evaluate(`localStorage.setItem('pharxmaco-ui-settings',JSON.stringify({theme:'dark',accent:'purple'}));`);
 await delay(100);
 assert.equal(await evaluate(`document.querySelector('#theme-peer').contentDocument.documentElement.classList.contains('dark')`),true);
 assert.equal(await evaluate(`document.querySelector('#theme-peer').contentDocument.documentElement.dataset.accent`),'purple');
 await evaluate(`document.querySelector('#theme-peer').remove();window.dispatchEvent(new StorageEvent('storage',{key:'pharxmaco-ui-settings'}));`);
 for(const [accent,rgb] of Object.entries({green:'rgb(54, 201, 133)',purple:'rgb(165, 138, 255)',orange:'rgb(243, 165, 61)'})) {
  await evaluate(`window.dispatchEvent(new CustomEvent('pharxmaco:ui-settings-applied',{detail:{theme:'dark',accent:'${accent}'}}))`);
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.receipt-button--primary')).backgroundColor`),rgb);
 }
 checks.push({themeSync:'real storage event across documents; app settings event; all dark accents'});
 for(const paper of ['58','80']) {
  await evaluate(`document.querySelector('[name="receipt-paper-width"][value="${paper}"]').click()`);
  await send('Emulation.setEmulatedMedia',{media:'print'});
  const state=await evaluate(`({paper:document.querySelector('.receipt-paper').getBoundingClientRect().width, toolbar:getComputedStyle(document.querySelector('.receipt-toolbar')).display, aside:getComputedStyle(document.querySelector('.receipt-help')).display})`);
  assert.ok(Math.abs(state.paper-Number(paper)*96/25.4)<1);assert.equal(state.toolbar,'none');assert.equal(state.aside,'none');
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.receipt-paper')).backgroundColor`),'rgb(255, 255, 255)');
  assert.equal(await evaluate(`getComputedStyle(document.querySelector('.receipt-items td')).color`),'rgb(0, 0, 0)');
  const pdf=await send('Page.printToPDF',{paperWidth:Number(paper)/25.4,paperHeight:14,marginTop:0,marginRight:0,marginBottom:0,marginLeft:0,printBackground:false,displayHeaderFooter:false});
  fs.writeFileSync(path.join(dir,`receipt-${paper}mm.pdf`),Buffer.from(pdf.data,'base64'));
  checks.push({print:paper,...state});
  if(paper==='58') {
   const shot=await send('Page.captureScreenshot',{format:'png'});
   fs.writeFileSync(path.join(dir,'print-58mm.png'),Buffer.from(shot.data,'base64'));
  }
  await send('Emulation.setEmulatedMedia',{media:'screen'});
 }
 await send('Page.navigate',{url:`http://127.0.0.1:${port}/preview.html`});
 for(let i=0;i<60;i++){if(await evaluate("!!document.querySelector('#rp-method') && document.readyState === 'complete'"))break;await delay(100);}
 await evaluate(`document.querySelector('.refund-qty-input').value=1;document.querySelector('.refund-qty-input').dispatchEvent(new Event('input'));document.querySelector('#refund-method').value='gcash';document.querySelector('#refund-method').dispatchEvent(new Event('change'));document.getElementsByName('return_condition[1]')[0].value='damaged';document.getElementsByName('return_condition[1]')[0].dispatchEvent(new Event('change'));`);
 assert.equal(await evaluate("document.querySelector('#rp-method').textContent"),'GCash');
 assert.equal(await evaluate("document.querySelector('#rp-grand-total').textContent"),'₱112.50');
 assert.ok(await evaluate("document.querySelector('#rp-items-body').textContent.includes('Damaged')"));
 for(const dark of [true,false]) {
  await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);
  assert.equal(await evaluate("getComputedStyle(document.querySelector('#rp-items-body td')).color"),dark?'rgb(232, 237, 245)':'rgb(19, 43, 59)');
  assert.equal(await evaluate("getComputedStyle(document.querySelector('.receipt-paper')).backgroundColor"),dark?'rgb(22, 30, 46)':'rgb(255, 255, 255)');
  assert.equal(await evaluate("getComputedStyle(document.querySelector('.receipt-items th')).backgroundColor"),'rgba(0, 0, 0, 0)');
 }
 for(const width of [320,768,1440]) {
  await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
  assert.equal(await evaluate('document.documentElement.scrollWidth>document.documentElement.clientWidth'),false,'Preview overflow '+width);
 }
 const previewShot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});
 fs.writeFileSync(path.join(dir,'preview-1440.png'),Buffer.from(previewShot.data,'base64'));
 checks.push({preview:'quantity, condition, payout updates; 320/768/1440 widths'});
 await evaluate(`localStorage.setItem('pharxmaco-ui-settings',JSON.stringify({theme:'system',accent:'blue'}))`);
 await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-color-scheme',value:'dark'}]});
 await send('Page.navigate',{url:`http://127.0.0.1:${port}/sale.html`});
 for(let i=0;i<60;i++){if(await evaluate("document.readyState === 'complete' && !!document.querySelector('.receipt-page')"))break;await delay(50);}
 assert.equal(await evaluate(`document.documentElement.classList.contains('dark')`),true);
 await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-color-scheme',value:'light'}]});
 await delay(100);
 assert.equal(await evaluate(`document.documentElement.classList.contains('dark')`),false);
 await evaluate(`localStorage.setItem('pharxmaco-ui-settings','invalid json');window.dispatchEvent(new StorageEvent('storage',{key:'pharxmaco-ui-settings'}));`);
 assert.equal(await evaluate(`document.documentElement.classList.contains('dark')`),false);
 checks.push({systemTheme:'restored before rendering; OS changes applied; malformed settings tolerated'});
 await send('Page.addScriptToEvaluateOnNewDocument',{source:'window.printCount=0;window.print=()=>window.printCount++;'});
 await send('Page.navigate',{url:`http://127.0.0.1:${port}/auto-print.html`});
 for(let i=0;i<60;i++){if(await evaluate('window.printCount === 1'))break;await delay(50);}
 assert.equal(await evaluate('window.printCount'),1,'Auto-print once after load');
 assert.equal(await evaluate('document.body.dataset.paperWidth'),'80','Remember selected paper width');
 checks.push({autoPrint:'one invocation after load; width preference restored'});
 fs.writeFileSync(path.join(dir,'checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} screen/print checks, print button, and peso rendering.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
