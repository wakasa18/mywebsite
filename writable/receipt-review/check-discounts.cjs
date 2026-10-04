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
 const load=async name=>{
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/discount-${name}.html`});
  for(let i=0;i<80;i++){if(await evaluate("document.readyState==='complete' && !!window.PharxmacoDiscount"))break;await delay(50);}
 };
 const checks=[];
 await load('pos');
 await evaluate("document.querySelector('#discountSelect').value='1';document.querySelector('#discountSelect').dispatchEvent(new Event('change'))");
 assert.equal(await evaluate("document.querySelector('#coFinalTotal').textContent"),'\u20b10.50');
 assert.equal(await evaluate("document.querySelector('#amountPaidInput').min"),'0.50');
 await evaluate("document.querySelector('.cart-row').dataset.price='19.90';document.querySelector('.cart-qty-input').value=3;document.querySelector('#discountSelect').value='2';document.querySelector('#discountSelect').dispatchEvent(new Event('change'))");
 assert.equal(await evaluate("document.querySelector('#coFinalTotal').textContent"),'\u20b153.73');
 assert.equal(await evaluate("document.querySelector('#discountWarning').textContent"),'');
 await evaluate("document.querySelector('.cart-qty-input').value=2;document.querySelector('#discountSelect').dispatchEvent(new Event('change'))");
 assert.ok(await evaluate("document.querySelector('#discountWarning').textContent.includes('qualify')"));
 assert.equal(await evaluate("document.querySelector('#completeSaleBtn').disabled"),true);
 checks.push('POS centavo rounding, threshold eligibility and invalid-discount checkout blocking');
 for(const [name,quantity,discount,total] of [['fixed',8,20,30],['fixed',3,20,30],['capped',10,10,40]]) {
  await load(name);
  assert.equal(await evaluate("document.querySelector('.qty-input').readOnly"),true);
  await evaluate(`document.querySelector('.qty-input').value=${quantity};document.querySelector('.qty-input').dispatchEvent(new Event('input'))`);
  assert.equal(await evaluate("document.querySelector('#discountTotalCell').textContent.replace(/[^0-9.]/g,'')"),discount.toFixed(2));
  assert.equal(await evaluate("document.querySelector('#finalTotalCell').textContent.replace(/[^0-9.]/g,'')"),total.toFixed(2));
  assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),true);
  checks.push(`${name} quantity ${quantity}: blocked; original discount ${discount}, total ${total} preserved`);
 }
 await load('legacy');
 await evaluate("document.querySelector('.qty-input').value=3;document.querySelector('.qty-input').dispatchEvent(new Event('input'))");
 assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),true);
 assert.ok(await evaluate("document.querySelector('#correctionPreviewNotice').textContent.includes('refund and a new sale')"));
 await evaluate("document.querySelector('.qty-input').value=5;document.querySelector('.qty-input').dispatchEvent(new Event('input'))");
 assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),false);
 checks.push('Legacy quantity changes blocked; unchanged quantities allow notes/payment corrections');
 await load('plain');
 assert.equal(await evaluate("document.querySelector('.qty-input').readOnly"),false);
 await evaluate("document.querySelector('.qty-input').value=3;document.querySelector('.qty-input').dispatchEvent(new Event('input'))");
 assert.equal(await evaluate("document.querySelector('#finalTotalCell').textContent.replace(/[^0-9.]/g,'')"),'30.00');
 assert.equal(await evaluate("document.querySelector('.save-btn').disabled"),false);
 checks.push('Non-discounted sale quantities remain editable');
 fs.writeFileSync(path.join(dir,'discount-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} browser discount scenarios.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
