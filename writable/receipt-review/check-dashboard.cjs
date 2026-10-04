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
 const checks=[],errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 if(process.argv.includes('--visual')) {
  for(const width of [390,1440]) {
   await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
   await send('Page.navigate',{url:`http://127.0.0.1:${port}/dashboard-full.html`});
   for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&window.Chart&&Object.keys(Chart.instances).length===4"))break;await delay(100);}
   await evaluate("document.documentElement.classList.add('dark','reduce-motion');document.documentElement.dataset.accent='purple'");
   await delay(400);
   assert.equal(await evaluate("Object.values(Chart.instances).every(chart=>chart.options.animation===false)"),true);
   await evaluate("document.querySelector('.dash-section-nav a[href=\"#dashTrends\"]').click();document.activeElement.blur()");
   await delay(500);
   assert.equal(await evaluate("document.getElementById('dashTrends').getBoundingClientRect().top >= document.querySelector('.topbar').getBoundingClientRect().bottom-1"),true);
   const trend=await send('Page.captureScreenshot',{format:'png'});
   fs.writeFileSync(path.join(dir,`dashboard-trends-${width}.png`),Buffer.from(trend.data,'base64'));
   await evaluate("document.documentElement.classList.remove('dark');document.querySelector('.dash-stock-details').open=true;document.querySelector('.dash-stock-details').scrollIntoView({block:'start',behavior:'instant'});window.scrollBy(0,-130)");
   await delay(400);
   const stock=await send('Page.captureScreenshot',{format:'png'});
   fs.writeFileSync(path.join(dir,`dashboard-stock-${width}.png`),Buffer.from(stock.data,'base64'));
  }
  assert.deepEqual(errors,[]);
  console.log('Dashboard chart and stock screenshots saved; reduced motion, accent change and section navigation checks passed.');
  return;
 }
 for(const state of ['full','empty','offline'])for(const width of [320,390,768,1024,1440])for(const dark of [false,true]) {
  await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:false});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/dashboard-${state}.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!document.querySelector('.dash-page')"))break;await delay(100);}
  await evaluate(`document.documentElement.classList.toggle('dark',${dark})`);
  await delay(350);
  assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,`Page overflow ${state} ${width} ${dark}`);
  assert.equal(await evaluate("document.querySelectorAll('.dash-page .kpi').length"),9);
  assert.equal(await evaluate("document.querySelectorAll('.dash-page h2').length"),15);
  assert.equal(await evaluate("document.querySelectorAll('.dash-section-nav a').length"),5);
  await evaluate("document.querySelector('.dash-stock-details summary').focus()");
  await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,text:'\r'});
  await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
  assert.equal(await evaluate("document.querySelector('.dash-stock-details').open"),true,'Inventory details open from keyboard');
  assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth+1'),true,`Expanded details overflow ${state} ${width}`);
  if(width<=1024)assert.equal(await evaluate("[...document.querySelectorAll('.dash-page .table-wrap')].every(el=>el.scrollWidth<=el.clientWidth+1)"),true,'Mobile tables should not need sideways scrolling');
  await evaluate("document.querySelector('.dash-stock-details').open=false");
  if(state!=='offline') {
   await evaluate("document.getElementById('tab12m').click()");
   assert.equal(await evaluate("document.getElementById('tab12m').getAttribute('aria-pressed')"),'true');
   assert.equal(await evaluate("document.getElementById('dashTrendPeriod').textContent"),'Last 12 months · net sales');
   assert.equal(await evaluate("document.getElementById('panel12m').classList.contains('active')"),true);
   if(state==='full') {
    await evaluate("document.getElementById('tabTopUnits').click()");
    assert.equal(await evaluate("Chart.getChart('chartTopProducts').data.datasets[0].label"),'Units Sold');
    assert.equal(await evaluate("Chart.getChart('chartDaily').options.plugins.tooltip.backgroundColor===getComputedStyle(document.documentElement).getPropertyValue('--surface').trim()"),true);
    assert.equal(await evaluate('Object.keys(Chart.instances).length'),4);
   } else {
    assert.equal(await evaluate("getComputedStyle(document.getElementById('chartMonthly')).display"),'none');
    assert.equal(await evaluate("[...document.querySelectorAll('.chart-wrap .chart-empty')].every(el=>el.getBoundingClientRect().height<=el.parentElement.getBoundingClientRect().height+1)"),true);
   }
   await evaluate("document.getElementById('tab30d').click()");
  } else {
   assert.equal(await evaluate("[...document.querySelectorAll('.dash-page .chart-tab')].every(el=>el.disabled)"),true);
   assert.equal(await evaluate("document.getElementById('chartLibraryError').hidden"),false);
  }
  await evaluate("window.scrollTo({top:0,behavior:'instant'})");
  if(state==='full'&&[390,1440].includes(width)) {
   const shot=await send('Page.captureScreenshot',{format:'png'});
   fs.writeFileSync(path.join(dir,`dashboard-${width}-${dark?'dark':'light'}.png`),Buffer.from(shot.data,'base64'));
  }
  checks.push({state,width,dark});
 }
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'dashboard-checks.json'),JSON.stringify(checks,null,2));
 console.log(`Passed ${checks.length} dashboard scenarios: responsive tables, keyboard disclosure, chart controls, themes, empty and unavailable charts.`);
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});