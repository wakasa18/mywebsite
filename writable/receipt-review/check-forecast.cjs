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
  if(req.method==='POST'&&url.pathname==='/update-forecast'){
   let body='';req.on('data',chunk=>body+=chunk);req.on('end',()=>{
    const params=new URLSearchParams(body);
    [...params.keys()].forEach(key=>{if(!['branch_id','report_type','report_date_from','report_date_to','forecast_type','forecast_date_from','forecast_date_to'].includes(key))params.delete(key)});
    res.writeHead(303,{Location:'/reports-ui-full.html?'+params.toString()});res.end();
   });return;
  }
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
 const checks=[], errors=[];
 await send('Runtime.enable');
 ws.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.exception?.description);});
 for(const width of [390,1440])for(const dark of [false,true]) {
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-full.html`});
  for(let i=0;i<100;i++){if(await evaluate("document.readyState==='complete'&&!!window.Chart&&Object.keys(Chart.instances).length===3"))break;await delay(100);}
  await evaluate(`document.documentElement.classList.toggle('dark',${dark});window.reviewSubmits=[];document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{event.preventDefault();window.reviewSubmits.push({id:form.id,data:Object.fromEntries(new FormData(form)),button:event.submitter?.textContent.trim()})}));window.beforeForecast=JSON.stringify(Chart.getChart('forecastChart').data);window.beforeExport=document.querySelector('[data-report-export]').href`);
  assert.equal(await evaluate("document.getElementById('salesForecastFilters').classList.contains('auto-filter-enabled')"),false);
  assert.equal(await evaluate("document.getElementById('salesForecastFilters').method"),'post');
  assert.equal(await evaluate("!!document.querySelector('#salesForecastFilters input[name=csrf_token]')"),true,'CSRF token included');
  assert.equal(await evaluate("document.getElementById('salesReportFilters').classList.contains('auto-filter-enabled')"),true,'Other report filters keep their existing automatic behavior');
  await evaluate("document.getElementById('forecastGrouping').value='weekly';document.getElementById('forecastDateFrom').value='2026-08-02';document.getElementById('forecastDateTo').value='2026-09-22';['forecastGrouping','forecastDateFrom','forecastDateTo'].forEach(id=>{const input=document.getElementById(id);input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true}))})");
  await delay(850);
  assert.equal(await evaluate('window.reviewSubmits.length'),0,'Forecast inputs must not automatically submit');
  assert.equal(await evaluate("JSON.stringify(Chart.getChart('forecastChart').data)===window.beforeForecast"),true);
  assert.equal(await evaluate("document.querySelector('[data-report-export]').href===window.beforeExport"),true);
  assert.equal(await evaluate("document.getElementById('forecastPendingFilters').hidden"),false);
  assert.equal(await evaluate("document.getElementById('salesForecastFilters').checkValidity()"),true);
  await evaluate("document.querySelector('#salesForecastFilters button[type=submit]').click()");
  const submissions=await evaluate('window.reviewSubmits');
  assert.equal(submissions.length,1);
  assert.equal(submissions[0].id,'salesForecastFilters');
  assert.equal(submissions[0].button,'Update Forecast');
  assert.equal(submissions[0].data.forecast_type,'weekly');
  assert.equal(submissions[0].data.forecast_date_from,'2026-08-02');
  assert.equal(submissions[0].data.forecast_date_to,'2026-09-22');
  assert.equal(submissions[0].data.report_date_from,'2026-09-01');
  await evaluate("document.getElementById('salesForecastFilters').reset();document.getElementById('forecastGrouping').dispatchEvent(new Event('change',{bubbles:true}))");
  assert.equal(await evaluate("document.getElementById('forecastPendingFilters').hidden"),true);
  await evaluate("document.getElementById('reportBranch').value='1';document.getElementById('reportBranch').dispatchEvent(new Event('change',{bubbles:true}))");
  await delay(650);
  assert.equal(await evaluate("window.reviewSubmits.filter(x=>x.id==='salesReportFilters').length"),1);
  checks.push({width,dark,automaticForecastSubmits:0,manualForecastSubmits:1});
 }
 for(const width of [390,1440])for(const dark of [false,true]) {
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
  await send('Page.navigate',{url:`http://127.0.0.1:${port}/reports-ui-full.html`});
  const waitForReport = async () => {
   for(let i=0;i<100;i++) {
    try { if(await evaluate("document.readyState==='complete'&&!!window.Chart&&Object.keys(Chart.instances).length===3")) {await delay(300);return;} } catch (_) {}
    await delay(100);
   }
   throw Error('Report did not finish loading');
  };
  await waitForReport();
  await evaluate(`document.documentElement.classList.toggle('dark',${dark});document.getElementById('salesForecastFilters').action=location.origin+'/update-forecast';document.getElementById('salesForecastFilters').dataset.returnUrl=location.origin+'/reports-ui-full.html';document.getElementById('forecastGrouping').value='weekly';document.getElementById('forecastGrouping').dispatchEvent(new Event('change',{bubbles:true}));window.scrollTo({top:window.scrollY+document.getElementById('salesForecastFilters').getBoundingClientRect().top-100,behavior:'instant'})`);
  await delay(200);
  const before=await evaluate("document.getElementById('salesForecastFilters').getBoundingClientRect().top");
  await evaluate("document.querySelector('#salesForecastFilters button[type=submit]').click()");
  await delay(300);
  await waitForReport();
  assert.equal(await evaluate("new URLSearchParams(location.search).get('forecast_type')"),'weekly');
  const after=await evaluate("document.getElementById('salesForecastFilters').getBoundingClientRect().top");
  assert.ok(Math.abs(after-before)<3,`Forecast viewport moved at ${width}px: ${before} to ${after}`);
  assert.equal(await evaluate("sessionStorage.getItem('reports:forecast-scroll')"),null,'Saved position is consumed once');
  await send('Page.reload');
  await delay(300);
  await waitForReport();
  const refreshed=await evaluate("document.getElementById('salesForecastFilters').getBoundingClientRect().top");
  assert.ok(Math.abs(refreshed-after)<3,`Regular refresh moved view: ${after} to ${refreshed}`);
 }
 console.log('Passed 4 forecast position scenarios: POST/303/GET restores section position and subsequent refresh retains it.');
 assert.deepEqual(errors,[]);
 fs.writeFileSync(path.join(dir,'forecast-manual-checks.json'),JSON.stringify(checks,null,2));
 console.log('Passed 4 manual forecast scenarios: no automatic submission, explicit button submits selected dates, chart/export unchanged before submit, other filters preserved.');
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{ws?.close();chrome?.kill();server?.close();});
